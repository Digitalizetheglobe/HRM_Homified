<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Carryforward Leaves')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Carryforward Leaves')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <?php if(isset($canViewAll) && $canViewAll): ?>
    <!-- Employee Selection Section -->
    <div class="col-xl-12 mb-3">
        <div class="card">
            <div class="card-header">
                <h5><?php echo e(__('Select Employee to View Carryforward Leaves')); ?></h5>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('leave.carryforward')); ?>" method="GET" id="employeeSelectForm">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="employee_id" class="form-label"><?php echo e(__('Employee')); ?></label>
                                <select name="employee_id" id="employee_id" class="form-control select2" onchange="document.getElementById('employeeSelectForm').submit();">
                                    <option value=""><?php echo e(__('Select an Employee')); ?></option>
                                    <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($employee->id); ?>" <?php echo e($selectedEmployeeId == $employee->id ? 'selected' : ''); ?>>
                                            <?php echo e(\Auth::user()->employeeIdFormat($employee->employee_id)); ?> - <?php echo e($employee->full_name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Details Section -->
    <?php if($selectedEmployeeId): ?>
        <?php if(empty($employeeData)): ?>
            <div class="col-xl-12">
                <div class="alert alert-info">
                    <?php echo e(__('No leave data found for the selected employee in the current year.')); ?>

                </div>
            </div>
        <?php else: ?>
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?php echo e(__('Detailed Leave Calculation for Year: ')); ?> <?php echo e($currentYear); ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Navigation Tabs for Leave Types -->
                        <ul class="nav nav-tabs mb-3" id="leaveTypeTabs" role="tablist">
                            <?php $firstTab = true; ?>
                            <?php $__currentLoopData = $employeeData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leaveType => $monthsData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $tabId = str_replace(' ', '', $leaveType); ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo e($firstTab ? 'active' : ''); ?>" id="<?php echo e($tabId); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo e($tabId); ?>" type="button" role="tab" aria-controls="<?php echo e($tabId); ?>" aria-selected="<?php echo e($firstTab ? 'true' : 'false'); ?>">
                                        <?php echo e($leaveType); ?>

                                    </button>
                                </li>
                                <?php $firstTab = false; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>

                        <!-- Tab Contents -->
                        <div class="tab-content" id="leaveTypeTabsContent">
                            <?php $firstContent = true; ?>
                            <?php $__currentLoopData = $employeeData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leaveType => $monthsData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $tabId = str_replace(' ', '', $leaveType); ?>
                                <div class="tab-pane fade <?php echo e($firstContent ? 'show active' : ''); ?>" id="<?php echo e($tabId); ?>" role="tabpanel" aria-labelledby="<?php echo e($tabId); ?>-tab">
                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><?php echo e(__('Month')); ?></th>
                                                    <th class="text-center" title="Balance carried forward from the previous month"><?php echo e(__('Opening Balance')); ?> <i class="ti ti-info-circle"></i></th>
                                                    <th class="text-center text-success" title="Leaves earned or allocated this month">+ <?php echo e(__('Leaves Earned')); ?></th>
                                                    <th class="text-center text-danger" title="Leaves taken or deducted this month">- <?php echo e(__('Leaves Used')); ?></th>
                                                    <th class="text-center text-primary" title="Opening Balance + Leaves Earned - Leaves Used"><?php echo e(__('Available Balance')); ?> <i class="ti ti-info-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $monthsData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td class="font-weight-bold"><?php echo e($data['month']); ?></td>
                                                        <td class="text-center"><?php echo e($data['opening_balance']); ?></td>
                                                        <td class="text-center text-success"><?php echo e($data['allocated']); ?></td>
                                                        <td class="text-center text-danger"><?php echo e($data['used']); ?></td>
                                                        <td class="text-center font-weight-bold text-primary"><?php echo e($data['available']); ?></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                        <small class="text-muted mt-2 d-block">
                                            <strong>Formula:</strong> Available Balance = Opening Balance + Leaves Earned - Leaves Used. The Available Balance at the end of the month becomes the Opening Balance for the next month.
                                        </small>
                                    </div>
                                </div>
                                <?php $firstContent = false; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/leave/carryforward.blade.php ENDPATH**/ ?>