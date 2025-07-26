@extends('layouts.app')

@section('title', 'Deliveries Management')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Delivery Management</h1>
        <p class="text-sm text-gray-600 mt-1">Monitor and manage fuel deliveries with FIFO automation</p>
    </div>
    <div class="flex items-center gap-3">
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
                    <p class="text-sm font-medium text-gray-600">Avg Cost/L</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->avg_cost_per_liter ?? 0, 0) }}</p>
                </div>
                <div class="h-12 w-12 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calculator text-orange-600"></i>
                </div>
            </div>
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
                    <div class="text-sm text-gray-600">
                        Showing {{ $deliveries->count() }} of {{ $deliveries->total() }} deliveries
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="overflow-hidden">
            @if($deliveries->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank & Fuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume & Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($deliveries as $delivery)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ $delivery->delivery_reference }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($delivery->delivery_date . ' ' . $delivery->delivery_time)->format('M j, Y \a\t g:i A') }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $delivery->station_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">Tank {{ $delivery->tank_number }}</div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                                    @if($delivery->fuel_type === 'petrol') bg-green-100 text-green-800
                                    @elseif($delivery->fuel_type === 'diesel') bg-blue-100 text-blue-800
                                    @elseif($delivery->fuel_type === 'kerosene') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($delivery->fuel_type) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ number_format($delivery->volume_liters, 3) }}L</div>
                                <div class="text-sm text-gray-500">{{ number_format($delivery->cost_per_liter_ugx, 2) }} UGX/L</div>
                                <div class="text-sm font-medium text-gray-900">{{ number_format($delivery->total_cost_ugx, 2) }} UGX</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm text-gray-900">{{ $delivery->supplier_name ?: 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ $delivery->invoice_number ?: 'No invoice' }}</div>
                                <div class="text-xs text-gray-400">By: {{ $delivery->first_name }} {{ $delivery->last_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('deliveries.show', $delivery->id) }}"
                               class="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 transition-colors">
                                <i class="fas fa-eye text-xs"></i>View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Laravel Pagination -->
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $deliveries->links('pagination::tailwind') }}
            </div>
            @else
            <!-- Empty State -->
            <div class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-truck text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No deliveries found</h3>
                    <p class="text-gray-500 mb-6">No deliveries match your current filters.</p>
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

<script>
function deliveryManager() {
    return {
        init() {
            this.validateDateInputs();
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
        }
    }
}

// Station selection handler
function applyStationFilter() {
    const selector = document.getElementById('station-selector');
    const selectedValue = selector.value;

    // Build URL directly
    let newUrl = '/deliveries';

    // Add station_id if selected
    if (selectedValue) {
        newUrl += '?station_id=' + selectedValue;
    }

    // Navigate to new URL
    window.location.href = newUrl;
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
