@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    {{ Form::model($bookingForm, ['route' => ['booking.update', $bookingForm->id], 'method' => 'PUT', 'id' => 'bookingForm']) }}

    @if (\Auth::user()->type != 'employee')
        <div class="form-group col-md-12">
            {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}
            {!! Form::select('employee_id', $employees, $bookingForm->employee_id, [
                'class' => 'form-control select2',
                'id' => 'choices-multiple',
                'placeholder' => __('Select Employee'),
            ]) !!}
        </div>
    @endif
    
    <!-- Project and Enquiry Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Select Project and Enquiry</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="form-group col-md-6">
                    {{ Form::label('project_id', __('Project'), ['class' => 'col-form-label']) }}
                    <select name="project_id" id="projectDropdown" class="form-control select2" required>
                        <option value="">Select Project</option>
                        @foreach($projects as $id => $name)
                            <option value="{{ $id }}" 
                                {{ $bookingForm->project_id == $id ? 'selected' : '' }}
                                data-type="{{ $projectTypes[$id] ?? '' }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    {{ Form::hidden('project_type', $projectType ?? '', ['id' => 'project_type']) }}
                </div>
                <div class="form-group col-md-6">
                    {{ Form::label('enquiry_id', __('Enquiry'), ['class' => 'col-form-label']) }}
                    <select name="enquiry_id" id="enquiryFilter" class="form-control" 
                        {{ !$bookingForm->project_id ? 'disabled' : '' }} required>
                        <option value="">Select Enquiry</option>
                        @foreach($enquiries as $enquiry)
                            <option value="{{ $enquiry->id }}" {{ $bookingForm->enquiry_id == $enquiry->id ? 'selected' : '' }}>
                                {{ $enquiry->full_name }} ({{ $enquiry->mobile_no }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Personal Info -->
    <div id="step1" class="step">
        <h4>Primary Applicant Details</h4>
        <div class="row">
            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_name', __('Primary Applicant Name'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_name', $bookingForm->primary_applicant_name ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Primary Applicant Name']) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_contact_no', __('Primary Applicant Contact No.'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_contact_no', $bookingForm->primary_applicant_contact_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Contact No.']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_email', __('Primary Applicant Email'), ['class' => 'col-form-label']) }}
                {{ Form::email('primary_applicant_email', $bookingForm->primary_applicant_email ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Email']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_occupation', __('Primary Applicant Occupation'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_occupation', $bookingForm->primary_applicant_occupation ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Occupation']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_company', __('Primary Applicant Company'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_company', $bookingForm->primary_applicant_company ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Company Name']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_designation', __('Primary Applicant Designation'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_designation', $bookingForm->primary_applicant_designation ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Designation']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_birth_date', __('Primary Applicant Birth Date'), ['class' => 'col-form-label']) }}
                {{ Form::date('primary_applicant_birth_date', $bookingForm->primary_applicant_birth_date ?? '', ['class' => 'form-control']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_nationality', __('Primary Applicant Nationality'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_nationality', $bookingForm->primary_applicant_nationality ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Nationality']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_pan_no', __('Primary Applicant PAN No'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_pan_no', $bookingForm->primary_applicant_pan_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter PAN No']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_aadhar_no', __('Primary Applicant Aadhar No'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_aadhar_no', $bookingForm->primary_applicant_aadhar_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Aadhar No']) }}
            </div>
        </div>

        <h4>Secondary Applicant Details</h4>
        <div class="row">
            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_name', __('Secondary Applicant Name'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_name', $bookingForm->secondary_applicant_name ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Secondary Applicant Name']) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_contact_no', __('Secondary Applicant Contact No.'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_contact_no', $bookingForm->secondary_applicant_contact_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Contact No.']) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_email', __('Secondary Applicant Email'), ['class' => 'col-form-label']) }}
                {{ Form::email('secondary_applicant_email', $bookingForm->secondary_applicant_email ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Email']) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_occupation', __('Secondary Applicant Occupation'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_occupation', $bookingForm->secondary_applicant_occupation ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Occupation']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_company', __('Secondary Applicant Company'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_company', $bookingForm->secondary_applicant_company ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Company Name']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_designation', __('Secondary Applicant Designation'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_designation', $bookingForm->secondary_applicant_designation ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Designation']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_birth_date', __('Secondary Applicant Birth Date'), ['class' => 'col-form-label']) }}
                {{ Form::date('secondary_applicant_birth_date', $bookingForm->secondary_applicant_birth_date ?? '', ['class' => 'form-control']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_nationality', __('Secondary Applicant Nationality'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_nationality', $bookingForm->secondary_applicant_nationality ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Nationality']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_pan_no', __('Secondary Applicant PAN No.'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_pan_no', $bookingForm->secondary_applicant_pan_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter PAN No.']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('secondary_applicant_aadhar_no', __('Secondary Applicant Aadhar No.'), ['class' => 'col-form-label']) }}
                {{ Form::text('secondary_applicant_aadhar_no', $bookingForm->secondary_applicant_aadhar_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Aadhar No.']) }}
            </div>
        </div>

        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
    </div>

    <!-- Step 2: Account Info -->
    <div id="step2" class="step hidden">
        <h4>Project and Cost Details</h4>
        <div class="row">
            <div class="form-group col-md-4">
                {{ Form::label('project_id', __('Project Name'), ['class' => 'col-form-label']) }}
                <select name="project_id" id="projectDropdown" class="form-control select2" required disabled>
                    <option value="">Select Project</option>
                    @foreach($projects as $id => $name)
                        <option value="{{ $id }}" {{ $bookingForm->project_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('unit_id', __('Unit Name'), ['class' => 'col-form-label']) }}
                <select name="unit_id" id="unitDropdown" class="form-control" required>
                    <option value="">Select Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" 
                            {{ $bookingForm->unit_id == $unit->id ? 'selected' : '' }}
                            data-size="{{ $unit->unit_size }}">
                            {{ $unit->unit_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Unit Size Display -->
            <div class="form-group col-md-4">
                {{ Form::label('unit_size', __('Unit Size (sq.ft)'), ['class' => 'col-form-label']) }}
                {{ Form::text('unit_size', $bookingForm->unit_size ?? '', ['class' => 'form-control', 'id' => 'unit_size', 'readonly' => true]) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('booking_date', __('Booking Date'), ['class' => 'col-form-label']) }}
                {{ Form::date('booking_date', $bookingForm->booking_date ?? '', ['class' => 'form-control']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('unit_no', __('Unit No'), ['class' => 'col-form-label']) }}
                {{ Form::text('unit_no', $bookingForm->unit_no ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Unit No.']) }}
            </div>

            <!-- Fields based on project type -->
            @if($projectType == 3) <!-- Plotting project -->
                <div class="form-group col-md-4 plotting-field">
                    {{ Form::label('plot_area', __('Plot Area (sq.ft)'), ['class' => 'col-form-label']) }}
                    {{ Form::text('plot_area', $bookingForm->plot_area ?? '', ['class' => 'form-control plotting-calc-input', 'placeholder' => 'Enter Plot Area']) }}
                </div>
            @else <!-- Residential/Commercial -->
                <div class="form-group col-md-4 residential-commercial-field">
                    {{ Form::label('carpet_area', __('Carpet Area (sq.ft)'), ['class' => 'col-form-label']) }}
                    {{ Form::text('carpet_area', $bookingForm->carpet_area ?? '', ['class' => 'form-control residential-commercial-input', 'placeholder' => 'Enter Carpet Area']) }}
                </div>
                <div class="form-group col-md-4 residential-commercial-field">
                    {{ Form::label('built_up_area', __('Built Up Area (sq.ft)'), ['class' => 'col-form-label']) }}
                    {{ Form::text('built_up_area', $bookingForm->built_up_area ?? '', ['class' => 'form-control residential-commercial-result', 'readonly' => true]) }}
                </div>
            @endif

            <div class="form-group col-md-4">
                {{ Form::label('rate_per_sq_ft', __('Rate Per Sq.Ft (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('rate_per_sq_ft', $bookingForm->rate_per_sq_ft ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Rate per Sq.Ft']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('basic_cost', __('Basic Cost Towards Plot/Unit (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('basic_cost', $bookingForm->basic_cost ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Basic Cost']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('cost_infrastructure', __('Cost Towards Infrastructure (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('cost_infrastructure', $bookingForm->cost_infrastructure ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Infrastructure Cost']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('gst', __('GST (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('gst', $bookingForm->gst ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter GST Amount']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('stamp_duty', __('Stamp Duty (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('stamp_duty', $bookingForm->stamp_duty ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Stamp Duty']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('registration', __('Registration (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('registration', $bookingForm->registration ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Registration Cost']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('maintenance_cost', __('Maintenance Cost (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('maintenance_cost', $bookingForm->maintenance_cost ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Maintenance Cost']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('other', __('Other (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('other', $bookingForm->other ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Other Costs']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('total_cost', __('Total Cost (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('total_cost', $bookingForm->total_cost ?? '', ['class' => 'form-control', 'id' => 'total_cost', 'readonly' => true]) }}
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">Back</button>
        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
    </div>

    <!-- Step 3: Confirmation -->
    <div id="step3" class="step hidden">
        <div class="row">
            <!-- Remaining Amount Section -->
            <div class="form-group col-md-4">
                {{ Form::label('confirm_total_cost', __('Total Cost (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('confirm_total_cost', $bookingForm->total_cost ?? '', ['class' => 'form-control', 'id' => 'confirm_total_cost', 'readonly' => true]) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('remaining', __('Remaining Amount (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('remaining', $bookingForm->remaining ?? $bookingForm->total_cost, ['class' => 'form-control', 'id' => 'remaining', 'readonly' => true]) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('total_paid', __('Total Paid Amount (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('total_paid', collect($bookingForm->payment_data ?? [])->sum('amount') ?? '0', ['class' => 'form-control', 'id' => 'total_paid', 'readonly' => true]) }}
            </div>
        </div>

        <!-- Payment Details Section -->
        <h4>Payments:</h4>
        <div id="payment-section">
            @php
                $payments = $bookingForm->payment_data ?? [];
                $paymentLabels = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
            @endphp
            
            @foreach($payments as $index => $payment)
            <div class="payment-entry">
                <h4>{{ ($paymentLabels[$index] ?? ($index+1).'th') }} Payment :</h4>
                <div class="row align-items-center">
                    <!-- Mode of Payment -->
                    <div class="form-group col-md-2">
                        {{ Form::label('mode_of_payment[]', __('Mode of Payment'), ['class' => 'col-form-label']) }}
                        {{ Form::select('mode_of_payment[]', ['cash' => 'Cash', 'upi' => 'UPI', 'online' => 'Online', 'cheque' => 'Cheque'], $payment['mode_of_payment'] ?? null, [
                            'class' => 'form-control mode-of-payment',
                            'disabled' => \Auth::user()->type == 'employee' ? true : false
                        ]) }}
                    </div>
                    
                    <!-- Payment Detail -->
                    <div class="form-group col-md-3 payment-detail" style="{{ ($payment['mode_of_payment'] ?? 'cash') === 'cash' ? 'display:none;' : '' }}">
                        {{ Form::label('payment_detail[]', __('Payment Detail'), ['class' => 'col-form-label']) }}
                        {{ Form::text('payment_detail[]', $payment['payment_detail'] ?? null, [
                            'class' => 'form-control', 
                            'placeholder' => 'Enter Payment Detail',
                            'disabled' => \Auth::user()->type == 'employee' ? true : false
                        ]) }}
                    </div>
                    
                    <!-- Booking Date -->
                    <div class="form-group col-md-2">
                        {{ Form::label('payment_date[]', __('Date'), ['class' => 'col-form-label']) }}
                        {{ Form::date('payment_date[]', $payment['date'] ?? null, [
                            'class' => 'form-control payment-date',
                            'disabled' => \Auth::user()->type == 'employee' ? true : false
                        ]) }}
                    </div>
                    
                    <!-- Amount -->
                    <div class="form-group col-md-3">
                        {{ Form::label('amount[]', __('Amount (Rs)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('amount[]', $payment['amount'] ?? null, [
                            'class' => 'form-control payment-amount', 
                            'placeholder' => 'Enter Amount',
                            'readonly' => \Auth::user()->type == 'employee' ? true : false
                        ]) }}
                    </div>
                    
                    <!-- Action Buttons - Only show for employees -->
                    @if(\Auth::user()->type == 'employee')
                        <div class="col-md-2 d-flex align-items-center gap-2 mt-3">
                            <!-- Remove Button -->
                            <div class="action-btn bg-danger">
                                <button type="button" class="btn btn-sm d-flex justify-content-center align-items-center remove-btn">
                                    <i class="ti ti-trash text-white"></i>
                                </button>
                            </div>

                            <!-- Done Button -->
                            <div class="action-btn bg-info">
                                <button type="button" class="btn btn-sm d-flex justify-content-center align-items-center done-btn">
                                    <i class="ti ti-check text-white"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Template for new payment entries -->
        <div id="payment-template" style="display: none;">
            <div class="payment-entry">
                <h4>New Payment :</h4>
                <div class="row align-items-center">
                    <!-- Mode of Payment -->
                    <div class="form-group col-md-2">
                        {{ Form::label('mode_of_payment[]', __('Mode of Payment'), ['class' => 'col-form-label']) }}
                        {{ Form::select('mode_of_payment[]', ['cash' => 'Cash', 'upi' => 'UPI', 'online' => 'Online', 'cheque' => 'Cheque'], null, ['class' => 'form-control mode-of-payment']) }}
                    </div>
                    
                    <!-- Payment Detail -->
                    <div class="form-group col-md-3 payment-detail" style="display:none;">
                        {{ Form::label('payment_detail[]', __('Payment Detail'), ['class' => 'col-form-label']) }}
                        {{ Form::text('payment_detail[]', '', ['class' => 'form-control', 'placeholder' => 'Enter Payment Detail']) }}
                    </div>
                    
                    <!-- Booking Date -->
                    <div class="form-group col-md-2">
                        {{ Form::label('payment_date[]', __('Date'), ['class' => 'col-form-label']) }}
                        {{ Form::date('payment_date[]', null, ['class' => 'form-control payment-date']) }}
                    </div>
                    
                    <!-- Amount -->
                    <div class="form-group col-md-3">
                        {{ Form::label('amount[]', __('Amount (Rs)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('amount[]', '', ['class' => 'form-control payment-amount', 'placeholder' => 'Enter Amount']) }}
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-2 d-flex align-items-center gap-2 mt-3">
                        <!-- Remove Button -->
                        <div class="action-btn bg-danger">
                            <button type="button" class="btn btn-sm align-items-center remove-btn">
                                <i class="ti ti-trash text-white"></i>
                            </button>
                        </div>

                        <!-- Done Button -->
                        <div class="action-btn bg-info">
                            <button type="button" class="btn btn-sm align-items-center done-btn">
                                <i class="ti ti-check text-white"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Payment Link -->
        <p class="text-primary mt-3" id="addPayment" style="cursor: pointer;">Add New Payment</p>

        <!-- Submit Buttons -->
        <div class="d-flex justify-content-start col-md-12 gap-3 mt-3">
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(2)">Back</button>
            <input type="submit" value="{{ __('Update') }}" class="btn btn-primary mt-3">
        </div>
    </div>

    {{ Form::close() }}
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Track all payment entries
    let paymentEntries = @json($bookingForm->payment_data ?? []);
    let totalPaid = {{ collect($bookingForm->payment_data ?? [])->sum('amount') ?? 0 }};
    let totalCost = {{ $bookingForm->total_cost ?? 0 }};
    let remainingAmount = {{ $bookingForm->remaining ?? ($bookingForm->total_cost ?? 0) }};

    // Function to calculate total cost in step 2
    function calculateTotalCost() {
        let rateperSqFt = parseFloat(document.getElementById('rate_per_sq_ft').value) || 0;
        let basicCost = parseFloat(document.getElementById('basic_cost').value) || 0;
        let infraCost = parseFloat(document.getElementById('cost_infrastructure').value) || 0;
        let gst = parseFloat(document.getElementById('gst').value) || 0;
        let stampDuty = parseFloat(document.getElementById('stamp_duty').value) || 0;
        let registration = parseFloat(document.getElementById('registration').value) || 0;
        let other = parseFloat(document.getElementById('other').value) || 0;
        let maintenanceCost = parseFloat(document.getElementById('maintenance_cost').value) || 0;

        totalCost = rateperSqFt + basicCost + infraCost + gst + stampDuty + registration + other + maintenanceCost;
        document.getElementById('total_cost').value = totalCost.toFixed(2);
    }

    // Calculate on input change in step 2
    document.querySelectorAll('.calc-field').forEach(field => {
        field.addEventListener('input', calculateTotalCost);
    });

    // Navigation between steps
    function nextStep(step) {
        document.querySelectorAll('.step').forEach(e => e.classList.add('hidden'));
        document.getElementById(`step${step}`).classList.remove('hidden');
        
        // When moving to step 3, sync the total cost and initialize remaining amount
        if (step === 3) {
            totalCost = parseFloat(document.getElementById('total_cost').value) || {{ $bookingForm->total_cost ?? 0 }};
            document.getElementById('confirm_total_cost').val = totalCost.toFixed(2);
            remainingAmount = totalCost - totalPaid;
            document.getElementById('remaining').value = remainingAmount.toFixed(2);
            document.getElementById('total_paid').value = totalPaid.toFixed(2);
        }
    }

    function prevStep(step) {
        document.querySelectorAll('.step').forEach(e => e.classList.add('hidden'));
        document.getElementById(`step${step}`).classList.remove('hidden');
    }

    // Handle payment mode selection to show/hide payment details
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('mode-of-payment')) {
            const paymentDetailDiv = e.target.closest('.row').querySelector('.payment-detail');
            if (e.target.value !== 'cash') {
                paymentDetailDiv.style.display = 'block';
            } else {
                paymentDetailDiv.style.display = 'none';
            }
        }
    });

    // Handle done button click to deduct payment from remaining amount
    document.addEventListener('click', function(e) {
        if (e.target.closest('.done-btn') && !e.target.closest('.done-btn').disabled) {
            const paymentEntry = e.target.closest('.payment-entry');
            const amountInput = paymentEntry.querySelector('.payment-amount');
            const amount = parseFloat(amountInput.value) || 0;
            
            if (amount <= 0) {
                alert('Please enter a valid payment amount');
                return;
            }
            
            if (amount > remainingAmount) {
                alert('Payment amount cannot be greater than remaining amount');
                return;
            }
            
            // Update totals
            totalPaid += amount;
            remainingAmount -= amount;
            
            // Update UI
            document.getElementById('remaining').value = remainingAmount.toFixed(2);
            document.getElementById('total_paid').value = totalPaid.toFixed(2);
            
            // Disable all fields and buttons for this payment
            paymentEntry.querySelectorAll('input, select').forEach(field => {
                field.disabled = true;
            });
            paymentEntry.querySelector('.payment-amount').readOnly = true;
            
            // Disable the action buttons
            const actionBtns = paymentEntry.querySelectorAll('.action-btn');
            actionBtns.forEach(btn => {
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            });
            paymentEntry.querySelectorAll('.done-btn, .remove-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // Store the payment entry data
            const entryData = {
                mode: paymentEntry.querySelector('.mode-of-payment').value,
                detail: paymentEntry.querySelector('.payment-detail input')?.value || '',
                date: paymentEntry.querySelector('.payment-date').value,
                amount: amount
            };
            
            paymentEntries.push(entryData);
        }
    });

    // Add new payment entry
    document.getElementById('addPayment').addEventListener('click', function() {
        const paymentSection = document.getElementById('payment-section');
        const paymentTemplate = document.getElementById('payment-template').cloneNode(true);
        const newEntry = paymentTemplate.querySelector('.payment-entry');
        
        // Update payment label
        const paymentLabels = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
        const paymentNumber = paymentSection.querySelectorAll('.payment-entry').length + 1;
        const paymentLabel = paymentNumber <= paymentLabels.length ? paymentLabels[paymentNumber - 1] : `${paymentNumber}th`;
        newEntry.querySelector('h4').textContent = `${paymentLabel} Payment :`;
        
        // Make the new entry visible
        newEntry.style.display = 'block';
        
        // Add to DOM
        paymentSection.appendChild(newEntry);
    });

    // Remove payment entry
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-btn')) {
            const paymentEntry = e.target.closest('.payment-entry');
            const amountInput = paymentEntry.querySelector('.payment-amount');
            const amount = parseFloat(amountInput.value) || 0;
            
            // If this payment was already done, add the amount back to remaining
            if (amountInput.readOnly) {
                remainingAmount += amount;
                totalPaid -= amount;
                
                document.getElementById('remaining').value = remainingAmount.toFixed(2);
                document.getElementById('total_paid').value = totalPaid.toFixed(2);
                
                // Remove from payment entries array
                const entryIndex = paymentEntries.findIndex(entry => 
                    entry.amount === amount && 
                    entry.date === paymentEntry.querySelector('.payment-date').value
                );
                
                if (entryIndex !== -1) {
                    paymentEntries.splice(entryIndex, 1);
                }
            }
            
            // Remove from DOM
            paymentEntry.remove();
        }
    });

    // Handle project and enquiry selection
    $(document).ready(function() {
        // Project dropdown change handler
        $('#projectDropdown').on('change', function() {
            const projectId = $(this).val();
            const $enquiryFilter = $('#enquiryFilter');
            
            $enquiryFilter.empty().append('<option value="">Loading...</option>').prop('disabled', true);

            if (!projectId) {
                $enquiryFilter.empty().append('<option value="">Select Project First</option>');
                return;
            }

            $.ajax({
                url: `/hrm_rising/get-booked-enquiries-by-project/${projectId}`,
                type: 'GET',
                dataType: 'json',
                data: {
                    current_booking_id: '{{ $bookingForm->id }}',
                    current_unit_id: '{{ $bookingForm->unit_id }}'
                },
                success: function(response) {
                    $enquiryFilter.empty().append('<option value="">Select Enquiry</option>');
                    
                    if (response.enquiries && response.enquiries.length > 0) {
                        response.enquiries.forEach(function(enquiry) {
                            $enquiryFilter.append(
                                `<option value="${enquiry.id}">${enquiry.full_name} (${enquiry.mobile_no})</option>`
                            );
                        });
                    } else {
                        $enquiryFilter.append('<option value="">No booked enquiries found</option>');
                    }
                    
                    $enquiryFilter.prop('disabled', false);
                    
                    // If there was a previously selected enquiry, try to select it
                    @if($bookingForm->enquiry_id)
                        $enquiryFilter.val('{{ $bookingForm->enquiry_id }}');
                    @endif
                },
                error: function() {
                    $enquiryFilter.empty().append('<option value="">Error loading enquiries</option>');
                }
            });

            // Also update units when project changes
            $.ajax({
                url: `/hrm_rising/get-units-by-project/${projectId}`,
                type: 'GET',
                dataType: 'json',
                data: {
                    current_booking_id: '{{ $bookingForm->id }}'
                },
                success: function(response) {
                    const $unitDropdown = $('#unitDropdown');
                    $unitDropdown.empty().append('<option value="">Select Unit</option>');
                    
                    if (response.units && response.units.length > 0) {
                        response.units.forEach(function(unit) {
                            $unitDropdown.append(
                                `<option value="${unit.id}" data-size="${unit.unit_size}">${unit.unit_name}</option>`
                            );
                        });
                    } else {
                        $unitDropdown.append('<option value="">No units available</option>');
                    }
                    
                    // Select the current unit
                    @if($bookingForm->unit_id)
                        $unitDropdown.val('{{ $bookingForm->unit_id }}');
                        $('#unit_size').val('{{ $bookingForm->unit_size }}');
                    @endif
                },
                error: function() {
                    $('#unitDropdown').empty().append('<option value="">Error loading units</option>');
                }
            });
        });

        // Unit selection handler
        $('#unitDropdown').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const unitSize = selectedOption.data('size');
            $('#unit_size').val(unitSize || '');
        });

        // Initialize if project is preselected
        @if($bookingForm->project_id)
            $('#projectDropdown').trigger('change');
        @endif

        // Initialize unit size if unit is preselected
        @if($bookingForm->unit_id)
            $('#unitDropdown').trigger('change');
        @endif

        // Form submission handler
        $('#bookingForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (totalCost <= 0) {
                Swal.fire('Error', 'Please calculate and finalize the total cost first', 'error');
                return false;
            }

            // Prepare payment data
            const paymentData = [];
            $('.payment-entry').each(function() {
                const entry = $(this);
                paymentData.push({
                    mode_of_payment: entry.find('.mode-of-payment').val(),
                    payment_detail: entry.find('.payment-detail input').val() || '',
                    date: entry.find('.payment-date').val(),
                    amount: parseFloat(entry.find('.payment-amount').val()) || 0
                });
            });

            // Add payment data to form
            $('<input>').attr({
                type: 'hidden',
                name: 'payment_json',
                value: JSON.stringify(paymentData)
            }).appendTo('#bookingForm');

            // Submit form
            this.submit();
        });
    });
</script>

<style>
    .hidden { display: none; }
    .text-danger { color: red; }
    .payment-entry {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    .payment-entry h4 {
        margin-bottom: 15px;
    }
    .action-btn {
        display: inline-block;
        margin-right: 5px;
    }
    .action-btn .btn {
        padding: 0.25rem 0.5rem;
    }
    .payment-detail {
        transition: all 0.3s ease;
    }
    .plotting-field, .residential-commercial-field {
        display: none;
    }
    @if($projectType == 3)
        .plotting-field {
            display: block;
        }
    @else
        .residential-commercial-field {
            display: block;
        }
    @endif
</style>
@endsection