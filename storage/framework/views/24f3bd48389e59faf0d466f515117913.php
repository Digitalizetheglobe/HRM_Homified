<?php
    $isSiteHead = false;
    if ($timeSheet->project && !empty($timeSheet->project->site_heads)) {
        $currentEmployeeId = Auth::user()->employee->id ?? null;
        $isSiteHead = $currentEmployeeId && in_array((string)$currentEmployeeId, $timeSheet->project->site_heads, true);
    }
?>

<?php if($isSiteHead): ?>
    <div class="alert alert-info">
        <i class="ti ti-info-circle"></i> You are viewing this enquiry as a Site Head (read-only access)
    </div>
<?php endif; ?>
<div class="modal-body">
    <div class="card border-0 shadow-none">
        <div class="card-body">
            <?php if(!$timeSheet->isVisibleTo(Auth::user()->id) && $timeSheet->employee_id != Auth::user()->id): ?>

            <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Employee Name ')); ?></label>
                        <p><?php echo e($timeSheet->assignedEmployee->full_name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Date')); ?></label>
                        <p><?php echo e(\Auth::user()->dateFormat($timeSheet->date)); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Project')); ?></label>
                        <p><?php echo e($timeSheet->project->project_name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Name')); ?></label>
                        <p><?php echo e($timeSheet->full_name); ?></p>
                    </div>
                </div>



                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Mobile No')); ?></label>
                        <p><?php echo e($timeSheet->mobile_no); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Email')); ?></label>
                        <p><?php echo e($timeSheet->email_id ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold"><?php echo e(__('Address')); ?></label>
                        <p><?php echo e($timeSheet->address ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Source')); ?></label>
                        <p><?php echo e($timeSheet->recommended_by ?? 'N/A'); ?></p>
                    </div>
                    <?php if($timeSheet->recommended_by == 'CP'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('CP Name')); ?></label>
                        <p><?php echo e($timeSheet->cp_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if($timeSheet->recommended_by == 'Refrence'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Refrence Name')); ?></label>
                        <p><?php echo e($timeSheet->refrence_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if($timeSheet->recommended_by == 'Others'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Other Data')); ?></label>
                        <p><?php echo e($timeSheet->other_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Presale Employee')); ?></label>
                        <p><?php echo e($timeSheet->presaleEmployee->name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Primary Reason')); ?></label>
                        <p><?php echo e($timeSheet->primary_reason ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Area Requirement')); ?></label>
                        <p><?php echo e($timeSheet->square_feet_range ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Price Range')); ?></label>
                        <p><?php echo e($timeSheet->price_range ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Status')); ?></label>
                        <p><?php echo e($timeSheet->client_status ?? 'N/A'); ?></p>
                    </div>

                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold"><?php echo e(__('Executive Remark')); ?></label>
                        <p><?php echo e($timeSheet->executive_remark ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo e(__('Follow Up Date')); ?></label>
                        <p><?php echo e($timeSheet->follow_up_date ? \Auth::user()->dateFormat($timeSheet->follow_up_date) : 'N/A'); ?></p>
                    </div>
                </div>

                <!-- Feedback Section -->
                <?php if(is_array($feedbacks) && count($feedbacks) > 0): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><?php echo e(__('Feedbacks')); ?></label>
                            <?php $__currentLoopData = $feedbacks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(is_array($feedback) && isset($feedback['description'])): ?>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <p><?php echo e($feedback['description'] ?? 'N/A'); ?></p>
                                        <small class="text-muted">
                                            <?php if(isset($feedback['followup_date'])): ?>
                                            Follow-up: <?php echo e($feedback['followup_date'] ? \Auth::user()->dateFormat($feedback['followup_date']) : 'N/A'); ?><br>
                                            <?php endif; ?>
                                            Added by: <?php echo e($feedback['added_by'] ?? 'N/A'); ?> on 
                                            <?php echo e($feedback['created_at'] ?? 'N/A'); ?>

                                            <?php if(isset($feedback['updated_by'])): ?>
                                            <br>Last updated by: <?php echo e($feedback['updated_by']); ?> on <?php echo e($feedback['updated_at']); ?>

                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><?php echo e(__('Feedbacks')); ?></label>
                            <p>No feedback available</p>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Employee Name ')); ?></label>
                        <p><?php echo e($timeSheet->assignedEmployee->full_name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Date')); ?></label>
                        <p><?php echo e(\Auth::user()->dateFormat($timeSheet->date)); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Project')); ?></label>
                        <p><?php echo e($timeSheet->project->project_name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Name')); ?></label>
                        <p><?php echo e($timeSheet->full_name); ?></p>
                    </div>
                </div>



                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Mobile No')); ?></label>
                        <p><?php echo e($timeSheet->mobile_no); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Email')); ?></label>
                        <p><?php echo e($timeSheet->email_id ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold"><?php echo e(__('Address')); ?></label>
                        <p><?php echo e($timeSheet->address ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Source')); ?></label>
                        <p><?php echo e($timeSheet->recommended_by ?? 'N/A'); ?></p>
                    </div>
                    <?php if($timeSheet->recommended_by == 'CP'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('CP Name')); ?></label>
                        <p><?php echo e($timeSheet->cp_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if($timeSheet->recommended_by == 'Refrence'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Refrence Name')); ?></label>
                        <p><?php echo e($timeSheet->refrence_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if($timeSheet->recommended_by == 'Others'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Other Data')); ?></label>
                        <p><?php echo e($timeSheet->other_data ?? 'N/A'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Presale Employee')); ?></label>
                        <p><?php echo e($timeSheet->presaleEmployee->name ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Primary Reason')); ?></label>
                        <p><?php echo e($timeSheet->primary_reason ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Area Requirement')); ?></label>
                        <p><?php echo e($timeSheet->square_feet_range ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Price Range')); ?></label>
                        <p><?php echo e($timeSheet->price_range ?? 'N/A'); ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo e(__('Client Status')); ?></label>
                        <p><?php echo e($timeSheet->client_status ?? 'N/A'); ?></p>
                    </div>

                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold"><?php echo e(__('Executive Remark')); ?></label>
                        <p><?php echo e($timeSheet->executive_remark ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo e(__('Follow Up Date')); ?></label>
                        <p><?php echo e($timeSheet->follow_up_date ? \Auth::user()->dateFormat($timeSheet->follow_up_date) : 'N/A'); ?></p>
                    </div>
                </div>

                <!-- Feedback Section -->
                <?php if(is_array($feedbacks) && count($feedbacks) > 0): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><?php echo e(__('Feedbacks')); ?></label>
                            <?php $__currentLoopData = $feedbacks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(is_array($feedback) && isset($feedback['description'])): ?>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <p><?php echo e($feedback['description'] ?? 'N/A'); ?></p>
                                        <small class="text-muted">
                                            <?php if(isset($feedback['followup_date'])): ?>
                                            Follow-up: <?php echo e($feedback['followup_date'] ? \Auth::user()->dateFormat($feedback['followup_date']) : 'N/A'); ?><br>
                                            <?php endif; ?>
                                            Added by: <?php echo e($feedback['added_by'] ?? 'N/A'); ?> on 
                                            <?php echo e($feedback['created_at'] ?? 'N/A'); ?>

                                            <?php if(isset($feedback['updated_by'])): ?>
                                            <br>Last updated by: <?php echo e($feedback['updated_by']); ?> on <?php echo e($feedback['updated_at']); ?>

                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><?php echo e(__('Feedbacks')); ?></label>
                            <p>No feedback available</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).on('click', '.view-btn', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var title = $(this).data('title');
        var size = $(this).data('size') || 'lg'; // Default to large if not specified
        
        // Set modal title and size
        $('#commonModal .modal-title').text(title);
        $('#commonModal .modal-dialog').removeClass('modal-lg modal-xl').addClass('modal-' + size);
        
        // Show loading spinner
        $('#commonModal .modal-body').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading enquiry details...</p>
            </div>
        `);
        
        // Show modal immediately with loading state
        $('#commonModal').modal('show');
        
        // Load modal content via AJAX
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#commonModal .modal-body').html(response);
            },
            error: function() {
                $('#commonModal .modal-body').html(`
                    <div class="alert alert-danger">
                        Failed to load enquiry details. Please try again.
                    </div>
                `);
            }
        });
    });

    function unlockTimesheet(element) {
        if (confirm('Are you sure you want to unlock this timesheet entry? You will be able to view it.')) {
            $.ajax({
                url: $(element).attr('href'),
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        show_toastr('Success', response.message, 'success');
                        // Reload the modal content
                        $('#commonModal .modal-body').load(window.location.href + ' #commonModal .modal-body > *');
                    }
                },
                error: function(xhr) {
                    show_toastr('Error', xhr.responseJSON.message || 'Error unlocking entry', 'error');
                }
            });
        }
    }
</script>

<script>
    // Function to load assignable employees based on project
function loadAssignableEmployees(projectId) {
    if (!projectId) {
        $('#assignEmployeeDropdown').html('<option value="">Select Project First</option>');
        return;
    }

    $.ajax({
        url: "<?php echo e(url('get-project-employees')); ?>/" + projectId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let options = '<option value="">Select Employee to Assign</option>';
            
            response.employees.forEach(employee => {
                // Exclude current user from assignable employees
                if (employee.id != <?php echo e(Auth::id()); ?>) {
                    options += `<option value="${employee.id}">${employee.name}</option>`;
                }
            });
            
            $('#assignEmployeeDropdown').html(options);
        },
        error: function(xhr) {
            console.error('Error loading employees:', xhr.responseText);
            $('#assignEmployeeDropdown').html('<option value="">Error loading employees</option>');
        }
    });
}

// Call this when project changes
$('#projectDropdown').on('change', function() {
    loadAssignableEmployees($(this).val());
});

// Initialize if editing
<?php if(isset($timeSheet) && $timeSheet->project_id): ?>
loadAssignableEmployees(<?php echo e($timeSheet->project_id); ?>);
<?php endif; ?>    
</script><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/timeSheet/show.blade.php ENDPATH**/ ?>