<?php

/**
 * 判断IP是否大陆
 * https://raw.githubusercontent.com/misakaio/chnroutes2/master/chnroutes.txt 下载与根目录
 *
 * @param $ip
 * @return bool
 */
function ip_is_china($ip)
{
    $path = __DIR__ . '/data.txt';
    if (is_file($path)) {
        $ipInt = ip2long($ip);
        $fh = fopen($path, 'r') or exit("Unable to open file chnroutes.txt !");
        while (!feof($fh)) {
            $ipSegment = fgets($fh);
            if (substr($ipSegment, 0, 1) == '#') {
                unset($ipSegment);
                continue;
            }
            list($ipBegin, $type) = explode('/', $ipSegment);
            $ipBegin = ip2long($ipBegin);
            $mask = 0xFFFFFFFF << (32 - intval($type));
            if (intval($ipInt & $mask) == intval($ipBegin & $mask)) {
                unset($raw);
                fclose($fh);
                return true;
            }
            unset($raw);
        }
        fclose($fh);
    }
    return false;
}
