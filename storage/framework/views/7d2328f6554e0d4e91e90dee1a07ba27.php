<?php
    $setting = App\Models\Utility::settings();
?>

<?php echo e(Form::model($project, ['route' => ['projects.update', $project->id], 'method' => 'POST', 'id' => 'projectForm', 'class' => 'needs-validation', 'novalidate' => 'novalidate'])); ?>

<?php echo csrf_field(); ?>
<?php echo method_field('PUT'); ?>

<div class="modal-body">
    <div class="row">

        <!-- Project Type Field -->
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                <label for="project_type" class="form-label"><?php echo e(__('Project Type')); ?></label>
                <div class="form-icon-user">
                    <select class="form-control" name="project_type" id="project_type" required>
                        <option value=""><?php echo e(__('Select Project Type')); ?></option>
                        <option value="1" <?php echo e($project->project_type == 1 ? 'selected' : ''); ?>><?php echo e(__('Residential Project')); ?></option>
                        <option value="2" <?php echo e($project->project_type == 2 ? 'selected' : ''); ?>><?php echo e(__('Commercial Project')); ?></option>
                        <option value="3" <?php echo e($project->project_type == 3 ? 'selected' : ''); ?>><?php echo e(__('Plotting Project')); ?></option>
                    </select>
                </div>
                <div class="invalid-feedback project_type-error" style="display: none;"></div>
            </div>
        </div>
        <!-- Project Name Field -->
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                <?php echo e(Form::label('project_name', __('Project Name'), ['class' => 'form-label'])); ?>

                <div class="form-icon-user">
                    <?php echo e(Form::text('project_name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Project Name')])); ?>

                </div>
                <div class="invalid-feedback project_name-error" style="display: none;"></div>
            </div>
        </div>

        <!-- Site Heads Field -->
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                <label for="site_heads" class="form-label"><?php echo e(__('Site Heads')); ?></label>
                <select class="form-control select2" name="site_heads[]" id="site_heads" multiple>
                    <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($employee->id); ?>" 
                            <?php echo e(in_array($employee->id, (array)$project->site_heads) ? 'selected' : ''); ?>>
                            <?php echo e($employee->user->name ?? $employee->full_name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <div class="invalid-feedback site_heads-error" style="display: none;"></div>
            </div>
        </div>

        <!-- Location Field -->
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                <?php echo e(Form::label('location', __('Location'), ['class' => 'form-label'])); ?>

                <div class="form-icon-user">
                    <?php echo e(Form::text('location', null, ['class' => 'form-control', 'placeholder' => __('Enter Location')])); ?>

                </div>
            </div>
        </div>

    <!-- Project Start Date Field -->
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="form-group">
            <?php echo e(Form::label('project_startdate', __('Project Start Date'), ['class' => 'form-label'])); ?>

            <div class="form-icon-user">
                <input type="date" class="form-control" name="project_startdate" id="project_startdate" 
                    value="<?php echo e($project->project_startdate ? \Carbon\Carbon::parse($project->project_startdate)->format('Y-m-d') : ''); ?>" 
                    autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Project End Date Field -->
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="form-group">
            <?php echo e(Form::label('project_enddate', __('Project End Date'), ['class' => 'form-label'])); ?>

            <div class="form-icon-user">
                <input type="date" class="form-control" name="project_enddate" id="project_enddate" 
                    value="<?php echo e($project->project_enddate ? \Carbon\Carbon::parse($project->project_enddate)->format('Y-m-d') : ''); ?>" 
                    autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Branch Field -->
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="form-group">
            <label for="branch_id" class="form-label"><?php echo e(__('Branch')); ?></label>
            <div class="form-icon-user">
                <select class="form-control" name="branch_id" id="branch_id" required>
                    <option value=""><?php echo e(__('Select Branch')); ?></option>
                    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="invalid-feedback branch_id-error" style="display: none;"></div>
        </div>
    </div>

    <!-- Department Field -->
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="form-group">
            <?php echo e(Form::label('department_id', __('Department'), ['class' => 'form-label'])); ?>

            <div class="form-icon-user">
                <select class="form-control" name="department_id" id="department_id" required disabled>
                    <option value=""><?php echo e(__('Select Department')); ?></option>
                </select>
            </div>
            <div class="invalid-feedback department_id-error" style="display: none;"></div>
        </div>
    </div>

        <!-- Employee Selection -->
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                <?php echo e(Form::label('employee_id', __('Employee'), ['class' => 'form-label'])); ?>

                <div class="form-icon-user">
                    <select class="form-control" id="employee_id" disabled>
                        <option value=""><?php echo e(__('Select Employee')); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Hidden field for assigned_data -->
        <input type="hidden" name="assigned_data" id="assignedData" 
            value="<?php echo e(json_encode($project->assigned_data ?? [])); ?>">

        <div class="col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label"><?php echo e(__('Selected Assignments')); ?></label>
                <div id="selectedAssignmentsBox" class="p-2 border rounded bg-light" style="min-height: 100px;">
                    <?php if(is_array($project->assigned_data) && count($project->assigned_data) > 0): ?>
                        <?php $__currentLoopData = $project->assigned_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php    
                                $department = \App\Models\Department::find($assignment['department_id']);
                                $employees = \App\Models\Employee::whereIn('id', $assignment['employee_ids'] ?? [])->get();
                            ?>
                            <div class="mb-3 assignment-group" data-dept-id="<?php echo e($assignment['department_id']); ?>">
                                <h6 class="mb-1"><?php echo e($department ? $department->name : 'Unknown Department'); ?></h6>
                                <div class="d-flex flex-wrap employees-container">
                                    <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge bg-primary me-1 mb-1 employee-badge" 
                                            data-dept-id="<?php echo e($assignment['department_id']); ?>" 
                                            data-emp-id="<?php echo e($employee->id); ?>">
                                            <?php echo e($employee->full_name); ?>

                                            <i class="fas fa-times ms-1 remove-employee" style="cursor: pointer;"></i>
                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <div class="text-muted"><?php echo e(__('No assignments selected.')); ?></div>
                    <?php endif; ?>
                </div>
                <div class="invalid-feedback assigned_data-error" style="display: none;"></div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="<?php echo e(__('Update')); ?>" class="btn btn-primary">
</div>
<?php echo e(Form::close()); ?>


<script>
$(document).ready(function() {
    let assignments = <?php echo json_encode($project->assigned_data ?? []); ?>; 
    let allEmployees = {}; // Cache of employees by department: { deptId: [employee1, employee2] }
    let allDepartments = {}; // Cache of departments


    // Initialize the UI with existing assignments
    updateAssignmentsUI();

    // Branch change event
    $('#branch_id').change(function() {
        const branchId = $(this).val();
        const departmentSelect = $('#department_id');
        
        if (!branchId) {
            departmentSelect.html('<option value=""><?php echo e(__("Select Department")); ?></option>').prop('disabled', true);
            $('#employee_id').html('<option value=""><?php echo e(__("Select Employee")); ?></option>').prop('disabled', true);
            return;
        }

        // Enable department select
        departmentSelect.prop('disabled', false);
        
        // Show loading state
        departmentSelect.html('<option value=""><?php echo e(__("Loading departments...")); ?></option>');

        $.ajax({
            url: '<?php echo e(route("get-departments-by-branch", "")); ?>/' + branchId,
            type: 'GET',
            success: function(data) {
                let options = '<option value=""><?php echo e(__("Select Department")); ?></option>';
                
                data.forEach(department => {
                    options += `<option value="${department.id}">${department.name}</option>`;
                });
                
                departmentSelect.html(options);
                $('#employee_id').html('<option value=""><?php echo e(__("Select Employee")); ?></option>').prop('disabled', true);
            },
            error: function(xhr) {
                console.error('Error loading departments:', xhr);
                departmentSelect.html('<option value=""><?php echo e(__("Error loading departments")); ?></option>');
            }
        });
    });

    // Department change event
    $('#department_id').change(function() {
        const departmentId = $(this).val();
        const employeeSelect = $('#employee_id');
        
        if (!departmentId) {
            employeeSelect.html('<option value=""><?php echo e(__("Select Employee")); ?></option>').prop('disabled', true);
            return;
        }

        // Enable employee select
        employeeSelect.prop('disabled', false);
        
        // Check if we already have this department's employees cached
        if (allEmployees[departmentId]) {
            updateEmployeeDropdown(departmentId);
            return;
        }

        // Show loading state
        employeeSelect.html('<option value=""><?php echo e(__("Loading employees...")); ?></option>');

        $.ajax({
            url: '<?php echo e(route("get-employees-by-department", "")); ?>/' + departmentId,
            type: 'GET',
            success: function(data) {
                // Cache the employees for this department
                allEmployees[departmentId] = data;
                updateEmployeeDropdown(departmentId);
            },
            error: function(xhr) {
                console.error('Error loading employees:', xhr);
                employeeSelect.html('<option value=""><?php echo e(__("Error loading employees")); ?></option>');
            }
        });
    });

    // Update the updateEmployeeDropdown function
    function updateEmployeeDropdown(departmentId) {
        const currentEmployees = allEmployees[departmentId] || [];
        const employeeSelect = $('#employee_id');
        
        // Get employees already selected in this department
        const selectedInDepartment = assignments
            .filter(a => a.department_id == departmentId)
            .flatMap(a => a.employee_ids);

        let options = '<option value=""><?php echo e(__("Select Employee")); ?></option>';
        
        currentEmployees.forEach(employee => {
            // Only show employees not already selected in this department
            if (!selectedInDepartment.includes(employee.id)) {
                options += `<option value="${employee.id}">${employee.name}</option>`;
            }
        });

        employeeSelect.html(options);
    }

    // Add employee to assignment
    $('#employee_id').change(function() {
        const employeeId = $(this).val();
        const employeeName = $(this).find('option:selected').text();
        const departmentId = $('#department_id').val();
        const departmentName = $('#department_id option:selected').text();

        if (!employeeId || !departmentId) return;

        // Find or create department assignment
        let assignment = assignments.find(a => a.department_id == departmentId);
        if (!assignment) {
            assignment = {
                department_id: parseInt(departmentId),
                employee_ids: []
            };
            assignments.push(assignment);
        }

        // Add employee if not already exists
        if (!assignment.employee_ids.includes(parseInt(employeeId))) {
            assignment.employee_ids.push(parseInt(employeeId));
            updateAssignmentsUI();
            updateEmployeeDropdown(departmentId); // Refresh dropdown
        }

        $(this).val('');
    });

 // Pre-load department and employee data for existing assignments
    async function preloadAssignmentData() {
        if (assignments.length === 0) return;

        // Get unique department IDs from assignments
        const departmentIds = [...new Set(assignments.map(a => a.department_id))];
        
        // Pre-load departments
        try {
            const deptResponse = await $.ajax({
                url: '<?php echo e(route("get-departments-by-id")); ?>',
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    department_ids: departmentIds
                }
            });
            
            deptResponse.forEach(dept => {
                allDepartments[dept.id] = dept;
            });
        } catch (error) {
            console.error('Error loading departments:', error);
        }

        // Pre-load employees for each department
        for (const deptId of departmentIds) {
            try {
                const empResponse = await $.ajax({
                    url: '<?php echo e(route("get-employees-by-department", "")); ?>/' + deptId,
                    type: 'GET'
                });
                
                allEmployees[deptId] = empResponse;
            } catch (error) {
                console.error('Error loading employees for department ' + deptId + ':', error);
            }
        }

        // Now update the UI with proper names
        updateAssignmentsUI();
    }

    // Update the updateAssignmentsUI function to use cached data
    function updateAssignmentsUI() {
        const container = $('#selectedAssignmentsBox');
        container.empty();

        if (assignments.length === 0) {
            container.html('<div class="text-muted"><?php echo e(__("No assignments selected.")); ?></div>');
            $('#assignedData').val(JSON.stringify([]));
            return;
        }

        assignments.forEach(assignment => {
            const departmentId = assignment.department_id;
            const department = allDepartments[departmentId] || { name: 'Department #' + departmentId };
            
            const deptDiv = $(`
                <div class="mb-3 assignment-group" data-dept-id="${departmentId}">
                    <h6 class="mb-1">${department.name}</h6>
                    <div class="d-flex flex-wrap employees-container"></div>
                </div>
            `);

            const employeesContainer = deptDiv.find('.employees-container');
            
            if (assignment.employee_ids.length === 0) {
                employeesContainer.append('<span class="text-muted small"><?php echo e(__("No employees selected")); ?></span>');
            } else {
                assignment.employee_ids.forEach(employeeId => {
                    const employee = (allEmployees[departmentId] || []).find(emp => emp.id == employeeId) || 
                                   { name: 'Employee #' + employeeId };
                    
                    employeesContainer.append(`
                        <span class="badge bg-primary me-1 mb-1 employee-badge" 
                              data-dept-id="${departmentId}" =
                              data-emp-id="${employeeId}">
                            ${employee.name}
                            <i class="fas fa-times ms-1 remove-employee" style="cursor: pointer;"></i>
                        </span>
                    `);
                });
            }

            container.append(deptDiv);
        });

        // Update hidden field with the current assignments
        $('#assignedData').val(JSON.stringify(assignments));
    }

    // Call the preload function
    preloadAssignmentData();


    // Remove employee from assignment
    $(document).on('click', '.remove-employee', function() {
        const badge = $(this).closest('.employee-badge');
        const departmentId = badge.data('dept-id');
        const employeeId = badge.data('emp-id');

        // Find the assignment
        const assignment = assignments.find(a => a.department_id == departmentId);
        if (assignment) {
            // Remove the employee
            assignment.employee_ids = assignment.employee_ids.filter(id => id != employeeId);
            
            // If no more employees, remove the entire assignment
            if (assignment.employee_ids.length === 0) {
                assignments = assignments.filter(a => a.department_id != departmentId);
            }
            
            updateAssignmentsUI();
            
            // Refresh dropdown if this is the current department
            if ($('#department_id').val() == departmentId) {
                updateEmployeeDropdown(departmentId);
            }
        }
    });

    // Pre-load employees for existing assignments
    <?php if(isset($project) && is_array($project->assigned_data) && count($project->assigned_data) > 0): ?>
        // Set the initial assignments structure
        assignments = <?php echo json_encode($project->assigned_data); ?>;
        updateAssignmentsUI();
    <?php endif; ?>

    // Initialize select2 for site heads
    $('#site_heads').select2({
        placeholder: "Select Site Heads",
        allowClear: true
    });
});

// Form validation
$('#projectForm').on('submit', function(e) {
    e.preventDefault();
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').hide();
    
    let isValid = true;
    
    // Check required fields
    if (!$('#project_name').val().trim()) {
        showError('project_name', 'Project name is required');
        isValid = false;
    }

    if (!$('#project_type').val()) {
        showError('project_type', 'Project type is required');
        isValid = false;
    }
    
    if (!$('#branch_id').val()) {
        showError('branch_id', 'Please select a branch');
        isValid = false;
    }
    
    if (!$('#department_id').val()) {
        showError('department_id', 'Please select a department');
        isValid = false;
    }
    
    // Check date validation if dates are provided
    const startDate = $('#project_startdate').val();
    const endDate = $('#project_enddate').val();
    
    if (startDate && endDate) {
        if (new Date(endDate) < new Date(startDate)) {
            showError('project_enddate', 'End date must be after start date');
            isValid = false;
        }
    }
    
    // Check assignments
    if (!validateAssignments()) {
        isValid = false;
    }
    
    if (isValid) {
        let formData = new FormData(this);
        
        // Add the _method parameter for Laravel to recognize it as a PUT request
        formData.append('_method', 'PUT');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST', // Use POST but with _method=PUT
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.href = '<?php echo e(route('projects.index')); ?>';
                }
            },
            error: function(xhr) {
                // Handle errors
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (const field in errors) {
                        showError(field, errors[field][0]);
                    }
                } else {
                    alert('An error occurred while updating the project.');
                }
            }
        });
    }
});

function showError(fieldName, message) {
    $(`#${fieldName}`).addClass('is-invalid');
    $(`.${fieldName}-error`).text(message).show();
}

// Real-time validation for fields when they lose focus
$('#project_name, #branch_id, #department_id').on('blur', function() {
    const field = $(this);
    const fieldName = field.attr('id');
    
    if (fieldName === 'project_name' && !field.val().trim()) {
        showError(fieldName, 'Project name is required');
    } else if (fieldName === 'branch_id' && !field.val()) {
        showError(fieldName, 'Please select a branch');
    } else if (fieldName === 'department_id' && !field.val()) {
        showError(fieldName, 'Please select a department');
    } else {
        field.removeClass('is-invalid');
        $(`.${fieldName}-error`).hide();
    }
});

$('#project_type').on('blur', function() {
    if (!$(this).val()) {
        showError('project_type', 'Project type is required');
    } else {
        $(this).removeClass('is-invalid');
        $('.project_type-error').hide();
    }
});

// Validate assigned data when adding/removing employees
function validateAssignments() {
    const assignedData = JSON.parse($('#assignedData').val() || '[]');
    const errorElement = $('.assigned_data-error');
    
    if (assignedData.length === 0) {
        errorElement.text('At least one employee assignment is required').show();
        $('#selectedAssignmentsBox').addClass('is-invalid');
        return false;
    }
    
    let hasEmployees = false;
    assignedData.forEach(assignment => {
        if (assignment.employee_ids && assignment.employee_ids.length > 0) {
            hasEmployees = true;
        }
    });
    
    if (!hasEmployees) {
        errorElement.text('At least one employee must be assigned').show();
        $('#selectedAssignmentsBox').addClass('is-invalid');
        return false;
    }
    
    errorElement.hide();
    $('#selectedAssignmentsBox').removeClass('is-invalid');
    return true;
}

// Call validateAssignments when assignments change
function updateAssignmentsUI() {
    // Add validation check
    validateAssignments();
}
</script><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/projects/edit.blade.php ENDPATH**/ ?>