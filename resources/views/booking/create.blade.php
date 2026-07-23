<div class="container mx-auto p-4">
    {{ Form::open(['route' => ['booking.store']]) }}

    @if (\Auth::user()->type != 'employee')
        <div class="form-group col-md-12">
            {{ Form::label('employee_id', __('Employee'), ['class' => 'col-form-label']) }}
            {!! Form::select('employee_id', $employees, null, [
                'class' => 'form-control select2',
                'id' => 'choices-multiple',
                'placeholder' => __('Select Employee'),
                'required' => true,
            ]) !!}
        </div>
    @endif
    
    <!-- Step 1: Personal Info -->
    <div id="step1" class="step">
        <h4>Primary Applicant Details</h4>
        <div class="row">
            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_name', __('Primary Applicant Name'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_name', $bookingForm->primary_applicant_name ?? '', [
                    'class' => 'form-control required-field', 
                    'placeholder' => 'Enter Primary Applicant Name',
                    'required' => true
                ]) }}
                <div class="invalid-feedback primary_applicant_name-error"></div>
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_contact_no', __('Primary Applicant Contact No.'), ['class' => 'col-form-label']) }}
                {{ Form::text('primary_applicant_contact_no', $bookingForm->primary_applicant_contact_no ?? '', [
                    'class' => 'form-control required-field', 
                    'placeholder' => 'Enter Contact No.',
                    'required' => true
                ]) }}
                <div class="invalid-feedback primary_applicant_contact_no-error"></div>
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('primary_applicant_email', __('Primary Applicant Email'), ['class' => 'col-form-label']) }}
                {{ Form::email('primary_applicant_email', $bookingForm->primary_applicant_email ?? '', [
                    'class' => 'form-control required-field', 
                    'placeholder' => 'Enter Email',
                    'required' => true
                ]) }}
                <div class="invalid-feedback primary_applicant_email-error"></div>
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
                <select name="project_id" id="projectDropdown" class="form-control select2" required>
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
                    @if($bookingForm->unit_id && $bookingForm->unit)
                        <option value="{{ $bookingForm->unit_id }}" selected>{{ $bookingForm->unit->unit_name }}</option>
                    @endif
                </select>
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('unit_size', __('Unit Size (sq.ft)'), ['class' => 'col-form-label']) }}
                {{ Form::text('unit_size', $bookingForm->unit_size ?? '', ['class' => 'form-control', 'id' => 'unit_size', 'readonly' => true]) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('booking_date', __('Booking Date'), ['class' => 'col-form-label']) }}
                {{ Form::date('booking_date', $bookingForm->booking_date ?? '', ['class' => 'form-control']) }}
            </div>


            <div class="form-group col-md-4">
                {{ Form::label('plot_area', __('Plot Area (sq.ft)'), ['class' => 'col-form-label']) }}
                {{ Form::text('plot_area', $bookingForm->plot_area ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Plot Area']) }}
            </div>

            <div class="form-group col-md-4">
                {{ Form::label('carpet_area', __('Carpet Area (sq.ft)'), ['class' => 'col-form-label']) }}
                {{ Form::text('carpet_area', $bookingForm->carpet_area ?? '', ['class' => 'form-control', 'placeholder' => 'Enter Carpet Area']) }}
            </div>

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
                {{ Form::label('other', __('Other (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('other', $bookingForm->other ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Other Costs']) }}
            </div>

            <!-- Maintenance Cost Fields -->
            <div class="form-group col-md-4">
                {{ Form::label('maintenance_cost', __('Maintenance Cost (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('maintenance_cost', $bookingForm->maintenance_cost ?? '', ['class' => 'form-control calc-field', 'placeholder' => 'Enter Maintenance Cost']) }}
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
                {{ Form::text('confirm_total_cost', '', ['class' => 'form-control', 'id' => 'confirm_total_cost', 'readonly' => true]) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('remaining', __('Remaining Amount (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('remaining', $bookingForm->remaining ?? '', ['class' => 'form-control', 'id' => 'remaining', 'readonly' => true]) }}
            </div>
            <div class="form-group col-md-4">
                {{ Form::label('total_paid', __('Total Paid Amount (Rs)'), ['class' => 'col-form-label']) }}
                {{ Form::text('total_paid', '0', ['class' => 'form-control', 'id' => 'total_paid', 'readonly' => true]) }}
            </div>
        </div>

        <!-- Payment Details Section -->
        <h4>First Payment :</h4>
        <div id="payment-section">
            <div class="payment-entry">
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
        <p class="text-primary mt-3" id="addPayment" style="cursor: pointer;">Add Next Payment Data</p>

        <!-- Submit Buttons -->
        <div class="d-flex justify-content-start col-md-12 gap-3 mt-3 ">
            <button type="button" class="btn btn-secondary mt-3" onclick="prevStep(2)">Back</button>
            <input type="submit" value="{{ __('Create') }}" class="btn btn-primary mt-3">
        </div>
    </div>

    {{ Form::close() }}
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Track all payment entries
    let paymentEntries = [];
    let totalPaid = 0;
    let totalCost = 0;
    let remainingAmount = 0;

    

    // Function to calculate total cost in step 2
    function calculateTotalCost() {
        const fields = [
            'rate_per_sq_ft', 'basic_cost', 'cost_infrastructure', 
            'gst', 'stamp_duty', 'registration', 'other',
            'maintenance_cost'
        ];
        
        let total = 0;
        fields.forEach(field => {
            const value = parseFloat(document.getElementById(field).value) || 0;
            total += value;
        });
        
        document.getElementById('total_cost').value = total.toFixed(2);
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
            totalCost = parseFloat(document.getElementById('total_cost').value) || 0;
            document.getElementById('confirm_total_cost').value = totalCost.toFixed(2);
            remainingAmount = totalCost;
            document.getElementById('remaining').value = remainingAmount.toFixed(2);
            document.getElementById('total_paid').value = '0.00';
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
        if (e.target.closest('.done-btn')) {
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
            
            // Disable the amount field after payment is done
            amountInput.readOnly = true;
            
            // Disable the done button
            e.target.closest('.done-btn').disabled = true;
            
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
        const paymentEntries = document.querySelectorAll('.payment-entry');
        const newEntry = paymentEntries[0].cloneNode(true);
        
        // Clear values
        newEntry.querySelector('.mode-of-payment').value = 'cash';
        newEntry.querySelector('.payment-detail input').value = '';
        newEntry.querySelector('.payment-date').value = '';
        newEntry.querySelector('.payment-amount').value = '';
        newEntry.querySelector('.payment-amount').readOnly = false;
        newEntry.querySelector('.done-btn').disabled = false;
        
        // Update payment label
        const paymentLabels = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth'];
        const paymentNumber = paymentEntries.length + 1;
        const paymentLabel = paymentNumber <= paymentLabels.length ? paymentLabels[paymentNumber - 1] : `${paymentNumber}th`;
        
        // Find or create the h4 element for the payment title
        let paymentTitle = newEntry.querySelector('h4');
        if (!paymentTitle) {
            paymentTitle = document.createElement('h4');
            newEntry.prepend(paymentTitle);
        }
        paymentTitle.textContent = `${paymentLabel} Payment :`;
        
        // Hide payment detail by default
        newEntry.querySelector('.payment-detail').style.display = 'none';
        
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

   // Initialize form
   document.addEventListener('DOMContentLoaded', function () {
        const projectDropdown = document.getElementById('projectDropdown');
        const unitDropdown = document.getElementById('unitDropdown');

        projectDropdown.addEventListener('change', function () {
            const projectId = this.value;

            // Clear the Unit dropdown and show a loading message
            unitDropdown.innerHTML = '<option value="">Loading...</option>';

            if (projectId) {
                // Fetch units for the selected project
                fetch(`/get-units-by-project/${projectId}`)
                    .then(response => response.json())
                    .then(data => {
                        unitDropdown.innerHTML = '<option value="">Select Unit</option>';
                        data.units.forEach(unit => {
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = unit.unit_name;
                            unitDropdown.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching units:', error);
                        unitDropdown.innerHTML = '<option value="">Error loading units</option>';
                    });
            } else {
                unitDropdown.innerHTML = '<option value="">Select Project First</option>';
            }
        });
    });

$(document).ready(function() {
    // Debug function
    function debug(message) {
        $('#unit-debug').append('<div>' + message + '</div>');
        console.log(message);
    }

    debug('Document ready');

    // Project dropdown change handler
    $('#projectDropdown').on('change', function() {
        const projectId = $(this).val();
        const $unitDropdown = $('#unitDropdown');
        
        console.log('Project changed to:', projectId);
        
        // Clear and disable dropdown while loading
        $unitDropdown.empty().append('<option value="">Loading units...</option>').prop('disabled', true);
        
        if (!projectId) {
            $unitDropdown.empty().append('<option value="">Select project first</option>');
            return;
        }
        
        $.ajax({
            url: `/get-units-by-project/${projectId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Units received:', response);
                
                $unitDropdown.empty().append('<option value="">Select unit</option>');
                
                if (response.units && response.units.length > 0) {
                    response.units.forEach(function(unit) {
                        $unitDropdown.append(
                            $('<option></option>')
                                .val(unit.id)
                                .text(unit.name)
                                .attr('data-size', unit.unit_size) // Store size in data attribute
                        );
                    });
                    
                    $unitDropdown.prop('disabled', false);
                    
                    // If editing existing booking, set the unit
                    @if(isset($bookingForm) && $bookingForm->unit_id)
                        $unitDropdown.val('{{ $bookingForm->unit_id }}').trigger('change');
                    @endif
                } else {
                    $unitDropdown.append('<option value="">No units available</option>');
                }
                
                // Force dropdown to update visually
                if ($unitDropdown.hasClass('select2-hidden-accessible')) {
                    $unitDropdown.select2('destroy').select2();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading units:', error);
                $unitDropdown.empty().append('<option value="">Error loading units</option>');
            }
        });
    });

    // Handle unit selection
    $('#unitDropdown').on('change', function() {
        const unitId = $(this).val();
        console.log('Unit selected:', unitId, $(this).find('option:selected').text());
        
        if (!unitId) {
            $('#unit_size').val('');
            return;
        }
        
        // Get size from data attribute if available
        const size = $(this).find('option:selected').data('size');
        if (size) {
            $('#unit_size').val(size);
            return;
        }
        
        // Fallback to AJAX if size not in data attribute
        $.ajax({
            url: `/get-unit-details/${unitId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#unit_size').val(response.unit_size || '');
            },
            error: function() {
                $('#unit_size').val('Error loading size');
            }
        });
    });
    
    // Initialize if project is already selected
    @if(isset($bookingForm) && $bookingForm->project_id)
        debug('Initializing with project: {{ $bookingForm->project_id }}');
        $('#projectDropdown').trigger('change');
    @endif
});
</script>




<style>
    .hidden { display: none; }
    .text-danger { color: red; }

    /* Force dropdown visibility */
    #unitDropdown {
        display: block !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 9999 !important;
        width: 100% !important;
        height: auto !important;
        color: #000;
    }
    
    /* Debug box styling */
    #unit-debug {
        max-height: 200px;
        overflow-y: auto;
        background: #f8f9fa;
        border: 1px solid #dc3545;
        padding: 10px;
        margin-top: 10px;
        font-family: monospace;
        font-size: 12px;
    }
</style>
