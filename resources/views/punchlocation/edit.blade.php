{{ Form::model($punchlocation, ['route' => ['punchlocation.update', $punchlocation->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12">
            <div class="form-group">
                {{ Form::label('name', __('Location Name'), ['class' => 'form-label']) }}
                {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Example: Pune Office')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('latitude', __('Latitude'), ['class' => 'form-label']) }}
                {{ Form::text('latitude', null, ['class' => 'form-control', 'id' => 'punch_latitude', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('longitude', __('Longitude'), ['class' => 'form-label']) }}
                {{ Form::text('longitude', null, ['class' => 'form-control', 'id' => 'punch_longitude', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('radius_meters', __('Allowed radius (meters)'), ['class' => 'form-label']) }}
                {{ Form::number('radius_meters', null, ['class' => 'form-control', 'min' => 5, 'max' => 500, 'required' => 'required']) }}
                <small class="text-muted">{{ __('Punch In is allowed only inside this distance.') }}</small>
            </div>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-group">
                <button type="button" class="btn btn-light" id="use-current-location">{{ __('Use my current location') }}</button>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
<script>
    document.getElementById('use-current-location')?.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('{{ __('Location is not available in this browser.') }}');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('punch_latitude').value = position.coords.latitude.toFixed(7);
            document.getElementById('punch_longitude').value = position.coords.longitude.toFixed(7);
        }, function () {
            alert('{{ __('Could not read the current location. Allow location access and try again.') }}');
        }, { enableHighAccuracy: true, timeout: 10000 });
    });
</script>
