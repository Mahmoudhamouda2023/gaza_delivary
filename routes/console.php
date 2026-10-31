<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('delivery:backup', function () {
    if (config('database.default') !== 'sqlite') {
        $this->error('Use a database-native consistent backup for MySQL/MariaDB. See README_AR.md.');

        return 1;
    }
    $dir = storage_path('app/private/backups');
    if (! is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $target = $dir.'/delivery-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
    $pdo = DB::connection()->getPdo();
    $pdo->exec('VACUUM INTO '.$pdo->quote($target));
    @chmod($target, 0600);
    $this->info('Backup created: '.$target);
    $this->warn('Keep a separate protected copy of .env and APP_KEY with the database backup.');

    return 0;
})->purpose('Create a consistent SQLite backup without overwriting any existing file');
