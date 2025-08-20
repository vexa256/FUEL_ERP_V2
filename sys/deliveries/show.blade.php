@extends('layouts.app')

@section('title', 'Delivery Details')

@section('page-header')
<div class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex-1">
        <h1 class="text-3xl font-bold text-gray-900">Delivery Details</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $delivery->delivery_reference }} - FIFO Automation & Overflow Management Report</p>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        @if($overflow_records->count() > 0)
            <a href="{{ route('deliveries.overflow.dashboard') }}"
               class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                <i class="fas fa-warehouse mr-2"></i>Manage Overflow
            </a>
        @endif
        <a href="{{ route('deliveries.edit', $delivery->id) }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            <i class="fas fa-edit mr-2"></i>Edit
        </a>
        <a href="{{ route('deliveries.create') }}"
           class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
            <i class="fas fa-plus mr-2"></i>New Delivery
        </a>
        <a href="{{ route('deliveries.index') }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="deliveryReport()" class="space-y-6">
    <!-- Overflow Status Alert (if applicable) -->
    @if($overflow_records->count() > 0)
    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-warehouse text-orange-600 text-lg"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-orange-800 mb-2">Overflow Storage Active</h3>
                <p class="text-orange-700 mb-3">
                    This delivery created overflow storage because it exceeded tank capacity.
                    {{ $overflow_records->sum('remaining_volume_liters') }}L is currently stored as overflow and can be returned to the tank when space becomes available.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white/70 rounded-lg p-3 border border-orange-200">
                        <span class="font-medium text-orange-800">Total Overflow:</span>
                        <span class="text-orange-700">{{ number_format($overflow_records->sum('overflow_volume_liters'), 3) }}L</span>
                    </div>
                    <div class="bg-white/70 rounded-lg p-3 border border-orange-200">
                        <span class="font-medium text-orange-800">Remaining:</span>
                        <span class="text-orange-700">{{ number_format($overflow_records->sum('remaining_volume_liters'), 3) }}L</span>
                    </div>
                    <div class="bg-white/70 rounded-lg p-3 border border-orange-200">
                        <span class="font-medium text-orange-800">Current Space:</span>
                        <span class="text-orange-700">{{ number_format($current_tank_status->available_space, 0) }}L available</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <a href="{{ route('deliveries.overflow.dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700">
                        <i class="fas fa-cogs text-xs"></i>Process RTT Operations
                    </a>
                    @if($current_tank_status->available_space > 0)
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 text-green-700 text-sm rounded-lg">
                            <i class="fas fa-check-circle text-xs"></i>RTT Possible
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 text-sm rounded-lg">
                            <i class="fas fa-times-circle text-xs"></i>Tank Full - RTT Blocked
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- RTT History Alert (if applicable) -->
    @if($rtt_deliveries->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <i class="fas fa-history text-blue-600 text-lg"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">Return-to-Tank Operations</h3>
                <p class="text-blue-700 mb-3">
                    This delivery has associated RTT operations where overflow fuel was returned to the tank.
                </p>
                <div class="space-y-2">
                    @foreach($rtt_deliveries as $rtt)
                    <div class="flex items-center justify-between p-2 bg-white/70 rounded border border-blue-200 text-sm">
                        <div>
                            <span class="font-medium text-blue-800">{{ $rtt->delivery_reference }}</span>
                            <span class="text-blue-600 ml-2">{{ number_format($rtt->volume_liters, 3) }}L returned</span>
                        </div>
                        <div class="text-blue-600">
                            {{ \Carbon\Carbon::parse($rtt->delivery_date . ' ' . $rtt->delivery_time)->format('M j, g:i A') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-gas-pump text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Volume to Tank</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($delivery->volume_liters, 3) }}L</p>
                    @if($overflow_records->count() > 0)
                        <p class="text-xs text-orange-600">+ {{ number_format($overflow_records->sum('overflow_volume_liters'), 0) }}L overflow</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Investment</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($delivery->total_cost_ugx + ($overflow_records->sum('remaining_value_ugx') ?? 0), 0) }}</p>
                    <p class="text-xs text-gray-500">{{ $delivery->currency_code }} (incl. overflow)</p>
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
                <div class="p-3 {{ $overflow_records->count() > 0 ? 'bg-orange-50' : 'bg-indigo-50' }} rounded-lg">
                    <i class="fas {{ $overflow_records->count() > 0 ? 'fa-warehouse text-orange-600' : 'fa-percentage text-indigo-600' }} text-xl"></i>
                </div>
                <div class="ml-4">
                    @if($overflow_records->count() > 0)
                        <p class="text-sm font-medium text-gray-600">Overflow Status</p>
                        <p class="text-2xl font-bold text-orange-600">{{ number_format($overflow_records->sum('remaining_volume_liters'), 0) }}L</p>
                        <p class="text-xs text-orange-600">Awaiting RTT</p>
                    @else
                        <p class="text-sm font-medium text-gray-600">Tank Fill Impact</p>
                        <p class="text-2xl font-bold text-gray-900">+{{ number_format(($delivery->volume_liters / $delivery->capacity_liters) * 100, 1) }}%</p>
                        <p class="text-xs text-gray-500">Capacity increase</p>
                    @endif
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
                                   ($delivery->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' :
                                   ($delivery->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst(str_replace('_', ' ', $delivery->fuel_type)) }}
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

            <!-- Current Tank Status -->
            @if($current_tank_status)
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Tank Status</h3>

                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Current Volume</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($current_tank_status->current_volume_liters, 0) }}L</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Available Space</span>
                        <span class="text-sm font-medium {{ $current_tank_status->available_space > 1000 ? 'text-green-600' : 'text-orange-600' }}">
                            {{ number_format($current_tank_status->available_space, 0) }}L
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Fill Percentage</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($current_tank_status->fill_percentage, 1) }}%</span>
                    </div>

                    <!-- Visual Fill Indicator -->
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Tank Fill</span>
                            <span>{{ number_format($current_tank_status->fill_percentage, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all
                                {{ $current_tank_status->fill_percentage > 90 ? 'bg-red-500' :
                                   ($current_tank_status->fill_percentage > 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min(100, $current_tank_status->fill_percentage) }}%"></div>
                        </div>
                    </div>

                    @if($current_overflow_status && $current_overflow_status->total_overflow_volume > 0)
                    <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="text-sm font-medium text-orange-800 mb-1">Overflow Storage</div>
                        <div class="text-xs text-orange-600">
                            {{ number_format($current_overflow_status->total_overflow_volume, 0) }}L in
                            {{ $current_overflow_status->overflow_count }} record(s)
                        </div>
                        @if($current_overflow_status->oldest_overflow_date)
                        <div class="text-xs text-orange-500 mt-1">
                            Oldest: {{ \Carbon\Carbon::parse($current_overflow_status->oldest_overflow_date)->diffForHumans() }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

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
                        <span class="text-sm text-gray-600">Value</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($fifo_layer->remaining_value_ugx, 0) }} UGX</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $fifo_layer->layer_status === 'ACTIVE' ? 'bg-green-100 text-green-800' :
                               ($fifo_layer->layer_status === 'DEPLETED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ $fifo_layer->layer_status }}
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
            <!-- Overflow Records Table (if applicable) -->
            @if($overflow_records->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Overflow Storage Records</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($overflow_records as $overflow)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $overflow->delivery_reference }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($overflow->overflow_date)->format('M j, Y') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ number_format($overflow->remaining_volume_liters, 3) }}L</div>
                                    <div class="text-xs text-gray-500">of {{ number_format($overflow->overflow_volume_liters, 3) }}L</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ number_format($overflow->remaining_value_ugx, 0) }} UGX</div>
                                    <div class="text-xs text-gray-500">{{ number_format($overflow->cost_per_liter_ugx, 2) }}/L</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $overflow->priority_level === 'CRITICAL' ? 'bg-red-100 text-red-800' :
                                           ($overflow->priority_level === 'HIGH' ? 'bg-orange-100 text-orange-800' :
                                           ($overflow->priority_level === 'NORMAL' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ $overflow->priority_level }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($overflow->manual_hold)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Manual Hold
                                        </span>
                                    @elseif(!$overflow->quality_approved)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Quality Pending
                                        </span>
                                    @elseif($overflow->is_exhausted)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Exhausted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            RTT Ready
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                    @if(!$overflow->is_exhausted && !$overflow->manual_hold && $overflow->quality_approved && $current_tank_status->available_space > 0)
                                        <button onclick="initiateRTT({{ $overflow->id }})"
                                                class="text-orange-600 hover:text-orange-800">
                                            RTT
                                        </button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

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
                            <div class="text-sm text-gray-600">Tank Investment</div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($delivery->total_cost_ugx, 0) }} UGX</div>
                            <div class="text-xs text-gray-500">{{ number_format($delivery->cost_per_liter_ugx, 2) }} UGX/L</div>
                        </div>
                        @if($overflow_records->count() > 0)
                        <div class="bg-orange-50 rounded-lg p-4">
                            <div class="text-sm text-orange-600">Overflow Investment</div>
                            <div class="text-2xl font-bold text-orange-900">{{ number_format($overflow_records->sum('remaining_value_ugx'), 0) }} UGX</div>
                            <div class="text-xs text-orange-500">{{ number_format($overflow_records->sum('remaining_volume_liters'), 0) }}L awaiting RTT</div>
                        </div>
                        @endif
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600">Volume Efficiency</div>
                            <div class="text-2xl font-bold text-gray-900">{{ number_format(($delivery->volume_liters / ($delivery->volume_liters + ($overflow_records->sum('overflow_volume_liters') ?? 0))) * 100, 1) }}%</div>
                            <div class="text-xs text-gray-500">directly to tank</div>
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

<!-- RTT Modal -->
<div id="rtt-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Return to Tank (RTT)</h3>
                <button onclick="closeRTTModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="rtt-content">
                <!-- RTT form will be loaded here -->
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
            // Tank Capacity Chart with Overflow
            const capacityChart = echarts.init(document.getElementById('capacityChart'));
            const deliveredToTank = {{ $delivery->volume_liters }};
            const overflowVolume = {{ $overflow_records->sum('overflow_volume_liters') ?? 0 }};
            const currentVolume = {{ $current_tank_status->current_volume_liters ?? $delivery->current_volume_liters }};
            const capacity = {{ $delivery->capacity_liters }};
            const availableSpace = capacity - currentVolume;

            capacityChart.setOption({
                title: {
                    text: 'Delivery Impact on Tank Capacity',
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
                    data: ['Current Volume', 'Available Space', 'Overflow Storage'],
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
                    max: capacity * 1.2,
                    axisLabel: {
                        formatter: function(value) {
                            return (value/1000).toFixed(0) + 'k';
                        }
                    }
                },
                series: [
                    {
                        name: 'Current Volume',
                        type: 'bar',
                        data: [currentVolume],
                        itemStyle: { color: '#10b981' },
                        stack: 'tank'
                    },
                    {
                        name: 'Available Space',
                        type: 'bar',
                        data: [availableSpace],
                        itemStyle: { color: '#e5e7eb' },
                        stack: 'tank'
                    },
                    @if($overflow_records->count() > 0)
                    {
                        name: 'Overflow Storage',
                        type: 'bar',
                        data: [overflowVolume],
                        itemStyle: { color: '#f97316' },
                        stack: 'overflow'
                    }
                    @endif
                ]
            });

            // Enhanced Cost Breakdown with Overflow
            const costChart = echarts.init(document.getElementById('costBreakdownChart'));
            const tankCost = {{ $delivery->total_cost_ugx }};
            const overflowCost = {{ $overflow_records->sum('remaining_value_ugx') ?? 0 }};

            costChart.setOption({
                title: {
                    text: 'Investment Breakdown',
                    left: 'center',
                    textStyle: { fontSize: 14 }
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} UGX ({d}%)'
                },
                series: [{
                    name: 'Investment',
                    type: 'pie',
                    radius: ['30%', '70%'],
                    data: [
                        {
                            value: tankCost,
                            name: 'Tank Investment',
                            itemStyle: { color: '#3b82f6' }
                        },
                        @if($overflow_records->count() > 0)
                        {
                            value: overflowCost,
                            name: 'Overflow Value',
                            itemStyle: { color: '#f97316' }
                        }
                        @endif
                    ].filter(item => item.value > 0),
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }]
            });

            // Timeline Chart with RTT Operations
            const timelineChart = echarts.init(document.getElementById('timelineChart'));
            const deliveryTime = '{{ $delivery->delivery_date }}T{{ $delivery->delivery_time }}';
            const createdTime = '{{ $delivery->created_at }}';

            let timelineData = [
                [deliveryTime, 'Delivery', deliveredToTank],
                [createdTime, 'System Processing', deliveredToTank]
            ];

            @if($overflow_records->count() > 0)
            @foreach($overflow_records as $overflow)
            timelineData.push(['{{ $overflow->overflow_date }}T{{ $overflow->overflow_time }}', 'Overflow Created', {{ $overflow->overflow_volume_liters }}]);
            @endforeach
            @endif

            @foreach($rtt_deliveries as $rtt)
            timelineData.push(['{{ $rtt->delivery_date }}T{{ $rtt->delivery_time }}', 'RTT Operation', {{ $rtt->volume_liters }}]);
            @endforeach

            timelineChart.setOption({
                title: {
                    text: 'Processing & Operations Timeline',
                    left: 'center',
                    textStyle: { fontSize: 14 }
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        const time = new Date(params[0].name);
                        return `${params[0].data[1]}<br/>${time.toLocaleDateString()} ${time.toLocaleTimeString()}<br/>Volume: ${params[0].data[2]}L`;
                    }
                },
                grid: { top: 50, bottom: 30, left: 120, right: 30 },
                xAxis: {
                    type: 'time',
                    axisLabel: {
                        formatter: function(value) {
                            return new Date(value).toLocaleDateString();
                        }
                    }
                },
                yAxis: {
                    type: 'category',
                    data: ['Delivery', 'System Processing', 'Overflow Created', 'RTT Operation']
                },
                series: [{
                    name: 'Timeline',
                    type: 'scatter',
                    symbolSize: function(data) {
                        return Math.max(8, Math.min(20, data[2] / 100));
                    },
                    data: timelineData,
                    itemStyle: {
                        color: function(params) {
                            const eventType = params.data[1];
                            if (eventType === 'Delivery') return '#10b981';
                            if (eventType === 'System Processing') return '#3b82f6';
                            if (eventType === 'Overflow Created') return '#f97316';
                            if (eventType === 'RTT Operation') return '#8b5cf6';
                            return '#6b7280';
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

// RTT functionality
function initiateRTT(overflowId) {
    document.getElementById('rtt-modal').classList.remove('hidden');
    document.getElementById('rtt-modal').classList.add('flex');

    document.getElementById('rtt-content').innerHTML = `
        <form id="rtt-form" onsubmit="processRTT(event, ${overflowId})">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Return Volume (L)</label>
                <input type="number" name="return_volume_liters" step="0.001" min="0.001"
                       max="${Math.min({{ $current_tank_status->available_space ?? 0 }}, 999999)}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <p class="text-xs text-gray-500 mt-1">Max: ${Math.min({{ $current_tank_status->available_space ?? 0 }}, 999999).toLocaleString()}L</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeRTTModal()"
                        class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">
                    Process RTT
                </button>
            </div>
        </form>
    `;
}

function closeRTTModal() {
    document.getElementById('rtt-modal').classList.add('hidden');
    document.getElementById('rtt-modal').classList.remove('flex');
}

function processRTT(event, overflowId) {
    event.preventDefault();

    const formData = new FormData(event.target);
    formData.append('overflow_id', overflowId);
    formData.append('_token', '{{ csrf_token() }}');

    // Disable submit button
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

    fetch('{{ route('deliveries.overflow.rtt') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'RTT Completed',
                text: data.message,
                timer: 3000
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.error || 'RTT operation failed');
        }
    })
    .catch(error => {
        console.error('RTT Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'RTT Failed',
            text: error.message || 'RTT operation failed. Please try again.'
        });
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>
@endsection
