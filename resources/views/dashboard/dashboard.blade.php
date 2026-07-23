    @extends('layouts.admin')

    @section('page-title')
        {{ __('Dashboard') }}
    @endsection

    @section('content')
    <style>
        .fc-prev-button, .fc-next-button {
            padding: 5px 8px !important;
            font-size: 14px !important;
            background-color: #007bff !important;
            border-radius: 5px !important;
            border: none !important;
            color: white !important;
        }

        .fc-prev-button:hover, .fc-next-button:hover {
            background-color: #0056b3 !important;
        }

        #calendar {
            margin-bottom: 10px;
        }

        .calendar-navigation {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
    </style>

    <div>
        <div class="row">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if (\Auth::user()->type == 'employee')
                <div class="col-12 mb-4">
                    <h3 class="mb-0">{{ __('Welcome') }}, {{ Auth::user()->name }}</h3>
                </div>
                <div class="col-xxl-9">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row">
                                @if($emp)
                                <div class="col-xl-6">
                                    <div class="card">
                                        <div class="card-header d-flex align-items-center">
                                            <img src="{{ asset('storage/uploads/avatar/' . ($emp->user->avatar ?? 'default-avatar.png')) }}"
                                                alt="Profile Image"
                                                class="rounded-circle me-4"
                                                width="60"
                                                height="60">
                                            <div>
                                                <h4 class="mb-0" style="color:black;">{{ $emp->full_name }}</h4>
                                                <small style="font-size: 12px; color:black;">{{ $emp->department->name ?? 'No Department' }} Team</small><small style="font-size:16px; color:black;"> &nbsp{{ $emp->designation->name ?? 'No Designation' }}&nbsp</small><br>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Phone Number:<br></strong> {{ $emp->phone ?? 'N/A' }}</p><br>
                                            <p><strong>Email Address:<br></strong> {{ $emp->email ?? 'N/A' }}</p><br>
                                            <p><strong>Joined On:<br></strong> {{ \Carbon\Carbon::parse($emp->company_doj)->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="col-md-6">
                                    <div class="card" style="">
                                        <div class="card-header">
                                            <h5 style="font-size:20px;color:black">
                                                {{ __('Attendance') }} 
                                                <span class="badge bg-success" style="font-size: 10px; padding: 2px 5px; vertical-align: middle;">v2.0 Fast</span>
                                            </h5>
                                            <p id="currentDateTime"></p>
                                        </div>
                                        <div class="card-body text-center p-1">
                                            

                                            <p id="attendanceStatus" class="font-bold">
                                                @if (!isset($employeeAttendance) || !$employeeAttendance->clock_in)
                                                    <span class="text-primary"><i class="fas fa-fingerprint"></i> Not Punched In</span>
                                                @elseif ($employeeAttendance->clock_out == '00:00:00' || !$employeeAttendance->clock_out)
                                                    <span class="text-success"><i class="fas fa-fingerprint"></i> Punched In at {{ \Carbon\Carbon::parse($employeeAttendance->clock_in)->format('h:i A') }}</span>
                                                @else
                                                    <span class="text-danger"><i class="fas fa-sign-out-alt"></i> Punched Out at {{ \Carbon\Carbon::parse($employeeAttendance->clock_out)->format('h:i A') }}</span>
                                                @endif
                                            </p>

                                            {{ Form::open(['url' => 'attendanceemployee/attendance', 'method' => 'post', 'id' => 'attendanceForm']) }}
                                                @if (empty($employeeAttendance))
                                                    <button type="submit" value="0" name="in" id="clock_in" class="btn btn-primary">{{ __('Punch In') }}</button>
                                                @elseif ($employeeAttendance->clock_out == '00:00:00')
                                                    <button type="button" id="clock_out" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmClockOutModal">Punch Out</button>
                                                @else
                                                    <button type="button" disabled class="btn btn-secondary">{{ __('Completed') }}</button>
                                                @endif
                                            {{ Form::close() }}


                                            <div id="gpsMessage" class="alert d-none mt-2" role="alert"></div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($emp && $emp->department && (strtolower(trim($emp->department->name)) == 'human resource' || strtolower(trim($emp->department->name)) == 'hr'))
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-header card-body table-border-style d-flex flex-wrap justify-content-between align-items-center">
                                    <h5 class="mb-2 mb-md-0" style="font-size: 20px; color: black;">
                                        {{ __("Today's Attendance") }}
                                    </h5>
                                </div>
                                <div class="card-body" style="height: 300px; overflow: auto; padding-top: 25px;">
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
                                                                <div>--:--</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center">No attendance records found for today.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
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
                                    <div class="card-body" style="height: 300px; overflow: auto; padding-top:25px;">
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

                            <!-- Employees On Leave / WeekOff Card -->
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
                        @endif

                        
                        <div class="col-xl-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header card-body table-border-style d-flex justify-content-between align-items-center">
                                            <h5 style="font-size:20px; color:black; margin: 0;">{{ __('Notices') }}</h5>
                                        </div>
                                        <div class="card-body" style="height: 325px; overflow: auto; padding: 10px; padding-top:25px;">
                                            <div class="table-responsive" style="max-width:452px;">
                                                <table class="table table-bordered text-center">
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

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header card-body table-border-style">
                                            <h5 style="font-size:20px;color:black">{{ __('TO-DO Lists') }}</h5>
                                        </div>
                                        <div class="card-body" style="height: 324px; overflow:auto;">
                                            <div class="table-responsive"> 
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                        <th>{{ __('Task Title') }}</th>
                                                        <th>{{ __('Priority') }}</th>
                                                        <th>{{ __('Due Date') }}</th>
                                                        <th>{{ __('Status') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="list">
                                                        @foreach ($todos as $todo)
                                                            <tr>
                                                                <td>{{ $todo->task }}</td>
                                                                <td>
                                                                    @if($todo->priority == 1)
                                                                        <span class="badge bg-danger">{{ __('High') }}</span>
                                                                    @elseif($todo->priority == 2)
                                                                        <span class="badge bg-warning">{{ __('Medium') }}</span>
                                                                    @else
                                                                        <span class="badge bg-success">{{ __('Low') }}</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ \Carbon\Carbon::parse($todo->expires_at)->format('d M Y') }}</td>
                                                                <td>
                                                                    @if($todo->is_completed)
                                                                        <span class="badge bg-success">{{ __('Completed') }}</span>
                                                                    @else
                                                                        <span class="badge bg-danger">{{ __('Pending') }}</span>
                                                                    @endif
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
                                    <div class="text-center p-3">
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
                                        <!-- <input type="hidden" id="path_admin" value="{{ url('/') }}"> -->
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="card-body" style="padding-top:0px;">
                                <div id='calendar' class='calendar'></div>
                            </div>
                        </div>


                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="confirmClockOutModal" tabindex="-1" aria-labelledby="confirmClockOutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmClockOutModalLabel">Confirm Clock Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to clock out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmClockOutBtn">
                        Yes, Clock Out
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @push('script-page')
        <script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>

        @if (Auth::user()->type == 'employee')
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
                            left: 'prev',
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
        @endif

    @endpush

    @push('script-page')
    <script>
    document.addEventListener("DOMContentLoaded", function () {

        let isPunchInProgress = false;
        let prefetchedLocation = null;
        let lastPrefetchTime = 0;

        const clockInBtn  = document.getElementById("clock_in");
        const clockOutBtn = document.getElementById("clock_out");
        const confirmClockOutBtn = document.getElementById("confirmClockOutBtn");

        // START PREFETCHING IMMEDIATELY
        startLocationPrefetching();

        if (confirmClockOutBtn) {
            confirmClockOutBtn.addEventListener("click", function () {
                const modalEl = document.getElementById('confirmClockOutModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                submitAttendance('out');
            });
        }

        async function startLocationPrefetching() {
            if (!navigator.geolocation) return;
            
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    });
                });

                prefetchedLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                lastPrefetchTime = Date.now();
                console.log("📍 Location pre-fetched successfully");
            } catch (e) {
                console.warn("📍 Location pre-fetch failed:", e.message);
            }
        }

        function getLocation(options) {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error("Geolocation not supported"));
                    return;
                }
                
                // Add a hard timeout safety net
                const timeoutId = setTimeout(() => {
                    reject(new Error("Location request timed out"));
                }, (options.timeout || 10000) + 2000);

                navigator.geolocation.getCurrentPosition(
                    (pos) => { clearTimeout(timeoutId); resolve(pos); },
                    (err) => { clearTimeout(timeoutId); reject(err); },
                    options
                );
            });
        }

        async function captureGPSLocation(type) {
            // Use prefetched location if it's fresh (less than 2 minutes old)
            if (prefetchedLocation && (Date.now() - lastPrefetchTime < 120000)) {
                console.log("📍 Using fresh pre-fetched location");
                return prefetchedLocation;
            }

            return new Promise(async (resolve, reject) => {
                showMessage('info', '📍 Getting your current location...');
                
                try {
                    const position = await getLocation({
                        enableHighAccuracy: true,
                        timeout: 8000,
                        maximumAge: 30000
                    });
                    
                    const loc = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy || 100
                    };
                    resolve(loc);
                } catch (error) {
                    // Fallback to lower accuracy if high accuracy fails
                    try {
                        const position = await getLocation({
                            enableHighAccuracy: false,
                            timeout: 7000,
                            maximumAge: 60000
                        });
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy || 200
                        });
                    } catch (err2) {
                        reject(new Error('Location could not be captured. Please ensure GPS is enabled and permission is granted.'));
                    }
                }
            });
        }

        async function submitAttendance(type) {
            if (isPunchInProgress) return;
            isPunchInProgress = true;

            const btn = type === 'in' ? clockInBtn : clockOutBtn;
            const originalBtnText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            clearMessage();

            try {
                // STEP 1: Get Location
                const location = await captureGPSLocation(type);
                
                // STEP 2: Record Attendance
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                showMessage('info', '📡 Saving your attendance to server...');

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append(type === 'in' ? 'in' : 'out', '1');
                formData.append('latitude', location.latitude);
                formData.append('longitude', location.longitude);
                formData.append('accuracy', location.accuracy);

                const response = await fetch("{{ url('attendanceemployee/attendance') }}", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    let errorMsg = "Server error (" + response.status + "). Please contact HR.";
                    try {
                        const errorTextContent = await response.text();
                        const errorData = JSON.parse(errorTextContent);
                        if (errorData.message) errorMsg = errorData.message;
                    } catch (e) {
                        // ignore parse error
                    }

                    // Auto-recover from CSRF token mismatch (419) by seamlessly reloading
                    if (response.status === 419 || errorMsg.toLowerCase().includes('csrf')) {
                        showMessage('warning', '🔄 Session expired. Refreshing securely...');
                        setTimeout(() => window.location.reload(true), 1200);
                        return; // Stop execution, let it reload
                    }

                    throw new Error(errorMsg);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Attendance submission failed.');
                }

                // STEP 3: Success & Reload
                showMessage('success', '✅ ' + (data.message || 'Attendance recorded successfully!'));
                btn.innerHTML = '<i class="fas fa-check"></i> Done';
                
                // Safe reload - ensures page refreshes even if standard reload is blocked
                safeReload();

            } catch (error) {
                showMessage('danger', '❌ ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                isPunchInProgress = false;
            }
        }

        function safeReload() {
            setTimeout(() => {
                // Add a visual hint that it's reloading
                showMessage('info', '🔄 Reloading page... Please wait.');
                window.location.assign(window.location.href);
                
                // Absolute backup - force reload after another 3 seconds if still here
                setTimeout(() => {
                    window.location.reload(true);
                }, 3000);
            }, 1000);
        }

        if (clockInBtn) {
            clockInBtn.addEventListener("click", function (e) {
                e.preventDefault();
                submitAttendance('in');
            });
        }

        function showMessage(type, text) {
            const box = document.getElementById('gpsMessage');
            if (!box) return;
            box.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
            const map = { success: 'alert-success', warning: 'alert-warning', danger: 'alert-danger', info: 'alert-info' };
            box.classList.add(map[type] || 'alert-info');
            box.innerHTML = text.replace(/\n/g, '<br>');
        }

        function clearMessage() {
            const box = document.getElementById('gpsMessage');
            if (box) box.classList.add('d-none');
        }
    });
    </script>
    @endpush
