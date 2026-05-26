@extends('layouts.app')

@section('content')
<div class="dashboard-container at-mobile-scroll-fix container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        A/B Testing Analytics
                    </h2>
                    <p class="text-muted mb-0">
                        {{ $service->title }} - {{ $landingPage->hero_title }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('client.service-landing-pages.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Landing Pages
                    </a>
                    <a href="{{ route('services.landing-page.edit', ['service' => $service, 'landingPage' => $landingPage]) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($analytics->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No tracking data yet. Visit your landing page to start collecting analytics data.
        </div>
    @else
        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Views</div>
                                <h4 class="mb-0">{{ $analytics->sum('total_views') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-mouse-pointer"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total Clicks</div>
                                <h4 class="mb-0">{{ $analytics->sum('total_clicks') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Conversions</div>
                                <h4 class="mb-0">{{ $analytics->sum('total_submissions') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Avg Conversion Rate</div>
                                <h4 class="mb-0">{{ number_format($analytics->avg('conversion_rate'), 2) }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Variants Comparison Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Variants Performance Comparison
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Variant</th>
                                <th>Status</th>
                                <th class="text-end">Views</th>
                                <th class="text-end">Clicks</th>
                                <th class="text-end">Conversions</th>
                                <th class="text-end">Conversion Rate</th>
                                <th class="text-end">CTR</th>
                                <th class="text-end">Avg Time</th>
                                <th class="text-end">Scroll Depth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analytics->sortByDesc('conversion_rate') as $variant)
                                <tr class="{{ $variant['is_winner'] ? 'table-success' : '' }}">
                                    <td>
                                        <strong>{{ $variant['name'] }}</strong>
                                        @if($variant['is_winner'])
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-trophy"></i> Winner
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($variant['is_active'])
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($variant['total_views']) }}</td>
                                    <td class="text-end">{{ number_format($variant['total_clicks']) }}</td>
                                    <td class="text-end">
                                        <strong class="text-success">{{ number_format($variant['total_submissions']) }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary">{{ number_format($variant['conversion_rate'], 2) }}%</span>
                                    </td>
                                    <td class="text-end">{{ number_format($variant['ctr'], 2) }}%</td>
                                    <td class="text-end">{{ number_format($variant['avg_time_on_page'] ?? 0) }}s</td>
                                    <td class="text-end">{{ number_format($variant['avg_scroll_depth'] ?? 0) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

       {{-- Chart Visualization --}}
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Conversion Rate by Variant</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="conversionChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Traffic Distribution</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="trafficChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Conversion Rate Chart
    const conversionCtx = document.getElementById('conversionChart');
    if (conversionCtx) {
        new Chart(conversionCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($analytics->pluck('name')) !!},
                datasets: [{
                    label: 'Conversion Rate (%)',
                    data: {!! json_encode($analytics->pluck('conversion_rate')) !!},
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // Traffic Distribution Chart
    const trafficCtx = document.getElementById('trafficChart');
    if (trafficCtx) {
        new Chart(trafficCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($analytics->pluck('name')) !!},
                datasets: [{
                    data: {!! json_encode($analytics->pluck('total_views')) !!},
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
