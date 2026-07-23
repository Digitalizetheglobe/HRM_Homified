@php
    $plan = Utility::getChatGPTSettings();
    $attachmentPath = \App\Models\Utility::get_file('uploads/tickets/');
@endphp

<link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">

{{ Form::model($ticket, ['route' => ['ticket.update', $ticket->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">

    

    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('title', __('Subject'), ['class' => 'col-form-label']) }}
            {{ Form::text('title', null, ['class' => 'form-control', 'placeholder' => __('Enter Ticket Subject')]) }}
        </div>
        @if (\Auth::user()->type != 'employee')
            <div class="form-group col-md-6">
                {{ Form::label('employee_id', __('Ticket for Employee'), ['class' => 'col-form-label']) }}
                {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select2']) }}
            </div>
        @endif

        <div class="form-group col-md-6">
            {{ Form::label('priority', __('Priority'), ['class' => 'col-form-label']) }}
            <select name="priority" class="form-control">
                <option value="low" @if ($ticket->priority == 'low') selected @endif>{{ __('Low') }}</option>
                <option value="medium" @if ($ticket->priority == 'medium') selected @endif>{{ __('Medium') }}</option>
                <option value="high" @if ($ticket->priority == 'high') selected @endif>{{ __('High') }}</option>
                <option value="critical" @if ($ticket->priority == 'critical') selected @endif>{{ __('Critical') }}</option>
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('end_date', __('End Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('end_date', null, ['class' => 'form-control', 'autocomplete' => 'off']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('status', __('Status'), ['class' => 'col-form-label']) }}
            <select name="status" class="form-control " id="status">
                <option value="open" @if ($ticket->status == 'open') selected @endif>{{ __('Open') }}</option>
                <option value="onhold" @if ($ticket->status == 'onhold') selected @endif>{{ __('On Hold') }}</option>
                <option value="close" @if ($ticket->status == 'close') selected @endif>{{ __('Close') }}</option>
            </select>
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'col-form-label']) !!}
            <textarea class="form-control summernote-simple-2" name="description" id="description" rows="7">{{ $ticket->description }}</textarea>
        </div>

        <div class="form-group col-md-6">
            <label class="col-form-label">{{ __('Attachments') }}</label>
            <div class="choose-file">
                <label for="attachment" class="form-label">
                    <input type="file" name="attachment" id="attachment"
                        class="form-control {{ $errors->has('attachment') ? ' is-invalid' : '' }}"
                        onchange="document.getElementById('blah').src = window.URL.createObjectURL(this.files[0])"
                        data-filename="attachment_selection">
                    <div class="invalid-feedback">
                        {{ $errors->first('attachment') }}
                    </div>
                </label>
                <p class="attachment_selection"></p>
            </div>
        </div>
        <div class="form-group col-md-6 d-flex justify-content-center align-items-center">
            <img src="@if ($ticket->attachment) {{ $attachmentPath . $ticket->attachment }} @else {{ $attachmentPath . 'default.png' }} @endif"
                id="blah" style="max-width: 60%; max-height: 150px; object-fit: contain;" />
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>

<script>
    $(document).ready(function() {
        if ($(".summernote-simple-2").length) {
            $('.summernote-simple-2').summernote({
                dialogsInBody: !0,
                minHeight: 200,
                maxHeight: 300,
                toolbar: [
                    ['style', ['style']],
                    ["font", ["bold", "italic", "underline", "clear", "strikethrough"]],
                    ['fontname', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                ]
            });
        }
    });
</script>
