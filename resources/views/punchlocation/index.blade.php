@extends('layouts.admin')

@section('page-title')
    {{ __('Punch Locations') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Punch Locations') }}</li>
@endsection

@section('action-button')
    @can('Manage Punch Location')
        <a href="#" data-url="{{ route('punchlocation.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create Punch Location') }}" data-size="md" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        {{ __('Add a site with latitude and longitude. An employee can punch in only within that site\'s radius. Assign one or more sites on the employee form.') }}
                    </p>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Location') }}</th>
                                    <th>{{ __('Latitude') }}</th>
                                    <th>{{ __('Longitude') }}</th>
                                    <th>{{ __('Radius (meters)') }}</th>
                                    <th>{{ __('Employees') }}</th>
                                    <th width="200px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($locations as $location)
                                    <tr>
                                        <td>{{ $location->name }}</td>
                                        <td>{{ $location->latitude }}</td>
                                        <td>{{ $location->longitude }}</td>
                                        <td>{{ $location->radius_meters }}</td>
                                        <td>{{ $location->employees_count }}</td>
                                        <td class="Action">
                                            <span>
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                        data-url="{{ route('punchlocation.edit', $location->id) }}"
                                                        data-ajax-popup="true" data-size="md"
                                                        data-title="{{ __('Edit Punch Location') }}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['punchlocation.destroy', $location->id], 'id' => 'delete-form-' . $location->id]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" aria-label="Delete">
                                                        <i class="ti ti-trash text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            </span>
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
@endsection
