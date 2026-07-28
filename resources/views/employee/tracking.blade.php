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
    
    <style>
        /* ── Map Container ─────────────────────────── */
        #map {
            height: 600px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            z-index: 1;
        }

        /* ── Form Controls & Sidebar ───────────────── */
        .filter-card {
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05) !important;
        }
        .filter-card .card-header {
            border-left: 4px solid var(--color-customColor, #c9a227);
            background: linear-gradient(to right, rgba(201,162,39,0.06) 0%, #fff 55%);
            padding: 14px 20px;
            border-bottom: 1px solid #ebebeb;
        }
        .filter-card .card-header h5 {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a202c;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-card .card-header h5 .ti {
            color: var(--color-customColor, #c9a227);
            font-size: 1.1rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            color: #4b5563;
            margin-bottom: 5px;
            display: inline-block;
        }
        .form-control, select.form-control {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            font-size: 0.875rem !important;
            padding: 8px 11px !important;
            background-color: #f9fafb !important;
            color: #374151 !important;
            height: auto !important;
        }
        .form-control:focus, select.form-control:focus {
            border-color: var(--color-customColor, #c9a227) !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 3px rgba(201,162,39,0.13) !important;
        }

        /* ── Sidebar stats & lists ────────────────── */
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .info-box-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
        }
        .info-box-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        /* ── Leaflet Custom Styling ────────────────── */
        .leaflet-div-icon-start {
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .leaflet-div-icon-end {
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .leaflet-div-icon-current {
            background: #3b82f6;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.4), 0 2px 6px rgba(0,0,0,0.4);
            animation: pulse-marker 1.8s infinite;
        }

        @keyframes pulse-marker {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7), 0 2px 6px rgba(0,0,0,0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0), 0 2px 6px rgba(0,0,0,0.4); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0), 0 2px 6px rgba(0,0,0,0.4); }
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <!-- Control panel column -->
        <div class="col-xl-3 col-lg-4 col-md-12 mb-4">
            <div class="card filter-card h-100 mb-0">
                <div class="card-header">
                    <h5><i class="ti ti-adjustments-horizontal"></i>{{ __('Tracking Options') }}</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <!-- Employee Filter -->
                        <div class="form-group mb-3">
                            <label for="employee_select" class="form-label">{{ __('Select Employee') }}</label>
                            <select id="employee_select" class="form-control select">
                                <option value="" disabled selected>{{ __('Select an Employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-name="{{ $employee->name }}">
                                        {{ $employee->name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Filter -->
                        <div class="form-group mb-4">
                            <label for="date_select" class="form-label">{{ __('Select Date') }}</label>
                            <input type="date" id="date_select" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <!-- Status Indicators -->
                        <div class="mb-4">
                            <h6 class="form-label mb-2">{{ __('Tracking Summary') }}</h6>
                            <div class="info-box">
                                <span class="info-box-title">{{ __('Status') }}</span>
                                <span class="info-box-value" id="status_text">--</span>
                            </div>
                            <div class="info-box">
                                <span class="info-box-title">{{ __('Last Active Location') }}</span>
                                <span class="info-box-value" id="last_seen">--</span>
                            </div>
                            <div class="info-box">
                                <span class="info-box-title">{{ __('Total Points Tracked') }}</span>
                                <span class="info-box-value" id="total_points">0</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="button" id="refresh_btn" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="ti ti-refresh"></i>{{ __('Refresh Data') }}
                        </button>
                        <a id="google_maps_btn" href="#" target="_blank" class="btn btn-outline-success w-100 d-none align-items-center justify-content-center gap-2 mt-2">
                            <i class="ti ti-map-pin"></i>{{ __('View on Google Maps') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map panel column -->
        <div class="col-xl-9 col-lg-8 col-md-12 mb-4">
            <div class="card filter-card h-100 mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="ti ti-map-pin"></i>{{ __('Live Route Visualization') }}</h5>
                    <span class="badge bg-light text-dark py-2 px-3 border" id="active_employee_label">No employee selected</span>
                </div>
                <div class="card-body p-2">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <!-- Leaflet.js JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        $(document).ready(function () {
            // Default center coordinate (Pune, India - or default fallback if no logs exist)
            const defaultCenter = [18.5204, 73.8567];
            
            // Initialize Leaflet Map
            const map = L.map('map').setView(defaultCenter, 13);

            // Add Google Maps Tile Layer
            L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 22,
                attribution: '© Google Maps'
            }).addTo(map);

            // Keep track of layers
            let routeLine = null;
            let markersGroup = L.featureGroup().addTo(map);

            // Fetch location data on change/click
            $('#employee_select, #date_select').change(function() {
                loadTrackingData();
            });

            $('#refresh_btn').click(function() {
                loadTrackingData();
            });

            // Set interval to auto-refresh current selected employee every 30 seconds
            setInterval(function() {
                if ($('#employee_select').val()) {
                    loadTrackingData(true); // silent refresh (doesn't fitBounds the map again to avoid interrupting admin viewing)
                }
            }, 30000);

            function loadTrackingData(silent = false) {
                const employeeId = $('#employee_select').val();
                const date = $('#date_select').val();

                if (!employeeId) return;

                const employeeName = $('#employee_select option:selected').attr('data-name');
                $('#active_employee_label').text(`${employeeName} - ${date}`);

                $.ajax({
                    url: "{{ route('employee.tracking-data') }}",
                    type: "GET",
                    dataType: "json",
                    data: {
                        employee_id: employeeId,
                        date: date
                    },
                    success: function (response) {
                        if (typeof response !== 'object' || response === null) {
                            console.error("Invalid response format:", response);
                            show_toastr('Error', 'Invalid data format received from server.', 'error');
                            return;
                        }
                        if (!response.success) {
                            show_toastr('Error', response.message || 'Failed to retrieve tracking data.', 'error');
                            return;
                        }

                        // Clear existing lines & markers
                        if (routeLine) {
                            map.removeLayer(routeLine);
                            routeLine = null;
                        }
                        markersGroup.clearLayers();

                        let route = response.route;
                        const latest = response.current_location;

                        // Clean GPS spikes/drift and stationary jitter from the route
                        if (route && route.length > 1) {
                            const cleanRoute = [];
                            for (let i = 0; i < route.length; i++) {
                                if (i === 0) {
                                    cleanRoute.push(route[i]);
                                    continue;
                                }
                                const prev = cleanRoute[cleanRoute.length - 1];
                                const curr = route[i];
                                const distance = map.distance([prev.lat, prev.lng], [curr.lat, curr.lng]);
                                
                                // 1. Filter out stationary jitter (movements < 15 meters while sitting in one place)
                                if (distance < 15) {
                                    continue;
                                }
                                
                                // 2. Detect single-point spike: sudden jump > 40m, but next point returns back within 20m of the previous
                                if (distance > 40 && i < route.length - 1) {
                                    const next = route[i + 1];
                                    const distancePrevToNext = map.distance([prev.lat, prev.lng], [next.lat, next.lng]);
                                    if (distancePrevToNext < 20) {
                                        console.log("Filtered out GPS spike:", curr);
                                        continue;
                                    }
                                }
                                cleanRoute.push(curr);
                            }
                            route = cleanRoute;
                        }

                        // 1. Draw Route Polyline (Following actual roads using OSRM Routing API)
                        if (route && route.length > 0) {
                            const drawPolyline = (latlngs) => {
                                if (routeLine) map.removeLayer(routeLine);
                                routeLine = L.polyline(latlngs, {
                                    color: 'var(--color-customColor, #c9a227)',
                                    weight: 5,
                                    opacity: 0.85
                                }).addTo(map);
                            };

                            const defaultLatlngs = route.map(p => [p.lat, p.lng]);
                            drawPolyline(defaultLatlngs); // Draw straight lines immediately as fallback

                            if (route.length > 1) {
                                // Downsample route if too long for OSRM URL limit (max 80 coordinates)
                                let sampledRoute = route;
                                if (route.length > 80) {
                                    const step = Math.ceil(route.length / 80);
                                    sampledRoute = route.filter((_, idx) => idx % step === 0 || idx === route.length - 1);
                                }

                                const coordsString = sampledRoute.map(p => `${p.lng},${p.lat}`).join(';');
                                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${coordsString}?overview=full&geometries=geojson`;

                                $.getJSON(osrmUrl, function(data) {
                                    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                                        const routeCoords = data.routes[0].geometry.coordinates;
                                        const snappedLatlngs = routeCoords.map(coord => [coord[1], coord[0]]);
                                        drawPolyline(snappedLatlngs); // Update with snapped road route
                                    }
                                }).fail(function() {
                                    console.warn("OSRM routing failed. Falling back to straight lines.");
                                });
                            }

                            // Draw start point marker
                            const start = route[0];
                            const startUrl = `https://www.google.com/maps/search/?api=1&query=${start.lat},${start.lng}`;
                            L.marker([start.lat, start.lng], {
                                icon: L.divIcon({
                                    className: 'leaflet-div-icon-start',
                                    iconSize: [12, 12]
                                })
                            }).bindPopup(`<b>Start Location</b><br>Time: ${start.time}<br><a href="${startUrl}" target="_blank" class="btn btn-sm btn-link p-0 text-primary mt-1"><i class="ti ti-map-pin"></i> Open in Google Maps</a>`).addTo(markersGroup);

                            // Draw end point marker (last coordinate of route for selected date)
                            const end = route[route.length - 1];
                            const endUrl = `https://www.google.com/maps/search/?api=1&query=${end.lat},${end.lng}`;
                            L.marker([end.lat, end.lng], {
                                icon: L.divIcon({
                                    className: 'leaflet-div-icon-end',
                                    iconSize: [12, 12]
                                })
                            }).bindPopup(`<b>End Location</b><br>Time: ${end.time}<br><a href="${endUrl}" target="_blank" class="btn btn-sm btn-link p-0 text-primary mt-1"><i class="ti ti-map-pin"></i> Open in Google Maps</a>`).addTo(markersGroup);

                            $('#total_points').text(route.length);
                            $('#status_text').html('<span class="text-success"><i class="ti ti-circle-filled"></i> Route Tracked</span>');
                        } else {
                            $('#total_points').text(0);
                            $('#status_text').html('<span class="text-warning"><i class="ti ti-circle-filled"></i> No movement recorded</span>');
                        }

                        // 2. Draw Live Current Location Marker (if available)
                        if (latest) {
                            const latestUrl = `https://www.google.com/maps/search/?api=1&query=${latest.lat},${latest.lng}`;
                            const liveMarker = L.marker([latest.lat, latest.lng], {
                                icon: L.divIcon({
                                    className: 'leaflet-div-icon-current',
                                    iconSize: [18, 18]
                                })
                            }).bindPopup(`<b>${employeeName} (Current Position)</b><br>Last Seen: ${latest.time}<br><a href="${latestUrl}" target="_blank" class="btn btn-sm btn-link p-0 text-primary mt-1"><i class="ti ti-map-pin"></i> Open in Google Maps</a>`).addTo(markersGroup);
                            
                            $('#last_seen').text(latest.time);
                            $('#google_maps_btn').attr('href', latestUrl).removeClass('d-none').addClass('d-flex');
                        } else {
                            $('#last_seen').text('--');
                            $('#google_maps_btn').addClass('d-none').removeClass('d-flex');
                        }

                        // 3. Zoom / Fit Map bounds
                        if (!silent) {
                            if (route && route.length > 0) {
                                map.fitBounds(markersGroup.getBounds(), { padding: [50, 50] });
                            } else if (latest) {
                                map.setView([latest.lat, latest.lng], 15);
                            } else {
                                map.setView(defaultCenter, 13);
                            }
                        }
                    },
                    error: function (xhr) {
                        console.error("Error fetching location tracking logs", xhr);
                        let errorMsg = 'Failed to retrieve tracking data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.status) {
                            errorMsg += ' (Status: ' + xhr.status + ' ' + xhr.statusText + ')';
                            if (xhr.responseText && xhr.responseText.trim().startsWith('{')) {
                                try {
                                    const parsed = JSON.parse(xhr.responseText);
                                    if (parsed.message) errorMsg += ' - ' + parsed.message;
                                } catch(e){}
                            } else if (xhr.responseText) {
                                errorMsg += ' - ' + xhr.responseText.substring(0, 80).replace(/<[^>]*>/g, '');
                            }
                        }
                        show_toastr('Error', errorMsg, 'error');
                    }
                });
            }
        });
    </script>
@endpush
