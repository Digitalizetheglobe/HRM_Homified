<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

$startDate = Carbon::parse('2026-01-01');
$endDate = now();

echo "Starting sync...\n";

for ($date = $startDate; $date <= $endDate; $date->addDay()) {
    $dateStr = $date->format('Y-m-d');
    Artisan::call('comp-off:process', ['--date' => $dateStr]);
    // echo "Processed $dateStr\n";
}

echo "Sync complete!\n";
