<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking  - {{ $booking->id }}</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }
        .row{
            display: block; 
            flex-wrap: wrap; 
            margin: 0 -10px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 25px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        .header h1 { 
            color: #2c3e50; 
            margin: 0; 
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        .section { 
            margin-bottom: 25px; 
        }
        .section-title { 
            background-color: #34495e;
            color: white;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 12px;
            border-radius: 3px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .info-value {
            padding: 5px;
            background: #f8f9fa;
            border-radius: 3px;
            min-height: 20px;
        }
        .applicant-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .applicant-section {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background: #fff;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .cost-table th {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .cost-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .cost-table .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
            font-size: 13px;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .payment-table th {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .payment-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .payment-summary {
            background-color: #e8f4f8;
            padding: 15px;
            border: 1px solid #b8d4e8;
            border-radius: 5px;
            margin: 20px 0;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .summary-item.total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 8px;
        }
        .signature-area {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .signature-line {
            width: 250px;
            border-top: 1px solid #666;
            margin-top: 40px;
            margin-bottom: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 10px;
            color: #7f8c8d;
            padding-top: 10px;
            border-top: 1px solid #ecf0f1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .page-break { page-break-before: always; }
        
        /* Print specific styles */
        @media print {
            body { 
                padding: 15px;
                font-size: 11px;
            }
            .no-print { display: none; }
            .header { margin-bottom: 15px; }
            .section { margin-bottom: 15px; }
            .cost-table, .payment-table {
                font-size: 10px;
            }
            .cost-table th, .cost-table td,
            .payment-table th, .payment-table td {
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="row">
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Print / Save as PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                Close
            </button>
        </div>

        <div class="header">
            <h1>BOOKING FORM</h1>
            <div class="subtitle">Official Booking Document</div>
        </div>

        <!-- Project & Unit Information -->
        <div class="section">
            <div class="section-title">PROJECT & UNIT INFORMATION</div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Project</div>
                    <div class="info-value">{{ $booking->project->project_name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Unit</div>
                    <div class="info-value">{{ $booking->unit_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Unit Size</div>
                    <div class="info-value">{{ $booking->unit_size }} sq.ft</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Booking Date</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Applicant Details -->
        <div class="section">
            <div class="section-title">APPLICANT DETAILS</div>
            
            <div class="applicant-container">
                <!-- Primary Applicant -->
                <div class="applicant-section">
                    <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 8px;">Primary Applicant</h3>
                    
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ $booking->primary_applicant_name }}</div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Contact No</div>
                            <div class="info-value">{{ $booking->primary_applicant_contact_no }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $booking->primary_applicant_email }}</div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Occupation</div>
                            <div class="info-value">{{ $booking->primary_applicant_occupation ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Company</div>
                            <div class="info-value">{{ $booking->primary_applicant_company ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Designation</div>
                        <div class="info-value">{{ $booking->primary_applicant_designation ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Birth Date</div>
                            <div class="info-value">{{ $booking->primary_applicant_birth_date ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nationality</div>
                            <div class="info-value">{{ $booking->primary_applicant_nationality ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">PAN No</div>
                            <div class="info-value">{{ $booking->primary_applicant_pan_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Aadhar No</div>
                            <div class="info-value">{{ $booking->primary_applicant_aadhar_no ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Applicant -->
                @if($booking->secondary_applicant_name)
                <div class="applicant-section">
                    <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 8px;">Secondary Applicant</h3>
                    
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">{{ $booking->secondary_applicant_name }}</div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Contact No</div>
                            <div class="info-value">{{ $booking->secondary_applicant_contact_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $booking->secondary_applicant_email ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Occupation</div>
                            <div class="info-value">{{ $booking->secondary_applicant_occupation ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Company</div>
                            <div class="info-value">{{ $booking->secondary_applicant_company ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Designation</div>
                        <div class="info-value">{{ $booking->secondary_applicant_designation ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Birth Date</div>
                            <div class="info-value">{{ $booking->secondary_applicant_birth_date ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nationality</div>
                            <div class="info-value">{{ $booking->secondary_applicant_nationality ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">PAN No</div>
                            <div class="info-value">{{ $booking->secondary_applicant_pan_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Aadhar No</div>
                            <div class="info-value">{{ $booking->secondary_applicant_aadhar_no ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Property Details -->
        <div class="section">
            <div class="section-title">PROPERTY DETAILS</div>
            
            <div class="info-grid">
                @if($booking->plot_area)
                <div class="info-item">
                    <div class="info-label">Plot Area</div>
                    <div class="info-value">{{ $booking->plot_area }} sq.ft</div>
                </div>
                @endif
                
                @if($booking->carpet_area)
                <div class="info-item">
                    <div class="info-label">Carpet Area</div>
                    <div class="info-value">{{ $booking->carpet_area }} sq.ft</div>
                </div>
                @endif
                
                @if($booking->built_up_area)
                <div class="info-item">
                    <div class="info-label">Built Up Area</div>
                    <div class="info-value">{{ $booking->built_up_area }} sq.ft</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="section">
            <div class="section-title">COST BREAKDOWN</div>
            
            <table class="cost-table">
                <tr>
                    <th width="70%">Description</th>
                    <th width="30%">Amount (₹)</th>
                </tr>
                @if($booking->rate_per_sq_ft)
                <tr>
                    <td>Rate per sq.ft</td>
                    <td class="text-right">₹{{ number_format($booking->rate_per_sq_ft, 2) }}</td>
                </tr>
                @endif
                @if($booking->basic_cost)
                <tr>
                    <td>Basic Cost Towards Plot/Unit</td>
                    <td class="text-right">₹{{ number_format($booking->basic_cost, 2) }}</td>
                </tr>
                @endif
                @if($booking->cost_infrastructure)
                <tr>
                    <td>Cost Towards Infrastructure</td>
                    <td class="text-right">₹{{ number_format($booking->cost_infrastructure, 2) }}</td>
                </tr>
                @endif
                @if($booking->gst)
                <tr>
                    <td>GST</td>
                    <td class="text-right">₹{{ number_format($booking->gst, 2) }}</td>
                </tr>
                @endif
                @if($booking->stamp_duty)
                <tr>
                    <td>Stamp Duty</td>
                    <td class="text-right">₹{{ number_format($booking->stamp_duty, 2) }}</td>
                </tr>
                @endif
                @if($booking->registration)
                <tr>
                    <td>Registration</td>
                    <td class="text-right">₹{{ number_format($booking->registration, 2) }}</td>
                </tr>
                @endif
                @if($booking->other)
                <tr>
                    <td>Other Charges</td>
                    <td class="text-right">₹{{ number_format($booking->other, 2) }}</td>
                </tr>
                @endif
                @if($booking->maintenance_cost)
                <tr>
                    <td>Maintenance Cost</td>
                    <td class="text-right">₹{{ number_format($booking->maintenance_cost, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>TOTAL COST</strong></td>
                    <td class="text-right"><strong>₹{{ number_format($booking->total_cost, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <div class="summary-item">
                <span>Total Cost:</span>
                <span class="text-bold">₹{{ number_format($booking->total_cost, 2) }}</span>
            </div>
            <div class="summary-item">
                <span>Remaining Amount:</span>
                <span class="text-bold">₹{{ number_format($booking->remaining, 2) }}</span>
            </div>
        </div>

        <!-- Payment Transactions -->
        <div class="section">
            <div class="section-title">PAYMENT TRANSACTIONS</div>
            
            @php
                $paymentData = is_array($booking->payment_data) ? $booking->payment_data : json_decode($booking->payment_data, true);
            @endphp
            
            @if(!empty($paymentData))
            <table class="payment-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Mode</th>
                        <th width="15%">Date</th>
                        <th width="40%">Transaction Details</th>
                        <th width="25%" class="text-right">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalPaid = 0;
                        $counter = 1;
                    @endphp
                    @foreach($paymentData as $payment)
                        @php
                            $totalPaid += $payment['amount'];
                        @endphp
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment['mode_of_payment'] ?? 'N/A')) }}</td>
                            <td>{{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $payment['payment_detail'] ?? '-' }}</td>
                            <td class="text-right">₹{{ number_format($payment['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background-color: #ecf0f1; font-weight: bold;">
                        <td colspan="4" class="text-right">Total Paid</td>
                        <td class="text-right">₹{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @else
            <p style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                No payment transactions available.
            </p>
            @endif
        </div>

        <!-- Signatures -->
        <div class="section">
            <div class="section-title">AUTHORIZATION</div>
            
            <div style="display: flex; justify-content: space-between; gap: 30px;">
                <div style="flex: 1;">
                    <div class="signature-area">
                        <p><strong>Applicant Signature</strong></p>
                        <div class="signature-line"></div>
                        <p>Date: ___________________</p>
                    </div>
                </div>
                <div style="flex: 1;">
                    <div class="signature-area">
                        <p><strong>Company Authorized Signature</strong></p>
                        <div class="signature-line"></div>
                        <p>Date: ___________________</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on: {{ \Carbon\Carbon::now()->format('M d, Y H:i:s') }} | Booking ID: {{ $booking->id }}</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Optional: Auto-print when page loads
            // window.print();
        };
    </script>
</body>
</html>