@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Company Policy') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Company Policy') }}</li>
@endsection

@section('action-button')
    <div class="d-flex gap-2">
        @if(Gate::check('company_policy.create') || Gate::check('company_policy.manage.create.all'))
            <a href="#" data-url="{{ route('company-policy.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create New Company Policy') }}" data-size="lg"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endif
        @if(\Auth::user()->type == 'company' || Gate::check('company_policy.manage.acknowledgements.all'))
            <a href="{{ route('company-policy.acknowledgements') }}" 
                class="btn btn-sm btn-info">
                <i class="ti ti-check"></i> {{ __('Acknowledgements') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                {{-- <h5></h5> --}}
                <div class="table-responsive">
                    <table class="table" id="pc-dt-simple">
                        <thead>
                            <tr>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Attachment') }}</th>
                                @if (Gate::check('company_policy.edit') || Gate::check('company_policy.delete') || Gate::check('company_policy.manage.edit.all') || Gate::check('company_policy.manage.delete.all'))
                                    <th width="200px">{{ __('Action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companyPolicy as $policy)
                               
                             @php
                                $policyPath=\App\Models\Utility::get_file('uploads/companyPolicy');
                             @endphp
                                <tr>
                                    <td>{{ !empty($policy->branches) ? $policy->branches->name : '-' }}</td>
                                    <td>{{ $policy->title }}</td>
                                    <td>
                                        @if(!empty($policy->description))
                                            <button type="button"
                                                class="btn btn-sm view-desc-btn"
                                                data-description="{{ e($policy->description) }}"
                                                data-title="{{ e($policy->title) }}"
                                                title="{{ __('View Description') }}"
                                                style="border:none; background:transparent; padding:4px 8px;">
                                                <i class="ti ti-message-circle" style="font-size:1.3rem; color:#ea3538;"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($policy->attachment))
                                        <div class="action-btn bg-primary ms-2">

                                            <a  class="mx-3 btn btn-sm align-items-center" href="{{ $policyPath . '/' . $policy->attachment }}" download="">
                                                <i class="ti ti-download text-white"></i>
                                            </a>
                                        </div>
                                            <div class="action-btn bg-secondary ms-2">
                                                <a class="mx-3 btn btn-sm align-items-center" href="{{ $policyPath . '/' . $policy->attachment }}" target="_blank"  >
                                                    <i class="ti ti-crosshair text-white"></i>
                                                </a>
                                            </div>
                                        @else
                                            <p>-</p>
                                        @endif
                                    </td>
                                    @if (Gate::check('company_policy.edit') || Gate::check('company_policy.delete') || Gate::check('company_policy.manage.edit.all') || Gate::check('company_policy.manage.delete.all'))
                                        <td class="Action">
                                            <span>
                                                @if(Gate::check('company_policy.edit') || Gate::check('company_policy.manage.edit.all'))
                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="#" data-size="lg" class="mx-3 btn btn-sm  align-items-center"
                                                            data-url="{{ route('company-policy.edit', $policy->id) }}"
                                                            data-ajax-popup="true" data-size="md" data-title="{{ __('Edit Company Policy') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @if(Gate::check('company_policy.delete') || Gate::check('company_policy.manage.delete.all'))
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['company-policy.destroy', $policy->id], 'id' => 'delete-form-' . $policy->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                            aria-label="Delete"><i
                                                                class="ti ti-trash text-white text-white"></i></a>
                                                        </form>
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

{{-- Description Popup Modal --}}
<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px; box-shadow: 0 8px 32px rgba(234,53,56,0.18);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ea3538 0%, #c0292b 100%); border-radius:12px 12px 0 0; border:none;">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-message-circle text-white" style="font-size:1.4rem;"></i>
                    <h5 class="modal-title text-white mb-0" id="descriptionModalLabel">{{ __('Description') }}</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="mb-2">
                    <span class="badge" style="background:linear-gradient(135deg,#ea3538,#c0292b); font-size:0.85rem; padding:6px 14px; border-radius:20px;" id="desc-modal-policy-title"></span>
                </div>
                <div class="mt-3 p-3" style="background:#fff5f5; border-radius:8px; border-left:4px solid #ea3538; min-height:60px;">
                    <p class="mb-0" id="desc-modal-body" style="line-height:1.7; color:#374151; white-space:pre-wrap;"></p>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
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
            min-width: 150px; /* Branch */
        }
        
        #pc-dt-simple th:nth-child(2) {
            min-width: 200px; /* Title */
        }
        
        #pc-dt-simple th:nth-child(3) {
            min-width: 300px; /* Description */
        }
        
        #pc-dt-simple th:nth-child(4) {
            min-width: 180px; /* Attachment */
        }
        
        #pc-dt-simple th:nth-child(5) {
            min-width: 220px; /* Action (conditional) */
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.view-desc-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var desc = this.getAttribute('data-description');
                    var title = this.getAttribute('data-title');
                    document.getElementById('desc-modal-body').textContent = desc;
                    document.getElementById('desc-modal-policy-title').innerHTML = title;
                    var modal = new bootstrap.Modal(document.getElementById('descriptionModal'));
                    modal.show();
                });
            });
        });
    </script>

@endpush

