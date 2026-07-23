@extends('layouts.admin')

@section('page-title')
    {{ __('Carryforward Leaves') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Carryforward Leaves') }}</li>
@endsection

@section('content')
<div class="row">
    @if(isset($canViewAll) && $canViewAll)
    <!-- Employee Selection Section -->
    <div class="col-xl-12 mb-3">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Select Employee to View Carryforward Leaves') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('leave.carryforward') }}" method="GET" id="employeeSelectForm">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="employee_id" class="form-label">{{ __('Employee') }}</label>
                                <select name="employee_id" id="employee_id" class="form-control select2" onchange="document.getElementById('employeeSelectForm').submit();">
                                    <option value="">{{ __('Select an Employee') }}</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                                            {{ \Auth::user()->employeeIdFormat($employee->employee_id) }} - {{ $employee->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Details Section -->
    @if($selectedEmployeeId)
        @if(empty($employeeData))
            <div class="col-xl-12">
                <div class="alert alert-info">
                    {{ __('No leave data found for the selected employee in the current year.') }}
                </div>
            </div>
        @else
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>{{ __('Detailed Leave Calculation for Year: ') }} {{ $currentYear }}</h5>
                    </div>
                    <div class="card-body">
                        <!-- Navigation Tabs for Leave Types -->
                        <ul class="nav nav-tabs mb-3" id="leaveTypeTabs" role="tablist">
                            @php $firstTab = true; @endphp
                            @foreach($employeeData as $leaveType => $monthsData)
                                @php $tabId = str_replace(' ', '', $leaveType); @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $firstTab ? 'active' : '' }}" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button" role="tab" aria-controls="{{ $tabId }}" aria-selected="{{ $firstTab ? 'true' : 'false' }}">
                                        {{ $leaveType }}
                                    </button>
                                </li>
                                @php $firstTab = false; @endphp
                            @endforeach
                        </ul>

                        <!-- Tab Contents -->
                        <div class="tab-content" id="leaveTypeTabsContent">
                            @php $firstContent = true; @endphp
                            @foreach($employeeData as $leaveType => $monthsData)
                                @php $tabId = str_replace(' ', '', $leaveType); @endphp
                                <div class="tab-pane fade {{ $firstContent ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>{{ __('Month') }}</th>
                                                    <th class="text-center" title="Balance carried forward from the previous month">{{ __('Opening Balance') }} <i class="ti ti-info-circle"></i></th>
                                                    <th class="text-center text-success" title="Leaves earned or allocated this month">+ {{ __('Leaves Earned') }}</th>
                                                    <th class="text-center text-danger" title="Leaves taken or deducted this month">- {{ __('Leaves Used') }}</th>
                                                    <th class="text-center text-primary" title="Opening Balance + Leaves Earned - Leaves Used">{{ __('Available Balance') }} <i class="ti ti-info-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($monthsData as $data)
                                                    <tr>
                                                        <td class="font-weight-bold">{{ $data['month'] }}</td>
                                                        <td class="text-center">{{ $data['opening_balance'] }}</td>
                                                        <td class="text-center text-success">{{ $data['allocated'] }}</td>
                                                        <td class="text-center text-danger">{{ $data['used'] }}</td>
                                                        <td class="text-center font-weight-bold text-primary">{{ $data['available'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <small class="text-muted mt-2 d-block">
                                            <strong>Formula:</strong> Available Balance = Opening Balance + Leaves Earned - Leaves Used. The Available Balance at the end of the month becomes the Opening Balance for the next month.
                                        </small>
                                    </div>
                                </div>
                                @php $firstContent = false; @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
