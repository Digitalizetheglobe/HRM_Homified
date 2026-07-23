@extends('layouts.admin')



@php
    $setting = App\Models\Utility::settings();
@endphp

@section('content')
<style>

    .fc-prev-button, .fc-next-button {
        padding: 6px 10px !important;
        font-size: 14px !important;
        background-color: #007bff !important;
        border-radius: 6px !important;
        border: none !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.3s ease;
    }

    .fc-prev-button:hover, .fc-next-button:hover {
        background-color: #0056b3 !important;
        transform: scale(1.05);
    }

    /* FullCalendar Responsive Header */
    .fc .fc-toolbar {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 15px !important;
        margin-bottom: 1.5em !important;
    }

    .fc .fc-toolbar-title {
        font-size: 1.2rem !important;
        margin: 0 !important;
        font-weight: 700 !important;
        color: #333 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
    }

    .fc .fc-button-group {
        display: flex !important;
        gap: 5px !important;
    }

    /* Calendar Grid Responsiveness */
    @media (max-width: 576px) {
        .fc .fc-toolbar-title {
            font-size: 1rem !important;
        }
        .fc .fc-col-header-cell-cushion,
        .fc .fc-daygrid-day-number {
            font-size: 0.8rem !important;
            padding: 4px !important;
        }
        .fc .fc-daygrid-day-top {
            justify-content: center !important;
        }
    }

    #calendar {
        margin-bottom: 10px;
    }

    .loading {
        position: relative;
        pointer-events: none;
        opacity: 0.7;
    }

    .loading:after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.7) url('{{ asset("assets/img/loading.gif") }}') no-repeat center;
        background-size: 50px 50px;
        z-index: 1000;
    }

    /* Dash content padding reduction for mobile */

    /* Tablet responsive (up to 1024px) */
    @media (max-width: 1024px) {
        .dash-content {
            padding-top: 10px !important;
        }
    }

    /* Mobile responsive (up to 768px) */
    @media (max-width: 768px) {
        .dash-content {
            padding-top: 8px !important;
        }
        .dashboard-header {
            margin-bottom: 15px !important;
        }
        .dashboard-header h3 {
            font-size: 20px;
        }
    }

    /* Small mobile responsive (up to 576px) */
    @media (max-width: 576px) {
        .dash-content {
            padding-top: 5px !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        .dashboard-header {
            margin-bottom: 10px !important;
            gap: 8px !important;
        }
        .dashboard-header h3 {
            font-size: 18px;
        }
        #customDatePicker {
            max-width: 130px !important;
            min-width: 110px !important;
            font-size: 12px !important;
            padding: 4px 6px !important;
        }
        #dateFilterButton {
            font-size: 12px !important;
            padding: 5px 10px !important;
        }
        .dropdown-menu {
            min-width: auto;
        }
        /* Cards responsive spacing */
        .card {
            margin-bottom: 10px !important;
        }
        .card-body {
            padding: 15px !important;
        }
    }

    @media (max-width: 400px) {
        .dashboard-header h3 {
            font-size: 16px;
        }
        #customDatePicker {
            max-width: 120px !important;
            min-width: 100px !important;
        }
    }

    #customDatePicker {
        height: 31.5px !important;
        padding: 6px 12px !important;
        font-size: 13px !important;
        border: 1px solid #ced4da;
        border-radius: 5px;
        background-color: #fff;
    }

    .dashboard-header h3 {
        transition: all 0.3s ease;
    }

    .dashboard-header.compact h3 {
        font-size: 1.1rem !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px; /* Limit width to ensure picker has room */
    }

    /* Mobile Stat Card Optimizations */
    @media (max-width: 576px) {
        .stat-card-body {
            padding: 12px !important;
        }
        .stat-icon-circle {
            width: 40px !important;
            height: 40px !important;
        }
        .stat-icon-circle i {
            font-size: 18px !important;
        }
        .stat-label h6 {
            font-size: 10px !important;
        }
        .stat-label h4 {
            font-size: 11px !important;
            font-weight: 700 !important;
        }
        .stat-count h4 {
            font-size: 20px !important;
            margin-top: 2px !important;
        }
        .br-mobile {
            display: none !important;
        }
        /* Optional: align items horizontally on mobile if they feel too tall */
        .stat-inner-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-data-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .dashboard-header.compact h3 {
            font-size: 0.9rem !important;
            max-width: 100px;
        }
    }
</style>
<div>
    <div class="row">
        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

    @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr' || Auth::user()->type == 'Director')

    <div class="row" style="padding-right: 0px;">
            <div class="d-flex justify-content-between align-items-center w-100 gap-2 dashboard-header" id="dashboardHeader" style="margin-bottom: 30px; flex-wrap: nowrap;">
                <div class="mb-0">
                    <h3 class="mb-0" id="dashboardGreeting">
                        @if(Auth::user()->type == 'employee')
                            {{ __('Welcome') }}, {{ Auth::user()->name }}
                        @else
                            {{ __('Welcome Admin') }}
                        @endif
                    </h3>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2" style="flex-shrink: 0;">
                    <div class="btn-group" style="z-index: 1;">
                        <button type="button" class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="dateFilterButton" style="white-space: nowrap; font-size: 13px; padding: 6px 12px;">
                            Today
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-value="today">Today</a></li>
                            <li><a class="dropdown-item" href="#" data-value="yesterday">Yesterday</a></li>
                            <li><a class="dropdown-item" href="#" data-value="custom">Select Date</a></li>
                        </ul>
                    </div>
                    <div class="position-relative" id="customDatePickerWrapper" style="display: none;">
                        <input type="date" class="form-control form-control-sm" id="customDatePicker" >
                    </div>
                </div>
            </div>
    </div>  
            <!-- Employee specific content -->
        


            <div class="col-xxl-9">
                <div class="row">
                    <!-- Left Side Cards -->
                    <div class="col-xl-12">

            
                       <div class="row">
                            <div class="col-xxl-12">
                                <div class="col-xl-12">
                                    <div class="row">
                                        <!-- first Card - Employees -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" onclick="window.location.href='employee'">
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #B55CC4; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-user-tie" style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color:#555657 !important; font-weight: 800; margin: 0;">Employees</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #0569a6;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color : #000 !important; "> {{ $countEmployee }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- second Card - Department -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" >
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #299dc6; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-sitemap"  style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color: #555657 !important; font-weight: 800; margin: 0;">Department</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #0569a6;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color : #000 !important; "> {{ $totalDepartment }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Third Card - Leaves -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" onclick="window.location.href='leave'">
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #28a745; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-calendar" style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color: #555657 !important; font-weight: 800; margin: 0;">Leaves</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #6c757d;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color:#000 !important; "> {{ $totalleaves }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fourth Card - Holidays -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" onclick="window.location.href='holiday'">
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #28a745; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-calendar-days" style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color: #555657 !important; font-weight: 800; margin: 0;">Holidays</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #0569a6;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color : #000 !important; "> {{ $totalHolidays }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    
                                        <!-- fifth Card - Projects -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" onclick="window.location.href='projects'">
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #F26522; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-diagram-project" style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color: #555657 !important; font-weight: 800; margin: 0;">Projects</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #6c757d;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color:#000 !important; "> {{ $totalProjects }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Six Card - Ticket -->
                                        <div class="col-6 col-lg-4 col-md-4">
                                            <div class="card" style="border-radius: 10px; background-color: #fff; cursor: pointer;" onclick="window.location.href='ticket'">
                                                <div class="card-body" style="padding: 20px;">
                                                    <div class="align-items-center">
                                                        <div class="col-auto">
                                                            <div style="background-color: #FD3995; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                                                                <i class="fa-solid fa-ticket" style="font-size: 25px; color: #fff;"></i>
                                                            </div>
                                                        </div><br>
                                                        <div class="col-auto" style="display: flex; align-items: center; gap: 5px;">
                                                            <h6 style="font-size: 14px; color: #515356; margin: 0;">Total</h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 15px; color: #555657 !important; font-weight: 800; margin: 0;">Ticket</h4>
                                                        </div>
                                                        <div class="col-auto">
                                                            <h6 style="font-size: 14px; color: #6c757d;"> </h6>
                                                            <h4 class="m-0 text-primary" style="font-size: 30px; color:#000 !important; "> {{ $countTicket }}  </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    

                        <!-- Additional Data Below Cards -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-body table-border-style d-flex flex-wrap justify-content-between align-items-center">
                                        <h5 class="mb-2 mb-md-0" style="font-size: 20px; color: black;">
                                            {{ __("Today's Attendance") }}
                                        </h5>
                                        
                                    </div>

                                    <div class="card-body" style="height: 300px; overflow: auto; padding: px; padding-top: 25px;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-left" id="attendanceTable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Employee Name') }}</th>
                                                        <th>{{ __('Punch-In Time') }}</th>
                                                        <th>{{ __('Punch-In Location') }}<br><small style="font-size: 10px;">(Captured At)</small></th>
                                                        <th>{{ __('Punch-Out Time') }}</th>
                                                        <th>{{ __('Punch-Out Location') }}<br><small style="font-size: 10px;">(Captured At)</small></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($presentEmployeesWithClockIn as $data)
                                                        <tr>
                                                            <td>{{ $data['employee']->name ?? 'N/A' }}</td>
                                                            <td>{{ $data['clock_in'] ?? '--:--' }}</td>
                                                            <td>
                                                                @if(!empty($data['clock_in_location']) && $data['clock_in_location'] != 'Location not captured yet')
                                                                    <div>
                                                                        <a href="https://www.google.com/maps?q={{ $data['clock_in_latitude'] }},{{ $data['clock_in_longitude'] }}" 
                                                                        target="_blank">
                                                                            {{ Str::limit($data['clock_in_location'], 50, '...') }}
                                                                        </a>
                                                                    </div>
                                                                    @if(!empty($data['clock_in_location_captured_at']))
                                                                        <small style="font-size: 10px; color: #6c757d;">
                                                                            @php
                                                                                $capturedAt = \Carbon\Carbon::parse($data['clock_in_location_captured_at']);
                                                                                $punchIn = \Carbon\Carbon::parse($data['clock_in']);
                                                                                $delay = $capturedAt->diffInSeconds($punchIn);
                                                                            @endphp
                                                                            Captured: {{ $capturedAt->format('h:i A') }}
                                                                            @if($delay > 30)
                                                                                <span style="color: #ff9800;">({{ $delay > 60 ? round($delay / 60) . ' min later' : $delay . ' sec later' }})</span>
                                                                            @endif
                                                                        </small>
                                                                    @endif
                                                                @else
                                                                    <div>{{ $data['clock_in_location'] ?? 'Location pending...' }}</div>
                                                                    <small style="font-size: 10px; color: #6c757d;">Location being captured in background</small>
                                                                @endif
                                                            </td>
                                                            <td>{{ $data['clock_out'] ?? '--:--' }}</td>
                                                            <td>
                                                                @if(!empty($data['clock_out_location']) && $data['clock_out_location'] != 'Location not captured yet')
                                                                    <div>
                                                                        <a href="https://www.google.com/maps?q={{ $data['clock_out_latitude'] }},{{ $data['clock_out_longitude'] }}" 
                                                                        target="_blank">
                                                                            {{ Str::limit($data['clock_out_location'], 50, '...') }}
                                                                        </a>
                                                                    </div>
                                                                    @if(!empty($data['clock_out_location_captured_at']))
                                                                        <small style="font-size: 10px; color: #6c757d;">
                                                                            @php
                                                                                $capturedAt = \Carbon\Carbon::parse($data['clock_out_location_captured_at']);
                                                                                $punchOut = \Carbon\Carbon::parse($data['clock_out']);
                                                                                $delay = $capturedAt->diffInSeconds($punchOut);
                                                                            @endphp
                                                                            Captured: {{ $capturedAt->format('h:i A') }}
                                                                            @if($delay > 30)
                                                                                <span style="color: #ff9800;">({{ $delay > 60 ? round($delay / 60) . ' min later' : $delay . ' sec later' }})</span>
                                                                            @endif
                                                                        </small>
                                                                    @endif
                                                                @elseif($data['clock_out'] != '--:--')
                                                                    <div>{{ $data['clock_out_location'] ?? 'Location pending...' }}</div>
                                                                    <small style="font-size: 10px; color: #6c757d;">Location being captured in background</small>
                                                                @else
                                                                    --
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5">{{ __('No attendance records found for today.') }}</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Not Clock In Employees Card -->
                            <div class="col-12 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header card-body table-border-style d-flex flex-wrap justify-content-between align-items-center">
                                        <h5 class="mb-2 mb-md-0" style="font-size:20px; color:black; margin: 0;">
                                            {{ __('Yet To Arrive ') }}
                                        </h5>
                                       
                                    </div>
                                    <div class="card-body" style="height: 300px; overflow: auto; padding: ; padding-top:25px;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-left" id="notClockInTable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Employee Name') }}</th>
                                                        <th>{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($notClockIns as $employee)
                                                        <tr>
                                                            <td>{{ $employee->full_name ?? 'N/A' }}</td>
                                                            <td style="color: red;">Absent</td>
                                                        </tr>
                                                    @endforeach
                                                    @if($notClockIns->isEmpty())
                                                        <tr>
                                                            <td colspan="2">All employees are present or accounted for</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Leaves Employees Card -->
                            <div class="col-12 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header card-body table-border-style d-flex flex-wrap justify-content-between align-items-center">
                                        <h5 class="mb-2 mb-md-0" style="font-size:20px; color:black; margin: 0;">
                                            {{ __('Employees On Leave / WeekOff') }}
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 300px; overflow: auto; padding-top:25px;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-left" id="onLeaveTable">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Employee Name') }}</th>
                                                        <th>{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($employeesNotWorkingToday as $employee)
                                                        <tr>
                                                            <td>{{ $employee['employee_name'] }}</td>
                                                            <td>{{ $employee['status'] }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2">No employees on leave or week off today</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-header card-body table-border-style">
                                    <h5 style="font-size:20px;color:black">{{ __('Project Details') }}</h5>
                                </div>
                                <div class="card-body" style="height: 300px; overflow:auto">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Project Name') }}</th>
                                                    <th>{{ __('Start Date') }}</th>
                                                    <th>{{ __('End Date') }}</th>
                                                    @if(Auth::user()->type != 'employee')
                                                        <th>{{ __('Assigned Employees') }}</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                @forelse ($projects as $project)
                                                    <tr>
                                                        <td>{{ $project->project_name }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($project->project_startdate)->format('d M Y') }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($project->project_enddate)->format('d M Y') }}</td>
                                                        @if(Auth::user()->type != 'employee')
                                                            <td>
                                                                @if(is_array($project->assigned_data))
                                                                    @php $empCount = 0; @endphp
                                                                    @foreach($project->assigned_data as $assignment)
                                                                        @foreach($assignment['employee_ids'] ?? [] as $employeeId)
                                                                            @if(isset($employees[$employeeId]))
                                                                                <span class="badge bg-success me-1 mb-1">
                                                                                    {{ $employees[$employeeId]->user->name ?? __('Unknown') }}
                                                                                </span>
                                                                                @php $empCount++; @endphp
                                                                                @if($empCount % 5 == 0)
                                                                                    <br>
                                                                                @endif
                                                                            @endif
                                                                        @endforeach
                                                                    @endforeach
                                                                @endif
                                                            </td>
                                                        @endif
                                    
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-left">{{ __('No projects assigned') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-12">
                            <div class="card">
                                    <div class="card-header card-body table-border-style d-flex flex-wrap justify-content-between align-items-center">
                                        <h5 class="mb-2 mb-md-0" style="font-size:20px; color:black; margin: 0;">
                                            {{ __('Notices') }}
                                        </h5>
                                        
                                    </div>
                                    <div class="card-body" style="height: 300px; overflow: auto; padding: ; padding-top:25px;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-left">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 60%;">Title</th>
                                                        <th style="width: 40%;">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($notices as $notice)
                                                        <tr>
                                                            <td style="word-wrap: break-word; white-space: normal;">
                                                                {{ Str::limit($notice->title, 50, '...') }}
                                                            </td>
                                                            <td>
                                                                {{ \Carbon\Carbon::parse($notice->notice_startdate)->format('d M Y') }} - 
                                                                {{ \Carbon\Carbon::parse($notice->notice_enddate)->format('d M Y') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                        </div>

                    </div>
                </div>
            </div>

              <!-- Right Side Calendar -->

                <div class="col-xxl-3" style="z-index: 0;">
                    <div class="d-flex flex-column gap-2 sticky-top" style="">
                        <div class="card flex-grow-1" style="height: 250px;">
                            <div class="card-header">
                                <h5 style="font-size:20px;color:black">{{ __("Upcoming Events This Month") }}</h5>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                @if(count($allEvents) > 0)
                                    <div class="list-group">
                                        @foreach($allEvents as $event)
                                            <span class="list-group-item list-group-item-action flex-column align-items-start">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">{{ $event['title'] }}</h6>
                                                    <small>{{ \Carbon\Carbon::parse($event['start'])->format('D, M d') }}</small>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        @if($event['type'] == 'birthday')
                                                            <span class="badge bg-success">Birthday</span>
                                                        @elseif($event['type'] == 'anniversary')
                                                            <span class="badge bg-primary">
                                                                <i class="ti ti-award me-1"></i>
                                                                {{ isset($event['years_label']) ? $event['years_label'] . ' Work Anniversary' : 'Work Anniversary' }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-info">Event</span>
                                                        @endif
                                                    </small>
                                                    @if(\Carbon\Carbon::parse($event['start'])->isToday())
                                                        <span class="badge bg-warning">Today</span>
                                                    @endif
                                                </div>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-left p-3">
                                        <p>No upcoming events this month</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card flex-grow-1">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>{{ __('Calendar') }}</h5>
                                        <input type="hidden" id="path_admin" value="{{ url('/') }}">
                                    </div>
                                    <div class="col-lg-6">
                                        @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
                                            <select class="form-control" name="calender_type" id="calender_type"
                                                style="float: right; width: 1px;" onchange="get_data()">
                                                <option value="local_calender" selected="true">{{ __('Local Calendar') }}</option>
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body " style="padding-top:0px;">
                                <div id='calendar'  class='calendar'></div>
                            </div>
                        </div>

                    </div>
                </div>


        @endif
    </div>
</div>
@endsection

@push('script-page')
    <script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>

    @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr' || Auth::user()->type == 'Director')
    <script type="text/javascript">
        $(document).ready(function() {
            get_data();
        });

        function get_data() {
            var calender_type = $('#calender_type :selected').val();

            $('#calendar').removeClass('local_calender google_calender');
            if (!calender_type) {
                calender_type = 'local_calender';
            }
            $('#calendar').addClass(calender_type);

            $.ajax({
                data: {
                    "_token": "{{ csrf_token() }}",
                    'calender_type': calender_type
                },
                success: function(data) {
                    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                        headerToolbar: {
                            left: 'prev', // Only navigation arrows
                            center: 'title',
                            right: 'next'
                        },
                        themeSystem: 'bootstrap',
                        slotDuration: '00:10:00',
                        allDaySlot: true,
                        navLinks: false,
                        droppable: true,
                        selectable: true,
                        selectMirror: true,
                        editable: true,
                        dayMaxEvents: true,
                        handleWindowResize: true,
                        height: '360px',
                    });
                    calendar.render();
                }
            });
        }
    </script>

    @else
        <script>
            $(document).ready(function() {
                get_data();
            });

            function get_data() {
                var calender_type = $('#calender_type :selected').val();

                $('#event_calendar').removeClass('local_calender');
                $('#event_calendar').removeClass('google_calender');
                if (calender_type == undefined) {
                    calender_type = 'local_calender';
                }
                $('#event_calendar').addClass(calender_type);

                $.ajax({
                    url: $("#path_admin").val() + "/event/get_event_data",
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'calender_type': calender_type
                    },
                    success: function(data) {
                        var etitle;
                        var etype;
                        var etypeclass;
                        var calendar = new FullCalendar.Calendar(document.getElementById('event_calendar'), {
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            buttonText: {
                                timeGridDay: "{{ __('Day') }}",
                                timeGridWeek: "{{ __('Week') }}",
                                // dayGridMonth: "{{ __('Month') }}"
                            },
                            // slotLabelFormat: {
                            //     hour: '2-digit',
                            //     minute: '2-digit',
                            //     hour12: false,
                            // },
                            themeSystem: 'tailwind',
                            slotDuration: '00:10:00',
                            allDaySlot: true,
                            navLinks: true,
                            droppable: true,
                            selectable: true,
                            selectMirror: true,
                            editable: true,
                            dayMaxEvents: true,
                            handleWindowResize: true,
                            events: data,
                            height: '400px',
                            // timeFormat: 'H(:mm)',

                        });

                        calendar.render();
                    }
                });
            };
        </script>
    @endif

    @if (Auth::user()->type == 'company' || Auth::user()->type == 'hr' || Auth::user()->type == 'Director')
        <script>
            (function() {
                var totalEmployees = {{ $totalEmployees }};
                var presentEmployees = {{ count($presentEmployeesWithClockIn) }};
                var attendancePercentage = {{ round($attendancePercentage, 2) }};
                
                var options = {
                    series: [attendancePercentage],
                    chart: {
                        height: 380,
                        type: 'radialBar',
                        offsetY: -20,
                        sparkline: {
                            enabled: true
                        }
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -90,
                            endAngle: 90,
                            track: {
                                background: "#eef5ff",
                                strokeWidth: '98%',
                                margin: 5,
                            
                            },
                            dataLabels: {
                                name: {
                                    show: true
                                },
                                value: {
                                    offsetY: -50,
                                    fontSize: '20px'
                                }
                            }
                        }
                    },
                    grid: {
                        padding: {
                            top: -10
                        }
                    },
                    colors: ["#68A288"],
                    labels: [''],
                    tooltip: {
                        enabled: true,
                        y: {
                            formatter: function(val) {
                                return `Out of ${totalEmployees} employees, ${presentEmployees} are present.`;
                            }
                        }
                    }
                };

                var chart = new ApexCharts(document.querySelector("#attendance-chart"), options);
                chart.render();
            })();
        </script>

        <style>
            .apexcharts-tooltip {
                background: #000 !important;
                color: #fff !important;
                border-radius: 8px;
                font-size: 14px;
            }
        </style>
    @endif
@endpush

@push('script-page')
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
    <script>
        (function() {
            var options = {
                chart: {
                    height: 265,
                    type: 'bar',
                    toolbar: {
                        show: false,
                    },
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 4,
                    curve: 'smooth'
                },
                series: {!! json_encode($chartData['data']) !!},
                xaxis: {
                    categories: {!! json_encode($chartData['labels']) !!},
                },
                colors: ['#b4d1c4', '#68a288'],
                fill: {
                    type: 'solid',
                },
                grid: {
                    strokeDashArray: 4,
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'right',
                },
                markers: {
                    size: 4,
                    colors: ['#000', '#FF3A6E'],
                    opacity: 2.5,
                    strokeWidth: 4,
                    hover: {
                        size: 8,
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#income-expense-chart"), options);
            chart.render();
        })();
    </script>

    <script>
        $(document).ready(function() {
            // Initialize custom date picker with today's date
            const today = new Date().toISOString().split('T')[0];
            const $customPicker = $('#customDatePicker');
            $customPicker.val(today);

            // Trigger the date picker when the input is clicked
            $customPicker.on('click', function() {
                if (this.showPicker) {
                    try {
                        this.showPicker();
                    } catch (e) {
                        $(this).focus();
                    }
                } else {
                    $(this).focus();
                }
            });

            // Handle date filter dropdown selection - only target items within the date filter dropdown
            $('#dateFilterButton').closest('.btn-group').find('.dropdown-item').on('click', function(e) {
                e.preventDefault();
                const filterType = $(this).data('value');
                
                // Only process if it's a valid filter type (has data-value attribute)
                if (!filterType) {
                    return; // Allow default behavior for items without data-value
                }
                
                const filterText = $(this).text();
                
                if (filterType === 'custom') {
                    $('#customDatePickerWrapper').show();
                    $('#dashboardHeader').addClass('compact');
                    $customPicker.focus();
                    // Update button text to the current value of the date picker
                    const selectedDate = $customPicker.val();
                    if (selectedDate) {
                        const dateObj = new Date(selectedDate);
                        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        $('#dateFilterButton').text(formattedDate);
                        loadDashboardData('custom', selectedDate);
                        
                        // Try to open picker automatically
                        if ($customPicker[0].showPicker) {
                            try { $customPicker[0].showPicker(); } catch(e) {}
                        }
                    } else {
                        $('#dateFilterButton').text(filterText);
                    }
                } else if (filterType === 'today' || filterType === 'yesterday') {
                    $('#dateFilterButton').text(filterText);
                    $('#customDatePickerWrapper').hide();
                    $('#dashboardHeader').removeClass('compact');
                    loadDashboardData(filterType);
                }
            });
            
            // Handle custom date selection
            $('#customDatePicker').on('change', function() {
                const selectedDate = $(this).val();
                if (selectedDate) {
                    // Format the date for display
                    const dateObj = new Date(selectedDate);
                    const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    $('#dateFilterButton').text(formattedDate);
                    loadDashboardData('custom', selectedDate);
                }
            });
            
            function loadDashboardData(filterType, customDate = null) {
                let url = '{{ route("dashboard.filter") }}';
                let data = {
                    _token: '{{ csrf_token() }}',
                    filter_type: filterType
                };
                
                if (filterType === 'custom' && customDate) {
                    data.custom_date = customDate;
                }
                
                $('.card-body').addClass('loading');
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            $('#todayEnquiryCount').text(response.todayEnquiryCount);
                            $('#todayBookingCount').text(response.todayBookingCount);
                            
                            updateTable('#attendanceTable tbody', response.presentEmployeesWithClockIn, 'attendance');
                            updateTable('#notClockInTable tbody', response.notClockIns, 'notClockIn');
                            updateTable('#onLeaveTable tbody', response.employeesNotWorkingToday, 'onLeave');
                        }
                    },
                    error: function(xhr) {
                        console.error('Dashboard filter error:', xhr);
                        let errorMessage = 'Error loading dashboard data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage += ': ' + xhr.responseJSON.message;
                        } else if (xhr.statusText) {
                            errorMessage += ': ' + xhr.statusText;
                        }
                        alert(errorMessage);
                    },
                    complete: function() {
                        $('.card-body').removeClass('loading');
                    }
                });
            }

            function updateTable(tableSelector, data, tableType) {
                const $table = $(tableSelector);
                $table.empty();
                
                if (data.length === 0) {
                    let noDataText = 'No data available';
                    let colspan = 5;
                    if (tableType === 'attendance') {
                        noDataText = 'No attendance records found.';
                        colspan = 5;
                    } else if (tableType === 'notClockIn') {
                        noDataText = 'All employees are present';
                        colspan = 2;
                    } else if (tableType === 'onLeave') {
                        noDataText = 'No employees on leave or week off today';
                        colspan = 2;
                    }
                    
                    $table.append('<tr><td colspan="' + colspan + '">' + noDataText + '</td></tr>');
                    return;
                }
                
                data.forEach(function(item) {
                    let row = '';
                    if (tableType === 'attendance') {
                        row = `<tr>
                            <td>${item.employee ? item.employee.name : 'N/A'}</td>
                            <td>${item.clock_in || '--:--'}</td>
                            <td>${formatLocationWithTimestamp(
                                item.clock_in_location, 
                                item.clock_in_latitude, 
                                item.clock_in_longitude,
                                item.clock_in_location_captured_at,
                                item.clock_in
                            )}</td>
                            <td>${item.clock_out || '--:--'}</td>
                            <td>${item.clock_out === '--:--' ? '--' : formatLocationWithTimestamp(
                                item.clock_out_location, 
                                item.clock_out_latitude, 
                                item.clock_out_longitude,
                                item.clock_out_location_captured_at,
                                item.clock_out
                            )}</td>
                        </tr>`;
                    } else if (tableType === 'notClockIn') {
                        row = `<tr>
                            <td>${item.name || 'N/A'}</td>
                            <td style="color: red;">Absent</td>
                        </tr>`;
                    } else if (tableType === 'onLeave') {
                        row = `<tr>
                            <td>${item.employee_name || 'N/A'}</td>
                            <td>${item.status || 'Leave'}</td>
                        </tr>`;
                    }
                    $table.append(row);
                });
            }

            function formatLocationWithTimestamp(location, lat, lng, capturedAt, punchTime) {
                if (!location || location === 'Location not captured yet' || location === 'Location not available') {
                    return '<div>Location pending...</div><small style="font-size: 10px; color: #6c757d;">Location being captured in background</small>';
                }
                
                let locationHtml = '<div>';
                if (lat && lng) {
                    locationHtml += '<a href="https://www.google.com/maps?q='+lat+','+lng+'" target="_blank">';
                    locationHtml += (location.length > 50 ? location.substring(0, 50) + '...' : location);
                    locationHtml += '</a>';
                } else {
                    locationHtml += location;
                }
                locationHtml += '</div>';
                
                // Show capture timestamp if available
                if (capturedAt && punchTime) {
                    try {
                        const captured = new Date(capturedAt);
                        const punch = new Date('2000-01-01 ' + punchTime); // Create date with punch time
                        const delay = Math.floor((captured - punch) / 1000); // delay in seconds
                        
                        const capturedTime = captured.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                        locationHtml += '<small style="font-size: 10px; color: #6c757d;">Captured: ' + capturedTime;
                        
                        if (delay > 30) {
                            const delayText = delay > 60 ? Math.round(delay / 60) + ' min later' : delay + ' sec later';
                            locationHtml += ' <span style="color: #ff9800;">(' + delayText + ')</span>';
                        }
                        locationHtml += '</small>';
                    } catch (e) {
                        // Silent fail on date parsing
                    }
                }
                
                return locationHtml;
            }

            function formatLocation(location, lat, lng) {
                if (!location || location === 'Location not available' || location === 'Location not captured yet') {
                    if (lat && lng) {
                        return '<a href="https://www.google.com/maps?q='+lat+','+lng+'" target="_blank">View on Map</a>';
                    }
                    return 'Location pending...';
                }
                const truncated = location.length > 50 ? location.substring(0, 50) + '...' : location;
                return '<a href="https://www.google.com/maps?q='+lat+','+lng+'" target="_blank">'+truncated+'</a>';
            }

            
            function formatDateRange(start, end) {
                try {
                    const startDate = new Date(start);
                    const endDate = new Date(end);
                    return `${startDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}`;
                } catch (e) {
                    console.error('Error formatting date:', e);
                    return '--';
                }
            }
        });
    </script>
@endpush