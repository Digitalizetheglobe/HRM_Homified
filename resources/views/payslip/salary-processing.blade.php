@extends('layouts.admin')

@section('page-title')
    {{ __('Salary Processing') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Salary Processing') }}</li>
@endsection

@section('content')
@php
    // Check if user is from Finance & Accounts department
    $isFinanceAccounts = false;
    if (\Auth::user()->type == 'employee') {
        // Load employee with department relationship
        $employee = \App\Models\Employee::where('user_id', \Auth::user()->id)
            ->with('department')
            ->first();
        if ($employee && $employee->department) {
            $deptName = strtolower(trim($employee->department->name));
            // Check for various possible department name formats
            $isFinanceAccounts = (
                $deptName == 'finance & accounts' || 
                $deptName == 'finance and accounts' ||
                $deptName == 'finance & account' ||
                strpos($deptName, 'finance') !== false && strpos($deptName, 'account') !== false
            );
        }
    } elseif (\Auth::user()->type == 'company') {
        $isFinanceAccounts = true;
    }
@endphp
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h5>{{ __('Salary Processing') }}</h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-box" style="min-width: 120px;">
                            {{ Form::label('month', __('Month'), ['class' => 'form-label']) }}
                            {{ Form::select('month', $month, date('m'), ['class' => 'form-control month_date', 'placeholder' => __('Select Month')]) }}
                        </div>
                        <div class="btn-box" style="min-width: 100px;">
                            {{ Form::label('year', __('Year'), ['class' => 'form-label']) }}
                            {{ Form::select('year', $year, date('Y'), ['class' => 'form-control year_date']) }}
                        </div>
                        <div class="btn-box" style="min-width: 150px;">
                            {{ Form::label('department_id', __('Department'), ['class' => 'form-label']) }}
                            {{ Form::select('department_id', $departments, '', ['class' => 'form-control department_filter', 'placeholder' => __('All Departments')]) }}
                        </div>
                        @if(\Auth::user()->type == 'company' || \Gate::check('payroll.salary_processing.export.all'))
                        <div class="btn-box align-self-end">
                            {{ Form::open(['route' => ['salary-processing.export'], 'method' => 'POST', 'id' => 'salary_processing_export_form']) }}
                            <input type="hidden" name="datePicker" class="export_date_picker" value="">
                            <input type="hidden" name="department_id" class="export_department_id" value="">
                            <button type="submit" class="btn btn-primary" id="export_btn">
                                <i class="ti ti-file-export"></i> {{ __('Export') }}
                            </button>
                            {{ Form::close() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body table-border-style">
                <div class="table-responsive">
                <table class="table" id="pc-dt-render-column-cells">
                        <thead>
                            <tr>
                                <th>{{ __('Employee Name') }}</th>
                                <th>{{ __('Total Week Off') }}</th>
                                <th>{{ __('Total Absent') }}</th>
                                <th>{{ __('Total Present Days') }}</th>
                                <th>{{ __('Total Paid Leave') }}</th>
                                <th>{{ __('Total Leave Taken') }}</th>
                                <th>{{ __('Total Remaining Leave') }}</th>
                                <th>{{ __('Total Comp Off Earned') }}</th>
                                <th>{{ __('Total Comp Off Used') }}</th>
                                <th>{{ __('Total Remaining Comp Off') }}</th>
                                <th>{{ __('Salary Calculation') }}</th>
                                <th>{{ __('Salary') }}</th>
                            </tr>
                        </thead>
                        <tbody>
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
            callback();

            function callback() {
                var month = $(".month_date").val();
                var year = $(".year_date").val();

                if (month == '' || month == '--') {
                    month = '{{ date('m') }}';
                    year = '{{ date('Y') }}';
                }

                var datePicker = year + '-' + month;
                var departmentId = $(".department_filter").val();
                
                // Build data object
                var ajaxData = {
                    "datePicker": datePicker,
                    "_token": "{{ csrf_token() }}",
                };
                
                // Only add department_id if a department is selected
                if (departmentId && departmentId !== '' && departmentId !== '0') {
                    ajaxData.department_id = departmentId;
                }

                $.ajax({
                    url: '{{ route('salary-processing.search_json') }}',
                    type: 'POST',
                    data: ajaxData,
                    success: function(data) {
                        console.clear();
                        var tr = '';
                        if (data.length > 0) {
                            $.each(data, function(indexInArray, valueOfElement) {
                                var url_employee = valueOfElement['url'];
                                var employeeName = valueOfElement[1] || '';
                                var totalWeekOff = parseFloat(valueOfElement[2]) || 0;
                                var totalAbsent = parseFloat(valueOfElement[3]) || 0;
                                var totalPresent = parseFloat(valueOfElement[4]) || 0;
                                var totalPaidLeave = parseFloat(valueOfElement[5]) || 0;
                                var totalLeaveTaken = parseFloat(valueOfElement[6]) || 0;
                                var totalRemainingLeave = parseFloat(valueOfElement[7]) || 0;
                                var totalCompOffEarned = parseFloat(valueOfElement[8]) || 0;
                                var totalCompOffUsed = parseFloat(valueOfElement[9]) || 0;
                                var totalRemainingCompOff = parseFloat(valueOfElement[10]) || 0;
                                var salaryCalculation = parseFloat(valueOfElement[11]) || 0;
                                var salary = parseFloat(valueOfElement[12]) || 0;

                                function formatNumber(num) {
                                    return parseFloat(num).toLocaleString('en-IN', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }

                                var nameCell = url_employee
                                    ? '<a href="' + url_employee + '">' + employeeName + '</a>'
                                    : employeeName;

                                tr +=
                                    '<tr>' +
                                    '<td>' + nameCell + '</td>' +
                                    '<td>' + formatNumber(totalWeekOff) + '</td>' +
                                    '<td>' + formatNumber(totalAbsent) + '</td>' +
                                    '<td>' + formatNumber(totalPresent) + '</td>' +
                                    '<td>' + formatNumber(totalPaidLeave) + '</td>' +
                                    '<td>' + formatNumber(totalLeaveTaken) + '</td>' +
                                    '<td>' + formatNumber(totalRemainingLeave) + '</td>' +
                                    '<td>' + formatNumber(totalCompOffEarned) + '</td>' +
                                    '<td>' + formatNumber(totalCompOffUsed) + '</td>' +
                                    '<td>' + formatNumber(totalRemainingCompOff) + '</td>' +
                                    '<td>' + formatNumber(salaryCalculation) + '</td>' +
                                    '<td>' + formatNumber(salary) + '</td>' +
                                    '</tr>';
                            });
                        } else {
                            tr = '<tr><td class="dataTables-empty" colspan="12">{{ __('No entries found') }}</td></tr>';
                        }

                        $('#pc-dt-render-column-cells tbody').html(tr);
                        var table = document.querySelector("#pc-dt-render-column-cells");
                        if (table && typeof simpleDatatables !== 'undefined') {
                            var datatable = new simpleDatatables.DataTable(table);
                        }
                    },
                    error: function(data) {
                        console.log('Error:', data);
                    }
                });
            }

            $(document).on("change", ".month_date,.year_date,.department_filter", function() {
                callback();
            });

            // Update export date picker and department when month/year/department changes
            function updateExportForm() {
                var month = $(".month_date").val();
                var year = $(".year_date").val();
                var departmentId = $(".department_filter").val();

                if (month == '' || month == '--') {
                    month = '{{ date('m') }}';
                    year = '{{ date('Y') }}';
                }

                var datePicker = year + '-' + month;
                $('.export_date_picker').val(datePicker);
                $('.export_department_id').val(departmentId || '');
            }

            // Initialize export form
            updateExportForm();

            // Update export form on change
            $(document).on("change", ".month_date,.year_date,.department_filter", function() {
                updateExportForm();
            });

            // Handle payment status change with confirmation for Finance & Accounts users
            @if ($isFinanceAccounts)
            $(document).on("click", ".mark-payment-btn", function(e) {
                e.preventDefault();
                var $btn = $(this);
                var employeeId = $btn.data('employee-id');
                var employeeName = $btn.data('employee-name');
                var currentStatus = $btn.data('current-status');
                var newStatus = $btn.data('new-status');
                var month = $(".month_date").val();
                var year = $(".year_date").val();

                if (month == '' || month == '--') {
                    month = '{{ date('m') }}';
                    year = '{{ date('Y') }}';
                }

                var monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                var monthName = monthNames[parseInt(month) - 1];
                var statusText = newStatus === 'Done' ? 'Paid' : 'Pending';

                // Show confirmation modal using SweetAlert
                const swalWithBootstrapButtons = Swal.mixin({
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-danger'
                    },
                    buttonsStyling: false
                });

                swalWithBootstrapButtons.fire({
                    title: 'Confirm Payment Status Change',
                    html: '<div class="text-start">' +
                          '<p><strong>Employee:</strong> ' + employeeName + '</p>' +
                          '<p><strong>Period:</strong> ' + monthName + ' ' + year + '</p>' +
                          '<p><strong>Current Status:</strong> <span class="badge bg-info">' + currentStatus + '</span></p>' +
                          '<p><strong>New Status:</strong> <span class="badge bg-success">' + statusText + '</span></p>' +
                          '<hr>' +
                          '<p class="text-danger"><strong>Are you sure the payment has been completed?</strong></p>' +
                          '<p class="text-muted small">This action will mark the salary payment as ' + statusText + ' for this employee.</p>' +
                          '</div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ti ti-check"></i> Yes, Confirm Payment',
                    cancelButtonText: '<i class="ti ti-x"></i> Cancel',
                    reverseButtons: true,
                    focusConfirm: false,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Disable button during processing
                        $btn.prop('disabled', true).html('<i class="ti ti-loader"></i> Processing...');

                        $.ajax({
                            url: '{{ route('salary-processing.update-status') }}',
                            type: 'POST',
                            data: {
                                employee_id: employeeId,
                                year: year,
                                month: month,
                                status: newStatus,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    swalWithBootstrapButtons.fire({
                                        title: 'Success!',
                                        text: 'Payment status has been updated successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        // Reload the table to show updated status
                                        callback();
                                    });
                                } else {
                                    swalWithBootstrapButtons.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to update status',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = 'Failed to update payment status';
                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMsg = xhr.responseJSON.error;
                                }
                                
                                swalWithBootstrapButtons.fire({
                                    title: 'Error!',
                                    text: errorMsg,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                
                                // Re-enable button and reload table
                                $btn.prop('disabled', false);
                                var originalText = newStatus === 'Done' ? '<i class="ti ti-check"></i> Mark as Paid' : '<i class="ti ti-x"></i> Mark as Pending';
                                $btn.html(originalText);
                                callback();
                            }
                        });
                    } else {
                        // User cancelled - do nothing
                    }
                });
            });
            @endif
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
        #pc-dt-render-column-cells th {
            min-width: 140px;
        }
        
        #pc-dt-render-column-cells th:nth-child(1) {
            min-width: 200px;
        }
    </style>
    
    <!-- <script>
        $(document).ready(function() {
            // Initialize DataTables with proper configuration
            $('#pc-dt-render-column-cells').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    emptyTable: "No salary processing records found"
                },
                autoWidth: false,
                scrollX: true
            });
        });
    </script> -->
@endpush


