@extends('layouts.app')

@section('title', 'Pricing Analysis')

@section('content')
<div x-data="pricingAnalysis()" class="space-y-6">
    <!-- Station Selector -->
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h3 class="text-sm font-medium">Station Selection</h3>
                @if(auth()->user()->role === 'admin')
                    <select x-model="selectedStation" @change="changeStation()" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option value="">All Stations</option>
                        @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ $accessible_stations->first()->name ?? 'No Station' }}</span>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold">{{ $accessible_stations->first()->location ?? '' }}</span>
                    </div>
                @endif
            </div>
            <div class="flex gap-2">
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ $summary_stats->active_prices ?? 0 }} Active</span>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">{{ $summary_stats->stations_count ?? 0 }} Stations</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
        <!-- Tab Navigation -->
        <div class="border-b border-border">
            <nav class="flex space-x-8 px-6" role="tablist">
                <button @click="setTab('overview')"
                        :class="activeTab === 'overview' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i>Overview
                </button>
                <button @click="setTab('profit')"
                        :class="activeTab === 'profit' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>Profit Analysis
                </button>
                <button @click="setTab('history')"
                        :class="activeTab === 'history' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-history mr-2"></i>Price History
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-6">
                <!-- Current Pricing Grid -->
                <div class="grid gap-4">
                    @forelse($current_pricing as $pricing)
                        <div class="rounded-lg border p-6 transition-colors hover:bg-muted/50">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                        <i class="fas fa-gas-pump text-primary"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold capitalize">{{ $pricing->fuel_type }}</h3>
                                        <p class="text-sm text-muted-foreground">{{ $pricing->station_name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold">{{ number_format($pricing->price_per_liter_ugx, 0) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $pricing->currency_code }}/L</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div>
                                    <div class="text-xl font-bold text-muted-foreground">{{ number_format($pricing->avg_cost_per_liter ?? 0, 0) }}</div>
                                    <div class="text-xs text-muted-foreground">Cost/L</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-green-600">{{ number_format($pricing->margin_per_liter ?? 0, 0) }}</div>
                                    <div class="text-xs text-muted-foreground">Margin/L</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-blue-600">{{ number_format($pricing->margin_percentage ?? 0, 1) }}%</div>
                                    <div class="text-xs text-muted-foreground">Margin %</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-purple-600">{{ number_format($pricing->current_stock_liters ?? 0, 0) }}</div>
                                    <div class="text-xs text-muted-foreground">Stock (L)</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-muted-foreground">
                            <i class="fas fa-tags text-4xl mb-4"></i>
                            <p>No pricing data available</p>
                        </div>
                    @endforelse
                </div>

                <!-- Sales Performance Table -->
                @if($sales_performance->count() > 0)
                    <div class="rounded-lg border">
                        <div class="border-b border-border p-4">
                            <h3 class="text-lg font-semibold">Sales Performance (Last 30 Days)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b border-border bg-muted/50">
                                    <tr>
                                        <th class="text-left py-3 px-4 font-medium">Fuel Type</th>
                                        <th class="text-right py-3 px-4 font-medium">Sales (UGX)</th>
                                        <th class="text-right py-3 px-4 font-medium">Profit (UGX)</th>
                                        <th class="text-right py-3 px-4 font-medium">Margin %</th>
                                        <th class="text-right py-3 px-4 font-medium">Volume (L)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sales_performance as $sale)
                                        <tr class="border-b border-border/50">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-3 h-3 rounded-full bg-primary"></div>
                                                    <span class="capitalize font-medium">{{ $sale->fuel_type }}</span>
                                                </div>
                                            </td>
                                            <td class="text-right py-3 px-4 font-mono">{{ number_format($sale->total_sales, 0) }}</td>
                                            <td class="text-right py-3 px-4 font-mono text-green-600">{{ number_format($sale->gross_profit, 0) }}</td>
                                            <td class="text-right py-3 px-4 font-mono">{{ number_format($sale->avg_margin, 1) }}%</td>
                                            <td class="text-right py-3 px-4 font-mono">{{ number_format($sale->total_volume, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Profit Analysis Tab -->
            <div x-show="activeTab === 'profit'">
                <div x-show="loading" class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-2xl text-primary mb-4"></i>
                    <p class="text-muted-foreground">Loading profit analysis...</p>
                </div>

                <div x-show="!loading && ajaxData.length === 0" class="text-center py-12 text-muted-foreground">
                    <i class="fas fa-chart-line text-4xl mb-4"></i>
                    <p>No profit data available</p>
                </div>

                <div x-show="!loading && ajaxData.length > 0" class="space-y-4">
                    <template x-for="item in ajaxData" :key="item.reconciliation_date + item.fuel_type">
                        <div class="rounded-lg border p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-semibold capitalize" x-text="item.fuel_type"></span>
                                    <span class="text-muted-foreground ml-2" x-text="item.reconciliation_date"></span>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold" x-text="'UGX ' + Number(item.gross_profit_ugx || 0).toLocaleString()"></div>
                                    <div class="text-sm text-muted-foreground" x-text="Number(item.profit_margin_percentage || 0).toFixed(1) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Price History Tab -->
            <div x-show="activeTab === 'history'">
                <div x-show="loading" class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-2xl text-primary mb-4"></i>
                    <p class="text-muted-foreground">Loading price history...</p>
                </div>

                <div x-show="!loading && ajaxData.length === 0" class="text-center py-12 text-muted-foreground">
                    <i class="fas fa-history text-4xl mb-4"></i>
                    <p>No price history available</p>
                </div>

                <div x-show="!loading && ajaxData.length > 0" class="space-y-4">
                    <template x-for="item in ajaxData" :key="item.id">
                        <div class="rounded-lg border p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-semibold capitalize" x-text="item.fuel_type"></span>
                                    <div class="text-sm text-muted-foreground" x-text="item.station_name"></div>
                                    <div class="text-xs text-muted-foreground" x-text="new Date(item.created_at).toLocaleDateString()"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm">
                                        <span x-text="Number(item.old_price_ugx || 0).toLocaleString()"></span>
                                        <i class="fas fa-arrow-right mx-2"></i>
                                        <span x-text="Number(item.new_price_ugx || 0).toLocaleString()"></span>
                                    </div>
                                    <div class="text-xs" :class="Number(item.price_change_percentage || 0) >= 0 ? 'text-green-600' : 'text-red-600'" x-text="Number(item.price_change_percentage || 0).toFixed(1) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function pricingAnalysis() {
    return {
        selectedStation: '{{ $station_id ?? "" }}',
        activeTab: 'overview',
        ajaxData: [],
        loading: false,

        async loadData(endpoint) {
            this.loading = true;
            try {
                const response = await fetch(`/pricing/${endpoint}?station_id=${this.selectedStation}&days=30`);
                if (!response.ok) {
                    throw new Error('Failed to load data');
                }
                this.ajaxData = await response.json();
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
                this.ajaxData = [];
            }
            this.loading = false;
        },

        setTab(tab) {
            this.activeTab = tab;
            if (tab === 'profit') {
                this.loadData('profit-analysis');
            } else if (tab === 'history') {
                this.loadData('price-history');
            }
        },

        changeStation() {
            if (this.selectedStation) {
                window.location.href = `/pricing?station_id=${this.selectedStation}`;
            }
        }
    }
}
</script>
@endpush
