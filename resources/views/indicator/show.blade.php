

<div class="modal-body">
    <div class="row py-4">
        <div class="col-md-12 ">
            <div class="info text-sm">
                <strong>{{ __('Branch') }} : </strong>
                <span>{{ !empty($indicator->branches) ? $indicator->branches->name : '' }}</span>
            </div>
        </div>
        <div class="col-md-6 mt-2">
            <div class="info text-sm font-style">
                <strong>{{ __('Department') }} : </strong>
                <span>{{ !empty($indicator->departments) ? $indicator->departments->name : '' }}</span>
            </div>
        </div>
        <div class="col-md-6 mt-3">
            <div class="info text-sm font-style">
                <strong>{{ __('Designation') }} : </strong>
                <span>{{ !empty($indicator->designations) ? $indicator->designations->name : '' }}</span>
            </div>
        </div>

    </div>
    <div class="row">
        @foreach ($performance_types as $performances)
            <div class="col-md-12 mt-3">
                <h6>{{ $performances->name }}</h6>
                <hr class="mt-0">
            </div>
            @foreach ($performances->types as $types)
                <div class="col-6">
                    {{ $types->name }}
                </div>
                <div class="col-6">
                    <fieldset id='demo1' class="rate">
                        <input class="stars" type="radio" id="technical-5-{{ $types->id }}"
                            name="rating[{{ $types->id }}]" value="5"
                            {{ isset($ratings[$types->id]) && $ratings[$types->id] == 5 ? 'checked' : '' }} disabled>
                        <label class="full" for="technical-5-{{ $types->id }}"></label>
                        <input class="stars" type="radio" id="technical-4-{{ $types->id }}"
                            name="rating[{{ $types->id }}]" value="4"
                            {{ isset($ratings[$types->id]) && $ratings[$types->id] == 4 ? 'checked' : '' }} disabled>
                        <label class="full" for="technical-4-{{ $types->id }}"></label>
                        <input class="stars" type="radio" id="technical-3-{{ $types->id }}"
                            name="rating[{{ $types->id }}]" value="3"
                            {{ isset($ratings[$types->id]) && $ratings[$types->id] == 3 ? 'checked' : '' }} disabled>
                        <label class="full" for="technical-3-{{ $types->id }}"></label>
                        <input class="stars" type="radio" id="technical-2-{{ $types->id }}"
                            name="rating[{{ $types->id }}]" value="2"
                            {{ isset($ratings[$types->id]) && $ratings[$types->id] == 2 ? 'checked' : '' }} disabled>
                        <label class="full" for="technical-2-{{ $types->id }}"></label>
                        <input class="stars" type="radio" id="technical-1-{{ $types->id }}"
                            name="rating[{{ $types->id }}]" value="1"
                            {{ isset($ratings[$types->id]) && $ratings[$types->id] == 1 ? 'checked' : '' }} disabled>
                        <label class="full" for="technical-1-{{ $types->id }}"></label>
                    </fieldset>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
