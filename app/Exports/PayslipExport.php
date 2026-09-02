<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\PaySlip;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PayslipExport implements FromCollection, WithHeadings
{
    protected $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $request = $this->data;

        $month = (!empty($request->filter_month) && $request->filter_month !== '--')
            ? str_pad($request->filter_month, 2, '0', STR_PAD_LEFT)
            : date('m');
        $year = !empty($request->filter_year) ? $request->filter_year : date('Y');
        $formate_month_year = $year . '-' . $month;

        $query = PaySlip::with('employees')
            ->where('salary_month', $formate_month_year)
            ->where('created_by', Auth::user()->creatorId());

        $canExportAll = Auth::user()->can('payroll.payslip.export.all') || Auth::user()->type === 'company';
        if (!$canExportAll) {
            $ownEmployee = Employee::where('user_id', Auth::id())->first();
            if ($ownEmployee) {
                $query->where('employee_id', $ownEmployee->id);
            } else {
                return collect([]);
            }
        }

        $result = [];
        foreach ($query->get() as $payslip) {
            $employee = $payslip->employees;
            $result[] = [
                'employee_id' => $employee ? Auth::user()->employeeIdFormat($employee->employee_id) : '',
                'employee_name' => $employee ? ($employee->full_name ?: $employee->name) : '',
                'basic_salary' => Auth::user()->priceFormat($payslip->basic_salary),
                'net_salary' => Auth::user()->priceFormat($payslip->net_payble),
                'status' => $payslip->status == 0 ? 'UnPaid' : 'Paid',
                'account_holder_name' => $employee->account_holder_name ?? '',
                'account_number' => $employee->account_number ?? '',
                'bank_name' => $employee->bank_name ?? '',
                'bank_identifier_code' => $employee->bank_identifier_code ?? '',
                'branch_location' => $employee->branch_location ?? '',
                'tax_payer_id' => $employee->tax_payer_id ?? '',
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            "EMP ID",
            "Name",
            "Salary",
            "Net Salary",
            "Status",
            "Account Holder Name",
            "Account Number",
            "Bank Name",
            "Bank Identifier Code",
            "Branch Location",
            "Tax Payer Id",
        ];
    }
}
