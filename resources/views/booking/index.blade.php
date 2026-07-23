@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bookings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Bookings') }}</li>
@endsection

@section('action-button')
    <!-- <a href="{{ route('booking.export') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-file-export"></i>
    </a> -->

    @can('Create TimeSheet')
        <a href="#" data-url="{{ route('booking.create') }}" data-ajax-popup="true" data-size="xl"
            data-title="{{ __('Create New Booking') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@section('content')
    <div class="row">
            <div class="col-sm-12">
                <div class="mt-2" id="multiCollapseExample1">
                    <div class="card">
                        <div class="card-body">
                            {{ Form::open(['route' => ['booking.index'], 'method' => 'get', 'id' => 'booking_filter']) }}
                            <div class="row align-items-center justify-content-end">
                                <div class="col-xl-10">
                                    <div class="row">
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box"></div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                                {{ Form::date('start_date', isset($_GET['start_date']) ? $_GET['start_date'] : '', ['class' => 'month-btn form-control current_date', 'autocomplete' => 'off', 'id' => 'current_date']) }}
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                                {{ Form::date('end_date', isset($_GET['end_date']) ? $_GET['end_date'] : '', ['class' => 'month-btn form-control current_date', 'autocomplete' => 'off', 'id' => 'current_date']) }}
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('project', __('Project'), ['class' => 'form-label']) }}
                                                {{ Form::select('project', $projects, isset($_GET['project']) ? $_GET['project'] : '', ['class' => 'form-control select', 'id' => 'project_id']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="row">
                                        <div class="col-auto mt-4">
                                            <a href="#" class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('booking_filter').submit(); return false;">
                                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                            </a>
                                            <a href="{{ route('booking.index') }}" class="btn btn-sm btn-danger">
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
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-body table-border-style">
                    <div class="card-body py-0">
                        <div class="table-responsive">
                            <table class="table" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        @if (\Auth::user()->type != 'employee')
                                            <th>{{ __('Employee') }}</th>
                                        @endif
                                        <th>{{ __('Project') }}</th> <!-- Add this line -->
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Contact No') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bookings as $booking)
                                        <tr>
                                            @if (\Auth::user()->type != 'employee')
                                            <td>{{ !empty($booking->employee) ? $booking->employee->full_name : 'N/A' }}</td>
                                            @endif
                                            <td>
                                                @if($booking->project)
                                                    {{ $booking->project->project_name }}
                                                @else
                                                    {{ $booking->project_name ?? 'N/A' }}
                                                @endif
                                            </td>    
                                            <td>{{ $booking->primary_applicant_name }}</td>
                                            <td>{{ $booking->primary_applicant_contact_no }}</td>
                                            <td>{{ $booking->primary_applicant_email }}</td>
                                            <td class="Action">
                                                <span>
                                                    
                                                    <!-- @can('Create TimeSheet')
                                                        <div class="action-btn bg-blue-500 ms-2" >
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('booking.create', $booking->id) }}"
                                                                data-ajax-popup="true" data-size="xl">
                                                                <i class="ti ti-plus text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan -->

                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('booking.payslip', $booking->id) }}"
                                                            data-ajax-popup="true" data-size="lg">
                                                            <i class="ti ti-printer text-white"></i>
                                                        </a>
                                                    </div>

                                                    @can('Edit TimeSheet')
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                                data-url="{{ route('booking.edit', $booking->id) }}"
                                                                data-ajax-popup="true" data-size="xl">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                     


                                                
                                                    @can('Delete TimeSheet')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['booking.destroy', $booking->id],
                                                                'id' => 'delete-form-' . $booking->id,
                                                            ]) !!}
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para">
                                                                <i class="ti ti-trash text-white"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan

                                                    

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
    </div>
@endsection


@push('script-page')
    <script>
        $(document).ready(function() {
            var now = new Date();
            var month = (now.getMonth() + 1);
            var day = now.getDate();
            if (month < 10) month = "0" + month;
            if (day < 10) day = "0" + day;
            var today = now.getFullYear() + '-' + month + '-' + day;
            $('.current_date').val(today);
        });
    </script>
@endpush

@push('scripts')
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
            min-width: 200px !important; /* Employee (conditional) */
            width: 200px !important;
            max-width: 200px !important;
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 260px !important; /* Project */
            width: 220px !important;
            max-width: 220px !important;
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 280px !important; /* Name (Primary Applicant) */
            width: 320px !important;
            max-width: 280px !important;
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 180px !important; /* Contact No */
            width: 180px !important;
            max-width: 180px !important;
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 200px !important; /* Email */
            width: 200px !important;
            max-width: 200px !important;
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 220px !important; /* Action */
            width: 220px !important;
            max-width: 220px !important;
        }
        
        /* Force column widths for table cells as well */
        #pc-dt-simple td:nth-child(1) {
            min-width: 200px !important; /* Employee (conditional) */
            width: 200px !important;
            max-width: 200px !important;
        }
        
        #pc-dt-simple td:nth-child(2) {
            min-width: 220px !important; /* Project */
            width: 220px !important;
            max-width: 220px !important;
        }
        
        #pc-dt-simple td:nth-child(3) {
            min-width: 280px !important; /* Name (Primary Applicant) */
            width: 280px !important;
            max-width: 280px !important;
        }
        
        #pc-dt-simple td:nth-child(4) {
            min-width: 180px !important; /* Contact No */
            width: 180px !important;
            max-width: 180px !important;
        }
        
        #pc-dt-simple td:nth-child(5) {
            min-width: 200px !important; /* Email */
            width: 200px !important;
            max-width: 200px !important;
        }
    </style>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables with proper configuration
            $('#pc-dt-simple').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    emptyTable: "No booking records found"
                },
                autoWidth: false
            });
        });
    </script>
@endpush

