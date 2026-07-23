<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table modal-table" id="pc-dt-simple">
                        <tr role="row">
                            <th><?php echo e(__('Employee')); ?></th>
                            <td><?php echo e(!empty($employee->full_name) ? $employee->full_name : ''); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Leave Type')); ?></th>
                            <td><?php echo e(!empty($leavetype->title) ? $leavetype->title : ''); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Leave Duration')); ?></th>
                            <td>
                                <?php if(isset($leave->leave_duration_type)): ?>
                                    <?php if($leave->leave_duration_type == 'full_day'): ?>
                                        <span class="badge bg-primary"><?php echo e(__('Full Day')); ?></span>
                                    <?php elseif($leave->leave_duration_type == 'half_day'): ?>
                                        <span class="badge bg-info"><?php echo e(__('Half Day')); ?></span>
                                        <?php if($leave->remark): ?>
                                            <?php
                                                $remark = $leave->remark;
                                                if (strpos($remark, 'Half Day Session:') !== false) {
                                                    $session = str_replace('Half Day Session: ', '', substr($remark, strpos($remark, 'Half Day Session:')));
                                                    $session = trim(explode('|', $session)[0]);
                                                    echo '<br><small class="badge bg-info">' . $session . '</small>';
                                                }
                                            ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo e(__('N/A')); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo e(__('N/A')); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Applied On')); ?></th>
                            <td><?php echo e(\Auth::user()->dateFormat($leave->applied_on)); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Start Date')); ?></th>
                            <td><?php echo e(\Auth::user()->dateFormat($leave->start_date)); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('End Date')); ?></th>
                            <td><?php echo e(\Auth::user()->dateFormat($leave->end_date)); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Total Days')); ?></th>
                            <td><?php echo e($leave->total_leave_days); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Leave Reason')); ?></th>
                            <td><?php echo e(!empty($leave->leave_reason) ? $leave->leave_reason : ''); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo e(__('Status')); ?></th>
                            <td>
                                <?php if($leave->status == 'Pending'): ?>
                                    <div class="badge bg-warning p-2 px-3 rounded">
                                        <?php echo e($leave->status); ?>

                                    </div>
                                <?php elseif($leave->status == 'Approved'): ?>
                                    <div class="badge bg-success p-2 px-3 rounded">
                                        <?php echo e($leave->status); ?>

                                    </div>
                                <?php elseif($leave->status == 'Reject'): ?>
                                    <div class="badge bg-danger p-2 px-3 rounded">
                                        <?php echo e($leave->status); ?>

                                    </div>
                                <?php else: ?>
                                    <?php echo e($leave->status); ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if($leave->forwarded_to_director_id): ?>
                            <tr>
                                <th><?php echo e(__('Forwarded To')); ?></th>
                                <td><?php echo e($leave->forwardedToDirector->name ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo e(__('Company Approved')); ?></th>
                                <td><?php echo e($leave->company_approved ? 'Yes' : 'No'); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if(!empty($leave->remark) && strpos($leave->remark, 'Half Day Session:') === false): ?>
                            <tr>
                                <th><?php echo e(__('Remark')); ?></th>
                                <td><?php echo e($leave->remark); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/leave/show.blade.php ENDPATH**/ ?>