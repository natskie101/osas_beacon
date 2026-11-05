<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';
require BASE_PATH . '/app/Support/helpers.php';

App\Support\Autoloader::register();

App\Support\Env::load(BASE_PATH . '/.env');
App\Support\Config::load(BASE_PATH . '/config');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

App\Support\Session::start();

return require BASE_PATH . '/routes/web.php';
