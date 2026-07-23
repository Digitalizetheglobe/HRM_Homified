<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$formate_month_year = '2026-06';
$userId = 2; // Assuming company user is ID 2 (or we can just login as id 2)
Auth::loginUsingId(2);

$paylip_employee = \App\Models\PaySlip::select(
    [
        'employees.id',
        'employees.employee_id',
        'employees.name',
        'employees.salary',
        'payslip_types.name as payroll_type',
        'pay_slips.basic_salary',
        'pay_slips.net_payble',
        'pay_slips.id as pay_slip_id',
        'pay_slips.status',
        'employees.user_id',
    ]
)->leftjoin(
    'employees',
    function ($join) use ($formate_month_year) {
        $join->on('employees.id', '=', 'pay_slips.employee_id');
        $join->on('pay_slips.salary_month', '=', \DB::raw("'" . $formate_month_year . "'"));
        $join->leftjoin('payslip_types', 'payslip_types.id', '=', 'employees.salary_type');
    }
)->leftjoin('users', 'users.id', '=', 'employees.user_id')
->where('employees.created_by', \Auth::user()->creatorId())
->where('users.type', 'employee')
->where('employees.salary', '>', 0)
->whereNotNull('employees.salary')
->get();

echo json_encode($paylip_employee);
