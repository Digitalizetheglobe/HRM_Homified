@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Resignation') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Resignation') }}</li>
@endsection

@section('action-button')
   

    @if(Gate::check('exit.resignation.create.all') || Gate::check('exit.resignation.create.own'))
        <a href="#" data-url="{{ route('resignation.create') }}{{ request()->has('own') ? '?own=1' : '' }}" data-ajax-popup="true" data-size="lg"
            data-title="{{ __('Create New Resignation') }}" data-size="lg"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    @endif
@endsection

@section('content')
<div class="row">
    
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                {{-- <h5> </h5> --}}
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                @role('company')
                                    <th>{{ __('Employee Name') }}</th>
                                @endrole
                                <th>{{ __('Resignation Date') }}</th>
                                <th>{{ __('Last Working Day') }}</th>
                                <th class="text-center">{{ __('Reason') }}</th>
                                <th>{{ __('Status') }}</th> 
                                @if (Gate::check('exit.resignation.edit.all') || Gate::check('exit.resignation.delete.all') || Gate::check('exit.resignation.edit.own') || Gate::check('exit.resignation.delete.own') || Gate::check('exit.resignation.show.all') || Gate::check('exit.resignation.show.own'))
                                    <th width="200px">{{ __('Action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                        

                            @foreach ($resignations as $resignation)
                                <tr>
                                    @role('company')
                                        <td>{{ !empty($resignation->employee_id) ? $resignation->employee->full_name : '' }}
                                        </td>
                                    @endrole
                                    <td>{{ \Auth::user()->dateFormat($resignation->notice_date) }}</td>
                                    <td>{{ \Auth::user()->dateFormat($resignation->resignation_date) }}</td>
                                    <td class="text-center">
                                        <a href="#" data-url="{{ route('resignation.description', $resignation->id) }}"
                                           data-ajax-popup="true" data-title="{{ __('Resignation Reason') }}"
                                           class="btn btn-sm btn-icon btn-light-primary">
                                            <i class="ti ti-message-2"></i>
                                        </a>
                                    </td>
                                    <td>
                                        @if($resignation->status == 'pending')
                                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                                        @else
                                            <span class="badge bg-success">{{ __('Approved') }}</span>
                                        @endif
                                    </td>
                                    <td class="Action">
                                        @if (Gate::check('exit.resignation.edit.all') || Gate::check('exit.resignation.delete.all') || Gate::check('exit.resignation.edit.own') || Gate::check('exit.resignation.delete.own') || Gate::check('exit.resignation.show.all') || Gate::check('exit.resignation.show.own'))
                                            <span>
                                                @if(Gate::check('exit.resignation.show.all') || (Gate::check('exit.resignation.show.own') && $resignation->employee_id == (\Auth::user()->employee ? \Auth::user()->employee->id : 0)))
                                                    @if($resignation->status == 'pending')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="{{ route('resignation.review', $resignation->id) }}" 
                                                        class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{ __('Review') }}">
                                                            <i class="ti ti-eye text-white"></i>
                                                        </a>
                                                    </div>
                                                    @endif
                                                @endif
                                                @if(Gate::check('exit.resignation.edit.all') || (Gate::check('exit.resignation.edit.own') && $resignation->employee_id == (\Auth::user()->employee ? \Auth::user()->employee->id : 0)))
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center" data-size="lg"
                                                            data-url="{{ URL::to('resignation/' . $resignation->id . '/edit') }}"
                                                            data-ajax-popup="true" data-size="md" data-title="{{ __('Edit Resignation') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @if(Gate::check('exit.resignation.delete.all') || (Gate::check('exit.resignation.delete.own') && $resignation->employee_id == (\Auth::user()->employee ? \Auth::user()->employee->id : 0)))
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['resignation.destroy', $resignation->id], 'id' => 'delete-form-' . $resignation->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                            aria-label="Delete"><i
                                                                class="ti ti-trash text-white text-white"></i></a>
                                                        </form>
                                                    </div>
                                                @endif
                                            </span>
                                        @endif
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
</div>
@push('scripts')
    <style>
        /* Increase all resignation table column widths */
        #pc-dt-simple th:nth-child(1) {
            min-width: 220px; /* Employee Name - increased */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 180px; /* Resignation Date - increased */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 160px; /* Last Working Day - increased */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 200px; /* Reason - increased */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 120px; /* Status - increased */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 220px; /* Action - increased */
        }
    </style>
@endpush
@endsection
