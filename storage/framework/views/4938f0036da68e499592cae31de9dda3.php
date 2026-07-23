

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('View Booking Details')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo e(route('booking.all')); ?>"><?php echo e(__('Bookings')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('View Booking')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('action-button'); ?>
    <a href="<?php echo e(route('booking.all')); ?>" class="btn btn-sm btn-primary">
        <i class="ti ti-arrow-left"></i> <?php echo e(__('Back')); ?>

    </a>
    <!-- <?php if(Auth::user()->can('Edit TimeSheet') || Auth::user()->type == 'company'): ?>
        <a href="<?php echo e(route('booking.edit', $bookingForm->id)); ?>" class="btn btn-sm btn-info">
            <i class="ti ti-edit"></i> <?php echo e(__('Edit')); ?>

        </a>
    <?php endif; ?> -->
    <a href="<?php echo e(route('booking.form.pdf', Crypt::encrypt($bookingForm->id))); ?>" class="btn btn-sm btn-success">
        <i class="ti ti-download"></i> <?php echo e(__('Download PDF')); ?>

    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-xl-12">
            <!-- Booking Information Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo e(__('Booking Information')); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Booking ID')); ?></label>
                                <p class="form-control-static"><strong>#<?php echo e($bookingForm->id); ?></strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Booking Date')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->booking_date ? \Carbon\Carbon::parse($bookingForm->booking_date)->format('d-m-Y') : 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Project')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->project_name ?? ($bookingForm->project->project_name ?? 'N/A')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Unit')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->unit_name ?? ($bookingForm->unit->unit_name ?? 'N/A')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Unit Size')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->unit_size ?? 'N/A'); ?> sq.ft.</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Sales Executive')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->employee->name ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Status')); ?></label>
                                <p class="form-control-static">
                                    <?php if($bookingForm->is_cancelled): ?>
                                        <span class="badge bg-danger"><?php echo e(__('Cancelled')); ?></span>
                                    <?php elseif($bookingForm->remaining <= 0): ?>
                                        <?php if($bookingForm->agreement == 'done'): ?>
                                            <span class="badge bg-success"><?php echo e(__('Agreement Done')); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo e(__('Completed')); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if($bookingForm->agreement == 'done'): ?>
                                            <span class="badge bg-info"><?php echo e(__('Agreement')); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?php echo e(__('Active')); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Agreement Status')); ?></label>
                                <p class="form-control-static">
                                    <span class="badge bg-<?php echo e($bookingForm->agreement == 'done' ? 'success' : 'warning'); ?>">
                                        <?php echo e(ucfirst($bookingForm->agreement ?? 'Pending')); ?>

                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Applicant Details Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo e(__('Primary Applicant Details')); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Full Name')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_name ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Contact No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_contact_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Email')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_email ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Occupation')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_occupation ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Company')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_company ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Designation')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_designation ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Birth Date')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_birth_date ? \Carbon\Carbon::parse($bookingForm->primary_applicant_birth_date)->format('d-m-Y') : 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Nationality')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_nationality ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('PAN No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_pan_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Aadhar No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->primary_applicant_aadhar_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Applicant Details Card -->
            <?php if($bookingForm->secondary_applicant_name): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo e(__('Secondary Applicant Details')); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Full Name')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_name ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Contact No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_contact_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Email')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_email ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Occupation')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_occupation ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Company')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_company ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Designation')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_designation ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Birth Date')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_birth_date ? \Carbon\Carbon::parse($bookingForm->secondary_applicant_birth_date)->format('d-m-Y') : 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Nationality')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_nationality ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('PAN No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_pan_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Aadhar No')); ?></label>
                                <p class="form-control-static"><?php echo e($bookingForm->secondary_applicant_aadhar_no ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Financial Details Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo e(__('Financial Details')); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if($bookingForm->plot_area): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Plot Area')); ?></label>
                                <p class="form-control-static"><?php echo e(number_format($bookingForm->plot_area, 2)); ?> sq.ft.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->carpet_area): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Carpet Area')); ?></label>
                                <p class="form-control-static"><?php echo e(number_format($bookingForm->carpet_area, 2)); ?> sq.ft.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->built_up_area): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Built Up Area')); ?></label>
                                <p class="form-control-static"><?php echo e(number_format($bookingForm->built_up_area, 2)); ?> sq.ft.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->rate_per_sq_ft): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Rate Per Sq.ft')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->rate_per_sq_ft, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->basic_cost): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Basic Cost')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->basic_cost, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->cost_infrastructure): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Infrastructure Cost')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->cost_infrastructure, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->gst): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('GST')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->gst, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->stamp_duty): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Stamp Duty')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->stamp_duty, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->registration): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Registration')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->registration, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->legal_charges): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Legal Charges')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->legal_charges, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->other): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Other Charges')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->other, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->maintenance_cost): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Maintenance Cost')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->maintenance_cost, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($bookingForm->agreement_cost): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><?php echo e(__('Agreement Cost')); ?></label>
                                <p class="form-control-static">₹<?php echo e(number_format($bookingForm->agreement_cost, 2)); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><strong><?php echo e(__('Total Cost')); ?></strong></label>
                                <p class="form-control-static"><strong>₹<?php echo e(number_format($bookingForm->total_cost ?? 0, 2)); ?></strong></p>
                            </div>
                        </div>
                        <?php if($bookingForm->remaining !== null): ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><strong><?php echo e(__('Remaining Amount')); ?></strong></label>
                                <p class="form-control-static"><strong class="text-<?php echo e($bookingForm->remaining > 0 ? 'danger' : 'success'); ?>">₹<?php echo e(number_format($bookingForm->remaining, 2)); ?></strong></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Details Card -->
            <?php if($bookingForm->payment_data && is_array($bookingForm->payment_data) && count($bookingForm->payment_data) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo e(__('Payment Details')); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('Mode')); ?></th>
                                    <th><?php echo e(__('Date')); ?></th>
                                    <th><?php echo e(__('Details')); ?></th>
                                    <th><?php echo e(__('Amount (₹)')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $totalPaid = 0;
                                ?>
                                <?php $__currentLoopData = $bookingForm->payment_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $amount = $payment['amount'] ?? 0;
                                        $totalPaid += $amount;
                                    ?>
                                    <tr>
                                        <td><?php echo e(ucfirst($payment['mode'] ?? 'N/A')); ?></td>
                                        <td><?php echo e(isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d-m-Y') : 'N/A'); ?></td>
                                        <td><?php echo e($payment['payment_detail'] ?? 'N/A'); ?></td>
                                        <td>₹<?php echo e(number_format($amount, 2)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end"><?php echo e(__('Total Paid')); ?>:</th>
                                    <th>₹<?php echo e(number_format($totalPaid, 2)); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/booking/show.blade.php ENDPATH**/ ?>