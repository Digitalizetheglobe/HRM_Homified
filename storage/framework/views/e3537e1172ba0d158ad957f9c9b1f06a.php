

<?php $__env->startSection('page-title'); ?>
    <?php echo e(__('Payslip')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>"><?php echo e(__('Dashboard')); ?></a></li>
    <li class="breadcrumb-item"><?php echo e(__('payslip')); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
   <?php if(Gate::check('payroll.payslip.generate.all') || Gate::check('payroll.payslip.delete.all')): ?>
        <div class="col-sm-12 col-lg-12 col-xl-12 col-md-12 mt-4">
            <div class="card">
                <div class="card-body">
                    <?php echo e(Form::open(['route' => ['payslip.store'], 'method' => 'POST', 'id' => 'payslip_form'])); ?>

                    <div class="d-flex justify-content-end align-items-end gap-2">
                        <div class="generate-control-wrapper">
                            <?php echo e(Form::label('month', __('Select Month'), ['class' => 'form-label'])); ?>

                            <?php echo e(Form::select('month', $month, date('m'), ['class' => 'form-control select', 'id' => 'month', 'style' => 'min-width: 120px;'])); ?>

                        </div>
                        <div class="generate-control-wrapper">
                            <?php echo e(Form::label('year', __('Select Year'), ['class' => 'form-label'])); ?>

                            <?php echo e(Form::select('year', $year, date('Y'), ['class' => 'form-control select', 'style' => 'min-width: 100px;'])); ?>

                        </div>
                        <div class="generate-control-wrapper">
                            <label class="form-label">&nbsp;</label>
                            <a href="#" class="btn btn-primary generate-btn-mobile"
                                onclick="document.getElementById('payslip_form').submit(); return false;"
                                data-original-title="<?php echo e(__('Generate Payslip')); ?>">
                                <?php echo e(__('Generate Payslip')); ?>

                            </a>
                        </div>
                    </div>
                    <?php echo e(Form::close()); ?>

                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-4" style="margin-bottom: 10px;">
                        <div class="d-flex align-items-center justify-content-start">
                            <h5><?php echo e(__('Find Employee Payslip')); ?></h5>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-end gap-2">
                            <div class="filter-control-wrapper">
                                <label class="form-label"><?php echo e(__('Month')); ?></label>
                                <select class="form-control month_date" name="year" tabindex="-1"
                                    aria-hidden="true" style="min-width: 120px;">
                                    <option value="--">--</option>
                                    <?php $__currentLoopData = $month; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $mon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $selected = date('m') == $k ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo e($k); ?>" <?php echo e($selected); ?>><?php echo e($mon); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="filter-control-wrapper">
                                <label class="form-label"><?php echo e(__('Year')); ?></label>
                                <?php echo e(Form::select('year', $year, date('Y'), ['class' => 'form-control year_date', 'style' => 'min-width: 100px;'])); ?>

                            </div>
                            <div class="filter-control-wrapper">
                                <?php if(Gate::check('payroll.payslip.export.all') || Gate::check('payroll.payslip.export.own')): ?>
                                    <label class="form-label">&nbsp;</label>
                                    <?php echo e(Form::open(['route' => ['payslip.export'], 'method' => 'POST', 'id' => 'payslip_form'])); ?>

                                    <input type="hidden" name="filter_month" class="filter_month">
                                    <input type="hidden" name="filter_year" class="filter_year">
                                    <input type="submit" value="<?php echo e(__('Export')); ?>" class="btn btn-primary export-btn-mobile">
                                    <?php echo e(Form::close()); ?>

                                <?php endif; ?>
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
                                <th class="text-start"><?php echo e(__('Employee Id')); ?></th>
                                <?php if(\Auth::user()->type != 'employee' || Gate::check('payroll.payslip.view.all')): ?>
                                    <th class="text-start"><?php echo e(__('Name')); ?></th>
                                <?php endif; ?>
                                <th class="text-start"><?php echo e(__('Payroll Type')); ?></th>
                                <th class="text-start"><?php echo e(__('Salary')); ?></th>
                                <th class="text-start"><?php echo e(__('Net Salary')); ?></th>
                                <th class="text-start"><?php echo e(__('Status')); ?></th>
                                <th class="text-center"><?php echo e(__('Action')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script-page'); ?>
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
                    month = '<?php echo e(date('m', strtotime('last month'))); ?>';
                    year = '<?php echo e(date('Y')); ?>';

                    $('.filter_month').val(month);
                    $('.filter_year').val(year);
                }

                var datePicker = year + '-' + month;

                $.ajax({
                    url: '<?php echo e(route('payslip.search_json')); ?>',
                    type: 'POST',
                    data: {
                        "datePicker": datePicker,
                        "own": "<?php echo e(request('own')); ?>",
                        "_token": "<?php echo e(csrf_token()); ?>",
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
                                    '<a href="#" data-url="<?php echo e(url('payslip/pdf/')); ?>/' + id +
                                    '/' + datePicker +
                                    '" data-size="md-pdf"  data-ajax-popup="true" class="btn btn-primary" data-title="<?php echo e(__('Employee Payslip')); ?>">' +
                                    '<?php echo e(__('Payslip')); ?>' + '</a> ';
                            }

                            if (status == "UnPaid" && data != 0) {
                                clickToPaid = '<a href="<?php echo e(url('payslip/paysalary/')); ?>/' + id +
                                    '/' + datePicker + '"  class="view-btn primary-bg btn-sm">' +
                                    '<?php echo e(__('Click To Paid')); ?>' + '</a>  ';
                            }

                            if (data != 0) {
                                view =
                                    '<a href="#" data-url="<?php echo e(url('payslip/showemployee/')); ?>/' +
                                    payslip_id +
                                    '"  data-ajax-popup="true" class="view-btn gray-bg" data-title="<?php echo e(__('View Employee Detail')); ?>">' +
                                    '<?php echo e(__('View')); ?>' + '</a>';
                            }

                            if (data != 0 && status == "UnPaid") {
                                edit =
                                    '<a href="#" data-url="<?php echo e(url('payslip/editemployee/')); ?>/' +
                                    payslip_id +
                                    '"  data-ajax-popup="true" class="view-btn blue-bg" data-title="<?php echo e(__('Edit Employee salary')); ?>">' +
                                    '<?php echo e(__('Edit')); ?>' + '</a>';
                            }

                            var url = '<?php echo e(route('payslip.delete', ':id')); ?>';
                            url = url.replace(':id', payslip_id);

                            <?php if(Gate::check('payroll.payslip.delete.all') || \Auth::user()->type == 'employee'): ?>
                                if (data != 0) {
                                    deleted = '<a href="#"  data-url="' + url +
                                        '" class="payslip_delete view-btn red-bg" >' +
                                        '<?php echo e(__('Delete')); ?>' + '</a>';
                                }
                            <?php endif; ?>

                            return view + payslip + clickToPaid + edit + deleted + form;
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
                                        '<a href="#" data-url="<?php echo e(url('payslip/pdf/')); ?>/' +
                                        id + '/' + datePicker +
                                        '" data-size="lg"  data-ajax-popup="true" class=" btn-sm btn btn-warning" data-title="<?php echo e(__('Employee Payslip')); ?>">' +
                                        '<?php echo e(__('Payslip')); ?>' + '</a> ';
                                }
                                if (valueOfElement[6] == "UnPaid" && valueOfElement[7] != 0) {
                                    var clickToPaid =
                                        '<a href="<?php echo e(url('payslip/paysalary/')); ?>/' + id +
                                        '/' + datePicker +
                                        '"  class="btn-sm btn btn-primary">' +
                                        '<?php echo e(__('Click To Paid')); ?>' + '</a>  ';
                                } else {
                                    var clickToPaid = '';
                                }

                                if (valueOfElement[7] != 0 && valueOfElement[6] == "UnPaid") {
                                    var edit =
                                        '<a href="#" data-url="<?php echo e(url('payslip/editemployee/')); ?>/' +
                                        payslip_id +
                                        '"  data-ajax-popup="true" class="btn-sm btn btn-info" data-title="<?php echo e(__('Edit Employee salary')); ?>">' +
                                        '<?php echo e(__('Edit')); ?>' + '</a>';
                                } else {
                                    var edit = '';
                                }

                                var url = '<?php echo e(route('payslip.delete', ':id')); ?>';
                                url = url.replace(':id', payslip_id);

                                <?php if(Gate::check('payroll.payslip.delete.all')): ?>
                                    var deleted = '<a href="#"  data-url="' + url +
                                        '" class="payslip_delete view-btn btn btn-danger ms-1 btn-sm"  >' +
                                        '<?php echo e(__('Delete')); ?>' + '</a>';
                                <?php else: ?>
                                    var deleted = '';
                                <?php endif; ?>

                                var url_employee = valueOfElement['url'];
                                <?php if(\Auth::user()->type != 'employee' || Gate::check('payroll.payslip.view.all')): ?>
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
                                <?php else: ?>
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
                                <?php endif; ?>
                            });
                        } else {
                            var colspan = $('#pc-dt-render-column-cells thead tr th').length;
                            var tr = '<tr><td class="dataTables-empty" colspan="' + colspan +
                                '"><?php echo e(__('No entries found')); ?></td></tr>';
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\hrm_realestate\resources\views/payslip/index.blade.php ENDPATH**/ ?>