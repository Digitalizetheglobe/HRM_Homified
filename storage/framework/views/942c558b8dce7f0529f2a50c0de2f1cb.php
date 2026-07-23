<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Employee')); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('Manage Employee')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('action-button'); ?>
    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">

        
        <?php if(\Auth::user()->type === 'employee' && \Auth::user()->id === $employee->user_id && $employee->approval_status !== 'approved'): ?>
            
            <a href="<?php echo e(route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))); ?>" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i> <?php echo e(__('Edit')); ?>

            </a>
        <?php endif; ?>
        
        
        <?php if(\Auth::user()->type !== 'employee' && ($employee->approval_status === 'pending' || empty($employee->approval_status))): ?>
            <button type="button" class="btn btn-sm btn-success" 
                data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="ti ti-check"></i> <?php echo e(__('Approve')); ?>

            </button>
            
            <button type="button" class="btn btn-sm btn-danger" 
                data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="ti ti-x"></i> <?php echo e(__('Reject')); ?>

            </button>
        <?php endif; ?>
        
        
        <?php if(\Auth::user()->type === 'employee' && $employee->approval_status === 'rejected'): ?>
            <form action="<?php echo e(route('employee.request-approval', $employee->id)); ?>" method="POST" class="d-inline-block">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="ti ti-refresh"></i> <?php echo e(__('Request Approval Again')); ?>

                </button>
            </form>
        <?php endif; ?>
        
        
        <a href="javascript:void(0)" 
            onclick="downloadFileBackground('<?php echo e(route('joiningletter.download.pdf', $employee->id)); ?>')"
            class="btn btn-sm btn-info">
            <i class="ti ti-download"></i> <?php echo e(__('Offer Letter')); ?>

        </a>

        <a href="javascript:void(0)" 
            onclick="downloadFileBackground('<?php echo e(route('exp.download.pdf', $employee->id)); ?>')"
            class="btn btn-sm btn-info">
            <i class="ti ti-download"></i> <?php echo e(__('Experience Certificate')); ?>

        </a>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-<?php if($employee->approval_status === 'approved'): ?>success
                                <?php elseif($employee->approval_status === 'rejected'): ?>danger
                                @elsewarning <?php endif; ?>">
                <strong><?php echo e(__('Approval Status')); ?>:</strong> 
                <?php echo e(ucfirst($employee->approval_status ?? 'pending')); ?>

                
                <?php if($employee->approval_status === 'approved' && $employee->approved_at): ?>
                    <br><small><?php echo e(__('Approved on')); ?>: <?php echo e(\Auth::user()->dateFormat($employee->approved_at)); ?> 
                    <?php if($employee->approvedBy): ?> by <?php echo e($employee->approvedBy->name); ?> <?php endif; ?></small>
                <?php endif; ?>
                
                <?php if($employee->approval_status === 'rejected' && $employee->rejection_reason): ?>
                    <br><small><?php echo e(__('Reason')); ?>: <?php echo e($employee->rejection_reason); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-user me-2"></i><?php echo e(__('Personal Details')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Employee ID')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employeesId); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Name')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->full_name); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Email')); ?></span>
                            <span class="font-weight-bold text-dark text-break"><?php echo e($employee->email); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Phone')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->phone); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Office Phone 1')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->office_phone_one ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Office Phone 2')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->office_phone_two ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Emergency Number')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->emergency_number ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Date of Birth')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->dob ? \Auth::user()->dateFormat($employee->dob) : __('Not Set')); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Blood Group')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->blood_group ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Gender')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->gender); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Week Off Day')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->week_off_day ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-12 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Address')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->address ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-briefcase me-2"></i><?php echo e(__('Company Details')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Branch')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->branch->name ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Department')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->department->name ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Designation')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->designation->name ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Work Location')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->work_location ?? 'Pune'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Date of Joining')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->company_doj ? \Auth::user()->dateFormat($employee->company_doj) : __('Not Set')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-credit-card me-2"></i><?php echo e(__('Bank Account Details')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Account Holder Name')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->account_holder_name ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Account Number')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->account_number ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Bank Name')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->bank_name ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('IFSC Code')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->bank_identifier_code ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Branch Location')); ?></span>
                            <span class="font-weight-bold text-dark"><?php echo e($employee->branch_location ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="text-muted text-xs d-block"><?php echo e(__('Account Type')); ?></span>
                            <span class="font-weight-bold text-dark">
                                <?php if(!empty($employee->account_type)): ?>
                                    <?php if($employee->account_type == 'salary_account'): ?>
                                        <?php echo e(__('Salary Account')); ?>

                                    <?php elseif($employee->account_type == 'savings_account'): ?>
                                        <?php echo e(__('Savings Account')); ?>

                                    <?php elseif($employee->account_type == 'Salary account' || $employee->account_type == 'Saving account'): ?>
                                        <?php echo e($employee->account_type); ?>

                                    <?php else: ?>
                                        <?php echo e(ucfirst(str_replace(['_', '-'], ' ', $employee->account_type))); ?>

                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-file-description me-2"></i><?php echo e(__('Document Detail')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                            $employeedoc = $employee->documents()->pluck('document_value', 'document_id');
                        ?>
                        <?php if(!$documents->isEmpty()): ?>
                            <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-12 mb-2">
                                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-2 border-bottom border-light gap-2">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-sm bg-light-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="ti ti-file-text text-primary"></i>
                                            </div>
                                            <span class="text-dark font-weight-bold"><?php echo e($document->name); ?></span>
                                        </div>
                                        <div>
                                            <?php if(!empty($employeedoc[$document->id])): ?>
                                                <a href="<?php echo e(\App\Models\Utility::get_file($employeedoc[$document->id])); ?>" 
                                                    class="btn btn-sm btn-info d-inline-flex align-items-center mt-1 mt-sm-0 me-1 view-document-modal" 
                                                    data-title="<?php echo e($document->name); ?>">
                                                    <i class="ti ti-eye me-1"></i> <?php echo e(__('View')); ?>

                                                </a>
                                                <a href="javascript:void(0)" 
                                                    onclick="downloadFileBackground('<?php echo e(\App\Models\Utility::get_file($employeedoc[$document->id])); ?>')"
                                                    class="btn btn-sm btn-primary d-inline-flex align-items-center mt-1 mt-sm-0">
                                                    <i class="ti ti-download me-1"></i> <?php echo e(__('Download')); ?>

                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light-warning text-warning text-xs"><?php echo e(__('Not Uploaded')); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-3">
                                <p class="text-muted mb-0"><?php echo e(__('No Document Type Added!')); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-history me-2"></i><?php echo e(__('Experience Detail')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-flush border-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0"><?php echo e(__('Company Name')); ?></th>
                                    <th class="border-0"><?php echo e(__('Designation')); ?></th>
                                    <th class="border-0"><?php echo e(__('Start Date')); ?></th>
                                    <th class="border-0"><?php echo e(__('End Date')); ?></th>
                                    <th class="border-0"><?php echo e(__('Previous Salary')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($experienceDetails)): ?>
                                    <?php $__currentLoopData = $experienceDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark"><?php echo e($exp['previous_company_name'] ?? '-'); ?></td>
                                            <td><?php echo e($exp['previous_designation'] ?? '-'); ?></td>
                                            <td><?php echo e($exp['start_date'] ?? '-'); ?></td>
                                            <td><?php echo e($exp['end_date'] ?? '-'); ?></td>
                                            <td><?php echo e($exp['previous_salary'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted"><?php echo e(__('No experience detail available.')); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-school me-2"></i><?php echo e(__('Education Details')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-flush border-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0"><?php echo e(__('Degree')); ?></th>
                                    <th class="border-0"><?php echo e(__('College Name')); ?></th>
                                    <th class="border-0"><?php echo e(__('Passing Year')); ?></th>
                                    <th class="border-0"><?php echo e(__('Grade')); ?></th>
                                    <th class="border-0 text-end"><?php echo e(__('Action')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($educationDetails)): ?>
                                    <?php $__currentLoopData = $educationDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark"><?php echo e($edu['degree'] ?? '-'); ?></td>
                                            <td><?php echo e($edu['college_name'] ?? '-'); ?></td>
                                            <td><?php echo e($edu['passing_year'] ?? '-'); ?></td>
                                            <td><?php echo e($edu['grade'] ?? '-'); ?></td>
                                            <td class="text-end">
                                                <?php if(!empty($edu['document_path'])): ?>
                                                    <a href="<?php echo e(\App\Models\Utility::get_file($edu['document_path'])); ?>" 
                                                       class="btn btn-sm btn-info me-1 view-document-modal" 
                                                       data-title="<?php echo e($edu['degree'] ?? __('Education Document')); ?>">
                                                        <i class="ti ti-eye me-1"></i> <?php echo e(__('View')); ?>

                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="downloadFileBackground('<?php echo e(\App\Models\Utility::get_file($edu['document_path'])); ?>')"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="ti ti-download me-1"></i> <?php echo e(__('Download')); ?>

                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted text-xs"><?php echo e(__('No File')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted"><?php echo e(__('No education details available.')); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="row">
        <!-- Company Policy Acknowledgements Section -->
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-file-certificate me-2"></i><?php echo e(__('Company Policy Acknowledgement Status')); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo e(__('Policy Title')); ?></th>
                                    <th><?php echo e(__('Attachment')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <?php if(\Auth::user()->id == $employee->user_id): ?>
                                        <th><?php echo e(__('Action')); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $companyPolicy; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $policyPath = \App\Models\Utility::get_file('uploads/companyPolicy');
                                        $ack = $policyAcknowledgements->get($policy->id);
                                        $isAcknowledged = $ack ? $ack->acknowledged_at : false;
                                        $hasPreviewed = $ack ? $ack->has_previewed : false;
                                        $hasDownloaded = $ack ? $ack->has_downloaded : false;
                                    ?>
                                    <tr>
                                        <td class="fw-medium text-dark"><?php echo e($policy->title); ?></td>
                                        <td>
                                            <?php if($policy->attachment): ?>
                                                <div class="d-flex gap-2">
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="<?php echo e(route('company-policy.employee.download', $policy->id)); ?>" class="mx-3 btn btn-sm align-items-center track-policy-show" data-id="<?php echo e($policy->id); ?>">
                                                            <i class="ti ti-download text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn bg-secondary ms-2">
                                                        <a href="<?php echo e(route('company-policy.employee.stream', $policy->id)); ?>" class="mx-3 btn btn-sm align-items-center track-policy-show view-document-modal" data-id="<?php echo e($policy->id); ?>" data-title="<?php echo e($policy->title); ?>">
                                                            <i class="ti ti-crosshair text-white"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo e(__('No File')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($isAcknowledged): ?>
                                                <span class="badge bg-success p-2 px-3 rounded"><i class="ti ti-check"></i> <?php echo e(__('Acknowledged on')); ?> <?php echo e(\Auth::user()->dateFormat($isAcknowledged)); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger p-2 px-3 rounded status-badge-<?php echo e($policy->id); ?>"><i class="ti ti-x"></i> <?php echo e(__('Not Acknowledged')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if(\Auth::user()->id == $employee->user_id): ?>
                                            <td>
                                                <?php if(!$isAcknowledged): ?>
                                                    <button type="button" class="btn btn-sm btn-primary acknowledge-policy-show-btn" data-id="<?php echo e($policy->id); ?>" <?php echo e(($hasPreviewed || $hasDownloaded) ? '' : 'disabled'); ?>>
                                                        <?php echo e(__('Acknowledgement')); ?>

                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if($companyPolicy->isEmpty()): ?>
                                    <tr>
                                        <td colspan="<?php echo e(\Auth::user()->id == $employee->user_id ? 4 : 3); ?>" class="text-center text-muted"><?php echo e(__('No company policies available.')); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <?php if(\Auth::user()->type !== 'employee'): ?>
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel"><?php echo e(__('Approve Employee Details')); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo e(__('Are you sure you want to approve this employee\'s details?')); ?></p>
                        <p><?php echo e(__('Once approved, the employee will not be able to edit their information.')); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <form action="<?php echo e(route('employee.approve', $employee->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-success"><?php echo e(__('Approve')); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel"><?php echo e(__('Reject Employee Details')); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?php echo e(route('employee.reject', $employee->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="modal-body">
                            <p><?php echo e(__('Please provide a reason for rejecting this employee\'s details:')); ?></p>
                            <div class="form-group">
                                <textarea name="rejection_reason" class="form-control" rows="3" required 
                                          placeholder="<?php echo e(__('Enter rejection reason...')); ?>"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                            <button type="submit" class="btn btn-danger"><?php echo e(__('Reject')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script-page'); ?>
<script>
    /**
     * Downloads a file in the background using a hidden iframe.
     * This avoids opening new tabs/windows which can cause crashes in APKs/PWAs.
     */
    function downloadFileBackground(url) {
        // Show a small loader or toast if needed
        if (typeof show_toastr === 'function') {
            show_toastr('Info', '<?php echo e(__("Preparing your download...")); ?>', 'info');
        }

        // Create a hidden iframe that is still rendered by the browser
        // This is necessary for html2canvas (used in PDF generation) to work properly
        var a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // Company Policy Scripts for Show Page
    $(document).on('click', '.track-policy-show', function() {
        var policyId = $(this).data('id');
        
        // Allow the download/preview to happen natively via target="_blank"
        setTimeout(function() {
            $.ajax({
                url: '<?php echo e(route("company-policy.employee.track-download", "")); ?>/' + policyId,
                type: 'POST',
                data: {
                    "_token": "<?php echo e(csrf_token()); ?>",
                },
                success: function (data) {
                    if(data.success) {
                        // Enable the acknowledge button
                        $('.acknowledge-policy-show-btn[data-id="' + policyId + '"]').removeAttr('disabled');
                    }
                }
            });
        }, 1000);
    });

    $(document).on('click', '.acknowledge-policy-show-btn', function() {
        var policyId = $(this).data('id');
        var btn = $(this);
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo e(route("company-policy.employee.acknowledge", "")); ?>/' + policyId,
            type: 'POST',
            data: {
                "_token": "<?php echo e(csrf_token()); ?>",
            },
            success: function (data) {
                if(data.success) {
                    show_toastr('Success', data.message, 'success');
                    btn.hide();
                    $('.status-badge-' + policyId).replaceWith('<span class="badge bg-success p-2 px-3 rounded"><i class="ti ti-check"></i> ' + '<?php echo e(__('Acknowledged')); ?>' + '</span>');
                } else {
                    show_toastr('Error', data.message, 'error');
                    btn.html('<?php echo e(__('Acknowledgement')); ?>').prop('disabled', false);
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    msg = xhr.status + ' ' + xhr.statusText;
                }
                show_toastr('Error', msg, 'error');
                btn.html('<?php echo e(__('Acknowledgement')); ?>').prop('disabled', false);
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/employee/show.blade.php ENDPATH**/ ?>