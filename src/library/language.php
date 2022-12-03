<?php
// --- 语言相关  ---//
$lang_package = array(
    'en' => array(),
    'zh' => array(
        'SHORT URL SERVICE'    => '短链接服务',
        'PHP URL Shortener'    => 'PHP 轻量级短链接系统',
        'GENERATE'             => '生成',
        'GITHUB'               => 'Github 地址',
        'About'                => '关于',
        'GENERATE SHORT URL'   => '生成短网址',
        'Quickly generate URL' => '快速生成短网址',
        'Enter URL link'       => '输入URL链接',
        'Generate'             => '生成',
        'normal'               => '原始',
        'no referer'           => '无Referer',
        'encrypt redirect'     => '加密跳转',
        'Fake page'            => '伪装页面',
        'redirect once'        => '阅后即焚',
        'password access'      => '密码访问',
        'whisper text'         => '附加图文',
        'PC access only'       => '仅限PC访问',
        'Mobile access only'   => '仅限手机访问',
        'Forked from'          => '衍生自',
        'More items'           => '更多项目',
        'Friendship links'     => '友情链接',

        'Jump directly to the website'                                        => '直接跳转到目标网站',
        'No Referer parameter'                                                => '无 Referer 参数，目标网站无法获取来源站地址',
        'Encrypted access, anti-crawler'                                      => '加密跳转参数信息，反大部分爬虫抓取探测',
        'Use random news, forums, product website information to fool robots' => '使用随机信息、论坛、商品来骗过机器人爬虫',
        'Jump only once'                                                      => '一次性跳转(阅后即焚)',
        'Password required'                                                   => '将为你生成密码，访问时需要密码验证',
        'Append rich text information'                                        => '附加富文本信息，您可以在此留言并分享给您的其他社交媒体用户',
        'Only PC users can access this page'                                  => '仅限PC用户访问该地址',
        'Only Mobile users can access this page'                              => '仅限手机用户访问该地址',
        'mainland China access only'                                          => '仅限中国大陆访问',
        'Non-mainland China access only'                                      => '仅限非中国大陆访问',

        'Access only to users in mainland China'          => '仅限中国大陆用户访问',
        'Only access users who are not in mainland China' => '仅限非中国大陆用户访问',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '当前站点历史生成链接:url_record_history个，当前有效:url_active_history个',

        'Password verification failed'                  => '密码验证失败',
        'Wrong encode_type parameter'                   => '错误的 encode_type 参数',
        'url cannot be empty'                           => 'URL不能为空',
        'Too long url'                                  => 'URL太长',
        'Too much content'                              => '内容太多',
        'Link expired'                                  => '链接已失效',
        'Link created successfully'                     => '链接创建成功',
        'Links can only be accessed via mobile devices' => '该链接只能通过手机移动设备访问',
    ),
    'ja' => array(
        'SHORT URL SERVICE'              => '短いURLサービス',
        'GENERATE'                       => '生成',
        'GITHUB'                         => 'Github',
        'About'                          => 'ついて',
        'GENERATE SHORT URL'             => '短いURLを生成する',
        'Quickly generate URL'           => 'URLをすばやく生成する',
        'Enter URL link'                 => 'URLリンクを入力します',
        'Generate'                       => '生成',
        'normal'                         => 'デフォルト',
        'no referer'                     => '「Referer」パラメータなし',
        'encrypt redirect'               => '暗号化されたアクセス',
        'Fake Page'                      => '偽のウェブページ',
        'redirect once'                  => '1回限りの訪問',
        'password access'                => 'パスワードの検証',
        'whisper text'                   => '追加テキスト',
        'PC access only'                 => 'PCアクセスのみ',
        'Mobile access only'             => 'モバイルアクセスのみ',
        'mainland China access only'     => '中国本土のユーザーのみがアクセス可能',
        'Non-mainland China access only' => '中国本土以外のユーザーに限定',

        'Jump directly to the website'                                        => 'ターゲットのWebサイトに直接ジャンプします',
        'No Referer parameter'                                                => '「Referer」パラメータがないと、ターゲットWebサイトは送信元ステーションのアドレスを取得できません',
        'Encrypted access, anti-crawler'                                      => '暗号化されたジャンプパラメータ情報、ほとんどのクローラーの検出防止',
        'Use random news, forums, product website information to fool robots' => 'ロボットを欺くためにランダムなニュース、フォーラム、製品のウェブサイト情報を生成する',
        'Jump only once'                                                      => 'リンクには一度しかアクセスできず、非常に安全です',
        'Password required'                                                   => 'リンクのパスワードを生成し、アクセス時に確認します',
        'Append rich text information'                                        => 'テキストメッセージを残すことができます',
        'Only PC users can access this page'                                  => 'このアドレスにアクセスできるのはPCユーザーのみです',
        'Only Mobile users can access this page'                              => 'このアドレスにアクセスできるのは携帯電話ユーザーのみです',

        'Access only to users in mainland China'          => '中国本土のユーザーのみがアクセス可能',//このウェブサイトは中国本土でのみアクセスできます
        'Only access users who are not in mainland China' => '中国本土以外のユーザーに限定',

        'This site generates a total of :url_record_history links，Currently active :url_active_history' => '現在のサイト履歴生成リンク:url_record_historyつのリンク、現在有効:url_active_historyつのリンク',

        'Password verification failed'                  => 'パスワードの確認に失敗しました',
        'Wrong encode_type parameter'                   => '間違ったencode_typeパラメータ',
        'url cannot be empty'                           => 'URLを空にすることはできません',
        'Too long url'                                  => 'URLが長すぎます',
        'Too much content'                              => 'コンテンツが多すぎます',
        'Link created successfully'                     => 'リンクが正常に作成されました',
        'Links can only be accessed via mobile devices' => 'リンクにはモバイルデバイス経由でのみアクセスできます',
    )
);


function get_lang()
{
    $locale = 'en';
    $http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
        "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
        $http_accept_language, $hits, PREG_SET_ORDER);
    if (isset($hits[0])) {
        $locale = $hits[0][1];
    }
    return $locale;
}

function __($name, $_vars = array())
{
    global $lang_package;
    $locale = get_lang();
    if (!isset($lang_package[$locale])) {
        $locale = 'en';
    }
    if (!isset($lang_package[$locale][$name])) {
        $locale = 'en';
    }
    $text = isset($lang_package[$locale][$name]) && !empty($lang_package[$locale][$name]) ? $lang_package[$locale][$name] : $name;
    foreach ($_vars as $name => $value) {
        $text = str_replace(":$name", $value, $text);
    }
    return $text;
}
