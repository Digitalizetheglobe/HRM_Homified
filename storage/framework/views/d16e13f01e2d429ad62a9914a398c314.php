<?php
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $company_logo = \App\Models\Utility::GetLogo();
    $currUser = \Auth::user();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $currantLang = $currUser->currentLanguage();
    $emailTemplate = App\Models\EmailTemplate::getemailTemplate();
    $lang = $currUser->lang;
    $userType = \Auth::user()->type;
    
    $employee = \App\Models\Employee::where('user_id', $currUser->id)->first();

    // Determine if this is a pending employee — they only see their profile link
    $isPendingEmployee = ($currUser->type === 'employee') 
        && ($employee) 
        && (strtolower(trim($employee->approval_status ?? '')) !== 'approved');
?>
    
<?php
    // Helper function to check Finance & Accounts department
    $isFinanceAccountsUser = function() use ($currUser) {
        try {
            $userEmail = strtolower($currUser->email ?? '');
            $isFinanceByEmail = strpos($userEmail, 'accounts@') !== false || 
                                strpos($userEmail, 'finance@') !== false ||
                                strpos($userEmail, '@accounts') !== false ||
                                strpos($userEmail, '@finance') !== false;
            
            if ($isFinanceByEmail) return true;
            
            $employee = \App\Models\Employee::where('user_id', $currUser->id)->first();
            if ($employee && !empty($employee->department_id)) {
                $department = \App\Models\Department::where('id', $employee->department_id)
                    ->where('created_by', $currUser->creatorId())
                    ->first();
                
                if ($department) {
                    $deptName = strtolower(trim($department->name));
                    return $deptName == 'finance & accounts' || 
                           $deptName == 'finance and accounts' ||
                           $deptName == 'finance & account' ||
                           $deptName == 'finance & accounts team' ||
                           $deptName == 'finance and accounts team' ||
                           (strpos($deptName, 'finance') !== false && (strpos($deptName, 'account') !== false || strpos($deptName, 'accounts') !== false));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error checking Finance & Accounts department: ' . $e->getMessage());
        }
        return false;
    };
?>
<?php if(isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on'): ?>
<nav class="dash-sidebar light-sidebar transprent-bg" style="background: linear-gradient(to bottom, #000, #575757);">
<?php else: ?>
<nav class="dash-sidebar light-sidebar" style="background: linear-gradient(to bottom, #000, #050505);">
<?php endif; ?>

<div class="navbar-wrapper">
    <div class="m-header main-logo">
        <a href="<?php echo e($isPendingEmployee ? '#' : route('dashboard')); ?>" class="b-brand">
            <img src="<?php echo e(asset('storage/uploads/logo/logo-menu.png')); ?>"
                 alt="<?php echo e(config('app.name', 'HRMGo')); ?>" class="logo logo-lg" style="height: 90px; width: auto; object-fit: contain;">
        </a>
    </div>
    <div class="navbar-content">
        <ul class="dash-navbar">

            
            
            
            <?php if($isPendingEmployee): ?>
                <li class="dash-item">
                    <a href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>"
                       class="dash-link text-white text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-user text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('My Profile')); ?></span>
                    </a>
                </li>
                <li style="padding: 10px 16px; margin-top: 10px;">
                    <div style="background: rgba(255,165,0,0.15); border: 1px solid rgba(255,165,0,0.5); border-radius: 8px; padding: 10px; color: #ffc107; font-size: 12px; line-height: 1.5;">
                        <i class="ti ti-clock" style="margin-right: 5px;"></i>
                        <?php echo e(__('Your account is pending approval. Please complete your profile.')); ?>

                    </div>
                </li>

            <?php else: ?>
            
            
            

            <!-- Dashboard -->
            <?php if(\Auth::user()->type != 'super admin'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'dashboard' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('dashboard')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-home text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Dashboard')); ?></span>
                    </a>
                </li>
            <?php endif; ?>


            <!-- Employee -->
            <?php if(Gate::check('employee.view.own') || Gate::check('employee.view.all') || Gate::check('employee.profile.view.own')): ?>
                <?php
                    $employee = App\Models\Employee::where('user_id', \Auth::user()->id)->first(); 
                    $canViewAll = Gate::check('employee.view.all');
                    $canViewProfile = Gate::check('employee.profile.view.own');
                    $empOwn = \Auth::user()->type != 'company' && $employee && $canViewProfile;
                    $empCount = ($empOwn ? 1 : 0) + ($canViewAll ? 1 : 0);
                ?>
                
                <?php if($empCount > 1): ?>
                    <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'employee' ? 'dash-trigger active' : ''); ?>">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-users text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Employee')); ?></span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            <?php if($empOwn): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'employee' && Request::segment(2) != 'index' ? 'active' : ''); ?>">
                                <a href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>"
                                   class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md">
                                    <?php echo e(__('My Profile')); ?>

                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if($canViewAll): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'employee' && Request::segment(2) == 'index' ? 'active' : ''); ?>">
                                <a href="<?php echo e(route('employee.index')); ?>"
                                   class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Employee') : __('All Employees')); ?>

                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php elseif($empCount == 1): ?>
                    <li class="dash-item <?php echo e(Request::segment(1) == 'employee' ? 'active' : ''); ?>">
                        <?php if($empOwn): ?>
                            <a href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <?php else: ?>
                            <a href="<?php echo e(route('employee.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <?php endif; ?>
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-users text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Employee')); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if(\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'employee-permissions' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('employee-permissions.index')); ?>"
                       class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-lock text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Employee Permissions')); ?></span>
                    </a>
                </li>
            <?php endif; ?>            <!-- Attendance -->
            <?php if(Gate::check('attendance.calendar.view.own') || Gate::check('attendance.calendar.view.all') || 
                 Gate::check('attendance.regularisation.view.own') || Gate::check('attendance.regularisation.view.all') || 
                 Gate::check('attendance.marked.view.own') || Gate::check('attendance.marked.view.all') || 
                 Gate::check('attendance.bulk.view.own') || Gate::check('attendance.bulk.view.all') || 
                 Gate::check('attendance.biometric.view.own') || Gate::check('attendance.biometric.view.all')): ?>
                
                <li class="dash-item dash-hasmenu <?php echo e(Request::is('attendance*') || Request::is('biometric-attendance*') ? 'dash-trigger active' : ''); ?>">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-clock text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Attendance')); ?></span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        <!-- Attendance Calendar -->
                        <?php if(\Auth::user()->type != 'company' && Gate::check('attendance.calendar.view.own')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'attendance-calendar' && request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendance.calendar')); ?>?own=1">
                                    <?php echo e(__('Attendance Calendar')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('attendance.calendar.view.all')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'attendance-calendar' && !request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendance.calendar')); ?>">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Attendance Calendar') : __('All Employees Calendar')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Attendance Regularisation -->
                        <?php if(\Auth::user()->type != 'company' && Gate::check('attendance.regularisation.view.own')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'attendance-regularisation' && request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendance-regularisation.index')); ?>?own=1">
                                    <?php echo e(__('Attendance Regularisation')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('attendance.regularisation.view.all')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'attendance-regularisation' && !request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendance-regularisation.index')); ?>">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Attendance Regularisation') : __('All Employees Regularisation')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Marked Attendance -->
                        <?php if(\Auth::user()->type != 'company' && Gate::check('attendance.marked.view.own')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendanceemployee.index')); ?>?own=1">
                                    <?php echo e(__('Marked Attendance')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('attendance.marked.view.all')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendanceemployee.index')); ?>">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Marked Attendance') : __('All Employees Attendance')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Bulk Attendance -->
                        <?php if(Gate::check('attendance.bulk.view.all')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('attendanceemployee.bulkattendance')); ?>">
                                    <?php echo e(__('Bulk Attendance')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Biometric Attendance -->
                        <?php if(Gate::check('attendance.biometric.view.all')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('biometric-attendance.index')); ?>">
                                    <?php echo e(__('Biometric Attendance')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>




            <!-- Leave -->
            <?php if(Gate::check('leave.manage.view.own') || Gate::check('leave.manage.view.all') || 
                 Gate::check('leave.details.view.own') || Gate::check('leave.details.view.all')): ?>
            <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'calender' && Request::segment(2) == 'leave' ? 'dash-trigger active' : ''); ?>">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-calendar text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('Leave')); ?></span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <?php if(\Auth::user()->type != 'company' && Gate::check('leave.manage.view.own')): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'calender' && request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('leave.index')); ?>?own=1">
                                <?php echo e(__('Manage Leave')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if(Gate::check('leave.manage.view.all')): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'calender' && !request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('leave.index')); ?>">
                                <?php echo e(\Auth::user()->type == 'company' ? __('Manage Leave') : __('All Employees Leaves')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if(Gate::check('leave.details.view.all')): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'leave-details' && !request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('leave.details')); ?>">
                                <?php echo e(\Auth::user()->type == 'company' ? __('Leave Details') : __('All Employees Leave Details')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if(\Auth::user()->type != 'employee'): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'compoff' ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('leave.compoff.index')); ?>">
                                <?php echo e(__('Comp-Off Leaves')); ?>

                            </a>
                        </li>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'carry-forward-leaves' ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('leave.carryforward')); ?>">
                                <?php echo e(__('Carryforward Leaves')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
             
            <!-- Employee Payroll section removed to rely on Spatie permissions block -->
                         <!-- Admin Payroll -->
            <?php if(Gate::check('payroll.salary.view.own') || Gate::check('payroll.salary.view.all') || 
                 Gate::check('payroll.payslip.view.own') || Gate::check('payroll.payslip.view.all')): ?>
                <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'setsalary' ? 'dash-trigger active' : ''); ?>">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-receipt text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Payroll')); ?></span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        <?php if(\Auth::user()->type != 'company' && Gate::check('payroll.salary.view.own')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'setsalary' && request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('setsalary.index')); ?>?own=1">
                                    <?php echo e(__('Set Salary')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('payroll.salary.view.all')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'setsalary' && !request('own') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('setsalary.index')); ?>">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Set Salary') : __('All Employees Salary')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if(\Auth::user()->type != 'company' && Gate::check('payroll.payslip.view.own')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('payslip.index')); ?>?own=1">
                                    <?php echo e(__('Payslip')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('payroll.payslip.view.all')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('payslip.index')); ?>">
                                    <?php echo e(\Auth::user()->type == 'company' ? __('Payslip') : __('All Employees Payslip')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if(Gate::check('payroll.salary_arrears.view.all') || \Auth::user()->type == 'company'): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'salary-arrears' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('salary-arrears.index')); ?>">
                                    <?php echo e(__('Salary Arrears')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('payroll.other_deduction.view.all') || \Auth::user()->type == 'company'): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'other-deduction' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('other-deduction.index')); ?>">
                                    <?php echo e(__('Other Deduction')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if(Gate::check('payroll.petrol_allowance.view.all') || \Auth::user()->type == 'company'): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'petrol-allowance' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('petrol-allowance.index')); ?>">
                                    <?php echo e(__('Petrol Allowance')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if(Gate::check('payroll.payable_days.view.all')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'payable-days' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('payable-days.index')); ?>">
                                    <?php echo e(__('Payable Days')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if($isFinanceAccountsUser() || \Auth::user()->type == 'company' || Gate::check('payroll.salary_processing.view.all')): ?>
                            <li class="dash-item <?php echo e(Request::segment(1) == 'salary-processing' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('salary-processing.index')); ?>">
                                    <?php echo e(__('Salary Processing')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Company Policy for Employees -->
            <!-- <?php if(\Auth::user()->type == 'employee'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'company-policy' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('company-policy.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-file-text text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Company Policy')); ?></span>
                    </a>
                </li>
            <?php endif; ?> -->

            <!-- Admin Company Policy -->
            <?php if(Gate::check('company_policy.manage.view.own') || Gate::check('company_policy.manage.view.all')): ?>
                <?php
                    $cpOwn = \Auth::user()->type != 'company' && Gate::check('company_policy.manage.view.own');
                    $cpAll = Gate::check('company_policy.manage.view.all');
                    $cpCount = ($cpOwn ? 1 : 0) + ($cpAll ? 1 : 0);
                ?>
                
                <?php if($cpCount > 1): ?>
                    <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'company-policy' ? 'dash-trigger active' : ''); ?>">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-file-text text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Company Policy')); ?></span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            <?php if($cpOwn): ?>
                                <li class="dash-item <?php echo e(Request::segment(1) == 'company-policy' && request('own') ? 'active' : ''); ?>">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('company-policy.index')); ?>?own=1">
                                        <?php echo e(__('My Policies')); ?>

                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if($cpAll): ?>
                                <li class="dash-item <?php echo e(Request::segment(1) == 'company-policy' && !request('own') ? 'active' : ''); ?>">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('company-policy.index')); ?>">
                                        <?php echo e(\Auth::user()->type == 'company' ? __('Company Policy') : __('All Company Policies')); ?>

                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php elseif($cpCount == 1): ?>
                    <li class="dash-item <?php echo e(Request::segment(1) == 'company-policy' ? 'active' : ''); ?>">
                        <a href="<?php echo e($cpOwn ? route('company-policy.index').'?own=1' : route('company-policy.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-file-text text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Company Policy')); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>



            <!-- Ticket -->
            <!-- Ticket -->
            <?php if(Gate::check('ticket.manage.view.own') || Gate::check('ticket.manage.view.all')): ?>
                <?php
                    $tktOwn = \Auth::user()->type != 'company' && Gate::check('ticket.manage.view.own');
                    $tktAll = Gate::check('ticket.manage.view.all');
                    $tktCount = ($tktOwn ? 1 : 0) + ($tktAll ? 1 : 0);
                ?>
                
                <?php if($tktCount > 1): ?>
                    <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'ticket' ? 'dash-trigger active' : ''); ?>">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-ticket text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Ticket')); ?></span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            <?php if($tktOwn): ?>
                                <li class="dash-item <?php echo e(Request::segment(1) == 'ticket' && request('own') ? 'active' : ''); ?>">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('ticket.index')); ?>?own=1">
                                        <?php echo e(__('My Tickets')); ?>

                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if($tktAll): ?>
                                <li class="dash-item <?php echo e(Request::segment(1) == 'ticket' && !request('own') ? 'active' : ''); ?>">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('ticket.index')); ?>">
                                        <?php echo e(\Auth::user()->type == 'company' ? __('Ticket') : __('All Employees Tickets')); ?>

                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php elseif($tktCount == 1): ?>
                    <li class="dash-item <?php echo e(Request::segment(1) == 'ticket' ? 'active' : ''); ?>">
                        <a href="<?php echo e($tktOwn ? route('ticket.index').'?own=1' : route('ticket.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-ticket text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Ticket')); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if(\Auth::user()->type == 'company'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'holiday' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('holiday.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-calendar-event text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Holiday')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Project Management -->
            <?php if($userType === 'company' || $userType === 'hr'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'projects' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('projects.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-clipboard-list text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Project')); ?></span>
                    </a>
                </li>

                <li class="dash-item <?php echo e(Request::segment(1) == 'units' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('units.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-building text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Units')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Enquiry -->
            <?php if(Gate::check('Manage TimeSheet')): ?>
            <li class="dash-item <?php echo e(Request::segment(1) == 'timesheet' ? 'active' : ''); ?>">
                <a href="<?php echo e(route('timesheet.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-question-mark text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('Enquiry')); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Booking -->
            <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'booking' ? 'dash-trigger active' : ''); ?>">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-calendar-event text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('Booking')); ?></span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <li class="dash-item <?php echo e(Request::segment(2) == 'all' ? 'active' : ''); ?>">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('booking.all')); ?>">
                            <?php echo e(__('All Booking')); ?>

                        </a>
                    </li>
                    <li class="dash-item <?php echo e(Request::segment(2) == 'add' ? 'active' : ''); ?>">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('booking.add')); ?>">
                            <?php echo e(__('Add Booking')); ?>

                        </a>
                    </li>
                    <li class="dash-item <?php echo e(Request::segment(2) == 'edit' ? 'active' : ''); ?>">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('edit.booking')); ?>">
                            <?php echo e(__('Edit Booking')); ?>

                        </a>
                    </li>
                </ul>
            </li>

            <!-- Recruitment -->
            <?php if(Gate::check('Manage Job') || Gate::check('Manage Job Application') || Gate::check('Manage Job OnBoard') || Gate::check('Manage Custom Question') || Gate::check('Manage Interview Schedule') || Gate::check('Manage Career')): ?>
                <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'job' || Request::segment(1) == 'job-application' ? 'dash-trigger active' : ''); ?>">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-license text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Recruitment')); ?></span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Job')): ?>
                            <li class="dash-item <?php echo e(Request::route()->getName() == 'job.index' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('job.index')); ?>">
                                    <?php echo e(__('Jobs')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Job')): ?>
                            <li class="dash-item <?php echo e(Request::route()->getName() == 'job.create' ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('job.create')); ?>">
                                    <?php echo e(__('Job Create')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Job Application')): ?>
                            <li class="dash-item <?php echo e(request()->is('job-application*') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('job-application.index')); ?>">
                                    <?php echo e(__('Job Application')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Job Application')): ?>
                            <li class="dash-item <?php echo e(request()->is('candidates-job-applications') ? 'active' : ''); ?>">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('job.application.candidate')); ?>">
                                    <?php echo e(__('Job Candidate')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Job OnBoard')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('job.on.board')); ?>">
                                    <?php echo e(__('Job On-Boarding')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Custom Question')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('custom-question.index')); ?>">
                                    <?php echo e(__('Custom Question')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Interview Schedule')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('interview-schedule.index')); ?>">
                                    <?php echo e(__('Interview Schedule')); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('Manage Career')): ?>
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('career', [\Auth::user()->creatorId(), $lang])); ?>" target="_blank">
                                    <?php echo e(__('Career')); ?>

                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>



            <!-- To-Do List -->
            <?php if(Gate::check('Manage ToDoList')): ?>
            <li class="dash-item <?php echo e(Request::segment(1) == 'todo' ? 'active' : ''); ?>">
                <a href="<?php echo e(route('todo.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="fas fa-tasks text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('To-Do List')); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Notice -->
            <?php if(Gate::check('Manage Notice')): ?>
            <li class="dash-item <?php echo e(Request::segment(1) == 'notices' ? 'active' : ''); ?>">
                <a href="<?php echo e(route('notices.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-bell text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('Notice')); ?></span>
                </a>
            </li>
            <?php endif; ?>



            <!-- Loan -->
            <?php if(\Auth::user()->type == 'company'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'loan' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('loan.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-cash text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Loan')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Exit Formalities -->
            <?php if(Gate::check('exit.resignation.view.own') || Gate::check('exit.resignation.view.all') || Gate::check('exit.termination.view.own') || Gate::check('exit.termination.view.all')): ?>
            <?php
                $resOwn = \Auth::user()->type != 'company' && Gate::check('exit.resignation.view.own');
                $resAll = Gate::check('exit.resignation.view.all');
                $termOwn = \Auth::user()->type != 'company' && Gate::check('exit.termination.view.own');
                $termAll = Gate::check('exit.termination.view.all');
                $exitCount = ($resOwn ? 1 : 0) + ($resAll ? 1 : 0) + ($termOwn ? 1 : 0) + ($termAll ? 1 : 0);
            ?>
            
            <?php if($exitCount > 1): ?>
            <li class="dash-item dash-hasmenu <?php echo e(Request::segment(1) == 'resignation' || Request::segment(1) == 'termination' ? 'dash-trigger active' : ''); ?>">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-user-x text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow"><?php echo e(__('Exit Formalities')); ?></span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <?php if($resOwn): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'resignation' && request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('resignation.index')); ?>?own=1">
                                <?php echo e(__('My Resignation')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if($resAll): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'resignation' && !request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('resignation.index')); ?>">
                                <?php echo e(\Auth::user()->type == 'company' ? __('Resignation') : __('All Employees Resignation')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if($termOwn): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'termination' && request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('termination.index')); ?>?own=1">
                                <?php echo e(__('My Termination')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if($termAll): ?>
                        <li class="dash-item <?php echo e(Request::segment(1) == 'termination' && !request('own') ? 'active' : ''); ?>">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="<?php echo e(route('termination.index')); ?>">
                                <?php echo e(\Auth::user()->type == 'company' ? __('Termination') : __('All Employees Termination')); ?>

                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php elseif($exitCount == 1): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'resignation' || Request::segment(1) == 'termination' ? 'active' : ''); ?>">
                    <?php if($resOwn): ?>
                        <a href="<?php echo e(route('resignation.index').'?own=1'); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <?php elseif($resAll): ?>
                        <a href="<?php echo e(route('resignation.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <?php elseif($termOwn): ?>
                        <a href="<?php echo e(route('termination.index').'?own=1'); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <?php else: ?>
                        <a href="<?php echo e(route('termination.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <?php endif; ?>
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-user-x text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Exit Formalities')); ?></span>
                    </a>
                </li>
            <?php endif; ?>
            <?php endif; ?>


            
            <!--constant-->
            <?php if(Gate::check('Manage Department') ||
                    Gate::check('Manage Designation') ||
                    Gate::check('Manage Document Type') ||
                    Gate::check('Manage Branch') ||
                    Gate::check('Manage Site') ||
                    Gate::check('Manage Award Type') ||
                    Gate::check('Manage Termination Types') ||
                    Gate::check('Manage Payslip Type') ||
                    Gate::check('Manage Allowance Option') ||
                    Gate::check('Manage Loan Options') ||
                    Gate::check('Manage Deduction Options') ||
                    Gate::check('Manage Expense Type') ||
                    Gate::check('Manage Income Type') ||
                    Gate::check('Manage Payment Type') ||
                    Gate::check('Manage Leave Type') ||
                    Gate::check('Manage Training Type') ||
                    Gate::check('Manage Job Category') ||
                    Gate::check('Manage Job Stage')): ?>
                <li
                    class="dash-item dash-hasmenu <?php echo e(Request::route()->getName() == 'branch.index'||Request::route()->getName() == 'site.index'  ||Request::route()->getName() == 'department.index' ||Request::route()->getName() == 'designation.index' ||Request::route()->getName() == 'leavetype.index' ||Request::route()->getName() == 'document.index' ||Request::route()->getName() == 'paysliptype.index' ||Request::route()->getName() == 'allowanceoption.index' ||Request::route()->getName() == 'loanoption.index' ||Request::route()->getName() == 'deductionoption.index' ||Request::route()->getName() == 'goaltype.index' ||Request::route()->getName() == 'trainingtype.index' ||Request::route()->getName() == 'awardtype.index' ||Request::route()->getName() == 'terminationtype.index' ||Request::route()->getName() == 'job-category.index' ||Request::route()->getName() == 'job-stage.index' ||Request::route()->getName() == 'performanceType.index' ||Request::route()->getName() == 'competencies.index' ||Request::route()->getName() == 'expensetype.index' ||Request::route()->getName() == 'incometype.index' ||Request::route()->getName() == 'paymenttype.index' ||Request::route()->getName() == 'contract_type.index'? ' active': ''); ?>">
                    <a href="<?php echo e(route('branch.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-lg flex items-center space-x-2"><span class="dash-micon text-white text-[30px] shadow-none" style="background: none;">
                               <i class="ti ti-table text-white text-[30px]"></i></span><span
                            class="dash-mtext"><?php echo e(__('HRM System Setup')); ?></span></a>
                </li>

            <?php endif; ?>


            














            


           






            








            


             




            <!-- Assets -->
            <?php if(Gate::check('Manage Assets')): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'account-assets' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('account-assets.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-package text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Assets')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Super Admin Companies -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'user' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('user.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-building-factory-2 text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Companies')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Super Admin Plans -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <?php if(Gate::check('Manage Plan')): ?>
                    <li class="dash-item <?php echo e(Request::segment(1) == 'plans' ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('plans.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-trophy text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Plan')); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Plan Request -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'plan_request' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('plan_request.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-arrow-down-right-circle text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Plan Request')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Referral Program -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'referral-program' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('referral-program.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-discount-2 text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Referral Program')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Coupon -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <?php if(Gate::check('manage coupon')): ?>
                    <li class="dash-item <?php echo e(Request::segment(1) == 'coupons' ? 'active' : ''); ?>">
                        <a href="<?php echo e(route('coupons.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-gift text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow"><?php echo e(__('Coupon')); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Order -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <li class="dash-item <?php echo e(request()->is('orders*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('order.index')); ?>" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-shopping-cart text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Order')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Landing Page -->
            <?php if(\Auth::user()->type == 'super admin'): ?>
                <?php echo $__env->make('landingpage::menu.landingpage', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>

            <!-- Settings -->
            <!-- Settings -->
            <?php if(Gate::check('Manage System Settings')): ?>
                <li class="dash-item <?php echo e(Request::segment(1) == 'settings' ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('settings.index')); ?>" class="dash-link text-white hover:text-white text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-settings text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow"><?php echo e(__('Settings')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php endif; ?> 

        </ul>
    </div>
</div>
</nav>

<?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/partial/Admin/menu.blade.php ENDPATH**/ ?>