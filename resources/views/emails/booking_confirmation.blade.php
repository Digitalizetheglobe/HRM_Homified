<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
        }
        .greeting {
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        .booking-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #007bff;
        }
        .booking-details ul {
            list-style-type: none;
            padding-left: 0;
        }
        .booking-details li {
            margin-bottom: 8px;
        }
        .signature {
            margin-top: 30px;
        }
        .logo {
            margin: 20px 0;
            text-align: center;
        }
        .logo img {
            max-width: 200px;
            height: auto;
        }
        .contact-info {
            margin-top: 10px;
        }
        .website {
            margin-top: 5px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="greeting">
            <p>Dear Mr./Ms. {{ $booking->primary_applicant_name }},</p>
            <p>Greetings of the day!</p>
        </div>

        <div class="content">
            <p>We are delighted to welcome you to the <strong>{{ $project->project_name ?? 'N/A' }}</strong> family.</p>

            <p>This is to formally confirm the receipt of a token/booking amount of <strong>₹{{ number_format($firstPayment['amount'] ?? 0, 2) }}/-</strong> towards the booking of your plot at our project <strong>{{ $project->project_name ?? 'N/A' }}</strong>.</p>
        </div>

        <div class="booking-details">
            <h3 style="margin-top: 0;">Booking Details:</h3>
            <ul>
                <li><strong>Project Name:</strong> {{ $project->project_name ?? 'N/A' }}</li>
                <li><strong>Plot No.:</strong> {{ $booking->unit->unit_name ?? 'N/A' }}</li>
                @if($booking->plot_area)
                <li><strong>Plot Area:</strong> {{ number_format($booking->plot_area, 0) }} sq. ft.</li>
                @endif
                <li><strong>Token Amount Received:</strong> ₹{{ number_format($firstPayment['amount'] ?? 0, 2) }}/-</li>
                <li><strong>Mode of Payment:</strong> {{ ucfirst($firstPayment['mode'] ?? 'N/A') }}</li>
            </ul>
        </div>

        <div class="content">
            <p>On behalf of <strong>{{ $project->project_name ?? 'N/A' }}</strong>, we sincerely thank you for choosing us and for placing your trust in this investment. We are proud to welcome you to our growing community of valued customers and look forward to a long and fruitful association.</p>
        </div>

        <div class="signature">
            <p>Warm regards,</p>
            
            <div class="logo">
                @php
                    $logoPath = \App\Models\Utility::GetLogo();
                    $logoUrl = asset('uploads/logo/' . $logoPath);
                @endphp
                @if($logoPath && file_exists(public_path('uploads/logo/' . $logoPath)))
                    <img src="{{ $logoUrl }}" alt="Company Logo" style="max-width: 200px; height: auto;">
                @else
                    <div style="font-size: 24px; font-weight: bold; color: #d32f2f;">
                        RISING SPACES<br>
                        <span style="font-size: 14px; color: #666;">RISE A NEW</span>
                    </div>
                @endif
            </div>

            <div class="contact-info">
                @if($employee)
                    <p><strong>{{ trim(($employee->name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')) }}</strong></p>
                @endif
                <p>Contact Number: {{ $contactNumber }}</p>
                <p class="website">www.risingspaces.in</p>
            </div>
        </div>
    </div>
</body>
</html>
