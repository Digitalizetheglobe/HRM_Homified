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
            $leaveSummary = $this->paySlipController->getLeaveAndCompOffSummary($employee, $this->year, $this->month);

            $result[] = [
                'employee_name' => trim(($employee->name ?? '') . ' ' . ($employee->last_name ?? '')),
                'total_week_off' => number_format($figures['week_off_days'], 2),
                'total_absent' => number_format($figures['absent_days'], 2),
                'total_present_days' => number_format($figures['present_days'], 2),
                'total_paid_leave' => number_format($figures['paid_leave_days'], 2),
                'total_leave_taken' => number_format($figures['total_leave_taken'], 2),
                'total_remaining_leave' => number_format($leaveSummary['remaining_leave'], 2),
                'total_comp_off_earned' => number_format($leaveSummary['comp_off_earned'], 2),
                'total_comp_off_used' => number_format($leaveSummary['comp_off_used'], 2),
                'total_remaining_comp_off' => number_format($leaveSummary['comp_off_remaining'], 2),
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Total Week Off',
            'Total Absent',
            'Total Present Days',
            'Total Paid Leave',
            'Total Leave Taken',
            'Total Remaining Leave',
            'Total Comp Off Earned',
            'Total Comp Off Used',
            'Total Remaining Comp Off',
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
            'A' => 25, // Employee Name
            'B' => 16, // Total Week Off
            'C' => 14, // Total Absent
            'D' => 20, // Total Present Days
            'E' => 18, // Total Paid Leave
            'F' => 18, // Total Leave Taken
            'G' => 22, // Total Remaining Leave
            'H' => 22, // Total Comp Off Earned
            'I' => 20, // Total Comp Off Used
            'J' => 24, // Total Remaining Comp Off
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
            'B2:J' . $lastRow => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
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

