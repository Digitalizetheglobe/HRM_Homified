<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$leave = DB::table('leaves')->where('id', 150)->first();
$type = DB::table('leave_types')->where('id', $leave->leave_type_id)->first();
echo "Leave ID: " . $leave->id . "\n";
echo "Type Title: [" . $type->title . "]\n";
echo "Is Comp-Off: " . ($type->title === 'Comp-Off' ? 'YES' : 'NO') . "\n";
echo "Total Leave Days: [" . $leave->total_leave_days . "]\n";
echo "Days to Deduct: " . (int)$leave->total_leave_days . "\n";
