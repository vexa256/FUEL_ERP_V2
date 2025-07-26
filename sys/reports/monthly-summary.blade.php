@extends('layouts.app')

@section('title', 'Monthly Summary Reports')

@section('breadcrumb')
    <span class="text-muted-foreground">Reports</span>
    <i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
    <span class="text-foreground font-medium">Monthly Summary</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-foreground">Monthly Summary Reports</h1>
        <p class="text-muted-foreground">Comprehensive monthly performance analysis and strategic insights</p>
    </div>
@endsection

@section('content')
<div x-data="{
    activeTab: 'executive',
    selectedStation: '{{ $station_id }}',
    selectedMonth: '{{ $month }}',
    loading: false,

    refreshData() {
        this.loading = true;
        const params = new URLSearchParams({
            station_id: this.selectedStation,
            month: this.selectedMonth
        });
        window.location.href = `{{ route('reports.monthly-summary') }}?${params}`;
    },

    formatNumber(num) {
        return new Intl.NumberFormat('en-UG').format(num);
    },

    getVarianceSeverityColor(severity) {
        const colors = {
            'low': 'bg-green-100 text-green-800',
            'medium': 'bg-yellow-100 text-yellow-800',
            'high': 'bg-orange-100 text-orange-800',
            'critical': 'bg-red-100 text-red-800'
        };
        return colors[severity] || 'bg-gray-100 text-gray-800';
    }
}" class="space-y-6">

    <!-- Controls Header -->
    <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Station Selector -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Station</label>
                <select x-model="selectedStation" @change="refreshData()"
                        class="select w-full" :disabled="loading">
                    @foreach($accessible_stations as $station)
                        <option value="{{ $station->id }}">{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Month Selector -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Month</label>
                <input type="month" x-model="selectedMonth" @change="refreshData()"
                       class="input w-full" :disabled="loading"
                       max="{{ now()->format('Y-m') }}">
            </div>

            <!-- Period Display -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Reporting Period</label>
                <div class="input w-full bg-muted flex items-center">
                    <i class="fas fa-calendar-alt mr-2 text-muted-foreground"></i>
                    <span class="text-sm">{{ \Carbon\Carbon::parse($month_start)->format('M d') }} - {{ \Carbon\Carbon::parse($month_end)->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-border">
        <nav class="flex space-x-8">
            <button @click="activeTab = 'executive'"
                    :class="activeTab === 'executive' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-chart-pie mr-2"></i>Executive
            </button>
            <button @click="activeTab = 'performance'"
                    :class="activeTab === 'performance' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-chart-line mr-2"></i>Performance
            </button>
            <button @click="activeTab = 'tanks'"
                    :class="activeTab === 'tanks' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-database mr-2"></i>Tank Insights
            </button>
            <button @click="activeTab = 'variance'"
                    :class="activeTab === 'variance' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-exclamation-triangle mr-2"></i>Variance Analysis
            </button>
            <button @click="activeTab = 'deliveries'"
                    :class="activeTab === 'deliveries' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-truck mr-2"></i>Deliveries
            </button>
        </nav>
    </div>

    <!-- Executive Tab -->
    <div x-show="activeTab === 'executive'" class="space-y-6">
        <!-- Executive KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Revenue</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($monthly_totals->total_sales ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                        <p class="text-xs text-green-600 font-medium mt-1">
                            <i class="fas fa-arrow-up mr-1"></i>
                            {{ number_format(($monthly_totals->total_sales ?? 0) / max(($monthly_totals->days_reported ?? 1), 1), 0) }}/day avg
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Gross Profit</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($monthly_totals->total_profit ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                        <p class="text-xs text-blue-600 font-medium mt-1">
                            <i class="fas fa-percentage mr-1"></i>
                            {{ ($monthly_totals->total_sales ?? 0) > 0 ? number_format((($monthly_totals->total_profit ?? 0) / $monthly_totals->total_sales) * 100, 1) : 0 }}% margin
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Volume Sold</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($monthly_totals->total_volume ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">L</span>
                        </p>
                        <p class="text-xs text-purple-600 font-medium mt-1">
                            <i class="fas fa-gas-pump mr-1"></i>
                            {{ number_format(($monthly_totals->total_volume ?? 0) / max(($monthly_totals->days_reported ?? 1), 1), 0) }}L/day
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-gas-pump text-purple-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Max Variance</p>
                        <p class="text-2xl font-bold {{ ($monthly_totals->max_variance ?? 0) > 5 ? 'text-red-600' : (($monthly_totals->max_variance ?? 0) > 2 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ number_format($monthly_totals->max_variance ?? 0, 2) }}%
                        </p>
                        <p class="text-xs {{ ($monthly_totals->variance_alerts ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }} font-medium mt-1">
                            <i class="fas {{ ($monthly_totals->variance_alerts ?? 0) > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>
                            {{ $monthly_totals->variance_alerts ?? 0 }} alerts
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full {{ ($monthly_totals->max_variance ?? 0) > 5 ? 'bg-red-100' : 'bg-orange-100' }} flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle {{ ($monthly_totals->max_variance ?? 0) > 5 ? 'text-red-600' : 'text-orange-600' }} text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="card p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-primary">{{ $monthly_totals->days_reported ?? 0 }}</p>
                    <p class="text-sm text-muted-foreground mt-1">Days Reported</p>
                    <div class="w-full bg-muted rounded-full h-2 mt-2">
                        <div class="bg-primary h-2 rounded-full" style="width: {{ (($monthly_totals->days_reported ?? 0) / \Carbon\Carbon::parse($month)->daysInMonth) * 100 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-primary">{{ $monthly_totals->tanks_active ?? 0 }}</p>
                    <p class="text-sm text-muted-foreground mt-1">Active Tanks</p>
                </div>
            </div>

            <div class="card p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-primary">{{ $monthly_totals->total_deliveries ?? 0 }}</p>
                    <p class="text-sm text-muted-foreground mt-1">Deliveries</p>
                </div>
            </div>

            <div class="card p-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-primary">{{ $monthly_totals->total_reconciliations ?? 0 }}</p>
                    <p class="text-sm text-muted-foreground mt-1">Reconciliations</p>
                </div>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <div class="card p-6">
            <h3 class="font-semibold text-foreground mb-6">Daily Performance Trend</h3>
            <div class="grid grid-cols-7 gap-2 mb-4">
                @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                    <div class="text-center text-xs font-medium text-muted-foreground">{{ $day }}</div>
                @endforeach
            </div>

            @php
                $monthStart = \Carbon\Carbon::parse($month_start);
                $monthEnd = \Carbon\Carbon::parse($month_end);
                $dailyData = $daily_trend->keyBy('reconciliation_date');
                $maxSales = $daily_trend->max('daily_sales') ?: 1;
            @endphp

            <div class="grid grid-cols-7 gap-2">
                @for($day = 1; $day <= $monthStart->daysInMonth; $day++)
                    @php
                        $currentDate = $monthStart->copy()->day($day);
                        $dateStr = $currentDate->format('Y-m-d');
                        $dayData = $dailyData->get($dateStr);
                        $intensity = $dayData ? ($dayData->daily_sales / $maxSales) : 0;
                        $colorIntensity = min(255, max(50, $intensity * 200));
                    @endphp

                    <div class="aspect-square rounded-lg border border-border flex flex-col items-center justify-center text-xs relative group
                        {{ $dayData ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground' }}"
                         style="{{ $dayData ? 'opacity: ' . (0.3 + $intensity * 0.7) : '' }}">
                        <span class="font-medium">{{ $day }}</span>
                        @if($dayData)
                            <span class="text-xs">{{ number_format($dayData->daily_sales / 1000, 0) }}k</span>
                        @endif

                        @if($dayData)
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                            <div class="bg-background border border-border rounded-lg p-3 shadow-lg text-foreground whitespace-nowrap">
                                <p class="font-medium">{{ $currentDate->format('M d, Y') }}</p>
                                <p class="text-sm">Sales: {{ number_format($dayData->daily_sales) }} UGX</p>
                                <p class="text-sm">Profit: {{ number_format($dayData->daily_profit) }} UGX</p>
                                <p class="text-sm">Volume: {{ number_format($dayData->daily_volume) }}L</p>
                            </div>
                        </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Performance Tab -->
    <div x-show="activeTab === 'performance'" class="space-y-6">
        <!-- Fuel Performance Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @foreach($fuel_performance as $fuel)
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full
                            {{ $fuel->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                            {{ $fuel->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                            {{ $fuel->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"></div>
                        <h3 class="font-semibold text-foreground">{{ ucfirst($fuel->fuel_type) }}</h3>
                    </div>
                    <span class="text-sm font-medium px-3 py-1 rounded-full
                        {{ $fuel->avg_margin > 15 ? 'bg-green-100 text-green-800' : ($fuel->avg_margin > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($fuel->avg_margin, 1) }}%
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Sales</span>
                        <span class="font-medium text-foreground">{{ number_format($fuel->fuel_sales, 0) }} UGX</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Profit</span>
                        <span class="font-medium text-foreground">{{ number_format($fuel->fuel_profit, 0) }} UGX</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Volume</span>
                        <span class="font-medium text-foreground">{{ number_format($fuel->fuel_volume, 0) }} L</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Avg Variance</span>
                        <span class="font-medium {{ $fuel->avg_variance > 2 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($fuel->avg_variance, 2) }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Incidents</span>
                        <span class="font-medium {{ $fuel->variance_incidents > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $fuel->variance_incidents }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Performance Comparison -->
        <div class="card p-6">
            <h3 class="font-semibold text-foreground mb-6">Fuel Type Comparison</h3>
            <div class="space-y-6">
                @php $totalSales = $fuel_performance->sum('fuel_sales'); @endphp

                <!-- Sales Distribution -->
                <div>
                    <h4 class="text-sm font-medium text-foreground mb-3">Sales Distribution</h4>
                    @foreach($fuel_performance as $fuel)
                        @php $percentage = $totalSales > 0 ? ($fuel->fuel_sales / $totalSales) * 100 : 0; @endphp
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full
                                    {{ $fuel->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                    {{ $fuel->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                    {{ $fuel->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"></div>
                                <span class="text-sm font-medium text-foreground">{{ ucfirst($fuel->fuel_type) }}</span>
                            </div>
                            <span class="text-sm text-muted-foreground">{{ number_format($percentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2 mb-3">
                            <div class="h-2 rounded-full
                                {{ $fuel->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                {{ $fuel->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                {{ $fuel->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"
                                style="width: {{ $percentage }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Tank Insights Tab -->
    <div x-show="activeTab === 'tanks'" class="space-y-6">
        <div class="card">
            <div class="border-b border-border p-6">
                <h3 class="font-semibold text-foreground">Tank Performance Matrix</h3>
                <p class="text-sm text-muted-foreground">Individual tank performance metrics and variance tracking</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Fuel</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Sales (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Profit (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Volume (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Margin %</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Avg Variance</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Max Variance</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Incidents</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($tank_insights as $tank)
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-foreground">{{ $tank->tank_number }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $tank->fuel_type === 'petrol' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $tank->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $tank->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($tank->fuel_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-foreground">{{ number_format($tank->tank_sales, 0) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-foreground">{{ number_format($tank->tank_profit, 0) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($tank->tank_volume, 0) }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <span class="font-medium {{ $tank->avg_margin > 15 ? 'text-green-600' : ($tank->avg_margin > 10 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($tank->avg_margin, 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <span class="font-medium {{ $tank->avg_variance > 2 ? 'text-red-600' : ($tank->avg_variance > 1 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ number_format($tank->avg_variance, 2) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <span class="font-medium {{ $tank->max_variance > 5 ? 'text-red-600' : ($tank->max_variance > 2 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ number_format($tank->max_variance, 2) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tank->variance_count > 0)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $tank->variance_count }}
                                    </span>
                                @else
                                    <span class="text-green-600"><i class="fas fa-check"></i></span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-foreground">{{ $tank->reconciliation_days }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-muted-foreground">
                                <i class="fas fa-database text-4xl mb-4 block"></i>
                                No tank data available for this period
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Variance Analysis Tab -->
    <div x-show="activeTab === 'variance'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Variance by Severity -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-6">Variance Alerts by Severity</h3>
                <div class="space-y-4">
                    @foreach($variance_analysis as $variance)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-border">
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $variance->severity === 'low' ? 'bg-green-100 text-green-800' : ($variance->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : ($variance->severity === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($variance->severity) }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ $variance->alert_count }} alerts</p>
                                <p class="text-xs text-muted-foreground">Avg: {{ number_format($variance->avg_variance_pct, 2) }}% | Max: {{ number_format($variance->max_variance_pct, 2) }}%</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-foreground">{{ number_format($variance->total_variance_liters, 0) }}L</p>
                            <p class="text-xs text-muted-foreground">Total impact</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Variance Impact Summary -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-6">Variance Impact Analysis</h3>
                <div class="space-y-6">
                    @php
                        $totalAlerts = $variance_analysis->sum('alert_count');
                        $totalImpact = $variance_analysis->sum('total_variance_liters');
                    @endphp

                    <div class="text-center p-4 bg-muted rounded-lg">
                        <p class="text-2xl font-bold text-foreground">{{ $totalAlerts }}</p>
                        <p class="text-sm text-muted-foreground">Total Variance Alerts</p>
                    </div>

                    <div class="text-center p-4 bg-muted rounded-lg">
                        <p class="text-2xl font-bold text-foreground">{{ number_format($totalImpact, 0) }}L</p>
                        <p class="text-sm text-muted-foreground">Total Volume Impact</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($variance_analysis as $variance)
                            @php $percentage = $totalAlerts > 0 ? ($variance->alert_count / $totalAlerts) * 100 : 0; @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-foreground">{{ ucfirst($variance->severity) }}</span>
                                    <span class="text-sm text-muted-foreground">{{ number_format($percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-muted rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $variance->severity === 'low' ? 'bg-green-500' : ($variance->severity === 'medium' ? 'bg-yellow-500' : ($variance->severity === 'high' ? 'bg-orange-500' : 'bg-red-500')) }}"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliveries Tab -->
    <div x-show="activeTab === 'deliveries'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Delivery Summary Cards -->
            <div class="space-y-4">
                @foreach($delivery_insights as $delivery)
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 rounded-full
                                {{ $delivery->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                {{ $delivery->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                {{ $delivery->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"></div>
                            <h3 class="font-semibold text-foreground">{{ ucfirst($delivery->fuel_type) }} Deliveries</h3>
                        </div>
                        <span class="text-sm font-medium px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                            {{ $delivery->total_deliveries }} deliveries
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Volume Delivered</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($delivery->total_delivered_volume, 0) }}L</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Total Cost</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($delivery->total_delivery_cost, 0) }} UGX</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Avg Cost/Liter</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($delivery->avg_cost_per_liter, 0) }} UGX</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Suppliers</p>
                            <p class="text-lg font-bold text-foreground">{{ $delivery->unique_suppliers }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Delivery Analysis -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-6">Delivery Analysis</h3>
                <div class="space-y-6">
                    @php
                        $totalDeliveryVolume = $delivery_insights->sum('total_delivered_volume');
                        $totalDeliveryCost = $delivery_insights->sum('total_delivery_cost');
                        $totalDeliveries = $delivery_insights->sum('total_deliveries');
                        $avgCostPerLiter = $totalDeliveryVolume > 0 ? $totalDeliveryCost / $totalDeliveryVolume : 0;
                    @endphp

                    <div class="grid grid-cols-1 gap-4">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-700">{{ number_format($totalDeliveryVolume, 0) }}L</p>
                            <p class="text-sm text-green-600">Total Volume Delivered</p>
                        </div>

                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-700">{{ number_format($totalDeliveryCost, 0) }} UGX</p>
                            <p class="text-sm text-blue-600">Total Delivery Cost</p>
                        </div>

                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-700">{{ number_format($avgCostPerLiter, 0) }} UGX</p>
                            <p class="text-sm text-purple-600">Weighted Avg Cost/Liter</p>
                        </div>
                    </div>

                    <!-- Fuel Type Distribution -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-medium text-foreground">Volume Distribution</h4>
                        @foreach($delivery_insights as $delivery)
                            @php $percentage = $totalDeliveryVolume > 0 ? ($delivery->total_delivered_volume / $totalDeliveryVolume) * 100 : 0; @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-foreground">{{ ucfirst($delivery->fuel_type) }}</span>
                                    <span class="text-sm text-muted-foreground">{{ number_format($percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-muted rounded-full h-2">
                                    <div class="h-2 rounded-full
                                        {{ $delivery->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                        {{ $delivery->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                        {{ $delivery->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="card p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-foreground font-medium">Loading monthly summary...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export functionality
    window.exportMonthlyReport = function() {
        const monthlyData = {
            totals: @json($monthly_totals),
            fuel_performance: @json($fuel_performance),
            tank_insights: @json($tank_insights),
            variance_analysis: @json($variance_analysis),
            delivery_insights: @json($delivery_insights)
        };

        const csv = generateMonthlyCSV(monthlyData);
        downloadCSV(csv, `monthly-summary-{{ $month }}.csv`);
    };

    function generateMonthlyCSV(data) {
        let csv = [
            'Monthly Summary Report',
            `Period: {{ \Carbon\Carbon::parse($month_start)->format('M d') }} - {{ \Carbon\Carbon::parse($month_end)->format('M d, Y') }}`,
            '',
            'EXECUTIVE SUMMARY',
            `Total Sales,${data.totals.total_sales}`,
            `Total Profit,${data.totals.total_profit}`,
            `Total Volume,${data.totals.total_volume}`,
            `Average Variance,${data.totals.avg_variance}%`,
            `Maximum Variance,${data.totals.max_variance}%`,
            `Variance Alerts,${data.totals.variance_alerts}`,
            '',
            'FUEL PERFORMANCE',
            'Fuel Type,Sales,Profit,Volume,Margin,Variance',
            ...data.fuel_performance.map(fuel =>
                `${fuel.fuel_type},${fuel.fuel_sales},${fuel.fuel_profit},${fuel.fuel_volume},${fuel.avg_margin}%,${fuel.avg_variance}%`
            )
        ].join('\n');

        return csv;
    }

    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
});
</script>
@endpush
