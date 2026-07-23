@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Enquiry') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Enquiry') }}</li>
@endsection

@section('action-button')
    @if(Auth::user()->type != 'hr') {{-- Only show export and create for non-HR users --}}
        <a href="{{ route('timesheet.export', request()->query()) }}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>

        @can('Create TimeSheet')
            <a href="#" data-url="{{ route('timesheet.create') }}" data-ajax-popup="true" data-size="xl"
                data-title="{{ __('Create New Enquiry') }}"
                class="btn btn-sm btn-primary" id="create-btn">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    @endif
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-4" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['timesheet.index'], 'method' => 'get', 'id' => 'timesheet_filter']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('project', __('Project'), ['class' => 'form-label']) }}
                                                {{ Form::select('project', $projectsList, isset($_GET['project']) ? $_GET['project'] : '', ['class' => 'form-control select', 'id' => 'project_id']) }}
                                            </div>
                                        </div>
                                    <div class="col-xl-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('start_date', isset($_GET['start_date']) ? $_GET['start_date'] : '', ['class' => 'month-btn form-control', 'autocomplete' => 'off', 'id' => 'start_date']) }}                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('end_date', isset($_GET['end_date']) ? $_GET['end_date'] : '', ['class' => 'month-btn form-control', 'autocomplete' => 'off', 'id' => 'end_date']) }}                                        </div>
                                    </div>
                                       
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('client_status', __('Client Status'), ['class' => 'form-label']) }}
                                                {{ Form::select('client_status', [
                                                    'Select Client Status' => 'Select Client Status',
                                                    'Intrested' => 'Intrested',
                                                    'Not-Intrested' => 'Not-Intrested',
                                                    'Call-Back' => 'Call-Back',
                                                    'Hold' => 'Hold',
                                                    'Booked' => 'Booked'
                                                ], request()->get('client_status'), ['class' => 'form-control select', 'id' => 'client_status']) }}
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary"
                                            onclick="document.getElementById('timesheet_filter').submit(); return false;">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-danger">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-trash-off text-white-off "></i></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                <div class="card-body py-0">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    @if (\Auth::user()->type != 'employee')
                                        <th>{{ __('Employee Name') }}</th>
                                    @endif
                                    <th>{{ __('Project') }}</th>
                                    <th>{{ __('Client Name') }}</th>
                                    <th>{{ __('Mobile No') }}</th>
                                     <th>{{ __('Client Status') }}</th>
                                    @if (Auth::user()->type != 'hr' && (Gate::check('Edit TimeSheet') || Gate::check('Delete TimeSheet')))
                                        <th width="200px">{{ __('Action') }}</th>
                                    @endif
                                    </tr>
                            </thead>
                                <tbody id="enquiry-table-body">
                                    @foreach ($timeSheets as $timeSheet)
                                        <tr>
                                            @if (\Auth::user()->type != 'employee')
                                                <td>
                                                    @if($timeSheet->assigned_to)
                                                        {{ $timeSheet->assignedEmployee->employee->full_name ?? $timeSheet->assignedEmployee->name ?? 'N/A' }}
                                                    @else
                                                        {{ $timeSheet->employee->employee->full_name ?? $timeSheet->employee->name ?? 'N/A' }}
                                                    @endif
                                                </td>
                                            @endif
                                            <td>{{ $timeSheet->project->project_name ?? 'N/A' }}</td>
                                            <td>{{ $timeSheet->full_name }}</td>
                                            <td>{{ $timeSheet->mobile_no }}</td>
                                            <td>{{ $timeSheet->client_status }}</td>
                                            @php
                                                $isSiteHead = false;
                                                if ($timeSheet->project && !empty($timeSheet->project->site_heads)) {
                                                    $currentEmployeeId = Auth::user()->employee->id ?? null;
                                                    $isSiteHead = $currentEmployeeId && in_array((string)$currentEmployeeId, $timeSheet->project->site_heads, true);
                                                }
                                            @endphp

                                            <td class="Action">
                                                <span class="d-flex justify-content-end align-items-center gap-1">
                                                    {{-- View button always visible --}}
                                                    <div class="action-btn bg-warning">
                                                        <a href="#" class="btn btn-sm view-btn"
                                                            data-url="{{ route('timesheet.show', $timeSheet->id) }}"
                                                            data-ajax-popup="true"
                                                            data-size="xl"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#commonModal"
                                                            data-title="{{ __('Enquiry Details') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>

                                                    {{-- Edit/Delete buttons --}}
                                                        @can('Edit TimeSheet')
                                                            <div class="action-btn bg-info">
                                                                <a href="#"
                                                                class="btn btn-sm edit-btn"
                                                                data-url="{{ route('timesheet.edit', $timeSheet->id) }}"
                                                                data-ajax-popup="true"
                                                                data-size="xl"
                                                                data-title="{{ __('Edit TimeSheet') }}">
                                                                    <i class="ti ti-pencil text-white"></i>
                                                                </a>
                                                            </div>
                                                        @endcan
                                                        @if(!$isSiteHead && Auth::user()->type != 'Director' && strcasecmp(Auth::user()->type, 'Employee') != 0)
                                                            @can('Delete TimeSheet')
                                                                <div class="action-btn bg-danger">
                                                                    <form action="{{ route('timesheet.destroy', $timeSheet->id) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                                class="btn btn-sm">
                                                                            <i class="ti ti-trash text-white"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $(document).ready(function() {
       // Remove or comment out this part
        var now = new Date();
        var month = (now.getMonth() + 1).toString().padStart(2, '0');
        var day = now.getDate().toString().padStart(2, '0');
        var today = now.getFullYear() + '-' + month + '-' + day;
        $('.current_date').val(today);

        // Function to refresh the enquiry table
        function refreshEnquiryTable() {
            $.ajax({
                url: "{{ route('timesheet.index') }}",
                type: 'GET',
                data: $('#timesheet_filter').serialize(),
                success: function(response) {
                    // Extract just the table body content from the response
                    var newContent = $(response).find('#enquiry-table-body').html();
                    $('#enquiry-table-body').html(newContent);
                    
                    // Reinitialize any necessary plugins or event handlers
                    initEventHandlers();
                },
                error: function() {
                    
                }
            });
        }

        // Initialize event handlers
        function initEventHandlers() {
            // Handle form submission for creating new enquiry
            $('#create-btn').on('click', function() {
                $.ajax({
                    url: $(this).data('url'),
                    type: 'GET',
                    success: function(response) {
                        $('#commonModal .modal-body').html(response);
                        $('#commonModal').modal('show');
                        
                        // Handle form submission inside the modal
                        $('#timesheet-form').on('submit', function(e) {
                            e.preventDefault();
                            var form = $(this);
                            var url = form.attr('action');
                            
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: form.serialize(),
                                success: function(response) {
                                    if(response.success) {
                                        $('#commonModal').modal('hide');
                                        show_toastr('Success', response.message, 'success');
                                        refreshEnquiryTable();
                                    }
                                },
                                error: function(xhr) {
                                    if(xhr.status === 422) {
                                        $('.invalid-feedback').remove();
                                        $('.is-invalid').removeClass('is-invalid');
                                        
                                        var errors = xhr.responseJSON.errors;
                                        $.each(errors, function(field, messages) {
                                            var input = $('[name="' + field + '"]');
                                            input.addClass('is-invalid');
                                            input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                        });
                                    } else {
                                        show_toastr('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                                    }
                                }
                            });
                        });
                    }
                });
            });

            // Handle edit button clicks
            $('.edit-btn').on('click', function() {
                var url = $(this).data('url');
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        $('#commonModal .modal-body').html(response);
                        $('#commonModal').modal('show');
                        
                        // Handle form submission inside the modal
                        $('#timesheet-form').on('submit', function(e) {
                            e.preventDefault();
                            var form = $(this);
                            var url = form.attr('action');
                            
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: form.serialize(),
                                success: function(response) {
                                    if(response.success) {
                                        $('#commonModal').modal('hide');
                                        show_toastr('Success', response.message, 'success');
                                        refreshEnquiryTable();
                                    }
                                },
                                error: function(xhr) {
                                    if(xhr.status === 422) {
                                        $('.invalid-feedback').remove();
                                        $('.is-invalid').removeClass('is-invalid');
                                        
                                        var errors = xhr.responseJSON.errors;
                                        $.each(errors, function(field, messages) {
                                            var input = $('[name="' + field + '"]');
                                            input.addClass('is-invalid');
                                            input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                                        });
                                    } else {
                                        show_toastr('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                                    }
                                }
                            });
                        });
                    }
                });
            });
        }

        // Initialize event handlers on page load
        initEventHandlers();

        // Handle filter form submission
        $('#timesheet_filter').on('submit', function(e) {
            e.preventDefault();
            refreshEnquiryTable();
        });

        // Refresh table when modal is closed (in case changes were made)
        $('#commonModal').on('hidden.bs.modal', function () {
            refreshEnquiryTable();
        });

        // Add this to your initEventHandlers function
        $('.view-btn').on('click', function() {
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $('#commonModal .modal-body').html(response);
                    $('#commonModal').modal('show');
                }
            });
        });


    });

</script>

<style>
    .table th {
        white-space: nowrap;
        text-align: left !important;
        vertical-align: middle !important;
        padding-right: 25px !important;
        position: relative;
    }
    
    .table td {
        vertical-align: middle !important;
    }
    
    /* Fix DataTables sorting icons alignment */
    .dataTables_wrapper .dataTables_scrollHead .table th {
        position: relative;
    }
    
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after,
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after,
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 8px !important;
        margin-top: 0 !important;
    }
    
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after {
        content: "·" !important;
    }
    
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
        content: "·" !important;
    }
    
    .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after {
        content: "·" !important;
        opacity: 0.3;
    }
    
    /* Ensure proper column width alignment */
    #pc-dt-simple th {
        min-width: 120px;
    }
    
    #pc-dt-simple th:nth-child(1) {
        min-width: 200px; /* Employee Name (conditional) */
    }
    
    #pc-dt-simple th:nth-child(2) {
        min-width: 220px; /* Project */
    }
    
    #pc-dt-simple th:nth-child(3) {
        min-width: 200px; /* Client Name */
    }
    
    #pc-dt-simple th:nth-child(4) {
        min-width: 150px; /* Mobile No */
    }
    
    #pc-dt-simple th:nth-child(5) {
        min-width: 160px; /* Client Status */
    }
    
    #pc-dt-simple th:nth-child(6) {
        min-width: 220px; /* Action (conditional) */
    }
    
    /* Force column widths for table cells as well */
    #pc-dt-simple td:nth-child(1) {
        min-width: 200px !important; /* Employee Name (conditional) */
        width: 200px !important;
        max-width: 200px !important;
    }
    
    #pc-dt-simple td:nth-child(2) {
        min-width: 220px !important; /* Project */
        width: 220px !important;
        max-width: 220px !important;
    }
    
    #pc-dt-simple td:nth-child(3) {
        min-width: 200px !important; /* Client Name */
        width: 200px !important;
        max-width: 200px !important;
    }
    
    #pc-dt-simple td:nth-child(4) {
        min-width: 150px !important; /* Mobile No */
        width: 150px !important;
        max-width: 150px !important;
    }
    
    #pc-dt-simple td:nth-child(5) {
        min-width: 160px !important; /* Client Status */
        width: 160px !important;
        max-width: 160px !important;
    }
</style>
@endpush