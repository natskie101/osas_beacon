<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';
require BASE_PATH . '/app/Support/helpers.php';

App\Support\Autoloader::register();
App\Support\Env::load(BASE_PATH . '/.env');
App\Support\Config::load(BASE_PATH . '/config');

date_default_timezone_set((string) config('app.timezone', 'UTC'));
