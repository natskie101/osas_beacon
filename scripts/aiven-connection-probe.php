<?php
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
echo 'user:     ' . DB::selectOne('select current_user as u')->u . PHP_EOL;
echo 'server:   ' . DB::selectOne('select version() as v')->v . PHP_EOL;
echo 'tables:   ' . DB::selectOne("select count(*) as c from information_schema.tables where table_schema = 'public'")->c . PHP_EOL;
echo PHP_EOL . 'OK - PDO connection works.' . PHP_EOL;
