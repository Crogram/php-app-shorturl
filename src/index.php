<?php

error_reporting(E_ALL);

header("X-Powered-By: URL Shortener");
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', __DIR__);
define('CACHE_TYPE', 'file'); // 支持REDIS OR FILE
define('CACHE_REDIS_KEY', ''); // REDIS AUTH KEY
define('CACHE_REDIS_HOST', '127.0.0.1');
define('CACHE_REDIS_PORT', 6379);
define('CACHE_REDIS_PREFIX', 'shorturl:');
define('CACHE_DIR', 'cache'); // FILE模式下缓存目录
define('CACHE_EXPIRE', 3600 * 24 * 7); // 缓存时间，单位秒
define('IS_HTTPS', false); //是否HTTPS
define('VIEW_PATH', 'views'); //视图模板目录
define('PASS', true); // 访问许可
define('SUB_PATH', '/'); // 子目录： `/` OR `/shorturl/` 必须以 `/` 结尾

require __DIR__ . '/library/language.php';
require __DIR__ . '/library/functions.php';
require __DIR__ . '/library/chnroutes/chnroutes.php';

//--- 缓存相关 ---//

function getCacheType()
{
    $engine = CACHE_TYPE;
    if (!in_array(CACHE_TYPE, ['file', 'redis'])) {
        $engine = 'file';
    }
    return strtolower($engine);
}

function getRedisConnect()
{
    $redis = new \Redis();
    $redis->connect(CACHE_REDIS_HOST, CACHE_REDIS_PORT);
    CACHE_REDIS_KEY && $redis->auth(CACHE_REDIS_KEY);
    if ($redis->isConnected()) {
        return $redis;
    }
    throw new RuntimeException("Redis 连接失败!");
}

function putCache($name, $value, $timeout = null)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.data', serialize($value));
    } else if ($engine == 'redis') {
        $redis = getRedisConnect();
        $redis->set(CACHE_REDIS_PREFIX . $name, json_encode($value), $timeout);
    }
}

function hasCache($name)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        return is_file($dir . DIRECTORY_SEPARATOR . $name . '.data');
    } else if ($engine == 'redis') {
        $redis = getRedisConnect();
        return $redis->exists(CACHE_REDIS_PREFIX . $name);
    }
    return false;
}

function getCache($name)
{
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        $raw = @file_get_contents($dir . DIRECTORY_SEPARATOR . $name . '.data');
        return unserialize($raw);
    } else if ($engine == 'redis') {
        $redis = getRedisConnect();
        return json_decode($redis->get(CACHE_REDIS_PREFIX . $name), true);
    }
}

function clearCache($name = null)
{
    $currentTime = time();
    $count = 0;
    $engine = getCacheType();
    if ($engine == 'file') {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . CACHE_DIR;

        if ($name == null) {
            $list = scandir($dir);
            foreach ($list as $file) {
                if ($file == '.' or $file == '..' or !preg_match('/^(url|request)_/i', $file)) {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if ($currentTime - filemtime($path) > CACHE_EXPIRE) {
                    if (preg_match('/^((url|request)_[A-z0-9]+)\.[A-z]+$/', $file, $matches)) {
                        $cache = getCache($matches[1]);
                        $url = $cache['url'];
                        unlink($path) && $count++ && cleanUrlRecord($url);
                        unset($url);
                    }
                }
            }
        } else {
            $path = $dir . DIRECTORY_SEPARATOR . $name . '.data';
            @unlink($path);
        }

    } else if ($engine == 'redis') {
        $redis = getRedisConnect();
        if ($name != null) {
            $redis->del(CACHE_REDIS_PREFIX . $name);
            $count++;
        } else {
            $iterator = null;
            while ($list = $redis->scan($iterator, CACHE_REDIS_PREFIX . '*', 200)) {
                $chunk_keys = array_chunk($list, 50);
                foreach ($chunk_keys as $keys) {
                    $redis->del(...$keys);
                    $count += count($keys);
                }
            }
        }
    }
    return $count;
}

//--- URL转换相关 ---//

function urlHash()
{
    $hash = getRandStr(5);
    while (hasCache('url_' . $hash)) {
        $hash = getRandStr(5);
    }
    return $hash;
}

/**
 * 映射URL和HASH关系
 *
 * @param $url
 * @param $encrypt_type
 * @param $extent
 * @return bool|string
 */
function urlToHash($url, $encrypt_type, $extent)
{
    $hash = urlHash();
    putCache('url_' . $hash, [
        'url' => $url,
        'encrypt_type' => $encrypt_type,
        'extent' => $extent
    ]);
    return $hash;
}

/**
 * 从HASH中还原URL
 *
 * @param $hash
 * @return mixed|string
 */
function hashToUrl($hash)
{
    if (hasCache('url_' . $hash)) {
        $res = getCache('url_' . $hash);
    }
    return isset($res) && !empty($res) ? $res : '';
}

/**
 * URL转短链接
 *
 * @param $url
 * @param string $encrypt_type
 * @param array $extent
 * @return string
 */
function urlToShort($url, $encrypt_type = 'encrypt', $extent = [])
{
    if (!preg_match('/^[A-z]+:\/\//i', $url)) {
        $url = 'http://' . $url;
    }
    $id = urlToHash($url, $encrypt_type, $extent);
    $shortUrl = sprintf('%s://%s/%s/%s', IS_HTTPS ? 'https' : 'http', $_SERVER['HTTP_HOST'], ltrim(SUB_PATH, '/') . 's', $id);
    addUrlRecord($url);
    return ($shortUrl);
}

/**
 * 自增统计URL生成次数
 *
 * @param $url
 */
function addUrlRecord($url)
{
    $cache = [];
    if (hasCache('manage')) {
        $cache = getCache('manage');
    }
    $cache[$url] = isset($cache[$url]) ? $cache[$url] + 1 : 1;
    putCache('manage', $cache);
    addUrlRecordHistory($url);
}

function getUrlRecord($url = false)
{
    $count = 0;
    $cache = [];
    if (hasCache('manage')) {
        $cache = getCache('manage');
    }
    if ($url) {
        $count += $cache[$url];
    } else {
        foreach ($cache as $value) {
            $count += intval($value);
        }
    }

    return $count;
}

function cleanUrlRecord($url = false)
{
    $cache = [];
    if (hasCache('manage')) {
        $cache = getCache('manage');
    }
    if ($url) {
        unset($cache[$url]);
    } else {
        $cache = [];
    }
    putCache('manage', $cache);
}

/**
 * 自增统计URL历史生成次数
 *
 * @param $url
 */
function addUrlRecordHistory($url)
{
    $cache = [];
    if (hasCache('history')) {
        $cache = getCache('history');
    }
    $cache['count'] = isset($cache['count']) ? $cache['count'] + 1 : 1;
    $cache['url'][] = $url;
    putCache('history', $cache);
}

function getUrlRecordHistory()
{
    $cache = [];
    if (hasCache('history')) {
        $cache = getCache('history');
    }
    return isset($cache['count']) && !empty($cache['count']) ? $cache['count'] : 0;
}

//--- 加密相关 ---//

function charCodeAt($str, $index)
{
    $char = mb_substr($str, $index, 1, 'UTF-8');
    if (mb_check_encoding($char, 'UTF-8')) {
        $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
        return hexdec(bin2hex($ret));
    } else {
        return null;
    }
}

function uchr($codes)
{
    if (is_scalar($codes)) {
        $codes = func_get_args();
    }

    $str = '';
    foreach ($codes as $code) {
        $buf = html_entity_decode('&#' . $code . ';', ENT_NOQUOTES, 'UTF-8');
        $buf == '&#' . $code . ';' && ($buf = mb_convert_encoding('&#' . intval($code) . ';', 'UTF-8', 'HTML-ENTITIES'));
        $str .= $buf;
    }
    return $str;
}

function aaEncode($javascript)
{
    $b = [
        "(c^_^o)",
        "(ﾟΘﾟ)",
        "((o^_^o) - (ﾟΘﾟ))",
        "(o^_^o)",
        "(ﾟｰﾟ)",
        "((ﾟｰﾟ) + (ﾟΘﾟ))",
        "((o^_^o) +(o^_^o))",
        "((ﾟｰﾟ) + (o^_^o))",
        "((ﾟｰﾟ) + (ﾟｰﾟ))",
        "((ﾟｰﾟ) + (ﾟｰﾟ) + (ﾟΘﾟ))",
        "(ﾟДﾟ) .ﾟωﾟﾉ",
        "(ﾟДﾟ) .ﾟΘﾟﾉ",
        "(ﾟДﾟ) ['c']",
        "(ﾟДﾟ) .ﾟｰﾟﾉ",
        "(ﾟДﾟ) .ﾟДﾟﾉ",
        "(ﾟДﾟ) [ﾟΘﾟ]"
    ];
    $r = "ﾟωﾟﾉ= /｀ｍ´）ﾉ ~┻━┻   //*´∇｀*/ ['_']; o=(ﾟｰﾟ)  =_=3; c=(ﾟΘﾟ) =(ﾟｰﾟ)-(ﾟｰﾟ); ";
    if (preg_match('/ひだまりスケッチ×(365|３５６)\s*来週も見てくださいね[!！]/', $javascript)) {
        $r .= "X=_=3; ";
        $r .= "\r\n\r\n    X / _ / X < \"来週も見てくださいね!\";\r\n\r\n";
    }
    $r .= "(ﾟДﾟ) =(ﾟΘﾟ)= (o^_^o)/ (o^_^o);" .
        "(ﾟДﾟ)={ﾟΘﾟ: '_' ,ﾟωﾟﾉ : ((ﾟωﾟﾉ==3) +'_') [ﾟΘﾟ] " .
        ",ﾟｰﾟﾉ :(ﾟωﾟﾉ+ '_')[o^_^o -(ﾟΘﾟ)] " .
        ",ﾟДﾟﾉ:((ﾟｰﾟ==3) +'_')[ﾟｰﾟ] }; (ﾟДﾟ) [ﾟΘﾟ] =((ﾟωﾟﾉ==3) +'_') [c^_^o];" .
        "(ﾟДﾟ) ['c'] = ((ﾟДﾟ)+'_') [ (ﾟｰﾟ)+(ﾟｰﾟ)-(ﾟΘﾟ) ];" .
        "(ﾟДﾟ) ['o'] = ((ﾟДﾟ)+'_') [ﾟΘﾟ];" .
        "(ﾟoﾟ)=(ﾟДﾟ) ['c']+(ﾟДﾟ) ['o']+(ﾟωﾟﾉ +'_')[ﾟΘﾟ]+ ((ﾟωﾟﾉ==3) +'_') [ﾟｰﾟ] + " .
        "((ﾟДﾟ) +'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ ((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+" .
        "((ﾟｰﾟ==3) +'_') [(ﾟｰﾟ) - (ﾟΘﾟ)]+(ﾟДﾟ) ['c']+" .
        "((ﾟДﾟ)+'_') [(ﾟｰﾟ)+(ﾟｰﾟ)]+ (ﾟДﾟ) ['o']+" .
        "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ];(ﾟДﾟ) ['_'] =(o^_^o) [ﾟoﾟ] [ﾟoﾟ];" .
        "(ﾟεﾟ)=((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟДﾟ) .ﾟДﾟﾉ+" .
        "((ﾟДﾟ)+'_') [(ﾟｰﾟ) + (ﾟｰﾟ)]+((ﾟｰﾟ==3) +'_') [o^_^o -ﾟΘﾟ]+" .
        "((ﾟｰﾟ==3) +'_') [ﾟΘﾟ]+ (ﾟωﾟﾉ +'_') [ﾟΘﾟ]; " .
        "(ﾟｰﾟ)+=(ﾟΘﾟ); (ﾟДﾟ)[ﾟεﾟ]='\\\\'; " .
        "(ﾟДﾟ).ﾟΘﾟﾉ=(ﾟДﾟ+ ﾟｰﾟ)[o^_^o -(ﾟΘﾟ)];" .
        "(oﾟｰﾟo)=(ﾟωﾟﾉ +'_')[c^_^o];" .
        "(ﾟДﾟ) [ﾟoﾟ]='\\\"';" .
        "(ﾟДﾟ) ['_'] ( (ﾟДﾟ) ['_'] (ﾟεﾟ+";
    $r .= "(ﾟДﾟ)[ﾟoﾟ]+ ";

    for ($i = 0; $i < mb_strlen($javascript); $i++) {
        $n = charCodeAt($javascript, $i);
        $t = "(ﾟДﾟ)[ﾟεﾟ]+";
        if ($n <= 127) {
            $t .= preg_replace_callback('/[0-7]/', function ($c) use ($b) {
                return $b[$c[0]] . "+ ";
            }, ((string) decoct($n)));
        } else {
            if (preg_match('/[0-9a-f]{4}$/', '000' . ((string) dechex($n)), $result)) {
                $m = $result[0];
            } else {
                $m = '';
            }
            $t .= "(oﾟｰﾟo)+ " . preg_replace_callback('/[0-9a-f]/i', function ($c) use ($b) {
                return $b[hexdec($c[0])] . "+ ";
            }, $m);
        }
        $r .= $t;
    }

    $r .= "(ﾟДﾟ)[ﾟoﾟ]) (ﾟΘﾟ)) ('_');";
    return $r;

}

//--- Response 相关 ---//

function json($msg, $code = 200, $data = [])
{
    $format = [
        'msg' => $msg,
        'code' => $code,
        'data' => $data
    ];
    return json_encode($format, JSON_UNESCAPED_UNICODE);
}

function view($name, $vars = [])
{
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . VIEW_PATH . DIRECTORY_SEPARATOR . $name . '.php';
    extract($vars);
    @include $path;
}

function abort($status = 404)
{
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . $status . '.html';
    if (is_file($path)) {
        @include $path;
    } else {
        echo $status;
    }
    die();
}

function route($uri, Closure $_route)
{
    if (empty($_SERVER['PATH_INFO'])) {
        $pathInfo = isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    } else {
        $pathInfo = $_SERVER['PATH_INFO'];
    }

    if (substr($pathInfo, 0, strlen(SUB_PATH)) == SUB_PATH) {
        $pathInfo = '/' . ltrim(substr($pathInfo, strlen(SUB_PATH)), '/');
    }

    $pathInfo = preg_replace('/\?.*?$/is', '', $pathInfo);
    if (preg_match('#^' . $uri . '$#', $pathInfo, $matches)) {
        $_route($matches);
        exit(0);
    }
}

/**
 * 获取随机假页面
 * @return string
 */
function getFakePage()
{
    $url_list = [
//        'https://item.jd.com/10000' . (str_pad(rand(0, 9999999), 7, '0')) . '.html',
        'https://item.jd.com/100007460214.html',
    ];
    $url = $url_list[rand(0, count($url_list) - 1)];

    $path = ROOT_PATH . DIRECTORY_SEPARATOR . '/cache/html_' . md5($url);
    if (!is_file($path)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36 Edg/95.0.1020.53');
        $res = curl_exec($ch);
        curl_close($ch);
        file_put_contents($path, $res);
    } else {
        $res = file_get_contents($path);
    }
    return $res;
}

/**
 * 给访客响应短链接跳转
 * @param string $url
 * @param array $encrypt_type
 * @param string $hash
 * @param array $extent
 */
function redirect($url, array $encrypt_type, $hash, array $extent = [])
{
    if (in_array('normal', $encrypt_type)) {
        header('Location: ' . $url);
    } else {
        // no referer 默认必须附加
        $referer = '';
        $once = in_array('once', $encrypt_type); // 是否一次访问
        $script = '<script type="text/javascript">setTimeout(function(){location.href="' . $url . '"}, 500);</script>';
        $title = 'web redirection...';
        $request_id = getRandStr(20);
        $page_html = '';
        $is_auth = in_array('password', $encrypt_type);
        $is_middle_page = false;
        if (in_array('dynamic', $encrypt_type)) {
            $referer = '<meta name="referrer" content="no-referrer" />';
        }

        // 判断是否一次访问(一次访问必须为异步加载，否则由于网速慢或者访客多次刷新导致错误计算)
        if ($once) {
            // print('更新设置: 失效');
            $extent['invalid'] = 1;
            putCache('url_' . $hash, [
                'url' => $url,
                'encrypt_type' => $encrypt_type,
                'extent' => $extent
            ]);
        }

        // 判断是否需要加密
        $javascript = '';
        if (in_array('encrypt', $encrypt_type)) {
            $script = '<script src="' . SUB_PATH . 'request/{{request_id}}" type="text/javascript" charset="utf-8"></script>';
            $javascript = makeRedirectJs($url);
            $script = str_replace('{{request_id}}', $request_id, $script);
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{title}}</title>{{referer}}</head><body>{{script}}</body></html>';
        $html = str_replace('{{title}}', $title, $html);
        $html = str_replace('{{referer}}', $referer, $html);
        $html = str_replace('{{script}}', $script, $html);

        // 判断是否需要密码访问
        if ($is_auth) {
            // 密码访问则清空script，否则会跳转
            $script = '';
            $is_middle_page = true;
        }

        // 判断是否需要附加图文
        $whisper = $whisper_head = $whisper_body = '';
        if (in_array('whisper', $encrypt_type)) {
            // 图文则清空script，否则会跳转
            $script = '';
            $is_middle_page = true;
        }
        // 判断是否仅限大陆访问
        if (in_array('china_only', $encrypt_type)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }
            if (!ip_is_china($ip)) {
                exit(__('Access only to users in mainland China'));
            }
        }

        // 判断是否仅限非大陆访问
        if (in_array('non_china_only', $encrypt_type)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }
            if (ip_is_china($ip)) {
                exit(__('Only access users who are not in mainland China'));
            }
        }

        $iv = 'fb57789c1a994622';
        $mobile_list = [
            0 => 'bf04f7f36555cb565f4ffd92a2361e7c',
            1 => '867e57bd062c7169995dc03cc0541c19',
            2 => 'd3cd8888c2c901914e06c8a19e440cc4',
            3 => '820d75c403bc050dfd71763759c9bad5',
        ];
        $pc_list = [
            0 => '4c8be35e5fe3d8471f378a69f74c0ab6',
            1 => 'a99913111481b4f0bcb70e08e3e99405',
            2 => '615c44ade81b52dff685ad7e4add7010',
        ];
        $encrypt_request = [];
        // 判断是否手机访问
        if (in_array('mobile_only', $encrypt_type)) {
            $method = 'AES-128-CBC'; //加密方法
            $encrypt_request = [];
            foreach ($mobile_list as $mobileId) {
                $encrypt_request[] = openssl_encrypt($request_id, $method, substr($mobileId, 8, 16), 0, $iv);
            }
            $script = '';
            $is_middle_page = true;
        }

        // 判断是否PC访问
        if (in_array('pc_only', $encrypt_type)) {
            $method = 'AES-128-CBC'; //加密方法
            $encrypt_request = [];
            foreach ($pc_list as $pcId) {
                $encrypt_request[] = openssl_encrypt($request_id, $method, substr($pcId, 8, 16), 0, $iv);
            }
            $script = '';
            $is_middle_page = true;
        }

        // 默认均需要展示此页面
        $data = [
            'request_id' => $request_id,
            'is_auth' => $is_auth,
            'encrypt_request' => implode(',', $encrypt_request),
            'is_middle_page' => $is_middle_page
        ];
        view('whisper', $data);
        $whisper = ob_get_clean();
        if (preg_match('#<head>(.*?)<\/head>#is', $whisper, $matches)) {
            $whisper_head = $matches[1];
        }
        if (preg_match('#<body>(.*?)<\/body>#is', $whisper, $matches)) {
            $whisper_body = $matches[1];
        }

        // 判断是否需要伪装页面
        if (in_array('fake_page', $encrypt_type)) {
            $page_html = getFakePage();
            // 清空jquery
            $page_html = preg_replace('#<script[^(src)]+src="[^"]*?jquery[^"]*?"[^>]*>[^<]*</script>#is', '', $page_html);

            // 清空手机端跳转判断
            $page_html = preg_replace('#jump_mobile\(\);#is', '', $page_html);

            // 忽略 JS 错误
            $ignore_error = '<script type="text/javascript">window.onerror = function(){return true};</script>';

            $page_html = preg_replace_callback('#<head>(.*?)<\/head>#is', function ($matches) use ($referer, $script, $whisper_head, $ignore_error) {
                $whisper_head = preg_replace('#<title>.*?</title>#is', '', $whisper_head); // 清空title
                return "<head>{$referer}\n{$script}\n{$whisper_head}\n{$matches[1]}\n{$ignore_error}</head>";
            }, $page_html);
            $page_html = preg_replace_callback('#<body[^>]+>(.*?)<\/body>#is', function ($matches) use ($referer, $script, $whisper_body) {
                return "<body>{$whisper_body}\n{$matches[1]}</body>";
            }, $page_html);
            $whisper = $page_html;
            $is_middle_page = true;
        }

        if ($is_middle_page) {
            $html = $whisper;
        }
        putCache('request_' . $request_id, [
            'js' => aaEncode($javascript),
            'hash' => $hash,
            'clean' => $once
        ], 10);

        echo $html;
    }
}

function responseJavascript($requestId)
{
    $name = 'request_' . $requestId;
    $cache = getCache($name);
    $is_middle_page = false;
    if (empty($cache)) {
        $javascript = 'alert("Invalid request")';
    } else {
        $javascript = empty($cache['js']) ? '' : $cache['js'];
    }

    // 判断是否密码验证
    $data = hashToUrl($cache['hash']);
    if (!is_array($data)) {
        return;
    }
    if (in_array('password', $data['encrypt_type'])) {
        $tmp = isset($_REQUEST['password']) && !empty($_REQUEST['password']) ? $_REQUEST['password'] : '';
        if ($data['extent']['password'] != $tmp) {
            echo json(__("Password verification failed"), 500, '');
            exit;
        } else {
            $javascript = aaEncode(makeRedirectJs($data['url'], 1000));
        }

        $is_middle_page = true;

    }
    if (in_array('whisper', $data['encrypt_type'])) {
        $javascript = makeReturnJs(json_encode($data));
        $is_middle_page = true;
    }
    if (in_array('mobile_only', $data['encrypt_type'])) {
        $javascript = makeReturnJs(json_encode($data));
        $is_middle_page = true;
    }
    if (in_array('pc_only', $data['encrypt_type'])) {
        $javascript = makeReturnJs(json_encode($data));
        $is_middle_page = true;
    }

    // 判断是否需要中间页面
    if ($is_middle_page) {
        echo json('ok', 200, $javascript);
    } else {
        echo $javascript;
    }

    if (isset($cache['clean']) && $cache['clean'] == true) {
        clearCache('url_' . $cache['hash']);
        cleanUrlRecord($data['url']);
    }
    clearCache($name);
}

//--- 入口逻辑 ---//

try {
    ob_start();

    route('/', function () {
        view('home', ['time' => date('Ymd')]);
    });

    route('/about', function () {
        view('about', ['time' => date('Ymd')]);
    });

    route('/s/([A-z0-9]+)', function ($matches) {
        $hash = $matches[1];
        $data = hashToUrl($hash);

        $url = '';
        // 直接重定向
        $encrypt_type = ['normal'];
        $extent = [];
        if (!empty($data['url'])) {
            $url = $data['url'];
            $encrypt_type = $data['encrypt_type'];
            if (isset($data['extent']) && !empty($data['extent'])) {
                $extent = $data['extent'];
                if (isset($extent['invalid']) && $extent['invalid'] == 1) {
                    // print('已失效');
                    abort(__('Link expired'));
                }
            }
        }
        if (empty($url)) {
            abort(404);
        }
        redirect($url, $encrypt_type, $hash, $extent);
    });

    route('/request/([A-z0-9]+)', function ($matches) {
        $request_id = $matches[1];
        responseJavascript($request_id);
    });

    route('/api/link', function ($matches) {
        $url = isset($_REQUEST['url']) && !empty($_REQUEST['url']) ? $_REQUEST['url'] : '';
        $encrypt_type = isset($_REQUEST['encrypt_type']) && !empty($_REQUEST['encrypt_type']) ? $_REQUEST['encrypt_type'] : '["normal"]';
        $extent = isset($_REQUEST['extent']) && !empty($_REQUEST['extent']) ? $_REQUEST['extent'] : [];
        if (null == ($encrypt_type = json_decode($encrypt_type, true))) {
            $response = json(__('Wrong encode_type parameter'), 500);
        } else if (empty($url)) {
            $response = json(__('url cannot be empty'), 500);
        } else if (mb_strlen($url) > 2047) {
            $response = json(__('Too long url'), 500);
        } else if (!empty($extent) && mb_strlen(json_encode($extent)) > 10000) {
            $response = json(__('Too much content'), 500);
        } else {
            $extent = json_decode($extent, true);
            $response = urlToShort($url, $encrypt_type, isset($extent) && !empty($extent) ? $extent : []);
            $response = json(__('Link created successfully'), 200, $response);
        }
        header('Content-type:text/json;charset=utf-8');
        echo $response;
    });

    route('/api/clean', function () {
        $count = clearCache();
        echo json(sprintf('clean %s files', $count), 200);
    });

    route('/404', function () {
        abort(404);
    });

    abort(404);
} catch (\RedisException $exception) {
    ob_clean();
    echo sprintf('Redis连接错误:[%s]%s', $exception->getCode(), $exception->getMessage());
} catch (\Exception $exception) {
    ob_clean();
    echo sprintf('Site Error:[%s]%s', $exception->getCode(), $exception->getMessage());
}
