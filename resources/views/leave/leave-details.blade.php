@extends('layouts.admin')

@section('page-title')
    {{ __('Leave Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('leave.index') }}">{{ __('Leave') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Leave Details') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Employee Leave Details') }}</h5>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th class="text-start">{{ __('Employee Name') }}</th>
                                    <th class="text-start">{{ __('Employee ID') }}</th>
                                    <th class="text-start">{{ __('Department') }}</th>
                                    <th class="text-start">{{ __('Comp Off Leaves') }}</th>
                                    <th class="text-start">{{ __('Earned Leaves') }}</th>
                                    <th class="text-start">{{ __('Sick Leaves') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employeeLeaveDetails as $employeeDetail)
                                    <tr>
                                        <td class="text-start">
                                            {{ $employeeDetail['employee']->name }} 
                                            @if(!empty($employeeDetail['employee']->middle_name))
                                                {{ $employeeDetail['employee']->middle_name }}
                                            @endif
                                            @if(!empty($employeeDetail['employee']->last_name))
                                                {{ $employeeDetail['employee']->last_name }}
                                            @endif
                                        </td>
                                        <td class="text-start">{{ $employeeDetail['employee']->employee_id ?? 'N/A' }}</td>
                                        <td class="text-start">
                                            @if(!empty($employeeDetail['employee']->department_id))
                                                @php
                                                    $department = \App\Models\Department::find($employeeDetail['employee']->department_id);
                                                @endphp
                                                {{ $department ? $department->name : 'N/A' }}
                                            @else
                                                {{ __('N/A') }}
                                            @endif
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-primary">{{ $employeeDetail['compOffBalance'] }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-success">{{ number_format($employeeDetail['earnedLeaveBalance'], 2) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-info">{{ number_format($employeeDetail['sickLeaveBalance'], 2) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mt-4">
        <div class="col-xl-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-end">
                    <h6 class="text-white mb-2 text-xl">{{ __('Total Comp Off Leaves') }}</h6>
                    <h3 class="mb-0">{{ collect($employeeLeaveDetails)->sum('compOffBalance') }}</h3>
                    <small class="text-white">{{ __('Across All Employees') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card bg-success text-white">
                <div class="card-body text-end">
                    <h6 class="text-white mb-2 text-xl">{{ __('Total Earned Leaves') }}</h6>
                    <h3 class="mb-0">{{ number_format(collect($employeeLeaveDetails)->sum('earnedLeaveBalance'), 2) }}</h3>
                    <small class="text-white">{{ __('Across All Employees') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card bg-info text-white">
                <div class="card-body text-end">
                    <h6 class="text-white mb-2 text-xl">{{ __('Total Sick Leaves') }}</h6>
                    <h3 class="mb-0">{{ number_format(collect($employeeLeaveDetails)->sum('sickLeaveBalance'), 2) }}</h3>
                    <small class="text-white">{{ __('Across All Employees') }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-end">
                    <h6 class="text-white mb-2 text-xl">{{ __('Total Employees') }}</h6>
                    <h3 class="mb-0">{{ count($employeeLeaveDetails) }}</h3>
                    <small class="text-white">{{ __('In Organization') }}</small>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <style>
        .table th {
            white-space: nowrap;
            text-align: left !important;
            vertical-align: middle !important;
            padding-right: 25px !important;
            position: relative;
        }
        
        .table td {
            vertical-align: middle !important;
        }
        
        /* Fix DataTables sorting icons alignment */
        .dataTables_wrapper .dataTables_scrollHead .table th {
            position: relative;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after,
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after,
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 8px !important;
            margin-top: 0 !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_asc:after {
            content: "·" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting_desc:after {
            content: "·" !important;
        }
        
        .dataTables_wrapper .dataTables_scrollHead .table th.sorting:after {
            content: "·" !important;
            opacity: 0.3;
        }
        
        /* Ensure proper column width alignment */
        #pc-dt-simple th {
            min-width: 120px;
        }
        
        #pc-dt-simple th:nth-child(1) {
            min-width: 250px; /* Employee Name */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 140px; /* Employee ID */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 180px; /* Department */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 160px; /* Comp Off Leaves */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 160px; /* Earned Leaves */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 160px; /* Sick Leaves */
        }
    </style>
    
    <script>
        $(document).ready(function() {
            $('#pc-dt-simple').DataTable({
                "pageLength": 25,
                "order": [[ 0, "asc" ]],
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "paginate": {
                        "previous": "<i class='ti ti-chevron-left'></i>",
                        "next": "<i class='ti ti-chevron-right'></i>"
                    },
                    "emptyTable": "No employee leave details found"
                }
            });
        });
    </script>
@endpush
