<?php defined('PASS') or die('unauthorized access!') ?>
<!DOCTYPE html>
<html lang="<?php echo get_lang() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo __('About') ?> - <?php echo __('PHP URL Shortener') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/editor.md@1.5.0/css/editormd.min.css"/>
</head>
<body>

<div class="container mb-3 mt-3">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="/"><?php echo __('SHORT URL SERVICE') ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar"
                aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar">
            <div class="navbar-nav mr-auto">
                <a class="nav-item nav-link active" href="/about"><?php echo __('About') ?></a>
            </div>
            <div class="navbar-nav">
                <a class="nav-item nav-link" href="https://github.com/crogram/php-app-shorturl" target="_blank">Github</a>
            </div>
        </div>
    </nav>
</div>

<div class="container mt-3 mb-3">
    <div class="card">
        <div class="card-header text-center"><?php echo __('PHP URL Shortener') ?></div>
        <div class="card-body">
            <p><?php echo __('GITHUB'); ?>: <a href="https://github.com/crogram/php-app-shorturl" target="_blank">https://github.com/crogram/php-app-shorturl</a></p>
            <hr />
            <p><?php echo __('More items'); ?>:</p>
            <ul>
                <li><a href="https://github.com/crogram" target="_blank">https://github.com/crogram</a></li>
                <li><a href="https://crogram.org" target="_blank">https://crogram.org</a></li>
                <li><a href="https://github.com/jksdou" target="_blank">Jackson Dou</a></li>
            </ul>
            <hr />
            <p><?php echo __('Friendship links'); ?>: <a href="http://uinote.com" target="_blank">UINOTE</a>, <a href="http://uiisc.tk" target="_blank">UIISC.TK</a>, <a href="http://uiisc.ml" target="_blank">UIISC.ML</a></p>
        </div>
        <div class="card-footer text-muted text-center">
            <p class="py-0 mb-0">&copy <?php echo date('Y');?> <a href="http://uiisc.org" target="_blank">UIISC</a> All Rights Reserved. Powered by <a href="https://crogram.org" target="_blank">CROGRAM</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.2.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

</body>
</html>
