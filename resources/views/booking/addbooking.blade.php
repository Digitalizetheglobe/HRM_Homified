@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="card mb-4">
        <div class="card-header">
            <h4>Select Enquiry for Booking</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('booking.add') }}">
                <div class="row">
                    <div class="form-group col-md-6">
                        {{ Form::label('project_id', __('Project'), ['class' => 'col-form-label']) }}
                        <select name="project_id" id="projectFilter" class="form-control select2" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}" {{ request('project_id') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        {{ Form::label('enquiry_id', __('Enquiry'), ['class' => 'col-form-label']) }}
                        <select name="enquiry_id" id="enquiryFilter" class="form-control " 
                            {{ !request('project_id') ? 'disabled' : '' }} required>
                            <option value="">Select Enquiry</option>
                            @foreach($enquiries as $enquiry)
                                <option value="{{ $enquiry->id }}" {{ request('enquiry_id') == $enquiry->id ? 'selected' : '' }}>
                                    {{ $enquiry->full_name }} ({{ $enquiry->mobile_no }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2 booking-buttons-mobile">
                            <button type="submit" class="btn btn-primary">Load Booking Form</button>
                            <a href="{{ route('booking.add') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedEnquiry)
    
        <div class="card">
            <div class="card-header text-center">
                <h4>Booking Form for {{ $selectedEnquiry->full_name }}</h4>
            </div>
            
        </div>   
        <div class="card">
            <div class="card-header ">
                <h4>Applicant Details :</h4>
            </div>
            <div class="card-body">
                {{ Form::open(['route' => ['booking.store']]) }}
                {{ Form::hidden('enquiry_id', $selectedEnquiry->id) }}
                
                @if (\Auth::user()->type != 'employee')
                    <div class="row p-3 mb-3">
                        <div class="form-group col-md-6">
                            {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}
                            {!! Form::select('employee_id', $employees ?? [], null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('Select Employee'),
                                'required' => true,
                            ]) !!}
                        </div>
                    </div>
                @endif

                <!-- Primary Applicant Section -->
                <h5 class="mb-3">Primary Applicant Details</h5>
                <div class="row p-3 mb-3">
                    <div class="form-group col-md-4">
                        {{ Form::label('primary_applicant_name', __('Full Name'), ['class' => 'col-form-label']) }}
                        {{ Form::text('primary_applicant_name', $bookingForm->primary_applicant_name, [
                            'class' => 'form-control' . ($errors->has('primary_applicant_name') ? ' is-invalid' : ''),
                            'readonly' => true
                        ]) }}
                        @if($errors->has('primary_applicant_name'))
                            <div class="invalid-feedback">
                                {{ $errors->first('primary_applicant_name') }}
                            </div>
                        @endif
                    </div>
                    <div class="form-group col-md-4">
                        {{ Form::label('primary_applicant_contact_no', __('Contact No.'), ['class' => 'col-form-label']) }}
                        {{ Form::text('primary_applicant_contact_no', $bookingForm->primary_applicant_contact_no, [
                            'class' => 'form-control',
                            'readonly' => true
                        ]) }}
                    </div>
                    <div class="form-group col-md-4">
                        {{ Form::label('primary_applicant_email', __('Email'), ['class' => 'col-form-label']) }}
                        {{ Form::email('primary_applicant_email', $bookingForm->primary_applicant_email, [
                            'class' => 'form-control',
                            'readonly' => true
                        ]) }}
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
                        {{ Form::text('primary_applicant_pan_no', $bookingForm->primary_applicant_pan_no ?? '', ['class' => 'form-control pan-input', 'placeholder' => 'Enter PAN No', 'style' => 'text-transform: uppercase', 'maxlength' => '10']) }}
                    </div>

                    <div class="form-group col-md-4">
                        {{ Form::label('primary_applicant_aadhar_no', __('Primary Applicant Aadhar No'), ['class' => 'col-form-label']) }}
                        {{ Form::text('primary_applicant_aadhar_no', $bookingForm->primary_applicant_aadhar_no ?? '', ['class' => 'form-control aadhar-input', 'placeholder' => 'Enter Aadhar No (12 digits)', 'maxlength' => '12', 'pattern' => '[0-9]{12}', 'inputmode' => 'numeric', 'type' => 'tel', 'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57']) }}
                    </div>
                    <!-- Add more readonly fields as needed -->
                </div>

                <!-- Include the rest of your booking form here -->
                <!-- You can copy-paste the relevant sections from your create.blade.php -->
                <!-- Make sure to remove the fields that are already pre-filled above -->

                <!-- For example: -->
                <h5 class="mb-3">Secondary Applicant Details</h5>
                <div class="row">
                    <div class="form-group col-md-4">
                        {{ Form::label('secondary_applicant_name', __('Secondary Applicant Name'), ['class' => 'col-form-label']) }}
                        {{ Form::text('secondary_applicant_name', '', ['class' => 'form-control', 'placeholder' => 'Enter Secondary Applicant Name']) }}
                    </div>
                    <div class="form-group col-md-4">
                        {{ Form::label('secondary_applicant_contact_no', __('Secondary Applicant Contact No.'), ['class' => 'col-form-label']) }}
                        {{ Form::text('secondary_applicant_contact_no', '', ['class' => 'form-control', 'placeholder' => 'Enter Contact No.']) }}
                    </div>
                    <div class="form-group col-md-4">
                        {{ Form::label('secondary_applicant_email', __('Secondary Applicant Email'), ['class' => 'col-form-label']) }}
                        {{ Form::email('secondary_applicant_email', '', ['class' => 'form-control', 'placeholder' => 'Enter Email']) }}
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
                        {{ Form::text('secondary_applicant_pan_no', $bookingForm->secondary_applicant_pan_no ?? '', ['class' => 'form-control pan-input', 'placeholder' => 'Enter PAN No.', 'style' => 'text-transform: uppercase', 'maxlength' => '10']) }}
                    </div>

                    <div class="form-group col-md-4">
                        {{ Form::label('secondary_applicant_aadhar_no', __('Secondary Applicant Aadhar No.'), ['class' => 'col-form-label']) }}
                        {{ Form::text('secondary_applicant_aadhar_no', $bookingForm->secondary_applicant_aadhar_no ?? '', ['class' => 'form-control aadhar-input', 'placeholder' => 'Enter Aadhar No (12 digits)', 'maxlength' => '12', 'pattern' => '[0-9]{12}', 'inputmode' => 'numeric', 'type' => 'tel', 'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57']) }}
                    </div>
                </div>


                <!-- Payment Details Section -->
                <!-- Copy from your existing form -->

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>

                {{ Form::close() }}
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Booking Calculation & Area Details :</h4>
            </div>
            <div class="card-body">
                {{ Form::open(['route' => ['booking.store']]) }}
                {{ Form::hidden('enquiry_id', $selectedEnquiry->id) }}

                <div class="row">
                    <!-- Project Name -->
                    <div class="form-group col-md-4">
                        {{ Form::label('project_id', __('Project Name'), ['class' => 'col-form-label']) }}
                        <select name="project_id" id="projectDropdown" class="form-control select2" required disabled>
                            <option value="">Select Project</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}" {{ request('project_id') == $id ? 'selected' : '' }} 
                                        data-type="{{ $projectTypes[$id] ?? '' }}">
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        {{ Form::hidden('project_type', $projectType ?? '', ['id' => 'project_type']) }}
                    </div>

                    <!-- Unit Name -->
                    <div class="form-group col-md-4">
                        {{ Form::label('unit_id', __('Unit Name'), ['class' => 'col-form-label']) }}
                        <select name="unit_id" id="unitDropdown" class="form-control" required>
                            <option value="">Select Unit</option>
                            @if(request('project_id') && $units)
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" 
                                        data-size="{{ $unit->unit_size }}"
                                        {{ $bookingForm->unit_id == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->unit_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Unit Size -->
                    <div class="form-group col-md-4">
                        {{ Form::label('unit_size', __('Unit Size (sq.ft)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('unit_size', $bookingForm->unit_size ?? '', [
                            'class' => 'form-control',
                            'id' => 'unit_size',
                            'readonly' => true
                        ]) }}
                    </div>

                    <!-- Booking Date -->
                    <div class="form-group col-md-4">
                        {{ Form::label('booking_date', __('Booking Date'), ['class' => 'col-form-label']) }}
                        {{ Form::date('booking_date', $bookingForm->booking_date ?? '', ['class' => 'form-control']) }}
                    </div>

                    <!-- Fields for Residential/Commercial Projects -->
                    @if($projectType == 1 || $projectType == 2) {{-- Residential (1) or Commercial (2) --}}
                        <!-- Carpet Area (auto-filled from unit size, readonly) -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('carpet_area', __('Carpet Area (sq.ft)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('carpet_area', $bookingForm->carpet_area ?? '', [
                                'class' => 'form-control residential-commercial-input',
                                'id' => 'carpet_area',
                                'readonly' => true,
                                'required' => true
                            ]) }}
                        </div>

                        <!-- Built Up Area (calculated, readonly) -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('built_up_area', __('Built Up Area (sq.ft)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('built_up_area', $bookingForm->built_up_area ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- Rate Per Sq.Ft -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('rate_per_sq_ft', __('Rate Per Sq.Ft (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('rate_per_sq_ft', $bookingForm->rate_per_sq_ft ?? '', [
                                'class' => 'form-control residential-commercial-input',
                                'placeholder' => 'Enter Rate per Sq.Ft',
                                'required' => true
                            ]) }}
                        </div>

                        <!-- Cost Towards Infrastructure -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('cost_infrastructure', __('Cost Towards Infrastructure (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('cost_infrastructure', $bookingForm->cost_infrastructure ?? '', [
                                'class' => 'form-control residential-commercial-input',
                                'placeholder' => 'Enter Infrastructure Cost'
                            ]) }}
                        </div>

                        <!-- Agreement Cost -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('agreement_cost', __('Total Agreement Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('agreement_cost', $bookingForm->agreement_cost ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- GST -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('gst', __('GST (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('gst', $bookingForm->gst ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => false
                            ]) }}
                        </div>

                        <!-- Stamp Duty -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('stamp_duty', __('Stamp Duty (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('stamp_duty', $bookingForm->stamp_duty ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- Registration -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('registration', __('Registration (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('registration', $bookingForm->registration ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => false
                            ]) }}
                        </div>

                        <!-- Legal Charges -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('legal_charges', __('Legal Charges (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('legal_charges', $bookingForm->legal_charges ?? '', [
                                'class' => 'form-control residential-commercial-optional',
                                'placeholder' => 'Enter Legal Charges'
                            ]) }}
                        </div>

                        <!-- Other -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('other', __('Other (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('other', $bookingForm->other ?? '', [
                                'class' => 'form-control residential-commercial-optional',
                                'placeholder' => 'Enter Other Costs'
                            ]) }}
                        </div>

                        <!-- Maintenance Cost -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('maintenance_cost', __('Maintenance Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('maintenance_cost', $bookingForm->maintenance_cost ?? '', [
                                'class' => 'form-control residential-commercial-optional',
                                'placeholder' => 'Enter Maintenance Cost'
                            ]) }}
                        </div>

                        <!-- Total Cost -->
                        <div class="form-group col-md-4 residential-commercial-field">
                            {{ Form::label('total_cost', __('Total Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('total_cost', $bookingForm->total_cost ?? '', [
                                'class' => 'form-control residential-commercial-result',
                                'readonly' => true
                            ]) }}
                        </div>
                    @endif

                    @if($projectType == 3) {{-- Plotting project --}}
                        <!-- Plot Area (auto-filled from unit size, readonly) -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('plot_area', __('Plot Area (sq.ft)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('plot_area', $bookingForm->plot_area ?? '', [
                                'class' => 'form-control plotting-calc-input',
                                'id' => 'plot_area',
                                'readonly' => true,
                                'required' => true
                            ]) }}
                        </div>

                        <!-- Rate Per Sq.Ft -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('rate_per_sq_ft', __('Rate Per Sq.Ft (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('rate_per_sq_ft', $bookingForm->rate_per_sq_ft ?? '', [
                                'class' => 'form-control plotting-calc-input',
                                'placeholder' => 'Enter Rate per Sq.Ft',
                                'required' => true
                            ]) }}
                        </div>

                        <!-- Basic Cost -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('basic_cost', __('Basic Cost Towards Plot/Unit (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('basic_cost', $bookingForm->basic_cost ?? '', [
                                'class' => 'form-control plotting-calc-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- Infrastructure Cost -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('cost_infrastructure', __('Cost Towards Infrastructure (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('cost_infrastructure', $bookingForm->cost_infrastructure ?? '', [
                                'class' => 'form-control plotting-calc-input',
                                'placeholder' => 'Enter Infrastructure Cost',
                                'required' => true
                            ]) }}
                        </div>

                        <!-- Agreement Cost -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('agreement_cost', __('Total Agreement Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('agreement_cost', $bookingForm->agreement_cost ?? '', [
                                'class' => 'form-control plotting-calc-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- GST (now editable) -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('gst', __('GST (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('gst', $bookingForm->gst ?? '', [
                                'class' => 'form-control plotting-gst-input',
                                'placeholder' => 'Enter GST Amount'
                            ]) }}
                        </div>

                        <!-- Stamp Duty -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('stamp_duty', __('Stamp Duty (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('stamp_duty', $bookingForm->stamp_duty ?? '', [
                                'class' => 'form-control plotting-calc-result',
                                'readonly' => true
                            ]) }}
                        </div>

                        <!-- Registration -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('registration', __('Registration (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('registration', $bookingForm->registration ?? '', [
                                'class' => 'form-control plotting-calc-result',
                                'readonly' => false
                            ]) }}
                        </div>

                        <!-- Legal Charges -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('legal_charges', __('Legal Charges (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('legal_charges', $bookingForm->legal_charges ?? '', [
                                'class' => 'form-control plotting-optional-input',
                                'placeholder' => 'Enter Legal Charges'
                            ]) }}
                        </div>

                        <!-- Other -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('other', __('Other (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('other', $bookingForm->other ?? '', [
                                'class' => 'form-control plotting-optional-input',
                                'placeholder' => 'Enter Other Costs'
                            ]) }}
                        </div>

                        <!-- Maintenance Cost -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('maintenance_cost', __('Maintenance Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('maintenance_cost', $bookingForm->maintenance_cost ?? '', [
                                'class' => 'form-control plotting-optional-input',
                                'placeholder' => 'Enter Maintenance Cost'
                            ]) }}
                        </div>

                        <!-- Total Cost -->
                        <div class="form-group col-md-4 plotting-field">
                            {{ Form::label('total_cost', __('Total Cost (Rs)'), ['class' => 'col-form-label']) }}
                            {{ Form::text('total_cost', $bookingForm->total_cost ?? '', [
                                'class' => 'form-control plotting-calc-result',
                                'readonly' => true
                            ]) }}
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="button" id="finalizeCalculationBtn" class="btn" style="background-color: #ea3538; border-color: #ea3538; color: white;">
                        Done - Finalize Total Cost
                    </button>
                </div>

                {{ Form::close() }}
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Payment Details :</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Total Cost (readonly, populated from calculation card) -->
                    <div class="form-group col-md-4">
                        {{ Form::label('payment_total_cost', __('Total Cost (Rs)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('payment_total_cost', '', [
                            'class' => 'form-control', 
                            'id' => 'payment_total_cost', 
                            'readonly' => true
                        ]) }}
                    </div>
                    
                    <!-- Remaining Amount -->
                    <div class="form-group col-md-4">
                        {{ Form::label('remaining', __('Remaining Amount (Rs)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('remaining', '', [
                            'class' => 'form-control', 
                            'id' => 'remaining', 
                            'readonly' => true
                        ]) }}
                    </div>
                    
                    <!-- Total Paid Amount -->
                    <div class="form-group col-md-4">
                        {{ Form::label('total_paid', __('Total Paid Amount (Rs)'), ['class' => 'col-form-label']) }}
                        {{ Form::text('total_paid', '0', [
                            'class' => 'form-control', 
                            'id' => 'total_paid', 
                            'readonly' => true
                        ]) }}
                    </div>
                </div>

                <!-- Payment Entries Container -->
                <div id="payment-section">
                    <!-- First payment will be added here dynamically -->
                </div>
                
                <!-- Add Payment Link -->
                <p class="text-primary mt-3" id="addPayment" style="cursor: pointer;">
                    <i class="ti ti-plus"></i> Add Payment
                </p>
            </div>
        </div>

        <!-- Submit Button Section -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <button type="button" id="submitBookingBtn" class="btn btn-lg" style="background-color: #ea3538; border-color: #ea3538; color: white;">
                    Submit Booking
                </button>
            </div>
        </div>

    @endif
</div>

<script>
    $(document).ready(function() {
        // Debugging logs
        console.log('Document ready');
        console.log('Initial enquiry filter state:', $('#enquiryFilter').prop('disabled'));
        console.log('Select2 initialized:', $('#enquiryFilter').hasClass('select2-hidden-accessible'));
        console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');
        
        // Initialize Select2 on enquiry dropdown if it has options and is enabled
        if (typeof $.fn.select2 !== 'undefined' && !$('#enquiryFilter').prop('disabled')) {
            $('#enquiryFilter').select2({
                placeholder: 'Search or select enquiry...',
                allowClear: true,
                minimumInputLength: 0,
                width: '100%'
            });
        }
        
        // ==================== ENQUIRY FILTER HANDLING ====================
        $('#projectFilter').on('change', function() {
            const projectId = $(this).val();
            const $enquiryFilter = $('#enquiryFilter');
            
            // Reset and disable enquiry dropdown
            $enquiryFilter.empty().append('<option value="">Select Enquiry</option>');
            
            if (!projectId) {
                $enquiryFilter.prop('disabled', true);
                return;
            }
            
            // Show loading state
            $enquiryFilter.prop('disabled', true);
            
            // If using select2, destroy it first to prevent conflicts
            if (typeof $.fn.select2 !== 'undefined') {
                try {
                    if ($enquiryFilter.hasClass('select2-hidden-accessible')) {
                        $enquiryFilter.select2('destroy');
                    }
                } catch(e) {
                    console.log('Select2 destroy error (may not be initialized):', e);
                }
            }
            
            $enquiryFilter.empty().append('<option value="">Loading enquiries...</option>');
            
            // Temporarily remove select2 class to prevent conflicts
            $enquiryFilter.removeClass('select2');
            $enquiryFilter.removeAttr('disabled');
            
            // Fetch enquiries for selected project
            console.log('Fetching enquiries for project ID:', projectId);
            $.ajax({
                url: '/hrm_rising/get-enquiries-by-project/' + projectId,
                type: 'GET',
                dataType: 'json',
                cache: false,
                success: function(response) {
                    console.log('API Response:', response);
                    console.log('Response type:', Array.isArray(response) ? 'Array' : 'Object');
                    
                    // Handle both array response and object response
                    let enquiries = [];
                    if (Array.isArray(response)) {
                        // Response is directly an array
                        enquiries = response;
                        console.log('Response is array, enquiries count:', enquiries.length);
                    } else if (response.enquiries) {
                        // Response is an object with enquiries property
                        enquiries = response.enquiries;
                        console.log('Response is object, enquiries count:', enquiries.length);
                    }
                    
                    // Destroy Select2 before manipulating options to avoid conflicts
                    if (typeof $.fn.select2 !== 'undefined' && $enquiryFilter.hasClass('select2-hidden-accessible')) {
                        try {
                            $enquiryFilter.select2('destroy');
                        } catch(e) {
                            console.log('Select2 destroy error:', e);
                        }
                    }
                    
                    $enquiryFilter.empty().append('<option value="">Select Enquiry</option>');
                    
                    if (response && response.error) {
                        console.error('API Error:', response.error);
                        $enquiryFilter.append('<option value="">' + response.error + '</option>');
                    } else if (enquiries && enquiries.length > 0) {
                        console.log('Found enquiries:', enquiries.length);
                        $.each(enquiries, function(index, enquiry) {
                            // Handle both object format and ensure properties exist
                            const fullName = enquiry.full_name || enquiry.name || '';
                            const mobileNo = enquiry.mobile_no || enquiry.mobile || enquiry.phone || '';
                            const optionText = fullName + (mobileNo ? ' (' + mobileNo + ')' : '');
                            
                            if (enquiry.id && optionText) {
                                // Use native DOM methods to ensure options are added
                                const option = document.createElement('option');
                                option.value = enquiry.id;
                                option.textContent = optionText;
                                $enquiryFilter[0].appendChild(option);
                            }
                        });
                        console.log('Options added to dropdown. Total options:', $enquiryFilter.find('option').length);
                    } else {
                        console.warn('No unbooked enquiries found for this project');
                        let message = 'No unbooked enquiries found for this project';
                        if (response && !Array.isArray(response)) {
                            if (response.total_enquiries !== undefined) {
                                if (response.total_enquiries === 0) {
                                    message = 'No enquiries found for this project';
                                } else if (response.booked_enquiries > 0) {
                                    message = 'All enquiries for this project are already booked (' + response.booked_enquiries + ' booked)';
                                }
                            }
                        }
                        $enquiryFilter.append('<option value="">' + message + '</option>');
                    }
                    
                    // Enable the dropdown
                    $enquiryFilter.prop('disabled', false);
                    
                    // Reinitialize select2 with search functionality after a short delay
                    setTimeout(function() {
                        if (typeof $.fn.select2 !== 'undefined') {
                            try {
                                $enquiryFilter.select2({
                                    placeholder: 'Search or select enquiry...',
                                    allowClear: true,
                                    minimumInputLength: 0,
                                    width: '100%'
                                });
                                console.log('Select2 reinitialized successfully');
                            } catch(e) {
                                console.error('Select2 initialization error:', e);
                            }
                        } else {
                            $enquiryFilter.addClass('select2');
                        }
                    }, 150);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading enquiries:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    let errorMessage = 'Error loading enquiries';
                    if (xhr.status === 404) {
                        errorMessage = 'Route not found. Please check the URL.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $enquiryFilter.empty().append('<option value="">' + errorMessage + '</option>');
                    $enquiryFilter.prop('disabled', false);
                    
                    setTimeout(function() {
                        if (typeof $.fn.select2 !== 'undefined') {
                            $enquiryFilter.select2({
                                placeholder: 'Search or select enquiry...',
                                allowClear: true,
                                minimumInputLength: 0,
                                width: '100%'
                            });
                        } else {
                            $enquiryFilter.addClass('select2');
                        }
                    }, 100);
                }
            });
        });
        
        // ==================== UNIT SELECTION HANDLING ====================
        $(document).ready(function() {
            // Handle project selection change to load units
            // In your blade template where you handle the unit dropdown
            $('#projectDropdown').on('change', function() {
                const projectId = $(this).val();
                const $unitDropdown = $('#unitDropdown');
                const $unitSize = $('#unit_size');
                const $plotArea = $('#plot_area');
                const $carpetArea = $('#carpet_area');
                
                // Reset unit dropdown and size field
                $unitDropdown.empty().append('<option value="">Select Unit</option>');
                $unitSize.val('');
                $plotArea.val('');
                $carpetArea.val('');
                
                if (!projectId) {
                    $unitDropdown.prop('disabled', true);
                    return;
                }
                
                // Fetch units for selected project
                // Fetch units for selected project
                $.ajax({
                    url: '/hrm_rising/get-units-by-project/' + projectId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.units && response.units.length > 0) {
                            $.each(response.units, function(index, unit) {
                                $unitDropdown.append(
                                    $('<option>', {
                                        value: unit.id,
                                        text: unit.unit_name,
                                        'data-size': unit.unit_size // Ensure this is set
                                    })
                                );
                            });
                        } else {
                            $unitDropdown.append('<option value="">No units available</option>');
                        }
                        $unitDropdown.prop('disabled', false);
                        
                        // If there's a preselected unit, trigger the change
                        if ($unitDropdown.find('option:selected').val()) {
                            $unitDropdown.trigger('change');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading units:', error);
                        $unitDropdown.append('<option value="">Error loading units</option>');
                    }
                });
            });
            
            // Handle unit selection change to show size and populate plot area or carpet area
            $('#unitDropdown').change(function() {
                const selectedUnit = $(this).find('option:selected');
                const unitSize = selectedUnit.data('size');
                const projectType = $('#project_type').val();

                // Update unit size field
                $('#unit_size').val(unitSize);
                
                // Update plot area or carpet area field based on project type
                if (projectType == 3) { // Plotting project
                    $('#plot_area').val(unitSize);
                } else if (projectType == 1 || projectType == 2) { // Residential or Commercial
                    $('#carpet_area').val(unitSize);
                }
                
                if (selectedUnit.data('booked') === true) {
                    alert('This unit is already booked!');
                    $(this).val('');
                }
            });
            // Initialize if project is preselected
            @if(request('project_id'))
                $('#projectDropdown').trigger('change');
                @if($bookingForm->unit_id)
                    // Set the unit size and plot area if unit is already selected
                    setTimeout(function() {
                        $('#unitDropdown').trigger('change');
                    }, 500);
                @endif
            @endif
        });
        
        // ==================== PROJECT TYPE HANDLING ====================
        function handleProjectType(projectType) {
            // Store the project type in a hidden field
            $('#project_type').val(projectType);
            
            if (projectType == 3) { // Plotting project
                $('.plotting-field').show();
                $('.non-plotting-field').hide();
                $('.residential-commercial-field').hide();
                $('.plotting-calc-input').prop('required', true);
                setupPlottingCalculations();
            } else if (projectType == 1 || projectType == 2) { // Residential or Commercial
                $('.residential-commercial-field').show();
                $('.plotting-field').hide();
                $('.non-plotting-field').show();
                $('.residential-commercial-input').prop('required', true);
                setupResidentialCommercialCalculations();
            } else {
                // Hide all calculation fields if project type is not recognized
                $('.plotting-field').hide();
                $('.residential-commercial-field').hide();
                $('.non-plotting-field').hide();
            }
        }
        
        // ==================== PLOTTING CALCULATIONS ====================
        function setupPlottingCalculations() {
            // Make calculated fields readonly (except GST and Registration)
            $('.plotting-calc-result').not('#gst, #registration').prop('readonly', true);
            
            // Calculate when input fields change (including GST & Registration)
            $('.plotting-calc-input, .plotting-gst-input, .plotting-optional-input, #gst, #registration')
                .on('input', calculatePlottingCosts);
            
            // Initial calculation
            calculatePlottingCosts();
        }

        
        function calculatePlottingCosts() {
            // Get input values
            const plotArea = parseFloat($('#plot_area').val()) || 0;
            const ratePerSqFt = parseFloat($('#rate_per_sq_ft').val()) || 0;
            const costInfrastructure = parseFloat($('#cost_infrastructure').val()) || 0;
            
            // Calculate basic cost (Plot Area * Rate)
            const basicCost = plotArea * ratePerSqFt;
            $('#basic_cost').val(basicCost.toFixed(2));
            
            // Calculate total agreement cost (Basic Cost + Infrastructure)
            const totalAgreementCost = basicCost + costInfrastructure;
            $('#agreement_cost').val(totalAgreementCost.toFixed(2));
            
            // Get GST value (now manually entered)
            const gst = parseFloat($('#gst').val()) || 0;
            
            // Fixed percentages for other charges
            const stampDuty = totalAgreementCost * 0.06; // 6% Stamp Duty
            
            $('#stamp_duty').val(stampDuty.toFixed(2));
            
            // Get registration value (manually entered)
            const registration = parseFloat($('#registration').val()) || 0;
            
            // Get optional fields
            const legalCharges = parseFloat($('#legal_charges').val()) || 0;
            const other = parseFloat($('#other').val()) || 0;
            const maintenanceCost = parseFloat($('#maintenance_cost').val()) || 0;
            
            // Calculate total cost
            const totalCost = totalAgreementCost + gst + stampDuty + registration + 
                            legalCharges + other + maintenanceCost;
            $('#total_cost').val(totalCost.toFixed(2));
        }
        
        // ==================== INITIALIZATION ====================
        // Initialize if project is preselected
        @if(request('project_id'))
            $('#projectFilter').trigger('change');
            $('#projectDropdown').trigger('change');
            
            @if($bookingForm->unit_id)
                // Set the unit size and plot area if unit is already selected
                setTimeout(function() {
                    $('#unitDropdown').trigger('change');
                }, 500);
            @endif
            
            @if(isset($projectType))
                handleProjectType({{ $projectType }});
            @endif
        @endif

        // Add this to your existing JavaScript
        function setupResidentialCommercialCalculations() {
            // Make calculated fields readonly
            $('.residential-commercial-result').not('#registration').prop('readonly', true);
            
            // Calculate when input fields change
            $('.residential-commercial-input, .residential-commercial-optional').on('input', calculateResidentialCommercialCosts);
            
            // Initial calculation
            calculateResidentialCommercialCosts();
        }

        function calculateResidentialCommercialCosts() {
            // Get input values
            const carpetArea = parseFloat($('#carpet_area').val()) || 0;
            const ratePerSqFt = parseFloat($('#rate_per_sq_ft').val()) || 0;
            const costInfrastructure = parseFloat($('#cost_infrastructure').val()) || 0;
            const projectType = $('#project_type').val(); // 1 for residential, 2 for commercial
            
            // Calculate built up area (carpet area * 1.5)
            const builtUpArea = carpetArea * 1.5;
            $('#built_up_area').val(builtUpArea.toFixed(2));
            
            // Calculate basic cost (Built Up Area * Rate)
            const basicCost = builtUpArea * ratePerSqFt;
            
            // Calculate total agreement cost (Basic Cost + Infrastructure)
            const totalAgreementCost = basicCost + costInfrastructure;
            $('#agreement_cost').val(totalAgreementCost.toFixed(2));
            
            // Calculate taxes and fees based on project type
            let gstRate, stampDutyRate;
            
            if (projectType == 1) { // Residential
                gstRate = 0.01; // 1%
                stampDutyRate = 0.06; // 6%
            } else if (projectType == 2) { // Commercial
                gstRate = 0.12; // 12%
                stampDutyRate = 0.06; // 6%
            }
            
            const gst = totalAgreementCost * gstRate;
            const stampDuty = totalAgreementCost * stampDutyRate;
            
            $('#gst').val(gst.toFixed(2));
            $('#stamp_duty').val(stampDuty.toFixed(2));
            
            // Get registration value (manually entered)
            const registration = parseFloat($('#registration').val()) || 0;
            
            // Get optional fields
            const legalCharges = parseFloat($('#legal_charges').val()) || 0;
            const other = parseFloat($('#other').val()) || 0;
            const maintenanceCost = parseFloat($('#maintenance_cost').val()) || 0;
            
            // Calculate total cost
            const totalCost = totalAgreementCost + gst + stampDuty + registration + 
                            legalCharges + other + maintenanceCost;
            $('#total_cost').val(totalCost.toFixed(2));
        }
    });



   
</script>

<style>
    .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
    }

    .residential-commercial-result[readonly] {
    background-color: #f8f9fa;
    opacity: 1;
    }

    /* Ensure fields are properly hidden when not applicable */
    .plotting-field, .residential-commercial-field {
        display: none;
    }

    /* Mobile responsive for booking buttons */
    @media (max-width: 767px) {
        .booking-buttons-mobile {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .booking-buttons-mobile .btn {
            width: 100%;
            margin-bottom: 8px;
        }
        
        .booking-buttons-mobile .btn:last-child {
            margin-bottom: 0;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
// Payment system variables
let paymentEntries = [];
let totalPaid = 0;
let remainingAmount = 0;
let totalCost = 0;
let paymentCount = 0;

// Function to convert number to word (1 -> "First", 2 -> "Second", etc.)
function numberToWord(num) {
    const words = [
        'First', 'Second', 'Third', 'Fourth', 'Fifth', 
        'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth',
        'Eleventh', 'Twelfth', 'Thirteenth', 'Fourteenth', 'Fifteenth',
        'Sixteenth', 'Seventeenth', 'Eighteenth', 'Nineteenth', 'Twentieth'
    ];
    
    if (num <= words.length) {
        return words[num - 1];
    }
    return num + 'th';
}

// Function to create a new payment entry
function createPaymentEntry(number) {
    const word = numberToWord(number);
    
    return `
    <div class="payment-entry mt-4">
        <h4>${word} Payment:</h4>
        <div class="row align-items-center">
            <!-- Mode of Payment -->
            <div class="form-group col-md-2">
                <label class="col-form-label">Mode of Payment</label>
                <select name="mode_of_payment[]" class="form-control mode-of-payment">
                    <option value="cash">Cash</option>
                    <option value="cheque">Cheque</option>
                    <option value="net_banking">Net Banking</option>
                    <option value="upi">UPI</option>
                    <option value="online">Online</option>
                </select>
            </div>
            
            <!-- Payment Detail (hidden by default) -->
            <div class="form-group col-md-3 payment-detail" style="display:none;">
                <label class="col-form-label">Payment Detail</label>
                <input type="text" name="payment_detail[]" class="form-control" placeholder="Enter Payment Detail">
            </div>
            
            <!-- Date -->
            <div class="form-group col-md-2">
                <label class="col-form-label">Date</label>
                <input type="date" name='payment_date[]' class="form-control payment-date">
            </div>
            
            <!-- Amount -->
            <div class="form-group col-md-3">
                <label class="col-form-label">Amount (Rs)</label>
                <input type="text" name="amount[]" class="form-control payment-amount" placeholder="Enter Amount">
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
    `;
}

// Add new payment entry
$('#addPayment').on('click', function() {
    if (totalCost <= 0) {
        alert('Please finalize the total cost first');
        return;
    }
    
    paymentCount++;
    const newEntry = createPaymentEntry(paymentCount);
    $('#payment-section').append(newEntry);
    
    // Scroll to the new entry
    $('html, body').animate({
        scrollTop: $('#payment-section .payment-entry').last().offset().top - 100
    }, 300);
});

// Handle payment mode selection to show/hide payment detail
$(document).on('change', '.mode-of-payment', function() {
    const paymentDetailDiv = $(this).closest('.row').find('.payment-detail');
    const selectedMode = $(this).val();
    
    if (selectedMode === 'cash') {
        paymentDetailDiv.hide();
    } else {
        paymentDetailDiv.show();
    }
});

// Handle done button click
$(document).on('click', '.done-btn', function() {
    const paymentEntry = $(this).closest('.payment-entry');
    const amountInput = paymentEntry.find('.payment-amount');
    const amount = parseFloat(amountInput.val()) || 0;
    
    // Validation
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
    remainingAmount = totalCost - totalPaid;
    
    // Update UI
    $('#total_paid').val(totalPaid.toFixed(2));
    $('#remaining').val(remainingAmount.toFixed(2));
    
    // Disable the amount field after payment is done
    amountInput.prop('readonly', true);
    
    // Disable the done button
    $(this).prop('disabled', true);
    
    // Store the payment entry data
    const entryData = {
        mode: paymentEntry.find('.mode-of-payment').val(),
        detail: paymentEntry.find('.payment-detail input').val() || '',
        date: paymentEntry.find('.payment-date').val(),
        amount: amount
    };
    
    paymentEntries.push(entryData);
});

// Remove payment entry
$(document).on('click', '.remove-btn', function() {
    const paymentEntry = $(this).closest('.payment-entry');
    const amountInput = paymentEntry.find('.payment-amount');
    const amount = parseFloat(amountInput.val()) || 0;
    const isDone = amountInput.prop('readonly');
    
    // If this payment was already done, add the amount back to remaining
    if (isDone) {
        totalPaid -= amount;
        remainingAmount += amount;
        
        $('#total_paid').val(totalPaid.toFixed(2));
        $('#remaining').val(remainingAmount.toFixed(2));
        
        // Remove from payment entries array
        paymentEntries = paymentEntries.filter(entry => 
            !(entry.amount === amount && 
              entry.date === paymentEntry.find('.payment-date').val())
        );
    }
    
    // Decrement payment count if this wasn't the first payment
    if (paymentCount > 0) {
        paymentCount--;
    }
    
    // Remove from DOM
    paymentEntry.remove();
});

// Initialize when total cost is finalized
$('#finalizeCalculationBtn').on('click', function() {
    const calculatedTotal = parseFloat($('#total_cost').val()) || 0;

    if (calculatedTotal <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Incomplete Calculation',
            text: 'Please complete all calculations first',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    $('#payment_total_cost').val(calculatedTotal.toFixed(2));
    totalCost = calculatedTotal;
    
    totalPaid = 0;
    remainingAmount = totalCost;
    $('#total_paid').val('0.00');
    $('#remaining').val(remainingAmount.toFixed(2));
    
    $('.payment-entry').not(':first').remove();
    $('.payment-entry:first').find('.mode-of-payment').val('cash');
    $('.payment-entry:first').find('.payment-detail').hide().find('input').val('');
    $('.payment-entry:first').find('.payment-date').val('');
    $('.payment-entry:first').find('.payment-amount').val('').prop('readonly', false);
    $('.payment-entry:first').find('.done-btn').prop('disabled', false);
    
    paymentEntries = [];

    $('html, body').animate({
        scrollTop: $('#payment_total_cost').offset().top - 100
    }, 500);

    Swal.fire({
        icon: 'success',
        title: 'Calculation Finalized',
        text: 'Total cost has been finalized. You can now add payments.',
        confirmButtonColor: '#28a745'
    });
});

</script>


<script>
$('#submitBookingBtn').on('click', function() {
    // Validate form data
    if (totalCost <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Total Cost',
            text: 'Please finalize the total cost first',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Check if at least one payment is done
    if (paymentEntries.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Payments Added',
            text: 'Please add at least one payment entry',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Collect all form data
    const formData = new FormData();
    
    // Add regular form fields
    $('form').serializeArray().forEach(item => {
        formData.append(item.name, item.value);
    });
    
    // Add payment data as JSON
    formData.append('payment_json', JSON.stringify(paymentEntries));

    // Confirm submission
    Swal.fire({
        title: 'Confirm Booking Submission',
        text: 'Are you sure you want to submit this booking?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit via AJAX
            $.ajax({
                url: '{{ route("booking.store") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Booking has been successfully submitted.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.href = '{{ route("booking.all") }}';
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while submitting the booking.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).join('\n');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        title: 'Error!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    });
    
    // ==================== PAN NUMBER - AUTO UPPERCASE ====================
    $(document).on('input', '.pan-input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Also handle on keyup for better UX
    $(document).on('keyup', '.pan-input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // ==================== AADHAR NUMBER - NUMERIC ONLY ====================
    // Remove any non-numeric characters on input
    $(document).on('input', '.aadhar-input', function(e) {
        let value = $(this).val().replace(/[^0-9]/g, '');
        // Limit to 12 digits
        if (value.length > 12) {
            value = value.substring(0, 12);
        }
        $(this).val(value);
    });
    
    // Also handle on keyup to catch any missed characters
    $(document).on('keyup', '.aadhar-input', function(e) {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value.length > 12) {
            value = value.substring(0, 12);
        }
        if ($(this).val() !== value) {
            $(this).val(value);
        }
    });
    
    // Prevent non-numeric keypress (additional layer of protection)
    $(document).on('keypress', '.aadhar-input', function(e) {
        // Allow: backspace (8), delete (46), tab (9), escape (27), enter (13)
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            // Allow: Ctrl+A (65), Ctrl+C (67), Ctrl+V (86), Ctrl+X (88)
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            // Allow: home (36), end (35), left (37), right (39)
            (e.keyCode >= 35 && e.keyCode <= 39)) {
            return true;
        }
        // Only allow numeric keys (0-9) from main keyboard and numpad
        if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
            return false;
        }
        // Prevent if already 12 digits
        if ($(this).val().replace(/[^0-9]/g, '').length >= 12) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Handle paste event for Aadhar - remove non-numeric and limit to 12 digits
    $(document).on('paste', '.aadhar-input', function(e) {
        e.preventDefault();
        let pastedText = (e.originalEvent || e).clipboardData.getData('text/plain');
        let numericOnly = pastedText.replace(/[^0-9]/g, '');
        // Limit to 12 digits
        if (numericOnly.length > 12) {
            numericOnly = numericOnly.substring(0, 12);
        }
        $(this).val(numericOnly);
    });
    
    // Handle blur event to clean up any remaining non-numeric characters
    $(document).on('blur', '.aadhar-input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value.length > 12) {
            value = value.substring(0, 12);
        }
        $(this).val(value);
    });
});
</script>
@endsection