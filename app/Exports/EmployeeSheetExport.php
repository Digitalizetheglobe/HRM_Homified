<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeSheetExport implements FromCollection, WithHeadings, WithTitle
{
    private $type;
    private $selectedFields;

    private $allFields = [
        'employee_id' => 'Employee ID',
        'name' => 'Name',
        'dob' => 'Date of Birth',
        'blood_group' => 'Blood Group',
        'gender' => 'Gender',
        'phone' => 'Phone Number',
        'office_phone_one' => 'Office Phone One',
        'office_phone_two' => 'Office Phone Two',
        'emergency_number' => 'Emergency Number',
        'address' => 'Address',
        'email' => 'Email ID',
        'branch_id' => 'Branch',
        'department_id' => 'Department',
        'designation_id' => 'Designation',
        'education_details' => 'Education Details',
        'experience_details' => 'Experience Details',
        'company_doj' => 'Date of Join',
        'salary' => 'Salary',
        'week_off_day' => 'Week Off Day',
        'education_images' => 'Education Images',
    ];

    public function __construct(string $type, $selectedFields = null)
    {
        $this->type = $type;
        $this->selectedFields = $selectedFields;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = Employee::where('created_by', \Auth::user()->creatorId())
            ->with(['branch', 'department', 'designation']);

        if ($this->type === 'Active Employees') {
            $query->whereNotIn('id', function($q) {
                $q->select('employee_id')->from('terminations');
            });
        } else {
            $query->whereIn('id', function($q) {
                $q->select('employee_id')->from('terminations');
            });
        }

        $employees = $query->get();
        $data = [];
        
        foreach($employees as $employee)
        {
            // Format the employee ID as RIS001, RIS002, etc.
            $formattedId = 'RS' . str_pad($employee->id, 3, '0', STR_PAD_LEFT);
            
            $row = [
                'employee_id' => $formattedId, // Use the formatted ID
                'name' => $employee->full_name,
                'dob' => $employee->dob,
                'blood_group' => $employee->blood_group,
                'gender' => $employee->gender,
                'phone' => $employee->phone,
                'office_phone_one' => $employee->office_phone_one,
                'office_phone_two' => $employee->office_phone_two,
                'emergency_number' => $employee->emergency_number,
                'address' => $employee->address,
                'email' => $employee->email,
                'branch_id' => !empty($employee->branch_id) ? $employee->branch->name : '-',
                'department_id' => !empty($employee->department_id) ? $employee->department->name : '-',
                'designation_id' => !empty($employee->designation_id) ? $employee->designation->name : '-',
                'education_details' => !empty($employee->education_details) ? json_encode($employee->education_details) : '-',
                'experience_details' => !empty($employee->experience_details) ? json_encode($employee->experience_details) : '-',
                'company_doj' => $employee->company_doj,
                'salary' => Employee::employee_salary($employee->salary),
                'week_off_day' => $employee->week_off_day,
                'education_images' => !empty($employee->education_images) ? json_encode($employee->education_images) : '-',
            ];

            if ($this->selectedFields && is_array($this->selectedFields)) {
                $filteredRow = [];
                foreach ($this->selectedFields as $field) {
                    if (array_key_exists($field, $row)) {
                        $filteredRow[$field] = $row[$field];
                    }
                }
                $data[] = $filteredRow;
            } else {
                $data[] = array_values($row); // If no specific fields requested, fall back
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        if ($this->selectedFields && is_array($this->selectedFields)) {
            $headings = [];
            foreach ($this->selectedFields as $field) {
                if (array_key_exists($field, $this->allFields)) {
                    $headings[] = $this->allFields[$field];
                }
            }
            return $headings;
        }

        return array_values($this->allFields);
    }

    public function title(): string
    {
        return $this->type;
    }
}
