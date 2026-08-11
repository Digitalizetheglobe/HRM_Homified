<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Experience Certificate') }}</title>
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
            margin-bottom: 25px;
        }
        .title-section h2 {
            font-weight: bold;
            color: #000000;
            margin: 0;
            font-size: 26px;
            text-transform: uppercase;
        }
        .subtitle-section {
            text-align: center;
            margin-bottom: 45px;
        }
        .subtitle-section h3 {
            font-weight: bold;
            color: #000000;
            margin: 0;
            font-size: 18px;
        }
        .content-section {
            font-size: 15px;
            line-height: 1.8;
            text-align: justify;
        }
        .content-section p {
            margin-top: 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
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

    // Base64 encode the letterhead image
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
        <p style="margin: 0 0 15px 0;"><strong>Date:</strong> {{ $headerDate }}</p>
        <p style="margin: 0;">
            <strong>Address :</strong> 301, SV9 Corner,<br>
            choudhary Park,<br>
            Wakad - 411057
        </p>
    </div>

    {{-- Title --}}
    <div class="title-section">
        <h2>Experience Letter</h2>
    </div>

    {{-- Subtitle --}}
    <div class="subtitle-section">
        <h3>To Whomsoever it may concern</h3>
    </div>

    {{-- Content --}}
    <div class="content-section">
        <p>This is to certify that {{ $genderPrefix }} {{ $fullName }} was employed with <strong>Homified Consultants Pvt Ltd</strong> as <strong>{{ $designation }}</strong> from <strong>{{ $joiningDate }}</strong> to <strong>{{ $endDate }}</strong>.</p>

        <p>During their tenure with the organization, they performed their duties diligently and professionally. They were responsible for handling the responsibilities assigned to them and maintained satisfactory Performance and conduct throughout their employment.</p>

        <p style="margin-bottom: 50px;">We appreciate their contribution to the organization and wish them every success in their endeavours.</p>

        <p style="margin-bottom: 40px;">Sincerely,</p>

        <p style="margin-bottom: 5px;"><strong>Authorized Signatory</strong></p>
        <p style="margin-bottom: 0;">Homified Consultants Pvt Ltd</p>
    </div>
</div>
</body>
</html>
