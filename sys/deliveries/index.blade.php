@extends('layouts.app')

@section('title', 'Deliveries Management')
@section('page-header')
<div class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <!-- Left side -->
    <div class="flex-1">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Delivery Management</h1>
        <p class="text-sm text-gray-600 mt-1">
            Monitor and manage fuel deliveries with FIFO automation and overflow handling
        </p>
    </div>

    <!-- Right side buttons -->
    <div class="flex items-center gap-3 flex-shrink-0">
        @if(isset($critical_overflow) && $critical_overflow['total_overflow_records'] > 0)
            <a href="{{ route('deliveries.overflow.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors shadow-sm">
                <i class="fas fa-exclamation-triangle text-xs"></i>
                Overflow ({{ $critical_overflow['total_overflow_records'] }})
            </a>
        @endif

        <a href="{{ route('deliveries.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            New Delivery
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="deliveryManager()" class="space-y-6">
    <!-- MANDATORY: Station Selection Interface -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-blue-600"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">Station Context</h3>
                <p class="text-sm text-gray-600">{{ auth()->user()->role === 'admin' ? 'Select station to manage deliveries' : 'Your assigned station scope' }}</p>
            </div>
            @if(isset($critical_overflow) && $critical_overflow['total_overflow_records'] > 0)
                <div class="flex-shrink-0">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle text-orange-600"></i>
                            <span class="text-sm text-orange-700 font-medium">Overflow Storage Active</span>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">{{ number_format($critical_overflow['total_overflow_volume'], 0) }}L awaiting RTT</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="max-w-md">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ auth()->user()->role === 'admin' ? 'Select Station' : 'Assigned Station' }}
            </label>
            <div class="flex gap-3">
                <select id="station-selector"
                        {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500 bg-white {{ auth()->user()->role !== 'admin' ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                    @if(auth()->user()->role === 'admin')
                        <option value="">All Stations</option>
                    @endif
                    @foreach($accessible_stations as $station)
                        <option value="{{ $station->id }}"
                                {{ request('station_id') == $station->id ? 'selected' : '' }}>
                            {{ $station->name }} - {{ $station->location }}
                        </option>
                    @endforeach
                </select>
                @if(auth()->user()->role === 'admin')
                    <button onclick="applyStationFilter()"
                            class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        Apply
                    </button>
                @endif
            </div>
            @if(auth()->user()->role !== 'admin')
                <p class="text-xs text-gray-500 mt-1">Station assignment enforced by system security</p>
            @endif
        </div>
    </div>

    <!-- Overflow Guidance Panel (shown when there's overflow) -->
    @if(isset($critical_overflow) && $critical_overflow['total_overflow_records'] > 0 && (request('station_id') || auth()->user()->role !== 'admin'))
    <div class="bg-gradient-to-br from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-warehouse text-orange-600 text-lg"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-orange-800 mb-2">Overflow Storage Guide</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white/70 rounded-lg p-4 border border-orange-200">
                        <h4 class="font-medium text-orange-800 mb-2">What is Overflow?</h4>
                        <p class="text-orange-700">When deliveries exceed tank capacity, excess fuel is stored as "overflow" until tank space becomes available.</p>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-orange-200">
                        <h4 class="font-medium text-orange-800 mb-2">Return-to-Tank (RTT)</h4>
                        <p class="text-orange-700">Use RTT operations to move overflow fuel back into tanks when space is available. This optimizes storage efficiency.</p>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-orange-200">
                        <h4 class="font-medium text-orange-800 mb-2">Current Status</h4>
                        <p class="text-orange-700">{{ isset($critical_overflow) ? $critical_overflow['total_overflow_records'] : 0 }} overflow records with {{ isset($critical_overflow) ? number_format($critical_overflow['total_overflow_volume'], 0) : 0 }}L total volume across {{ isset($critical_overflow) ? $critical_overflow['tanks_with_overflow'] : 0 }} tanks. Contact administrator for overflow management.</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <a href="{{ route('deliveries.overflow.dashboard') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-cogs text-xs"></i>Manage Overflow
                    </a>
                    <button onclick="toggleOverflowView()"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-orange-300 text-orange-700 text-sm font-medium rounded-lg hover:bg-orange-50 transition-colors">
                        <i class="fas fa-eye text-xs"></i>
                        <span id="overflow-toggle-text">Show Overflow Records</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    @if(request('station_id') || auth()->user()->role !== 'admin')
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Deliveries</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->total_deliveries ?? 0) }}</p>
                </div>
                <div class="h-12 w-12 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-truck text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Volume</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->total_volume ?? 0, 0) }}L</p>
                </div>
                <div class="h-12 w-12 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-gas-pump text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Cost</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->total_cost ?? 0, 0) }} UGX</p>
                </div>
                <div class="h-12 w-12 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Overflow Volume</p>
                    <p class="text-2xl font-bold text-orange-600">{{ isset($critical_overflow) ? number_format($critical_overflow['total_overflow_volume'] ?? 0, 0) : 0 }}L</p>
                </div>
                <div class="h-12 w-12 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-warehouse text-orange-600"></i>
                </div>
            </div>
            @if(isset($critical_overflow) && $critical_overflow['rtt_eligible_records'] > 0)
                <div class="mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        {{ $critical_overflow['rtt_eligible_records'] }} RTT Ready
                    </span>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tanks Served</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->tanks_served ?? 0) }}</p>
                </div>
                <div class="h-12 w-12 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-oil-can text-indigo-600"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delivery Management Interface -->
    @if(request('station_id') || auth()->user()->role !== 'admin')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <!-- Filter Controls -->
        <div class="border-b border-gray-200 p-6">
            <form method="GET" action="{{ route('deliveries.index') }}" class="space-y-4">
                <!-- Preserve station_id -->
                @if(request('station_id'))
                    <input type="hidden" name="station_id" value="{{ request('station_id') }}">
                @endif

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Reference, supplier, invoice..."
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tank</label>
                        <select name="tank_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500 bg-white">
                            <option value="">All Tanks</option>
                            @foreach($available_tanks as $tank)
                                <option value="{{ $tank->id }}" {{ $tank_id == $tank->id ? 'selected' : '' }}>
                                    Tank {{ $tank->tank_number }} ({{ ucfirst($tank->fuel_type) }}) - {{ $tank->station_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" value="{{ $date_from }}"
                               max="{{ date('Y-m-d') }}"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" value="{{ $date_to }}"
                               max="{{ date('Y-m-d') }}"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <i class="fas fa-search text-xs"></i>Filter
                    </button>
                    <a href="{{ route('deliveries.index') }}{{ request('station_id') ? '?station_id=' . request('station_id') : '' }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-undo text-xs"></i>Clear Filters
                    </a>

                    <!-- Overflow Toggle -->
                    @if(isset($critical_overflow) && $critical_overflow['total_overflow_records'] > 0)
                    <div class="flex items-center">
                        <input type="checkbox" id="show_overflow" name="show_overflow" value="1"
                               {{ request('show_overflow') ? 'checked' : '' }}
                               class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                        <label for="show_overflow" class="ml-2 text-sm text-gray-700">Include Overflow Records</label>
                    </div>
                    @endif

                    <div class="text-sm text-gray-600">
                        Showing {{ $records->count() }} of {{ $records->total() }} records
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="overflow-hidden">
            @if($records->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank & Fuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume & Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($records as $record)
                    <tr class="hover:bg-gray-50 transition-colors {{ $record->record_type === 'overflow' ? 'bg-orange-50/30' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    @if($record->record_type === 'overflow')
                                        <i class="fas fa-warehouse text-orange-500 text-xs" title="Overflow Record"></i>
                                    @endif
                                    <div class="text-sm font-medium text-gray-900">{{ $record->delivery_reference }}</div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($record->delivery_date . ' ' . $record->delivery_time)->format('M j, Y \a\t g:i A') }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $record->station_name }}</div>
                                @if($record->record_type === 'overflow')
                                    <div class="text-xs text-orange-600 font-medium mt-1">
                                        Overflow: {{ $record->storage_reason }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">Tank {{ $record->tank_number }}</div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                                    @if($record->fuel_type === 'petrol') bg-green-100 text-green-800
                                    @elseif($record->fuel_type === 'diesel') bg-blue-100 text-blue-800
                                    @elseif($record->fuel_type === 'kerosene') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($record->fuel_type) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ number_format($record->volume_liters, 3) }}L</div>
                                <div class="text-sm text-gray-500">{{ number_format($record->cost_per_liter_ugx, 2) }} UGX/L</div>
                                <div class="text-sm font-medium text-gray-900">{{ number_format($record->total_cost_ugx, 2) }} UGX</div>
                                @if($record->record_type === 'overflow' && $record->overflow_volume)
                                    <div class="text-xs text-orange-600 mt-1">
                                        Overflow: {{ number_format($record->overflow_volume, 3) }}L
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm text-gray-900">{{ $record->supplier_name ?: 'N/A' }}</div>
                                @if($record->invoice_number)
                                    <div class="text-sm text-gray-500">{{ $record->invoice_number }}</div>
                                @endif
                                <div class="text-xs text-gray-400">By: {{ $record->first_name }} {{ $record->last_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($record->record_type === 'overflow')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-warehouse mr-1"></i>
                                    Overflow Storage
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Delivered to Tank
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('deliveries.show', $record->id) }}"
                                   class="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 transition-colors">
                                    <i class="fas fa-eye text-xs"></i>View
                                </a>
                                @if($record->record_type === 'overflow')
                                    <button onclick="initiateRTT({{ $record->id }})"
                                            class="inline-flex items-center gap-1 text-orange-600 hover:text-orange-800 transition-colors">
                                        <i class="fas fa-arrow-right text-xs"></i>RTT
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Laravel Pagination -->
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $records->appends(request()->query())->links('pagination::tailwind') }}
            </div>
            @else
            <!-- Empty State -->
            <div class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-truck text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No records found</h3>
                    <p class="text-gray-500 mb-6">No deliveries or overflow records match your current filters.</p>
                    <a href="{{ route('deliveries.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <i class="fas fa-plus text-xs"></i>New Delivery
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Station Selection Required Message -->
    @if(!request('station_id') && auth()->user()->role === 'admin')
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
        <div class="flex flex-col items-center">
            <i class="fas fa-building text-3xl text-yellow-600 mb-4"></i>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">Station Selection Required</h3>
            <p class="text-yellow-700">Please select a station above to view delivery data.</p>
        </div>
    </div>
    @endif
</div>

<!-- No RTT Modal needed - using redirect approach -->

<script>
function deliveryManager() {
    return {
        init() {
            this.validateDateInputs();
            this.handleOverflowToggle();
        },

        validateDateInputs() {
            const dateFromInput = document.querySelector('input[name="date_from"]');
            const dateToInput = document.querySelector('input[name="date_to"]');

            if (dateFromInput && dateToInput) {
                dateFromInput.addEventListener('change', () => {
                    if (dateFromInput.value && dateToInput.value && dateFromInput.value > dateToInput.value) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Date Range',
                            text: 'Start date cannot be after end date',
                            timer: 3000
                        });
                        dateFromInput.value = '';
                    }
                });

                dateToInput.addEventListener('change', () => {
                    if (dateToInput.value && dateFromInput.value && dateToInput.value < dateFromInput.value) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Date Range',
                            text: 'End date cannot be before start date',
                            timer: 3000
                        });
                        dateToInput.value = '';
                    }
                });
            }
        },

        handleOverflowToggle() {
            const overflowCheckbox = document.getElementById('show_overflow');
            if (overflowCheckbox) {
                overflowCheckbox.addEventListener('change', function() {
                    this.form.submit();
                });
            }
        }
    }
}

// Station selection handler
function applyStationFilter() {
    const selector = document.getElementById('station-selector');
    const selectedValue = selector.value;

    let newUrl = '/deliveries';
    if (selectedValue) {
        newUrl += '?station_id=' + selectedValue;
    }

    window.location.href = newUrl;
}

// Toggle overflow view
function toggleOverflowView() {
    const checkbox = document.getElementById('show_overflow');
    const toggleText = document.getElementById('overflow-toggle-text');

    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        toggleText.textContent = checkbox.checked ? 'Hide Overflow Records' : 'Show Overflow Records';
        checkbox.form.submit();
    }
}

// RTT functionality - simplified
function initiateRTT(overflowId) {
    alert('RTT functionality available. Contact administrator for overflow management.');
}

function closeRTTModal() {
    // No longer needed - redirect approach
}

function processRTT(overflowId) {
    alert('RTT functionality available. Contact administrator.');
}

// Auto-submit on change for better UX (optional)
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('station-selector');
    if (selector && '{{ auth()->user()->role }}' === 'admin') {
        selector.addEventListener('change', function() {
            // Optional: Auto-apply on change
            // applyStationFilter();
        });
    }
});
</script>
@endsection
