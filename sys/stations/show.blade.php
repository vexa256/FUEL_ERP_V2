@extends('layouts.app')

@section('title', $station->name ?? 'Station Details')

@section('breadcrumb')
<a href="{{ route('stations.index') }}" class="text-muted-foreground hover:text-primary transition-colors">
    Stations
</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">{{ $station->name ?? 'Station' }}</span>
@endsection

@section('page-header')
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-start gap-4">
        <div class="p-3 bg-blue-50 rounded-lg">
            <i class="fas fa-gas-pump text-blue-600 w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $station->name }}</h1>
            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                <span class="flex items-center gap-1">
                    <i class="fas fa-map-marker-alt w-4 h-4"></i>
                    {{ $station->location }}
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-coins w-4 h-4"></i>
                    {{ $station->currency_code }}
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-clock w-4 h-4"></i>
                    {{ $station->timezone }}
                </span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stations.edit', $station->id) }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-edit w-4 h-4"></i>
            Edit Station
        </a>
        <a href="{{ route('stations.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-arrow-left w-4 h-4"></i>
            Back to Stations
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="stationDashboard({{ $station->id }})" class="space-y-6">
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Tanks</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $station->total_tanks ?? 0 }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-database text-blue-600 w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Users</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $station->active_users ?? 0 }}</p>
                    <p class="text-xs text-gray-500">of {{ $station->total_users ?? 0 }} total</p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-users text-green-600 w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Meters</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $station->active_meters ?? 0 }}</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-tachometer-alt text-purple-600 w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Open Alerts</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $station->open_notifications ?? 0 }}</p>
                </div>
                <div class="p-3 {{ ($station->open_notifications ?? 0) > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded-lg">
                    <i class="fas fa-exclamation-triangle {{ ($station->open_notifications ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }} w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-chart-line w-4 h-4 mr-2"></i>
                    Overview
                </button>
                <button @click="activeTab = 'tanks'"
                        :class="activeTab === 'tanks' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-database w-4 h-4 mr-2"></i>
                    Tank Summary
                </button>
                <button @click="activeTab = 'activity'"
                        :class="activeTab === 'activity' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-history w-4 h-4 mr-2"></i>
                    Recent Activity
                </button>
                <button @click="activeTab = 'pricing'"
                        :class="activeTab === 'pricing' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-tags w-4 h-4 mr-2"></i>
                    Current Pricing
                </button>
                <button @click="activeTab = 'notifications'"
                        :class="activeTab === 'notifications' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-bell w-4 h-4 mr-2"></i>
                    Notifications
                    @if(($station->open_notifications ?? 0) > 0)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ $station->open_notifications }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Tank Capacity Chart -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tank Capacity Overview</h3>
                        <div id="tankCapacityChart" style="height: 300px;"></div>
                    </div>

                    <!-- Station Information -->
                    <div class="space-y-6">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Station Information</h3>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-600">Created</dt>
                                    <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($station->created_at)->format('M j, Y g:i A') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-600">Last Updated</dt>
                                    <dd class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($station->updated_at)->format('M j, Y g:i A') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-600">Active Prices</dt>
                                    <dd class="text-sm text-gray-900">{{ $station->active_prices ?? 0 }} fuel types</dd>
                                </div>
                            </dl>
                        </div>

                        @if($tanks_summary->isNotEmpty())
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                            <div class="space-y-3">
                                @foreach($tanks_summary as $summary)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 capitalize">{{ $summary->fuel_type }}</span>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900">{{ number_format($summary->avg_fill_percentage, 1) }}%</div>
                                        <div class="text-xs text-gray-500">{{ $summary->tank_count }} tanks</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tanks Tab -->
            <div x-show="activeTab === 'tanks'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                @if($tanks_summary->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fuel Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank Count</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Capacity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Volume</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fill Level</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($tanks_summary as $summary)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-3 {{ $summary->fuel_type === 'petrol' ? 'bg-green-500' : ($summary->fuel_type === 'diesel' ? 'bg-blue-500' : 'bg-orange-500') }}"></div>
                                        <span class="text-sm font-medium text-gray-900 capitalize">{{ $summary->fuel_type }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $summary->tank_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($summary->total_capacity, 0) }}L</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($summary->total_current_volume, 0) }}L</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                            <div class="h-2 rounded-full {{ $summary->avg_fill_percentage > 70 ? 'bg-green-500' : ($summary->avg_fill_percentage > 30 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                 style="width: {{ min(100, $summary->avg_fill_percentage) }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($summary->avg_fill_percentage, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-database text-gray-400 w-12 h-12 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No tanks configured</h3>
                    <p class="text-gray-600">Add tanks to this station to see capacity information.</p>
                </div>
                @endif
            </div>

            <!-- Activity Tab -->
            <div x-show="activeTab === 'activity'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                @if($recent_activity->isNotEmpty())
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity (Last 30 Days)</h3>
                        <span class="text-sm text-gray-500">{{ $recent_activity->count() }} records</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fuel Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales ({{ $station->currency_code }})</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume (L)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Variance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recent_activity as $activity)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($activity->reconciliation_date)->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize {{ $activity->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' : ($activity->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800') }}">
                                            {{ $activity->fuel_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($activity->daily_sales ?? 0, 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($activity->daily_volume ?? 0, 1) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm {{ abs($activity->avg_variance ?? 0) > 2 ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                            {{ number_format($activity->avg_variance ?? 0, 2) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-history text-gray-400 w-12 h-12 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No recent activity</h3>
                    <p class="text-gray-600">Station activity will appear here once operations begin.</p>
                </div>
                @endif
            </div>

            <!-- Pricing Tab -->
            <div x-show="activeTab === 'pricing'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                @if($current_prices->isNotEmpty())
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Current Selling Prices</h3>
                        <span class="text-sm text-gray-500">{{ $current_prices->count() }} active prices</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($current_prices as $price)
                        <div class="bg-gray-50 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-semibold text-gray-900 capitalize">{{ $price->fuel_type }}</h4>
                                <div class="w-3 h-3 rounded-full {{ $price->fuel_type === 'petrol' ? 'bg-green-500' : ($price->fuel_type === 'diesel' ? 'bg-blue-500' : 'bg-orange-500') }}"></div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Price per Liter</span>
                                    <span class="text-lg font-semibold text-gray-900">{{ number_format($price->price_per_liter_ugx, 0) }} {{ $station->currency_code }}</span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Effective From</span>
                                    <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($price->effective_from_date)->format('M j, Y') }}</span>
                                </div>

                                @if($price->effective_to_date)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Effective To</span>
                                    <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($price->effective_to_date)->format('M j, Y') }}</span>
                                </div>
                                @endif

                                <div class="flex justify-between pt-2 border-t border-gray-200">
                                    <span class="text-sm text-gray-600">Set By</span>
                                    <span class="text-sm text-gray-900">{{ $price->first_name }} {{ $price->last_name }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-tags text-gray-400 w-12 h-12 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No prices configured</h3>
                    <p class="text-gray-600">Set up selling prices for fuel types to begin operations.</p>
                </div>
                @endif
            </div>

            <!-- Notifications Tab -->
            <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                @if($recent_notifications->isNotEmpty())
                <div class="space-y-4">
                    @foreach($recent_notifications as $notification)
                    <div class="border border-gray-200 rounded-lg p-4 {{ $notification->status === 'open' ? 'bg-red-50 border-red-200' : 'bg-gray-50' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div class="p-2 rounded-lg {{ $notification->severity === 'critical' ? 'bg-red-100 text-red-600' : ($notification->severity === 'high' ? 'bg-orange-100 text-orange-600' : ($notification->severity === 'medium' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600')) }}">
                                    <i class="fas fa-{{ $notification->notification_type === 'volume_variance' ? 'exclamation-triangle' : ($notification->notification_type === 'low_stock' ? 'gas-pump' : 'info-circle') }} w-4 h-4"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $notification->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                        <span>{{ \Carbon\Carbon::parse($notification->created_at)->format('M j, Y g:i A') }}</span>
                                        <span class="capitalize">{{ $notification->notification_type }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $notification->severity === 'critical' ? 'bg-red-100 text-red-800' : ($notification->severity === 'high' ? 'bg-orange-100 text-orange-800' : ($notification->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                                            {{ $notification->severity }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($notification->status === 'open')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Open
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($notification->status) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-bell text-gray-400 w-12 h-12 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                    <p class="text-gray-600">System notifications will appear here when they occur.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function stationDashboard(stationId) {
    return {
        activeTab: 'overview',
        stationId: stationId,
        tankChart: null,

        init() {
            this.$nextTick(() => {
                this.initTankChart();
            });
        },

        initTankChart() {
            const chartDom = document.getElementById('tankCapacityChart');
            if (!chartDom) return;

            this.tankChart = echarts.init(chartDom);

            const tankData = @json($tanks_summary);

            if (tankData.length === 0) {
                this.tankChart.setOption({
                    title: {
                        text: 'No tank data available',
                        left: 'center',
                        top: 'center',
                        textStyle: {
                            color: '#9ca3af'
                        }
                    }
                });
                return;
            }

            const option = {
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c}L ({d}%)'
                },
                legend: {
                    bottom: '0%',
                    left: 'center'
                },
                series: [
                    {
                        name: 'Tank Capacity',
                        type: 'pie',
                        radius: ['40%', '70%'],
                        center: ['50%', '45%'],
                        avoidLabelOverlap: false,
                        itemStyle: {
                            borderRadius: 4,
                            borderColor: '#fff',
                            borderWidth: 2
                        },
                        label: {
                            show: false,
                            position: 'center'
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: 20,
                                fontWeight: 'bold'
                            }
                        },
                        labelLine: {
                            show: false
                        },
                        data: tankData.map(tank => ({
                            value: parseFloat(tank.total_current_volume),
                            name: tank.fuel_type.charAt(0).toUpperCase() + tank.fuel_type.slice(1),
                            itemStyle: {
                                color: tank.fuel_type === 'petrol' ? '#10b981' :
                                       tank.fuel_type === 'diesel' ? '#3b82f6' : '#f59e0b'
                            }
                        }))
                    }
                ]
            };

            this.tankChart.setOption(option);

            // Handle window resize
            window.addEventListener('resize', () => {
                this.tankChart?.resize();
            });
        }
    }
}
</script>
@endsection
