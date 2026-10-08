<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Create DB if not exists via config
DB::statement("CREATE DATABASE IF NOT EXISTS db_ufinance_test");
config(['database.connections.mysql.database' => 'db_ufinance_test']);
DB::reconnect();

DB::unprepared(file_get_contents(__DIR__.'/ufinance.sql'));
echo "Imported ufinance.sql\n";
