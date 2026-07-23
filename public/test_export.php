<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = new \App\Exports\EmployeeSheetExport('Active Employees', ['name', 'salary']); 
print_r($e->headings()); 
echo 'Done';
