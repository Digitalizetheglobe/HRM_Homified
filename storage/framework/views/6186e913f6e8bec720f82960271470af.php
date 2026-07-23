<?php
    use App\Models\Utility;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    $users = Auth::user();
    $currantLang = $users->currentLanguage();
    $profile = asset('storage/uploads/avatar/');
    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::user()->id)
        ->where('seen', 0)
        ->count();
    $unseen_count = DB::select('SELECT from_id, COUNT(*) AS totalmasseges FROM ch_messages WHERE seen = 0 GROUP BY from_id');

    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::id())
        ->where('seen', 0)
        ->count();

    // Get leave notifications (for company type and forwarded Casual Leaves to HR/Director)
    $leaveNotifications = collect([]);
    $unseenLeaveCount = 0;
    if (\Auth::user()->type == 'company') {
        // Company sees all pending leaves that haven't been cleared
        $leaveNotifications = \App\Models\Leave::where('status', 'pending')
            ->where('seen_by_manager', 0)
            ->with(['employees.user', 'leaveType'])
            ->orderBy('created_at', 'desc')
            ->get();
        $unseenLeaveCount = $leaveNotifications->count();
    } elseif (in_array(strtolower(\Auth::user()->type), ['director', 'hr'])) {
        // Directors and HR see only forwarded Casual Leave requests that haven't been cleared
        $leaveNotifications = \App\Models\Leave::where('status', 'pending')
            ->where('forwarded_to_director_id', \Auth::id())
            ->where('company_approved', true)
            ->where('director_approved', false)
            ->where('seen_by_director', 0)
            ->whereHas('leaveType', function($query) {
                $query->where('title', 'Casual Leave');
            })
            ->with(['employees.user', 'leaveType', 'forwardedByCompany'])
            ->orderBy('forwarded_at', 'desc')
            ->get();
        $unseenLeaveCount = $leaveNotifications->count();
    }

    // Get booking notifications (for company, hr, director types)
    $bookingNotifications = collect([]);
    $unseenBookingCount = 0;
    if (in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director'])) {
        try {
            if (\Schema::hasTable('notifications')) {
                $bookingNotifications = Auth::user()->unreadNotifications()
                    ->where('type', 'App\Notifications\BookingCreatedNotification')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
                $unseenBookingCount = $bookingNotifications->count();
            }
        } catch (\Exception $e) {
            // Table doesn't exist or query failed, use empty collection
            $bookingNotifications = collect([]);
            $unseenBookingCount = 0;
        }
    }

    // Get attendance regularization notifications (for company, hr, director types)
    $regularisationNotifications = collect([]);
    $unseenRegularisationCount = 0;
    if (in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director'])) {
        try {
            if (\Schema::hasTable('notifications')) {
                // Get regularisation notifications - use OR condition to catch both exact and partial matches
                $regularisationNotifications = Auth::user()->unreadNotifications()
                    ->where(function($query) {
                        $query->where('type', 'App\Notifications\AttendanceRegularisationNotification')
                              ->orWhere('type', 'LIKE', '%AttendanceRegularisation%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
                
                // Count unread notifications
                $unseenRegularisationCount = $regularisationNotifications->count();
            }
        } catch (\Exception $e) {
            // Table doesn't exist or query failed, use empty collection
            \Log::error('Error fetching regularisation notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $regularisationNotifications = collect([]);
            $unseenRegularisationCount = 0;
        }
    }

    // Get pending employee notifications
    $pendingEmployees = collect([]);
    $unseenPendingEmployeeCount = 0;
    if (in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director'])) {
        $pendingEmployees = \App\Models\Employee::where('approval_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $unseenPendingEmployeeCount = $pendingEmployees->count();
    }

    // Calculate total unseen notifications
    $totalUnseenCount = $unseenLeaveCount + $unseenBookingCount + $unseenRegularisationCount + $unseenPendingEmployeeCount;
?>

<?php if(isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on'): ?>
    <header class="dash-header transprent-bg" style="background: linear-gradient(to right, #fff, #fff); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
<?php else: ?>
    <header class="dash-header" style="background: linear-gradient(to right, #0a3772, #008ecc);">
<?php endif; ?>

<div class="header-wrapper" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
    <div class="me-auto dash-mob-drp">
        <ul class="list-unstyled" style="display: flex; align-items: center;">
            <li class="dash-h-item mob-hamburger">
                <a href="#!" class="dash-head-link" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
            </li>
            <li class="dropdown dash-h-item drp-company">
                <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                   role="button" aria-haspopup="false" aria-expanded="false" style="background-color: white;">
                    <span class="theme-avtar" style="background-color: white;">
                        <img alt="User Avatar"
                             src="<?php echo e(asset('storage/uploads/avatar/avatar.png')); ?>"
                             class="header-avtar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: white;">
                    </span>
                    <span class="hide-mob ms-2" style="background-color: white;"><?php echo e('Hi, ' . Auth::user()->name . '!'); ?>

                        <i class="ti ti-chevron-down drp-arrow nocolor hide-mob" style="background-color: white;"></i>
                    </span>
                </a>
                <div class="dropdown-menu dash-h-dropdown" style="background-color: white;">
                    <a href="<?php echo e(route('profile')); ?>" class="dropdown-item" style="background-color: white;">
                        <i class="ti ti-user"></i>
                        <span><?php echo e(__('My Profile')); ?></span>
                    </a>
                    <a href="<?php echo e(route('logout')); ?>" class="dropdown-item"
                       onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                       style="background-color: white;">
                        <i class="ti ti-power"></i>
                        <span><?php echo e(__('Logout')); ?></span>
                    </a>
                    <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                        <?php echo csrf_field(); ?>
                    </form>
                </div>
            </li>
        </ul>
    </div>
    
    <!-- Marquee Section for Daily Quote -->
    <div class="quote-container" style="display: flex; justify-content: center; align-items: center; flex-grow: 1; overflow: hidden;">
        <marquee behavior="scroll" direction="left" scrollamount="6" style="color: #0a3c77; font-size: 18px; font-weight: bold; width: 100%; margin-left: 11px;">
            " <?php echo e($quote->quote ?? 'No quote for today!!'); ?> "
        </marquee>
    </div>

    <div class="ms-auto" style="display: flex; justify-content: flex-end; align-items: center;">
        <ul class="list-unstyled" style="display: flex; align-items: center;">
            <?php if(\Auth::user()->type == 'company' || in_array(strtolower(\Auth::user()->type), ['hr', 'director'])): ?>
                <li class="dropdown dash-h-item drp-notification">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0 position-relative" 
                        data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false" id="unified-notification-btn">
                        <i class="ti ti-bell fs-7"></i>

                        <?php if($totalUnseenCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger unified-counter">
                                <?php echo e($totalUnseenCount); ?>

                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end" style="max-width: 400px;">
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo e(__('Notifications')); ?></h6>
                            <?php if($leaveNotifications->count() > 0 || $bookingNotifications->count() > 0 || $regularisationNotifications->count() > 0 || $pendingEmployees->count() > 0): ?>
                                <a href="#!" id="mark-all-read" class="text-sm text-primary" style="font-size: 12px;"><?php echo e(__('Clear All')); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="noti-body" style="max-height: 400px; overflow-y: auto;">
                            
                            <?php if($pendingEmployees->count() > 0): ?>
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                        <i class="ti ti-users"></i> Pending Employees
                                    </h6>
                                </div>
                                <?php $__currentLoopData = $pendingEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="d-flex align-items-center p-2 border-bottom" style="background-color: #f8f9fa;">
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0" style="font-size: 14px;">
                                                <?php echo e($employee->full_name); ?>

                                                <span class="text-muted" style="font-size: 12px;">(<?php echo e($employee->formatted_id); ?>)</span>
                                            </h6>
                                            <p class="mb-0 text-muted" style="font-size: 12px;">
                                                Status: <span class="text-warning">Pending Approval</span>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo e($employee->created_at->diffForHumans()); ?></small>
                                            <br>
                                        </div>
                                        <div class="action-btn bg-success ms-2">
                                            <a href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>" 
                                                class="mx-3 btn btn-sm align-items-center"
                                                data-bs-toggle="tooltip" title="<?php echo e(__('View Profile')); ?>">
                                                <i class="ti ti-caret-right text-white"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            
                            <?php if((\Auth::user()->type == 'company' || in_array(strtolower(\Auth::user()->type), ['director', 'hr'])) && $leaveNotifications->count() > 0): ?>
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                        <i class="ti ti-calendar-time"></i> 
                                        <?php if(\Auth::user()->type == 'company'): ?>
                                            Leave Requests
                                        <?php else: ?>
                                            Forwarded Leave Requests
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <?php $__currentLoopData = $leaveNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="d-flex align-items-center p-2 border-bottom leave-notification-item" 
                                        data-leave-id="<?php echo e($leave->id); ?>"
                                        style="background-color: <?php echo e(($leave->seen_by_manager || $leave->seen_by_director) ? '#fff' : '#f8f9fa'); ?>;">
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0" style="font-size: 14px;">
                                                <?php if($leave->employees && $leave->employees->user): ?>
                                                    <?php echo e($leave->employees->user->name); ?>

                                                <?php elseif($leave->employee_name): ?>
                                                    <?php echo e($leave->employee_name); ?>

                                                <?php else: ?>
                                                    Unknown Employee
                                                <?php endif; ?>
                                                <span class="text-muted" style="font-size: 12px;">
                                                    (<?php echo e($leave->leaveType->title ?? 'N/A'); ?>)
                                                </span>
                                            </h6>
                                            <p class="mb-0 text-muted" style="font-size: 12px;">
                                                <?php echo e($leave->start_date); ?> to <?php echo e($leave->end_date); ?><br>
                                                Reason: <?php echo e(Str::limit($leave->leave_reason, 30)); ?>

                                                <?php if(strtolower(\Auth::user()->type) == 'director' && $leave->forwardedByCompany): ?>
                                                    <br><small class="text-info">Forwarded by: <?php echo e($leave->forwardedByCompany->name); ?></small>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?php if($leave->forwarded_at): ?>
                                                    <?php echo e(\Carbon\Carbon::parse($leave->forwarded_at)->diffForHumans()); ?>

                                                <?php else: ?>
                                                    <?php echo e($leave->created_at->diffForHumans()); ?>

                                                <?php endif; ?>
                                            </small>
                                            <br>
                                        </div>

                                        <div class="action-btn bg-success ms-2">
                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                data-size="lg"
                                                data-url="<?php echo e(URL::to('leave/' . $leave->id . '/action')); ?>"
                                                data-ajax-popup="true"
                                                data-size="md"
                                                data-title="<?php echo e(__('Leave Action')); ?>">
                                                <i class="ti ti-caret-right text-white"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            
                            <?php if(in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director']) && $bookingNotifications->count() > 0): ?>
                                <?php if(\Auth::user()->type == 'company' && $leaveNotifications->count() > 0): ?>
                                    <div class="px-3 py-2 bg-light border-top border-bottom mt-2">
                                        <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                            <i class="ti ti-calendar-event"></i> Booking Notifications
                                        </h6>
                                    </div>
                                <?php else: ?>
                                    <div class="px-3 py-2 bg-light border-bottom">
                                        <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                            <i class="ti ti-calendar-event"></i> Booking Notifications
                                        </h6>
                                    </div>
                                <?php endif; ?>
                                <?php $__currentLoopData = $bookingNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $data = $notification->data;
                                    ?>
                                    <div class="d-flex align-items-center p-2 border-bottom booking-notification-item" 
                                        data-notification-id="<?php echo e($notification->id); ?>"
                                        style="background-color: <?php echo e($notification->read_at ? '#fff' : '#f8f9fa'); ?>;">
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0" style="font-size: 14px;">
                                                New Booking Created
                                                <span class="text-muted" style="font-size: 12px;">
                                                    (<?php echo e($data['project_name'] ?? 'N/A'); ?>)
                                                </span>
                                            </h6>
                                            <p class="mb-0 text-muted" style="font-size: 12px;">
                                                Applicant: <?php echo e($data['applicant_name'] ?? 'N/A'); ?><br>
                                                Unit: <?php echo e($data['unit_name'] ?? 'N/A'); ?>

                                            </p>
                                        </div>
                                        
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo e($notification->created_at->diffForHumans()); ?></small>
                                            <br>
                                        </div>

                                        <div class="action-btn bg-primary ms-2">
                                            <a href="<?php echo e($data['url'] ?? '#'); ?>" 
                                                class="mx-3 btn btn-sm align-items-center mark-notification-read"
                                                data-notification-id="<?php echo e($notification->id); ?>">
                                                <i class="ti ti-eye text-white"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            
                            <?php if(in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director']) && $regularisationNotifications->count() > 0): ?>
                                <?php if((\Auth::user()->type == 'company' && ($leaveNotifications->count() > 0 || $bookingNotifications->count() > 0)) || (in_array(strtolower(\Auth::user()->type), ['hr', 'director']) && ($leaveNotifications->count() > 0 || $bookingNotifications->count() > 0))): ?>
                                    <div class="px-3 py-2 bg-light border-top border-bottom mt-2">
                                        <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                            <i class="ti ti-clock-hour-4"></i> Attendance Regularisation Requests
                                        </h6>
                                    </div>
                                <?php else: ?>
                                    <div class="px-3 py-2 bg-light border-bottom">
                                        <h6 class="mb-0 text-muted" style="font-size: 12px; font-weight: 600;">
                                            <i class="ti ti-clock-hour-4"></i> Attendance Regularisation Requests
                                        </h6>
                                    </div>
                                <?php endif; ?>
                                <?php $__currentLoopData = $regularisationNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $data = $notification->data;
                                    ?>
                                    <div class="d-flex align-items-center p-2 border-bottom regularisation-notification-item" 
                                        data-notification-id="<?php echo e($notification->id); ?>"
                                        style="background-color: <?php echo e($notification->read_at ? '#fff' : '#f8f9fa'); ?>;">
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0" style="font-size: 14px;">
                                                Attendance Regularisation Request
                                                <span class="text-muted" style="font-size: 12px;">
                                                    (<?php echo e($data['employee_name'] ?? 'Employee'); ?>)
                                                </span>
                                            </h6>
                                            <p class="mb-0 text-muted" style="font-size: 12px;">
                                                Date: <?php echo e($data['date'] ?? 'N/A'); ?><br>
                                                <?php echo e(Str::limit($data['message'] ?? '', 50)); ?>

                                            </p>
                                        </div>
                                        
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo e($notification->created_at->diffForHumans()); ?></small>
                                            <br>
                                        </div>

                                        <div class="action-btn bg-info ms-2">
                                            <a href="<?php echo e($data['url'] ?? route('attendance-regularisation.index')); ?>" 
                                                class="mx-3 btn btn-sm align-items-center mark-notification-read"
                                                data-notification-id="<?php echo e($notification->id); ?>">
                                                <i class="ti ti-eye text-white"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            
                            <?php if($leaveNotifications->count() == 0 && $bookingNotifications->count() == 0 && $regularisationNotifications->count() == 0 && $pendingEmployees->count() == 0): ?>
                                <div class="text-center p-3">
                                    <p class="mb-0"><?php echo e(__('No notifications')); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="noti-footer">
                            <div class="d-grid gap-2">
                                <?php if((\Auth::user()->type == 'company' || in_array(strtolower(\Auth::user()->type), ['director', 'hr'])) && $leaveNotifications->count() > 0): ?>
                                    <a href="<?php echo e(route('leave.index')); ?>"
                                        class="btn dash-head-link justify-content-center text-primary mx-0">
                                        View All Leaves
                                    </a>
                                <?php endif; ?>
                                <?php if(in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director']) && $bookingNotifications->count() > 0): ?>
                                    <a href="<?php echo e(route('booking.all')); ?>"
                                        class="btn dash-head-link justify-content-center text-primary mx-0">
                                        View All Bookings
                                    </a>
                                <?php endif; ?>
                                <?php if(in_array(strtolower(\Auth::user()->type), ['company', 'hr', 'director']) && $regularisationNotifications->count() > 0): ?>
                                    <a href="<?php echo e(route('attendance-regularisation.index')); ?>"
                                        class="btn dash-head-link justify-content-center text-primary mx-0">
                                        View All Regularisation Requests
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>

</div>
</header>

<?php $__env->startPush('scripts'); ?>
    <script>
        $('#msg-btn').click(function() {
            let contactsPage = 1;
            let contactsLoading = false;
            let noMoreContacts = false;
            $.ajax({
                url: url + "/getContacts",
                method: "GET",
                data: {
                    _token: "<?php echo e(csrf_token()); ?>",
                    page: contactsPage,
                    type: 'custom',
                },
                dataType: "JSON",
                success: (data) => {
                    if (contactsPage < 2) {
                        $(".count-listOfContacts").html(data.contacts);
                    } else {
                        $(".count-listOfContacts").append(data.contacts);
                    }
                    $('.count-listOfContacts').find('.messenger-list-item').each(function(e) {
                        $('.noti-body .activeStatus').remove()
                        $('.noti-body .avatar').remove()
                        $(this).find('span').remove()
                        $(this).find('p').addClass("d-inline")
                        $(this).find('b').css({
                            "position": "absolute",
                            "right": "50px"
                        });
                        $(this).find('tr').remove('td')
                    })
                },
                error: (error) => {
                    setContactsLoading(false);
                    console.error(error);
                },
            });
        })
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.show-employee-info').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const leaveId = this.getAttribute('data-leave-id');
                    const card = document.getElementById(`employee-card-${leaveId}`);
                    
                    document.querySelectorAll('.employee-info-card').forEach(el => {
                        el.style.display = 'none';
                    });
                    
                    card.style.display = 'block';
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            document.querySelectorAll('.cancel-action').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.employee-info-card').style.display = 'none';
                });
            });

            document.querySelectorAll('.confirm-action').forEach(button => {
                button.addEventListener('click', function(e) {
                    if(!confirm(`Are you sure you want to ${this.getAttribute('data-status')} this leave request?`)) {
                        e.preventDefault();
                    }
                });
            });

            // Update unified badge when leave notification is interacted with
            document.querySelectorAll('.leave-notification-item').forEach(item => {
                const actionLink = item.querySelector('a[data-url*="leave"]');
                if (actionLink) {
                    actionLink.addEventListener('click', function() {
                        // Mark as seen visually
                        item.style.backgroundColor = '#fff';
                        // Update unified badge count
                        const badge = document.querySelector('.unified-counter');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent) || 0;
                            if (currentCount > 1) {
                                badge.textContent = currentCount - 1;
                            } else {
                                badge.remove();
                            }
                        }
                    });
                }
            });

            // Mark booking and regularisation notifications as read when view link is clicked
            document.querySelectorAll('.mark-notification-read').forEach(link => {
                link.addEventListener('click', function(e) {
                    const notificationId = this.getAttribute('data-notification-id');
                    if (notificationId) {
                        // Mark as read via AJAX using Laravel's notification system
                        fetch('<?php echo e(url("/notification/mark-read")); ?>/' + notificationId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(response => {
                            if (response.ok) {
                                // Handle both booking and regularisation notifications
                                const notificationItem = this.closest('.booking-notification-item') || 
                                                         this.closest('.regularisation-notification-item');
                                if (notificationItem) {
                                    notificationItem.style.backgroundColor = '#fff';
                                    // Update unified badge count
                                    const badge = document.querySelector('.unified-counter');
                                    if (badge) {
                                        const currentCount = parseInt(badge.textContent) || 0;
                                        if (currentCount > 1) {
                                            badge.textContent = currentCount - 1;
                                        } else {
                                            badge.remove();
                                        }
                                    }
                                }
                            }
                        }).catch(error => {
                            console.error('Error marking notification as read:', error);
                        });
                    }
                });
            });

            // Mark all notifications as read
            const markAllReadBtn = document.getElementById('mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetch('<?php echo e(route("notification.markAllRead")); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(response => {
                            if (response.ok) {
                                location.reload();
                            }
                        }).catch(error => {
                            console.error('Error marking all notifications as read:', error);
                        });
                });
            }
        });
    </script>
<?php $__env->stopPush(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/partial/Admin/header.blade.php ENDPATH**/ ?>