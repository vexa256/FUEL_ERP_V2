@extends('layouts.app')

@section('title', 'Weekly Summary Reports')

@section('breadcrumb')
    <span class="text-muted-foreground">Reports</span>
    <i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
    <span class="text-foreground font-medium">Weekly Summary</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-foreground">Weekly Summary Reports</h1>
        <p class="text-muted-foreground">7-day performance analysis and fuel type breakdown</p>
    </div>
@endsection

@section('content')
<div x-data="{
    activeTab: 'overview',
    selectedStation: '{{ $station_id }}',
    weekStart: '{{ $week_start }}',
    loading: false,

    refreshData() {
        this.loading = true;
        const params = new URLSearchParams({
            station_id: this.selectedStation,
            week_start: this.weekStart
        });
        window.location.href = `{{ route('reports.weekly-summary') }}?${params}`;
    },

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-UG', {
            style: 'currency',
            currency: 'UGX',
            minimumFractionDigits: 0
        }).format(amount);
    },

    calculateGrowth(current, previous) {
        if (!previous || previous === 0) return 0;
        return ((current - previous) / previous * 100).toFixed(1);
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

            <!-- Week Selector -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Week Starting</label>
                <input type="date" x-model="weekStart" @change="refreshData()"
                       class="input w-full" :disabled="loading"
                       max="{{ now()->startOfWeek()->format('Y-m-d') }}">
            </div>

            <!-- Week Range Display -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Period</label>
                <div class="input w-full bg-muted flex items-center">
                    <i class="fas fa-calendar mr-2 text-muted-foreground"></i>
                    <span class="text-sm">{{ \Carbon\Carbon::parse($week_start)->format('M d') }} - {{ \Carbon\Carbon::parse($week_end)->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-border">
        <nav class="flex space-x-8">
            <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-chart-bar mr-2"></i>Overview
            </button>
            <button @click="activeTab = 'daily'"
                    :class="activeTab === 'daily' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-calendar-day mr-2"></i>Daily Breakdown
            </button>
            <button @click="activeTab = 'fuel'"
                    :class="activeTab === 'fuel' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-gas-pump mr-2"></i>Fuel Analysis
            </button>
            <button @click="activeTab = 'trends'"
                    :class="activeTab === 'trends' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-trending-up mr-2"></i>Trends
            </button>
        </nav>
    </div>

    <!-- Overview Tab -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Week Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Sales</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($week_totals->total_sales ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                        <p class="text-xs text-green-600 font-medium mt-1">
                            <i class="fas fa-arrow-up mr-1"></i>
                            Avg: {{ number_format(($week_totals->total_sales ?? 0) / max(($week_totals->days_reported ?? 1), 1), 0) }}/day
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Gross Profit</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($week_totals->total_profit ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                        <p class="text-xs text-blue-600 font-medium mt-1">
                            <i class="fas fa-percentage mr-1"></i>
                            {{ ($week_totals->total_sales ?? 0) > 0 ? number_format((($week_totals->total_profit ?? 0) / $week_totals->total_sales) * 100, 1) : 0 }}% margin
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
                            {{ number_format($week_totals->total_volume ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">L</span>
                        </p>
                        <p class="text-xs text-purple-600 font-medium mt-1">
                            <i class="fas fa-tachometer-alt mr-1"></i>
                            {{ number_format(($week_totals->total_volume ?? 0) / max(($week_totals->days_reported ?? 1), 1), 0) }}L/day
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
                        <p class="text-sm text-muted-foreground">Avg Variance</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($week_totals->avg_variance ?? 0, 2) }}%
                        </p>
                        <p class="text-xs {{ ($week_totals->total_variance_alerts ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }} font-medium mt-1">
                            <i class="fas {{ ($week_totals->total_variance_alerts ?? 0) > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>
                            {{ $week_totals->total_variance_alerts ?? 0 }} alerts
                        </p>
                    </div>
                    <div class="h-12 w-12 rounded-full {{ ($week_totals->avg_variance ?? 0) > 2 ? 'bg-red-100' : 'bg-orange-100' }} flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle {{ ($week_totals->avg_variance ?? 0) > 2 ? 'text-red-600' : 'text-orange-600' }} text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-foreground">Days Reported</h3>
                    <i class="fas fa-calendar-check text-primary"></i>
                </div>
                <p class="text-3xl font-bold text-primary">{{ $week_totals->days_reported ?? 0 }}<span class="text-lg text-muted-foreground">/7</span></p>
                <div class="w-full bg-muted rounded-full h-2 mt-2">
                    <div class="bg-primary h-2 rounded-full" style="width: {{ (($week_totals->days_reported ?? 0) / 7) * 100 }}%"></div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-foreground">Active Tanks</h3>
                    <i class="fas fa-database text-primary"></i>
                </div>
                <p class="text-3xl font-bold text-primary">{{ $week_totals->tanks_active ?? 0 }}</p>
                <p class="text-sm text-muted-foreground mt-2">Tanks with activity</p>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-foreground">Cost Efficiency</h3>
                    <i class="fas fa-coins text-primary"></i>
                </div>
                <p class="text-3xl font-bold text-primary">
                    {{ ($week_totals->total_sales ?? 0) > 0 ? number_format((($week_totals->total_cogs ?? 0) / $week_totals->total_sales) * 100, 1) : 0 }}%
                </p>
                <p class="text-sm text-muted-foreground mt-2">COGS ratio</p>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown Tab -->
    <div x-show="activeTab === 'daily'" class="space-y-6">
        <div class="card">
            <div class="border-b border-border p-6">
                <h3 class="font-semibold text-foreground">Daily Performance Timeline</h3>
                <p class="text-sm text-muted-foreground">Day-by-day breakdown for {{ \Carbon\Carbon::parse($week_start)->format('M d') }} - {{ \Carbon\Carbon::parse($week_end)->format('M d, Y') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Fuel Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Sales (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Profit (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Volume (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Avg Variance</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Alerts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @php
                            $groupedData = $weekly_data->groupBy('reconciliation_date');
                            $currentDate = \Carbon\Carbon::parse($week_start);
                            $endDate = \Carbon\Carbon::parse($week_end);
                        @endphp

                        @while($currentDate <= $endDate)
                            @php
                                $dateStr = $currentDate->format('Y-m-d');
                                $dayData = $groupedData->get($dateStr, collect());
                                $isWeekend = $currentDate->isWeekend();
                            @endphp

                            @if($dayData->count() > 0)
                                @foreach($dayData as $data)
                                <tr class="hover:bg-muted/30 transition-colors {{ $isWeekend ? 'bg-blue-50/50' : '' }}">
                                    <td class="px-4 py-3 text-sm font-medium text-foreground">
                                        {{ $currentDate->format('D, M d') }}
                                        @if($isWeekend)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Weekend</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $data->fuel_type === 'petrol' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $data->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $data->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ ucfirst($data->fuel_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-medium text-foreground">{{ number_format($data->daily_sales, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium text-foreground">{{ number_format($data->daily_profit, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($data->daily_volume, 1) }}</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <span class="font-medium {{ $data->daily_avg_variance > 2 ? 'text-red-600' : ($data->daily_avg_variance > 1 ? 'text-yellow-600' : 'text-green-600') }}">
                                            {{ number_format($data->daily_avg_variance, 2) }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($data->daily_variance_alerts > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $data->daily_variance_alerts }}
                                            </span>
                                        @else
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr class="hover:bg-muted/30 transition-colors {{ $isWeekend ? 'bg-blue-50/50' : '' }}">
                                    <td class="px-4 py-3 text-sm font-medium text-muted-foreground">
                                        {{ $currentDate->format('D, M d') }}
                                        @if($isWeekend)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Weekend</span>
                                        @endif
                                    </td>
                                    <td colspan="6" class="px-4 py-3 text-sm text-center text-muted-foreground italic">No data recorded</td>
                                </tr>
                            @endif

                            @php $currentDate->addDay(); @endphp
                        @endwhile
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fuel Analysis Tab -->
    <div x-show="activeTab === 'fuel'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Fuel Performance Cards -->
            <div class="space-y-4">
                @foreach($fuel_breakdown as $fuel)
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 rounded-full
                                {{ $fuel->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                {{ $fuel->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                {{ $fuel->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"></div>
                            <h3 class="font-semibold text-foreground">{{ ucfirst($fuel->fuel_type) }}</h3>
                        </div>
                        <span class="text-sm font-medium px-2 py-1 rounded-full
                            {{ $fuel->avg_margin > 15 ? 'bg-green-100 text-green-800' : ($fuel->avg_margin > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ number_format($fuel->avg_margin, 1) }}% margin
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Sales</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($fuel->fuel_sales, 0) }} UGX</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Profit</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($fuel->fuel_profit, 0) }} UGX</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Volume</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($fuel->fuel_volume, 0) }} L</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">COGS</p>
                            <p class="text-lg font-bold text-foreground">{{ number_format($fuel->fuel_cogs, 0) }} UGX</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Fuel Mix Chart -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-6">Sales Distribution</h3>
                <div class="space-y-4">
                    @php $totalSales = $fuel_breakdown->sum('fuel_sales'); @endphp
                    @foreach($fuel_breakdown as $fuel)
                        @php $percentage = $totalSales > 0 ? ($fuel->fuel_sales / $totalSales) * 100 : 0; @endphp
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-foreground">{{ ucfirst($fuel->fuel_type) }}</span>
                                <span class="text-sm text-muted-foreground">{{ number_format($percentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-3">
                                <div class="h-3 rounded-full
                                    {{ $fuel->fuel_type === 'petrol' ? 'bg-red-500' : '' }}
                                    {{ $fuel->fuel_type === 'diesel' ? 'bg-blue-500' : '' }}
                                    {{ $fuel->fuel_type === 'kerosene' ? 'bg-yellow-500' : '' }}"
                                    style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Trends Tab -->
    <div x-show="activeTab === 'trends'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Performance Trends -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-4">Performance Indicators</h3>
                <div class="space-y-6">
                    @php
                        $dailyTotals = $weekly_data->groupBy('reconciliation_date')->map(function($dayData) {
                            return [
                                'sales' => $dayData->sum('daily_sales'),
                                'profit' => $dayData->sum('daily_profit'),
                                'volume' => $dayData->sum('daily_volume'),
                                'variance' => $dayData->avg('daily_avg_variance')
                            ];
                        });
                        $avgDailySales = $dailyTotals->avg('sales');
                        $avgDailyProfit = $dailyTotals->avg('profit');
                        $avgDailyVolume = $dailyTotals->avg('volume');
                    @endphp

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-foreground">Average Daily Sales</span>
                            <span class="text-sm font-bold text-foreground">{{ number_format($avgDailySales, 0) }} UGX</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-foreground">Average Daily Profit</span>
                            <span class="text-sm font-bold text-foreground">{{ number_format($avgDailyProfit, 0) }} UGX</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 78%"></div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-foreground">Average Daily Volume</span>
                            <span class="text-sm font-bold text-foreground">{{ number_format($avgDailyVolume, 0) }} L</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: 92%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Week Summary -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-4">Week Summary</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-green-800">Best Performing Day</p>
                            <p class="text-xs text-green-600">{{ $dailyTotals->sortByDesc('sales')->keys()->first() ? \Carbon\Carbon::parse($dailyTotals->sortByDesc('sales')->keys()->first())->format('D, M d') : 'N/A' }}</p>
                        </div>
                        <p class="text-lg font-bold text-green-700">{{ number_format($dailyTotals->max('sales'), 0) }} UGX</p>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-blue-800">Total Profit Margin</p>
                            <p class="text-xs text-blue-600">Week average</p>
                        </div>
                        <p class="text-lg font-bold text-blue-700">
                            {{ ($week_totals->total_sales ?? 0) > 0 ? number_format((($week_totals->total_profit ?? 0) / $week_totals->total_sales) * 100, 1) : 0 }}%
                        </p>
                    </div>

                    <div class="flex justify-between items-center p-3 {{ ($week_totals->avg_variance ?? 0) > 2 ? 'bg-red-50' : 'bg-orange-50' }} rounded-lg">
                        <div>
                            <p class="text-sm font-medium {{ ($week_totals->avg_variance ?? 0) > 2 ? 'text-red-800' : 'text-orange-800' }}">Variance Status</p>
                            <p class="text-xs {{ ($week_totals->avg_variance ?? 0) > 2 ? 'text-red-600' : 'text-orange-600' }}">{{ $week_totals->total_variance_alerts ?? 0 }} alerts this week</p>
                        </div>
                        <p class="text-lg font-bold {{ ($week_totals->avg_variance ?? 0) > 2 ? 'text-red-700' : 'text-orange-700' }}">
                            {{ number_format($week_totals->avg_variance ?? 0, 2) }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="card p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-foreground font-medium">Loading weekly summary...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh for real-time updates
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            const currentParams = new URLSearchParams(window.location.search);
            if (currentParams.has('station_id')) {
                window.location.reload();
            }
        }
    }, 600000); // 10 minutes for weekly data

    // Export functionality
    window.exportWeeklyReport = function() {
        const weeklyData = @json($weekly_data);
        const fuelData = @json($fuel_breakdown);
        const summary = @json($week_totals);

        const csv = [
            'Weekly Summary Report',
            `Period: {{ \Carbon\Carbon::parse($week_start)->format('M d') }} - {{ \Carbon\Carbon::parse($week_end)->format('M d, Y') }}`,
            '',
            'SUMMARY',
            `Total Sales,${summary.total_sales}`,
            `Total Profit,${summary.total_profit}`,
            `Total Volume,${summary.total_volume}`,
            `Average Variance,${summary.avg_variance}%`,
            '',
            'DAILY BREAKDOWN',
            'Date,Fuel Type,Sales,Profit,Volume,Variance',
            ...weeklyData.map(row => `${row.reconciliation_date},${row.fuel_type},${row.daily_sales},${row.daily_profit},${row.daily_volume},${row.daily_avg_variance}%`)
        ].join('\n');

        downloadCSV(csv, `weekly-summary-${new Date().toISOString().split('T')[0]}.csv`);
    };

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
