@extends('layouts.admin')

@section('page-title')
   {{ __('Manage Employee Salary') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Employee Salary') }}</li>
@endsection


@section('content')
<div class="row">
    <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                <th class="text-start">{{ __('Employee Id') }}</th>
                                <th class="text-start">{{ __('Name') }}</th>
                                <th class="text-start">{{ __('Payroll Type') }}</th>
                                <th class="text-start">{{ __('Salary') }}</th>
                                <th class="text-start">{{ __('Net Salary') }}</th>
                                @if(!request('own'))
                                    <th class="text-center" width="200px">{{ __('Action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                <tr>
                                    <td class="text-start">
                                        <a href="#" data-url="{{ route('setsalary.popup', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}" 
                                            data-ajax-popup="true" data-title="{{ __('Manage Salary') }}: {{ $employee->full_name }}" 
                                            data-size="xl" class="btn btn-outline-primary">
                                            {{ \Auth::user()->employeeIdFormat($employee->employee_id) }}
                                        </a>
                                    </td>
                                    <td class="text-start">{{ $employee->full_name }}</td>
                                    <td class="text-start">{{ !empty($employee->salary_type()) ? $employee->salary_type() : '-' }}</td>
                                    <td class="text-start">{{ \Auth::user()->priceFormat($employee->salary) }}</td>
                                    <td class="text-start">{{ !empty($employee->get_net_salary()) ? \Auth::user()->priceFormat($employee->get_net_salary()) : '-' }}
                                    </td>
                                    @if(!request('own'))
                                    <td class="Action">
                                        <span>
                                            <div class="action-btn bg-warning ms-2">
                                                <a href="#" data-url="{{ route('setsalary.popup', \Illuminate\Support\Facades\Crypt::encrypt($employee->id)) }}" 
                                                    data-ajax-popup="true" data-title="{{ __('Manage Salary') }}: {{ $employee->full_name }}" 
                                                    data-size="xl" class="mx-3 btn btn-sm align-items-center">
                                                    <i class="ti ti-eye text-white"></i>
                                                </a>
                                            </div>
                                            
                                            @php
                                                $isTerminated = \App\Models\Termination::where('employee_id', $employee->id)->exists();
                                                $isResigned = \App\Models\Resignation::where('employee_id', $employee->id)->exists();
                                            @endphp
                                            
                                            @if(!$isTerminated && !$isResigned)
                                                @if(Gate::check('payroll.salary.increment.all'))
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#" data-size="lg" data-url="{{ route('salary-increment.form', $employee->id) }}" data-ajax-popup="true"
                                                            class="mx-3 btn btn-sm align-items-center">
                                                            <i class="ti ti-arrow-up text-white"></i>
                                                        </a>
                                                        
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            @if($employee->salaryIncrements && $employee->salaryIncrements->count() > 0 && Gate::check('payroll.salary.download_increment.all'))
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="javascript:void(0)" 
                                                        onclick="downloadFileBackground('{{ route('salary-increment.pdf', $employee->salaryIncrements->first()->id) }}')"
                                                        class="mx-3 btn btn-sm align-items-center">
                                                        <i class="ti ti-download text-white"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </span>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
    /**
     * Downloads a file in the background using a hidden iframe.
     * This avoids opening new tabs/windows which can cause crashes in APKs/PWAs.
     */
    function downloadFileBackground(url) {
        // Show a small loader or toast if needed
        if (typeof show_toastr === 'function') {
            show_toastr('Info', '{{ __("Preparing your download...") }}', 'info');
        }

        // Create a hidden iframe that is still rendered by the browser
        // This is necessary for html2canvas (used in PDF generation) to work properly
        var iframe = document.createElement('iframe');
        iframe.style.position = 'absolute';
        iframe.style.width = '1200px';
        iframe.style.height = '1500px';
        iframe.style.left = '-9999px';
        iframe.style.top = '-9999px';
        iframe.style.border = 'none';
        iframe.src = url;
        
        // Add to body
        document.body.appendChild(iframe);
        
        // Remove from body after a reasonable time
        setTimeout(function() {
            if (document.body.contains(iframe)) {
                document.body.removeChild(iframe);
            }
        }, 15000); // 15 seconds is usually enough for PDF generation and download trigger
    }
</script>
@endpush