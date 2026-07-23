@php
    $plan = Utility::getChatGPTSettings();
@endphp

{{ Form::open(['url' => 'ticket', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}

<link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">

<div class="modal-body">

    

    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('title', __('Subject'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
            {{ Form::text('title', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Ticket Subject')]) }}
        </div>
        @if (\Auth::user()->type != 'employee')
            <div class="form-group col-md-6">
                {{ Form::label('employee_id', __('Ticket for Employee'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
                {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select2 employee_id', 'placeholder' => __('Select Employee')]) }}
            </div>
        @else
            {!! Form::hidden('employee_id', !empty($employees) ? $employees->id : 0, ['id' => 'employee_id']) !!}
        @endif

        <div class="form-group col-md-6">
            {{ Form::label('priority', __('Priority'), ['class' => 'col-form-label']) }}<span class="text-danger pl-1">*</span>
            <select name="priority" class="form-control" required>
                <option value="">{{ __('Select Priority') }}</option>
                <option value="low">{{ __('Low') }}</option>
                <option value="medium">{{ __('Medium') }}</option>
                <option value="high">{{ __('High') }}</option>
                <option value="critical">{{ __('Critical') }}</option>
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('end_date', __('End Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('end_date', date('Y-m-d'), ['class' => 'form-control current_date', 'autocomplete' => 'off']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('status', __('Status'), ['class' => 'col-form-label']) }}
            <select name="status" class="form-control" id="status" required>
                <option value="open">{{ __('Open') }}</option>
                <option value="onhold">{{ __('On Hold') }}</option>
                <option value="close">{{ __('Close') }}</option>
            </select>
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'col-form-label']) !!}
            <textarea class="form-control summernote-simple-2" name="description" id="exampleFormControlTextarea1" rows="7"></textarea>
        </div>

        <div class="form-group col-md-6">
            <label class="col-form-label">{{ __('Attachments') }}</label>
            <div class="choose-file">
                <label for="attachment" class="form-label">
                    <input type="file" name="attachment" id="attachment" class="form-control {{ $errors->has('attachment') ? ' is-invalid' : '' }}" onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])" data-filename="attachment_selection">
                    <div class="invalid-feedback">
                        {{ $errors->first('attachment') }}
                    </div>
                </label>
                <p class="attachment_selection"></p>
            </div>
        </div>
        <div class="form-group col-md-6 d-flex justify-content-center align-items-center">
            <img src="" id="blah" style="max-width: 60%; max-height: 150px; object-fit: contain;" />
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">

</div>
{{ Form::close() }}

<script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>

<script>
    $(document).ready(function() {
        var now = new Date();
        var month = (now.getMonth() + 1);
        var day = now.getDate();
        if (month < 10) month = "0" + month;
        if (day < 10) day = "0" + day;
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').val(today);
        
        if ($(".summernote-simple-2").length) {
            $('.summernote-simple-2').summernote({
                dialogsInBody: !0,
                minHeight: 200,
                maxHeight: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                    ['list', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'unlink']],
                ]
            });
        }
    });
</script>
