{{-- resources/views/leave/compoff.blade.php --}}
@extends('layouts.admin')

@section('page-title')
    {{ __('Comp-Off Leaves') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Comp-Off Leaves') }}</li>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('errors') && is_array(session('errors')) && count(session('errors')) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ __('Some errors occurred:') }}</strong>
            <ul class="mb-0">
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif


    @php
        $canNormalize = \Auth::user()->type == 'company' || \Auth::user()->can('leave.compoff.view.all');
    @endphp

    @if ($canNormalize)
        <div class="row mb-3">
            <div class="col-xl-12">
                <div class="card border-warning">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Comp-Off data cleanup (year)') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            {{ __('Recalculates earned comp off for the selected year using each employee’s current week off and attendance (Present / Half Day / Single Punch). Removes comp off rows that no longer qualify, and inserts missing valid rows. Run a preview first; applying changes requires confirmation.') }}
                        </p>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Year') }}</label>
                                <select id="comp_off_norm_year" class="form-control">
                                    @for ($y = (int) date('Y'); $y >= 2020; $y--)
                                        <option value="{{ $y }}" @selected($y === (int) date('Y'))>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="comp_off_norm_dry" checked>
                                    <label class="form-check-label" for="comp_off_norm_dry">{{ __('Dry run (no database changes)') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="comp_off_norm_confirm">
                                    <label class="form-check-label" for="comp_off_norm_confirm">{{ __('I confirm apply (required when not dry run)') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button type="button" class="btn btn-primary" id="comp_off_norm_run">
                                    <i class="ti ti-adjustments-horizontal"></i> {{ __('Run normalization') }}
                                </button>
                            </div>
                        </div>
                        <pre id="comp_off_norm_report" class="mt-3 p-3 bg-light border rounded small" style="max-height: 420px; overflow: auto; display: none;"></pre>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5>{{ __('Comp-Off Leaves Balance') }}</h5>
                        </div>
                        <!-- <div class="col-md-6 text-end">
                            @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'hr' || \Auth::user()->type == 'Director')
                                <form method="POST" action="{{ route('comp-off.process-year') }}" class="d-inline">
                                    @csrf
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <select name="year" class="form-control form-control-sm" style="width: 70px;">
                                            @for($y = date('Y'); $y >= 2020; $y--)
                                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="ti ti-refresh"></i> {{ __('Process Comp-Offs for Year') }}
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div> -->
                    </div>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th class="text-start">{{ __('Employee ID') }}</th>
                                    <th class="text-start">{{ __('Employee Name') }}</th>
                                    <th class="text-start">{{ __('Total Earned') }}</th>
                                    <th class="text-start">{{ __('Total Used') }}</th>
                                    <th class="text-start">{{ __('Remaining Balance') }}</th>
                                    <th class="text-start">{{ __('Last Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Calculate comp-off balance: Total Earned - Total Approved Leaves
                                    $compOffTypeId = \App\Models\LeaveType::where('title', 'Comp-Off')->value('id') ?? 0;
                                    
                                    $query = \DB::table('employees')
                                        ->whereNotIn('employees.id', [4, 13, 22, 23])
                                        ->whereNotIn('employees.id', function($q) {
                                            $q->select('employee_id')->from('terminations');
                                        });

                                    if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.compoff.view.all'))) {
                                        $query->where('employees.user_id', \Auth::user()->id);
                                    }

                                    $compOffCounts = $query->select('employees.id', 'employees.name', 'employees.last_name')
                                        ->addSelect(['earned_count' => \App\Models\CompOffLeave::selectRaw('count(*)')
                                            ->whereColumn('employees_id', 'employees.id')
                                        ])
                                        ->addSelect(['used_count' => \App\Models\Leave::selectRaw('COALESCE(sum(total_leave_days), 0)')
                                            ->whereColumn('employee_id', 'employees.id')
                                            ->where('leave_type_id', $compOffTypeId)
                                            ->where('status', 'Approved')
                                        ])
                                        ->addSelect(['last_updated' => \App\Models\CompOffLeave::select('updated_at')
                                            ->whereColumn('employees_id', 'employees.id')
                                            ->latest()
                                            ->limit(1)
                                        ])
                                        ->get()
                                        ->filter(function($employee) {
                                            return ($employee->earned_count - $employee->used_count) > 0;
                                        });
                                @endphp
                                
                                @foreach($compOffCounts as $compOff)
                                    <tr>
                                        <td class="text-start">{{ $compOff->id }}</td>
                                        <td class="text-start">{{ $compOff->name }} {{ $compOff->last_name }}</td>
                                        <td class="text-start">
                                            <span class="badge bg-success p-2 px-3 rounded">
                                                {{ $compOff->earned_count }}
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-secondary p-2 px-3 rounded">
                                                {{ $compOff->used_count }}
                                            </span>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge bg-primary p-2 px-3 rounded">
                                                {{ $compOff->earned_count - $compOff->used_count }}
                                            </span>
                                        </td>
                                        <td class="text-start">{{ !empty($compOff->last_updated) ? \Auth::user()->dateFormat($compOff->last_updated) : 'N/A' }}</td>
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

@push('scripts')
    @php
        $canNormalizeScripts = \Auth::user()->can('Manage Leave')
            && in_array(strtolower((string) \Auth::user()->type), ['company', 'hr', 'director'], true);
    @endphp
    @if ($canNormalizeScripts)
        <script>
            document.getElementById('comp_off_norm_run')?.addEventListener('click', function () {
                const year = document.getElementById('comp_off_norm_year').value;
                const dryRun = document.getElementById('comp_off_norm_dry').checked;
                const confirm = document.getElementById('comp_off_norm_confirm').checked;
                const pre = document.getElementById('comp_off_norm_report');
                pre.style.display = 'block';
                pre.textContent = '{{ __("Running…") }}';

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('{{ route('comp-off.normalize-year') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        year: parseInt(year, 10),
                        dry_run: dryRun,
                        confirm: confirm
                    })
                }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, status: r.status, data: data }; }); })
                .then(function (res) {
                    pre.textContent = JSON.stringify(res.data, null, 2);
                    if (!res.data.ok) {
                        pre.classList.add('border-danger');
                    } else {
                        pre.classList.remove('border-danger');
                    }
                }).catch(function (e) {
                    pre.textContent = String(e);
                    pre.classList.add('border-danger');
                });
            });
        </script>
    @endif
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
        /* DataTables styles */
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        
        /* Ensure proper column width alignment */
        #pc-dt-simple th {
            min-width: 120px;
        }
        
        #pc-dt-simple th:nth-child(1) {
            min-width: 120px; /* Employee ID */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 250px; /* Employee Name */
        }
        
        #pc-dt-simple th:nth-child(3),
        #pc-dt-simple th:nth-child(4),
        #pc-dt-simple th:nth-child(5) {
            min-width: 150px; /* Earned, Used, Remaining */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 160px; /* Last Updated */
        }
    </style>
@endpush