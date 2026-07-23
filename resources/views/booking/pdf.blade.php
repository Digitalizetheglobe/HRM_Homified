@extends('layouts.contractheader')

@section('page-title')
    {{ __('Booking Form') }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="container">
            <div class="card mt-5" id="printTable" style="margin-left: 50px; margin-right: 50px; padding: 20px;">
                <div class="card-body" id="boxes">
                    <div style="padding: 30px;">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h2 class="text-center">BOOKING FORM</h2>
                            <p class="text-muted">Official Booking Document</p>
                        </div>

                        <!-- Project & Unit Information -->
                        <div class="section mb-4">
                            <h4 class="section-title bg-dark text-white p-2">PROJECT & UNIT INFORMATION</h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>Project:</strong> {{ $booking->project->project_name ?? 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Unit:</strong> {{ $booking->unit->unit_name ?? 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Unit Size:</strong> {{ $booking->unit_size }} sq.ft
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Booking Date:</strong> {{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>

                        <!-- Applicant Details -->
                        <div class="section mb-4">
                            <h4 class="section-title bg-dark text-white p-2">APPLICANT DETAILS</h4>
                            
                            <div class="row">
                                <!-- Primary Applicant -->
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">Primary Applicant</h5>
                                    
                                    <div class="mb-2"><strong>Name:</strong> {{ $booking->primary_applicant_name }}</div>
                                    <div class="mb-2"><strong>Contact No:</strong> {{ $booking->primary_applicant_contact_no }}</div>
                                    <div class="mb-2"><strong>Email:</strong> {{ $booking->primary_applicant_email }}</div>
                                    <div class="mb-2"><strong>Occupation:</strong> {{ $booking->primary_applicant_occupation ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Company:</strong> {{ $booking->primary_applicant_company ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Designation:</strong> {{ $booking->primary_applicant_designation ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Birth Date:</strong> {{ $booking->primary_applicant_birth_date ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Nationality:</strong> {{ $booking->primary_applicant_nationality ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>PAN No:</strong> {{ $booking->primary_applicant_pan_no ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Aadhar No:</strong> {{ $booking->primary_applicant_aadhar_no ?? 'N/A' }}</div>
                                </div>

                                <!-- Secondary Applicant -->
                                @if($booking->secondary_applicant_name)
                                <div class="col-md-6">
                                    <h5 class="border-bottom pb-2">Secondary Applicant</h5>
                                    
                                    <div class="mb-2"><strong>Name:</strong> {{ $booking->secondary_applicant_name }}</div>
                                    <div class="mb-2"><strong>Contact No:</strong> {{ $booking->secondary_applicant_contact_no ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Email:</strong> {{ $booking->secondary_applicant_email ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Occupation:</strong> {{ $booking->secondary_applicant_occupation ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Company:</strong> {{ $booking->secondary_applicant_company ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Designation:</strong> {{ $booking->secondary_applicant_designation ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Birth Date:</strong> {{ $booking->secondary_applicant_birth_date ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Nationality:</strong> {{ $booking->secondary_applicant_nationality ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>PAN No:</strong> {{ $booking->secondary_applicant_pan_no ?? 'N/A' }}</div>
                                    <div class="mb-2"><strong>Aadhar No:</strong> {{ $booking->secondary_applicant_aadhar_no ?? 'N/A' }}</div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Cost Breakdown -->
                        <div class="section mb-4">
                            <h4 class="section-title bg-dark text-white p-2">COST BREAKDOWN</h4>
                            
                            <table class="table table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="70%">Description</th>
                                        <th width="30%" class="text-right">Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($booking->plot_area)
                                    <tr>
                                        <td>Plot Area</td>
                                        <td class="text-right">{{ $booking->plot_area }} sq.ft</td>
                                    </tr>
                                    @endif
                                    @if($booking->carpet_area)
                                    <tr>
                                        <td>Carpet Area</td>
                                        <td class="text-right">{{ $booking->carpet_area }} sq.ft</td>
                                    </tr>
                                    @endif 
                                    @if($booking->built_up_area)
                                    <tr>
                                        <td>Built Up Area</td>
                                        <td class="text-right">{{ $booking->built_up_area }} sq.ft</td>
                                    </tr>
                                    @endif               
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
                                    @if($booking->agreement_cost)
                                    <tr>
                                        <td>Agreement Cost</td>
                                        <td class="text-right">₹{{ number_format($booking->agreement_cost, 2) }}</td>
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
                                    @if($booking->legal_charges)
                                    <tr>
                                        <td>Legal Charges</td>
                                        <td class="text-right">₹{{ number_format($booking->legal_charges, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr class="table-primary">
                                        <td><strong>TOTAL COST</strong></td>
                                        <td class="text-right"><strong>₹{{ number_format($booking->total_cost, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Payment Transactions -->
                        <div class="section mb-4">
                            <h4 class="section-title bg-dark text-white p-2">PAYMENT TRANSACTIONS</h4>
                            
                            @php
                                $paymentData = is_array($booking->payment_data) ? $booking->payment_data : json_decode($booking->payment_data, true);
                            @endphp
                            
                            @if(!empty($paymentData))
                            <table class="table table-bordered">
                                <thead class="thead-dark">
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
                                            <td>{{ ucfirst(str_replace('_', ' ', $payment['mode'] ?? 'N/A')) }}</td>
                                            <td>{{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('M d, Y') : 'N/A' }}</td>
                                            <td>{{ $payment['payment_detail'] ?? '-' }}</td>
                                            <td class="text-right">₹{{ number_format($payment['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-info">
                                        <td colspan="4" class="text-right"><strong>Total Paid</strong></td>
                                        <td class="text-right"><strong>₹{{ number_format($totalPaid, 2) }}</strong></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td colspan="4" class="text-right"><strong>Remaining Amount</strong></td>
                                        <td class="text-right"><strong>₹{{ number_format($booking->remaining, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-info text-center">
                                No payment transactions available.
                            </div>
                            @endif
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-5 pt-3 border-top">
                            <p class="text-muted small">
                                This is a computer-generated document. No signature is required.<br>
                                Generated on: {{ \Carbon\Carbon::now()->format('M d, Y H:i:s') }} | Booking ID: {{ $booking->id }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    function closeScript() {
        setTimeout(function () {
            window.open(window.location, '_self').close();
        }, 1000);
    }

    $(window).on('load', function () {
        var element = document.getElementById('boxes');
        var opt = {
            filename: 'Booking_Form_{{ $booking->id }}_{{ $booking->primary_applicant_name }}',
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 4, dpi: 72, letterRendering: true },
            jsPDF: { unit: 'in', format: 'A4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(closeScript);
    });
</script>
@endpush