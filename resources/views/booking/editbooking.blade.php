@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="card mb-4">
        <div class="card-header">
            <h4>Select Booking to Edit</h4>
        </div>
        <div class="card-body">
            <form id="editBookingForm">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="col-form-label">Project</label>
                        <select name="project_id" id="projectFilter" class="form-control select2" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="col-form-label">Booking</label>
                        <select name="booking_id" id="bookingFilter" class="form-control" disabled required>
                            <option value="">Select Booking</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12 ">
                        <div class="d-flex justify-content-end gap-2 booking-buttons-mobile">
                            <button type="button" id="loadBookingBtn" class="btn btn-primary" disabled>Load Booking Data</button>
                            <a href="{{ route('edit.booking') }}" class="btn btn-secondary">Reset</a>
                            <button type="button" id="cancelBookingBtn" class="btn btn-danger">Cancel Booking</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="bookingFormContainer" style="display: none;">
        <!-- Booking form will be loaded here via AJAX -->
    </div>
</div>

<script>
$(document).ready(function() {
    // ==================== PROJECT FILTER HANDLING ====================
    $('#projectFilter').on('change', function() {
        const projectId = $(this).val();
        const $bookingFilter = $('#bookingFilter');
        
        $bookingFilter.empty().append('<option value="">Select Booking</option>');
        $('#loadBookingBtn').prop('disabled', true);
        
        if (!projectId) {
            $bookingFilter.prop('disabled', true);
            return;
        }
        
        $bookingFilter.prop('disabled', true);
        $bookingFilter.empty().append('<option value="">Loading bookings...</option>');
        
        $.ajax({
            url: '/hrm_rising/booking/get-bookings-by-project/' + projectId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $bookingFilter.empty().append('<option value="">Select Booking</option>');
                
                if (response.bookings && response.bookings.length > 0) {
                    $.each(response.bookings, function(index, booking) {
                        const optionText = booking.primary_applicant_name + ' - ' + (booking.primary_applicant_contact_no || 'Unit N/A');
                        $bookingFilter.append($('<option>', {
                            value: booking.id,
                            text: optionText
                        }));
                    });
                } else {
                    $bookingFilter.append('<option value="">No bookings found</option>');
                }
                
                $bookingFilter.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error loading bookings:', error);
                $bookingFilter.empty().append('<option value="">Error loading bookings</option>');
                $bookingFilter.prop('disabled', false);
            }
        });
    });
    
    // Enable load button when booking is selected
    $('#bookingFilter').on('change', function() {
        $('#loadBookingBtn').prop('disabled', !$(this).val());
    });
    
    // ==================== LOAD BOOKING DATA ====================
    $('#loadBookingBtn').on('click', function() {
        const bookingId = $('#bookingFilter').val();
        
        if (!bookingId) return;
        
        $('#bookingFormContainer').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Loading booking data...</div>');
        $('#bookingFormContainer').show();
        
        $.ajax({
            url: '/hrm_rising/load-booking-data',
            type: 'POST',
            data: {
                booking_id: bookingId,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    $('#bookingFormContainer').html('<div class="alert alert-danger">' + response.error + '</div>');
                    return;
                }
                
                const booking = response.booking;
                const projectType = response.project_type;
                
                // Function to safely get nested properties with fallback
                function getProperty(obj, prop, fallback = '') {
                    return obj && obj[prop] !== undefined && obj[prop] !== null ? obj[prop] : fallback;
                }

                const formHtml = `
                    <div class="card">
                        <div class="card-header text-center">
                            <h4>Edit Booking for ${getProperty(booking, 'primary_applicant_name', 'Applicant')}</h4>
                        </div>
                    </div>

                    <div>
                        <form id="updateBookingForm">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="booking_id" value="${booking.id}">
                            <input type="hidden" name="project_type" id="project_type" value="${projectType}">
                            
                            <!-- Applicant Details Section -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4>Applicant Details :</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Primary Applicant Section -->
                                    <h5 class="mb-3">Primary Applicant Details</h5>
                                    <div class="row p-3 mb-3">
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Full Name</label>
                                            <input type="text" name="primary_applicant_name" class="form-control" value="${getProperty(booking, 'primary_applicant_name')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Contact No.</label>
                                            <input type="text" name="primary_applicant_contact_no" class="form-control" value="${getProperty(booking, 'primary_applicant_contact_no')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Email</label>
                                            <input type="email" name="primary_applicant_email" class="form-control" value="${getProperty(booking, 'primary_applicant_email')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Occupation</label>
                                            <input type="text" name="primary_applicant_occupation" class="form-control" value="${getProperty(booking, 'primary_applicant_occupation')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Company</label>
                                            <input type="text" name="primary_applicant_company" class="form-control" value="${getProperty(booking, 'primary_applicant_company')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Designation</label>
                                            <input type="text" name="primary_applicant_designation" class="form-control" value="${getProperty(booking, 'primary_applicant_designation')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Birth Date</label>
                                            <input type="date" name="primary_applicant_birth_date" class="form-control" value="${getProperty(booking, 'primary_applicant_birth_date')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Nationality</label>
                                            <input type="text" name="primary_applicant_nationality" class="form-control" value="${getProperty(booking, 'primary_applicant_nationality')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">PAN No</label>
                                            <input type="text" name="primary_applicant_pan_no" class="form-control" value="${getProperty(booking, 'primary_applicant_pan_no')}" >
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Aadhar No</label>
                                            <input type="text" name="primary_applicant_aadhar_no" class="form-control" value="${getProperty(booking, 'primary_applicant_aadhar_no')}">
                                        </div>
                                    </div>

                                    <!-- Secondary Applicant Section -->
                                    <h5 class="mb-3">Secondary Applicant Details</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Name</label>
                                            <input type="text" name="secondary_applicant_name" class="form-control" value="${getProperty(booking, 'secondary_applicant_name')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Contact No.</label>
                                            <input type="text" name="secondary_applicant_contact_no" class="form-control" value="${getProperty(booking, 'secondary_applicant_contact_no')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Email</label>
                                            <input type="email" name="secondary_applicant_email" class="form-control" value="${getProperty(booking, 'secondary_applicant_email')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Occupation</label>
                                            <input type="text" name="secondary_applicant_occupation" class="form-control" value="${getProperty(booking, 'secondary_applicant_occupation')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Company</label>
                                            <input type="text" name="secondary_applicant_company" class="form-control" value="${getProperty(booking, 'secondary_applicant_company')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Designation</label>
                                            <input type="text" name="secondary_applicant_designation" class="form-control" value="${getProperty(booking, 'secondary_applicant_designation')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Birth Date</label>
                                            <input type="date" name="secondary_applicant_birth_date" class="form-control" value="${getProperty(booking, 'secondary_applicant_birth_date')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Nationality</label>
                                            <input type="text" name="secondary_applicant_nationality" class="form-control" value="${getProperty(booking, 'secondary_applicant_nationality')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">PAN No.</label>
                                            <input type="text" name="secondary_applicant_pan_no" class="form-control" value="${getProperty(booking, 'secondary_applicant_pan_no')}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Aadhar No.</label>
                                            <input type="text" name="secondary_applicant_aadhar_no" class="form-control" value="${getProperty(booking, 'secondary_applicant_aadhar_no')}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Booking Calculation Section -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4>Booking Calculation & Area Details :</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Project Name -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Project Name</label>
                                            <input type="text" class="form-control" 
                                                value="${getProperty(response, 'project_name')}" readonly>
                                            <input type="hidden" name="project_id" 
                                                value="${getProperty(booking, 'project_id')}">
                                        </div>

                                        
                                        <!-- Unit Selection -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Unit Name</label>
                                            <select name="unit_id" id="unitDropdown" class="form-control" required>
                                                <option value="">Select Unit</option>
                                                ${response.units.map(unit => `
                                                    <option value="${unit.id}" 
                                                        data-size="${unit.unit_size}"
                                                        ${unit.id == booking.unit_id ? 'selected' : ''}>
                                                        ${unit.unit_name}
                                                    </option>
                                                `).join('')}
                                            </select>
                                        </div>
                                        
                                        <!-- Unit Size -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Unit Size (sq.ft)</label>
                                            <input type="text" name="unit_size" class="form-control" value="${getProperty(booking, 'unit_size')}" readonly id="unit_size">
                                        </div>

                                        <!-- Booking Date -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Booking Date</label>
                                            <input type="date" name="booking_date" class="form-control" value="${getProperty(booking, 'booking_date')}">
                                        </div>

                                        ${projectType == 1 || projectType == 2 ? `
                                            <!-- Residential/Commercial Fields -->
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Carpet Area (sq.ft)</label>
                                                <input type="text" name="carpet_area" class="form-control residential-commercial-input" value="${getProperty(booking, 'carpet_area')}" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Built Up Area (sq.ft)</label>
                                                <input type="text" name="built_up_area" class="form-control residential-commercial-result" value="${getProperty(booking, 'built_up_area')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Rate Per Sq.Ft (Rs)</label>
                                                <input type="text" name="rate_per_sq_ft" class="form-control residential-commercial-input" value="${getProperty(booking, 'rate_per_sq_ft')}" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Cost Towards Infrastructure (Rs)</label>
                                                <input type="text" name="cost_infrastructure" class="form-control residential-commercial-input" value="${getProperty(booking, 'cost_infrastructure')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Total Agreement Cost (Rs)</label>
                                                <input type="text" name="agreement_cost" class="form-control residential-commercial-result" value="${getProperty(booking, 'agreement_cost')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">GST (Rs)</label>
                                                <input type="text" name="gst" class="form-control residential-commercial-result" value="${getProperty(booking, 'gst')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Stamp Duty (Rs)</label>
                                                <input type="text" name="stamp_duty" class="form-control residential-commercial-result" value="${getProperty(booking, 'stamp_duty')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Registration (Rs)</label>
                                                <input type="text" name="registration" class="form-control residential-commercial-result" value="${getProperty(booking, 'registration')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Legal Charges (Rs)</label>
                                                <input type="text" name="legal_charges" class="form-control residential-commercial-optional" value="${getProperty(booking, 'legal_charges')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Other (Rs)</label>
                                                <input type="text" name="other" class="form-control residential-commercial-optional" value="${getProperty(booking, 'other')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Maintenance Cost (Rs)</label>
                                                <input type="text" name="maintenance_cost" class="form-control residential-commercial-optional" value="${getProperty(booking, 'maintenance_cost')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Total Cost (Rs)</label>
                                                <input type="text" name="total_cost" class="form-control residential-commercial-result" value="${getProperty(booking, 'total_cost')}" readonly id="total_cost">
                                            </div>
                                        ` : ''}

                                        ${projectType == 3 ? `
                                            <!-- Plotting Fields -->
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Plot Area (sq.ft)</label>
                                                <input type="text" name="plot_area" class="form-control plotting-calc-input" value="${getProperty(booking, 'plot_area')}" required readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Rate Per Sq.Ft (Rs)</label>
                                                <input type="text" name="rate_per_sq_ft" class="form-control plotting-calc-input" value="${getProperty(booking, 'rate_per_sq_ft')}" required readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Basic Cost (Rs)</label>
                                                <input type="text" name="basic_cost" class="form-control plotting-calc-result" value="${getProperty(booking, 'basic_cost')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Infrastructure Cost (Rs)</label>
                                                <input type="text" name="cost_infrastructure" class="form-control plotting-calc-input" value="${getProperty(booking, 'cost_infrastructure')}" required readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Agreement Cost (Rs)</label>
                                                <input type="text" name="agreement_cost" class="form-control plotting-calc-result" value="${getProperty(booking, 'agreement_cost')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">GST (Rs)</label>
                                                <input type="text" name="gst" class="form-control plotting-gst-input" value="${getProperty(booking, 'gst')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Stamp Duty (Rs)</label>
                                                <input type="text" name="stamp_duty" class="form-control plotting-calc-result" value="${getProperty(booking, 'stamp_duty')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Registration (Rs)</label>
                                                <input type="text" name="registration" class="form-control plotting-calc-result" value="${getProperty(booking, 'registration')}" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Legal Charges (Rs)</label>
                                                <input type="text" name="legal_charges" class="form-control plotting-optional-input" value="${getProperty(booking, 'legal_charges')}" >
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Other (Rs)</label>
                                                <input type="text" name="other" class="form-control plotting-optional-input" value="${getProperty(booking, 'other')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Maintenance Cost (Rs)</label>
                                                <input type="text" name="maintenance_cost" class="form-control plotting-optional-input" value="${getProperty(booking, 'maintenance_cost')}">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Total Cost (Rs)</label>
                                                <input type="text" name="total_cost" class="form-control plotting-calc-result" value="${getProperty(booking, 'total_cost')}" readonly id="total_cost">
                                            </div>
                                        ` : ''}
                                    </div>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" id="finalizeCalculationBtn" class="btn" style="background-color: #ea3538; border-color: #ea3538; color: white;">
                                            Done - Finalize Total Cost
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Details Section -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4>Payment Details :</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Total Cost -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Total Cost (Rs)</label>
                                            <input type="text" class="form-control" value="${getProperty(booking, 'total_cost')}" readonly id="payment_total_cost">
                                        </div>
                                        
                                        <!-- Remaining Amount -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Remaining Amount (Rs)</label>
                                            <input type="text" class="form-control" value="${getProperty(booking, 'remaining')}" readonly id="remaining">
                                        </div>
                                        
                                        <!-- Total Paid Amount -->
                                        <div class="form-group col-md-4">
                                            <label class="col-form-label">Total Paid Amount (Rs)</label>
                                            <input type="text" class="form-control" value="${(parseFloat(getProperty(booking, 'total_cost', 0)) - parseFloat(getProperty(booking, 'remaining', 0))).toFixed(2)}" readonly id="total_paid">
                                        </div>
                                    </div>

                                    <!-- Payment Entries Container -->
                                    <div id="payment-section">
                                        ${booking.payment_data && Array.isArray(booking.payment_data) ? booking.payment_data.map((payment, index) => `
                                            <div class="payment-entry mt-4">
                                                <h4>${['First', 'Second', 'Third', 'Fourth', 'Fifth'][index] || (index + 1) + 'th'} Payment:</h4>
                                                <div class="row align-items-center">
                                                    <!-- Mode of Payment -->
                                                    <div class="form-group col-md-2">
                                                        <label class="col-form-label">Mode of Payment</label>
                                                        <select name="mode_of_payment[]" class="form-control mode-of-payment" disabled>
                                                            <option value="cash" ${payment.mode === 'cash' ? 'selected' : ''} >Cash</option >
                                                            <option value="cheque" ${payment.mode === 'cheque' ? 'selected' : ''}>Cheque</option>
                                                            <option value="net_banking" ${payment.mode === 'net_banking' ? 'selected' : ''}>Net Banking</option>
                                                            <option value="upi" ${payment.mode === 'upi' ? 'selected' : ''}>UPI</option>
                                                            <option value="online" ${payment.mode === 'online' ? 'selected' : ''}>Online</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <!-- Payment Detail -->
                                                    <div class="form-group col-md-3 payment-detail" style="${payment.mode === 'cash' ? 'display:none;' : ''}">
                                                        <label class="col-form-label">Payment Detail</label>
                                                        <input type="text" name="payment_detail[]" class="form-control" value="${getProperty(payment, 'payment_detail')}" readonly>
                                                    </div>
                                                    
                                                    <!-- Date -->
                                                    <div class="form-group col-md-2">
                                                        <label class="col-form-label">Date</label>
                                                        <input type="date" name="payment_date[]" class="form-control payment-date" value="${getProperty(payment, 'date')}" readonly>
                                                    </div>
                                                    
                                                    <!-- Amount -->
                                                    <div class="form-group col-md-3">
                                                        <label class="col-form-label">Amount (Rs)</label>
                                                        <input type="text" name="amount[]" class="form-control payment-amount" value="${getProperty(payment, 'amount')}" readonly>
                                                    </div>
                                                    
                                                    <!-- Action Buttons -->

                                                </div>
                                            </div>
                                        `).join('') : ''}
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
                                        Update Booking
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                `;
                
                $('#bookingFormContainer').html(formHtml);
                
                // Initialize all the calculation scripts
                initializeCalculationScripts(projectType);
                
                // Initialize payment system with existing payments
                initializePaymentSystem(
                    parseFloat(getProperty(booking, 'total_cost', 0)),
                    parseFloat(getProperty(booking, 'remaining', 0)),
                    booking.payment_data || []
                );
            },
            error: function(xhr) {
                let errorMessage = 'Error loading booking data';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                $('#bookingFormContainer').html('<div class="alert alert-danger">' + errorMessage + '</div>');
            }
        });
    });
    
    // ==================== CALCULATION SCRIPTS INITIALIZATION ====================
    function initializeCalculationScripts(projectType) {
        // Handle unit selection change to show size
        $(document).on('change', '#unitDropdown', function() {
            const selectedUnit = $(this).find('option:selected');
            const unitSize = selectedUnit.data('size');
            $('#unit_size').val(unitSize || '');
        });

        // Initialize calculations based on project type
        if (projectType == 3) {
            setupPlottingCalculations();
        } else if (projectType == 1 || projectType == 2) {
            setupResidentialCommercialCalculations();
            // Trigger initial calculation
            setTimeout(function() {
                calculateResidentialCommercialCosts();
            }, 100);
        }
        
        // Handle finalize calculation button
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
            
            const totalPaid = parseFloat($('#total_paid').val()) || 0;
            const remaining = calculatedTotal - totalPaid;
            $('#remaining').val(remaining.toFixed(2));
            
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
    }
    
    // ==================== PLOTTING CALCULATIONS ====================
    function setupPlottingCalculations() {
        // Make calculated fields readonly (except GST)
        $('.plotting-calc-result').not('#gst, #registration').prop('readonly', true);
        
        // Calculate when input fields change (including GST)
        $('.plotting-calc-input, .plotting-gst-input, .plotting-optional-input').on('input', calculatePlottingCosts);
        
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
        const registration = totalAgreementCost * 0.01; // 1% Registration
        
        $('#stamp_duty').val(stampDuty.toFixed(2));
        $('#registration').val(registration.toFixed(2));
        
        // Get optional fields
        const legalCharges = parseFloat($('#legal_charges').val()) || 0;
        const other = parseFloat($('#other').val()) || 0;
        const maintenanceCost = parseFloat($('#maintenance_cost').val()) || 0;
        
        // Calculate total cost
        const totalCost = totalAgreementCost + gst + stampDuty + registration + 
                        legalCharges + other + maintenanceCost;
        $('#total_cost').val(totalCost.toFixed(2));
    }
    
    // ==================== RESIDENTIAL/COMMERCIAL CALCULATIONS ====================
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
        let gstRate, stampDutyRate, registrationRate;
        
        if (projectType == 1) { // Residential
            gstRate = 0.01; // 1%
            stampDutyRate = 0.06; // 6%
            registrationRate = 0.01; // 1%
        } else if (projectType == 2) { // Commercial
            gstRate = 0.12; // 12%
            stampDutyRate = 0.06; // 6%
            registrationRate = 0.01; // 1%
        }
        
        const gst = totalAgreementCost * gstRate;
        const stampDuty = totalAgreementCost * stampDutyRate;
        const registration = totalAgreementCost * registrationRate;
        
        $('#gst').val(gst.toFixed(2));
        $('#stamp_duty').val(stampDuty.toFixed(2));
        $('#registration').val(registration.toFixed(2));
        
        // Get optional fields
        const legalCharges = parseFloat($('#legal_charges').val()) || 0;
        const other = parseFloat($('#other').val()) || 0;
        const maintenanceCost = parseFloat($('#maintenance_cost').val()) || 0;
        
        // Calculate total cost
        const totalCost = totalAgreementCost + gst + stampDuty + registration + 
                        legalCharges + other + maintenanceCost;
        $('#total_cost').val(totalCost.toFixed(2));
    }
    
    // ==================== PAYMENT SYSTEM ====================
    function initializePaymentSystem(totalCost = 0, remaining = 0, existingPayments = []) {
        // Payment system variables
        let paymentEntries = existingPayments || [];
        let paymentCount = paymentEntries.length;
        
        // Set initial values
        $('#payment_total_cost').val(totalCost.toFixed(2));
        $('#remaining').val(remaining.toFixed(2));
        $('#total_paid').val((totalCost - remaining).toFixed(2));
        
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
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            
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
                        <input type="date" name="payment_date[]" class="form-control payment-date" value="${today}">
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
            const totalCost = parseFloat($('#payment_total_cost').val()) || 0;
            
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
            const totalCost = parseFloat($('#payment_total_cost').val()) || 0;
            const remaining = parseFloat($('#remaining').val()) || totalCost;
            const totalPaid = parseFloat($('#total_paid').val()) || 0;
            
            // Validation
            if (amount <= 0) {
                alert('Please enter a valid payment amount');
                return;
            }
            
            if (amount > remaining) {
                alert('Payment amount cannot be greater than remaining amount');
                return;
            }
            
            // Update totals
            const newTotalPaid = totalPaid + amount;
            const newRemaining = totalCost - newTotalPaid;
            
            // Update UI
            $('#total_paid').val(newTotalPaid.toFixed(2));
            $('#remaining').val(newRemaining.toFixed(2));
            
            // Disable the amount field after payment is done
            amountInput.prop('readonly', true);
            
            // Disable the done button
            $(this).prop('disabled', true);
            
            // Store the payment entry data
            const entryData = {
                mode: paymentEntry.find('.mode-of-payment').val(),
                payment_detail: paymentEntry.find('.payment-detail input').val() || '',
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
            const totalCost = parseFloat($('#payment_total_cost').val()) || 0;
            const totalPaid = parseFloat($('#total_paid').val()) || 0;
            const remaining = parseFloat($('#remaining').val()) || totalCost;
            
            // If this payment was already done, add the amount back to remaining
            if (isDone) {
                const newTotalPaid = totalPaid - amount;
                const newRemaining = totalCost - newTotalPaid;
                
                $('#total_paid').val(newTotalPaid.toFixed(2));
                $('#remaining').val(newRemaining.toFixed(2));
                
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
    }
    
    // ==================== FORM SUBMISSION ====================
    $(document).on('click', '#submitBookingBtn', function() {
        const totalCost = parseFloat($('#payment_total_cost').val()) || 0;
        
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
        const paymentEntries = $('.payment-entry').length;
        if (paymentEntries === 0) {
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
        
        // Add all form fields
        $('#updateBookingForm').find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            
            if (name && value !== undefined) {
                // Handle array fields (payment fields)
                if (name.endsWith('[]')) {
                    const baseName = name.replace('[]', '');
                    if (!formData.has(baseName)) {
                        formData.append(baseName, value);
                    } else {
                        formData.append(baseName, formData.get(baseName) + ',' + value);
                    }
                } else {
                    formData.append(name, value);
                }
            }
        });
        
        // Add CSRF token
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('_method', 'PUT');
        
    // In your form submission code, replace the payment data collection with:
    const paymentData = [];
    $('.payment-entry').each(function() {
        const payment = {
            mode: $(this).find('.mode-of-payment').val(),
            payment_detail: $(this).find('.payment-detail input').val() || '',
            date: $(this).find('.payment-date').val() || new Date().toISOString().split('T')[0],
            amount: parseFloat($(this).find('.payment-amount').val()) || 0
        };
        paymentData.push(payment);
    });

    formData.append('payment_json', JSON.stringify(paymentData));
        
        // Get the booking ID
        const bookingId = $('input[name="booking_id"]').val();

        // Confirm submission
        Swal.fire({
            title: 'Confirm Booking Update',
            text: 'Are you sure you want to update this booking?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit via AJAX - Use the correct Laravel route
                $.ajax({
                    url: '{{ route("booking.update", ":id") }}'.replace(':id', bookingId),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Booking has been successfully updated.',
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            window.location.href = '{{ route("booking.all") }}';
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating the booking.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Show validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.status === 404) {
                            errorMessage = 'The update route was not found. Please check your server routes.';
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
    });


    // Add this to your initializeCalculationScripts function
    function initializeCalculationScripts(projectType) {
        // Handle unit selection change to show size
        $(document).on('change', '#unitDropdown', function() {
            const selectedUnit = $(this).find('option:selected');
            const unitSize = selectedUnit.data('size');
            $('#unit_size').val(unitSize || '');
        });

        if (projectType == 3) { // Plotting project
            setupPlottingCalculations();
            // Trigger initial calculation
            setTimeout(function() {
                calculatePlottingCosts();
            }, 100);
        } else if (projectType == 1 || projectType == 2) { // Residential or Commercial
            setupResidentialCommercialCalculations();
            // Trigger initial calculation
            setTimeout(function() {
                calculateResidentialCommercialCosts();
            }, 100);
        }
        
        // Handle finalize calculation button
        $('#finalizeCalculationBtn').on('click', function() {
            if (projectType == 3) {
                calculatePlottingCosts();
            } else if (projectType == 1 || projectType == 2) {
                calculateResidentialCommercialCosts();
            }
            
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
            
            const totalPaid = parseFloat($('#total_paid').val()) || 0;
            const remaining = calculatedTotal - totalPaid;
            $('#remaining').val(remaining.toFixed(2));
            
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
    }

    // ==================== CANCEL BOOKING FUNCTIONALITY ====================
    $(document).on('click', '#cancelBookingBtn', function() {
        const bookingId = $('#bookingFilter').val();
        
        if (!bookingId) {
            Swal.fire({
                icon: 'error',
                title: 'No Booking Selected',
                text: 'Please select a booking first',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Cancellation',
            text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel booking!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Processing...',
                    text: 'Cancelling booking',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request to cancel booking
                $.ajax({
                    url: '/booking/' + bookingId + '/cancel',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Booking has been successfully cancelled.',
                            icon: 'success',
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            // Reload the page to reflect changes
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while cancelling the booking.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
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
    });
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
@endsection