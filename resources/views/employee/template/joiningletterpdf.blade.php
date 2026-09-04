<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Appointment Letter') }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000000;
        }
        .letterhead-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .page-container {
            padding: 200px 40px 80px 40px;
            box-sizing: border-box;
        }
        .header-date-address {
            text-align: right;
            font-size: 15px;
            line-height: 1.4;
            margin-bottom: 40px;
        }
        .title-section {
            text-align: center;
            margin-bottom: 40px;
        }
        .title-section h2 {
            font-weight: bold;
            color: #000000;
            margin: 0;
            font-size: 26px;
        }
        .content-section {
            font-size: 15px;
            line-height: 1.8;
            text-align: justify;
        }
        .content-section p {
            margin-top: 0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
@php
    $fullName = trim(implode(' ', array_filter([
        $employees->name ?? '',
        $employees->middle_name ?? '',
        $employees->last_name ?? '',
    ])));
    
    // Choose gender prefix (Mr. or Ms.)
    $genderPrefix = 'Mr.';
    if (isset($employees->gender) && in_array(strtolower($employees->gender), ['female', 'f'])) {
        $genderPrefix = 'Ms.';
    }
    
    $joiningDate = !empty($employees->company_doj) ? date('jS F Y', strtotime($employees->company_doj)) : date('jS F Y');

    // Base64 encode the letterhead image to ensure it renders correctly in PDF without CORS or path issues
    $letterheadPath = public_path('uploads/logo/appointment -letter.png');
    $letterheadBase64 = '';
    if (file_exists($letterheadPath)) {
        $type = pathinfo($letterheadPath, PATHINFO_EXTENSION);
        $data = file_get_contents($letterheadPath);
        $letterheadBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
@endphp

@if(!empty($letterheadBase64))
    <img src="{{ $letterheadBase64 }}" class="letterhead-bg" />
@endif

<div class="page-container">
    {{-- Date and Address --}}
    <div class="header-date-address">
        <p style="margin: 0 0 15px 0;"><strong>Date:</strong> {{ $joiningDate }}</p>
        <p style="margin: 0;">
            <strong>Address :</strong> 301, SV9 Corner,<br>
            choudhary Park,<br>
            Wakad - 411057
        </p>
    </div>

    {{-- Title --}}
    <div class="title-section">
        <h2>Appointment Letter</h2>
    </div>

    {{-- Content --}}
    <div class="content-section">
        <p style="margin-bottom: 30px;">Dear {{ $genderPrefix }} {{ $fullName }},</p>

        <p style="margin-bottom: 20px;">We are pleased to appoint you the position of <strong>{{ !empty($employees->designation->name) ? $employees->designation->name : '' }}</strong> at <strong>Homified Consultants Private Limited</strong>.</p>
        
        <p style="margin-bottom: 35px;">Your Skills and Experience make you a valuable addition to our team, and we look forward to Your Contributions starting on <strong>{{ $joiningDate }}</strong>.</p>

        <p style="margin-bottom: 50px;">You will receive a per Month Salary of <strong>{{ !empty($employees->salary) ? number_format($employees->salary) : '' }}</strong>.</p>

        <p style="margin-bottom: 45px;">Sincerely,</p>

        <p style="margin-bottom: 0;"><strong>Mr. Karamdeep Singh Sethi (Chairman)</strong></p>
    </div>
</div>
</body>
</html>
