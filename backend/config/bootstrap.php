<?php
// config/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

error_reporting(E_ALL);

if ($_SERVER['APP_ENV'] ?? 'dev' === 'dev') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
}

ini_set('log_errors', '1');

$logDir = dirname(__DIR__).'/var/log';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
ini_set('error_log', $logDir.'/php_errors.log');

use Symfony\Component\ErrorHandler\Debug;
if ($_SERVER['APP_DEBUG'] ?? ($_ENV['APP_DEBUG'] ?? false)) {
    Debug::enable();
}