
@php
    if (!function_exists('breakAfterWords')) {
        function breakAfterWords($text, $wordsPerLine = 3) {
            $words = explode(' ', $text);
            $lines = array_chunk($words, $wordsPerLine);
            return implode('<br>', array_map('implode', array_fill(0, count($lines), ' '), $lines));
        }
    }
@endphp

@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Attendance List') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance List') }}</li>
@endsection



@push('script-page')
    <script>
        $('input[name="type"]:radio').on('change', function(e) {
            var type = $(this).val();
            if (type == 'monthly') {
                $('.month').addClass('d-block').removeClass('d-none');
                $('.date').addClass('d-none').removeClass('d-block');
            } else {
                $('.date').addClass('d-block').removeClass('d-none');
                $('.month').addClass('d-none').removeClass('d-block');
            }
        });
        $('input[name="type"]:radio:checked').trigger('change');
    </script>

    <script>
        $(document).ready(function () {

            // --- INITIAL STATE: lock department & employee ---
            lockDropdown('#department_id', '{{ __("Select Branch first") }}');
            lockDropdown('#employee_id',   '{{ __("Select Department first") }}');

            // --- PRE-SELECT FROM URL PARAMS ---
            var urlBranch     = '{{ isset($_GET["branch"])     ? $_GET["branch"]     : "" }}';
            var urlDepartment = '{{ isset($_GET["department"]) ? $_GET["department"] : "" }}';
            var urlEmployee   = '{{ isset($_GET["employee"])   ? $_GET["employee"]   : "" }}';

            if (urlBranch) {
                getDepartments(urlBranch, urlDepartment, urlEmployee);
            }

            // --- BRANCH CHANGE ---
            $(document).on('change', '#branch_id', function () {
                var branchId = $(this).val();
                lockDropdown('#department_id', '{{ __("Select Branch first") }}');
                lockDropdown('#employee_id',   '{{ __("Select Department first") }}');
                if (branchId) {
                    getDepartments(branchId, null, null);
                }
            });

            // --- DEPARTMENT CHANGE ---
            $(document).on('change', '#department_id', function () {
                var deptId = $(this).val();
                lockDropdown('#employee_id', '{{ __("Select Department first") }}');
                if (deptId) {
                    getEmployees(deptId, null);
                }
            });

            // --- EXPORT MODAL EVENT ---
            $('#exportAttendanceModal').on('show.bs.modal', function () {
                $('#modal_branch').val($('#branch_id').val() || '');
                $('#modal_department').val($('#department_id').val() || '');
                $('#modal_employee').val($('#employee_id').val() || '');
            });

            $('input[name="export_mode"]').on('change', function () {
                var mode = $(this).val();
                if (mode === 'range') {
                    $('#range_mode_wrapper').removeClass('d-none');
                    $('#specific_mode_wrapper').addClass('d-none');
                    $('#daily_mode_wrapper').addClass('d-none');
                } else if (mode === 'specific') {
                    $('#range_mode_wrapper').addClass('d-none');
                    $('#specific_mode_wrapper').removeClass('d-none');
                    $('#daily_mode_wrapper').addClass('d-none');
                } else if (mode === 'daily') {
                    $('#range_mode_wrapper').addClass('d-none');
                    $('#specific_mode_wrapper').addClass('d-none');
                    $('#daily_mode_wrapper').removeClass('d-none');
                }
            });
        });

        // ---- Helpers ----
        function lockDropdown(selector, placeholder) {
            $(selector)
                .html('<option value="">' + placeholder + '</option>')
                .prop('disabled', true)
                .closest('.filter-select-wrap').addClass('locked');
            // Grey out the step badge
            if (selector === '#department_id') $('#dept-badge').addClass('locked-badge').css('background','#d1d5e0');
            if (selector === '#employee_id')   $('#emp-badge').addClass('locked-badge').css('background','#d1d5e0');
        }

        function unlockDropdown(selector) {
            $(selector)
                .prop('disabled', false)
                .closest('.filter-select-wrap').removeClass('locked');
            // Activate step badge
            if (selector === '#department_id') $('#dept-badge').removeClass('locked-badge').css('background','#6366f1');
            if (selector === '#employee_id')   $('#emp-badge').removeClass('locked-badge').css('background','#6366f1');
        }

        function getDepartments(branchId, preselectDept, preselectEmp) {
            var $select = $('#department_id');
            $select.html('<option value="">{{ __("Loading...") }}</option>').prop('disabled', true);

            $.ajax({
                url: '{{ route("monthly.getdepartment") }}',
                type: 'POST',
                data: { branch_id: branchId, _token: '{{ csrf_token() }}' },
                success: function (data) {
                    var html = '<option value="">{{ __("All Departments") }}</option>';
                    $.each(data, function (key, value) {
                        var sel = (preselectDept && preselectDept == key) ? ' selected' : '';
                        html += '<option value="' + key + '"' + sel + '>' + value + '</option>';
                    });
                    $select.html(html);
                    unlockDropdown('#department_id');

                    if (preselectDept) {
                        getEmployees(preselectDept, preselectEmp);
                    }
                },
                error: function () {
                    lockDropdown('#department_id', '{{ __("Error loading departments") }}');
                }
            });
        }

        function getEmployees(deptId, preselectEmp) {
            var $select = $('#employee_id');
            $select.html('<option value="">{{ __("Loading...") }}</option>').prop('disabled', true);

            $.ajax({
                url: '{{ route("monthly.getemployee") }}',
                type: 'POST',
                data: { department_id: deptId, _token: '{{ csrf_token() }}' },
                success: function (data) {
                    var html = '<option value="">{{ __("All Employees") }}</option>';
                    $.each(data, function (key, value) {
                        var sel = (preselectEmp && preselectEmp == key) ? ' selected' : '';
                        html += '<option value="' + key + '"' + sel + '>' + value + '</option>';
                    });
                    $select.html(html);
                    unlockDropdown('#employee_id');
                },
                error: function () {
                    lockDropdown('#employee_id', '{{ __("Error loading employees") }}');
                }
            });
        }
    </script>

    <style>
        /* ---- Cascading Filter Styles ---- */
        .filter-card {
            border-radius: 12px;
            border: 1px solid #e5e9f0;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 130px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #7a8499;
            letter-spacing: .4px;
            text-transform: uppercase;
            margin: 0;
        }
        .filter-select-wrap {
            position: relative;
        }
        .filter-select-wrap select {
            width: 100%;
            padding: 8px 32px 8px 12px;
            border: 1.5px solid #dde3ef;
            border-radius: 8px;
            background: #fff;
            font-size: 13.5px;
            color: #3a3f5a;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .filter-select-wrap select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.1);
        }
        .filter-select-wrap::after {
            content: '\25BE';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7a8499;
            pointer-events: none;
            font-size: 14px;
        }
        /* Locked state */
        .filter-select-wrap.locked select {
            background: #f5f6fa;
            color: #b0b7c9;
            border-color: #e5e9f0;
            cursor: not-allowed;
        }
        /* Step indicators */
        .filter-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #6366f1;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            margin-right: 5px;
            flex-shrink: 0;
        }
        .filter-step-badge.locked-badge {
            background: #d1d5e0;
        }
        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            padding-bottom: 2px;
        }
        .divider-arrow {
            display: flex;
            align-items: flex-end;
            padding-bottom: 10px;
            color: #c0c6d9;
            font-size: 18px;
            line-height: 1;
        }
        @media (max-width: 767px) {
            .divider-arrow { display: none; }
        }
    </style>
@endpush

@section('action-button')
@endsection
@section('content')
@php
    $isHR = false;
    if (in_array(Auth::user()->type, ['company', 'hr', 'super admin'])) {
        $isHR = true;
    } elseif (Auth::user()->type === 'employee') {
        $employeeForHR = \App\Models\Employee::where('user_id', Auth::user()->id)->first();
        if ($employeeForHR && $employeeForHR->department_id) {
            $hrDepartment = \App\Models\Department::find($employeeForHR->department_id);
            if ($hrDepartment) {
                $deptName = strtolower(trim($hrDepartment->name));
                if (in_array($deptName, ['human resource', 'hr', 'human resources', 'hr department', 'human resource department']) || (strpos($deptName, 'human resource') !== false)) {
                    $isHR = true;
                }
            }
        }
    }
@endphp
    @if (session('status'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('   ') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card filter-card">
                    <div class="card-body py-3 px-4">
                        {{ Form::open(['route' => ['attendanceemployee.index'], 'method' => 'get', 'id' => 'attendanceemployee_filter']) }}

                        {{-- Row 1: Type + Month/Date --}}
                        <div class="filter-row mb-3 pb-3" style="border-bottom: 1px solid #eef0f6; justify-content: flex-end;">
                            <div class="filter-group" style="max-width:200px;">
                                <label>{{ __('Type') }}</label>
                                <div class="d-flex gap-3 pt-1">
                                    <div class="form-check mb-0">
                                        <input type="radio" id="daily" value="daily" name="type" class="form-check-input"
                                            {{ isset($_GET['type']) && $_GET['type'] == 'daily' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="daily" style="text-transform:none;font-size:13px;color:#3a3f5a;">{{ __('Daily') }}</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input type="radio" id="monthly" value="monthly" name="type" class="form-check-input"
                                            {{ isset($_GET['type']) && $_GET['type'] == 'monthly' ? 'checked' : 'checked' }}>
                                        <label class="form-check-label" for="monthly" style="text-transform:none;font-size:13px;color:#3a3f5a;">{{ __('Monthly') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-group month" style="max-width:200px;">
                                <label>{{ __('Month') }}</label>
                                {{ Form::month('month', isset($_GET['month']) ? $_GET['month'] : date('Y-m'), ['class' => 'form-control', 'style' => 'padding:8px 12px;border:1.5px solid #dde3ef;border-radius:8px;font-size:13.5px;']) }}
                            </div>
                            <div class="filter-group date d-none" style="max-width:200px;">
                                <label>{{ __('Date') }}</label>
                                {{ Form::date('date', isset($_GET['date']) ? $_GET['date'] : '', ['class' => 'form-control', 'style' => 'padding:8px 12px;border:1.5px solid #dde3ef;border-radius:8px;font-size:13.5px;']) }}
                            </div>

                            @if (!(\Auth::user()->type != 'employee' || ($isHR && !request()->has('own'))))
                            {{-- Simple action row for employee users on the same line --}}
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-sm btn-primary" title="{{ __('Search') }}" style="height:37px;width:37px;padding:0;border-radius:8px;">
                                    <i class="ti ti-search"></i>
                                </button>
                                <a href="{{ route('attendanceemployee.index') }}" class="btn btn-sm btn-danger" title="{{ __('Reset') }}" style="height:37px;width:37px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="ti ti-trash-off"></i>
                                </a>
                            </div>
                            @endif
                        </div>

                        {{-- Row 2: Cascading Branch → Dept → Employee --}}
                        @if (\Auth::user()->type != 'employee' || ($isHR && !request()->has('own')))
                        <div class="filter-row align-items-end flex-wrap" style="justify-content: flex-end;">

                            {{-- Step 1: Branch --}}
                            <div class="filter-group">
                                <label>
                                    <span class="filter-step-badge">1</span>
                                    {{ __('Branch') }}
                                </label>
                                <div class="filter-select-wrap">
                                    {{ Form::select('branch', $branch, isset($_GET['branch']) ? $_GET['branch'] : '', ['class' => 'form-control', 'id' => 'branch_id', 'style' => 'padding:8px 32px 8px 12px;border:1.5px solid #dde3ef;border-radius:8px;font-size:13.5px;']) }}
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="divider-arrow" style="padding-bottom:8px;">&#8250;</div>

                            {{-- Step 2: Department --}}
                            <div class="filter-group">
                                <label>
                                    <span class="filter-step-badge locked-badge" id="dept-badge">2</span>
                                    {{ __('Department') }}
                                </label>
                                <div class="filter-select-wrap" id="dept-select-wrap">
                                    <select name="department" id="department_id" class="form-control" style="padding:8px 32px 8px 12px;border:1.5px solid #dde3ef;border-radius:8px;font-size:13.5px;" disabled>
                                        <option value="">{{ __('Select Branch first') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="divider-arrow" style="padding-bottom:8px;">&#8250;</div>

                            {{-- Step 3: Employee --}}
                            <div class="filter-group">
                                <label>
                                    <span class="filter-step-badge locked-badge" id="emp-badge">3</span>
                                    {{ __('Employee') }}
                                </label>
                                <div class="filter-select-wrap" id="emp-select-wrap">
                                    <select name="employee" id="employee_id" class="form-control" style="padding:8px 32px 8px 12px;border:1.5px solid #dde3ef;border-radius:8px;font-size:13.5px;" disabled>
                                        <option value="">{{ __('Select Department first') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-sm btn-primary" title="{{ __('Search') }}" style="height:37px;width:37px;padding:0;border-radius:8px;">
                                    <i class="ti ti-search"></i>
                                </button>
                                <a href="{{ route('attendanceemployee.index') }}" class="btn btn-sm btn-danger" title="{{ __('Reset') }}" style="height:37px;width:37px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="ti ti-trash-off"></i>
                                </a>
                                @if (Gate::check('attendance.marked.import.all'))
                                    <a href="#" data-url="{{ route('attendance.file.import') }}"
                                        data-ajax-popup="true" data-title="{{ __('Import Attendance CSV File') }}"
                                        class="btn btn-sm btn-primary" title="{{ __('Import') }}" style="height:37px;width:37px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                                        <i class="ti ti-file"></i>
                                    </a>
                                @endif
                                @if (Gate::check('attendance.marked.export.all'))
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#exportAttendanceModal"
                                        class="btn btn-sm btn-primary" title="{{ __('Export') }}" style="height:37px;width:37px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                                        <i class="ti ti-download"></i>
                                    </a>
                                @endif
                            </div>
                        @endif

                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-body table-border-style" >
                    <div class="table-responsive" style="padding: 15px !important">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (\Auth::user()->type != 'employee' || ($isHR && !request()->has('own')))
                                        <th class="text-start">{{ __('Employee') }}</th>
                                    @endif
                                    <th class="text-start">{{ __('Date') }}</th>
                                    <th class="text-start">{{ __('Status') }}</th>
                                    <th class="text-start">{{ __('Clock-In Time') }}</th>
                                    <th class="text-start">{{ __('Late') }}</th>
                                    <th class="text-start">{{ __('Clock-In Location') }}</th>
                                    <th class="text-start">{{ __('Clock-In 2') }}</th>
                                    <th class="text-start">{{ __('Clock-In 2 Location') }}</th>
                                    <th class="text-start">{{ __('Clock-Out Time') }}</th>
                                    <th class="text-start">{{ __('Early Leaving') }}</th>
                                    <th class="text-start">{{ __('Clock-Out Location') }}</th>
                                    <th class="text-start">{{ __('Clock-Out 2') }}</th>
                                    <th class="text-start">{{ __('Clock-Out 2 Location') }}</th>
                                    @if (Gate::check('attendance.marked.edit.all') || Gate::check('attendance.marked.delete.all'))
                                        <th width="200px" class="text-center">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendanceEmployee as $attendance)
                                    <tr>
                                        @if (\Auth::user()->type != 'employee' || ($isHR && !request()->has('own')))
                                            <td class="text-start">{{ !empty($attendance->employee) ? $attendance->employee->full_name : '' }}</td>
                                        @endif
                                        <td class="text-start">{{ \Auth::user()->dateFormat($attendance->date) }}</td>
                                        <td class="text-start">
                                            @if(($attendance->clock_out == '00:00:00' || empty($attendance->clock_out)) && !empty($attendance->clock_in) && $attendance->clock_in != '00:00:00')
                                                <span class="badge bg-info">{{ __('Single Punch In') }}</span>
                                            @else
                                                {{ $attendance->status }}
                                            @endif
                                        </td>
                                        <td class="text-start">{{ $attendance->clock_in != '00:00:00' ? \Auth::user()->timeFormat($attendance->clock_in) : '00:00' }}
                                        </td>
                                        <td class="text-start">
                                            @if($attendance->late != '00:00:00')
                                                <span class="badge bg-danger">{{ $attendance->late }} {{ __('Late') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-start">{!! breakAfterWords($attendance->clock_in_location) !!}</td>
                                        <td class="text-start">{{ $attendance->clock_in_2 != '00:00:00' && !empty($attendance->clock_in_2) ? \Auth::user()->timeFormat($attendance->clock_in_2) : '-' }}</td>
                                        <td class="text-start">{!! breakAfterWords($attendance->clock_in_2_location) !!}</td>
                                        <td class="text-start">{{ $attendance->clock_out != '00:00:00' ? \Auth::user()->timeFormat($attendance->clock_out) : '00:00' }}
                                        </td>
                                        <td class="text-start">
                                            @if($attendance->early_leaving != '00:00:00')
                                                <span class="badge bg-danger">{{ $attendance->early_leaving }} {{ __('Early Leaving') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-start">{!! breakAfterWords($attendance->clock_out_location) !!}</td>
                                        <td class="text-start">{{ $attendance->clock_out_2 != '00:00:00' && !empty($attendance->clock_out_2) ? \Auth::user()->timeFormat($attendance->clock_out_2) : '-' }}</td>
                                        <td class="text-start">{!! breakAfterWords($attendance->clock_out_2_location) !!}</td>
                                        @if (Gate::check('attendance.marked.edit.all') || Gate::check('attendance.marked.delete.all'))
                                            <td class="Action" style="vertical-align: middle;">
                                                <span class="d-flex align-items-center justify-content-center">
                                                    @if (Gate::check('attendance.marked.edit.all'))
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="#" class="btn btn-sm d-flex align-items-center justify-content-center"
                                                                data-size="lg"
                                                                data-url="{{ URL::to('attendanceemployee/' . $attendance->id . '/edit') }}"
                                                                data-ajax-popup="true" data-size="md" data-title="{{ __('Edit Attendance') }}"
                                                                style="width: 100%; height: 100%; padding: 0; margin: 0;">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if (Gate::check('attendance.marked.delete.all'))
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['attendanceemployee.destroy', $attendance->id],
                                                                'id' => 'delete-form-' . $attendance->id,
                                                                'style' => 'display: contents;',
                                                            ]) !!}
                                                            <a href="#"
                                                                class="btn btn-sm d-flex align-items-center justify-content-center bs-pass-para" aria-label="Delete"
                                                                data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="document.getElementById('delete-form-{{ $attendance->id }}').submit();"
                                                                style="width: 100%; height: 100%; padding: 0; margin: 0;">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endif
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Attendance Modal -->
    <div class="modal fade" id="exportAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="exportAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="exportAttendanceModalLabel">{{ __('Advanced Attendance Export') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('attendance.export') }}" method="GET" id="exportAttendanceForm">
                    <div class="modal-body">
                        <!-- Hidden inputs for active filters on index page -->
                        <input type="hidden" name="branch" id="modal_branch">
                        <input type="hidden" name="department" id="modal_department">
                        <input type="hidden" name="employee" id="modal_employee">

                        <div class="form-group mb-3">
                            <label for="export_year" class="form-label font-weight-bold">{{ __('Select Year') }}</label>
                            <select name="export_year" id="export_year" class="form-control">
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold d-block">{{ __('Export Mode') }}</label>
                            <div class="d-flex gap-3 pt-1">
                                <div class="form-check">
                                    <input type="radio" id="mode_range" name="export_mode" value="range" class="form-check-input" checked>
                                    <label class="form-check-label" for="mode_range" style="font-size: 13.5px; color: #3a3f5a;">{{ __('Month Range') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="mode_specific" name="export_mode" value="specific" class="form-check-input">
                                    <label class="form-check-label" for="mode_specific" style="font-size: 13.5px; color: #3a3f5a;">{{ __('Selected Months') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="mode_daily" name="export_mode" value="daily" class="form-check-input">
                                    <label class="form-check-label" for="mode_daily" style="font-size: 13.5px; color: #3a3f5a;">{{ __('Single Date (Daily)') }}</label>
                                </div>
                            </div>
                        </div>

                        <!-- Mode: Single Date -->
                        <div id="daily_mode_wrapper" class="form-group mb-3 d-none">
                            <label for="export_date" class="form-label font-weight-bold">{{ __('Select Date') }}</label>
                            <input type="date" name="export_date" id="export_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <!-- Mode: Month Range -->
                        <div id="range_mode_wrapper" class="row">
                            <div class="col-6 form-group">
                                <label for="start_month" class="form-label">{{ __('From Month') }}</label>
                                <select name="start_month" id="start_month" class="form-control">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 form-group">
                                <label for="end_month" class="form-label">{{ __('To Month') }}</label>
                                <select name="end_month" id="end_month" class="form-control">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Mode: Specific Months -->
                        <div id="specific_mode_wrapper" class="form-group mb-3 d-none">
                            <label class="form-label d-block">{{ __('Select Months') }}</label>
                            <div class="row pt-1">
                                @foreach(range(1, 12) as $m)
                                    <div class="col-4 mb-2">
                                        <div class="form-check">
                                            <input type="checkbox" name="selected_months[]" value="{{ $m }}" id="month_{{ $m }}" class="form-check-input select-month-cb">
                                            <label class="form-check-label" for="month_{{ $m }}" style="font-size: 13px; color: #3a3f5a;">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Export Excel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


<script>
    $(document).ready(function() {
    $('.export-btn').click(function() {
        // Get all the filter values
        var type = $('input[name="type"]:checked').val();
        var month = $('input[name="month"]').val();
        var date = $('input[name="date"]').val();
        var branch = $('select[name="branch"]').val();
        var department = $('select[name="department"]').val();
        
        // Build the export URL with all filters
        var url = "{{ route('attendance.export') }}";
        url += "?type=" + type;
        
        if (type == 'monthly' && month) {
            url += "&month=" + month;
        } else if (type == 'daily' && date) {
            url += "&date=" + date;
        }
        
        if (branch) {
            url += "&branch=" + branch;
        }
        
        if (department) {
            url += "&department=" + department;
        }
        
        // Redirect to the export URL which will trigger the download
        window.location.href = url;
    });
});
</script>

    @push('scripts')
    <style>
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            /* Force Type radio buttons to be inline on mobile */
            .btn-box .d-flex {
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 1rem !important;
            }
            
            .btn-box .form-check {
                display: inline-block !important;
                margin-right: 1rem !important;
            }
            
            /* Force action buttons to be properly spaced on mobile */
            .justify-content-end.gap-2 {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 0.5rem !important;
                justify-content: flex-end !important;
                margin-top: 15px !important;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem !important;
            }
        }
        
        .table th {
            white-space: nowrap;
            text-align: left !important;
            vertical-align: middle !important;
            padding-right: 25px !important;
            position: relative;
        }
        
        .table td {
            vertical-align: middle !important;
        }
        
        /* Fix DataTables sorting icons alignment */
        .dataTables_wrapper .dataTables_scrollHead .table th {
            position: relative;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after,
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after,
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 8px !important;
            margin-top: 0 !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after {
            content: "·" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
            content: "·" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after {
            content: "·" !important;
            opacity: 0.3;
        }
        
        /* Ensure proper column width alignment */
        #pc-dt-simple th {
            min-width: 120px;
        }
        
        @if (\Auth::user()->type != 'employee' || ($isHR && !request()->has('own')))
        #pc-dt-simple th:nth-child(1) {
            min-width: 200px; /* Employee */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 140px; /* Date */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 120px; /* Status */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 160px; /* Clock-In Time */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 120px; /* Late */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 250px; /* Clock-In Location */
        }
        
        #pc-dt-simple th:nth-child(7) {
            min-width: 160px; /* Clock-In 2 */
        }
        
        #pc-dt-simple th:nth-child(8) {
            min-width: 250px; /* Clock-In 2 Location */
        }

        #pc-dt-simple th:nth-child(9) {
            min-width: 160px; /* Clock-Out Time */
        }
        
        #pc-dt-simple th:nth-child(10) {
            min-width: 160px; /* Early Leaving */
        }
        
        #pc-dt-simple th:nth-child(11) {
            min-width: 250px; /* Clock-Out Location */
        }

        #pc-dt-simple th:nth-child(12) {
            min-width: 160px; /* Clock-Out 2 */
        }

        #pc-dt-simple th:nth-child(13) {
            min-width: 250px; /* Clock-Out 2 Location */
        }
        
        #pc-dt-simple th:nth-child(14) {
            min-width: 200px; /* Action */
        }
        @else
        #pc-dt-simple th:nth-child(1) {
            min-width: 140px; /* Date */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 120px; /* Status */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 160px; /* Clock-In Time */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 120px; /* Late */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 250px; /* Clock-In Location */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 160px; /* Clock-In 2 */
        }

        #pc-dt-simple th:nth-child(7) {
            min-width: 250px; /* Clock-In 2 Location */
        }

        #pc-dt-simple th:nth-child(8) {
            min-width: 160px; /* Clock-Out Time */
        }
        
        #pc-dt-simple th:nth-child(9) {
            min-width: 160px; /* Early Leaving */
        }
        
        #pc-dt-simple th:nth-child(10) {
            min-width: 250px; /* Clock-Out Location */
        }

        #pc-dt-simple th:nth-child(11) {
            min-width: 160px; /* Clock-Out 2 */
        }

        #pc-dt-simple th:nth-child(12) {
            min-width: 250px; /* Clock-Out 2 Location */
        }
        
        #pc-dt-simple th:nth-child(13) {
            min-width: 200px; /* Action */
        }
        @endif
    </style>

@endpush

