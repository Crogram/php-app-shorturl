<?php

function getRandStr($len)
{
    $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
    $value = '';
    while (strlen($value) < $len) {
        $value .= substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $len);
    }
    return substr($value, 0, $len);
}

function makeRedirectJs($url, $time = 500)
{
    return 'setTimeout(function(){location.href="' . $url . '"}, ' . $time . ');';
}

function makeReturnJs($code)
{
    return '(function(){return window.atob("' . base64_encode($code) . '");})();';
}
