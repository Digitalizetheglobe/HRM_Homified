@php
    $userType = strtolower(Auth::user()->type);
    $isCompanyUser = $userType == 'company';
    $isDirectorUser = $userType == 'director';
    $isHrUser = $userType == 'hr';
    $isForwardedToUser = $leave->forwarded_to_director_id && $leave->forwarded_to_director_id == Auth::id();
    $isCompanyApproved = $leave->company_approved;
    $leaveStatus = strtolower($leave->status ?? '');
    $isPending = $leaveStatus == 'pending';
    $directors = [];
    // Enable forwarding for ALL leave types (Company User)
    if ($isCompanyUser) {
        // Get both Directors and HR users for forwarding
        $directors = \App\Models\User::where(function($query) {
                $query->where('type', 'director')
                      ->orWhere('type', 'Director')
                      ->orWhere('type', 'hr')
                      ->orWhere('type', 'HR');
            })
            ->where('created_by', Auth::user()->creatorId())
            ->get();
    }

    // Build list of dates for selection
    $startDateObj = \Carbon\Carbon::parse($leave->start_date);
    $endDateObj = \Carbon\Carbon::parse($leave->end_date);
    $leaveDateList = [];
    $currDate = $startDateObj->copy();
    while ($currDate->lte($endDateObj)) {
        $leaveDateList[] = [
            'date_str' => $currDate->format('Y-m-d'),
            'day_num'  => $currDate->format('d'),
            'month'    => $currDate->format('M'),
            'day_name' => $currDate->format('D'),
            'formatted'=> \Auth::user()->dateFormat($currDate->format('Y-m-d')),
        ];
        $currDate->addDay();
    }
    $totalDaysCount = count($leaveDateList);
@endphp

{{ Form::open(['url' => 'leave/changeaction', 'method' => 'post', 'id' => 'leave-action-form']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <table class="table modal-table" id="pc-dt-simple">
                <tr role="row">
                    <th>{{ __('Employee') }}</th>
                    <td>{{ !empty($employee->full_name) ? $employee->full_name : '' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Leave Type ') }}</th>
                    <td>{{ !empty($leavetype->title) ? $leavetype->title : '' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Appplied On') }}</th>
                    <td>{{ \Auth::user()->dateFormat($leave->applied_on) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Start Date') }}</th>
                    <td>{{ \Auth::user()->dateFormat($leave->start_date) }}</td>
                </tr>
                <tr>
                    <th>{{ __('End Date') }}</th>
                    <td>{{ \Auth::user()->dateFormat($leave->end_date) }}</td>
                </tr>
                <tr>
                    <th>{{ __('Leave Reason') }}</th>
                    <td>{{ !empty($leave->leave_reason) ? $leave->leave_reason : '' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Status') }}</th>
                    <td>{{ !empty($leave->status) ? $leave->status : '' }}</td>
                </tr>
                @if($leave->forwarded_to_director_id)
                    <tr>
                        <th>{{ __('Forwarded To') }}</th>
                        <td>{{ $leave->forwardedToDirector->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Company Approved') }}</th>
                        <td>{{ $leave->company_approved ? 'Yes' : 'No' }}</td>
                    </tr>
                @endif

                <input type="hidden" value="{{ $leave->id }}" name="leave_id">  
                <input type="hidden" value="{{ $leave->status }}" name="previous_status">
            </table>
        </div>
    </div>
    
    {{-- Interactive Date Selection Section --}}
    @if($isPending)
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border shadow-none" style="background-color: #f8f9fa; border-radius: 8px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid #ebedf0; border-radius: 8px 8px 0 0;">
                    <h6 class="mb-0 font-weight-bold" style="color: #333; font-size: 14px;">
                        <i class="fas fa-calendar-check text-primary me-2"></i>{{ __('Select Dates to Approve') }}
                    </h6>
                    <div>
                        <button type="button" class="btn btn-xs btn-outline-primary me-1" id="btn-select-all-dates" style="font-size: 11px; padding: 2px 8px;">
                            {{ __('Select All') }}
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-deselect-all-dates" style="font-size: 11px; padding: 2px 8px;">
                            {{ __('Deselect All') }}
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap justify-content-start align-items-center" id="date-checkbox-container" style="gap: 8px;">
                        @foreach($leaveDateList as $idx => $dItem)
                            <div class="date-select-pill">
                                <input type="checkbox" 
                                       name="selected_dates[]" 
                                       value="{{ $dItem['date_str'] }}" 
                                       id="date_chk_{{ $idx }}" 
                                       class="btn-check date-approval-checkbox" 
                                       checked 
                                       autocomplete="off">
                                <label class="btn btn-outline-success btn-sm d-flex flex-column align-items-center justify-content-center p-2 date-pill-label" 
                                       for="date_chk_{{ $idx }}" 
                                       style="min-width: 65px; border-radius: 8px; font-size: 12px; transition: all 0.2s ease-in-out; cursor: pointer;">
                                    <span class="fw-bold fs-6">{{ $dItem['day_num'] }}</span>
                                    <span class="small text-uppercase" style="font-size: 10px;">{{ $dItem['month'] }} ({{ $dItem['day_name'] }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="badge" id="selected-dates-count-badge" style="font-size: 12px; font-weight: 600; padding: 6px 12px; background-color: #e0f2fe; color: #0369a1; border-radius: 6px;">
                            <i class="fas fa-check-circle me-1"></i>Approved Days: <strong id="selected-count">{{ $totalDaysCount }}</strong> of {{ $totalDaysCount }} selected
                        </span>
                        <small class="text-muted" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i>Unselected dates will not be approved
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Forwarding Section for ALL leave types (Company User) --}}
    @if($isCompanyUser && $isPending && !$leave->forwarded_to_director_id)
        <div class="row mt-3" id="forward-section" style="display: none;">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6>{{ __('Forward to Director/HR (Optional)') }}</h6>
                    <div class="form-group">
                        <label for="director_id">{{ __('Select Director/HR') }}</label>
                        <select name="director_id" id="director_id" class="form-control">
                            <option value="">{{ __('Do not forward (Approve directly)') }}</option>
                            @foreach($directors as $director)
                                <option value="{{ $director->id }}">{{ $director->name }} ({{ ucfirst($director->type) }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if ($isCompanyUser || $isDirectorUser || $isHrUser || \Auth::user()->isEmployeeInHR() || \Auth::user()->can('leave.manage.action.all'))
    <div class="modal-footer">
        @if(strtolower($leave->status) == 'pending')
            @if($isCompanyUser && count($directors) > 0 && !$leave->forwarded_to_director_id)
                <button type="button" class="btn btn-warning rounded me-auto" id="forward-btn" onclick="showForwardSection()">
                    <i class="ti ti-arrow-forward me-1"></i>{{ __('Forward to Director/HR') }}
                </button>
            @endif
            <input type="submit" value="{{ __('Approved') }}" class="btn btn-success rounded" name="status" id="approve-btn">
            <input type="submit" value="{{ __('Reject') }}" class="btn btn-danger rounded" name="status">
        @else
            <p class="text-muted mb-0">{{ __('Leave status: ') . $leave->status }}</p>
        @endif
    </div>
@else
    <div class="modal-footer">
        <p class="text-muted mb-0">{{ __('Only company administrators or HR department members can change leave request status.') }}</p>
    </div>
@endif

{{ Form::close() }}

<script>
    function showForwardSection() {
        const forwardSection = document.getElementById('forward-section');
        const directorSelect = document.getElementById('director_id');
        
        if (forwardSection) {
            forwardSection.style.display = 'block';
            const forwardBtn = document.getElementById('forward-btn');
            if (forwardBtn) {
                forwardBtn.style.display = 'none';
            }
            if (directorSelect) {
                setTimeout(() => directorSelect.focus(), 100);
            }
            forwardSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const dateCheckboxes = document.querySelectorAll('.date-approval-checkbox');
        const selectedCountEl = document.getElementById('selected-count');
        const selectAllBtn = document.getElementById('btn-select-all-dates');
        const deselectAllBtn = document.getElementById('btn-deselect-all-dates');

        function updateSelectedCount() {
            let count = 0;
            dateCheckboxes.forEach(cb => {
                if (cb.checked) count++;
            });
            if (selectedCountEl) {
                selectedCountEl.textContent = count;
            }
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                dateCheckboxes.forEach(cb => cb.checked = true);
                updateSelectedCount();
            });
        }

        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                dateCheckboxes.forEach(cb => cb.checked = false);
                updateSelectedCount();
            });
        }

        dateCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });

        const form = document.getElementById('leave-action-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitter = e.submitter;
                if (submitter && submitter.value === 'Approved') {
                    let checkedCount = 0;
                    dateCheckboxes.forEach(cb => { if (cb.checked) checkedCount++; });
                    if (checkedCount === 0) {
                        e.preventDefault();
                        alert('Please select at least 1 date to approve, or click Reject to reject the entire leave request.');
                    }
                }
            });
        }
    });
</script>
