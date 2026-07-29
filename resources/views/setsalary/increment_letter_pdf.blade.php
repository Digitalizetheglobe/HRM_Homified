@extends('layouts.contractheader')

@section('page-title')
    {{ __('Increment Letter') }}
@endsection

@section('content')
@php
    $fullName = trim(implode(' ', array_filter([$employee->name, $employee->middle_name, $employee->last_name])));
    $empId = !empty($employee->employee_id) ? $employee->employee_id : $employee->custom_id;
    $designation = !empty($employee->designation->name) ? $employee->designation->name : '';
    
    // Effective Date
    $effectiveDate = '';
    if (!empty($increment->month_of_effective_date)) {
        try {
            $effectiveDate = date('jS F Y', strtotime($increment->month_of_effective_date));
        } catch (\Exception $e) {
            $effectiveDate = $increment->month_of_effective_date;
        }
    } else {
        $effectiveDate = date('jS F Y');
    }

    // Download Date (Current Date when downloaded)
    $downloadDate = date('jS F Y');

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
            <p style="margin: 0 0 15px 0;"><strong>Date:</strong> {{ $downloadDate }}</p>
            <p style="margin: 0; text-align: right; display: inline-block; max-width: 300px;">
                <strong>Address :</strong> 301, SV9 Corner,<br>
                choudhary Park,<br>
                 Wakad - 411057
            </p>
        </div>

        {{-- Title --}}
        <div style="text-align: center; margin-bottom: 35px;">
            <h2 style="font-family: Arial, sans-serif; font-weight: bold; color: #000; margin: 0; font-size: 26px; text-transform: uppercase; letter-spacing: 0.5px;">Appraisal Letter</h2>
        </div>

        {{-- Recipient Info --}}
        <div style="font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.5; margin-bottom: 35px;">
            <p style="margin: 0 0 5px 0;">Dear {{ $fullName }},</p>
            <p style="margin: 0 0 5px 0;">Emp ID : {{ $empId }}</p>
            <p style="margin: 0;">Designation: {{ $designation }}</p>
        </div>

        {{-- Content --}}
        <div style="font-family: Arial, sans-serif; font-size: 15px; color: #000; line-height: 1.8; text-align: justify;">
            
            <p style="margin-bottom: 25px;">We are pleased to inform you that, based on your performance and contribution to the organization, Your salary has been revised to <strong>{{ !empty($increment->new_salary) ? number_format($increment->new_salary) : '' }}</strong> per month, effective from <strong>{{ $effectiveDate }}</strong>.</p>

            <p style="margin-bottom: 25px;">We appreciate your dedication and hard work and look forward to your continued contribution and Success with the company.</p>

            <p style="margin-bottom: 50px;">Congratulations , and wish you all the best .</p>

            <p style="margin-bottom: 40px;">Sincerely,</p>

            <p style="margin-bottom: 5px;"><strong>Mr. Karamdeep Singh Sethi (Chairman)</strong></p>
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
                filename: 'Increment_Letter_{{ isset($employeeFirstName) ? $employeeFirstName : (explode(" ", $employee->full_name)[0] ?? $employee->full_name) }}',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'A4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(closeScript);
        }, 500);
    });
</script>
@endpush
