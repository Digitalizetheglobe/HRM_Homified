<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Termination;
use App\Models\Resignation;
use App\Models\SalaryArrears;
use App\Models\PetrolAllowance;
use App\Models\LoanDeduction;
use App\Models\EmployeeLoan;
use App\Models\AttendanceEmployee;
use App\Models\Leave as LocalLeave;
use App\Models\OtherDeduction;
use App\Models\SalaryProcessingStatus;
use App\Models\EmployeePayableDay;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Carbon;

class SalaryProcessingExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $year;
    protected $month;
    protected $departmentId;
    protected $paySlipController;

    public function __construct($year, $month, $paySlipController, $departmentId = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->departmentId = $departmentId;
        $this->paySlipController = $paySlipController;
    }

    public function collection()
    {
        $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->whereHas('user', function($query) {
                $query->where('type', 'employee');
            });

        // Filter by company_doj only if it's not null
        // If company_doj is null, include the employee (they might have been added without a joining date)
        $employees->where(function($query) {
            $query->whereNull('company_doj')
                  ->orWhere('company_doj', '<=', date($this->year . '-' . $this->month . '-t'));
        });

        // Filter by department if provided
        if (!empty($this->departmentId) && $this->departmentId !== '0' && $this->departmentId !== '') {
            $employees->where('department_id', (int)$this->departmentId);
        }

        $employees = $employees->get();

        $result = [];

        foreach ($employees as $employee) {
            // Check if employee was terminated or resigned before the month
            $terminationDate = Termination::where('employee_id', $employee->id)
                ->whereDate('termination_date', '<', Carbon::create($this->year, $this->month)->startOfMonth())
                ->exists();

            $resignationDate = Resignation::where('employee_id', $employee->id)
                ->whereDate('resignation_date', '<', Carbon::create($this->year, $this->month)->startOfMonth())
                ->exists();

            if ($terminationDate || $resignationDate) {
                continue;
            }

            if (!empty($employee->company_doj)) {
                try {
                    if (Carbon::parse($employee->company_doj)->gt(Carbon::create($this->year, $this->month)->endOfMonth())) {
                        continue;
                    }
                } catch (\Exception $e) {
                    // keep employee if joining date cannot be parsed
                }
            }

            $figures = $this->paySlipController->calculatePdfSalaryFigures($employee, $this->year, $this->month);

            $monthlyDays = $figures['total_days'];
            $payableDays = $figures['payable_days'];
            $totalLeave = $figures['leave_days'];
            $actualSalary = $figures['gross_salary'];
            $monthlySalary = $figures['gross_salary'];
            $basicPay = $figures['basic'];
            $hra = $figures['hra'];
            $conveyanceAllowance = $figures['conveyance'];
            $specialAllowance = $figures['special'];
            $medicalAllowance = $figures['medical'];
            $salaryArrears = $figures['arrears'];
            $petrolAllowance = $figures['petrol'];
            $grossSalary = $figures['gross_salary'] + $figures['arrears'] + $figures['petrol'];
            $lopDays = $figures['absent_days'];
            $lopDeductionAmount = $figures['absent_deduction'];
            $professionalTax = $figures['pt'];
            $salaryAdvance = $figures['loan'];
            $otherDeductions = $figures['casual_leave_deduction'];
            $netAmountPayable = $figures['total_deductions'];
            $finalPayableSalary = $figures['net_salary'];

            // Status
            $status = SalaryProcessingStatus::getStatus($employee->id, $this->year, $this->month);

            $result[] = [
                'employee_code' => \Auth::user()->employeeIdFormat($employee->employee_id),
                'employee_name' => trim(($employee->name ?? '') . ' ' . ($employee->last_name ?? '')),
                'monthly_days' => number_format($monthlyDays, 2),
                'payable_days' => number_format($payableDays, 2),
                'total_leave' => number_format($totalLeave, 2),
                'actual_salary' => number_format($actualSalary, 2),
                'monthly_salary' => number_format($monthlySalary, 2),
                'basic_pay' => number_format($basicPay, 2),
                'hra' => number_format($hra, 2),
                'conveyance_allowance' => number_format($conveyanceAllowance, 2),
                'special_allowance' => number_format($specialAllowance, 2),
                'medical_allowance' => number_format($medicalAllowance, 2),
                'salary_arrears' => number_format($salaryArrears, 2),
                'petrol_allowance' => number_format($petrolAllowance, 2),
                'gross_salary' => number_format($grossSalary, 2),
                'lop_days' => number_format($lopDays, 2),
                'lop_deduction_amount' => number_format($lopDeductionAmount, 2),
                'professional_tax' => number_format($professionalTax, 2),
                'salary_advance' => number_format($salaryAdvance, 2),
                'other_deductions' => number_format($otherDeductions, 2),
                'net_amount_payable' => number_format($netAmountPayable, 2),
                'final_salary' => number_format($finalPayableSalary, 2),
                'status' => $status,
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Monthly Days',
            'Payable Days',
            'Total Leave',
            'Actual Salary',
            'Monthly Salary',
            'Basic Pay (45%)',
            'HRA (18%)',
            'Conveyance Allowance (3.72%)',
            'Special Allowance (30.37%)',
            'Medical Allowance (2.91%)',
            'Salary Arrears',
            'Petrol Allowance',
            'Gross Salary',
            'LOP Days',
            'LOP Deduction Amount',
            'Professional Tax (PT)',
            'Salary Advance',
            'Casual Leave Deduction',
            'Net Amount Payable',
            'Final Salary',
            'Status',
        ];
    }

    public function title(): string
    {
        $monthName = Carbon::create($this->year, $this->month)->format('F Y');
        return 'Salary Processing ' . $monthName;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee Code
            'B' => 25, // Employee Name
            'C' => 12, // Monthly Days
            'D' => 12, // Payable Days
            'E' => 12, // Total Leave
            'F' => 15, // Actual Salary
            'G' => 15, // Monthly Salary
            'H' => 15, // Basic Pay
            'I' => 12, // HRA
            'J' => 20, // Conveyance Allowance
            'K' => 18, // Special Allowance
            'L' => 15, // Medical Allowance
            'M' => 15, // Salary Arrears
            'N' => 18, // Petrol Allowance
            'O' => 15, // Gross Salary
            'P' => 12, // LOP Days
            'Q' => 18, // LOP Deduction Amount
            'R' => 18, // Professional Tax
            'S' => 15, // Salary Advance
            'T' => 18, // Other Deductions
            'U' => 18, // Net Amount Payable
            'V' => 15, // Final Salary
            'W' => 12, // Status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'], // Blue background
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Data rows styling
            'A2:' . $lastColumn . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Number columns (right align)
            'C2:V' . $lastRow => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            // Monthly Salary, Gross Salary, Net Amount Payable, and Final Salary columns - bold
            'G2:G' . $lastRow => [
                'font' => [
                    'bold' => true,
                ],
            ],
            'O2:O' . $lastRow => [
                'font' => [
                    'bold' => true,
                ],
            ],
            'U2:U' . $lastRow => [
                'font' => [
                    'bold' => true,
                ],
            ],
            'V2:V' . $lastRow => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9'], // Light green background
                ],
            ],
        ];
    }

    private function getSalaryAdvance($employeeId, $year, $month)
    {
        $totalAdvance = LoanDeduction::whereHas('loan', function($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->whereYear('month', $year)
            ->whereMonth('month', $month)
            ->where('is_deducted', true)
            ->sum('emi_amount');

        return $totalAdvance;
    }
}

