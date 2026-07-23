<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Load Composer's autoloader
require __DIR__.'/../vendor/autoload.php';

// Boot Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

try {
    $resetDate = date('Y-m-01'); // First day of the current month (e.g. 2026-07-01)
    
    // 1. Delete all earned comp-offs from previous months
    \App\Models\CompOffLeave::where('comp_off_date', '<', $resetDate)->delete();
    
    // 2. Delete all used comp-off leave requests from previous months
    $compOffLeaveTypeId = \App\Models\LeaveType::where('title', 'Comp-Off')->value('id');
    
    if ($compOffLeaveTypeId) {
        \App\Models\Leave::where('leave_type_id', $compOffLeaveTypeId)
            ->where('start_date', '<', $resetDate)
            ->delete();
    }
    
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ccc; max-width: 600px; margin: 20px auto; border-radius: 8px;'>";
    echo "<h2 style='color: green;'>✅ Success!</h2>";
    echo "<p>All employees' Comp-Off balances have been successfully reset to <strong>0</strong>.</p>";
    echo "<p style='color: #555; font-size: 14px;'>Action performed:</p>";
    echo "<ul style='color: #555; font-size: 14px;'>";
    echo "<li>Cleared all earned comp-offs from the system.</li>";
    echo "<li>Cleared all used comp-off leave requests.</li>";
    echo "</ul>";
    echo "<p>You can now delete this script for security.</p>";
    echo "</div>";
} catch (\Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #ffcccc; background-color: #fff0f0; max-width: 600px; margin: 20px auto; border-radius: 8px;'>";
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p>Failed to reset comp-offs:</p>";
    echo "<code>" . $e->getMessage() . "</code>";
    echo "</div>";
}

$kernel->terminate($request, $response);
