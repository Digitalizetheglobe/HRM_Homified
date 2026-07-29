@extends('layouts.contractheader')
@section('page-title')
    {{ __('Appointment Letter') }}
@endsection

@section('content')
@php
    // Extract first name
    $firstName = explode(' ', trim($employees->name))[0];
    
    // Choose gender prefix (Mr. or Ms.)
    $genderPrefix = 'Mr.';
    if (isset($employees->gender) && in_array(strtolower($employees->gender), ['female', 'f'])) {
        $genderPrefix = 'Ms.';
    }
    
    $joiningDate = !empty($employees->company_doj) ? date('jS F Y', strtotime($employees->company_doj)) : date('jS F Y');

    // Base64 encode the letterhead image to ensure it renders correctly in PDF without CORS or space character issues
    $letterheadPath = public_path('uploads/logo/appointment -letter.png');
    $letterheadBase64 = '';
    if (file_exists($letterheadPath)) {
        $type = pathinfo($letterheadPath, PATHINFO_EXTENSION);
        $data = file_get_contents($letterheadPath);
        $letterheadBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
@endphp

<div style="padding: 10px; background-color: #f4f4f4; text-align: center;">
    <div id="boxes" style="width: 800px; height: 1120px; box-sizing: border-box; margin: 0 auto; background-color: #ffffff; background-image: url('{{ $letterheadBase64 }}'); background-size: 100% 100%; background-repeat: no-repeat; padding: 200px 40px 80px 40px; text-align: left;">

        {{-- Date and Address --}}
        <div style="text-align: right; font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.4; margin-bottom: 40px;">
            <p style="margin: 0 0 15px 0;"><strong>Date:</strong> {{ $joiningDate }}</p>
            <p style="margin: 0; text-align: right; display: inline-block; max-width: 300px;">
                <strong>Address :</strong> 301, SV9 Corner,<br>
                choudhary Park,<br>
                 Wakad - 411057
            </p>
        </div>

        {{-- Title --}}
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 26px;">Appointment Letter</h2>
        </div>

        {{-- Content --}}
        <div style="font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.8;">
            <p style="margin-bottom: 30px;">Dear {{ $genderPrefix }} {{ $firstName }},</p>

            <p style="margin-bottom: 20px; text-align: justify;">We are pleased to appoint you the position of <strong>{{ !empty($employees->designation->name) ? $employees->designation->name : '' }}</strong> at <strong>Homified Consultants Private Limited</strong>.</p>
            
            <p style="margin-bottom: 35px; text-align: justify;">Your Skills and Experience make you a valuable addition to our team, and we look forward to Your Contributions starting on <strong>{{ $joiningDate }}</strong>.</p>

            <p style="margin-bottom: 50px;">You will receive a per Month Salary of <strong>{{ !empty($employees->salary) ? number_format($employees->salary) : '' }}</strong>.</p>

            <p style="margin-bottom: 45px;">Sincerely,</p>

            <p style="margin-bottom: 0;"><strong>Mr. Karamdeep Singh Sethi (Chairman)</strong></p>
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
                margin: 0,
                filename: '{{$employees->name}}-AppointmentLetter.pdf',
                image: {type: 'jpeg', quality: 1},
                html2canvas: {scale: 2, useCORS: true},
                jsPDF: {unit: 'in', format: 'A4', orientation: 'portrait'}
            };

            html2pdf().set(opt).from(element).save().then(closeScript);
        }, 500);
    });
</script>
@endpush
