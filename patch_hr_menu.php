<?php
$file = 'c:/xampp/htdocs/hrm_rising/resources/views/partial/Admin/menu.blade.php';
$c = file_get_contents($file);

// 1. Employee Menu
$oldEmployee = "@if(\$isFinanceAccountsUser() && \$employee)";
$newEmployee = "@if((\$isFinanceAccountsUser() || (\Auth::user()->type == 'employee' && \Auth::user()->isHR())) && \$employee)";
$c = str_replace($oldEmployee, $newEmployee, $c);

// 2. Attendance Menu
$oldAttendance = <<<OLD
                    <ul class="dash-submenu">
                        <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}">
                                {{ __('Attendance Calendar') }}
                            </a>
                        </li>
OLD;

$newAttendance = <<<NEW
                    <ul class="dash-submenu">
                        @if (\Auth::user()->type == 'employee' && \Auth::user()->isHR())
                            <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' && request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}?own=1">
                                    {{ __('Attendance Calendar') }}
                                </a>
                            </li>
                            <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' && !request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}">
                                    {{ __('All Employees Calendar') }}
                                </a>
                            </li>
                        @else
                            <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}">
                                    {{ __('Attendance Calendar') }}
                                </a>
                            </li>
                        @endif
NEW;
$c = str_replace($oldAttendance, $newAttendance, $c);

// Marked Attendance
$oldMarked = <<<OLD
                        @can('Manage Attendance')
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}">
                                    {{ __('Marked Attendance') }}
                                </a>
                            </li>
                        @endcan
OLD;

$newMarked = <<<NEW
                        @can('Manage Attendance')
                            @if (\Auth::user()->type == 'employee' && \Auth::user()->isHR())
                                <li class="dash-item">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}?own=1">
                                        {{ __('Marked Attendance') }}
                                    </a>
                                </li>
                                <li class="dash-item">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}">
                                        {{ __('All Employees Attendance') }}
                                    </a>
                                </li>
                            @else
                                <li class="dash-item">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}">
                                        {{ __('Marked Attendance') }}
                                    </a>
                                </li>
                            @endif
                        @endcan
NEW;
$c = str_replace($oldMarked, $newMarked, $c);

// 3. Leave Menu
$oldLeave = <<<OLD
                <ul class="dash-submenu">
                    @can('Manage Leave')
                        <li class="dash-item {{ Request::segment(1) == 'calender' ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}">
                                {{ __('Manage Leave') }}
                            </a>
                        </li>
                    @endcan
OLD;

$newLeave = <<<NEW
                <ul class="dash-submenu">
                    @can('Manage Leave')
                        @if (\Auth::user()->type == 'employee' && \Auth::user()->isHR())
                            <li class="dash-item {{ Request::segment(1) == 'calender' && request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}?own=1">
                                    {{ __('Manage Leave') }}
                                </a>
                            </li>
                            <li class="dash-item {{ Request::segment(1) == 'calender' && !request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}">
                                    {{ __('All Employees Leaves') }}
                                </a>
                            </li>
                        @else
                            <li class="dash-item {{ Request::segment(1) == 'calender' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}">
                                    {{ __('Manage Leave') }}
                                </a>
                            </li>
                        @endif
                    @endcan
NEW;
$c = str_replace($oldLeave, $newLeave, $c);

file_put_contents($file, $c);
echo "menu.blade.php updated for HR explicit options.\n";
