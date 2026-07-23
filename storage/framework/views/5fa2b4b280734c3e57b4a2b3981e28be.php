<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Manage Bookings')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Bookings')); ?></li> 
<?php $__env->stopSection(); ?>

<?php $__env->startSection('action-button'); ?>
<?php if(Auth::user()->type != 'hr'): ?>
    <a href="<?php echo e(route('booking.export', [
        'start_date' => request('start_date'),
        'end_date' => request('end_date'),
        'status' => request('status'),
        'project' => request('project'),
        'search' => request('search')
    ])); ?>" class="btn btn-sm btn-primary">
        <i class="ti ti-file-export"></i>
    </a>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <?php echo e(Form::open(['route' => ['booking.all'], 'method' => 'get', 'id' => 'booking_filter'])); ?>

                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <?php echo e(Form::label('start_date', __('Start Date'), ['class' => 'form-label'])); ?>

                                            <?php echo e(Form::date('start_date', isset($_GET['start_date']) ? $_GET['start_date'] : '', ['class' => 'month-btn form-control', 'autocomplete' => 'off', 'id' => 'start_date'])); ?>

                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <?php echo e(Form::label('end_date', __('End Date'), ['class' => 'form-label'])); ?>

                                            <?php echo e(Form::date('end_date', isset($_GET['end_date']) ? $_GET['end_date'] : '', ['class' => 'month-btn form-control', 'autocomplete' => 'off', 'id' => 'end_date'])); ?>

                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            <?php echo e(Form::label('status', __('Status'), ['class' => 'form-label'])); ?>

                                            <?php echo e(Form::select('status', [
                                                '' => 'Select Status',
                                                'active' => 'Active',
                                                'cancelled' => 'Cancelled',
                                                'completed' => 'Completed',
                                                'agreement_done' => 'Agreement Done'
                                            ], request()->get('status'), ['class' => 'form-control select', 'id' => 'status'])); ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary"
                                            onclick="document.getElementById('booking_filter').submit(); return false;">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="<?php echo e(route('booking.all')); ?>" class="btn btn-sm btn-danger">
                                            <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off "></i></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php echo e(Form::close()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                <div class="card-body py-0">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <?php if(in_array(Auth::user()->type, ['company', 'director'])): ?>
                                        <th><?php echo e(__('Employee Name')); ?></th>
                                    <?php endif; ?>
                                    <th><?php echo e(__('Project')); ?></th>
                                    <th><?php echo e(__('Primary Applicant')); ?></th>
                                    <th><?php echo e(__('Contact No')); ?></th>
                                    <th><?php echo e(__('Email ID')); ?></th>
                                    <th><?php echo e(__('Booking Date')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <?php if(Auth::user()->type == 'employee'): ?>
                                        <th><?php echo e(__('Access Type')); ?></th>
                                    <?php endif; ?>
                                    <th width="200px"><?php echo e(__('Action')); ?></th>
                                </tr>
                            </thead>
                            <tbody id="booking-table-body">
                                <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $isSiteHead = false;
                                        if (Auth::user()->type == 'employee' && $booking->project) {
                                            $isSiteHead = !empty($booking->project->site_heads) && 
                                                        in_array((string)Auth::user()->id, $booking->project->site_heads);
                                        }
                                    ?>
                                    <tr>
                                        <?php if(in_array(Auth::user()->type, ['company', 'director'])): ?>
                                            <td><?php echo e($booking->employee->name ?? 'N/A'); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo e($booking->project->project_name ?? 'N/A'); ?></td>
                                        <td><?php echo e($booking->primary_applicant_name); ?></td>
                                        <td><?php echo e($booking->primary_applicant_contact_no); ?></td>
                                        <td><?php echo e($booking->primary_applicant_email); ?></td>
                                        <td><?php echo e(\Carbon\Carbon::parse($booking->booking_date)->format('d-m-Y')); ?></td>
                                        <td>
                                            <?php if($booking->is_cancelled): ?>
                                                <span class="badge bg-danger"><?php echo e(__('Cancelled')); ?></span>
                                            <?php elseif($booking->remaining <= 0): ?>
                                                <?php if($booking->agreement == 'done'): ?>
                                                    <span class="badge bg-success"><?php echo e(__('Agreement Done')); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo e(__('Completed')); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if($booking->agreement == 'done'): ?>
                                                    <span class="badge bg-info"><?php echo e(__('Agreement')); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><?php echo e(__('Active')); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if(Auth::user()->type == 'employee'): ?>
                                            <td>
                                                <?php if($isSiteHead): ?>
                                                    <span class="badge bg-warning"><?php echo e(__('Site Head')); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo e(__('Employee')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="Action">
                                            <!-- View Button -->
                                            <div class="action-btn bg-primary ms-2">
                                                <a href="<?php echo e(route('booking.show', $booking->id)); ?>" 
                                                class="mx-3 btn btn-sm align-items-center">
                                                    <i class="ti ti-eye text-white"></i>
                                                </a>
                                            </div>

                                            <!-- Add Download Button -->
                                            <div class="action-btn bg-info ms-2">
                                                <a href="<?php echo e(route('booking.form.pdf', Crypt::encrypt($booking->id))); ?>" 
                                                class="mx-3 btn btn-sm align-items-center">
                                                    <i class="ti ti-download text-white"></i>
                                                </a>
                                            </div>
                                            
                                            <?php if(!$booking->is_cancelled && $booking->agreement === 'pending' ): ?>
                                                <div class="action-btn bg-success ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center mark-agreement-done"
                                                    data-id="<?php echo e($booking->id); ?>">
                                                        <i class="ti ti-check text-white"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>


                                            <?php if(Auth::user()->type == 'company'): ?>
                                                <div class="action-btn bg-danger ms-2">
                                                    <form action="<?php echo e(route('booking.destroy', $booking->id)); ?>" method="POST" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit"
                                                            class="mx-3 btn btn-sm d-inline-flex align-items-center">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script-page'); ?>
<script>
    $(document).ready(function() {
        // Function to refresh the booking table
        function refreshBookingTable() {
            $.ajax({
                url: "<?php echo e(route('booking.all')); ?>",
                type: 'GET',
                data: $('#booking_filter').serialize(),
                success: function(response) {
                    var newContent = $(response).find('#booking-table-body').html();
                    $('#booking-table-body').html(newContent);
                    initEventHandlers();
                },
                error: function() {
                    // Handle error
                }
            });
        }

        // Initialize event handlers
        function initEventHandlers() {
            // Handle form submission for creating new booking
            $('#create-btn').on('click', function() {
                $.ajax({
                    url: $(this).data('url'),
                    type: 'GET',
                    success: function(response) {
                        $('#commonModal .modal-body').html(response);
                        $('#commonModal').modal('show');
                        
                        $('#booking-form').on('submit', function(e) {
                            e.preventDefault();
                            var form = $(this);
                            var url = form.attr('action');
                            
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: form.serialize(),
                                success: function(response) {
                                    if(response.success) {
                                        $('#commonModal').modal('hide');
                                        show_toastr('Success', response.message, 'success');
                                        refreshBookingTable();
                                    }
                                },
                                error: function(xhr) {
                                    if(xhr.status === 422) {
                                        $('.invalid-feedback').remove();
                                        $('.is-invalid').removeClass('is-invalid');
                                        
                                        var errors = xhr.responseJSON.errors;
                                        $.each(errors, function(field, messages) {
                                            var input = $('[name="' + field + '"]');
                                            input.addClass('is-invalid');
                                            input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                        });
                                    } else {
                                        show_toastr('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                                    }
                                }
                            });
                        });
                    }
                });
            });

            // Handle edit button clicks
            $('.edit-btn').on('click', function() {
                var url = $(this).data('url');
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#commonModal .modal-body').html(response);
                        $('#commonModal').modal('show');
                        
                        $('#booking-form').on('submit', function(e) {
                            e.preventDefault();
                            var form = $(this);
                            var url = form.attr('action');
                            
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: form.serialize(),
                                success: function(response) {
                                    if(response.success) {
                                        $('#commonModal').modal('hide');
                                        show_toastr('Success', response.message, 'success');
                                        refreshBookingTable();
                                    }
                                },
                                error: function(xhr) {
                                    if(xhr.status === 422) {
                                        $('.invalid-feedback').remove();
                                        $('.is-invalid').removeClass('is-invalid');
                                        
                                        var errors = xhr.responseJSON.errors;
                                        $.each(errors, function(field, messages) {
                                            var input = $('[name="' + field + '"]');
                                            input.addClass('is-invalid');
                                            input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                        });
                                    } else {
                                        show_toastr('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                                    }
                                }
                            });
                        });
                    }
                });
            });
            
            // Handle cancel booking button
            $('.cancel-booking').on('click', function(e) {
                e.preventDefault();
                var bookingId = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to cancel this booking?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, cancel it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "<?php echo e(url('booking')); ?>/" + bookingId + "/cancel",
                            type: 'POST',
                            data: {
                                _token: "<?php echo e(csrf_token()); ?>",
                                _method: 'POST'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Cancelled!',
                                        response.message,
                                        'success'
                                    ).then(() => {
                                        refreshBookingTable();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message,
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    'Something went wrong.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        }

        // Initialize event handlers on page load
        initEventHandlers();

        // Handle filter form submission
        $('#booking_filter').on('submit', function(e) {
            e.preventDefault();
            refreshBookingTable();
        });

        // Refresh table when modal is closed
        $('#commonModal').on('hidden.bs.modal', function () {
            refreshBookingTable();
        });

        // View button handler
        $('.view-btn').on('click', function() {
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $('#commonModal .modal-body').html(response);
                    $('#commonModal').modal('show');
                }
            });
        });
    });

    $(document).on('click', '.mark-agreement-done', function(e) {
        e.preventDefault();
        
        var bookingId = $(this).data('id');
        var button = $(this);
        
        // Build the correct URL
        var url = "<?php echo e(route('booking.mark.agreement.done', ['id' => ':id'])); ?>";
        url = url.replace(':id', bookingId);
        
        // Show confirmation popup
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to mark this agreement as done?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, mark as done!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>",
                        _method: 'POST'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Success!',
                                response.message,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/booking/allbooking.blade.php ENDPATH**/ ?>