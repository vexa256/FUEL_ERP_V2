@extends('layouts.app')

@section('title', 'Daily Reconciliation Reports')

@section('breadcrumb')
    <span class="text-muted-foreground">Reports</span>
    <i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
    <span class="text-foreground font-medium">Daily Reconciliation</span>
@endsection

@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-foreground">Daily Reconciliation Reports</h1>
        <p class="text-muted-foreground">Comprehensive daily fuel reconciliation analysis and variance tracking</p>
    </div>
@endsection

@section('content')
<div x-data="{
    activeTab: 'overview',
    selectedStation: '{{ $station_id }}',
    selectedDate: '{{ $report_date }}',
    selectedTank: '{{ $tank_id }}',
    loading: false,

    refreshData() {
        this.loading = true;
        const params = new URLSearchParams({
            station_id: this.selectedStation,
            report_date: this.selectedDate,
            tank_id: this.selectedTank || ''
        });
        window.location.href = `{{ route('reports.daily-reconciliation') }}?${params}`;
    },

    filterTable(query) {
        const rows = document.querySelectorAll('[data-row]');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query.toLowerCase()) ? '' : 'none';
        });
    }
}" class="space-y-6">

    <!-- Controls Header -->
    <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <!-- Date Selector -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Report Date</label>
                <input type="date" x-model="selectedDate" @change="refreshData()"
                       class="input w-full" :disabled="loading"
                       max="{{ now()->format('Y-m-d') }}">
            </div>

            <!-- Tank Filter -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Tank Filter</label>
                <select x-model="selectedTank" @change="refreshData()"
                        class="select w-full" :disabled="loading">
                    <option value="">All Tanks</option>
                    @foreach($available_tanks as $tank)
                        <option value="{{ $tank->id }}">{{ $tank->tank_number }} ({{ ucfirst($tank->fuel_type) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="space-y-2">
                <label class="text-sm font-medium text-foreground">Search</label>
                <input type="text" placeholder="Filter results..."
                       class="input w-full" @input="filterTable($event.target.value)">
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-border">
        <nav class="flex space-x-8">
            <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-chart-line mr-2"></i>Overview
            </button>
            <button @click="activeTab = 'reconciliations'"
                    :class="activeTab === 'reconciliations' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-table mr-2"></i>Reconciliations
            </button>
            <button @click="activeTab = 'analytics'"
                    :class="activeTab === 'analytics' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    class="border-b-2 py-2 px-1 text-sm font-medium transition-colors">
                <i class="fas fa-analytics mr-2"></i>Analytics
            </button>
        </nav>
    </div>

    <!-- Overview Tab -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Sales</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($summary->total_sales ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Gross Profit</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($summary->total_profit ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">UGX</span>
                        </p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Volume Sold</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($summary->total_volume_sold ?? 0, 0) }}
                            <span class="text-sm text-muted-foreground">L</span>
                        </p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-gas-pump text-purple-600"></i>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-muted-foreground">Avg Variance</p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ number_format($summary->avg_variance ?? 0, 2) }}%
                            @if(($summary->avg_variance ?? 0) > 2)
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full ml-2">HIGH</span>
                            @endif
                        </p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-4">
                <h3 class="font-semibold text-foreground mb-2">Tanks Reconciled</h3>
                <p class="text-3xl font-bold text-primary">{{ $summary->total_tanks ?? 0 }}</p>
            </div>

            <div class="card p-4">
                <h3 class="font-semibold text-foreground mb-2">High Variance Alerts</h3>
                <p class="text-3xl font-bold {{ ($summary->high_variance_count ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $summary->high_variance_count ?? 0 }}
                </p>
            </div>

            <div class="card p-4">
                <h3 class="font-semibold text-foreground mb-2">Profit Margin</h3>
                <p class="text-3xl font-bold text-foreground">
                    {{ $summary->total_sales > 0 ? number_format((($summary->total_profit ?? 0) / $summary->total_sales) * 100, 1) : 0 }}%
                </p>
            </div>
        </div>
    </div>

    <!-- Reconciliations Tab -->
    <div x-show="activeTab === 'reconciliations'" class="space-y-4">
        <div class="card">
            <div class="border-b border-border p-4">
                <h3 class="font-semibold text-foreground">Daily Reconciliation Records</h3>
                <p class="text-sm text-muted-foreground">{{ $reconciliations->count() }} reconciliations for {{ \Carbon\Carbon::parse($report_date)->format('M d, Y') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Fuel Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Opening (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Delivered (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Dispensed (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Closing (L)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Variance</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Sales (UGX)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Profit (UGX)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($reconciliations as $rec)
                        <tr data-row class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-foreground">{{ $rec->tank_number }}</td>
                            <td class="px-4 py-3 text-sm text-foreground">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $rec->fuel_type === 'petrol' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $rec->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $rec->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($rec->fuel_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($rec->opening_stock_liters, 1) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($rec->total_delivered_liters, 1) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($rec->total_dispensed_liters, 1) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-foreground">{{ number_format($rec->actual_closing_stock_liters, 1) }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <span class="font-medium {{ abs($rec->variance_percentage) > 2 ? 'text-red-600' : (abs($rec->variance_percentage) > 1 ? 'text-yellow-600' : 'text-green-600') }}">
                                    {{ $rec->variance_percentage > 0 ? '+' : '' }}{{ number_format($rec->variance_percentage, 2) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-foreground font-medium">{{ number_format($rec->total_sales_ugx, 0) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-foreground font-medium">{{ number_format($rec->gross_profit_ugx, 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">
                                <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                No reconciliation data available for {{ \Carbon\Carbon::parse($report_date)->format('M d, Y') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Analytics Tab -->
    <div x-show="activeTab === 'analytics'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Fuel Type Breakdown -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-4">Fuel Type Performance</h3>
                <div class="space-y-4">
                    @php
                        $fuelTypes = $reconciliations->groupBy('fuel_type');
                        $totalSales = $reconciliations->sum('total_sales_ugx');
                    @endphp
                    @foreach($fuelTypes as $fuelType => $records)
                        @php
                            $fuelSales = $records->sum('total_sales_ugx');
                            $fuelVolume = $records->sum('total_dispensed_liters');
                            $percentage = $totalSales > 0 ? ($fuelSales / $totalSales) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full
                                    {{ $fuelType === 'petrol' ? 'bg-red-500' : '' }}
                                    {{ $fuelType === 'diesel' ? 'bg-blue-500' : '' }}
                                    {{ $fuelType === 'kerosene' ? 'bg-yellow-500' : '' }}"></div>
                                <span class="text-sm font-medium text-foreground">{{ ucfirst($fuelType) }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">{{ number_format($fuelSales, 0) }} UGX</p>
                                <p class="text-xs text-muted-foreground">{{ number_format($fuelVolume, 0) }}L ({{ number_format($percentage, 1) }}%)</p>
                            </div>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="h-2 rounded-full
                                {{ $fuelType === 'petrol' ? 'bg-red-500' : '' }}
                                {{ $fuelType === 'diesel' ? 'bg-blue-500' : '' }}
                                {{ $fuelType === 'kerosene' ? 'bg-yellow-500' : '' }}"
                                style="width: {{ $percentage }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Variance Analysis -->
            <div class="card p-6">
                <h3 class="font-semibold text-foreground mb-4">Variance Analysis</h3>
                <div class="space-y-4">
                    @php
                        $varianceRanges = [
                            ['min' => 0, 'max' => 1, 'label' => 'Normal (0-1%)', 'color' => 'bg-green-500'],
                            ['min' => 1, 'max' => 2, 'label' => 'Moderate (1-2%)', 'color' => 'bg-yellow-500'],
                            ['min' => 2, 'max' => 5, 'label' => 'High (2-5%)', 'color' => 'bg-orange-500'],
                            ['min' => 5, 'max' => 100, 'label' => 'Critical (>5%)', 'color' => 'bg-red-500']
                        ];
                    @endphp
                    @foreach($varianceRanges as $range)
                        @php
                            $count = $reconciliations->filter(function($rec) use ($range) {
                                $absVariance = abs($rec->variance_percentage);
                                return $absVariance >= $range['min'] && $absVariance < $range['max'];
                            })->count();
                            $percentage = $reconciliations->count() > 0 ? ($count / $reconciliations->count()) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full {{ $range['color'] }}"></div>
                                <span class="text-sm font-medium text-foreground">{{ $range['label'] }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">{{ $count }} tanks</p>
                                <p class="text-xs text-muted-foreground">{{ number_format($percentage, 1) }}%</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="card p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-foreground font-medium">Loading report data...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh every 5 minutes for real-time data
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            const currentParams = new URLSearchParams(window.location.search);
            if (currentParams.has('station_id')) {
                window.location.reload();
            }
        }
    }, 300000);

    // Export functionality
    window.exportReport = function() {
        const data = @json($reconciliations);
        const csv = convertToCSV(data);
        downloadCSV(csv, `daily-reconciliation-${new Date().toISOString().split('T')[0]}.csv`);
    };

    function convertToCSV(data) {
        if (!data.length) return '';
        const headers = Object.keys(data[0]).join(',');
        const rows = data.map(row => Object.values(row).join(','));
        return [headers, ...rows].join('\n');
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
