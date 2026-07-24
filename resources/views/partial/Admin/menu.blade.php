@php
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
@endphp
    
@php
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
@endphp
@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
<nav class="dash-sidebar light-sidebar transprent-bg" style="background: linear-gradient(to bottom, #000, #575757);">
@else
<nav class="dash-sidebar light-sidebar" style="background: linear-gradient(to bottom, #000, #050505);">
@endif

<div class="navbar-wrapper">
    <div class="m-header main-logo">
        <a href="{{ $isPendingEmployee ? '#' : route('dashboard') }}" class="b-brand">
            <img src="{{ asset('storage/uploads/logo/logo-menu.png') }}"
                 alt="{{ config('app.name', 'HRMGo') }}" class="logo logo-lg" style="height: 90px; width: auto; object-fit: contain;">
        </a>
    </div>
    <div class="navbar-content">
        <ul class="dash-navbar">

            {{-- ======================================================= --}}
            {{-- PENDING EMPLOYEE: Show only the Profile link with notice --}}
            {{-- ======================================================= --}}
            @if ($isPendingEmployee)
                <li class="dash-item">
                    <a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}"
                       class="dash-link text-white text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-user text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('My Profile') }}</span>
                    </a>
                </li>
                <li style="padding: 10px 16px; margin-top: 10px;">
                    <div style="background: rgba(255,165,0,0.15); border: 1px solid rgba(255,165,0,0.5); border-radius: 8px; padding: 10px; color: #ffc107; font-size: 12px; line-height: 1.5;">
                        <i class="ti ti-clock" style="margin-right: 5px;"></i>
                        {{ __('Your account is pending approval. Please complete your profile.') }}
                    </div>
                </li>

            @else
            {{-- ======================================================= --}}
            {{-- APPROVED / ADMIN: Show full navigation below              --}}
            {{-- ======================================================= --}}

            <!-- Dashboard -->
            @if (\Auth::user()->type != 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'dashboard' ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-home text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Dashboard') }}</span>
                    </a>
                </li>
            @endif


            <!-- Employee -->
            @if (Gate::check('employee.view.own') || Gate::check('employee.view.all') || Gate::check('employee.profile.view.own'))
                @php
                    $employee = App\Models\Employee::where('user_id', \Auth::user()->id)->first(); 
                    $canViewAll = Gate::check('employee.view.all');
                    $canViewProfile = Gate::check('employee.profile.view.own');
                    $empOwn = \Auth::user()->type != 'company' && $employee && $canViewProfile;
                    $empCount = ($empOwn ? 1 : 0) + ($canViewAll ? 1 : 0);
                @endphp
                
                @if($empCount > 1)
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'employee' ? 'dash-trigger active' : '' }}">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-users text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Employee') }}</span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            @if($empOwn)
                            <li class="dash-item {{ Request::segment(1) == 'employee' && Request::segment(2) != 'index' ? 'active' : '' }}">
                                <a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}"
                                   class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md">
                                    {{ __('My Profile') }}
                                </a>
                            </li>
                            @endif
                            
                            @if($canViewAll)
                            <li class="dash-item {{ Request::segment(1) == 'employee' && Request::segment(2) == 'index' ? 'active' : '' }}">
                                <a href="{{ route('employee.index') }}"
                                   class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md">
                                    {{ \Auth::user()->type == 'company' ? __('Employee') : __('All Employees') }}
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                @elseif($empCount == 1)
                    <li class="dash-item {{ Request::segment(1) == 'employee' ? 'active' : '' }}">
                        @if($empOwn)
                            <a href="{{ route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        @else
                            <a href="{{ route('employee.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        @endif
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-users text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Employee') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'employee-permissions' ? 'active' : '' }}">
                    <a href="{{ route('employee-permissions.index') }}"
                       class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-lock text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Employee Permissions') }}</span>
                    </a>
                </li>
            @endif            <!-- Attendance -->
            @if (Gate::check('attendance.calendar.view.own') || Gate::check('attendance.calendar.view.all') || 
                 Gate::check('attendance.regularisation.view.own') || Gate::check('attendance.regularisation.view.all') || 
                 Gate::check('attendance.marked.view.own') || Gate::check('attendance.marked.view.all') || 
                 Gate::check('attendance.bulk.view.own') || Gate::check('attendance.bulk.view.all') || 
                 Gate::check('attendance.biometric.view.own') || Gate::check('attendance.biometric.view.all'))
                
                <li class="dash-item dash-hasmenu {{ Request::is('attendance*') || Request::is('biometric-attendance*') ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-clock text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Attendance') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        <!-- Attendance Calendar -->
                        @if (\Auth::user()->type != 'company' && Gate::check('attendance.calendar.view.own'))
                            <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' && request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}?own=1">
                                    {{ __('Attendance Calendar') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('attendance.calendar.view.all'))
                            <li class="dash-item {{ Request::segment(1) == 'attendance-calendar' && !request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance.calendar') }}">
                                    {{ \Auth::user()->type == 'company' ? __('Attendance Calendar') : __('All Employees Calendar') }}
                                </a>
                            </li>
                        @endif
                        
                        <!-- Attendance Regularisation -->
                        @if (\Auth::user()->type != 'company' && Gate::check('attendance.regularisation.view.own'))
                            <li class="dash-item {{ Request::segment(1) == 'attendance-regularisation' && request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance-regularisation.index') }}?own=1">
                                    {{ __('Attendance Regularisation') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('attendance.regularisation.view.all'))
                            <li class="dash-item {{ Request::segment(1) == 'attendance-regularisation' && !request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendance-regularisation.index') }}">
                                    {{ \Auth::user()->type == 'company' ? __('Attendance Regularisation') : __('All Employees Regularisation') }}
                                </a>
                            </li>
                        @endif

                        <!-- Marked Attendance -->
                        @if (\Auth::user()->type != 'company' && Gate::check('attendance.marked.view.own'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}?own=1">
                                    {{ __('Marked Attendance') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('attendance.marked.view.all'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.index') }}">
                                    {{ \Auth::user()->type == 'company' ? __('Marked Attendance') : __('All Employees Attendance') }}
                                </a>
                            </li>
                        @endif

                        <!-- Bulk Attendance -->
                        @if (Gate::check('attendance.bulk.view.all'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('attendanceemployee.bulkattendance') }}">
                                    {{ __('Bulk Attendance') }}
                                </a>
                            </li>
                        @endif

                        <!-- Biometric Attendance -->
                        @if (Gate::check('attendance.biometric.view.all'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('biometric-attendance.index') }}">
                                    {{ __('Biometric Attendance') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif




            <!-- Leave -->
            @if (Gate::check('leave.manage.view.own') || Gate::check('leave.manage.view.all') || 
                 Gate::check('leave.details.view.own') || Gate::check('leave.details.view.all'))
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'calender' && Request::segment(2) == 'leave' ? 'dash-trigger active' : '' }}">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-calendar text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('Leave') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    @if (\Auth::user()->type != 'company' && Gate::check('leave.manage.view.own'))
                        <li class="dash-item {{ Request::segment(1) == 'calender' && request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}?own=1">
                                {{ __('Manage Leave') }}
                            </a>
                        </li>
                    @endif
                    @if (Gate::check('leave.manage.view.all'))
                        <li class="dash-item {{ Request::segment(1) == 'calender' && !request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.index') }}">
                                {{ \Auth::user()->type == 'company' ? __('Manage Leave') : __('All Employees Leaves') }}
                            </a>
                        </li>
                    @endif
                    
                    @if (Gate::check('leave.details.view.all'))
                        <li class="dash-item {{ Request::segment(1) == 'leave-details' && !request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.details') }}">
                                {{ \Auth::user()->type == 'company' ? __('Leave Details') : __('All Employees Leave Details') }}
                            </a>
                        </li>
                    @endif
                    
                    @if (\Auth::user()->type != 'employee')
                        <li class="dash-item {{ Request::segment(1) == 'compoff' ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.compoff.index') }}">
                                {{ __('Comp-Off Leaves') }}
                            </a>
                        </li>
                        <li class="dash-item {{ Request::segment(1) == 'carry-forward-leaves' ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('leave.carryforward') }}">
                                {{ __('Carryforward Leaves') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            @endif
             
            <!-- Employee Payroll section removed to rely on Spatie permissions block -->
                         <!-- Admin Payroll -->
            @if (Gate::check('payroll.salary.view.own') || Gate::check('payroll.salary.view.all') || 
                 Gate::check('payroll.payslip.view.own') || Gate::check('payroll.payslip.view.all'))
                <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'setsalary' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-receipt text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Payroll') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        @if (\Auth::user()->type != 'company' && Gate::check('payroll.salary.view.own'))
                            <li class="dash-item {{ Request::segment(1) == 'setsalary' && request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('setsalary.index') }}?own=1">
                                    {{ __('Set Salary') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('payroll.salary.view.all'))
                            <li class="dash-item {{ Request::segment(1) == 'setsalary' && !request('own') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('setsalary.index') }}">
                                    {{ \Auth::user()->type == 'company' ? __('Set Salary') : __('All Employees Salary') }}
                                </a>
                            </li>
                        @endif
                        
                        @if (\Auth::user()->type != 'company' && Gate::check('payroll.payslip.view.own'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('payslip.index') }}?own=1">
                                    {{ __('Payslip') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('payroll.payslip.view.all'))
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('payslip.index') }}">
                                    {{ \Auth::user()->type == 'company' ? __('Payslip') : __('All Employees Payslip') }}
                                </a>
                            </li>
                        @endif
                        
                        @if (Gate::check('payroll.salary_arrears.view.all') || \Auth::user()->type == 'company')
                            <li class="dash-item {{ Request::segment(1) == 'salary-arrears' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('salary-arrears.index') }}">
                                    {{ __('Salary Arrears') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('payroll.other_deduction.view.all') || \Auth::user()->type == 'company')
                            <li class="dash-item {{ Request::segment(1) == 'other-deduction' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('other-deduction.index') }}">
                                    {{ __('Other Deduction') }}
                                </a>
                            </li>
                        @endif
                        @if (Gate::check('payroll.petrol_allowance.view.all') || \Auth::user()->type == 'company')
                            <li class="dash-item {{ Request::segment(1) == 'petrol-allowance' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('petrol-allowance.index') }}">
                                    {{ __('Petrol Allowance') }}
                                </a>
                            </li>
                        @endif
                        
                        @if(Gate::check('payroll.payable_days.view.all'))
                            <li class="dash-item {{ Request::segment(1) == 'payable-days' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('payable-days.index') }}">
                                    {{ __('Payable Days') }}
                                </a>
                            </li>
                        @endif
                        
                        @if ($isFinanceAccountsUser() || \Auth::user()->type == 'company' || Gate::check('payroll.salary_processing.view.all'))
                            <li class="dash-item {{ Request::segment(1) == 'salary-processing' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('salary-processing.index') }}">
                                    {{ __('Salary Processing') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            <!-- Company Policy for Employees -->
            <!-- @if (\Auth::user()->type == 'employee')
                <li class="dash-item {{ Request::segment(1) == 'company-policy' ? 'active' : '' }}">
                    <a href="{{ route('company-policy.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-file-text text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Company Policy') }}</span>
                    </a>
                </li>
            @endif -->

            <!-- Admin Company Policy -->
            @if(Gate::check('company_policy.manage.view.own') || Gate::check('company_policy.manage.view.all'))
                @php
                    $cpOwn = \Auth::user()->type != 'company' && Gate::check('company_policy.manage.view.own');
                    $cpAll = Gate::check('company_policy.manage.view.all');
                    $cpCount = ($cpOwn ? 1 : 0) + ($cpAll ? 1 : 0);
                @endphp
                
                @if($cpCount > 1)
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'company-policy' ? 'dash-trigger active' : '' }}">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-file-text text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Company Policy') }}</span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            @if($cpOwn)
                                <li class="dash-item {{ Request::segment(1) == 'company-policy' && request('own') ? 'active' : '' }}">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('company-policy.index') }}?own=1">
                                        {{ __('My Policies') }}
                                    </a>
                                </li>
                            @endif
                            @if($cpAll)
                                <li class="dash-item {{ Request::segment(1) == 'company-policy' && !request('own') ? 'active' : '' }}">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('company-policy.index') }}">
                                        {{ \Auth::user()->type == 'company' ? __('Company Policy') : __('All Company Policies') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @elseif($cpCount == 1)
                    <li class="dash-item {{ Request::segment(1) == 'company-policy' ? 'active' : '' }}">
                        <a href="{{ $cpOwn ? route('company-policy.index').'?own=1' : route('company-policy.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-file-text text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Company Policy') }}</span>
                        </a>
                    </li>
                @endif
            @endif



            <!-- Ticket -->
            <!-- Ticket -->
            @if(Gate::check('ticket.manage.view.own') || Gate::check('ticket.manage.view.all'))
                @php
                    $tktOwn = \Auth::user()->type != 'company' && Gate::check('ticket.manage.view.own');
                    $tktAll = Gate::check('ticket.manage.view.all');
                    $tktCount = ($tktOwn ? 1 : 0) + ($tktAll ? 1 : 0);
                @endphp
                
                @if($tktCount > 1)
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'ticket' ? 'dash-trigger active' : '' }}">
                        <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-ticket text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Ticket') }}</span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            @if($tktOwn)
                                <li class="dash-item {{ Request::segment(1) == 'ticket' && request('own') ? 'active' : '' }}">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('ticket.index') }}?own=1">
                                        {{ __('My Tickets') }}
                                    </a>
                                </li>
                            @endif
                            @if($tktAll)
                                <li class="dash-item {{ Request::segment(1) == 'ticket' && !request('own') ? 'active' : '' }}">
                                    <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('ticket.index') }}">
                                        {{ \Auth::user()->type == 'company' ? __('Ticket') : __('All Employees Tickets') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @elseif($tktCount == 1)
                    <li class="dash-item {{ Request::segment(1) == 'ticket' ? 'active' : '' }}">
                        <a href="{{ $tktOwn ? route('ticket.index').'?own=1' : route('ticket.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-ticket text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Ticket') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            @if(\Auth::user()->type == 'company')
                <li class="dash-item {{ Request::segment(1) == 'holiday' ? 'active' : '' }}">
                    <a href="{{ route('holiday.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-calendar-event text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Holiday') }}</span>
                    </a>
                </li>
            @endif

            <!-- Project Management -->
            <!-- @if($userType === 'company' || $userType === 'hr')
                <li class="dash-item {{ Request::segment(1) == 'projects' ? 'active' : '' }}">
                    <a href="{{ route('projects.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-clipboard-list text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Project') }}</span>
                    </a>
                </li>

                <li class="dash-item {{ Request::segment(1) == 'units' ? 'active' : '' }}">
                    <a href="{{ route('units.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-building text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Units') }}</span>
                    </a>
                </li>
            @endif -->

            <!-- Enquiry -->
            <!-- @if (Gate::check('Manage TimeSheet'))
            <li class="dash-item {{ Request::segment(1) == 'timesheet' ? 'active' : '' }}">
                <a href="{{ route('timesheet.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-question-mark text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('Enquiry') }}</span>
                </a>
            </li>
            @endif -->

            <!-- Booking -->
            <!-- <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'booking' ? 'dash-trigger active' : '' }}">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-calendar-event text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('Booking') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    <li class="dash-item {{ Request::segment(2) == 'all' ? 'active' : '' }}">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('booking.all') }}">
                            {{ __('All Booking') }}
                        </a>
                    </li>
                    <li class="dash-item {{ Request::segment(2) == 'add' ? 'active' : '' }}">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('booking.add') }}">
                            {{ __('Add Booking') }}
                        </a>
                    </li>
                    <li class="dash-item {{ Request::segment(2) == 'edit' ? 'active' : '' }}">
                        <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('edit.booking') }}">
                            {{ __('Edit Booking') }}
                        </a>
                    </li>
                </ul>
            </li> -->

            <!-- Recruitment -->
            @if (Gate::check('Manage Job') || Gate::check('Manage Job Application') || Gate::check('Manage Job OnBoard') || Gate::check('Manage Custom Question') || Gate::check('Manage Interview Schedule') || Gate::check('Manage Career'))
                <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'job' || Request::segment(1) == 'job-application' ? 'dash-trigger active' : '' }}">
                    <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-license text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Recruitment') }}</span>
                        <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="dash-submenu">
                        @can('Manage Job')
                            <li class="dash-item {{ Request::route()->getName() == 'job.index' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('job.index') }}">
                                    {{ __('Jobs') }}
                                </a>
                            </li>
                        @endcan
                        
                        @can('Manage Job')
                            <li class="dash-item {{ Request::route()->getName() == 'job.create' ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('job.create') }}">
                                    {{ __('Job Create') }}
                                </a>
                            </li>
                        @endcan
                        
                        @can('Manage Job Application')
                            <li class="dash-item {{ request()->is('job-application*') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('job-application.index') }}">
                                    {{ __('Job Application') }}
                                </a>
                            </li>
                        @endcan
                        
                        @can('Manage Job Application')
                            <li class="dash-item {{ request()->is('candidates-job-applications') ? 'active' : '' }}">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('job.application.candidate') }}">
                                    {{ __('Job Candidate') }}
                                </a>
                            </li>
                        @endcan

                        @can('Manage Job OnBoard')
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('job.on.board') }}">
                                    {{ __('Job On-Boarding') }}
                                </a>
                            </li>
                        @endcan

                        @can('Manage Custom Question')
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('custom-question.index') }}">
                                    {{ __('Custom Question') }}
                                </a>
                            </li>
                        @endcan

                        @can('Manage Interview Schedule')
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('interview-schedule.index') }}">
                                    {{ __('Interview Schedule') }}
                                </a>
                            </li>
                        @endcan

                        @can('Manage Career')
                            <li class="dash-item">
                                <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('career', [\Auth::user()->creatorId(), $lang]) }}" target="_blank">
                                    {{ __('Career') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif



            <!-- To-Do List -->
            @if (Gate::check('Manage ToDoList'))
            <li class="dash-item {{ Request::segment(1) == 'todo' ? 'active' : '' }}">
                <a href="{{ route('todo.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="fas fa-tasks text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('To-Do List') }}</span>
                </a>
            </li>
            @endif

            <!-- Notice -->
            @if (Gate::check('Manage Notice'))
            <li class="dash-item {{ Request::segment(1) == 'notices' ? 'active' : '' }}">
                <a href="{{ route('notices.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-bell text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('Notice') }}</span>
                </a>
            </li>
            @endif



            <!-- Loan -->
            @if (\Auth::user()->type == 'company')
                <li class="dash-item {{ Request::segment(1) == 'loan' ? 'active' : '' }}">
                    <a href="{{ route('loan.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-cash text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Loan') }}</span>
                    </a>
                </li>
            @endif

            <!-- Exit Formalities -->
            @if(Gate::check('exit.resignation.view.own') || Gate::check('exit.resignation.view.all') || Gate::check('exit.termination.view.own') || Gate::check('exit.termination.view.all'))
            @php
                $resOwn = \Auth::user()->type != 'company' && Gate::check('exit.resignation.view.own');
                $resAll = Gate::check('exit.resignation.view.all');
                $termOwn = \Auth::user()->type != 'company' && Gate::check('exit.termination.view.own');
                $termAll = Gate::check('exit.termination.view.all');
                $exitCount = ($resOwn ? 1 : 0) + ($resAll ? 1 : 0) + ($termOwn ? 1 : 0) + ($termAll ? 1 : 0);
            @endphp
            
            @if($exitCount > 1)
            <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'resignation' || Request::segment(1) == 'termination' ? 'dash-trigger active' : '' }}">
                <a href="#!" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    <span class="dash-micon shadow-none" style="background: none;">
                        <i class="ti ti-user-x text-white text-[22px]"></i>
                    </span>
                    <span class="dash-mtext flex-grow">{{ __('Exit Formalities') }}</span>
                    <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                </a>
                <ul class="dash-submenu">
                    @if($resOwn)
                        <li class="dash-item {{ Request::segment(1) == 'resignation' && request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('resignation.index') }}?own=1">
                                {{ __('My Resignation') }}
                            </a>
                        </li>
                    @endif
                    @if($resAll)
                        <li class="dash-item {{ Request::segment(1) == 'resignation' && !request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('resignation.index') }}">
                                {{ \Auth::user()->type == 'company' ? __('Resignation') : __('All Employees Resignation') }}
                            </a>
                        </li>
                    @endif
                    
                    @if($termOwn)
                        <li class="dash-item {{ Request::segment(1) == 'termination' && request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('termination.index') }}?own=1">
                                {{ __('My Termination') }}
                            </a>
                        </li>
                    @endif
                    @if($termAll)
                        <li class="dash-item {{ Request::segment(1) == 'termination' && !request('own') ? 'active' : '' }}">
                            <a class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-md" href="{{ route('termination.index') }}">
                                {{ \Auth::user()->type == 'company' ? __('Termination') : __('All Employees Termination') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
            @elseif($exitCount == 1)
                <li class="dash-item {{ Request::segment(1) == 'resignation' || Request::segment(1) == 'termination' ? 'active' : '' }}">
                    @if($resOwn)
                        <a href="{{ route('resignation.index').'?own=1' }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    @elseif($resAll)
                        <a href="{{ route('resignation.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    @elseif($termOwn)
                        <a href="{{ route('termination.index').'?own=1' }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    @else
                        <a href="{{ route('termination.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                    @endif
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-user-x text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Exit Formalities') }}</span>
                    </a>
                </li>
            @endif
            @endif


            
            <!--constant-->
            @if (Gate::check('Manage Department') ||
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
                    Gate::check('Manage Job Stage'))
                <li
                    class="dash-item dash-hasmenu {{ Request::route()->getName() == 'branch.index'||Request::route()->getName() == 'site.index'  ||Request::route()->getName() == 'department.index' ||Request::route()->getName() == 'designation.index' ||Request::route()->getName() == 'leavetype.index' ||Request::route()->getName() == 'document.index' ||Request::route()->getName() == 'paysliptype.index' ||Request::route()->getName() == 'allowanceoption.index' ||Request::route()->getName() == 'loanoption.index' ||Request::route()->getName() == 'deductionoption.index' ||Request::route()->getName() == 'goaltype.index' ||Request::route()->getName() == 'trainingtype.index' ||Request::route()->getName() == 'awardtype.index' ||Request::route()->getName() == 'terminationtype.index' ||Request::route()->getName() == 'job-category.index' ||Request::route()->getName() == 'job-stage.index' ||Request::route()->getName() == 'performanceType.index' ||Request::route()->getName() == 'competencies.index' ||Request::route()->getName() == 'expensetype.index' ||Request::route()->getName() == 'incometype.index' ||Request::route()->getName() == 'paymenttype.index' ||Request::route()->getName() == 'contract_type.index'? ' active': '' }}">
                    <a href="{{ route('branch.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-lg flex items-center space-x-2"><span class="dash-micon text-white text-[30px] shadow-none" style="background: none;">
                               <i class="ti ti-table text-white text-[30px]"></i></span><span
                            class="dash-mtext">{{ __('HRM System Setup') }}</span></a>
                </li>

            @endif


            














            


           






            








            


             




            <!-- Assets -->
            @if (Gate::check('Manage Assets'))
                <li class="dash-item {{ Request::segment(1) == 'account-assets' ? 'active' : '' }}">
                    <a href="{{ route('account-assets.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-package text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Assets') }}</span>
                    </a>
                </li>
            @endif

            <!-- Super Admin Companies -->
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'user' ? 'active' : '' }}">
                    <a href="{{ route('user.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-building-factory-2 text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Companies') }}</span>
                    </a>
                </li>
            @endif

            <!-- Super Admin Plans -->
            @if (\Auth::user()->type == 'super admin')
                @if (Gate::check('Manage Plan'))
                    <li class="dash-item {{ Request::segment(1) == 'plans' ? 'active' : '' }}">
                        <a href="{{ route('plans.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-trophy text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Plan') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            <!-- Plan Request -->
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'plan_request' ? 'active' : '' }}">
                    <a href="{{ route('plan_request.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-arrow-down-right-circle text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Plan Request') }}</span>
                    </a>
                </li>
            @endif

            <!-- Referral Program -->
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item {{ Request::segment(1) == 'referral-program' ? 'active' : '' }}">
                    <a href="{{ route('referral-program.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-discount-2 text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Referral Program') }}</span>
                    </a>
                </li>
            @endif

            <!-- Coupon -->
            @if (\Auth::user()->type == 'super admin')
                @if (Gate::check('manage coupon'))
                    <li class="dash-item {{ Request::segment(1) == 'coupons' ? 'active' : '' }}">
                        <a href="{{ route('coupons.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                            <span class="dash-micon shadow-none" style="background: none;">
                                <i class="ti ti-gift text-white text-[22px]"></i>
                            </span>
                            <span class="dash-mtext flex-grow">{{ __('Coupon') }}</span>
                        </a>
                    </li>
                @endif
            @endif

            <!-- Order -->
            @if (\Auth::user()->type == 'super admin')
                <li class="dash-item {{ request()->is('orders*') ? 'active' : '' }}">
                    <a href="{{ route('order.index') }}" class="dash-link text-white hover:text-white hover:bg-[#001a3b] text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-shopping-cart text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Order') }}</span>
                    </a>
                </li>
            @endif

            <!-- Landing Page -->
            @if (\Auth::user()->type == 'super admin')
                @include('landingpage::menu.landingpage')
            @endif

            <!-- Settings -->
            <!-- Settings -->
            @if (Gate::check('Manage System Settings'))
                <li class="dash-item {{ Request::segment(1) == 'settings' ? 'active' : '' }}">
                    <a href="{{ route('settings.index') }}" class="dash-link text-white hover:text-white text-base flex items-center w-full">
                        <span class="dash-micon shadow-none" style="background: none;">
                            <i class="ti ti-settings text-white text-[22px]"></i>
                        </span>
                        <span class="dash-mtext flex-grow">{{ __('Settings') }}</span>
                    </a>
                </li>
            @endif

            @endif {{-- END: $isPendingEmployee check --}}

        </ul>
    </div>
</div>
</nav>

