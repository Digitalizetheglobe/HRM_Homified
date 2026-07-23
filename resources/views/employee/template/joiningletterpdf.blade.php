@extends('layouts.contractheader')
@section('page-title')
    {{ __('Joining Letter') }}
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
        <h2 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 24px;">Offer Letter</h2>
    </div>

    {{-- Date and Recipient --}}
    <div style="display: flex; justify-content: space-between; font-family: Arial, sans-serif; font-size: 14px; margin-bottom: 30px; color: #000;">
        <div>
            <p style="margin: 0 0 5px 0;"><strong>To,</strong></p>
            <p style="margin: 0 0 5px 0;">{{ trim(join(' ', array_filter([$employees->name, $employees->last_name]))) }}</p>
            <p style="margin: 0 0 5px 0;">{{ !empty($employees->work_location) ? $employees->work_location : 'Pune' }}</p>
        </div>
        <div style="flex-shrink: 0; white-space: nowrap; text-align: right;">
            <p style="margin: 0;"><strong>Date :</strong> {{ !empty($employees->company_doj) ? date('jS F Y', strtotime($employees->company_doj)) : '' }}</p>
        </div>
    </div>

    {{-- Content --}}
    <div style="font-family: Arial, sans-serif; font-size: 14px; color: #000; line-height: 1.6;">
        <p style="margin-bottom: 20px;">Dear {{ trim(join(' ', array_filter([$employees->name]))) }},</p>

        <p style="margin-bottom: 20px; text-align: justify;">With reference to your application and the subsequent interview process, we are pleased to offer you the position of <strong>"{{ !empty($employees->designation->name) ? $employees->designation->name : '' }}"</strong>  in <strong>"{{ !empty($employees->department->name) ? $employees->department->name : '' }}"</strong> at our organization. Your monthly salary will be ₹ <strong>{{ !empty($employees->salary) ? number_format($employees->salary) : '' }} /-</strong> as mutually agreed upon during the interview.</p>

        <p style="margin-bottom: 20px;">Your joining date will be <strong>{{ !empty($employees->company_doj) ? date('jS F Y', strtotime($employees->company_doj)) : '' }}</strong>.</p>

        <p style="margin-bottom: 15px;">Kindly bring the following documents on your date of joining for completion of the onboarding process:</p>
        <ol style="margin-bottom: 20px; padding-left: 20px;">
            <li style="margin-bottom: 5px;">Educational Certificates (SSC, HSC, Graduation, Diploma/Degree, Post-Graduation)</li>
            <li style="margin-bottom: 5px;">Relieving/Experience Letters from your last two organizations</li>
            <li style="margin-bottom: 5px;">Last 3 months' Salary Slips</li>
            <li style="margin-bottom: 5px;">PAN Card and Aadhar Card</li>
            <li style="margin-bottom: 5px;">Two Passport Size Photographs</li>
            <li style="margin-bottom: 5px;">Bank Account Details</li>
        </ol>

        <p style="margin-bottom: 30px;">We warmly welcome you to the team and look forward to a successful and long-term association with you.</p>

        <p style="margin-bottom: 40px;">Best regards,</p>
        <div style="margin-top: 5px;">
            @php
                $signPath = storage_path('uploads/logo/sign.png');
                $signSrc = file_exists($signPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($signPath)) : '';
            @endphp
            @if($signSrc)
                <img src="{{ $signSrc }}" alt="Signature" style="height: 50px; width: auto; object-fit: contain;">
            @endif
        </div>

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
                filename: '{{$employees->name}}-OfferLetter.pdf',
                image: {type: 'jpeg', quality: 1},
                html2canvas: {scale: 2, useCORS: true},
                jsPDF: {unit: 'in', format: 'A4'}
            };

            html2pdf().set(opt).from(element).save().then(closeScript);
        }, 500);
    });
</script>
@endpush
