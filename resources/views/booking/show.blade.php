@extends('layouts.admin')

@section('page-title')
    {{ __('View Booking Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('booking.all') }}">{{ __('Bookings') }}</a></li>
    <li class="breadcrumb-item">{{ __('View Booking') }}</li>
@endsection

@section('action-button')
    <a href="{{ route('booking.all') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-arrow-left"></i> {{ __('Back') }}
    </a>
    <!-- @if(Auth::user()->can('Edit TimeSheet') || Auth::user()->type == 'company')
        <a href="{{ route('booking.edit', $bookingForm->id) }}" class="btn btn-sm btn-info">
            <i class="ti ti-edit"></i> {{ __('Edit') }}
        </a>
    @endif -->
    <a href="{{ route('booking.form.pdf', Crypt::encrypt($bookingForm->id)) }}" class="btn btn-sm btn-success">
        <i class="ti ti-download"></i> {{ __('Download PDF') }}
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <!-- Booking Information Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Booking Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Booking ID') }}</label>
                                <p class="form-control-static"><strong>#{{ $bookingForm->id }}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Booking Date') }}</label>
                                <p class="form-control-static">{{ $bookingForm->booking_date ? \Carbon\Carbon::parse($bookingForm->booking_date)->format('d-m-Y') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Project') }}</label>
                                <p class="form-control-static">{{ $bookingForm->project_name ?? ($bookingForm->project->project_name ?? 'N/A') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Unit') }}</label>
                                <p class="form-control-static">{{ $bookingForm->unit_name ?? ($bookingForm->unit->unit_name ?? 'N/A') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Unit Size') }}</label>
                                <p class="form-control-static">{{ $bookingForm->unit_size ?? 'N/A' }} sq.ft.</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Sales Executive') }}</label>
                                <p class="form-control-static">{{ $bookingForm->employee->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Status') }}</label>
                                <p class="form-control-static">
                                    @if($bookingForm->is_cancelled)
                                        <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                                    @elseif($bookingForm->remaining <= 0)
                                        @if($bookingForm->agreement == 'done')
                                            <span class="badge bg-success">{{ __('Agreement Done') }}</span>
                                        @else
                                            <span class="badge bg-success">{{ __('Completed') }}</span>
                                        @endif
                                    @else
                                        @if($bookingForm->agreement == 'done')
                                            <span class="badge bg-info">{{ __('Agreement') }}</span>
                                        @else
                                            <span class="badge bg-info">{{ __('Active') }}</span>
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Agreement Status') }}</label>
                                <p class="form-control-static">
                                    <span class="badge bg-{{ $bookingForm->agreement == 'done' ? 'success' : 'warning' }}">
                                        {{ ucfirst($bookingForm->agreement ?? 'Pending') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Applicant Details Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Primary Applicant Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Full Name') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Contact No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_contact_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Email') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_email ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Occupation') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_occupation ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Company') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_company ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Designation') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_designation ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Birth Date') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_birth_date ? \Carbon\Carbon::parse($bookingForm->primary_applicant_birth_date)->format('d-m-Y') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Nationality') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_nationality ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('PAN No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_pan_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Aadhar No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->primary_applicant_aadhar_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Applicant Details Card -->
            @if($bookingForm->secondary_applicant_name)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Secondary Applicant Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Full Name') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Contact No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_contact_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Email') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_email ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Occupation') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_occupation ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Company') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_company ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Designation') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_designation ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Birth Date') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_birth_date ? \Carbon\Carbon::parse($bookingForm->secondary_applicant_birth_date)->format('d-m-Y') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Nationality') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_nationality ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('PAN No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_pan_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">{{ __('Aadhar No') }}</label>
                                <p class="form-control-static">{{ $bookingForm->secondary_applicant_aadhar_no ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Financial Details Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Financial Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($bookingForm->plot_area)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Plot Area') }}</label>
                                <p class="form-control-static">{{ number_format($bookingForm->plot_area, 2) }} sq.ft.</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->carpet_area)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Carpet Area') }}</label>
                                <p class="form-control-static">{{ number_format($bookingForm->carpet_area, 2) }} sq.ft.</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->built_up_area)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Built Up Area') }}</label>
                                <p class="form-control-static">{{ number_format($bookingForm->built_up_area, 2) }} sq.ft.</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->rate_per_sq_ft)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Rate Per Sq.ft') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->rate_per_sq_ft, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->basic_cost)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Basic Cost') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->basic_cost, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->cost_infrastructure)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Infrastructure Cost') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->cost_infrastructure, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->gst)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('GST') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->gst, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->stamp_duty)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Stamp Duty') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->stamp_duty, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->registration)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Registration') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->registration, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->legal_charges)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Legal Charges') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->legal_charges, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->other)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Other Charges') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->other, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->maintenance_cost)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Maintenance Cost') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->maintenance_cost, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        @if($bookingForm->agreement_cost)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">{{ __('Agreement Cost') }}</label>
                                <p class="form-control-static">₹{{ number_format($bookingForm->agreement_cost, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><strong>{{ __('Total Cost') }}</strong></label>
                                <p class="form-control-static"><strong>₹{{ number_format($bookingForm->total_cost ?? 0, 2) }}</strong></p>
                            </div>
                        </div>
                        @if($bookingForm->remaining !== null)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label"><strong>{{ __('Remaining Amount') }}</strong></label>
                                <p class="form-control-static"><strong class="text-{{ $bookingForm->remaining > 0 ? 'danger' : 'success' }}">₹{{ number_format($bookingForm->remaining, 2) }}</strong></p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment Details Card -->
            @if($bookingForm->payment_data && is_array($bookingForm->payment_data) && count($bookingForm->payment_data) > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Payment Details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Mode') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('Amount (₹)') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalPaid = 0;
                                @endphp
                                @foreach($bookingForm->payment_data as $payment)
                                    @php
                                        $amount = $payment['amount'] ?? 0;
                                        $totalPaid += $amount;
                                    @endphp
                                    <tr>
                                        <td>{{ ucfirst($payment['mode'] ?? 'N/A') }}</td>
                                        <td>{{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d-m-Y') : 'N/A' }}</td>
                                        <td>{{ $payment['payment_detail'] ?? 'N/A' }}</td>
                                        <td>₹{{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">{{ __('Total Paid') }}:</th>
                                    <th>₹{{ number_format($totalPaid, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
