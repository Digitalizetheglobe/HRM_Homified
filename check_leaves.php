<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Leave;

$leaves = Leave::where('start_date', 'like', '2026-06-25%')->get();
foreach($leaves as $l) {
    echo "ID: $l->id, Emp: $l->employee_id, TypeID: $l->leave_type_id, Start: $l->start_date\n";
}
