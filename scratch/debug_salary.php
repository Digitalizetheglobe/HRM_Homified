<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\PaySlipController;
use Illuminate\Support\Facades\Auth;

$request = new Request();
$request->replace(['datePicker' => '2026-05', 'department_id' => '0']);

Auth::loginUsingId(2);
$controller = app(PaySlipController::class);
$data = $controller->salaryProcessingSearch($request);

echo "Count: " . count($data) . "\n";
if (count($data) == 0) {
    echo "No data found for May 2026\n";
    // Check why
    $employees = \App\Models\Employee::where('created_by', 2)->get();
    echo "Total employees for creator 2: " . $employees->count() . "\n";
} else {
    foreach($data as $row) {
        echo $row[2] . "\n";
    }
}
