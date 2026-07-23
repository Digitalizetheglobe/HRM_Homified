@extends('layouts.contractheader')
@section('page-title')
    {{ __('Experience Certificate') }}
@endsection

@section('content')
<div style="padding: 20px; background-color: #f4f4f4; text-align: center;">
    <div id="boxes" style="width: 800px; max-width: 100%; margin: 0 auto; background-color: #ffffff; padding: 50px; text-align: left;">

        {{-- Company Logo --}}
        <div style="margin-bottom: 30px;">
            <img src="{{ asset('storage/uploads/logo/logo.svg') }}"
                 alt="{{ config('app.name', 'HRMGo') }}"
                 style="height: 55px; width: auto; object-fit: contain;">
        </div>

        {{-- Title --}}
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 24px;">Experience Certificate</h2>
        </div>

        {{-- Date and Recipient --}}
        <div style="display: flex; justify-content: space-between; font-family: Arial, sans-serif; font-size: 14px; margin-bottom: 30px; color: #000;">
            <div>
                <p style="margin: 0 0 5px 0;"><strong>To,</strong></p>
                <p style="margin: 0 0 5px 0;">{{ trim(join(' ', array_filter([$employees->name, $employees->middle_name, $employees->last_name]))) }}</p>
                <p style="margin: 0 0 5px 0;">{{ !empty($employees->work_location) ? $employees->work_location : 'Pune' }}</p>
            </div>
            <div style="flex-shrink: 0; white-space: nowrap; text-align: right;">
                <p style="margin: 0;"><strong>Date :</strong> {{ date('jS F Y') }}</p>
            </div>
        </div>

        {{-- Content --}}
        <div style="font-family: Arial, sans-serif; font-size: 14px; color: #000; line-height: 1.6;">
            
            <p style="margin-bottom: 20px;">Dear {{ trim(join(' ', array_filter([$employees->name]))) }},</p>
            
            @php
                // Fetch resignation or termination dates
                $resignation = \App\Models\Resignation::where('employee_id', $employees->id)->first();
                $termination = \App\Models\Termination::where('employee_id', $employees->id)->first();
                
                $endDate = '';
                if (!empty($resignation->resignation_date)) {
                    $endDate = date('jS F Y', strtotime($resignation->resignation_date));
                } elseif (!empty($termination->termination_date)) {
                    $endDate = date('jS F Y', strtotime($termination->termination_date));
                } else {
                    $endDate = 'Present'; // If they haven't left yet
                }

                $noticeDate = '';
                if (!empty($resignation->notice_date)) {
                    $noticeDate = date('jS F Y', strtotime($resignation->notice_date));
                }
            @endphp

            <p style="margin-bottom: 20px; text-align: justify;">This is to formally acknowledge the receipt and acceptance of your resignation letter dated <strong>{{ !empty($noticeDate) ? $noticeDate : '______' }}</strong>, from your position as <strong>{{ !empty($employees->designation->name) ? $employees->designation->name : '' }}</strong> with <strong>{{ env('APP_NAME') }}</strong>.</p>

            <p style="margin-bottom: 20px; text-align: justify;">You have been relieved from your duties with effect from <strong>{{ $endDate }}</strong>, after serving the required notice period and completing all handover formalities. We confirm that there are no dues pending from your side as of your relieving date.</p>

            <p style="margin-bottom: 20px; text-align: justify;">We appreciate your contributions during your tenure with us and wish you all the best in your future professional endeavors.</p>

            <p style="margin-bottom: 30px;">Thank you for your service and dedication.</p>

            <p style="margin-bottom: 40px;">Sincerely,</p>

            <p style="margin-bottom: 5px;"><strong>Authorized Signatory</strong></p>
            <p style="margin-bottom: 0;">{{ env('APP_NAME') }}</p>
        </div>

    </div>
</div>
@endsection

@push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    function closeScript() {
        if (window.opener && window.opener !== window) {
            setTimeout(function () {
                window.close();
            }, 1000);
        }
    }

    $(window).on('load', function () {
        setTimeout(function() {
            var element = document.getElementById('boxes');
            var opt = {
                filename: '{{$employees->name}}-ExperienceCertificate.pdf',
                image: {type: 'jpeg', quality: 1},
                html2canvas: {scale: 2, useCORS: true},
                jsPDF: {unit: 'in', format: 'A4'}
            };

            html2pdf().set(opt).from(element).save().then(closeScript);
        }, 500);
    });
</script>
@endpush
