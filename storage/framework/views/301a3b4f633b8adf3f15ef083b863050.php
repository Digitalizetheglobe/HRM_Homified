<table>
    <tr>
        <td colspan="<?php echo e(count($dates) + 1); ?>"><strong><?php echo e(\Carbon\Carbon::parse($start_date)->format('M d Y')); ?> To <?php echo e(\Carbon\Carbon::parse($end_date)->format('M d Y')); ?></strong></td>
        <td></td>
        <td colspan="9"><strong>Summary</strong></td>
    </tr>
    <tr>
        <td colspan="<?php echo e(count($dates) + 1); ?>"></td>
        <td></td>
        <td><strong>Total Present Days</strong></td>
        <td><strong>Late Marks</strong></td>
        <td><strong>Leave Without Pay</strong></td>
        <td><strong>Week Off</strong></td>
        <td><strong>Earned Leaves</strong></td>
        <td><strong>Sick Leaves</strong></td>
        <td><strong>Comp Off</strong></td>
        <td><strong>Total Payable Days</strong></td>
        <td><strong>Final Payable Salary</strong></td>
        
    </tr>
    
    <?php
        $months = [];
        foreach($dates as $date) {
            $monthKey = \Carbon\Carbon::parse($date)->format('Y-m');
            $months[$monthKey][] = $date;
        }
    ?>

    <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <!-- Employee Header -->
        <tr>
            <td colspan="<?php echo e(count($dates) + 1); ?>"><strong>Employee Code:</strong> <?php echo e($employee->employee_id); ?> </td>
            <td></td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['present'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['lm'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['lop'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['wo'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['el'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['sl'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['co'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['total'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
            <td>
                <?php if(isset($payableDaysTotals[$employee->id])): ?>
                    <strong><?php echo e(number_format($payableDaysTotals[$employee->id]['final_salary'], 1)); ?></strong>
                <?php else: ?>
                    <strong>0</strong>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?php echo e(count($dates) + 1); ?>"><strong>Employee Name:</strong> <?php echo e($employee->full_name); ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        
        <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthKey => $monthDates): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td colspan="<?php echo e(count($monthDates) + 1); ?>" style="background-color: #f3f3f3;"><strong>Month: <?php echo e(\Carbon\Carbon::parse($monthKey . '-01')->format('F Y')); ?></strong></td>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td style="background-color: #f3f3f3;"></td>
                <?php endfor; ?>
            </tr>

            <!-- Status Row -->
            <tr>
                <td><strong>Days</strong></td>
                <?php $__currentLoopData = $monthDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td><?php echo e(\Carbon\Carbon::parse($date)->format('d M (D)')); ?></td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <?php $__currentLoopData = $monthDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td>
                        <?php echo e($statusCodes[$employee->id][$date] ?? ''); ?>

                    </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
            
            <!-- In Time Row -->
            <tr>
                <td><strong>InTime</strong></td>
                <?php $__currentLoopData = $monthDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td>
                        <?php if(isset($attendanceData[$employee->id][$date]['clock_in'])): ?>
                            <?php echo e(substr($attendanceData[$employee->id][$date]['clock_in'], 0, 5)); ?>

                        <?php endif; ?>
                    </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
            
            <!-- Out Time Row -->
            <tr>
                <td><strong>OutTime</strong></td>
                <?php $__currentLoopData = $monthDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td>
                        <?php if(isset($attendanceData[$employee->id][$date]['clock_out'])): ?>
                            <?php echo e(substr($attendanceData[$employee->id][$date]['clock_out'], 0, 5)); ?>

                        <?php endif; ?>
                    </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
            
            <!-- Total Time Row -->
            <tr>
                <td><strong>Total</strong></td>
                <?php $__currentLoopData = $monthDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td>
                        <?php if(isset($attendanceData[$employee->id][$date]['total'])): ?>
                            <?php echo e($attendanceData[$employee->id][$date]['total']); ?>

                        <?php else: ?>
                            00:00
                        <?php endif; ?>
                    </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php for($i = 0; $i < 10; $i++): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/attendance/export.blade.php ENDPATH**/ ?>