@extends('layouts.admin')

@section('main-content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('Dashboard') }}</h1>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success border-left-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <!-- Total Store Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Store</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalStores }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Departments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Departments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalDepartments }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Updated Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Last Updated</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $lastUpdatedDateOnly ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">{{ __('Users') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $widget['users'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Performance --}}
    <div class="row">
        <div class="col-lg-12 mb-2">
            <div class="card shadow mb-4">
                {{-- Filter --}}
                @php
                    $currentFilterDept = request('department', $selectedDept ?? 1);
                    $currentFilterStore = request('store', $selectedStore ?? 1);
                    $currentFilterPeriod = request('period', 'monthly');
                    $currentFilterChartType = request('chartType', 'line');

                    $periodDisplayLabels = [
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                    ];
                    $chartTypeDisplayLabels = [
                        'line' => 'Line',
                        'bar' => 'Bar',
                    ];

                    $periodIcons = [
                        'daily' => 'fa-calendar-day',
                        'weekly' => 'fa-calendar-week',
                        'monthly' => 'fa-calendar-alt',
                    ];
                    $chartIcons = [
                        'line' => 'fa-chart-line',
                        'bar' => 'fa-chart-bar',
                    ];
                @endphp

                <div class="card-header py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center">
                        {{-- Title Chart --}}
                        <h6 class="m-0 font-weight-bold text-primary mb-3 mb-md-0" id="chartMainTitle">
                            Sales Performance - Store {{ $currentFilterStore }} / Dept {{ $currentFilterDept }}
                        </h6>
                        <div class="d-flex flex-wrap justify-content-start justify-content-md-end align-items-center">
                            {{-- Dropdown Store --}}
                            <div class="dropdown mr-1 mb-1">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle text-nowrap"
                                    style="min-width: 160px; height: 38px; font-size: 0.8rem;" type="button"
                                    id="storeDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-store mr-1"></i> Store: {{ $currentFilterStore }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="storeDropdown"
                                    style="font-size: 0.8rem;">
                                    @foreach ($distinctStores as $storeId)
                                        <a class="dropdown-item {{ $storeId == $currentFilterStore ? 'active' : '' }}"
                                            href="?department={{ $currentFilterDept }}&store={{ $storeId }}&period={{ $currentFilterPeriod }}&chartType={{ $currentFilterChartType }}">
                                            <i class="fas fa-store fa-fw mr-2"></i> Store
                                            {{ str_pad($storeId, 2, '0', STR_PAD_LEFT) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Dropdown Departemen --}}
                            <div class="dropdown mr-1 mb-1">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle text-nowrap"
                                    style="min-width: 165px; height: 38px; font-size: 0.8rem;" type="button"
                                    id="departmentDropdown" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    <i class="fas fa-building mr-1"></i> Dept: {{ $currentFilterDept }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow-sm"
                                    aria-labelledby="departmentDropdown" style="font-size: 0.8rem;">
                                    @foreach ($distinctDepartments as $deptId)
                                        <a class="dropdown-item {{ $deptId == $currentFilterDept ? 'active' : '' }}"
                                            href="?department={{ $deptId }}&store={{ $currentFilterStore }}&period={{ $currentFilterPeriod }}&chartType={{ $currentFilterChartType }}">
                                            <i class="fas fa-building fa-fw mr-2"></i> Department
                                            {{ str_pad($deptId, 2, '0', STR_PAD_LEFT) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Dropdown Periode --}}
                            <div class="dropdown mr-1 mb-1">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle text-nowrap"
                                    style="min-width: 130px; height: 38px; font-size: 0.8rem;" type="button"
                                    id="periodDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas {{ $periodIcons[$currentFilterPeriod] ?? 'fa-calendar-alt' }} mr-1"></i>
                                    {{ $periodDisplayLabels[$currentFilterPeriod] ?? ucfirst($currentFilterPeriod) }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="periodDropdown"
                                    style="font-size: 0.8rem; min-width: 130px;">
                                    @foreach ($periodDisplayLabels as $periodValue => $periodLabel)
                                        <a class="dropdown-item {{ $periodValue == $currentFilterPeriod ? 'active' : '' }}"
                                            href="?department={{ $currentFilterDept }}&store={{ $currentFilterStore }}&period={{ $periodValue }}&chartType={{ $currentFilterChartType }}">
                                            <i
                                                class="fas {{ $periodIcons[$periodValue] ?? 'fa-calendar-alt' }} fa-fw mr-2"></i>
                                            {{ $periodLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Dropdown Chart --}}
                            <div class="dropdown mr-1 mb-1">
                                <button class="btn btn-sm btn-outline-success dropdown-toggle text-nowrap"
                                    style="min-width: 150px; height: 38px; font-size: 0.8rem;" type="button"
                                    id="chartTypeDropdown" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    <i class="fas {{ $chartIcons[$currentFilterChartType] ?? 'fa-chart-line' }} mr-1"></i>
                                    {{ $chartTypeDisplayLabels[$currentFilterChartType] ?? ucfirst($currentFilterChartType) }}
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow-sm"
                                    aria-labelledby="chartTypeDropdown" id="chartTypeDropdownMenu"
                                    style="font-size: 0.8rem; min-width: 150px;">
                                    @foreach ($chartTypeDisplayLabels as $typeValue => $typeLabel)
                                        <a class="dropdown-item chart-type-selector {{ $typeValue == $currentFilterChartType ? 'active' : '' }}"
                                            href="?department={{ $currentFilterDept }}&store={{ $currentFilterStore }}&period={{ $currentFilterPeriod }}&chartType={{ $typeValue }}">
                                            <i
                                                class="fas {{ $chartIcons[$typeValue] ?? 'fa-chart-line' }} fa-fw mr-2"></i>
                                            {{ $typeLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Zoom Button --}}
                            <div class="">
                                <div class="button-container">
                                    <div class="btn-group mb-1 " role="group" aria-label="Zoom controls">
                                        <button id="zoomInBtn" class="btn btn-sm btn-outline-secondary" title="Zoom In"
                                            style="height: 38px; font-size: 0.8rem; min-width: 45px;">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <button id="zoomOutBtn" class="btn btn-sm btn-outline-secondary" title="Zoom Out"
                                            style="height: 38px; font-size: 0.8rem; min-width: 45px;">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <button id="resetZoomBtn" class="btn btn-sm btn-outline-secondary"
                                            title="Reset Zoom" style="height: 38px; font-size: 0.8rem; min-width: 45px;">
                                            <i class="fas fa-compress-arrows-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body" wire:ignore>
                    <div style="position: relative; width: 100%; height: 500px;">
                        <canvas id="forecastChart"></canvas>
                    </div>
                </div>

                <div class="card-footer">
                    <small class="text-muted">
                        Showing data for Department {{ $selectedDept }} │
                        Last updated:
                        {{ $lastUpdated ?? 'N/A' }}
                    </small>
                </div>
            </div>
        </div>

        {{-- Analisis Sentimen --}}
        <div class="col-lg-12 mb-2">
            <div class="card shadow mb-4">
                <div class="card-header py-6">
                    <h6 class="m-0 font-weight-bold text-primary">Sentimen Analisis</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-5 col-md-12 mb-4 mb-lg-0">
                            {{-- Card Sentimen Positif --}}
                            <div class="card sentiment-card positive-card shadow-sm mb-3">
                                <div class="card-body d-flex flex-row align-items-center p-4">
                                    <div class="ml-5">
                                        <i class="{{ $sentimentIcons['positif'] }} fa-4x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 text-center">
                                        <div class="text-uppercase text-muted mb-1" style="font-size: 0.9rem;">Sentimen
                                            Positif</div>
                                        <div class="h3 font-weight-bold text-success mb-0">
                                            {{ $persentasePositif }}%
                                        </div>
                                        <small class="text-muted">({{ number_format($jumlahPositif) }} ulasan)</small>
                                    </div>
                                </div>
                            </div>
                            {{-- Card Sentimen Negatif --}}
                            <div class="card sentiment-card negative-card shadow-sm mb-3">
                                <div class="card-body d-flex flex-row align-items-center p-4">
                                    <div class="ml-5">
                                        <i class="{{ $sentimentIcons['negatif'] }} fa-4x text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1 text-center">
                                        <div class="text-uppercase text-muted mb-1" style="font-size: 0.9rem;">Sentimen
                                            Negatif</div>
                                        <div class="h3 font-weight-bold text-danger mb-0">
                                            {{ $persentaseNegatif }}%
                                        </div>
                                        <small class="text-muted">({{ number_format($jumlahNegatif) }} ulasan)</small>
                                    </div>
                                </div>
                            </div>
                            {{-- Card Sentimen Netral --}}
                            <div class="card sentiment-card neutral-card shadow-sm">
                                <div class="card-body d-flex flex-row align-items-center p-4">
                                    <div class="ml-5">
                                        <i class="{{ $sentimentIcons['netral'] }} fa-4x text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 text-center">
                                        <div class="text-uppercase text-muted mb-1" style="font-size: 0.9rem;">Sentimen
                                            Netral</div>
                                        <div class="h3 font-weight-bold text-warning mb-0">
                                            {{ $persentaseNetral }}%
                                        </div>
                                        <small class="text-muted">({{ number_format($jumlahNetral) }} ulasan)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Chart Donat Kata Populer --}}
                        <div class="col-lg-7 col-md-12">
                            <h6 class="m-0 font-weight-bold text-primary mb-5 text-center text-lg-left"
                                style="font-size: 16px;">Kata Populer
                            </h6>
                            <div style="height: 350px; width:100%; max-width: 900px; margin: 0 auto; position: relative;">
                                <canvas id="sentimentDonutChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <small class="text-muted">
                        Showing data for Sentiment Analysis │
                        Last updated:
                        {{ $sentimentLastUpdateDisplay ?? 'N/A' }}
                    </small>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let forecastChartInstance = null;
                    let agregatChartInstance = null;
                    let sentimentDonutChartInstance = null;

                    // --- HELPER FUNCTIONS ---

                    /**
                     * Formats a number into a compact K/M/B format.
                     * @param {number} num The number to format.
                     * @returns {string} The formatted number string.
                     */
                    function formatNumberKMB(num) {
                        if (num >= 1000000000) return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
                        if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
                        if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
                        return num.toString();
                    }

                    /**
                     * Safely destroys a Chart.js instance.
                     * @param {Chart} instance The chart instance to destroy.
                     */
                    const destroyChart = (instance) => {
                        if (instance) {
                            instance.destroy();
                        }
                    };

                    const zoomPluginOptions = {
                        pan: {
                            enabled: true,
                            mode: 'xy'
                        },
                        zoom: {
                            wheel: {
                                enabled: false
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'xy'
                        }
                    };

                    // --- CHART CREATION FUNCTIONS ---

                    function createForecastChart() {
                        destroyChart(forecastChartInstance);
                        const ctx = document.getElementById('forecastChart');
                        if (!ctx) return;

                        const chartType = @json($currentFilterChartType);

                        forecastChartInstance = new Chart(ctx, {
                            type: chartType, // Use dynamic chart type
                            data: {
                                labels: @json($months ?? []),
                                datasets: [{
                                    label: 'Actual Sales',
                                    data: @json($actualSales ?? []),
                                    borderColor: 'rgba(78, 115, 223, 1)',
                                    backgroundColor: chartType === 'bar' ? 'rgba(78, 115, 223, 0.8)' :
                                        'rgba(78, 115, 223, 0.1)',
                                    fill: chartType !== 'bar',
                                    tension: 0.3
                                }, {
                                    label: 'Forecast Sales',
                                    data: @json($forecastSales ?? []),
                                    borderColor: 'rgba(231, 74, 59, 1)',
                                    backgroundColor: chartType === 'bar' ? 'rgba(231, 74, 59, 0.8)' :
                                        'rgba(231, 74, 59, 0.1)',
                                    borderDash: [5, 5],
                                    fill: false,
                                    tension: 0.3
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        ticks: {
                                            callback: value => '$' + value.toLocaleString('en-US')
                                        }
                                    }
                                },
                                plugins: {
                                    zoom: zoomPluginOptions,
                                    tooltip: {
                                        callbacks: {
                                            title: (tooltipItems) => tooltipItems[0].label.replace(/ \d{4}/, '')
                                        }
                                    }
                                }
                            }
                        });
                    }

                    function createAgregatChart() {
                        destroyChart(agregatChartInstance);
                        const ctx = document.getElementById('agregatChart');
                        if (!ctx) return;

                        agregatChartInstance = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: @json($agregatLabels ?? []),
                                datasets: [{
                                    label: 'Actual Aggregate',
                                    data: @json($actualAgregatData ?? []),
                                    borderColor: 'rgba(28, 200, 138, 1)',
                                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                }, {
                                    label: 'Forecast Aggregate',
                                    data: @json($forecastAgregatData ?? []),
                                    borderColor: 'rgba(255, 159, 64, 1)',
                                    borderDash: [5, 5],
                                    fill: false,
                                    tension: 0.3
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    zoom: zoomPluginOptions,
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                if (context.parsed.y !== null) {
                                                    label += '$' + context.parsed.y.toLocaleString('en-US');
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        ticks: {
                                            callback: value => '$' + value.toLocaleString('en-US')
                                        }
                                    }
                                }
                            }
                        });
                    }

                    function renderSentimentDonutChart() {
                        destroyChart(sentimentDonutChartInstance);
                        const canvasElement = document.getElementById('sentimentDonutChart');
                        if (!canvasElement) {
                            console.error("Canvas element with ID 'sentimentDonutChart' not found.");
                            return;
                        }
                        const ctx = canvasElement.getContext('2d');

                        const sentimentLabels = @json($sentimentDonutLabels ?? []);
                        const sentimentDataValues = @json($sentimentDonutDataValues ?? []);
                        const totalCommentsRaw = @json($totalSentimenComments ?? 0);

                        if (sentimentLabels.length === 0 || sentimentDataValues.every(v => v === 0)) {
                            ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font = '14px "Inter", sans-serif';
                            ctx.fillStyle = '#888';
                            ctx.fillText('Tidak ada data kata populer untuk ditampilkan.', canvasElement.width / 2,
                                canvasElement.height / 2);
                            console.warn("No data for sentiment donut chart.");
                            return;
                        }

                        // Custom plugin to draw text in the center
                        const centerTextPlugin = {
                            id: 'centerText',
                            afterDraw: (chart) => {
                                const {
                                    ctx
                                } = chart;
                                const chartArea = chart.chartArea;
                                if (!chartArea) return;

                                const centerX = (chartArea.left + chartArea.right) / 2;
                                const centerY = (chartArea.top + chartArea.bottom) / 2;

                                // Total Comments (Formatted)
                                ctx.save();
                                ctx.font = "bold 25px 'Inter', sans-serif";
                                ctx.fillStyle = '#333';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(formatNumberKMB(totalCommentsRaw), centerX, centerY - 7);

                                // "Komentar" label
                                ctx.font = "12px 'Inter', sans-serif";
                                ctx.fillStyle = "#6c757d";
                                ctx.fillText("Komentar", centerX, centerY + 10);
                                ctx.restore();
                            }
                        };

                        sentimentDonutChartInstance = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: sentimentLabels,
                                datasets: [{
                                    label: 'Frekuensi Kata Populer',
                                    data: sentimentDataValues,
                                    backgroundColor: [
                                        'rgba(28, 200, 138, 0.8)', 'rgba(78, 115, 223, 0.8)',
                                        'rgba(246, 194, 62, 0.8)', 'rgba(231, 74, 59, 0.8)',
                                        'rgba(54, 185, 204, 0.8)', 'rgba(133, 135, 150, 0.8)',
                                        'rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)',
                                        'rgba(255, 206, 86, 0.8)', 'rgba(75, 192, 192, 0.8)'
                                    ].slice(0, sentimentDataValues.length),
                                    borderColor: '#fff',
                                    borderWidth: 3,
                                    hoverOffset: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '65%',
                                layout: {
                                    padding: {
                                        top: 5,
                                        bottom: 20,
                                        left: 5,
                                        right: 5
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: {
                                                size: 11,
                                                family: "'Inter', sans-serif"
                                            },
                                            boxWidth: 15,
                                            padding: 20,
                                            color: '#333'
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                if (context.parsed !== null) {
                                                    label += context.parsed.toLocaleString('id-ID');
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                            },
                            plugins: [centerTextPlugin] // Register the custom plugin instance
                        });
                    }

                    // --- INITIAL RENDERING ---
                    createForecastChart();
                    createAgregatChart();
                    renderSentimentDonutChart();

                    // --- EVENT LISTENERS FOR ZOOM ---
                    const setupZoomControls = (chartInstance, zoomInId, zoomOutId, resetZoomId) => {
                        const zoomInBtn = document.getElementById(zoomInId);
                        const zoomOutBtn = document.getElementById(zoomOutId);
                        const resetZoomBtn = document.getElementById(resetZoomId);

                        if (zoomInBtn) zoomInBtn.addEventListener('click', () => chartInstance?.zoom(1.1));
                        if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => chartInstance?.zoom(0.9));
                        if (resetZoomBtn) resetZoomBtn.addEventListener('click', () => chartInstance?.resetZoom());
                    };

                    setupZoomControls(forecastChartInstance, 'zoomInBtn', 'zoomOutBtn', 'resetZoomBtn');
                    setupZoomControls(agregatChartInstance, 'agregatZoomInBtn', 'agregatZoomOutBtn', 'agregatResetZoomBtn');
                });
            </script>
        @endpush


        @push('styles')
            <style>
                .sentiment-card .card-body {
                    padding: 1.5rem 1rem;
                }

                .sentiment-card .display-4 {
                    font-size: 2.75rem;
                    line-height: 1.2;
                }


                .positive-card,
                .negative-card,
                .neutral-card {
                    border-left: none !important;
                }


                .card-header .d-flex.flex-wrap .dropdown,
                .card-header .d-flex.flex-wrap .btn-group {
                    margin-bottom: 0.5rem;
                }

                @media (min-width: 768px) {

                    .card-header .d-flex.flex-wrap .dropdown,
                    .card-header .d-flex.flex-wrap .btn-group {
                        margin-bottom: 0;
                    }
                }
            </style>
        @endpush
    @endsection
