{{ Form::open(['route' => ['employee.export'], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <p>{{ __('Select the fields you want to export.') }}</p>
            
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="select_all" checked>
                <label class="form-check-label fw-bold" for="select_all">{{ __('Select All') }}</label>
            </div>
            <hr>
            
            <div class="row">
                @foreach($fields as $key => $label)
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="fields[]" value="{{ $key }}" id="field_{{ $key }}" class="form-check-input export-field" checked>
                            <label class="form-check-label" style="user-select: none;" for="field_{{ $key }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Export'), ['class' => 'btn btn-primary']) }}
</div>
{{ Form::close() }}

<script>
    $(document).ready(function() {
        $('#select_all').on('change', function() {
            $('.export-field').prop('checked', this.checked);
        });
        
        $('.export-field').on('change', function() {
            if ($('.export-field:checked').length === $('.export-field').length) {
                $('#select_all').prop('checked', true);
            } else {
                $('#select_all').prop('checked', false);
            }
        });
    });
</script>
