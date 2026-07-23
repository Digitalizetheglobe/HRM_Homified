{{ Form::open(['route' => ['employee.export_inactive', $employee->id], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <p>{{ __('Select how you want to export the attendance records for') }} <strong>{{ $employee->full_name }}</strong>.</p>
            <p class="text-muted text-sm">
                {{ __('Last Working Date:') }} <strong>{{ \Auth::user()->dateFormat($termination->termination_date) }}</strong>
            </p>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="form-check custom-radio mb-3">
                <input type="radio" id="export_up_to_last" name="export_type" value="up_to_last" class="form-check-input" checked>
                <label class="form-check-label" for="export_up_to_last">
                    <strong>{{ __('Complete History') }}</strong><br>
                    <small class="text-muted">{{ __('Export all attendance records from the start up to the employee\'s last working date.') }}</small>
                </label>
            </div>
            
            <div class="form-check custom-radio">
                <input type="radio" id="export_month_of_last" name="export_type" value="month_of_last" class="form-check-input">
                <label class="form-check-label" for="export_month_of_last">
                    <strong>{{ __('Final Month Only') }}</strong><br>
                    <small class="text-muted">{{ __('Export only the attendance for the month in which the employee\'s last working date falls.') }}</small>
                </label>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Download Excel') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
