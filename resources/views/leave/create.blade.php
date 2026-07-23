@php
    $setting = App\Models\Utility::settings();
    $plan = Utility::getChatGPTSettings();
    $compOffBalance = $compOffBalance ?? 0; // Default value if not defined
@endphp
{{ Form::open(['url' => 'leave', 'method' => 'post']) }}
<div class="modal-body">

    

    @if (\Auth::user()->type != 'employee' || (\Auth::user()->can('leave.manage.create.all') && !request()->has('own')))
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}
                    {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select2', 'id' => 'employee_id', 'placeholder' => __('Select Employee')]) }}
                </div>
            </div>
        </div>
    @else
        {!! Form::hidden('employee_id', !empty($employees) ? $employees->id : 0, ['id' => 'employee_id']) !!}
    @endif

    {{-- Step 1: Leave Type Selection (All Leave Types in One Dropdown) --}}
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_type_select', __('Leave Type'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                <select name="leave_type_select" id="leave_type_select" class="form-control select" required>
                    <option value="">{{ __('Select Leave Type') }}</option>
                    <optgroup label="{{ __('Regular Leaves') }}">
                        <option value="earned_leave" data-requires-duration="1">{{ __('Earned Leave') }}</option>
                        <option value="sick_leave" data-requires-duration="1">{{ __('Sick Leave') }}</option>
                    </optgroup>
                    @if(count($leavetypes) > 0)
                        <optgroup label="{{ __('Special Leave Types') }}">
                            @php
                                $compOffFound = false;
                            @endphp
                            @foreach ($leavetypes as $leave)
                                @if ($leave->title === 'Comp-Off')
                                    @php $compOffFound = true; @endphp
                                    <option value="special_{{ $leave->id }}" 
                                            data-requires-duration="0"
                                            data-is-comp-off="1"
                                            data-is-casual="0"
                                            data-balance="{{ $compOffBalance }}"
                                            data-comp-off-id="{{ $leave->id }}"
                                            class="comp-off-option">
                                        {{ $leave->title }} (0/{{ $compOffBalance }})
                                    </option>
                                @elseif ($leave->title === 'Leave Without Pay')
                                    <option value="special_{{ $leave->id }}" 
                                            data-requires-duration="0"
                                            data-is-casual="1">
                                        {{ $leave->title }} (Unlimited)
                                    </option>
                                @else
                                    <option value="special_{{ $leave->id }}"
                                            data-requires-duration="0"
                                            data-is-casual="0">
                                        {{ $leave->title }}
                                        @if($leave->days > 0)
                                            ({{ $leave->getUsedLeaves(Auth::user()->type == 'employee' ? $employeeId : null) }}/{{ $leave->days }})
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                            
                            {{-- If Comp-Off leave type doesn't exist in database, add it manually --}}
                            @if(!$compOffFound && isset($compOffLeaveTypeId) && $compOffLeaveTypeId)
                                <option value="special_{{ $compOffLeaveTypeId }}" 
                                        data-requires-duration="0"
                                        data-is-comp-off="1"
                                        data-is-casual="0"
                                        data-balance="{{ $compOffBalance }}"
                                        data-comp-off-id="{{ $compOffLeaveTypeId }}"
                                        class="comp-off-option">
                                    Comp-Off (0/{{ $compOffBalance }})
                                </option>
                            @endif
                        </optgroup>
                    @endif
                </select>
                <small class="form-text text-muted">{{ __('Select a leave type') }}</small>
            </div>
        </div>
    </div>

    {{-- Step 2: Leave Duration Selection (Full Day / Half Day) - Hidden until Regular Leave Type is selected --}}
    <div class="row" id="duration-selection-row" style="display: none;">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_duration_type', __('Leave Duration'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                <select name="leave_duration_type" id="leave_duration_type" class="form-control select">
                    <option value="">{{ __('Select Duration') }}</option>
                    <option value="full_day">{{ __('Full Day') }}</option>
                    <option value="half_day">{{ __('Half Day') }}</option>
                </select>
                <small class="form-text text-muted">{{ __('Select whether you want to take a full day or half day leave') }}</small>
            </div>
        </div>
    </div>

    {{-- Full Day Leave Fields --}}
    <div id="full-day-fields" style="display: none;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('start_date', __('From Date'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                    {{ Form::text('start_date', null, ['class' => 'form-control d_week current_date', 'autocomplete' => 'off', 'id' => 'start_date']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('end_date', __('To Date'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                    {{ Form::text('end_date', null, ['class' => 'form-control d_week current_date', 'autocomplete' => 'off', 'id' => 'end_date']) }}
                </div>
            </div>
        </div>
    </div>
    
    {{-- Half Day Leave Fields --}}
    <div id="half-day-fields" style="display: none;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('leave_date', __('Leave Date'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                    {{ Form::text('leave_date', null, ['class' => 'form-control d_week current_date', 'autocomplete' => 'off', 'id' => 'leave_date']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('half_day_session', __('Session'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                    <select name="half_day_session" id="half_day_session" class="form-control select">
                        <option value="">{{ __('Select Session') }}</option>
                        <option value="first_half">{{ __('First Half') }}</option>
                        <option value="second_half">{{ __('Second Half') }}</option>
                    </select>
                    <small class="form-text text-muted">{{ __('Select whether you want to take leave in the first half or second half of the day') }}</small>
                </div>
            </div>
        </div>
    </div>


    {{-- Leave Reason --}}
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('leave_reason', __('Reason for Leave'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                {{ Form::textarea('leave_reason', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Reason for Leave'), 'rows' => '3']) }}
            </div>
        </div>
    </div>

    {{-- Google Calendar Sync --}}
    @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
        <div class="form-group col-md-6">
            {{ Form::label('synchronize_type', __('Synchronize in Google Calendar?'), ['class' => 'form-label']) }}
            <div class="form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow" value="google_calendar">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
    @endif
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary" id="submit-btn">
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        // Set current date
        var now = new Date();
        var month = (now.getMonth() + 1).toString().padStart(2, '0');
        var day = now.getDate().toString().padStart(2, '0');
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').val(today);
        
        // Hide all fields initially
        $('#duration-selection-row').hide();
        $('#full-day-fields').hide();
        $('#half-day-fields').hide();
        
        // Handle Leave Type selection (All leave types in one dropdown)
        $('#leave_type_select').on('change', function() {
            var leaveType = $(this).val();
            var selectedOption = $(this).find('option:selected');
            var requiresDuration = selectedOption.data('requires-duration') == 1;
            var isSpecialLeave = leaveType && leaveType.indexOf('special_') === 0;
            
            // Reset duration and date fields
            $('#leave_duration_type').val('').trigger('change');
            $('#duration-selection-row').hide();
            $('#full-day-fields').hide();
            $('#half-day-fields').hide();
            
            // Clear required attributes
            $('#leave_duration_type, #start_date, #end_date, #leave_date, #half_day_session').removeAttr('required');
            
            if (requiresDuration) {
                // Regular leave types (Earned Leave or Sick Leave) - show duration selection
                $('#duration-selection-row').show();
                $('#leave_duration_type').attr('required', 'required');
            } else if (isSpecialLeave) {
                // Special leave types - show full day fields directly
                $('#full-day-fields').show();
                var isCasualLeave = selectedOption.data('is-casual') == 1;
                
                if (isCasualLeave) {
                    // Dates are optional for Leave Without Pay
                    $('#start_date, #end_date').removeAttr('required');
                } else {
                    // Dates are required for other special leave types
                    $('#start_date, #end_date').attr('required', 'required');
                }
            }
        });
        
        // Handle Leave Duration selection (Full Day / Half Day)
        $('#leave_duration_type').on('change', function() {
            var durationType = $(this).val();
            
            // Hide all date fields first
            $('#full-day-fields').hide();
            $('#half-day-fields').hide();
            
            // Clear required attributes
            $('#start_date, #end_date, #leave_date, #half_day_session').removeAttr('required');
            
            if (durationType === 'full_day') {
                // Show full day fields
                $('#full-day-fields').show();
                $('#start_date, #end_date').attr('required', 'required');
            } else if (durationType === 'half_day') {
                // Show half day fields
                $('#half-day-fields').show();
                $('#leave_date, #half_day_session').attr('required', 'required');
                
                // Set end_date to same as leave_date for half day
                $('#leave_date').on('change', function() {
                    var leaveDate = $(this).val();
                    if (!$('#end_date').length || $('#end_date').attr('type') !== 'hidden') {
                        if ($('#end_date').length) {
                            $('#end_date').remove();
                        }
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'end_date',
                            id: 'end_date',
                            value: leaveDate
                        }).appendTo('form');
                    } else {
                        $('#end_date').val(leaveDate);
                    }
                });
            }
        });
        
        // Update Comp-Off option visibility
        function updateCompOffOption(employeeId) {
            var compOffOption = $('#leave_type_select option[data-is-comp-off="1"]');
            
            if (!employeeId) {
                @if(\Auth::user()->type != 'employee')
                    compOffOption.hide();
                @endif
                return;
            }
            
            $.ajax({
                url: '{{ url("/get-comp-off-balance") }}/' + employeeId,
                type: 'GET',
                success: function(data) {
                    var balance = data.balance || 0;
                    
                    if (compOffOption.length > 0) {
                        compOffOption.attr('data-balance', balance);
                        compOffOption.text('Comp-Off (0/' + balance + ')');
                        
                        @if(\Auth::user()->type == 'employee')
                            if (balance > 0) {
                                compOffOption.show();
                            } else {
                                compOffOption.hide();
                            }
                        @else
                            compOffOption.show();
                        @endif
                    }
                },
                error: function() {
                    @if(\Auth::user()->type == 'employee')
                        compOffOption.hide();
                    @endif
                }
            });
        }
        
        // Update Comp-Off option when employee changes (for admin users)
        @if(\Auth::user()->type != 'employee')
            $('#employee_id').on('change', function() {
                var employeeId = $(this).val();
                updateCompOffOption(employeeId);
            });
        @endif
        
        // Initialize Comp-Off option visibility
        var initialEmployeeId = $('#employee_id').val();
        if (initialEmployeeId) {
            updateCompOffOption(initialEmployeeId);
        } else {
            @if(\Auth::user()->type == 'employee')
                // For employees, check if Comp-Off option exists and show it if balance > 0
                var compOffOption = $('#leave_type_select option[data-is-comp-off="1"]');
                var initialBalance = compOffOption.attr('data-balance') || 0;
                if (parseInt(initialBalance) > 0) {
                    compOffOption.show();
                }
            @endif
        }
    });
</script>
