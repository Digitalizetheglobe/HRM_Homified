<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (strpos($tableName, 'loan') !== false || strpos($tableName, 'arrear') !== false || strpos($tableName, 'petrol') !== false) {
        echo $tableName . "\n";
    }
}
