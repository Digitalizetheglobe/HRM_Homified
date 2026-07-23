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

@if (\Auth::user()->isEmployeeInHR())
    <div class="modal-footer">
        @if(strtolower($leave->status) == 'pending')
            <input type="submit" value="{{ __('Approved') }}" class="btn btn-success rounded" name="status" id="approve-btn">
            <input type="submit" value="{{ __('Reject') }}" class="btn btn-danger rounded" name="status">
        @else
            <p class="text-muted mb-0">{{ __('Leave status: ') . $leave->status }}</p>
        @endif
    </div>
@else
    <div class="modal-footer">
        <p class="text-muted mb-0">{{ __('Only employees in the Human Resource department can change leave request status.') }}</p>
    </div>
@endif

{{ Form::close() }}

@if($isCompanyUser && $directors->count() > 0)
<script>
    function showForwardSection() {
        const forwardSection = document.getElementById('forward-section');
        const directorSelect = document.getElementById('director_id');
        
        if (forwardSection) {
            forwardSection.style.display = 'block';
            // Hide the forward button
            const forwardBtn = document.getElementById('forward-btn');
            if (forwardBtn) {
                forwardBtn.style.display = 'none';
            }
            // Focus on the director select dropdown
            if (directorSelect) {
                setTimeout(() => directorSelect.focus(), 100);
            }
            // Scroll to the forward section
            forwardSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Handle form submission to ensure director_id is included
    document.getElementById('leave-action-form').addEventListener('submit', function(e) {
        const forwardSection = document.getElementById('forward-section');
        const directorSelect = document.getElementById('director_id');
        const statusInput = document.querySelector('input[name="status"]');
        
        // If forward section is visible and approve is clicked
        if (forwardSection && forwardSection.style.display !== 'none' && statusInput && statusInput.value === 'Approved') {
            const directorId = directorSelect ? directorSelect.value : '';
            
            // Ensure director_id is submitted (even if empty)
            if (directorSelect && !directorSelect.disabled) {
                // Field is already in form, will be submitted
            }
        }
    });
</script>
@endif
