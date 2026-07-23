<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Attendance Calendar')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('attendanceemployee.index')); ?>"><?php echo e(__('Attendance List')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Calendar')); ?></li>
<?php $__env->stopSection(); ?>

<?php
    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    $years = range(date('Y') - 5, date('Y') + 5);
?>

<?php $__env->startSection('content'); ?>
<?php
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
?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <?php echo e(Form::open(['route' => ['attendance.calendar'], 'method' => 'get', 'id' => 'attendance_calendar_filter'])); ?>

                    <div class="row align-items-center justify-content-end">
                        <div class="col-xl-10">
                            <div class="row">
                                <?php if(\Auth::user()->type != 'employee' || ($isHR && !request()->has('own'))): ?>
                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <?php echo e(Form::label('employee_id', __('Employee'), ['class' => 'form-label'])); ?>

                                            <select name="employee_id" class="form-control select2" id="employee_id">
                                                <option value=""><?php echo e(__('Select Employee')); ?></option>
                                                <?php $__currentLoopData = $allEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($employee->id); ?>" <?php echo e(($selectedEmployee && $selectedEmployee->id == $employee->id) ? 'selected' : ''); ?>>
                                                        <?php echo e(\Auth::user()->employeeIdFormat($employee->employee_id)); ?> - <?php echo e($employee->full_name); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="btn-box">
                                        <?php echo e(Form::label('month', __('Month'), ['class' => 'form-label'])); ?>

                                        <select name="month" class="form-control select" id="month">
                                            <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($key); ?>" <?php echo e($currentMonth == $key ? 'selected' : ''); ?>><?php echo e(__($name)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="btn-box">
                                        <?php echo e(Form::label('year', __('Year'), ['class' => 'form-label'])); ?>

                                        <select name="year" class="form-control select" id="year">
                                            <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($year); ?>" <?php echo e($currentYear == $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-auto mt-4">
                                    <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('attendance_calendar_filter').submit(); return false;">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </a>
                                    <a href="<?php echo e(route('attendance.calendar')); ?>" class="btn btn-sm btn-danger">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off"></i></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php echo e(Form::close()); ?>

                </div>
            </div>
        </div>

        <?php if($selectedEmployee): ?>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h5><?php echo e($selectedEmployee->full_name); ?> - <?php echo e(__($months[$currentMonth])); ?> <?php echo e($currentYear); ?></h5>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('attendance.calendar', ['employee_id' => $selectedEmployee->id, 'month' => $previousMonth, 'year' => $previousYear])); ?>" class="btn btn-primary d-inline-flex align-items-center">
                                        <i class="ti ti-chevron-left me-1"></i> <?php echo e(__('Previous')); ?>

                                    </a>
                                    <a href="<?php echo e(route('attendance.calendar', ['employee_id' => $selectedEmployee->id, 'month' => $nextMonth, 'year' => $nextYear])); ?>" class="btn btn-primary d-inline-flex align-items-center">
                                        <?php echo e(__('Next')); ?> <i class="ti ti-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-12 d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center"><span class="badge bg-success-light me-2 border border-success" style="width:15px;height:15px;display:inline-block;border-radius:3px;">&nbsp;</span> <?php echo e(__('Present')); ?></div>
                                <div class="d-flex align-items-center"><span class="badge bg-danger-light me-2 border border-danger" style="width:15px;height:15px;display:inline-block;border-radius:3px;">&nbsp;</span> <?php echo e(__('Absent')); ?></div>
                                <div class="d-flex align-items-center"><span class="badge bg-warning-light me-2 border border-warning" style="width:15px;height:15px;display:inline-block;border-radius:3px;">&nbsp;</span> <?php echo e(__('Late')); ?></div>
                                <div class="d-flex align-items-center"><span class="badge bg-pink-light me-2 border" style="width:15px;height:15px;display:inline-block;border-radius:3px;border-color:#e83e8c !important;">&nbsp;</span> <?php echo e(__('Leave')); ?></div>
                                <div class="d-flex align-items-center"><span class="badge bg-secondary-light me-2 border border-secondary" style="width:15px;height:15px;display:inline-block;border-radius:3px;">&nbsp;</span> <?php echo e(__('Week Off')); ?></div>
                                <div class="d-flex align-items-center"><span class="badge bg-primary-light me-2 border border-primary" style="width:15px;height:15px;display:inline-block;border-radius:3px;border-color:#5c59e8 !important;">&nbsp;</span> <?php echo e(__('Half Day / Single Punch')); ?></div>
                            </div>
                        </div>

                        <?php
                            $employeeInfo = $attendanceData[$selectedEmployee->id] ?? null;
                        ?>
                        <?php if($employeeInfo): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card shadow-none border mb-0">
                                        <div class="card-body p-3 bg-info-light" style="border-radius: 10px;">
                                            <div class="row text-center">
                                                <div class="col-md-4 border-end border-info">
                                                    <strong class="d-block mb-2" style="color: #ea3538;"><?php echo e(__('Comp-Off Summary')); ?></strong>
                                                    <span class="badge bg-success ms-1"><?php echo e(__('Earned:')); ?> <?php echo e($employeeInfo['total_earned_comp_offs']); ?></span>
                                                    <span class="badge bg-secondary ms-1"><?php echo e(__('Used:')); ?> <?php echo e($employeeInfo['total_used_comp_offs']); ?></span>
                                                    <span class="badge bg-danger ms-1"><?php echo e(__('Balance:')); ?> <?php echo e($employeeInfo['total_remaining_comp_offs']); ?></span>
                                                </div>
                                                <div class="col-md-4 border-end border-info">
                                                    <strong class="d-block mb-2" style="color: #ea3538;"><?php echo e(__('Earned Leave Summary')); ?></strong>
                                                    <span class="badge bg-success ms-1"><?php echo e(__('Earned:')); ?> <?php echo e($employeeInfo['el_earned'] ?? 0); ?></span>
                                                    <span class="badge bg-secondary ms-1"><?php echo e(__('Used:')); ?> <?php echo e($employeeInfo['el_used'] ?? 0); ?></span>
                                                    <span class="badge bg-danger ms-1"><?php echo e(__('Balance:')); ?> <?php echo e($employeeInfo['el_remaining'] ?? 0); ?></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong class="d-block mb-2" style="color: #ea3538;"><?php echo e(__('Sick Leave Summary')); ?></strong>
                                                    <span class="badge bg-success ms-1"><?php echo e(__('Earned:')); ?> <?php echo e($employeeInfo['sl_earned'] ?? 0); ?></span>
                                                    <span class="badge bg-secondary ms-1"><?php echo e(__('Used:')); ?> <?php echo e($employeeInfo['sl_used'] ?? 0); ?></span>
                                                    <span class="badge bg-danger ms-1"><?php echo e(__('Balance:')); ?> <?php echo e($employeeInfo['sl_remaining'] ?? 0); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="calendar-grid">
                            <?php
                                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                                $firstDay = date('N', strtotime("$currentYear-$currentMonth-01"));
                                $attendance = $attendanceData[$selectedEmployee->id]['data'] ?? [];
                            ?>

                            <div class="calendar-header-row">
                                <div class="calendar-day-head"><?php echo e(__('Mon')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Tue')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Wed')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Thu')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Fri')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Sat')); ?></div>
                                <div class="calendar-day-head"><?php echo e(__('Sun')); ?></div>
                            </div>

                            <div class="calendar-days-row">
                                <?php for($i = 1; $i < $firstDay; $i++): ?>
                                    <div class="calendar-day empty"></div>
                                <?php endfor; ?>

                                <?php for($day = 1; $day <= $daysInMonth; $day++): ?>
                                    <?php
                                        $dateString = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                        $dayData = $attendance[$dateString] ?? null;
                                        $class = '';
                                        $title = '';
                                        
                                        if($dayData) {
                                            switch($dayData['type']) {
                                                case 'present': 
                                                    $class = $dayData['is_late'] ? 'bg-warning-light' : 'bg-success-light';
                                                    $title = __('Clock In: ') . $dayData['clock_in'] . "\n" . __('Clock Out: ') . $dayData['clock_out'];
                                                    break;
                                                case 'half_day':
                                                    $class = 'bg-primary-light';
                                                    $title = __('Half Day: ') . $dayData['clock_in'] . ' - ' . $dayData['clock_out'];
                                                    break;
                                                case 'single_punch':
                                                    $class = 'bg-primary-light';
                                                    $title = __('Single Punch In: ') . $dayData['clock_in'];
                                                    break;
                                                case 'leave':
                                                    $class = 'bg-pink-light';
                                                    $title = __('Leave: ') . ($dayData['leave_type'] ?? '') . "\n" . ($dayData['reason'] ?? '');
                                                    break;
                                                case 'absent':
                                                    $class = 'bg-danger-light';
                                                    $title = __('Absent');
                                                    break;
                                                case 'week_off':
                                                    $class = 'bg-secondary-light';
                                                    $title = __('Week Off');
                                                    break;
                                            }
                                        }
                                        
                                        $isToday = $dateString == date('Y-m-d');
                                        
                                        $canEdit = \Auth::user()->type == 'company' || \Auth::user()->type == 'super admin' || \Auth::user()->can('attendance.calendar.update.all');
                                        $editClass = $canEdit && $dayData ? 'editable-day' : '';
                                        
                                        $dataAttrs = '';
                                        if($canEdit && $dayData) {
                                            $rawStatus = $dayData['raw_status'] ?? 'Absent';
                                            if ($dayData['type'] == 'leave') {
                                                $rawStatus = $dayData['leave_type'];
                                            }
                                            $dataAttrs = sprintf(
                                                'data-date="%s" data-status="%s" data-in="%s" data-out="%s"',
                                                $dateString,
                                                htmlentities($rawStatus),
                                                $dayData['clock_in'] ?? '',
                                                $dayData['clock_out'] ?? ''
                                            );
                                        }
                                    ?>
                                    <div class="calendar-day <?php echo e($class); ?> <?php echo e($isToday ? 'today' : ''); ?> <?php echo e($editClass); ?>" <?php echo $dataAttrs; ?>>
                                        <div class="day-number"><?php echo e($day); ?></div>
                                        <?php if($dayData && !empty($dayData['earned_comp_off'])): ?>
                                            <?php if(!empty($dayData['used_comp_off'])): ?>
                                                <div class="comp-off-badge" title="Used Comp-Off" style="position: absolute; top: 5px; right: 5px; background: #e9ecef; color: #6c757d; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: bold; border: 1px solid #ced4da; text-decoration: line-through;">
                                                    C
                                                </div>
                                            <?php else: ?>
                                                <div class="comp-off-badge" title="Available Comp-Off" style="position: absolute; top: 5px; right: 5px; background: #ffc107; color: #000; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: bold; border: 1px solid #d39e00;">
                                                    C
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if($dayData): ?>
                                            <div class="day-info">
                                                <?php if($dayData['type'] == 'present' || $dayData['type'] == 'half_day' || $dayData['type'] == 'single_punch'): ?>
                                                    <small class="d-block text-center"><?php echo e($dayData['clock_in'] != '00:00:00' ? date('H:i', strtotime($dayData['clock_in'])) : ''); ?></small>
                                                    <?php if($dayData['type'] == 'present' || $dayData['type'] == 'half_day'): ?>
                                                        <small class="d-block text-center"><?php echo e($dayData['clock_out'] != '00:00:00' ? date('H:i', strtotime($dayData['clock_out'])) : ''); ?></small>
                                                    <?php endif; ?>
                                                <?php elseif($dayData['type'] == 'leave'): ?>
                                                    <small class="d-block text-center text-truncate"><?php echo e($dayData['leave_type']); ?></small>
                                                <?php else: ?>
                                                    <small class="d-block text-center"><?php echo e(__(ucfirst(str_replace('_', ' ', $dayData['type'])))); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5><?php echo e(__('Please select an employee to view their attendance calendar.')); ?></h5>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttendanceModalLabel"><?php echo e(__('Edit Attendance')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAttendanceForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="employee_id" id="edit_employee_id" value="<?php echo e($selectedEmployee ? $selectedEmployee->id : ''); ?>">
                        <input type="hidden" name="date" id="edit_date">
                        
                        <div class="form-group">
                            <label for="edit_display_date" class="form-label"><?php echo e(__('Date')); ?></label>
                            <input type="text" class="form-control" id="edit_display_date" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_status" class="form-label"><?php echo e(__('Status')); ?></label>
                            <select class="form-control select2" name="status" id="edit_status" data-placeholder="<?php echo e(__('Select Status')); ?>">
                                <option value="" disabled selected><?php echo e(__('Select Status')); ?></option>
                                <option value="Present"><?php echo e(__('Present')); ?></option>
                                <option value="Half Day"><?php echo e(__('Half Day')); ?></option>
                                <option value="Paid Leave"><?php echo e(__('Paid Leave (Earned Leave)')); ?></option>
                                <option value="Sick Leave"><?php echo e(__('Sick Leave')); ?></option>
                                <option value="Comp-Off"><?php echo e(__('Comp-Off')); ?></option>
                                <option value="Absent"><?php echo e(__('Absent')); ?></option>
                            </select>
                        </div>
                        
                        <div class="row time-fields">
                            <div class="col-md-6 form-group">
                                <label for="edit_clock_in" class="form-label"><?php echo e(__('Clock In')); ?></label>
                                <input type="time" class="form-control" name="clock_in" id="edit_clock_in">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="edit_clock_out" class="form-label"><?php echo e(__('Clock Out')); ?></label>
                                <input type="time" class="form-control" name="clock_out" id="edit_clock_out">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                    <button type="button" class="btn btn-primary" id="saveAttendanceBtn"><?php echo e(__('Save Changes')); ?></button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .calendar-grid {
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        .calendar-header-row {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .calendar-day-head {
            padding: 10px;
            text-align: center;
            font-weight: bold;
            color: #555;
            border-right: 1px solid #eee;
        }
        .calendar-day-head:last-child { border-right: none; }
        
        .calendar-days-row {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .calendar-day {
            min-height: 100px;
            padding: 10px;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #eee;
            position: relative;
            transition: all 0.2s;
        }
        .calendar-day:nth-child(7n) { border-right: none; }
        .calendar-day.editable-day {
            cursor: pointer;
        }
        .calendar-day.editable-day:hover {
            filter: brightness(0.95);
            box-shadow: inset 0 0 0 2px rgba(92, 89, 232, 0.3);
        }
        .calendar-day.empty { background: #fafafa; }
        .day-number {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        .calendar-day.today {
            border: 2px solid #5c59e8 !important;
            z-index: 1;
        }
        .calendar-day.today .day-number {
            color: #5c59e8;
        }
        
        /* Status Backgrounds */
        .bg-success-light { background-color: rgba(40, 167, 69, 0.15) !important; color: #1e7e34; }
        .bg-danger-light { background-color: rgba(220, 53, 69, 0.15) !important; color: #bd2130; }
        .bg-warning-light { background-color: rgba(255, 193, 7, 0.15) !important; color: #856404; }
        .bg-info-light { background-color: rgba(23, 162, 184, 0.15) !important; color: #117a8b; }
        .bg-pink-light { background-color: rgba(232, 62, 140, 0.15) !important; color: #e83e8c; }
        .bg-secondary-light { background-color: rgba(108, 117, 125, 0.15) !important; color: #545b62; }
        .bg-primary-light { background-color: rgba(92, 89, 232, 0.15) !important; color: #5c59e8; }
        
        .day-info {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 70px;
                padding: 5px;
            }
            .day-number { font-size: 0.9rem; }
            .day-info { font-size: 0.7rem; }
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script-page'); ?>
    <script>
        $(document).ready(function() {
            // Toggle time fields visibility and set dynamic professional times
            $('#edit_status').on('change', function() {
                var status = $(this).val();
                
                if (status === 'Present') {
                    $('.time-fields').show();
                    // Apply default company times if they are empty
                    if (!$('#edit_clock_in').val()) $('#edit_clock_in').val('10:00');
                    if (!$('#edit_clock_out').val() || $('#edit_clock_out').val() === '14:30') {
                        $('#edit_clock_out').val('19:00'); // Full Day Time
                    }
                } else if (status === 'Half Day') {
                    $('.time-fields').show();
                    // Apply Half-Day default times
                    if (!$('#edit_clock_in').val()) $('#edit_clock_in').val('10:00');
                    if (!$('#edit_clock_out').val() || $('#edit_clock_out').val() === '19:00') {
                        $('#edit_clock_out').val('14:30'); // Half Day Time
                    }
                } else {
                    $('.time-fields').hide();
                }
            });

            // Handle clicking on a calendar day
            $('.editable-day').on('click', function() {
                var date = $(this).data('date');
                var status = $(this).data('status');
                var inTime = $(this).data('in');
                var outTime = $(this).data('out');

                $('#edit_date').val(date);
                $('#edit_display_date').val(date);
                
                // Map raw status to Dropdown options
                var uiStatus = ''; // Default to blank (Select Status placeholder)
                if(status === 'Present' || status === 'Single Punch In') uiStatus = 'Present';
                else if(status === 'Half Day') uiStatus = 'Half Day';
                else if(status === 'Paid Leave' || status === 'Earned Leave' || status === 'EL') uiStatus = 'Paid Leave';
                else if(status === 'Sick Leave' || status === 'SL') uiStatus = 'Sick Leave';
                else if(status === 'Comp-Off' || status === 'CO') uiStatus = 'Comp-Off';
                else if(status === 'Absent' || status === 'LOP' || status === 'Week Off') uiStatus = 'Absent';

                // Clear previous times before changing status
                $('#edit_clock_in').val('');
                $('#edit_clock_out').val('');

                if (inTime && inTime !== '00:00:00') $('#edit_clock_in').val(inTime);
                if (outTime && outTime !== '00:00:00') $('#edit_clock_out').val(outTime);

                // Triggering change will automatically set defaults if it's Present/Half Day and times are empty
                $('#edit_status').val(uiStatus).trigger('change');

                var modal = new bootstrap.Modal(document.getElementById('editAttendanceModal'));
                modal.show();
            });

            // Save changes
            $('#saveAttendanceBtn').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Saving...');
                
                $.ajax({
                    url: '<?php echo e(route("attendance.calendar.update")); ?>',
                    type: 'POST',
                    data: {
                        _token: $('#editAttendanceForm').find('input[name="_token"]').val(),
                        employee_id: $('#edit_employee_id').val(),
                        date: $('#edit_date').val(),
                        status: $('#edit_status').val(),
                        clock_in: $('#edit_clock_in').val(),
                        clock_out: $('#edit_clock_out').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            show_toastr('Success', response.success, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            show_toastr('Error', response.error, 'error');
                            btn.prop('disabled', false).text('Save Changes');
                        }
                    },
                    error: function() {
                        show_toastr('Error', 'An error occurred while saving.', 'error');
                        btn.prop('disabled', false).text('Save Changes');
                    }
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/attendance/calendar.blade.php ENDPATH**/ ?>