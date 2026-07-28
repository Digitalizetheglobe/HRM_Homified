@if($employee->approval_status === 'approved' && \Auth::user()->type === 'employee' && !\Auth::user()->isHR())

    @php
        // Prevent form submission by disabling all inputs
        $readonly = true;
    @endphp
@else
    @php
        $readonly = false;
    @endphp
@endif

@php
    $isEmployee = (\Auth::user()->type === 'employee' && !\Auth::user()->isHR());
    // For employees, restrict almost everything except the specified fields
    // For admins/company, they can still edit everything unless $readonly is true
    $isRestricted = ($readonly || $isEmployee);
@endphp

@extends('layouts.admin')

@section('page-title')
    {{ __('Edit Employee') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('employee') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit Employee') }}</li>
@endsection

@push('css-page')
    <style>
        /* =============================================
           HRM Employee Edit – Premium UI
           Primary colour: var(--color-customColor)
        ============================================= */
        .cursor-pointer { cursor: pointer; }

        /* ── Card ──────────────────────────────────── */
        .card {
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
            overflow: hidden;
        }
        .card .card-body { padding: 20px !important; }

        /* ── Card Header ────────────────────────────── */
        .card .card-header {
            border-left: 4px solid var(--color-customColor, #c9a227);
            background: linear-gradient(to right, rgba(201,162,39,0.08) 0%, #fff 55%);
            padding: 12px 18px;
            border-bottom: 1px solid #ebebeb;
        }
        .card .card-header h5,
        .card .card-header h6 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 0;
            font-size: 0.88rem;
            color: #1a202c;
        }
        .card .card-header .ti {
            color: var(--color-customColor, #c9a227);
            font-size: 1rem;
        }

        /* ── Form Labels ────────────────────────────── */
        .form-label {
            font-weight: 500;
            font-size: 0.79rem;
            letter-spacing: 0.2px;
            color: #4b5563;
            margin-bottom: 4px;
            display: inline-block;
        }

        /* ── Form Controls ──────────────────────────── */
        .form-control, select.form-control {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            padding: 8px 11px !important;
            background-color: #f9fafb !important;
            color: #374151 !important;
            transition: border-color 0.18s, box-shadow 0.18s;
            height: auto !important;
        }
        .form-control:focus, select.form-control:focus {
            border-color: var(--color-customColor, #c9a227) !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 3px rgba(201,162,39,0.13) !important;
        }
        textarea.form-control { min-height: 88px; resize: vertical; }

        /* ── Dynamic Row Cards ──────────────────────── */
        .education-detail-row,
        .experience-detail-row {
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            padding: 14px 12px 8px;
            margin-bottom: 16px;
            background: #f8fafc;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .education-detail-row:hover,
        .experience-detail-row:hover {
            box-shadow: 0 3px 14px rgba(0,0,0,0.08);
            border-color: #d1d5db;
        }

        /* ── Upload Button & Previews ───────────────── */
        .choose-files {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            display: block !important;
        }
        .choose-files > label {
            display: block;
            width: 100%;
            margin: 0;
            cursor: pointer;
        }
        .choose-files div {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            background: transparent !important;
        }
        .btn.document, .bg-primary.document {
            border-radius: 8px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 9px 14px !important;
            width: 100% !important;
            max-width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            background: var(--color-customColor, #c9a227) !important;
            color: #fff !important;
            border: none !important;
            white-space: nowrap !important;
        }
        input[type="file"].file { display: none !important; }

        .document-preview-img {
            max-height: 120px !important;
            width: auto !important;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-top: 8px;
            transition: transform 0.18s ease-in-out;
        }
        .document-preview-img:hover {
            transform: scale(1.03);
        }

        /* ── Submit Bar ─────────────────────────────── */
        .form-submit-bar {
            border-top: 1px solid #e5e7eb;
            padding: 16px 0;
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .form-submit-bar .btn { min-width: 160px; }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="">
            <div class="">
                {{ Form::model($employee, ['route' => ['employee.update', $employee->id], 'method' => 'put', 'enctype' => 'multipart/form-data']) }}

                <!-- Add this error display section at the top of your form -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row">
                    <!-- Personal Details Section -->
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                            <h5><i class="ti ti-user-circle"></i>{{ __('Personal Detail') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                    <div class="form-group col-md-4">
                                        {!! Form::label('name', __('First Name'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('name', old('name', $employee->name ?? ''), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter first name',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {!! Form::label('middle_name', __('Middle Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('middle_name', old('middle_name', $employee->middle_name ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter middle name',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {!! Form::label('last_name', __('Last Name'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('last_name', old('last_name', $employee->last_name ?? ''), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter last name',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('phone', __('Phone'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('phone', old('phone', $employee->phone), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter employee phone',
                                            'oninput' => 'validateNumbers()',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                        <span id="phone-error" class="text-danger"></span>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {!! Form::label('office_phone_one', __('Office Phone 1'), ['class' => 'form-label']) !!}
                                        {!! Form::text('office_phone_one', old('office_phone_one', $employee->office_phone_one), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter office phone 1',
                                            'oninput' => 'validateNumbers()',
                                            'readonly' => $readonly,
                                        ]) !!}
                                        <span id="office_phone_one-error" class="text-danger"></span>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {!! Form::label('office_phone_two', __('Office Phone 2'), ['class' => 'form-label']) !!}
                                        {!! Form::text('office_phone_two', old('office_phone_two', $employee->office_phone_two), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter office phone 2',
                                            'oninput' => 'validateNumbers()',
                                            'readonly' => $readonly,
                                        ]) !!}
                                        <span id="office_phone_two-error" class="text-danger"></span>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {!! Form::label('emergency_number', __('Emergency Number'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        {!! Form::text('emergency_number', old('emergency_number', $employee->emergency_number), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter emergency number',
                                            'oninput' => 'validateNumbers()',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                        <span id="emergency_number-error" class="text-danger"></span>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('dob', __('Date of Birth'), ['class' => 'form-label']) !!}
                                            {!! Form::date('dob', !empty($employee->dob) ? date('Y-m-d', strtotime($employee->dob)) : null, [
                                                'class' => 'form-control',
                                                'autocomplete' => 'off',
                                                'placeholder' => 'yyyy-mm-dd',
                                                'readonly' => $isRestricted,
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('blood_group', __('Blood-Group'), ['class' => 'form-label']) !!}<span class="text-danger pl-1"></span>
                                            {!! Form::text('blood_group', old('blood_group', $employee->blood_group), [
                                                'class' => 'form-control',
                                                'placeholder' => 'Enter employee Blood-Group',
                                                'readonly' => $readonly,
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('gender', __('Gender'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                            <div class="d-flex radio-check">
                                                @if($isRestricted)
                                                    <input type="hidden" name="gender" value="{{ $employee->gender }}">
                                                @endif
                                                <div class="custom-control custom-radio custom-control-inline">
                                                    <input type="radio" id="g_male" value="Male" name="gender"
                                                        class="form-check-input" {{ $employee->gender == 'Male' ? 'checked' : '' }} {{ $isRestricted ? 'disabled' : '' }}>
                                                    <label class="form-check-label "
                                                        for="g_male">{{ __('Male') }}</label>
                                                </div>
                                                <div class="custom-control custom-radio ms-1 custom-control-inline">
                                                    <input type="radio" id="g_female" value="Female" name="gender"
                                                        class="form-check-input" {{ $employee->gender == 'Female' ? 'checked' : '' }} {{ $isRestricted ? 'disabled' : '' }}>
                                                    <label class="form-check-label "
                                                        for="g_female">{{ __('Female') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('email', __('Email'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                        @if(\Auth::user()->type === 'employee' && \Auth::user()->id === $employee->user_id)
                                            <small class="text-muted d-block mb-1">
                                                <i class="ti ti-info-circle"></i> {{ __('Email cannot be changed. Please contact administrator.') }}
                                            </small>
                                        @endif
                                        {!! Form::email('email', old('email', $employee->email), [
                                            'class' => 'form-control' . ((\Auth::user()->type === 'employee' && \Auth::user()->id === $employee->user_id) ? ' bg-light' : ''),
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee email',
                                            'readonly' => (\Auth::user()->type === 'employee' && \Auth::user()->id === $employee->user_id),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}
                                        {!! Form::password('password', [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter new password (leave blank to keep current)',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    {!! Form::label('address', __('Address'), ['class' => 'form-label']) !!}<span class="text-danger pl-1">*</span>
                                    {!! Form::textarea('address', old('address', $employee->address), [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => 'Enter employee address',
                                        'readonly' => $readonly,
                                    ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="week_off_day">{{ __('Week Off Day') }}</label>
                                    @if($isRestricted)
                                        <input type="hidden" name="week_off_day" value="{{ $employee->week_off_day }}">
                                    @endif
                                    <select name="week_off_day" id="week_off_day" class="form-control" {{ $isRestricted ? 'disabled' : '' }}>
                                        <option value="Sunday" {{ $employee->week_off_day == 'Sunday' ? 'selected' : '' }}>{{ __('Sunday') }}</option>
                                        <option value="Monday" {{ $employee->week_off_day == 'Monday' ? 'selected' : '' }}>{{ __('Monday') }}</option>
                                        <option value="Tuesday" {{ $employee->week_off_day == 'Tuesday' ? 'selected' : '' }}>{{ __('Tuesday') }}</option>
                                        <option value="Wednesday" {{ $employee->week_off_day == 'Wednesday' ? 'selected' : '' }}>{{ __('Wednesday') }}</option>
                                        <option value="Thursday" {{ $employee->week_off_day == 'Thursday' ? 'selected' : '' }}>{{ __('Thursday') }}</option>
                                        <option value="Friday" {{ $employee->week_off_day == 'Friday' ? 'selected' : '' }}>{{ __('Friday') }}</option>
                                        <option value="Saturday" {{ $employee->week_off_day == 'Saturday' ? 'selected' : '' }}>{{ __('Saturday') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Details Section -->
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5><i class="ti ti-building"></i>{{ __('Company Detail') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row g-3">
                                    @csrf
                                    <div class="form-group">
                                        {!! Form::label('employee_id', __('Employee ID'), ['class' => 'form-label']) !!}
                                        {!! Form::text('employee_id', \Auth::user()->employeeIdFormat($employee->employee_id), ['class' => 'form-control', 'disabled' => 'disabled']) !!}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('branch_id', __('Select Branch*'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            @if($isRestricted)
                                                <input type="hidden" name="branch_id" value="{{ $employee->branch_id }}">
                                            @endif
                                            {{ Form::select('branch_id', $branches, $employee->branch_id, ['class' => 'form-control branch_id', 'id' => 'branch_id', 'required' => 'required', 'disabled' => $isRestricted]) }}
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <div class="form-icon-user" id="department_id">
                                            {{ Form::label('department_id', __('Department'), ['class' => 'form-label']) }}
                                            @if($isRestricted)
                                                <input type="hidden" name="department_id" value="{{ $employee->department_id }}">
                                            @endif
                                            <select class="form-control select department_id" name="department_id"
                                                id="department_id" placeholder="Select Department" required {{ $isRestricted ? 'disabled' : '' }}>
                                                @foreach($departments as $id => $department)
                                                    <option value="{{ $id }}" {{ $employee->department_id == $id ? 'selected' : '' }}>{{ $department }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('designation_id', __('Select Designation'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user designation_div">
                                            @if($isRestricted)
                                                <input type="hidden" name="designation_id" value="{{ $employee->designation_id }}">
                                            @endif
                                            <select class="form-control designation_id" name="designation_id" id="designation_id" required {{ $isRestricted ? 'disabled' : '' }}>
                                                @if($employee->designation_id)
                                                    <option value="{{ $employee->designation_id }}" selected>
                                                        {{ $designations[$employee->designation_id] ?? 'N/A' }}
                                                    </option>
                                                @else
                                                    <option value="" selected disabled>{{ __('Select Designation') }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('work_location', __('Work Location'), ['class' => 'form-label']) }}
                                        <div class="form-icon-user">
                                            @if($isRestricted)
                                                <input type="hidden" name="work_location" value="{{ $employee->work_location ?? 'Pune' }}">
                                            @endif
                                            {{ Form::select('work_location', ['Pune' => 'Pune', 'Mumbai' => 'Mumbai'], $employee->work_location ?? 'Pune', ['class' => 'form-control', 'id' => 'work_location', 'required' => 'required', 'disabled' => $isRestricted]) }}
                                        </div>
                                    </div>
                                    
                                   
                                    <div class="form-group">
                                        {!! Form::label('company_doj', __('Date Of Joining'), ['class' => 'form-label']) !!}
                                        {!! Form::date('company_doj', !empty($employee->company_doj) ? date('Y-m-d', strtotime($employee->company_doj)) : null, [
                                            'class' => 'form-control',
                                            'autocomplete' => 'off',
                                            'placeholder' => 'yyyy-mm-dd',
                                            'readonly' => $isRestricted,
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Experience Section -->
                    <div class="col-md-12">
                        <div class="card md-12">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ti ti-briefcase"></i>{{ __('Total Experience') }}</h5>
                                @if(!$readonly)
                                    <button type="button" class="btn btn-primary btn-sm add-experience-row">
                                        <i class="ti ti-plus me-1"></i>{{ __('Add Experience') }}
                                    </button>
                                @endif
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div id="experience-details-container">
                                        @if(!empty($employee->experience))
                                            @foreach($employee->experience as $key => $experience)
                                                <div class="row g-3 experience-detail-row">
                                                    <div class="form-group col-md-6">
                                                        {!! Form::label("experience[$key][previous_company_name]", __('Previous Company Name'), ['class' => 'form-label']) !!}
                                                        {!! Form::text("experience[$key][previous_company_name]", $experience['previous_company_name'] ?? null, [
                                                            'class' => 'form-control',
                                                            'placeholder' => 'Enter previous company name',
                                                            'readonly' => $readonly,
                                                        ]) !!}
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        {!! Form::label("experience[$key][previous_designation]", __('Designation'), ['class' => 'form-label']) !!}
                                                        {!! Form::text("experience[$key][previous_designation]", $experience['previous_designation'] ?? null, [
                                                            'class' => 'form-control',
                                                            'placeholder' => 'Enter designation',
                                                            'readonly' => $readonly,
                                                        ]) !!}
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        {!! Form::label("experience[$key][start_date]", __('Start Date'), ['class' => 'form-label']) !!}
                                                        {!! Form::date("experience[$key][start_date]", null, [
                                                            'class' => 'form-control',
                                                            'placeholder' => 'dd-mm-yyyy',
                                                            'readonly' => $readonly,
                                                        ]) !!}
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        {!! Form::label("experience[$key][end_date]", __('End Date'), ['class' => 'form-label']) !!}
                                                        {!! Form::date("experience[$key][end_date]", null, [
                                                            'class' => 'form-control',
                                                            'placeholder' => 'dd-mm-yyyy',
                                                            'readonly' => $readonly,
                                                        ]) !!}
                                                    </div>
                                                    <div class="form-group col-md-12">
                                                        {!! Form::label("experience[$key][previous_salary]", __('Previous Salary'), ['class' => 'form-label']) !!}
                                                        {!! Form::number("experience[$key][previous_salary]", $experience['previous_salary'] ?? null, [
                                                            'class' => 'form-control',
                                                            'placeholder' => 'Enter previous salary',
                                                            'readonly' => $readonly,
                                                        ]) !!}
                                                    </div>
                                                    <div class="form-group col-md-12 text-end">
                                                        @if(!$readonly)
                                                            <button type="button" class="btn btn-danger remove-experience-row">
                                                                <i class="ti ti-trash me-1"></i>{{ __('Remove') }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row g-3 experience-detail-row">
                                                <div class="form-group col-md-6">
                                                    {!! Form::label('experience[0][previous_company_name]', __('Previous Company Name'), ['class' => 'form-label']) !!}
                                                    {!! Form::text('experience[0][previous_company_name]', null, [
                                                        'class' => 'form-control',
                                                        'placeholder' => 'Enter previous company name',
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label('experience[0][previous_designation]', __('Designation'), ['class' => 'form-label']) !!}
                                                    {!! Form::text('experience[0][previous_designation]', null, [
                                                        'class' => 'form-control',
                                                        'placeholder' => 'Enter designation',
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label('experience[0][start_date]', __('Start Date'), ['class' => 'form-label']) !!}
                                                    {!! Form::date('experience[0][start_date]', null, [
                                                        'class' => 'form-control',
                                                        'placeholder' => 'Select start date',
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label('experience[0][end_date]', __('End Date'), ['class' => 'form-label']) !!}
                                                    {!! Form::date('experience[0][end_date]', null, [
                                                        'class' => 'form-control',
                                                        'placeholder' => 'Select end date',
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-12">
                                                    {!! Form::label('experience[0][previous_salary]', __('Previous Salary'), ['class' => 'form-label']) !!}
                                                    {!! Form::number('experience[0][previous_salary]', null, [
                                                        'class' => 'form-control',
                                                        'placeholder' => 'Enter previous salary',
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-12 text-end">
                                                    @if(!$readonly)
                                                        <button type="button" class="btn btn-danger remove-experience-row">
                                                            <i class="ti ti-trash me-1"></i>{{ __('Remove') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Documents and Education Section -->
                <div class="row">
                    <!-- Documents Section -->
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5><i class="ti ti-files"></i>{{ __('Document') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                @foreach ($documents as $key => $document)
                                    <div class="row mb-3 pb-3 border-bottom border-light align-items-center">
                                        <div class="col-12 col-sm-4 mb-2 mb-sm-0">
                                            <label for="document" class="form-label mb-0">
                                                {{ $document->name }} 
                                                @if ($document->is_required == 1)
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                        </div>
                                        <div class="col-12 col-sm-8">
                                            <input type="hidden" name="emp_doc_id[{{ $document->id }}]" value="{{ $document->id }}">
                                            <div class="d-flex flex-column gap-2 mt-0">
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    <div class="choose-files flex-grow-1 m-0">
                                                        <label for="document[{{ $document->id }}]" class="w-100 mb-0">
                                                            <div class="btn btn-sm btn-primary document cursor-pointer w-100 m-0">
                                                                <i class="ti ti-upload me-1"></i>{{ __('Choose file here') }}
                                                            </div>
                                                            <input type="file" 
                                                                class="form-control file d-none @error('document') is-invalid @enderror"
                                                                @if ($document->is_required == 1) required @endif
                                                                name="document[{{ $document->id }}]"
                                                                id="document[{{ $document->id }}]"
                                                                data-filename="{{ $document->id . '_filename' }}"
                                                                onchange="var img = document.getElementById('{{ 'blah' . $key }}'); img.src = window.URL.createObjectURL(this.files[0]); img.style.display = 'inline-block';"
                                                                {{ $readonly ? 'disabled' : '' }}>
                                                        </label>
                                                    </div>
                                                    @php
                                                        $employeeDoc = $employee->documents()->where('document_id', $document->id)->first();
                                                    @endphp
                                                    @if($employeeDoc && $employeeDoc->document_value)
                                                        <div class="flex-grow-0">
                                                            <a href="{{ \App\Models\Utility::get_file($employeeDoc->document_value) }}" 
                                                                class="btn btn-sm btn-info d-inline-flex align-items-center view-document-modal" 
                                                                data-title="{{ $document->name }}">
                                                                <i class="ti ti-eye me-1"></i> {{ __('View') }}
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    @if($employeeDoc && $employeeDoc->document_value)
                                                        @php
                                                            $ext = pathinfo($employeeDoc->document_value, PATHINFO_EXTENSION);
                                                            $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        @endphp
                                                        <img id="{{ 'blah' . $key }}" src="{{ \App\Models\Utility::get_file($employeeDoc->document_value) }}" class="document-preview-img" style="{{ $isImage ? '' : 'display: none;' }}" onerror="this.style.display='none';" />
                                                    @else
                                                        <img id="{{ 'blah' . $key }}" src="" class="document-preview-img" style="display: none;" onerror="this.style.display='none';" />
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
              
                    <!-- Education Section -->
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ti ti-school"></i>{{ __('Education Details') }}</h5>
                                @if(!$readonly)
                                    <button type="button" class="btn btn-primary btn-sm add-education-row">
                                        <i class="ti ti-plus me-1"></i>{{ __('Add Education') }}
                                    </button>
                                @endif
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div id="education-details-container">
                                    @if(!empty($educations))
                                        @foreach($educations as $key => $education)
                                            <div class="row g-3 education-detail-row">
                                                <div class="form-group col-md-6">
                                                    {!! Form::label("education[$key][degree]", __('Degree'), ['class' => 'form-label']) !!}
                                                    <select name="education[{{ $key }}][degree]" class="form-control degree" {{ $readonly ? 'disabled' : '' }}>
                                                        <option value="10th" {{ (isset($education['degree']) && $education['degree'] == '10th') ? 'selected' : '' }}>{{ __('10th') }}</option>
                                                        <option value="12th" {{ (isset($education['degree']) && $education['degree'] == '12th') ? 'selected' : '' }}>{{ __('12th') }}</option>
                                                        <option value="Bachelor" {{ (isset($education['degree']) && $education['degree'] == 'Bachelor') ? 'selected' : '' }}>{{ __('Bachelor') }}</option>
                                                        <option value="Master" {{ (isset($education['degree']) && $education['degree'] == 'Master') ? 'selected' : '' }}>{{ __('Master') }}</option>
                                                        <option value="PhD" {{ (isset($education['degree']) && $education['degree'] == 'PhD') ? 'selected' : '' }}>{{ __('PhD') }}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label("education[$key][college_name]", __('College Name'), ['class' => 'form-label']) !!}
                                                    {!! Form::text("education[$key][college_name]", $education['college_name'] ?? null, [
                                                        'class' => 'form-control college-name',
                                                        'placeholder' => 'Enter college name',
                                                        'readonly' => $readonly,
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label("education[$key][passing_year]", __('Passing Year'), ['class' => 'form-label']) !!}
                                                    <select name="education[{{ $key }}][passing_year]" class="form-control passing-year" {{ $readonly ? 'disabled' : '' }}>
                                                        <option value="" disabled selected>{{ __('Select Year') }}</option>
                                                        @for ($year = 1997; $year <= 2040; $year++)
                                                            <option value="{{ $year }}" {{ (isset($education['passing_year']) && $education['passing_year'] == $year) ? 'selected' : '' }}>{{ $year }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    {!! Form::label("education[$key][grade]", __('Grade'), ['class' => 'form-label']) !!}
                                                    {!! Form::number("education[$key][grade]", $education['grade'] ?? null, [
                                                        'class' => 'form-control grade',
                                                        'placeholder' => 'Enter grade (e.g., 4.0)',
                                                        'step' => '0.1',
                                                        'min' => '0',
                                                        'max' => '10',
                                                        'readonly' => $readonly,
                                                    ]) !!}
                                                </div>
                                                <div class="form-group col-md-12">
                                                    {!! Form::label("education[$key][document]", __('Education Document'), ['class' => 'form-label']) !!}
                                                    <div class="d-flex flex-column gap-3">
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                                <div class="choose-files">
                                                                    <label for="education[{{ $key }}][document]" class="m-0">
                                                                        <div class="btn btn-sm btn-primary document cursor-pointer">
                                                                            <i class="ti ti-upload me-1"></i>{{ __('Choose file here') }}
                                                                        </div>
                                                                        <input type="file" 
                                                                            name="education[{{ $key }}][document]" 
                                                                            id="education[{{ $key }}][document]" 
                                                                            class="form-control file education-document"
                                                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                                            {{ $readonly ? 'disabled' : '' }}>
                                                                    </label>
                                                                </div>
                                                                @if(isset($education['document_path']))
                                                                    <a href="{{ \App\Models\Utility::get_file($education['document_path']) }}" 
                                                                       class="btn btn-sm btn-info text-white view-document-modal" 
                                                                       data-title="{{ $education['degree'] ?? __('Education Document') }}">
                                                                        <i class="ti ti-eye"></i> {{ __('View Document') }}
                                                                    </a>
                                                                    <input type="hidden" name="education[{{ $key }}][existing_document]" value="{{ $education['document_path'] }}">
                                                                @endif
                                                            </div>
                                                            @if(!$readonly)
                                                                <button type="button" class="btn btn-sm btn-danger remove-education-row">
                                                                    <i class="fa fa-trash"></i> {{ __('Remove') }}
                                                                </button>
                                                            @endif
                                                        </div>
                                                        <div class="preview-container">
                                                            @if(isset($education['document_path']))
                                                                @php
                                                                    $ext = pathinfo($education['document_path'], PATHINFO_EXTENSION);
                                                                    $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                                @endphp
                                                                @if($isImage)
                                                                    <img src="{{ \App\Models\Utility::get_file($education['document_path']) }}" class="img-thumbnail mt-2" style="max-width: 200px;">
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="row education-detail-row">
                                            <div class="form-group col-md-6">
                                                {!! Form::label('education[0][degree]', __('Degree'), ['class' => 'form-label']) !!}
                                                <select name="education[0][degree]" class="form-control degree">
                                                    <option value="10th">{{ __('10th') }}</option>
                                                    <option value="12th">{{ __('12th') }}</option>
                                                    <option value="Bachelor">{{ __('Bachelor') }}</option>
                                                    <option value="Master">{{ __('Master') }}</option>
                                                    <option value="PhD">{{ __('PhD') }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                {!! Form::label('education[0][college_name]', __('College Name'), ['class' => 'form-label']) !!}
                                                {!! Form::text('education[0][college_name]', null, [
                                                    'class' => 'form-control college-name',
                                                    'placeholder' => 'Enter college name',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-md-6">
                                                {!! Form::label('education[0][passing_year]', __('Passing Year'), ['class' => 'form-label']) !!}
                                                <select name="education[0][passing_year]" class="form-control passing-year">
                                                    <option value="" disabled selected>{{ __('Select Year') }}</option>
                                                    @for ($year = 1997; $year <= 2040; $year++)
                                                        <option value="{{ $year }}">{{ $year }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                {!! Form::label('education[0][grade]', __('Grade'), ['class' => 'form-label']) !!}
                                                {!! Form::number('education[0][grade]', null, [
                                                    'class' => 'form-control grade',
                                                    'placeholder' => 'Enter grade (e.g., 4.0)',
                                                    'step' => '0.1',
                                                    'min' => '0',
                                                    'max' => '10',
                                                ]) !!}
                                            </div>
                                            <div class="form-group col-md-12">
                                                {!! Form::label("education[0][document]", __('Education Document'), ['class' => 'form-label']) !!}
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="choose-files m-0 p-0" style="margin: 0 !important;">
                                                        <label for="education[0][document]" style="margin: 0 !important; display: flex;">
                                                            <div class="btn btn-sm btn-primary document cursor-pointer m-0" style="margin: 0 !important; white-space: nowrap;">
                                                                <i class="ti ti-upload me-1"></i>{{ __('Choose file here') }}
                                                            </div>
                                                            <input type="file" 
                                                                name="education[0][document]" 
                                                                id="education[0][document]" 
                                                                class="form-control file education-document"
                                                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                                        </label>
                                                    </div>
                                                    <div class="m-0 p-0" style="margin: 0 !important;">
                                                        @if(!$readonly)
                                                            <button type="button" class="btn btn-sm btn-danger remove-education-row m-0" style="margin: 0 !important; white-space: nowrap;">
                                                                <i class="ti ti-trash me-1"></i>{{ __('Remove') }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Bank Details Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card em-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5><i class="ti ti-credit-card"></i>{{ __('Bank Details') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row g-3">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_holder_name', __('Account Holder Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('account_holder_name', old('account_holder_name', $employee->account_holder_name ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter account holder name',
                                            'readonly' => $readonly,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_name', __('Bank Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_name', old('bank_name', $employee->bank_name ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter bank name',
                                            'readonly' => $readonly,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_identifier_code', __('IFSC Code'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_identifier_code', old('bank_identifier_code', $employee->bank_identifier_code ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter IFSC code',
                                            'maxlength' => '11',
                                            'readonly' => $readonly,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('branch_location', __('Branch Location'), ['class' => 'form-label']) !!}
                                        {!! Form::text('branch_location', old('branch_location', $employee->branch_location ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter branch location',
                                            'readonly' => $readonly,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_number', __('Account Number'), ['class' => 'form-label']) !!}
                                        {!! Form::text('account_number', old('account_number', $employee->account_number ?? ''), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter account number',
                                            'readonly' => $readonly,
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_type', __('Account Type'), ['class' => 'form-label']) !!}
                                        @php
                                            // Handle old values and convert to new format
                                            $accountTypeValue = old('account_type', $employee->account_type ?? '');
                                            // Convert old string values
                                            if($accountTypeValue == 'Salary account') {
                                                $accountTypeValue = 'salary_account';
                                            } elseif($accountTypeValue == 'Saving account') {
                                                $accountTypeValue = 'savings_account';
                                            }
                                            // Convert old numeric values (0, 1) - legacy support
                                            elseif($accountTypeValue === 0 || $accountTypeValue === '0') {
                                                $accountTypeValue = 'salary_account'; // Default mapping
                                            } elseif($accountTypeValue === 1 || $accountTypeValue === '1') {
                                                $accountTypeValue = 'savings_account'; // Default mapping
                                            }
                                        @endphp
                                        @if($readonly)
                                            <input type="hidden" name="account_type" value="{{ $accountTypeValue }}">
                                        @endif
                                        {!! Form::select('account_type', [
                                            '' => __('Select Account Type'),
                                            'salary_account' => __('Salary Account'),
                                            'savings_account' => __('Savings Account'),
                                        ], $accountTypeValue, [
                                            'class' => 'form-control',
                                            'placeholder' => 'Select account type',
                                            'disabled' => $readonly,
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-submit-bar">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ti ti-device-floppy me-2"></i>{{ __('Update Employee') }}
                    </button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $('input[type="file"]').change(function(e) {
            var file = e.target.files[0].name;
            var file_name = $(this).attr('data-filename');
            $('.' + file_name).append(file);
        });
    </script>
    <script>
        $(document).ready(function() {
            var b_id = $('#branch_id').val();
            // getDepartment(b_id);
        });
        $(document).on('change', 'select[name=branch_id]', function() {
            var branch_id = $(this).val();

            getDepartment(branch_id);
        });

        function getDepartment(bid) {
            $.ajax({
                url: '{{ route('monthly.getdepartment') }}',
                type: 'POST',
                data: {
                    "branch_id": bid,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('.department_id').empty();
                    var emp_selct = `<select class="form-control department_id" name="department_id" id="choices-multiple"
                                            placeholder="Select Department" required>
                                            </select>`;
                    $('.department_div').html(emp_selct);

                    $('.department_id').append('<option value=""> {{ __('Select Department') }} </option>');
                    $.each(data, function(key, value) {
                        $('.department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    new Choices('#choices-multiple', {
                        removeItemButton: true,
                    });
                }
            });
        }

        $(document).ready(function() {
            var branch_id = $('#branch_id').val();
            var department_id = $('.department_id').val();
            
            // Fetch designations based on the current department
            if (department_id) {
                getDesignation(department_id).then(() => {
                    // After loading designations, set the selected designation
                    if ({{ $employee->designation_id ?? 'null' }}) {
                        $('.designation_id').val({{ $employee->designation_id }});
                    }
                });
            }
        });

        // Make getDesignation return a Promise
        function getDesignation(did) {
            return new Promise((resolve) => {
                $.ajax({
                    url: '{{ route("employee.json") }}',
                    type: 'POST',
                    data: {
                        "department_id": did,
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(data) {
                        $('.designation_id').empty();
                        $('.designation_id').append('<option value="">{{ __("Select Designation") }}</option>');
                        
                        $.each(data, function(key, value) {
                            $('.designation_id').append('<option value="' + key + '">' + value + '</option>');
                        });
                        
                        resolve(); // Resolve the promise when done
                    }
                });
            });
        }
    </script>



    <script>
        // Education Details Dynamic Rows
        $(document).ready(function() {
            let educationRowCount = {{ !empty($employee->education) ? count($employee->education) : 1 }};
            
            // Add new education row
            $('.add-education-row').click(function() {
                const newRow = `
                    <div class="row g-3 education-detail-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Degree</label>
                            <select name="education[${educationRowCount}][degree]" class="form-control degree">
                                <option value="10th">10th</option>
                                <option value="12th">12th</option>
                                <option value="Bachelor">Bachelor</option>
                                <option value="Master">Master</option>
                                <option value="PhD">PhD</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">College Name</label>
                            <input type="text" name="education[${educationRowCount}][college_name]" 
                                   class="form-control college-name" placeholder="Enter college name">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Passing Year</label>
                            <select name="education[${educationRowCount}][passing_year]" class="form-control passing-year">
                                <option value="" disabled selected>Select Year</option>
                                @for ($year = 1997; $year <= 2040; $year++)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Grade</label>
                            <input type="number" name="education[${educationRowCount}][grade]" 
                                   class="form-control grade" placeholder="Enter grade" step="0.1" min="0" max="10">
                        </div>
                        <div class="form-group col-md-12">
                            <label class="form-label">Education Document</label>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="choose-files">
                                        <label for="education[${educationRowCount}][document]" class="m-0">
                                            <div class="btn btn-sm btn-primary document cursor-pointer">
                                                <i class="ti ti-upload me-1"></i> Choose file here
                                            </div>
                                            <input type="file" name="education[${educationRowCount}][document]"
                                                   id="education[${educationRowCount}][document]"
                                                   class="form-control file education-document"
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger remove-education-row">
                                        <i class="ti ti-trash me-1"></i>{{ __('Remove') }}
                                    </button>
                                </div>
                                <div class="preview-container"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#education-details-container').append(newRow);
                educationRowCount++;
            });
            
            // Remove education row
            $(document).on('click', '.remove-education-row', function() {
                $(this).closest('.education-detail-row').remove();
            });

            // Experience Details Dynamic Rows
            let experienceRowCount = {{ !empty($employee->experience) ? count($employee->experience) : 1 }};

            // Add new experience row
            $(document).on('click', '.add-experience-row', function() {
                const newRow = `
                    <div class="row g-3 experience-detail-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Previous Company Name</label>
                            <input type="text" name="experience[${experienceRowCount}][previous_company_name]" 
                                class="form-control" placeholder="Enter previous company name">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" name="experience[${experienceRowCount}][previous_designation]" 
                                class="form-control" placeholder="Enter designation">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="experience[${experienceRowCount}][start_date]" 
                                class="form-control" placeholder="Select start date">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="experience[${experienceRowCount}][end_date]" 
                                class="form-control" placeholder="Select end date">
                        </div>
                        <div class="form-group col-md-12">
                            <label class="form-label">Previous Salary</label>
                            <input type="number" name="experience[${experienceRowCount}][previous_salary]" 
                                class="form-control" placeholder="Enter previous salary">
                        </div>
                        <div class="form-group col-md-12 text-end">
                            <button type="button" class="btn btn-danger remove-experience-row">
                                <i class="ti ti-trash me-1"></i>{{ __('Remove') }}
                            </button>
                        </div>
                    </div>
                `;
                
                $('#experience-details-container').append(newRow);
                experienceRowCount++;
            });

            // Remove experience row
            $(document).on('click', '.remove-experience-row', function() {
                $(this).closest('.experience-detail-row').remove();
            });
        });

        // Phone number validation
        function validateNumbers() {
            const phone = document.getElementsByName('phone')[0].value;
            const officePhoneOne = document.getElementsByName('office_phone_one')[0].value;
            const officePhoneTwo = document.getElementsByName('office_phone_two')[0].value;
            const emergencyNumber = document.getElementsByName('emergency_number')[0].value;

            const numbers = [phone, officePhoneOne, officePhoneTwo, emergencyNumber];
            const errorIds = ['phone-error', 'office_phone_one-error', 'office_phone_two-error', 'emergency_number-error'];
            
            // Clear previous errors
            errorIds.forEach(id => document.getElementById(id).innerText = '');
            
            // Check for duplicates
            for (let i = 0; i < numbers.length; i++) {
                if (numbers[i]) {
                    for (let j = 0; j < numbers.length; j++) {
                        if (i !== j && numbers[i] && numbers[i] === numbers[j]) {
                            document.getElementById(errorIds[i]).innerText = 'Do not use the same number in multiple fields.';
                            document.getElementById(errorIds[j]).innerText = 'Do not use the same number in multiple fields.';
                        }
                    }
                }
            }
        }

        // Project dropdown change event
        document.addEventListener('DOMContentLoaded', function () {
            const projectDropdown = document.getElementById('project_id');
            const siteDropdown = document.getElementById('site_id');

            if (projectDropdown && siteDropdown) {
                projectDropdown.addEventListener('change', function () {
                    const projectId = this.value;

                    // Clear the Site dropdown and show a loading message
                    siteDropdown.innerHTML = '<option value="">Loading...</option>';

                    if (projectId) {
                        // Fetch sites for the selected project
                        fetch(`/get-sites-by-project/${projectId}`)
                            .then(response => response.json())
                            .then(data => {
                                siteDropdown.innerHTML = '<option value="">Select Site</option>';
                                data.sites.forEach(site => {
                                    const option = document.createElement('option');
                                    option.value = site.id;
                                    option.textContent = site.name;
                                    siteDropdown.appendChild(option);
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching sites:', error);
                                siteDropdown.innerHTML = '<option value="">Error loading sites</option>';
                            });
                    } else {
                        siteDropdown.innerHTML = '<option value="">Select Project First</option>';
                    }
                });
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            // Handle education document preview
            $(document).on('change', '.education-document', function() {
                const input = this;
                const row = $(this).closest('.education-detail-row');
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Remove any existing preview
                        row.find('.document-preview-img').remove();
                        row.find('.document-preview-text').remove();
                        
                        // Add preview for image files
                        if (input.files[0].type.match('image.*')) {
                            const preview = $('<img class="document-preview-img img-thumbnail mt-2">');
                            preview.attr('src', e.target.result);
                            row.find('.preview-container').html(preview);
                        } else {
                            row.find('.preview-container').html('<span class="document-preview-text text-muted text-xs d-block mt-2">File selected (not an image preview)</span>');
                        }
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                }
            });
        });

    </script>
@endpush