<?php

date_default_timezone_set('Europe/Paris');

// web/index.php
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

// Routing
$app->register(new Spotlab\Sentinel\Controllers\SentinelControllers(), array());

$app->run();
