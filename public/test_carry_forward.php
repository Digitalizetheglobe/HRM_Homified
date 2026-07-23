<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = app()->make(\App\Http\Controllers\LeaveController::class); 
$e = \App\Models\Employee::find(10); 
$lt = \App\Models\LeaveType::find(1);

$method = new \ReflectionMethod($c, 'updateCarryForward');
$method->setAccessible(true);
$method->invoke($c, $e, $lt, now()); 

echo json_encode(\DB::table('employee_leave_balances')->where('employee_id', 10)->where('year', 2026)->where('leave_type_id', 1)->get());
