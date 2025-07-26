@extends('layouts.app')

@section('title', 'Delivery Details')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Delivery Details</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $delivery->delivery_reference }} - FIFO Automation Report</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('deliveries.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
            <i class="fas fa-plus mr-2"></i>New Delivery
        </a>
        <a href="{{ route('deliveries.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="deliveryReport()" class="space-y-6">
    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-gas-pump text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Volume Delivered</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($delivery->volume_liters, 3) }}L</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Cost</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($delivery->total_cost_ugx, 0) }}</p>
                    <p class="text-xs text-gray-500">{{ $delivery->currency_code }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-calculator text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Cost per Liter</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($delivery->cost_per_liter_ugx, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $delivery->currency_code }}/L</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-orange-50 rounded-lg">
                    <i class="fas fa-percentage text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Tank Fill Impact</p>
                    <p class="text-2xl font-bold text-gray-900">+{{ number_format(($delivery->volume_liters / $delivery->capacity_liters) * 100, 1) }}%</p>
                    <p class="text-xs text-gray-500">Capacity increase</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Delivery Information -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Basic Details -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Delivery Information</h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Reference</span>
                        <span class="text-sm font-medium text-gray-900 bg-gray-50 px-2 py-1 rounded">{{ $delivery->delivery_reference }}</span>
                    </div>

                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Date & Time</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($delivery->delivery_date)->format('M j, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($delivery->delivery_time)->format('g:i A') }}</div>
                        </div>
                    </div>

                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Station</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">{{ $delivery->station_name }}</div>
                            <div class="text-xs text-gray-500">{{ $delivery->station_location }}</div>
                        </div>
                    </div>

                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Tank</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">Tank {{ $delivery->tank_number }}</div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $delivery->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' :
                                   ($delivery->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($delivery->fuel_type) }}
                            </span>
                        </div>
                    </div>

                    @if($delivery->supplier_name)
                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Supplier</span>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">{{ $delivery->supplier_name }}</div>
                            @if($delivery->invoice_number)
                            <div class="text-xs text-gray-500">{{ $delivery->invoice_number }}</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="flex justify-between items-start">
                        <span class="text-sm text-gray-600">Recorded by</span>
                        <div class="text-sm font-medium text-gray-900">{{ $delivery->first_name }} {{ $delivery->last_name }}</div>
                    </div>
                </div>
            </div>

            <!-- FIFO Automation Status -->
            @if($fifo_layer)
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">FIFO Automation</h3>

                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                    <span class="text-sm font-medium text-green-800">Layer Created Successfully</span>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Layer Sequence</span>
                        <span class="text-sm font-medium text-gray-900">#{{ $fifo_layer->layer_sequence }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Original Volume</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($fifo_layer->original_volume_liters, 3) }}L</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Remaining</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($fifo_layer->remaining_volume_liters, 3) }}L</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $fifo_layer->is_exhausted ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ $fifo_layer->is_exhausted ? 'Exhausted' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                    <div>
                        <h4 class="text-sm font-medium text-red-800">FIFO Layer Missing</h4>
                        <p class="text-sm text-red-700 mt-1">Database trigger may have failed</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Charts and Analytics -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tank Capacity Analysis -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tank Capacity Analysis</h3>
                <div id="capacityChart" style="height: 300px;"></div>
            </div>

            <!-- Cost Breakdown -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Impact</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="costBreakdownChart" style="height: 250px;"></div>
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Investment per Liter</div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($delivery->cost_per_liter_ugx, 2) }} UGX</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Total Investment</div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($delivery->total_cost_ugx, 0) }} UGX</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Volume Efficiency</div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format(($delivery->volume_liters / $delivery->capacity_liters) * 100, 1) }}%</div>
                            <div class="text-xs text-gray-500">of tank capacity</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Timeline -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Operational Timeline</h3>
                <div id="timelineChart" style="height: 200px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
function deliveryReport() {
    return {
        charts: [],

        init() {
            this.$nextTick(() => {
                this.initializeCharts();
            });
        },

        initializeCharts() {
            // Tank Capacity Chart
            const capacityChart = echarts.init(document.getElementById('capacityChart'));
            const preDelivery = {{ $delivery->current_volume_liters }} - {{ $delivery->volume_liters }};
            const postDelivery = {{ $delivery->current_volume_liters }};
            const capacity = {{ $delivery->capacity_liters }};

            capacityChart.setOption({
                title: {
                    text: 'Before vs After Delivery',
                    left: 'center',
                    textStyle: { fontSize: 16, fontWeight: 'normal' }
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    formatter: function(params) {
                        return params.map(param =>
                            `${param.seriesName}: ${param.value.toLocaleString()}L (${(param.value/capacity*100).toFixed(1)}%)`
                        ).join('<br/>');
                    }
                },
                legend: {
                    data: ['Before Delivery', 'After Delivery', 'Available Capacity'],
                    bottom: 10
                },
                grid: { top: 60, bottom: 60, left: 60, right: 30 },
                xAxis: {
                    type: 'category',
                    data: ['Tank Status']
                },
                yAxis: {
                    type: 'value',
                    name: 'Volume (L)',
                    max: capacity * 1.1,
                    axisLabel: {
                        formatter: function(value) {
                            return (value/1000).toFixed(0) + 'k';
                        }
                    }
                },
                series: [
                    {
                        name: 'Before Delivery',
                        type: 'bar',
                        data: [preDelivery],
                        itemStyle: { color: '#ef4444' },
                        stack: 'total'
                    },
                    {
                        name: 'Delivery Added',
                        type: 'bar',
                        data: [{{ $delivery->volume_liters }}],
                        itemStyle: { color: '#10b981' },
                        stack: 'total'
                    },
                    {
                        name: 'Available Capacity',
                        type: 'bar',
                        data: [capacity - postDelivery],
                        itemStyle: { color: '#e5e7eb' },
                        stack: 'total'
                    }
                ]
            });

            // Cost Breakdown Pie Chart
            const costChart = echarts.init(document.getElementById('costBreakdownChart'));
            costChart.setOption({
                title: {
                    text: 'Cost Analysis',
                    left: 'center',
                    textStyle: { fontSize: 14 }
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} UGX ({d}%)'
                },
                series: [{
                    name: 'Cost Breakdown',
                    type: 'pie',
                    radius: ['30%', '70%'],
                    data: [
                        {
                            value: {{ $delivery->total_cost_ugx }},
                            name: 'Fuel Investment',
                            itemStyle: { color: '#3b82f6' }
                        },
                        {
                            value: {{ ($delivery->capacity_liters - $delivery->current_volume_liters) * $delivery->cost_per_liter_ugx }},
                            name: 'Remaining Capacity Value',
                            itemStyle: { color: '#e5e7eb' }
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

            // Timeline Chart
            const timelineChart = echarts.init(document.getElementById('timelineChart'));
            const deliveryTime = '{{ $delivery->delivery_date }}T{{ $delivery->delivery_time }}';
            const createdTime = '{{ $delivery->created_at }}';

            timelineChart.setOption({
                title: {
                    text: 'Processing Timeline',
                    left: 'center',
                    textStyle: { fontSize: 14 }
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        const time = new Date(params[0].name);
                        return `${params[0].seriesName}<br/>${time.toLocaleDateString()} ${time.toLocaleTimeString()}`;
                    }
                },
                grid: { top: 50, bottom: 30, left: 80, right: 30 },
                xAxis: {
                    type: 'time',
                    axisLabel: {
                        formatter: function(value) {
                            return new Date(value).toLocaleTimeString('en-US', {
                                hour: '2-digit', minute: '2-digit'
                            });
                        }
                    }
                },
                yAxis: {
                    type: 'category',
                    data: ['Delivery', 'System Processing']
                },
                series: [{
                    name: 'Timeline',
                    type: 'scatter',
                    symbolSize: 20,
                    data: [
                        [deliveryTime, 'Delivery', {{ $delivery->volume_liters }}],
                        [createdTime, 'System Processing', {{ $delivery->volume_liters }}]
                    ],
                    itemStyle: {
                        color: function(params) {
                            return params.dataIndex === 0 ? '#10b981' : '#3b82f6';
                        }
                    }
                }]
            });

            this.charts = [capacityChart, costChart, timelineChart];

            // Handle responsive resize
            window.addEventListener('resize', () => {
                this.charts.forEach(chart => chart.resize());
            });
        }
    }
}
</script>
@endsection
