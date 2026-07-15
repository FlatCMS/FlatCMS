<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
if (!defined('DATA_PATH')) {
    define('DATA_PATH', BASE_PATH . '/data');
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', BASE_PATH . '/storage');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
}

$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['CACHE_ENABLED'] = 'true';
$_ENV['CACHE_TTL'] = '60';

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

require BASE_PATH . '/vendor/autoload.php';

require APP_PATH . '/Helpers/functions.php';
require APP_PATH . '/Helpers/cache.php';
require APP_PATH . '/Helpers/validation.php';
require APP_PATH . '/Helpers/json.php';
require APP_PATH . '/Helpers/files.php';
