@extends('layouts.contractheader')

@section('page-title')
    {{ __('Increment Letter') }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-10">
        <div class="container">
            <div class="card mt-5" id="printTable" style="margin-left: 180px;margin-right: -57px; padding: 20px;">
                <div class="card-body" id="boxes">
                    <div style="padding: 50px;">
                        {{-- Company Logo --}}
                        <div style="margin-bottom: 30px; text-align: left;">
                            <img src="{{ asset('storage/uploads/logo/logo.svg') }}"
                                 alt="{{ config('app.name', 'HRMGo') }}"
                                 style="height: 55px; width: auto; object-fit: contain;">
                        </div>

                        <h2 class="text-center">Increment Letter</h2>

                        <div style="display: flex; justify-content: space-between; width: 100%;">
                            <div>
                                <p>To,<br>
                                {{ trim(join(' ', array_filter([$employee->name, $employee->last_name]))) }}<br>
                                {{ $employee->designation->name ?? '' }}<br>
                                {{ $employee->department->name ?? '' }}</p>
                            </div>
                            <div style="text-align: right; flex-shrink: 0; white-space: nowrap;">
                                <p>Date: {{ isset($date) ? $date : \Carbon\Carbon::parse($increment->created_at)->format('d-m-Y') }}</p>
                            </div>
                        </div>


                        <p>Dear {{ trim(join(' ', array_filter([$employee->name]))) }},</p>

                        <p>
                            We are pleased to inform you that in recognition of your continued dedication and performance, 
                            your salary has been revised effective from <strong>{{ $increment->month_of_effective_date }}</strong>.
                        </p>

                        <p>
                            Your new compensation will be 
                            <strong>{{ isset($formattedNewSalary) ? $formattedNewSalary : ('₹' . number_format($increment->new_salary, 2) . '/-') }}</strong> per annum, 
                            an increment of <strong>{{ isset($formattedIncrementAmount) ? $formattedIncrementAmount : ('₹' . number_format($increment->increment_amount, 2) . '/-') }}</strong> 
                            from your previous salary of <strong>{{ isset($formattedOldSalary) ? $formattedOldSalary : ('₹' . number_format($increment->old_salary, 2) . '/-') }}</strong>. 
                            This increment reflects our appreciation of your contributions and our confidence in your continued success with us.
                        </p>

                        <p>
                            Please note that this change will be reflected in your salary from 
                            <strong>{{ $increment->month_of_effective_date }}</strong> onwards.
                        </p>

                        <p>
                            We value your commitment to the organization and look forward to your continued contributions.
                        </p>

                        <br><br>

                        <p>Best regards,</p>

                        <p>
                            <strong>{{ \Auth::user()->name }}</strong><br>
                            <strong>{{ \Auth::user()->designation->name ?? 'HR' }}</strong><br>
                            <strong>{{ $app_name }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    function closeScript() {
        // Only attempt to close if we are in a popup/new window
        if (window.opener && window.opener !== window) {
            setTimeout(function () {
                window.close();
            }, 1000);
        }
    }

    $(window).on('load', function () {
        var element = document.getElementById('boxes');
                    var opt = {
            filename: 'Increment_Letter_{{ isset($employeeFirstName) ? $employeeFirstName : (explode(" ", $employee->full_name)[0] ?? $employee->full_name) }}',
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 4, dpi: 72, letterRendering: true },
            jsPDF: { unit: 'in', format: 'A4' }
        };

        html2pdf().set(opt).from(element).save().then(closeScript);
    });
</script>
@endpush
