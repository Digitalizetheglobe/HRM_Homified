<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <h6 class="text-primary mb-3">{{ __('Resignation Reason') }}</h6>
            <div class="p-3 bg-light rounded border">
                <p class="mb-0 text-dark" style="white-space: pre-wrap;">{{ $resignation->description }}</p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
</div>
