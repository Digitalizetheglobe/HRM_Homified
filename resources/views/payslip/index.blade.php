@extends('layouts.admin')

@section('page-title')
    {{ __('Payslip') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('payslip') }}</li>
@endsection

@section('content')
   @if (Gate::check('payroll.payslip.generate.all') || Gate::check('payroll.payslip.delete.all'))
        <div class="col-sm-12 col-lg-12 col-xl-12 col-md-12 mt-4">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(['route' => ['payslip.store'], 'method' => 'POST', 'id' => 'payslip_form']) }}
                    <div class="d-flex justify-content-end align-items-end gap-2">
                        <div class="generate-control-wrapper">
                            {{ Form::label('month', __('Select Month'), ['class' => 'form-label']) }}
                            {{ Form::select('month', $month, date('m'), ['class' => 'form-control select', 'id' => 'month', 'style' => 'min-width: 120px;']) }}
                        </div>
                        <div class="generate-control-wrapper">
                            {{ Form::label('year', __('Select Year'), ['class' => 'form-label']) }}
                            {{ Form::select('year', $year, date('Y'), ['class' => 'form-control select', 'style' => 'min-width: 100px;']) }}
                        </div>
                        <div class="generate-control-wrapper">
                            <label class="form-label">&nbsp;</label>
                            <a href="#" class="btn btn-primary generate-btn-mobile"
                                onclick="document.getElementById('payslip_form').submit(); return false;"
                                data-bs-toggle="tooltip" title="{{ __('Generate Payslip') }}">
                                <i class="ti ti-circle-check text-white"></i>
                            </a>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    @endif

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-4" style="margin-bottom: 10px;">
                        <div class="d-flex align-items-center justify-content-start">
                            <h5>{{ __('Find Employee Payslip') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-end gap-2">
                            <div class="filter-control-wrapper">
                                <label class="form-label">{{ __('Month') }}</label>
                                <select class="form-control month_date" name="year" tabindex="-1"
                                    aria-hidden="true" style="min-width: 120px;">
                                    <option value="--">--</option>
                                    @foreach ($month as $k => $mon)
                                        @php
                                            $selected = date('m') == $k ? 'selected' : '';
                                        @endphp
                                        <option value="{{ $k }}" {{ $selected }}>{{ $mon }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-control-wrapper">
                                <label class="form-label">{{ __('Year') }}</label>
                                {{ Form::select('year', $year, date('Y'), ['class' => 'form-control year_date', 'style' => 'min-width: 100px;']) }}
                            </div>
                            <div class="filter-control-wrapper">
                                @if (Gate::check('payroll.payslip.export.all') || Gate::check('payroll.payslip.export.own'))
                                    <label class="form-label">&nbsp;</label>
                                    {{ Form::open(['route' => ['payslip.export'], 'method' => 'POST', 'id' => 'payslip_form']) }}
                                    <input type="hidden" name="filter_month" class="filter_month">
                                    <input type="hidden" name="filter_year" class="filter_year">
                                    <button type="submit" class="btn btn-primary export-btn-mobile" data-bs-toggle="tooltip" title="{{ __('Export') }}">
                                        <i class="ti ti-file-export text-white"></i>
                                    </button>
                                    {{ Form::close() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="pc-dt-render-column-cells">
                        <thead>
                            <tr>
                                <th class="text-start">{{ __('Employee Id') }}</th>
                                @if (\Auth::user()->type != 'employee' || Gate::check('payroll.payslip.view.all'))
                                    <th class="text-start">{{ __('Name') }}</th>
                                @endif
                                <th class="text-start">{{ __('Payroll Type') }}</th>
                                <th class="text-start">{{ __('Salary') }}</th>
                                <th class="text-start">{{ __('Net Salary') }}</th>
                                <th class="text-start">{{ __('Status') }}</th>
                                <th class="text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
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
        
        /* Ensure proper column width alignment */
        #pc-dt-render-column-cells th {
            min-width: 120px;
        }
        
        #pc-dt-render-column-cells th:nth-child(1) {
            min-width: 180px; /* Employee Id */
        }
        
        #pc-dt-render-column-cells th:nth-child(2) {
            min-width: 180px; /* Name */
        }
        
        #pc-dt-render-column-cells th:nth-child(3) {
            min-width: 140px; /* Payroll Type */
        }
        
        #pc-dt-render-column-cells th:nth-child(4) {
            min-width: 120px; /* Salary */
        }
        
        #pc-dt-render-column-cells th:nth-child(5) {
            min-width: 130px; /* Net Salary */
        }
        
        #pc-dt-render-column-cells th:nth-child(6) {
            min-width: 120px; /* Status */
        }
        
        #pc-dt-render-column-cells th:nth-child(7) {
            min-width: 150px; /* Action */
        }

        /* Mobile margin for Export button */
        @media (max-width: 767px) {
            .export-btn-mobile {
                margin-top: 0px !important;
                width: 100%;
            }
            
            .generate-btn-mobile {
                width: 100% !important;
            }
            
            .filter-control-wrapper,
            .generate-control-wrapper {
                flex: 1 1 100%;
                margin-bottom: 10px;
            }
            
            .d-flex.justify-content-end.align-items-end {
                flex-direction: column;
                align-items: stretch !important;
            }
        }
    </style>
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var month = $(".month_date").val();
                var year = $(".year_date").val();

                $('.filter_month').val(month);
                $('.filter_year').val(year);

                if (month == '') {
                    month = '{{ date('m', strtotime('last month')) }}';
                    year = '{{ date('Y') }}';

                    $('.filter_month').val(month);
                    $('.filter_year').val(year);
                }

                var datePicker = year + '-' + month;

                $.ajax({
                    url: '{{ route('payslip.search_json') }}',
                    type: 'POST',
                    data: {
                        "datePicker": datePicker,
                        "own": "{{ request('own') }}",
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(data) {
                        var datatable_data = {
                            data: data
                        };

                        function renderstatus(data, cell, row) {
                            if (data == 'Paid')
                                return '<div class="badge bg-success p-2 px-3 rounded"><a href="#" class="text-white">' +
                                    data + '</a></div>';
                            else
                                return '<div class="badge bg-danger p-2 px-3 rounded"><a href="#" class="text-white">' +
                                    data + '</a></div>';
                        }

                        function renderButton(data, cell, row) {

                            var $div = $(row);
                            employee_id = $div.find('td:eq(0)').text();
                            status = $div.find('td:eq(6)').text();

                            var month = $(".month_date").val();
                            var year = $(".year_date").val();
                            var id = employee_id;
                            var payslip_id = data;
                            var clickToPaid = '';
                            var payslip = '';
                            var view = '';
                            var edit = '';
                            var deleted = '';
                            var form = '';

                            if (data != 0) {
                                var payslip =
                                    '<a href="#" data-url="{{ url('payslip/pdf/') }}/' + id +
                                    '/' + datePicker +
                                    '" data-size="md-pdf"  data-ajax-popup="true" class="btn btn-primary" data-title="{{ __('Employee Payslip') }}">' +
                                    '{{ __('Payslip') }}' + '</a> ';
                            }

                            if (data != 0) {
                                view =
                                    '<a href="#" data-url="{{ url('payslip/showemployee/') }}/' +
                                    payslip_id +
                                    '"  data-ajax-popup="true" class="view-btn gray-bg" data-title="{{ __('View Employee Detail') }}">' +
                                    '{{ __('View') }}' + '</a>';
                            }

                            var url = '{{ route('payslip.delete', ':id') }}';
                            url = url.replace(':id', payslip_id);

                            @if (Gate::check('payroll.payslip.delete.all') || \Auth::user()->type == 'employee')
                                if (data != 0) {
                                    deleted = '<a href="#"  data-url="' + url +
                                        '" class="payslip_delete view-btn red-bg" >' +
                                        '{{ __('Delete') }}' + '</a>';
                                }
                            @endif

                            return view + payslip + deleted + form;
                        }

                        console.clear();
                        var tr = '';
                        if (data.length > 0) {
                            $.each(data, function(indexInArray, valueOfElement) {
                                var status =
                                    '<div class="badge bg-danger p-2 px-3 rounded"><a href="#" class="text-white">' +
                                    valueOfElement[6] + '</a></div>';
                                if (valueOfElement[6] == 'Paid' || valueOfElement[6] ==
                                    'paid') {
                                    var status =
                                        '<div class="badge bg-success p-2 px-3 rounded"><a href="#" class="text-white">' +
                                        valueOfElement[6] + '</a></div>';
                                }

                                var id = valueOfElement[0];
                                var employee_id = valueOfElement[1];
                                var payslip_id = valueOfElement[7];

                                if (valueOfElement[7] != 0) {
                                    var payslip =
                                        '<a href="#" data-url="{{ url('payslip/pdf/') }}/' +
                                        id + '/' + datePicker +
                                        '" data-size="lg"  data-ajax-popup="true" class=" btn-sm btn btn-warning" data-bs-toggle="tooltip" title="{{ __('Employee Payslip') }}">' +
                                        '<i class="ti ti-receipt text-white"></i></a> ';
                                }
                                var clickToPaid = '';
                                var edit = '';

                                var url = '{{ route('payslip.delete', ':id') }}';
                                url = url.replace(':id', payslip_id);

                                @if (Gate::check('payroll.payslip.delete.all'))
                                    var deleted = '<a href="#"  data-url="' + url +
                                        '" class="payslip_delete view-btn btn btn-danger ms-1 btn-sm" data-bs-toggle="tooltip" title="{{ __('Delete') }}">' +
                                        '<i class="ti ti-trash text-white"></i></a>';
                                @else
                                    var deleted = '';
                                @endif

                                var url_employee = valueOfElement['url'];
                                @if (\Auth::user()->type != 'employee' || Gate::check('payroll.payslip.view.all'))
                                    tr +=
                                        '<tr>' +
                                        '<td> <a class="btn btn-outline-primary" href="' +
                                        url_employee + '">' + valueOfElement[1] + '</a></td> ' +
                                        '<td>' + valueOfElement[2] + '</td> ' +
                                        '<td>' + valueOfElement[3] + '</td>' +
                                        '<td>' + valueOfElement[4] + '</td>' +
                                        '<td>' + valueOfElement[5] + '</td>' +
                                        '<td>' + status + '</td>' +
                                        '<td>' + payslip + clickToPaid + edit + deleted +
                                        '</td>' +
                                        '</tr>';
                                @else
                                    tr +=
                                        '<tr>' +
                                        '<td> <a class="btn btn-outline-primary" href="' +
                                        url_employee + '">' + valueOfElement[1] + '</a></td> ' +
                                        '<td>' + valueOfElement[2] + '</td> ' +
                                        '<td>' + valueOfElement[4] + '</td>' +
                                        '<td>' + valueOfElement[5] + '</td>' +
                                        '<td>' + status + '</td>' +
                                        '<td>' + payslip + clickToPaid + edit + deleted +
                                        '</td>' +
                                        '</tr>';
                                @endif
                            });
                        } else {
                            var colspan = $('#pc-dt-render-column-cells thead tr th').length;
                            var tr = '<tr><td class="dataTables-empty" colspan="' + colspan +
                                '">{{ __('No entries found') }}</td></tr>';
                        }

                        $('#pc-dt-render-column-cells tbody').html(tr);
                        var table = document.querySelector("#pc-dt-render-column-cells");
                        var datatable = new simpleDatatables.DataTable(table);
                    },
                    error: function(data) {

                    }
                });
            }

            $(document).on("change", ".month_date,.year_date", function() {
                callback();
            });

            //bulkpayment Click
            $(document).on("click", "#bulk_payment", function() {
                var month = $(".month_date").val();
                var year = $(".year_date").val();
                var datePicker = year + '_' + month;

            });
            $(document).on('click', '#bulk_payment',
                'a[data-ajax-popup="true"], button[data-ajax-popup="true"], div[data-ajax-popup="true"]',
                function() {
                    var month = $(".month_date").val();
                    var year = $(".year_date").val();
                    var datePicker = year + '-' + month;

                    var title = 'Bulk Payment';
                    var size = 'md';
                    var url = 'payslip/bulk_pay_create/' + datePicker;

                    // return false;

                    $("#commonModal .modal-title").html(title);
                    $("#commonModal .modal-dialog").addClass('modal-' + size);
                    $.ajax({
                        url: url,
                        success: function(data) {
                            // alert(data);
                            // return false;
                            if (data.length) {
                                $('#commonModal .body').html(data);
                                $("#commonModal").modal('show');
                                // common_bind();
                            } else {
                                show_toastr('error', 'Permission denied.');
                                $("#commonModal").modal('hide');
                            }
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            show_toastr('error', data.error);
                        }
                    });
                });

            $(document).on("click", ".payslip_delete", function() {
                var confirmation = confirm("are you sure you want to delete this payslip?");
                var url = $(this).data('url');

                if (confirmation) {
                    $.ajax({
                        type: "GET",
                        url: url,
                        dataType: "JSON",
                        success: function(data) {
                            // show_toastr(data.status, data.msg, 'data.status');
                            show_toastr('error', 'Payslip Deleted Successfully', 'success');

                            setTimeout(function() {
                                location.reload();
                            }, 800)
                        },
                    });
                }
            });
        });
    </script>
@endpush
