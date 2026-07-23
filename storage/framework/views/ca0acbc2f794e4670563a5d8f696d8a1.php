<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Manage Employee')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Employee')); ?></li>
<?php $__env->stopSection(); ?>



<?php $__env->startSection('action-button'); ?>

    <?php if(Gate::check('employee.export.all') || \Auth::user()->type != 'employee'): ?>
            <a href="#" data-url="<?php echo e(route('employee.export_modal')); ?>" 
               data-ajax-popup="true" data-size="md" data-title="<?php echo e(__('Export Employee Data')); ?>"
               class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="<?php echo e(__('Export')); ?>">
                <i class="ti ti-file-export"></i>
            </a>
    <?php endif; ?>
    <?php if(Gate::check('employee.create.all') || \Auth::user()->type != 'employee'): ?>
            <a href="<?php echo e(route('employee.create')); ?>" 
               data-title="<?php echo e(__('Create New Employee')); ?>" 
               data-bs-toggle="tooltip" title="<?php echo e(__('Create')); ?>"
               class="btn btn-sm btn-primary ">
                <i class="ti ti-plus"></i>
            </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>



<?php $__env->startSection('content'); ?>
    <?php
        // Check if logged-in user is from Finance & Accounts department
        $isFinanceAccounts = false;
        if (Auth::user()->type == 'employee') {
            try {
                // Method 1: Check by email pattern
                $userEmail = strtolower(Auth::user()->email ?? '');
                if (strpos($userEmail, 'accounts@') !== false || 
                    strpos($userEmail, 'finance@') !== false ||
                    strpos($userEmail, '@accounts') !== false ||
                    strpos($userEmail, '@finance') !== false) {
                    $isFinanceAccounts = true;
                }
                
                // Method 2: Check by department name
                if (!$isFinanceAccounts) {
                    $currentEmployee = \App\Models\Employee::where('user_id', Auth::user()->id)->first();
                    
                    if ($currentEmployee && !empty($currentEmployee->department_id)) {
                        $department = \App\Models\Department::where('id', $currentEmployee->department_id)
                            ->where('created_by', Auth::user()->creatorId())
                            ->first();
                        
                        if ($department) {
                            $deptName = strtolower(trim($department->name));
                            $isFinanceAccounts = (
                                $deptName == 'finance & accounts' || 
                                $deptName == 'finance and accounts' ||
                                $deptName == 'finance & account' ||
                                $deptName == 'finance & accounts team' ||
                                $deptName == 'finance and accounts team' ||
                                (strpos($deptName, 'finance') !== false && (strpos($deptName, 'account') !== false || strpos($deptName, 'accounts') !== false))
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error checking Finance & Accounts department: ' . $e->getMessage());
            }
        }
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-border-style-tab">
                        <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                                    <?php echo e(__('Active Employees')); ?>

                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="left-tab" data-bs-toggle="tab" data-bs-target="#left" type="button" role="tab" aria-controls="left" aria-selected="false">
                                    <?php echo e(__('Inactive Employees')); ?>

                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3" id="employeeTabsContent">
                            <!-- Active Employees Tab -->
                            <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                                
                                    <table class="table" id="pc-dt-simple">
                                        <thead>
                                            <tr>
                                                <th class="text-start"><?php echo e(__('Employee ID')); ?></th>
                                                <th class="text-start"><?php echo e(__('Name')); ?></th>
                                                <th class="text-start"><?php echo e(__('Email')); ?></th>
                                                <th class="text-start"><?php echo e(__('Branch')); ?></th>
                                                <th class="text-start"><?php echo e(__('Department')); ?></th>
                                                <th class="text-start"><?php echo e(__('Designation')); ?></th>
                                                <th class="text-start"><?php echo e(__('Date Of Joining')); ?></th>
                                                <?php if(Auth::user()->type != 'hr' && !$isFinanceAccounts && (Gate::check('employee.edit.all') || Gate::check('employee.delete.all') || \Auth::user()->type != 'employee')): ?>
                                                    <th class="text-center" width="100px"><?php echo e(__('Action')); ?></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $activeEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td class="text-start">
                                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employee.show.all')): ?>
                                                            <a class="btn btn-outline-primary btn-sm"
                                                                href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>">
                                                                <?php echo e($employee->formatted_id); ?>

                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary">
                                                                <?php echo e($employee->formatted_id); ?>

                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-start"><?php echo e($employee->full_name); ?></td>
                                                    <td class="text-start"><?php echo e($employee->email ?? '-'); ?></td>  
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->branch?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->department?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->designation?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start"><?php echo e(\Auth::user()->dateFormat($employee->company_doj)); ?></td>
                                                    
                                                    <?php if(Auth::user()->type != 'hr' && !$isFinanceAccounts && (Gate::check('employee.edit.all') || Gate::check('employee.delete.all') || \Auth::user()->type != 'employee')): ?>
                                                        <td class="Action text-center" style="white-space: nowrap;">
                                                            <?php if(($employee->user?->is_active ?? 0) == 1 && ($employee->user?->is_disable ?? 0) == 1): ?>
                                                                <?php if(Gate::check('employee.send_mail.all') || (Gate::check('employee.edit.all') && Auth::user()->type != 'employee') || \Auth::user()->type != 'employee'): ?>
                                                                    <?php echo Form::open([
                                                                        'method' => 'POST',
                                                                        'route' => ['employee.resend_email', $employee->id],
                                                                        'style' => 'display:inline'
                                                                    ]); ?>

                                                                    <a href="#" onclick="event.preventDefault(); this.closest('form').submit();"
                                                                    class="btn btn-sm btn-icon-only bg-success ms-2" data-bs-toggle="tooltip" title="<?php echo e(__('Resend Welcome Email')); ?>">
                                                                        <i class="ti ti-mail text-white"></i>
                                                                    </a>
                                                                    <?php echo Form::close(); ?>

                                                                <?php endif; ?>

                                                                <?php if(Gate::check('employee.edit.all') || \Auth::user()->type != 'employee'): ?>
                                                                    <a href="<?php echo e(route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>" 
                                                                    class="btn btn-sm btn-icon-only bg-info ms-2">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employee.delete.all')): ?>
                                                                    <?php echo Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['employee.destroy', $employee->id],
                                                                        'style' => 'display:inline'
                                                                    ]); ?>

                                                                    <a href="#"
                                                                    class="btn btn-sm btn-icon-only bg-danger ms-2 custom-delete-btn">
                                                                        <i class="ti ti-trash text-white"></i>
                                                                    </a>
                                                                    <?php echo Form::close(); ?>

                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <i class="ti ti-lock"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                            </div>
                            
                            <!-- Left Employees Tab -->
                            <div class="tab-pane fade" id="left" role="tabpanel" aria-labelledby="left-tab">
                                    <table class="table" id="pc-dt-simple2">
                                        <thead>
                                            <tr>
                                                <th class="text-start"><?php echo e(__('Employee ID')); ?></th>
                                                <th class="text-start"><?php echo e(__('Name')); ?></th>
                                                <th class="text-start"><?php echo e(__('Email')); ?></th>
                                                <th class="text-start"><?php echo e(__('Branch')); ?></th>
                                                <th class="text-start"><?php echo e(__('Department')); ?></th>
                                                <th class="text-start"><?php echo e(__('Designation')); ?></th>
                                                <th class="text-start"><?php echo e(__('Date Of Joining')); ?></th>
                                                <th class="text-start"><?php echo e(__('Termination Date')); ?></th>
                                                <?php if(Auth::user()->type != 'hr' && !$isFinanceAccounts && Gate::check('employee.show.all')): ?>
                                                    <th class="text-center" width="80px"><?php echo e(__('Action')); ?></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $leftEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $termination = \App\Models\Termination::where('employee_id', $employee->id)->first();
                                                ?>
                                                <tr>
                                                    <td class="text-start">
                                                        <span class="">
                                                            <a class="btn btn-outline-primary btn-sm"
                                                                href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>">
                                                                <?php echo e($employee->formatted_id); ?>

                                                            </a>
                                                        </span>
                                                    </td>
                                                    <td class="text-start"><?php echo e($employee->full_name); ?></td>
                                                    <td class="text-start"><?php echo e($employee->email ?? '-'); ?></td>  
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->branch?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->department?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start">
                                                        <span class="">
                                                            <?php echo e($employee->designation?->name ?? '-'); ?>

                                                        </span>
                                                    </td>
                                                    <td class="text-start"><?php echo e(\Auth::user()->dateFormat($employee->company_doj)); ?></td>
                                                    <td class="text-start">
                                                        <?php if($termination): ?>
                                                            <?php echo e(\Auth::user()->dateFormat($termination->termination_date)); ?>

                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php if(Auth::user()->type != 'hr' && !$isFinanceAccounts && Gate::check('employee.show.all')): ?>
                                                        <td class="Action text-center">
                                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('employee.show.all')): ?>
                                                                <a href="<?php echo e(route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>"
                                                                class="btn btn-sm btn-icon-only bg-info" data-bs-toggle="tooltip" title="<?php echo e(__('View')); ?>">
                                                                    <i class="ti ti-eye text-white"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if($termination && \Auth::user()->can('Manage Employee')): ?>
                                                                <a href="#" data-url="<?php echo e(route('employee.export_inactive_modal', $employee->id)); ?>" 
                                                                data-ajax-popup="true" data-size="md" data-title="<?php echo e(__('Export Attendance')); ?>"
                                                                class="btn btn-sm btn-icon-only bg-primary ms-2" data-bs-toggle="tooltip" title="<?php echo e(__('Export Attendance')); ?>">
                                                                    <i class="ti ti-file-export text-white"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <style>
        /* Mobile responsive tabs */
        @media (max-width: 768px) {
            .nav-tabs {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 0;
            }
            
            .nav-tabs .nav-item {
                flex: 1;
                min-width: 150px;
            }
            
            .nav-tabs .nav-link {
                display: block;
                width: 100%;
                padding: 0.5rem 1rem;
                text-align: center;
                white-space: nowrap;
                border: 1px solid transparent;
                border-bottom: none;
                border-radius: 0.375rem 0.375rem 0 0;
                font-size: 0.875rem;
                color: #dc3545;
                background-color: #f8f9fa;
            }
            
            .nav-tabs .nav-link:hover {
                border-color: #e9ecef #e9ecef #dee2e6;
                color: #dc3545;
                background-color: #e9ecef;
            }
            
            .nav-tabs .nav-link.active {
                color: #dc3545;
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-bottom-color: transparent;
            }
            
            /* Remove thick border/scroll artifact */
            .nav-tabs::-webkit-scrollbar {
                display: none;
            }
            .nav-tabs {
                -ms-overflow-style: none;
                scrollbar-width: none;
                border-right: none !important;
            }
            
            /* Ensure tab content is responsive */
            .tab-content {
                overflow-x: auto;
            }
            
            .table-responsive {
                margin-bottom: 0;
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
            content: "↑" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
            content: "↓" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after {
            content: "↕" !important;
            opacity: 0.3;
        }
        
        /* Ensure proper column width alignment */
        #pc-dt-simple th,
        #pc-dt-simple2 th {
            min-width: 120px;
        }
        
        #pc-dt-simple th:nth-child(1),
        #pc-dt-simple2 th:nth-child(1) {
            min-width: 160px; /* Employee ID */
        }
        
        #pc-dt-simple th:nth-child(2),
        #pc-dt-simple2 th:nth-child(2) {
            min-width: 180px; /* Name */
        }
        
        #pc-dt-simple th:nth-child(3),
        #pc-dt-simple2 th:nth-child(3) {
            min-width: 250px; /* Email */
        }
        
        #pc-dt-simple th:nth-child(4),
        #pc-dt-simple2 th:nth-child(4) {
            min-width: 140px; /* Branch */
        }
        
        #pc-dt-simple th:nth-child(5),
        #pc-dt-simple2 th:nth-child(5) {
            min-width: 160px; /* Department */
        }
        
        #pc-dt-simple th:nth-child(6),
        #pc-dt-simple2 th:nth-child(6) {
            min-width: 160px; /* Designation */
        }
        
        #pc-dt-simple th:nth-child(7),
        #pc-dt-simple2 th:nth-child(7) {
            min-width: 180px; /* Date of Joining */
        }
        
        #pc-dt-simple th:nth-child(8) {
            min-width: 120px; /* Action - Active table */
        }
        
        #pc-dt-simple2 th:nth-child(8) {
            min-width: 180px; /* Termination Date */
        }
        
        #pc-dt-simple2 th:nth-child(9) {
            min-width: 100px; /* Action - Inactive table */
        }
    </style>
    <script>
        $(document).ready(function() {
            // Initialize the second table with simple-datatables
            // The first table (#pc-dt-simple) is already initialized in admin.blade.php
            if (document.querySelector("#pc-dt-simple2")) {
                const dataTable2 = new simpleDatatables.DataTable("#pc-dt-simple2");
            }
            
            // Delete functionality with confirmation
            $(document).on('click', '.custom-delete-btn', function(e) {
                e.preventDefault();
                const button = $(this);
                const form = button.closest('form');
                
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                });

                swalWithBootstrapButtons.fire({
                    title: 'Are you sure?',
                    text: "This action can not be undone. Do you want to continue?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form normally to allow Laravel to handle the redirect
                        form.submit();
                    }
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/employee/index.blade.php ENDPATH**/ ?>