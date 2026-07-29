@extends('layouts.admin')

@section('page-title')
    {{ __('Employee') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Manage Employee') }}</li>
@endsection

@section('action-button')
    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">

        {{-- Show Edit button: Only employees can edit themselves if not approved --}}
        @if(\Auth::user()->type === 'employee' && \Auth::user()->id === $employee->user_id && $employee->approval_status !== 'approved')
            {{-- Employees can only edit themselves if not approved --}}
            <a href="{{ route('employee.edit', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i> {{ __('Edit') }}
            </a>
        @endif
        
        {{-- Show approval buttons for company users --}}
        @if(\Auth::user()->type !== 'employee' && ($employee->approval_status === 'pending' || empty($employee->approval_status)))
            <button type="button" class="btn btn-sm btn-success" 
                data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="ti ti-check"></i> {{ __('Approve') }}
            </button>
            
            <button type="button" class="btn btn-sm btn-danger" 
                data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="ti ti-x"></i> {{ __('Reject') }}
            </button>
        @endif
        
        {{-- Show request approval button for employees when rejected --}}
        @if(\Auth::user()->type === 'employee' && $employee->approval_status === 'rejected')
            <form action="{{ route('employee.request-approval', $employee->id) }}" method="POST" class="d-inline-block">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="ti ti-refresh"></i> {{ __('Request Approval Again') }}
                </button>
            </form>
        @endif
        
        {{-- Offer Letter and Experience Certificate direct download buttons --}}
        <a href="javascript:void(0)" 
            onclick="downloadFileBackground('{{ route('joiningletter.download.pdf', $employee->id) }}')"
            class="btn btn-sm btn-info">
            <i class="ti ti-download"></i> {{ __('Offer Letter') }}
        </a>

        <a href="javascript:void(0)" 
            onclick="downloadFileBackground('{{ route('exp.download.pdf', $employee->id) }}')"
            class="btn btn-sm btn-info">
            <i class="ti ti-download"></i> {{ __('Experience Certificate') }}
        </a>
    </div>
@endsection

@push('css-page')
    <style>
        /* =============================================
           HRM Employee Show – Premium UI
           Primary colour: var(--color-customColor)
        ============================================= */

        /* ── Card ──────────────────────────────────── */
        .card {
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
            overflow: hidden;
        }
        .card .card-header {
            border-left: 4px solid var(--color-customColor, #c9a227);
            background: linear-gradient(to right, rgba(201,162,39,0.08) 0%, #fff 55%);
            padding: 12px 18px;
            border-bottom: 1px solid #ebebeb;
        }
        .card .card-header h5,
        .card .card-header h6 {
            font-weight: 600;
            margin-bottom: 0;
            color: #1a202c;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card .card-header .ti {
            color: var(--color-customColor, #c9a227);
        }

        /* ── Info Row Labels ─────────────────────────── */
        .info-row-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            display: block;
            margin-bottom: 2px;
        }
        .info-row-value {
            font-weight: 600;
            font-size: 0.875rem;
            color: #111827;
            display: block;
        }

        /* ── Approval Alert ──────────────────────────── */
        .alert { border-radius: 10px; font-size: 0.875rem; }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #ecfdf5);
            color: #065f46; border-color: #a7f3d0;
        }
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            color: #92400e; border-color: #fcd34d;
        }
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fef2f2);
            color: #991b1b; border-color: #fca5a5;
        }

        /* ── Tables ──────────────────────────────────── */
        .table thead th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        .table tbody td {
            font-size: 0.875rem;
            vertical-align: middle;
        }

        /* ── Badge ───────────────────────────────────── */
        .badge { border-radius: 6px; font-weight: 500; font-size: 0.78rem; }
    </style>
@endpush

@section('content')
    {{-- Approval Status Alert --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-@if($employee->approval_status === 'approved')success
                                @elseif($employee->approval_status === 'rejected')danger
                                @elsewarning @endif">
                <i class="ti ti-shield-check me-1"></i><strong>{{ __('Approval Status') }}:</strong> 
                {{ ucfirst($employee->approval_status ?? 'pending') }}
                
                @if($employee->approval_status === 'approved' && $employee->approved_at)
                    <br><small>{{ __('Approved on') }}: {{ \Auth::user()->dateFormat($employee->approved_at) }} 
                    @if($employee->approvedBy) by {{ $employee->approvedBy->name }} @endif</small>
                @endif
                
                @if($employee->approval_status === 'rejected' && $employee->rejection_reason)
                    <br><small>{{ __('Reason') }}: {{ $employee->rejection_reason }}</small>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-user me-2"></i>{{ __('Personal Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Employee ID') }}</span>
                            <span class="info-row-value">{{ $employeesId }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Name') }}</span>
                            <span class="info-row-value">{{ $employee->full_name }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Email') }}</span>
                            <span class="info-row-value text-break">{{ $employee->email }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Phone') }}</span>
                            <span class="info-row-value">{{ $employee->phone }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Office Phone 1') }}</span>
                            <span class="info-row-value">{{ $employee->office_phone_one ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Office Phone 2') }}</span>
                            <span class="info-row-value">{{ $employee->office_phone_two ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Emergency Number') }}</span>
                            <span class="info-row-value">{{ $employee->emergency_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Date of Birth') }}</span>
                            <span class="info-row-value">{{ $employee->dob ? \Auth::user()->dateFormat($employee->dob) : __('Not Set') }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Blood Group') }}</span>
                            <span class="info-row-value">{{ $employee->blood_group ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Gender') }}</span>
                            <span class="info-row-value">{{ $employee->gender }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Week Off Day') }}</span>
                            <span class="info-row-value">{{ $employee->week_off_day ?? 'N/A' }}</span>
                        </div>
                        <div class="col-12 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Address') }}</span>
                            <span class="info-row-value">{{ $employee->address ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-briefcase me-2"></i>{{ __('Company Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Branch') }}</span>
                            <span class="info-row-value">{{ $employee->branch->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Department') }}</span>
                            <span class="info-row-value">{{ $employee->department->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Designation') }}</span>
                            <span class="info-row-value">{{ $employee->designation->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Work Location') }}</span>
                            <span class="info-row-value">{{ $employee->work_location ?? 'Pune' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Date of Joining') }}</span>
                            <span class="info-row-value">{{ $employee->company_doj ? \Auth::user()->dateFormat($employee->company_doj) : __('Not Set') }}</span>
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
                    <h6 class="mb-0 text-primary"><i class="ti ti-credit-card me-2"></i>{{ __('Bank Account Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Account Holder Name') }}</span>
                            <span class="info-row-value">{{ $employee->account_holder_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Account Number') }}</span>
                            <span class="info-row-value">{{ $employee->account_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Bank Name') }}</span>
                            <span class="info-row-value">{{ $employee->bank_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('IFSC Code') }}</span>
                            <span class="info-row-value">{{ $employee->bank_identifier_code ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Branch Location') }}</span>
                            <span class="info-row-value">{{ $employee->branch_location ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 border-bottom border-light pb-2">
                            <span class="info-row-label">{{ __('Account Type') }}</span>
                            <span class="info-row-value">
                                @if(!empty($employee->account_type))
                                    @if($employee->account_type == 'salary_account')
                                        {{ __('Salary Account') }}
                                    @elseif($employee->account_type == 'savings_account')
                                        {{ __('Savings Account') }}
                                    @elseif($employee->account_type == 'Salary account' || $employee->account_type == 'Saving account')
                                        {{ $employee->account_type }}
                                    @else
                                        {{ ucfirst(str_replace(['_', '-'], ' ', $employee->account_type)) }}
                                    @endif
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="card shadow-none border h-100">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-file-description me-2"></i>{{ __('Document Detail') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $employeedoc = $employee->documents()->pluck('document_value', 'document_id');
                        @endphp
                        @if (!$documents->isEmpty())
                            @foreach ($documents as $key => $document)
                                <div class="col-md-12 mb-2">
                                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between p-2 border-bottom border-light gap-2">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-sm bg-light-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="ti ti-file-text text-primary"></i>
                                            </div>
                                            <span class="text-dark font-weight-bold">{{ $document->name }}</span>
                                        </div>
                                        <div>
                                            @if(!empty($employeedoc[$document->id]))
                                                <a href="{{ \App\Models\Utility::get_file($employeedoc[$document->id]) }}" 
                                                    class="btn btn-sm btn-info d-inline-flex align-items-center mt-1 mt-sm-0 me-1 view-document-modal" 
                                                    data-title="{{ $document->name }}">
                                                    <i class="ti ti-eye me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="javascript:void(0)" 
                                                    onclick="downloadFileBackground('{{ \App\Models\Utility::get_file($employeedoc[$document->id]) }}')"
                                                    class="btn btn-sm btn-primary d-inline-flex align-items-center mt-1 mt-sm-0">
                                                    <i class="ti ti-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            @else
                                                <span class="badge bg-light-warning text-warning text-xs">{{ __('Not Uploaded') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12 text-center py-3">
                                <p class="text-muted mb-0">{{ __('No Document Type Added!') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-history me-2"></i>{{ __('Experience Detail') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">{{ __('Company Name') }}</th>
                                    <th class="border-0">{{ __('Designation') }}</th>
                                    <th class="border-0">{{ __('Start Date') }}</th>
                                    <th class="border-0">{{ __('End Date') }}</th>
                                    <th class="border-0">{{ __('Previous Salary') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($experienceDetails))
                                    @foreach($experienceDetails as $exp)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $exp['previous_company_name'] ?? '-' }}</td>
                                            <td>{{ $exp['previous_designation'] ?? '-' }}</td>
                                            <td>{{ $exp['start_date'] ?? '-' }}</td>
                                            <td>{{ $exp['end_date'] ?? '-' }}</td>
                                            <td>{{ $exp['previous_salary'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No experience detail available.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-school me-2"></i>{{ __('Education Details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">{{ __('Degree') }}</th>
                                    <th class="border-0">{{ __('College Name') }}</th>
                                    <th class="border-0">{{ __('Passing Year') }}</th>
                                    <th class="border-0">{{ __('Grade') }}</th>
                                    <th class="border-0 text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($educationDetails))
                                    @foreach($educationDetails as $edu)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $edu['degree'] ?? '-' }}</td>
                                            <td>{{ $edu['college_name'] ?? '-' }}</td>
                                            <td>{{ $edu['passing_year'] ?? '-' }}</td>
                                            <td>{{ $edu['grade'] ?? '-' }}</td>
                                            <td class="text-end">
                                                @if(!empty($edu['document_path']))
                                                    <a href="{{ \App\Models\Utility::get_file($edu['document_path']) }}" 
                                                       class="btn btn-sm btn-info me-1 view-document-modal" 
                                                       data-title="{{ $edu['degree'] ?? __('Education Document') }}">
                                                        <i class="ti ti-eye me-1"></i> {{ __('View') }}
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="downloadFileBackground('{{ \App\Models\Utility::get_file($edu['document_path']) }}')"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="ti ti-download me-1"></i> {{ __('Download') }}
                                                    </a>
                                                @else
                                                    <span class="text-muted text-xs">{{ __('No File') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No education details available.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="row mt-4">
        <!-- Company Policy Acknowledgements Section -->
        <div class="col-xl-12">
            <div class="card shadow-none border">
                <div class="card-header">
                    <h6 class="mb-0 text-primary"><i class="ti ti-file-certificate me-2"></i>{{ __('Company Policy Acknowledgement Status') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Policy Title') }}</th>
                                    <th>{{ __('Attachment') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if(\Auth::user()->id == $employee->user_id)
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companyPolicy as $policy)
                                    @php
                                        $policyPath = \App\Models\Utility::get_file('uploads/companyPolicy');
                                        $ack = $policyAcknowledgements->get($policy->id);
                                        $isAcknowledged = $ack ? $ack->acknowledged_at : false;
                                        $hasPreviewed = $ack ? $ack->has_previewed : false;
                                        $hasDownloaded = $ack ? $ack->has_downloaded : false;
                                    @endphp
                                    <tr>
                                        <td class="fw-medium text-dark">{{ $policy->title }}</td>
                                        <td>
                                            @if($policy->attachment)
                                                <div class="d-flex gap-2">
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('company-policy.employee.download', $policy->id) }}" class="mx-3 btn btn-sm align-items-center track-policy-show" data-id="{{ $policy->id }}">
                                                            <i class="ti ti-download text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn bg-secondary ms-2">
                                                        <a href="{{ route('company-policy.employee.stream', $policy->id) }}" class="mx-3 btn btn-sm align-items-center track-policy-show view-document-modal" data-id="{{ $policy->id }}" data-title="{{ $policy->title }}">
                                                            <i class="ti ti-crosshair text-white"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">{{ __('No File') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isAcknowledged)
                                                <span class="badge bg-success p-2 px-3 rounded"><i class="ti ti-check"></i> {{ __('Acknowledged on') }} {{ \Auth::user()->dateFormat($isAcknowledged) }}</span>
                                            @else
                                                <span class="badge bg-danger p-2 px-3 rounded status-badge-{{ $policy->id }}"><i class="ti ti-x"></i> {{ __('Not Acknowledged') }}</span>
                                            @endif
                                        </td>
                                        @if(\Auth::user()->id == $employee->user_id)
                                            <td>
                                                @if(!$isAcknowledged)
                                                    <button type="button" class="btn btn-sm btn-primary acknowledge-policy-show-btn" data-id="{{ $policy->id }}" {{ ($hasPreviewed || $hasDownloaded) ? '' : 'disabled' }}>
                                                        {{ __('Acknowledgement') }}
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                                @if($companyPolicy->isEmpty())
                                    <tr>
                                        <td colspan="{{ \Auth::user()->id == $employee->user_id ? 4 : 3 }}" class="text-center text-muted">{{ __('No company policies available.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approval Modals --}}
    @if(\Auth::user()->type !== 'employee')
        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">{{ __('Approve Employee Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('Are you sure you want to approve this employee\'s details?') }}</p>
                        <p>{{ __('Once approved, the employee will not be able to edit their information.') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form action="{{ route('employee.approve', $employee->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">{{ __('Approve') }}</button>
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
                        <h5 class="modal-title" id="rejectModalLabel">{{ __('Reject Employee Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('employee.reject', $employee->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>{{ __('Please provide a reason for rejecting this employee\'s details:') }}</p>
                            <div class="form-group">
                                <textarea name="rejection_reason" class="form-control" rows="3" required 
                                          placeholder="{{ __('Enter rejection reason...') }}"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-danger">{{ __('Reject') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script-page')
<script>
    function downloadFileBackground(url) {
        // Show a small loader or toast if needed
        if (typeof show_toastr === 'function') {
            show_toastr('Info', '{{ __("Preparing your download...") }}', 'info');
        }

        // Create a hidden iframe that is still rendered by the browser.
        // This is necessary for html2canvas (used in PDF generation) to layout elements correctly.
        var iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.width = '1px';
        iframe.style.height = '1px';
        iframe.style.opacity = '0.01';
        iframe.style.left = '-9999px';
        iframe.src = url;
        document.body.appendChild(iframe);

        // Remove the iframe after a short delay once generation is complete
        setTimeout(function() {
            if (document.body.contains(iframe)) {
                document.body.removeChild(iframe);
            }
        }, 15000);
    }

    // Company Policy Scripts for Show Page
    $(document).on('click', '.track-policy-show', function() {
        var policyId = $(this).data('id');
        
        // Allow the download/preview to happen natively via target="_blank"
        setTimeout(function() {
            $.ajax({
                url: '{{ route("company-policy.employee.track-download", "") }}/' + policyId,
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
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
            url: '{{ route("company-policy.employee.acknowledge", "") }}/' + policyId,
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
            },
            success: function (data) {
                if(data.success) {
                    show_toastr('Success', data.message, 'success');
                    btn.hide();
                    $('.status-badge-' + policyId).replaceWith('<span class="badge bg-success p-2 px-3 rounded"><i class="ti ti-check"></i> ' + '{{ __('Acknowledged') }}' + '</span>');
                } else {
                    show_toastr('Error', data.message, 'error');
                    btn.html('{{ __('Acknowledgement') }}').prop('disabled', false);
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
                btn.html('{{ __('Acknowledgement') }}').prop('disabled', false);
            }
        });
    });
</script>
@endpush