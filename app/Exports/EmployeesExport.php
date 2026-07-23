<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeesExport implements WithMultipleSheets
{
    protected $selectedFields;

    public function __construct($selectedFields = null)
    {
        $this->selectedFields = $selectedFields;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new EmployeeSheetExport('Active Employees', $this->selectedFields);
        $sheets[] = new EmployeeSheetExport('Inactive Employees', $this->selectedFields);

        return $sheets;
    }
}