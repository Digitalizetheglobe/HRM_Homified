@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Exit Formalities') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Exit Formalities') }}</li>
@endsection


@section('action-button')
    @if(Gate::check('exit.termination.create.all') || Gate::check('exit.termination.create.own'))
        <a href="#" data-url="{{ route('termination.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create New Relieve') }}" data-size="lg"
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
                                    <th>{{ __('Relieve Type') }}</th>
                                    <th>{{ __('Notice Date') }}</th>
                                    <th>{{ __('Relieve Date') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    @if (Gate::check('exit.termination.edit.all') || Gate::check('exit.termination.delete.all') || Gate::check('exit.termination.edit.own') || Gate::check('exit.termination.delete.own'))
                                        <th width="200px">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>


                                @foreach ($terminations as $termination)
                                    <tr>
                                        @role('company')
                                            <td>{{ !empty($termination->employee_id) ? $termination->employee->full_name : '' }}
                                            </td>
                                        @endrole

                                        <td>{{ !empty($termination->termination_type) ? $termination->terminationType->name : '' }}
                                        </td>
                                        <td>{{ \Auth::user()->dateFormat($termination->notice_date) }}</td>
                                        <td>{{ \Auth::user()->dateFormat($termination->termination_date) }}</td>
                                        <td><a href="#" class="action-item"
                                                data-url="{{ route('termination.description', $termination->id) }}"
                                                data-ajax-popup="true" data-title="{{ __('Desciption') }}"><i
                                                    class="icon_desc fa fa-comment"></i></a>
                                        </td>
                                        <td class="Action">
                                            @if (Gate::check('exit.termination.edit.all') || Gate::check('exit.termination.delete.all') || Gate::check('exit.termination.edit.own') || Gate::check('exit.termination.delete.own'))
                                                <span>
                                                    @if(Gate::check('exit.termination.edit.all') || (Gate::check('exit.termination.edit.own') && $termination->employee_id == (\Auth::user()->employee ? \Auth::user()->employee->id : 0)))
                                                        <div class="action-btn bg-info ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                                data-size="lg"
                                                                data-url="{{ URL::to('termination/' . $termination->id . '/edit') }}"
                                                                data-ajax-popup="true" data-size="md" data-title="{{ __('Edit Termination') }}">
                                                                <i class="ti ti-pencil text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if(Gate::check('exit.termination.delete.all') || (Gate::check('exit.termination.delete.own') && $termination->employee_id == (\Auth::user()->employee ? \Auth::user()->employee->id : 0)))
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['termination.destroy', $termination->id],
                                                                'id' => 'delete-form-' . $termination->id,
                                                            ]) !!}
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm  align-items-center bs-pass-para" aria-label="Delete"><i
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
@push('scripts')
    <style>
        /* Increase all termination table column widths */
        #pc-dt-simple th:nth-child(1) {
            min-width: 220px; /* Employee Name - increased */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 180px; /* Relieve Type - increased */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 160px; /* Notice Date - increased */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 160px; /* Relieve Date - increased */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 200px; /* Description - increased */
        }
        
        #pc-dt-simple th:nth-child(6) {
            min-width: 220px; /* Action - increased */
        }
    </style>
@endpush
@endsection
