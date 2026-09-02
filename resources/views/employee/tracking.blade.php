@extends('layouts.admin')

@section('page-title')
    {{ __('Employee Live Location & Route History') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Employee Location Tracking') }}</li>
@endsection

@push('css-page')
    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        /* ── Select2 Custom Styling ────────────────── */
        .select2-container--default .select2-selection--single {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            height: 42px !important;
            padding: 4px 6px !important;
            background-color: #f9fafb !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important;
            font-size: 0.875rem !important;
            line-height: 28px !important;
            padding-left: 4px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--color-customColor, #c9a227) !important;
            box-shadow: 0 0 0 3px rgba(201,162,39,0.15) !important;
            background-color: #ffffff !important;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08) !important;
            overflow: hidden !important;
            z-index: 9999 !important;
        }
        .select2-search__field {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            font-size: 0.875rem !important;
        }
        .select2-search__field:focus {
            border-color: var(--color-customColor, #c9a227) !important;
            outline: none !important;
        }
        .select2-results__option--highlighted[aria-selected] {
            background-color: var(--color-customColor, #c9a227) !important;
        }
        /* ── Modern Card & Layout Base ──────────────── */
        .filter-card {
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04) !important;
            background: #ffffff;
            transition: all 0.2s ease-in-out;
        }
        .filter-card .card-header {
            border-left: 4px solid var(--color-customColor, #c9a227);
            background: linear-gradient(to right, rgba(201,162,39,0.08) 0%, #fff 60%);
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .filter-card .card-header h5 {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1e293b;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-card .card-header h5 .ti {
            color: var(--color-customColor, #c9a227);
            font-size: 1.2rem;
        }

        /* ── Metric Stat Cards ─────────────────────── */
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-icon.status-active { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .stat-icon.status-inactive { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .stat-icon.location { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .stat-icon.points { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .stat-icon.distance { background: rgba(236, 72, 153, 0.12); color: #ec4899; }

        .stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .stat-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .stat-sub {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* ── Map Container ─────────────────────────── */
        #map {
            height: 620px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            z-index: 1;
        }

        /* ── Visited Places Timeline Sidebar ────────── */
        .timeline-container {
            max-height: 570px;
            overflow-y: auto;
            padding-right: 6px;
        }
        .timeline-container::-webkit-scrollbar {
            width: 5px;
        }
        .timeline-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }

        .timeline-item {
            position: relative;
            padding-left: 32px;
            padding-bottom: 18px;
            border-left: 2px solid #e2e8f0;
            margin-left: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .timeline-item:last-child {
            border-left: 2px solid transparent;
            padding-bottom: 0;
        }
        .timeline-badge {
            position: absolute;
            left: -11px;
            top: 2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #64748b;
            box-shadow: 0 0 0 3px #ffffff;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
            font-weight: bold;
        }
        .timeline-item.start .timeline-badge {
            border-color: #10b981;
            background: #10b981;
        }
        .timeline-item.current .timeline-badge {
            border-color: #3b82f6;
            background: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
            animation: pulse-timeline 1.8s infinite;
        }
        .timeline-item.end .timeline-badge {
            border-color: #ef4444;
            background: #ef4444;
        }

        @keyframes pulse-timeline {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.6); }
            70% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        .timeline-card {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 10px 14px;
            transition: all 0.2s ease;
        }
        .timeline-item:hover .timeline-card {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateX(3px);
        }
        .timeline-item.active-selected .timeline-card {
            background: #eff6ff;
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.12);
        }

        .timeline-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .timeline-time {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
        }
        .timeline-coords {
            font-size: 0.72rem;
            color: #94a3b8;
            margin-top: 2px;
            font-family: monospace;
        }

        /* ── Map Markers Styling ──────────────────── */
        .leaflet-div-icon-start {
            background: #10b981;
            border: 2.5px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.5);
        }
        .leaflet-div-icon-waypoint {
            background: #6366f1;
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .leaflet-div-icon-end {
            background: #ef4444;
            border: 2.5px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.5);
        }
        .leaflet-div-icon-stay {
            background: #1d4ed8;
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(29, 78, 216, 0.45);
        }
        .leaflet-div-icon-current {
            background: #3b82f6;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.4), 0 3px 8px rgba(0,0,0,0.4);
            animation: pulse-marker 1.8s infinite;
        }
        .timeline-item.stay .timeline-badge {
            border-color: #1d4ed8;
            background: #1d4ed8;
        }

        @keyframes pulse-marker {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7), 0 3px 8px rgba(0,0,0,0.4); }
            70% { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0), 0 3px 8px rgba(0,0,0,0.4); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0), 0 3px 8px rgba(0,0,0,0.4); }
        }

        /* Live Indicator Badge */
        .live-pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-badge 1.5s infinite;
            margin-right: 5px;
        }
        @keyframes pulse-badge {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
@endpush

@section('content')
    <!-- Filter & Control Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card mb-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5><i class="ti ti-adjustments-horizontal"></i>{{ __('Live Employee Location & Route Tracking Options') }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span id="live_status_badge" class="badge bg-light-success text-success border border-success py-2 px-3 d-none align-items-center">
                            <span class="live-pulse"></span> {{ __('LIVE TRACKING ACTIVE') }}
                        </span>
                        <button type="button" id="simulate_modal_btn" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#simulatePingModal">
                            <i class="ti ti-location"></i> {{ __('Simulate Ping (Test)') }}
                        </button>
                    </div>
                </div>
                <div class="card-body py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-xl-4 col-md-5">
                            <label for="employee_select" class="form-label mb-1">{{ __('Select Employee') }}</label>
                            <select id="employee_select" class="form-control select">
                                <option value="" disabled selected>{{ __('Select an Employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-name="{{ $employee->name }}">
                                        {{ $employee->name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-3 col-md-4">
                            <label for="date_select" class="form-label mb-1">{{ __('Select Tracking Date') }}</label>
                            <input type="date" id="date_select" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-xl-5 col-md-3 d-flex align-items-end justify-content-md-end gap-2 mt-4 mt-md-0">
                            <button type="button" id="refresh_btn" class="btn btn-primary d-flex align-items-center gap-2">
                                <i class="ti ti-refresh"></i>{{ __('Refresh Data') }}
                            </button>
                            <a id="google_maps_btn" href="#" target="_blank" class="btn btn-outline-success d-none align-items-center gap-2">
                                <i class="ti ti-map-pin"></i>{{ __('Google Maps') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="stat-card">
                <div class="stat-icon status-active" id="stat_status_icon">
                    <i class="ti ti-circle-check"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('Duty Status') }}</div>
                    <div class="stat-value" id="stat_status">--</div>
                    <div class="stat-sub" id="stat_status_sub">Select an employee</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="stat-card">
                <div class="stat-icon location">
                    <i class="ti ti-current-location"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('Last Active Location') }}</div>
                    <div class="stat-value" id="stat_last_seen">--</div>
                    <div class="stat-sub" id="stat_last_seen_sub">No location ping</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
            <div class="stat-card">
                <div class="stat-icon points">
                    <i class="ti ti-map-pins"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('Stops Visited') }}</div>
                    <div class="stat-value" id="stat_points">0</div>
                    <div class="stat-sub" id="stat_points_sub">{{ __('GPS noise filtered') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon distance">
                    <i class="ti ti-route"></i>
                </div>
                <div>
                    <div class="stat-label">{{ __('Distance Traveled') }}</div>
                    <div class="stat-value" id="stat_distance">0.00 km</div>
                    <div class="stat-sub" id="stat_distance_sub">{{ __('Between real stops') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic Warning Alert Banner -->
    <div id="diagnostic_alert_banner" class="alert d-none align-items-center mb-4 p-3 shadow-sm" role="alert" style="border-radius: 12px; border-left: 5px solid #ef4444 !important;">
        <i id="diagnostic_alert_icon" class="ti ti-alert-triangle-filled fs-2 me-3 flex-shrink-0"></i>
        <div>
            <strong id="diagnostic_alert_title" class="d-block mb-1" style="font-size: 0.95rem;">Tracking Signal Status</strong>
            <div id="diagnostic_alert_text" class="small">Diagnostic information will appear here.</div>
        </div>
    </div>

    <!-- Map & Visited Places Timeline Section -->
    <div class="row">
        <!-- Map Column -->
        <div class="col-xl-8 col-lg-7 col-md-12 mb-4 mb-xl-0">
            <div class="card filter-card h-100 mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="ti ti-map-pin"></i>{{ __('Live Route & Movement Map') }}</h5>
                    <span class="badge bg-light text-dark py-2 px-3 border" id="active_employee_label">No employee selected</span>
                </div>
                <div class="card-body p-2">
                    <div id="map"></div>
                </div>
            </div>
        </div>

        <!-- Visited Places Timeline Column -->
        <div class="col-xl-4 col-lg-5 col-md-12">
            <div class="card filter-card h-100 mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="ti ti-history"></i>{{ __('Visited Places History') }}</h5>
                    <span class="badge bg-primary text-white" id="timeline_count_badge">0 stops</span>
                </div>
                <div class="card-body p-3">
                    <div class="timeline-container" id="timeline_list">
                        <div class="text-center py-5 text-muted">
                            <i class="ti ti-map-pin-off display-6 mb-2 d-block opacity-50"></i>
                            <p class="mb-0">{{ __('Select an employee and date to view visited places history.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simulate Ping Modal (For Admin Testing) -->
    <div class="modal fade" id="simulatePingModal" tabindex="-1" aria-labelledby="simulatePingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="simulatePingModalLabel"><i class="ti ti-location text-primary me-2"></i>{{ __('Simulate Test Location Ping') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">{{ __('Use this tool to test live route visualization by manually logging a test GPS coordinate for the selected employee.') }}</p>
                    <form id="simulate_ping_form">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Employee') }}</label>
                            <select id="sim_employee_id" class="form-control" required>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->employee_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">{{ __('Latitude') }}</label>
                                <input type="number" step="any" id="sim_latitude" class="form-control" value="18.5204" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">{{ __('Longitude') }}</label>
                                <input type="number" step="any" id="sim_longitude" class="form-control" value="73.8567" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">{{ __('Quick Nudge Step (Move Location)') }}</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-xs btn-outline-secondary nudge-btn" data-nlat="0.003" data-nlng="0.0">+0.003 N</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary nudge-btn" data-nlat="-0.003" data-nlng="0.0">-0.003 S</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary nudge-btn" data-nlat="0.0" data-nlng="0.003">+0.003 E</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary nudge-btn" data-nlat="0.0" data-nlng="-0.003">-0.003 W</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" id="send_sim_ping_btn" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('Send Location Ping') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <!-- Leaflet.js JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function () {
            // Initialize Select2 for searchable employee dropdown
            if ($.fn.select2) {
                $('#employee_select').select2({
                    placeholder: "{{ __('Search or select an employee...') }}",
                    width: '100%',
                    allowClear: false
                }).on('change', function() {
                    $('#sim_employee_id').val($(this).val());
                    loadTrackingData();
                });

                $('#sim_employee_id').select2({
                    dropdownParent: $('#simulatePingModal'),
                    width: '100%'
                });
            }

            // Default center coordinate (Pune, India fallback)
            const defaultCenter = [18.5204, 73.8567];
            
            // Initialize Leaflet Map
            const map = L.map('map').setView(defaultCenter, 13);

            // Add Google Maps Tile Layer
            L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 22,
                attribution: '© Google Maps'
            }).addTo(map);

            // Layer storage
            let routeLine = null;
            let routeLineBG = null;
            let markersGroup = L.featureGroup().addTo(map);
            let stayCirclesGroup = L.featureGroup().addTo(map);
            let leafletMarkersMap = {}; // id -> marker instance

            // Date change handler
            $('#date_select').change(function() {
                loadTrackingData();
            });

            $('#refresh_btn').click(function() {
                const btn = $(this);
                const icon = btn.find('i');
                icon.addClass('ti-spin');
                loadTrackingData(false, true, function() {
                    icon.removeClass('ti-spin');
                    if (typeof show_toastr === 'function') {
                        show_toastr('Success', 'Tracking data refreshed successfully.', 'success');
                    }
                });
            });

            // Quick Nudge step helper buttons in simulation modal
            $('.nudge-btn').click(function() {
                const nlat = parseFloat($(this).data('nlat'));
                const nlng = parseFloat($(this).data('nlng'));
                const curLat = parseFloat($('#sim_latitude').val()) || 18.5204;
                const curLng = parseFloat($('#sim_longitude').val()) || 73.8567;
                
                $('#sim_latitude').val((curLat + nlat).toFixed(6));
                $('#sim_longitude').val((curLng + nlng).toFixed(6));
            });

            // Simulate ping AJAX trigger
            $('#send_sim_ping_btn').click(function() {
                const empId = $('#sim_employee_id').val();
                const lat = $('#sim_latitude').val();
                const lng = $('#sim_longitude').val();

                if (!empId || !lat || !lng) {
                    show_toastr('Error', 'Please fill in employee ID and valid coordinates.', 'error');
                    return;
                }

                $.ajax({
                    url: "{{ route('employee.simulate-ping') }}",
                    type: "POST",
                    data: {
                        employee_id: empId,
                        latitude: lat,
                        longitude: lng,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.success) {
                            show_toastr('Success', res.message || 'Test ping sent!', 'success');
                            $('#simulatePingModal').modal('hide');
                            
                            // Select employee in dropdown if different
                            if ($('#employee_select').val() != empId) {
                                $('#employee_select').val(empId).trigger('change');
                            } else {
                                loadTrackingData();
                            }
                        } else {
                            show_toastr('Error', res.message || 'Failed to log test ping.', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error("Simulation error", xhr);
                        show_toastr('Error', 'Failed to log test location ping.', 'error');
                    }
                });
            });

            // Silent Auto-refresh interval (runs every 10 seconds)
            setInterval(function() {
                if ($('#employee_select').val()) {
                    loadTrackingData(true);
                }
            }, 10000);

            // Auto-load if employee selected on page ready
            if ($('#employee_select').val()) {
                loadTrackingData();
            }

            function loadTrackingData(silent = false, isManual = false, callback = null) {
                const employeeId = $('#employee_select').val();
                const date = $('#date_select').val();

                if (!employeeId) {
                    if (callback) callback();
                    return;
                }

                const employeeName = $('#employee_select option:selected').attr('data-name');
                $('#active_employee_label').text(`${employeeName} • ${date}`);

                $.ajax({
                    url: "{{ route('employee.tracking-data') }}",
                    type: "GET",
                    dataType: "json",
                    data: {
                        employee_id: employeeId,
                        date: date
                    },
                    complete: function() {
                        if (callback) callback();
                    },
                    success: function (response) {
                        if (typeof response !== 'object' || response === null || !response.success) {
                            show_toastr('Error', response?.message || 'Failed to retrieve tracking data.', 'error');
                            return;
                        }

                        // Clear existing lines & markers
                        if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
                        if (routeLineBG) { map.removeLayer(routeLineBG); routeLineBG = null; }
                        markersGroup.clearLayers();
                        stayCirclesGroup.clearLayers();
                        leafletMarkersMap = {};

                        let route = response.route || [];
                        const latest = response.current_location;
                        const isClockedIn = response.is_clocked_in;
                        const hasClockIn = response.has_clock_in;
                        const hasClockOut = response.has_clock_out;
                        const healthStatus = response.health_status;
                        const diagMessage = response.diagnostic_message;
                        const minsAgo = response.mins_since_last_ping;

                        // 1. Update Diagnostic Warning Banner & Status Metrics Cards
                        $('#diagnostic_alert_banner').addClass('d-none').removeClass('alert-danger alert-warning alert-info alert-success');

                        if (healthStatus === 'signal_lost') {
                            $('#diagnostic_alert_banner').removeClass('d-none').addClass('alert-danger d-flex');
                            $('#diagnostic_alert_title').text('🔴 TRACKING SIGNAL LOST (Location Updates Stopped)').attr('class', 'text-danger d-block mb-1');
                            $('#diagnostic_alert_text').text(diagMessage);
                            
                            $('#stat_status').html('<span class="text-danger"><i class="ti ti-alert-triangle-filled me-1"></i> Signal Lost</span>');
                            $('#stat_status_sub').text(`No update for ${Math.round(minsAgo || 0)} mins`);
                            $('#stat_status_icon').removeClass('status-active status-inactive').addClass('bg-light-danger text-danger');
                            $('#live_status_badge').addClass('d-none').removeClass('d-flex');
                        } else if (healthStatus === 'signal_delayed') {
                            $('#diagnostic_alert_banner').removeClass('d-none').addClass('alert-warning d-flex');
                            $('#diagnostic_alert_title').text('🟡 Tracking Signal Delayed').attr('class', 'text-warning d-block mb-1');
                            $('#diagnostic_alert_text').text(diagMessage);
                            
                            $('#stat_status').html('<span class="text-warning"><i class="ti ti-clock-warning me-1"></i> Signal Delayed</span>');
                            $('#stat_status_sub').text(`Weak GPS (${Math.round(minsAgo || 0)} mins ago)`);
                            $('#stat_status_icon').removeClass('status-active status-inactive').addClass('bg-light-warning text-warning');
                            $('#live_status_badge').addClass('d-none').removeClass('d-flex');
                        } else if (healthStatus === 'live_stationary') {
                            $('#stat_status').html('<span class="text-primary"><i class="ti ti-building-community me-1"></i> Live & Stationary</span>');
                            $('#stat_status_sub').text('Stationary at location (Confirmed Active)');
                            $('#stat_status_icon').removeClass('status-active status-inactive').addClass('bg-light-primary text-primary');
                            $('#live_status_badge').removeClass('d-none').addClass('d-flex');
                        } else if (healthStatus === 'live_moving') {
                            $('#stat_status').html('<span class="text-success"><i class="ti ti-navigation me-1"></i> Live & Moving</span>');
                            $('#stat_status_sub').text('Active on route');
                            $('#stat_status_icon').removeClass('status-inactive').addClass('status-active');
                            $('#live_status_badge').removeClass('d-none').addClass('d-flex');
                        } else if (hasClockOut) {
                            $('#stat_status').html('<span class="text-secondary"><i class="ti ti-clock-off me-1"></i> Off Duty</span>');
                            $('#stat_status_sub').text('Shift completed (Clocked Out)');
                            $('#stat_status_icon').removeClass('status-active').addClass('status-inactive');
                            $('#live_status_badge').addClass('d-none').removeClass('d-flex');
                        } else if (hasClockIn) {
                            $('#stat_status').html('<span class="text-info"><i class="ti ti-clock me-1"></i> Single Punch</span>');
                            $('#stat_status_sub').text('Clocked in earlier');
                            $('#stat_status_icon').removeClass('status-active').addClass('status-inactive');
                            $('#live_status_badge').addClass('d-none').removeClass('d-flex');
                        } else {
                            $('#stat_status').html('<span class="text-warning"><i class="ti ti-user-x me-1"></i> Not Clocked In</span>');
                            $('#stat_status_sub').text('No attendance punch today');
                            $('#stat_status_icon').removeClass('status-active').addClass('status-inactive');
                            $('#live_status_badge').addClass('d-none').removeClass('d-flex');
                        }

                        $('#stat_points').text(response.total_points || route.length);
                        const rawCount = response.raw_point_count || route.length;
                        if (rawCount > (response.total_points || route.length)) {
                            $('#stat_points_sub').text(`${rawCount} GPS pings → ${response.total_points || route.length} stops`);
                        } else {
                            $('#stat_points_sub').text('GPS noise filtered');
                        }
                        $('#stat_distance').text(`${response.total_distance_km || '0.00'} km`);
                        $('#stat_distance_sub').text('Between real stops');

                        if (latest) {
                            $('#stat_last_seen').text(latest.time || '--');
                            $('#stat_last_seen_sub').text(`Lat: ${latest.lat.toFixed(4)}, Lng: ${latest.lng.toFixed(4)}`);
                            const latestUrl = `https://www.google.com/maps/search/?api=1&query=${latest.lat},${latest.lng}`;
                            $('#google_maps_btn').attr('href', latestUrl).removeClass('d-none').addClass('d-flex');
                            
                            // Update simulation modal lat/lng to latest coordinate for easy stepping
                            $('#sim_latitude').val(latest.lat);
                            $('#sim_longitude').val(latest.lng);
                        } else {
                            $('#stat_last_seen').text('--');
                            $('#stat_last_seen_sub').text('No location data logged');
                            $('#google_maps_btn').addClass('d-none').removeClass('d-flex');
                        }

                        // 2. Draw Route Polyline & Snapping
                        if (route.length > 0) {
                            const drawPolyline = (latlngs) => {
                                if (routeLine) map.removeLayer(routeLine);
                                if (routeLineBG) map.removeLayer(routeLineBG);

                                // Glow background line
                                routeLineBG = L.polyline(latlngs, {
                                    color: '#3b82f6',
                                    weight: 12,
                                    opacity: 0.3,
                                    lineCap: 'round',
                                    lineJoin: 'round'
                                }).addTo(map);

                                // Foreground active polyline
                                routeLine = L.polyline(latlngs, {
                                    color: '#1d4ed8',
                                    weight: 6,
                                    opacity: 0.95,
                                    lineCap: 'round',
                                    lineJoin: 'round'
                                }).addTo(map);
                            };

                            const haversineM = (a, b) => {
                                const R = 6371000;
                                const dLat = (b[0] - a[0]) * Math.PI / 180;
                                const dLon = (b[1] - a[1]) * Math.PI / 180;
                                const x = Math.sin(dLat / 2) ** 2 +
                                    Math.cos(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) *
                                    Math.sin(dLon / 2) ** 2;
                                return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
                            };

                            const defaultLatlngs = route.map(p => [p.lat, p.lng]);

                            if (route.length > 1) {
                                drawPolyline(defaultLatlngs);
                                const longestLeg = defaultLatlngs.reduce((max, pt, idx) => {
                                    if (idx === 0) return max;
                                    return Math.max(max, haversineM(defaultLatlngs[idx - 1], pt));
                                }, 0);

                                // Only snap to roads when the employee actually traveled between stops.
                                // Snapping office GPS jitter creates the spider-web on the map.
                                if (longestLeg >= 250) {
                                    const coordsString = route.map(p => `${p.lng},${p.lat}`).join(';');
                                    const matchUrl = `https://router.project-osrm.org/match/v1/driving/${coordsString}?overview=full&geometries=geojson`;

                                    $.getJSON(matchUrl, function(data) {
                                        if (data.code === 'Ok' && data.matchings && data.matchings.length > 0) {
                                            const snappedLatlngs = data.matchings[0].geometry.coordinates.map(c => [c[1], c[0]]);
                                            drawPolyline(snappedLatlngs);
                                        }
                                    }).fail(function() {
                                        console.warn("OSRM routing server unavailable. Displaying stop-to-stop path.");
                                    });
                                }
                            }

                            // 3. Render stop markers, dwell circles & timeline
                            let timelineHtml = '';

                            route.forEach((pt, idx) => {
                                const isFirst = (idx === 0);
                                const isLast = (idx === route.length - 1);
                                const gmapsUrl = `https://www.google.com/maps/search/?api=1&query=${pt.lat},${pt.lng}`;
                                const dwell = pt.dwell_label || '';
                                const pingCount = pt.ping_count || 1;
                                const leftTime = pt.left_time || pt.time;

                                L.circle([pt.lat, pt.lng], {
                                    radius: 80,
                                    color: '#3b82f6',
                                    weight: 1,
                                    fillColor: '#3b82f6',
                                    fillOpacity: 0.12,
                                    opacity: 0.45
                                }).addTo(stayCirclesGroup);
                                
                                let markerClass = 'leaflet-div-icon-stay';
                                let markerSize = [22, 22];
                                let labelTitle = `Stop #${pt.index}`;
                                let timelineType = 'stay';
                                let badgeLetter = pt.index;

                                if (isFirst && (hasClockIn || pt.type === 'start')) {
                                    markerClass = 'leaflet-div-icon-start';
                                    markerSize = [16, 16];
                                    labelTitle = 'Morning start (clock-in)';
                                    timelineType = 'start';
                                    badgeLetter = 'S';
                                } else if (isFirst) {
                                    labelTitle = 'First recorded stop';
                                    badgeLetter = '1';
                                }

                                if (isLast) {
                                    if (healthStatus === 'signal_lost') {
                                        markerClass = 'leaflet-div-icon-end';
                                        markerSize = [16, 16];
                                        labelTitle = `Last stop (signal lost ${pt.time})`;
                                        timelineType = 'end';
                                        badgeLetter = '!';
                                    } else if (healthStatus === 'live_stationary') {
                                        markerClass = 'leaflet-div-icon-current';
                                        markerSize = [20, 20];
                                        labelTitle = `Currently here (${employeeName})`;
                                        timelineType = 'current';
                                        badgeLetter = 'L';
                                    } else if (isClockedIn) {
                                        markerClass = 'leaflet-div-icon-current';
                                        markerSize = [20, 20];
                                        labelTitle = `Current position (${employeeName})`;
                                        timelineType = 'current';
                                        badgeLetter = 'L';
                                    } else if (hasClockOut || pt.type === 'end') {
                                        markerClass = 'leaflet-div-icon-end';
                                        markerSize = [16, 16];
                                        labelTitle = 'Clock-out / end location';
                                        timelineType = 'end';
                                        badgeLetter = 'E';
                                    } else if (!isFirst) {
                                        labelTitle = `Last stop`;
                                        timelineType = 'stay';
                                        badgeLetter = pt.index;
                                    }
                                }

                                const marker = L.marker([pt.lat, pt.lng], {
                                    icon: L.divIcon({
                                        className: markerClass,
                                        iconSize: markerSize,
                                        html: (markerClass === 'leaflet-div-icon-waypoint' || markerClass === 'leaflet-div-icon-stay') ? pt.index : ''
                                    })
                                }).bindPopup(`
                                    <div style="min-width: 200px;">
                                        <b style="color: #1e293b; font-size: 0.9rem;">${labelTitle}</b><br>
                                        <span class="text-muted small"><i class="ti ti-clock"></i> ${pt.time}${dwell && leftTime !== pt.time ? ' – ' + leftTime : ''}</span><br>
                                        ${dwell ? `<span class="text-muted small d-block"><i class="ti ti-hourglass"></i> Stayed ${dwell} (${pingCount} GPS pings)</span>` : ''}
                                        <span class="text-muted small"><i class="ti ti-map-pin"></i> ${pt.lat.toFixed(5)}, ${pt.lng.toFixed(5)}</span><br>
                                        <a href="${gmapsUrl}" target="_blank" class="btn btn-sm btn-link p-0 text-primary mt-1 fw-bold">
                                            <i class="ti ti-external-link"></i> Open in Google Maps
                                        </a>
                                    </div>
                                `).addTo(markersGroup);

                                leafletMarkersMap[pt.id || idx] = marker;

                                const timeRange = (dwell && leftTime !== pt.time) ? `${pt.time} – ${leftTime}` : pt.time;
                                timelineHtml += `
                                    <div class="timeline-item ${timelineType}" data-point-id="${pt.id || idx}" data-lat="${pt.lat}" data-lng="${pt.lng}">
                                        <div class="timeline-badge">${badgeLetter}</div>
                                        <div class="timeline-card">
                                            <div class="timeline-title">
                                                <span>${labelTitle}</span>
                                                <span class="timeline-time">${timeRange}</span>
                                            </div>
                                            <div class="timeline-coords">${dwell ? 'Stayed ' + dwell + ' • ' : ''}${pt.lat.toFixed(5)}, ${pt.lng.toFixed(5)}</div>
                                        </div>
                                    </div>
                                `;
                            });

                            $('#timeline_list').html(timelineHtml);
                            $('#timeline_count_badge').text(`${route.length} stop${route.length === 1 ? '' : 's'}`);

                            // Click handler for timeline item to center map and open popup
                            $('.timeline-item').click(function() {
                                $('.timeline-item').removeClass('active-selected');
                                $(this).addClass('active-selected');

                                const ptId = $(this).data('point-id');
                                const lat = parseFloat($(this).data('lat'));
                                const lng = parseFloat($(this).data('lng'));

                                if (lat && lng) {
                                    map.setView([lat, lng], 16, { animate: true });
                                    if (leafletMarkersMap[ptId]) {
                                        leafletMarkersMap[ptId].openPopup();
                                    }
                                }
                            });

                        } else {
                            $('#timeline_list').html(`
                                <div class="text-center py-5 text-muted">
                                    <i class="ti ti-map-pin-off display-6 mb-2 d-block opacity-50"></i>
                                    <p class="mb-0">No movement pings recorded for this employee on ${date}.</p>
                                </div>
                            `);
                            $('#timeline_count_badge').text('0 stops');
                        }

                        // 4. Zoom / Fit Map bounds
                        if (!silent) {
                            if (route.length === 1) {
                                map.setView([route[0].lat, route[0].lng], 17);
                            } else if (route.length > 1) {
                                map.fitBounds(markersGroup.getBounds(), { padding: [50, 50], maxZoom: 16 });
                            } else {
                                map.setView(defaultCenter, 13);
                            }
                        }
                    },
                    error: function (xhr) {
                        console.error("Error fetching location tracking logs", xhr);
                        if (!silent) {
                            show_toastr('Error', xhr.responseJSON?.message || 'Failed to retrieve tracking data.', 'error');
                        }
                    }
                });
            }
        });
    </script>
@endpush
