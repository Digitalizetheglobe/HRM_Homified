<?php
$file = 'c:/xampp/htdocs/hrm_rising/resources/views/employee/index.blade.php';
$content = file_get_contents($file);

// 1. Fix Create Button
$oldCreate = <<<OLD
    @can('Create Assets')
            <a href="{{ route('employee.create') }}" 
               data-title="{{ __('Create New Employee') }}" 
               class="btn btn-sm btn-primary ">
                <i class="ti ti-plus"></i>
            </a>
    @endcan
OLD;

$newCreate = <<<NEW
    @if(Gate::check('Create Employee') || \Auth::user()->isHR())
            <a href="{{ route('employee.create') }}" 
               data-title="{{ __('Create New Employee') }}" 
               class="btn btn-sm btn-primary ">
                <i class="ti ti-plus"></i>
            </a>
    @endif
NEW;
$content = str_replace($oldCreate, $newCreate, $content);

// 2. Fix Header columns (should appear 2-4 times)
$oldHeaderCondition = "@if (Auth::user()->type != 'hr' && !\$isFinanceAccounts && (Gate::check('Edit Employee') || Gate::check('Delete Employee')))";
$newHeaderCondition = "@if (Auth::user()->type != 'hr' && !\$isFinanceAccounts && (Gate::check('Edit Employee') || Gate::check('Delete Employee') || \Auth::user()->isHR()))";
$content = str_replace($oldHeaderCondition, $newHeaderCondition, $content);

// 3. Fix Edit button
$oldEdit = <<<OLD
                                                                @can('Edit Employee')
                                                                    <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt(\$employee->id)) }}" 
                                                                    class="btn btn-sm btn-icon-only bg-info ms-2">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                @endcan
OLD;

$newEdit = <<<NEW
                                                                @if(Gate::check('Edit Employee') || \Auth::user()->isHR())
                                                                    <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt(\$employee->id)) }}" 
                                                                    class="btn btn-sm btn-icon-only bg-info ms-2">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                @endif
NEW;
$content = str_replace($oldEdit, $newEdit, $content);

// There's a second inactive employees loop that might have the edit button
$oldEditInactive = <<<OLD
                                                        @can('Edit Employee')
                                                            <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt(\$employee->id)) }}"
                                                            class="btn btn-sm btn-icon-only bg-info ms-2">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        @endcan
OLD;

$newEditInactive = <<<NEW
                                                        @if(Gate::check('Edit Employee') || \Auth::user()->isHR())
                                                            <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt(\$employee->id)) }}"
                                                            class="btn btn-sm btn-icon-only bg-info ms-2">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        @endif
NEW;
$content = str_replace($oldEditInactive, $newEditInactive, $content);


file_put_contents($file, $content);
echo "index.blade.php patched for Create and Edit buttons!\n";
