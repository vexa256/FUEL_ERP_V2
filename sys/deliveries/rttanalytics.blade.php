@extends('layouts.app')

@section('title', 'RTT Analytics Dashboard')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">RTT Analytics Dashboard</h1>
        <p class="text-sm text-gray-600 mt-1">Return-to-Tank operations analytics, history, and performance metrics</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('deliveries.overflow.dashboard') }}"
           class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
            <i class="fas fa-warehouse mr-2"></i>Manage Overflow
        </a>
        <a href="{{ route('deliveries.index') }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Deliveries
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="rttAnalytics()" class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form method="GET" action="{{ route('rtt.analytics') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Station</label>
                    <select name="station_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 bg-white">
                        <option value="">All Stations</option>
                        @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}" {{ $station_id == $station->id ? 'selected' : '' }}>
                                {{ $station->name }} - {{ $station->location }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ $date_from }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ $date_to }}" max="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full py-2 bg-gray-900 text-white rounded-lg font-medium hover:bg-gray-800">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-sync-alt text-blue-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">RTT Operations</p>
                    <p class="text-md font-bold text-blue-600">{{ number_format($rtt_summary['total_operations']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-gas-pump text-green-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Volume</p>
                    <p class="text-md font-bold text-green-600">{{ number_format($rtt_summary['total_volume'], 0) }}L</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-money-bill-wave text-purple-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Value</p>
                    <p class="text-md font-bold text-purple-600">{{ number_format($rtt_summary['total_value'], 0) }}</p>
                    <p class="text-xs text-gray-500">UGX</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-orange-50 rounded-lg">
                    <i class="fas fa-chart-line text-orange-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Avg Volume</p>
                    <p class="text-md font-bold text-orange-600">{{ number_format($rtt_summary['avg_volume'], 1) }}L</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-50 rounded-lg">
                    <i class="fas fa-oil-can text-indigo-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Tanks Involved</p>
                    <p class="text-md font-bold text-indigo-600">{{ $rtt_summary['tanks_involved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-red-50 rounded-lg">
                    <i class="fas fa-calendar-alt text-red-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Days</p>
                    <p class="text-md font-bold text-red-600">{{ $rtt_summary['active_days'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Overflow Processing Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Overflow Processing Status</h3>
            <div id="overflowProcessingChart" style="height: 300px;"></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Impact</h3>
            <div class="space-y-4">
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600">RTT Value Recovered</div>
                    <div class="text-2xl font-bold text-green-900">{{ number_format($financial_impact['total_rtt_value'], 0) }} UGX</div>
                    <div class="text-xs text-green-600">{{ number_format($financial_impact['total_rtt_volume'], 0) }}L processed</div>
                </div>

                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="text-sm text-orange-600">Overflow At Risk</div>
                    <div class="text-2xl font-bold text-orange-900">{{ number_format($financial_impact['total_overflow_value'], 0) }} UGX</div>
                    <div class="text-xs text-orange-600">{{ number_format($financial_impact['total_overflow_volume'], 0) }}L remaining</div>
                </div>

                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600">Processing Efficiency</div>
                    <div class="text-2xl font-bold text-blue-900">{{ number_format($financial_impact['efficiency_ratio'], 1) }}%</div>
                    <div class="text-xs text-blue-600">RTT vs Total Overflow</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly RTT Trends (Last 12 Months)</h3>
        <div id="monthlyTrendsChart" style="height: 400px;"></div>
    </div>

    <!-- Tank Efficiency Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Tank Efficiency Metrics</h3>
            <p class="text-sm text-gray-600 mt-1">RTT performance and current status by tank</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RTT Operations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume Processed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Overflow</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Efficiency</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tank_efficiency as $tank)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">Tank {{ $tank->tank_number }}</div>
                                <div class="text-sm text-gray-500">{{ $tank->station_name }}</div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1
                                    @if($tank->fuel_type === 'petrol') bg-green-100 text-green-800
                                    @elseif($tank->fuel_type === 'diesel') bg-blue-100 text-blue-800
                                    @elseif($tank->fuel_type === 'kerosene') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $tank->fuel_type)) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm text-gray-900">{{ number_format($tank->current_volume_liters, 0) }}L / {{ number_format($tank->capacity_liters, 0) }}L</div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="h-2 rounded-full {{ $tank->fill_percentage > 90 ? 'bg-red-500' : ($tank->fill_percentage > 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                         style="width: {{ min(100, $tank->fill_percentage) }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ number_format($tank->fill_percentage, 1) }}% full</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ number_format($tank->rtt_operations) }} ops</div>
                            <div class="text-xs text-gray-500">of {{ number_format($tank->total_deliveries) }} total</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-blue-600">{{ number_format($tank->rtt_volume, 0) }}L</div>
                            <div class="text-xs text-gray-500">{{ number_format($tank->direct_delivery_volume, 0) }}L direct</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tank->current_overflow > 0)
                                <div class="text-sm font-medium text-orange-600">{{ number_format($tank->current_overflow, 0) }}L</div>
                                <div class="text-xs text-orange-500">{{ $tank->overflow_records }} record(s)</div>
                            @else
                                <span class="text-sm text-gray-400">No overflow</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $total_volume = $tank->rtt_volume + $tank->direct_delivery_volume;
                                $rtt_percentage = $total_volume > 0 ? ($tank->rtt_volume / $total_volume) * 100 : 0;
                            @endphp
                            <div class="text-sm font-medium {{ $rtt_percentage > 20 ? 'text-green-600' : ($rtt_percentage > 10 ? 'text-yellow-600' : 'text-gray-600') }}">
                                {{ number_format($rtt_percentage, 1) }}%
                            </div>
                            <div class="text-xs text-gray-500">RTT ratio</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- RTT Operations History -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">RTT Operations History</h3>
            <p class="text-sm text-gray-600 mt-1">Recent Return-to-Tank operations with details</p>
        </div>

        <div class="overflow-x-auto">
            @if($rtt_operations->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RTT Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank & Fuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume & Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rtt_operations as $operation)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ $operation->delivery_reference }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($operation->delivery_date . ' ' . $operation->delivery_time)->format('M j, Y \a\t g:i A') }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $operation->station_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">Tank {{ $operation->tank_number }}</div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1
                                    @if($operation->fuel_type === 'petrol') bg-green-100 text-green-800
                                    @elseif($operation->fuel_type === 'diesel') bg-blue-100 text-blue-800
                                    @elseif($operation->fuel_type === 'kerosene') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $operation->fuel_type)) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ number_format($operation->volume_liters, 3) }}L</div>
                                <div class="text-sm text-gray-500">{{ number_format($operation->cost_per_liter_ugx, 2) }} UGX/L</div>
                                <div class="text-sm font-medium text-gray-900">{{ number_format($operation->total_cost_ugx, 0) }} UGX</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                @if($operation->source_overflow_ref)
                                    <div class="text-sm text-gray-900">{{ $operation->source_overflow_ref }}</div>
                                    <div class="text-xs text-gray-500">Overflow reference</div>
                                @endif
                                @if($operation->original_supplier)
                                    <div class="text-xs text-gray-500 mt-1">From: {{ $operation->original_supplier }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $operation->first_name }} {{ $operation->last_name }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($operation->created_at)->diffForHumans() }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $rtt_operations->links('pagination::tailwind') }}
            </div>
            @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-sync-alt text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No RTT Operations</h3>
                <p class="text-gray-500">No RTT operations found for the selected period.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function rttAnalytics() {
    return {
        charts: [],

        init() {
            this.$nextTick(() => {
                this.initializeCharts();
            });
        },

        initializeCharts() {
            // Overflow Processing Chart
            const overflowChart = echarts.init(document.getElementById('overflowProcessingChart'));

            overflowChart.setOption({
                title: {
                    text: 'Overflow Processing Status',
                    left: 'center',
                    textStyle: { fontSize: 14 }
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)'
                },
                legend: {
                    bottom: 10,
                    data: ['Fully Processed', 'Partially Processed', 'Unprocessed']
                },
                series: [{
                    name: 'Processing Status',
                    type: 'pie',
                    radius: ['30%', '70%'],
                    data: [
                        {
                            value: {{ $overflow_analytics['fully_processed'] }},
                            name: 'Fully Processed',
                            itemStyle: { color: '#10b981' }
                        },
                        {
                            value: {{ $overflow_analytics['partially_processed'] }},
                            name: 'Partially Processed',
                            itemStyle: { color: '#f59e0b' }
                        },
                        {
                            value: {{ $overflow_analytics['unprocessed'] }},
                            name: 'Unprocessed',
                            itemStyle: { color: '#ef4444' }
                        }
                    ],
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }]
            });

            // Monthly Trends Chart
            const trendsChart = echarts.init(document.getElementById('monthlyTrendsChart'));
            const trendsData = @json($monthly_trends);

            trendsChart.setOption({
                title: {
                    text: 'RTT Operations & Volume Trends',
                    left: 'center',
                    textStyle: { fontSize: 16 }
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'cross' }
                },
                legend: {
                    bottom: 10,
                    data: ['Operations', 'Volume (L)', 'Value (UGX)']
                },
                grid: { top: 60, bottom: 60, left: 60, right: 60 },
                xAxis: {
                    type: 'category',
                    data: trendsData.map(item => item.month_name)
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Operations / Volume',
                        position: 'left'
                    },
                    {
                        type: 'value',
                        name: 'Value (UGX)',
                        position: 'right',
                        axisLabel: {
                            formatter: function(value) {
                                return (value/1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                ],
                series: [
                    {
                        name: 'Operations',
                        type: 'bar',
                        data: trendsData.map(item => item.operations),
                        itemStyle: { color: '#3b82f6' }
                    },
                    {
                        name: 'Volume (L)',
                        type: 'line',
                        data: trendsData.map(item => item.volume),
                        itemStyle: { color: '#10b981' },
                        smooth: true
                    },
                    {
                        name: 'Value (UGX)',
                        type: 'line',
                        yAxisIndex: 1,
                        data: trendsData.map(item => item.value),
                        itemStyle: { color: '#f59e0b' },
                        smooth: true
                    }
                ]
            });

            this.charts = [overflowChart, trendsChart];

            // Handle responsive resize
            window.addEventListener('resize', () => {
                this.charts.forEach(chart => chart.resize());
            });
        }
    }
}
</script>
@endsection
