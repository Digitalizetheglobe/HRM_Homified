<?php
$file = 'c:/xampp/htdocs/hrm_rising/resources/views/employee/edit.blade.php';
$content = file_get_contents($file);

// 1. Fix readonly for HR
$oldReadonly = <<<OLD
@if(\$employee->approval_status === 'approved' && \Auth::user()->type === 'employee')

    @php
        // Prevent form submission by disabling all inputs
        \$readonly = true;
    @endphp
@else
    @php
        \$readonly = false;
    @endphp
@endif

@php
    \$isEmployee = (\Auth::user()->type === 'employee');
OLD;

$newReadonly = <<<NEW
@if(\$employee->approval_status === 'approved' && \Auth::user()->type === 'employee' && !\Auth::user()->isHR())

    @php
        // Prevent form submission by disabling all inputs
        \$readonly = true;
    @endphp
@else
    @php
        \$readonly = false;
    @endphp
@endif

@php
    \$isEmployee = (\Auth::user()->type === 'employee' && !\Auth::user()->isHR());
NEW;

$content = str_replace($oldReadonly, $newReadonly, $content);


// 2. Fix Document links (Education)
$content = str_replace(
    "asset(\$education['document_path'])", 
    "\App\Models\Utility::get_file(\$education['document_path'])", 
    $content
);

// 3. Fix Document links (Employee Documents)
$content = str_replace(
    "asset(str_replace('public/', '', \$employeeDoc->document_value))", 
    "\App\Models\Utility::get_file(\$employeeDoc->document_value)", 
    $content
);


file_put_contents($file, $content);
echo "edit.blade.php patched successfully!\n";
