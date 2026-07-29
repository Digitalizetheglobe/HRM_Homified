@extends('layouts.contractheader')
@section('page-title')
    {{ __('Experience Certificate') }}
@endsection

@section('content')
@php
    $genderPrefix = 'Mr.';
    if (isset($employees->gender) && in_array(strtolower($employees->gender), ['female', 'f'])) {
        $genderPrefix = 'Ms.';
    }

    $fullName = trim(implode(' ', array_filter([$employees->name, $employees->middle_name, $employees->last_name])));
    $designation = !empty($employees->designation->name) ? $employees->designation->name : '';
    $joiningDate = !empty($employees->company_doj) ? date('jS F Y', strtotime($employees->company_doj)) : '';

    // Fetch resignation or termination dates
    $resignation = \App\Models\Resignation::where('employee_id', $employees->id)->first();
    $termination = \App\Models\Termination::where('employee_id', $employees->id)->first();

    $endDate = '';
    $headerDate = '';
    if (!empty($resignation->resignation_date)) {
        $endDate = date('jS F Y', strtotime($resignation->resignation_date));
        $headerDate = date('F Y', strtotime($resignation->resignation_date));
    } elseif (!empty($termination->termination_date)) {
        $endDate = date('jS F Y', strtotime($termination->termination_date));
        $headerDate = date('F Y', strtotime($termination->termination_date));
    } else {
        $endDate = 'Present';
        $headerDate = date('F Y');
    }

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
            <p style="margin: 0 0 15px 0;"><strong>Date:</strong> {{ $headerDate }}</p>
            <p style="margin: 0; text-align: right; display: inline-block; max-width: 300px;">
                <strong>Address :</strong> 301, SV9 Corner,<br>
                choudhary Park,<br>
                 Wakad - 411057
            </p>
        </div>

        {{-- Title --}}
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 26px; text-transform: uppercase;">Experience Letter</h2>
        </div>

        {{-- Subtitle --}}
        <div style="text-align: center; margin-bottom: 45px;">
            <h3 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 18px;">To Whomsoever it may concern</h3>
        </div>

        {{-- Content --}}
        <div style="font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.8; text-align: justify;">
            
            <p style="margin-bottom: 30px;">This is to certify that {{ $genderPrefix }} {{ $fullName }} was employed with <strong>Homified Consultants Pvt Ltd</strong> as <strong>{{ $designation }}</strong> from <strong>{{ $joiningDate }}</strong> to <strong>{{ $endDate }}</strong>.</p>

            <p style="margin-bottom: 30px;">During their tenure with the organization, they performed their duties diligently and professionally. They were responsible for handling the responsibilities assigned to them and maintained satisfactory Performance and conduct throughout their employment.</p>

            <p style="margin-bottom: 50px;">We appreciate their contribution to the organization and wish them every success in their endeavours.</p>

            <p style="margin-bottom: 40px;">Sincerely,</p>

            <p style="margin-bottom: 5px;"><strong>Authorized Signatory</strong></p>
            <p style="margin-bottom: 0;">Homified Consultants Pvt Ltd</p>
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
                filename: '{{$employees->name}}-ExperienceCertificate.pdf',
                image: {type: 'jpeg', quality: 1},
                html2canvas: {scale: 2, useCORS: true},
                jsPDF: {unit: 'in', format: 'A4', orientation: 'portrait'}
            };

            html2pdf().set(opt).from(element).save().then(closeScript);
        }, 500);
    });
</script>
@endpush
