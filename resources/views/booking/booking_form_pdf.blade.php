<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Form - {{ $booking->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c5282;
        }
        .document-title {
            font-size: 20px;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #2c5282;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .signature-area {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ env('APP_NAME', 'Our Company') }}</div>
        <div class="document-title">BOOKING FORM</div>
        <div>Date: {{ date('d/m/Y') }}</div>
    </div>

    <!-- Booking Details -->
    <div class="section">
        <div class="section-title">Booking Information</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Booking ID:</span> {{ $booking->id }}
            </div>
            <div class="info-item">
                <span class="info-label">Booking Date:</span> {{ $booking->booking_date ? date('d/m/Y', strtotime($booking->booking_date)) : 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Project:</span> {{ $booking->project_name ?? $booking->project->project_name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Unit:</span> {{ $booking->unit_name ?? $booking->unit->unit_name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Unit Size:</span> {{ $booking->unit_size ?? 'N/A' }} sq.ft.
            </div>
            <div class="info-item">
                <span class="info-label">Sales Executive:</span> {{ $booking->employee->full_name ?? 'N/A' }}
            </div>
        </div>
    </div>

    <!-- Applicant Information -->
    <div class="section">
        <div class="section-title">Primary Applicant Details</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name:</span> {{ $booking->primary_applicant_name ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Contact No:</span> {{ $booking->primary_applicant_contact_no ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span> {{ $booking->primary_applicant_email ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Occupation:</span> {{ $booking->primary_applicant_occupation ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Company:</span> {{ $booking->primary_applicant_company ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Designation:</span> {{ $booking->primary_applicant_designation ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Date of Birth:</span> {{ $booking->primary_applicant_birth_date ? date('d/m/Y', strtotime($booking->primary_applicant_birth_date)) : 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Nationality:</span> {{ $booking->primary_applicant_nationality ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">PAN No:</span> {{ $booking->primary_applicant_pan_no ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Aadhar No:</span> {{ $booking->primary_applicant_aadhar_no ?? 'N/A' }}
            </div>
        </div>
    </div>

    @if($booking->secondary_applicant_name)
    <div class="section">
        <div class="section-title">Secondary Applicant Details</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name:</span> {{ $booking->secondary_applicant_name }}
            </div>
            <div class="info-item">
                <span class="info-label">Contact No:</span> {{ $booking->secondary_applicant_contact_no ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span> {{ $booking->secondary_applicant_email ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Occupation:</span> {{ $booking->secondary_applicant_occupation ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Company:</span> {{ $booking->secondary_applicant_company ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Designation:</span> {{ $booking->secondary_applicant_designation ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Date of Birth:</span> {{ $booking->secondary_applicant_birth_date ? date('d/m/Y', strtotime($booking->secondary_applicant_birth_date)) : 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Nationality:</span> {{ $booking->secondary_applicant_nationality ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">PAN No:</span> {{ $booking->secondary_applicant_pan_no ?? 'N/A' }}
            </div>
            <div class="info-item">
                <span class="info-label">Aadhar No:</span> {{ $booking->secondary_applicant_aadhar_no ?? 'N/A' }}
            </div>
        </div>
    </div>
    @endif

    <!-- Financial Details -->
    <div class="section">
        <div class="section-title">Financial Details</div>
        <table class="table">
            <tr>
                <th>Description</th>
                <th>Amount (₹)</th>
            </tr>
            @if($booking->rate_per_sq_ft)
            <tr>
                <td>Rate Per sq Ft.</td>
                <td>{{ number_format($booking->rate_per_sq_ft, 2) }}</td>
            </tr>
            @endif
            @if($booking->unit_size)
            <tr>
                <td>Plot Area (sq.ft)</td>
                <td>{{ number_format($booking->unit_size, 2) }}</td>
            </tr>
            @endif
            @if($booking->basic_cost)
            <tr>
                <td>Basic Cost ( Rate Per Sq Ft. * Plot Area (sq.ft) )</td>
                <td>{{ number_format($booking->basic_cost, 2) }}</td>
            </tr>
            @endif
            @if($booking->cost_infrastructure)
            <tr>
                <td>Infrastructure Cost</td>
                <td>{{ number_format($booking->cost_infrastructure, 2) }}</td>
            </tr>
            @endif
            @if($booking->gst)
            <tr>
                <td>GST</td>
                <td>{{ number_format($booking->gst, 2) }}</td>
            </tr>
            @endif
            @if($booking->stamp_duty)
            <tr>
                <td>Stamp Duty</td>
                <td>{{ number_format($booking->stamp_duty, 2) }}</td>
            </tr>
            @endif
            @if($booking->registration)
            <tr>
                <td>Registration Charges</td>
                <td>{{ number_format($booking->registration, 2) }}</td>
            </tr>
            @endif
            @if($booking->legal_charges)
            <tr>
                <td>Legal Charges</td>
                <td>{{ number_format($booking->legal_charges, 2) }}</td>
            </tr>
            @endif
            @if($booking->other)
            <tr>
                <td>Other Charges</td>
                <td>{{ number_format($booking->other, 2) }}</td>
            </tr>
            @endif
            @if($booking->maintenance_cost)
            <tr>
                <td>Maintenance Cost</td>
                <td>{{ number_format($booking->maintenance_cost, 2) }}</td>
            </tr>
            @endif
            <tr style="font-weight: bold;">
                <td>Total Cost</td>
                <td>{{ number_format($booking->total_cost, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Details -->
    @if($booking->payment_data && is_array($booking->payment_data) && count($booking->payment_data) > 0)
    <div class="section">
        <div class="section-title">Payment Details</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Mode</th>
                    <th>Date</th>
                    <th>Details</th>
                    <th>Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->payment_data as $payment)
                <tr>
                    <td>{{ ucfirst($payment['mode'] ?? 'N/A') }}</td>
                    <td>{{ isset($payment['date']) ? date('d/m/Y', strtotime($payment['date'])) : 'N/A' }}</td>
                    <td>{{ $payment['payment_detail'] ?? 'N/A' }}</td>
                    <td>{{ number_format($payment['amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: right;">Total Paid:</td>
                    <td>{{ number_format(collect($booking->payment_data)->sum('amount'), 2) }}</td>
                </tr>
                @if($booking->remaining)
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: right;">Remaining Balance:</td>
                    <td>{{ number_format($booking->remaining, 2) }}</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
    @endif

</body>
</html>