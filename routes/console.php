<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('portal:migrate', function () {
    $this->info('Running BEACON OSAS database migrations...');
    $this->call('migrate');
})->purpose('Run OSAS portal database migrations');

Artisan::command('portal:seed {--fresh : Wipe and re-seed database} {--minimal : Seed only essential admin accounts}', function () {
    $this->info('Seeding BEACON OSAS database...');
    // Populate argv for seed script
    global $argv;
    $argv = ['artisan'];
    if ($this->option('fresh')) {
        $argv[] = '--fresh';
    }
    if ($this->option('minimal')) {
        $argv[] = '--minimal';
    }
    require base_path('database/seed.php');
})->purpose('Seed OSAS portal demo dataset');
