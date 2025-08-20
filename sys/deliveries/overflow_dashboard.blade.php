@extends('layouts.app')

@section('title', 'Overflow Management Dashboard')
@section('page-header')
<div class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex-1">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Overflow Management Dashboard</h1>
        <p class="text-sm text-gray-600 mt-1">Manage fuel overflow storage and Return-to-Tank (RTT) operations</p>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        <a href="{{ route('deliveries.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            New Delivery
        </a>
        <a href="{{ route('deliveries.index') }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Deliveries
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="overflowDashboard()" class="space-y-6">
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-orange-50 rounded-lg">
                    <i class="fas fa-warehouse text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Overflow Records</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $overflow_records->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-gas-pump text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Overflow Volume</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($overflow_records->sum('remaining_volume_liters'), 0) }}L</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">RTT Eligible</p>
                    <p class="text-2xl font-bold text-green-600">{{ $overflow_records->where('rtt_eligibility', 'FULL_RTT_ELIGIBLE')->count() + $overflow_records->where('rtt_eligibility', 'PARTIAL_RTT_ELIGIBLE')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Value</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($overflow_records->sum('remaining_value_ugx'), 0) }}</p>
                    <p class="text-xs text-gray-500">UGX</p>
                </div>
            </div>
        </div>
    </div>

    <!-- RTT Operations Guide -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">Return-to-Tank (RTT) Operations Guide</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white/70 rounded-lg p-4 border border-blue-200">
                        <h4 class="font-medium text-blue-800 mb-2">🎯 Purpose</h4>
                        <p class="text-blue-700">RTT operations move overflow fuel back into tanks when space becomes available, optimizing storage efficiency and reducing waste.</p>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-blue-200">
                        <h4 class="font-medium text-blue-800 mb-2">📋 Process</h4>
                        <p class="text-blue-700">Select eligible overflow records, specify return volume (up to available tank space), and execute RTT to create a new delivery record.</p>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-blue-200">
                        <h4 class="font-medium text-blue-800 mb-2">✅ Requirements</h4>
                        <p class="text-blue-700">Tank must have available space, overflow must be quality-approved, and not on manual hold.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Station Filter -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 bg-gray-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-filter text-gray-600"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">Filter Options</h3>
                <p class="text-sm text-gray-600">Filter overflow records by station</p>
            </div>
        </div>

        <form method="GET" action="{{ route('deliveries.overflow.dashboard') }}" class="max-w-md">
            <div class="flex gap-3">
                <select name="station_id"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500 bg-white">
                    <option value="">All Stations</option>
                    @foreach($accessible_stations as $station)
                        <option value="{{ $station->id }}" {{ $station_id == $station->id ? 'selected' : '' }}>
                            {{ $station->name }} - {{ $station->location }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Apply Filter
                </button>
                @if($station_id)
                    <a href="{{ route('deliveries.overflow.dashboard') }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Overflow Records Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Overflow Storage Records</h3>
            <p class="text-sm text-gray-600 mt-1">Showing {{ $overflow_records->count() }} of {{ $overflow_records->total() }} overflow records</p>
        </div>

        <div class="overflow-hidden">
            @if($overflow_records->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overflow Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank & Fuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume & Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RTT Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($overflow_records as $overflow)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ $overflow->delivery_reference }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($overflow->overflow_date . ' ' . $overflow->overflow_time)->format('M j, Y \a\t g:i A') }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $overflow->station_name }}</div>
                                @if($overflow->supplier_name)
                                    <div class="text-xs text-gray-500 mt-1">{{ $overflow->supplier_name }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">Tank {{ $overflow->tank_number }}</div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                                    @if($overflow->fuel_type === 'petrol') bg-green-100 text-green-800
                                    @elseif($overflow->fuel_type === 'diesel') bg-blue-100 text-blue-800
                                    @elseif($overflow->fuel_type === 'kerosene') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $overflow->fuel_type)) }}
                                </span>
                                <div class="text-xs text-gray-500 mt-1">
                                    Space: {{ number_format($overflow->available_space, 0) }}L
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <div class="text-sm font-medium text-gray-900">{{ number_format($overflow->remaining_volume_liters, 3) }}L</div>
                                <div class="text-sm text-gray-500">of {{ number_format($overflow->overflow_volume_liters, 3) }}L</div>
                                <div class="text-sm font-medium text-gray-900">{{ number_format($overflow->remaining_value_ugx, 0) }} UGX</div>
                                <div class="text-xs text-gray-500">{{ number_format($overflow->cost_per_liter_ugx, 2) }} UGX/L</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($overflow->rtt_eligibility === 'FULL_RTT_ELIGIBLE')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Full RTT Available
                                </span>
                                <div class="text-xs text-green-600 mt-1">
                                    Max: {{ number_format($overflow->max_rtt_volume, 3) }}L
                                </div>
                            @elseif($overflow->rtt_eligibility === 'PARTIAL_RTT_ELIGIBLE')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Partial RTT Only
                                </span>
                                <div class="text-xs text-yellow-600 mt-1">
                                    Max: {{ number_format($overflow->max_rtt_volume, 3) }}L
                                </div>
                            @elseif($overflow->rtt_eligibility === 'NO_SPACE')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    No Tank Space
                                </span>
                            @elseif($overflow->rtt_eligibility === 'MANUAL_HOLD')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-hand-paper mr-1"></i>
                                    Manual Hold
                                </span>
                            @elseif($overflow->rtt_eligibility === 'QUALITY_PENDING')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-flask mr-1"></i>
                                    Quality Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-question-circle mr-1"></i>
                                    Not Eligible
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $overflow->priority_level === 'CRITICAL' ? 'bg-red-100 text-red-800' :
                                   ($overflow->priority_level === 'HIGH' ? 'bg-orange-100 text-orange-800' :
                                   ($overflow->priority_level === 'NORMAL' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $overflow->priority_level }}
                            </span>
                            @if($overflow->overflow_date)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ \Carbon\Carbon::parse($overflow->overflow_date)->diffForHumans() }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if(in_array($overflow->rtt_eligibility, ['FULL_RTT_ELIGIBLE', 'PARTIAL_RTT_ELIGIBLE']))
                                <button onclick="initiateRTT({{ $overflow->id }}, {{ $overflow->max_rtt_volume }}, '{{ $overflow->delivery_reference }}')"
                                        class="inline-flex items-center gap-1 px-3 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                    <i class="fas fa-arrow-right"></i>
                                    Process RTT
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">Not Available</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="border-t border-gray-200 px-6 py-3">
                {{ $overflow_records->appends(request()->query())->links('pagination::tailwind') }}
            </div>
            @else
            <!-- Empty State -->
            <div class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-warehouse text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Overflow Records</h3>
                    <p class="text-gray-500 mb-6">No overflow storage records found for the selected filters.</p>
                    <a href="{{ route('deliveries.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <i class="fas fa-plus text-xs"></i>New Delivery
                    </a>
                </div>
            </div>
            @endif
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
                <!-- RTT form will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
function overflowDashboard() {
    return {
        init() {
            // Check for focus parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const focusId = urlParams.get('focus');
            const rttId = urlParams.get('rtt');

            if (rttId) {
                // Auto-open RTT modal if rtt parameter is present
                this.$nextTick(() => {
                    const overflowRow = document.querySelector(`tr[data-overflow-id="${rttId}"]`);
                    if (overflowRow) {
                        overflowRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        overflowRow.classList.add('bg-yellow-100');
                        setTimeout(() => {
                            const rttButton = overflowRow.querySelector('button[onclick*="initiateRTT"]');
                            if (rttButton) {
                                rttButton.click();
                            }
                        }, 500);
                    }
                });
            } else if (focusId) {
                // Highlight focused overflow record
                this.$nextTick(() => {
                    const overflowRow = document.querySelector(`tr[data-overflow-id="${focusId}"]`);
                    if (overflowRow) {
                        overflowRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        overflowRow.classList.add('bg-blue-100');
                        setTimeout(() => {
                            overflowRow.classList.remove('bg-blue-100');
                        }, 3000);
                    }
                });
            }
        }
    }
}

// RTT functionality
function initiateRTT(overflowId, maxVolume, deliveryReference) {
    // Show modal
    document.getElementById('rtt-modal').classList.remove('hidden');
    document.getElementById('rtt-modal').classList.add('flex');

    // Build RTT form
    document.getElementById('rtt-content').innerHTML = `
        <form id="rtt-form" onsubmit="processRTT(event, ${overflowId})">
            <div class="mb-4">
                <div class="mb-2">
                    <span class="text-sm font-medium text-gray-700">Overflow Reference:</span>
                    <span class="text-sm text-gray-900">${deliveryReference}</span>
                </div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Return Volume (L) *</label>
                <input type="number" name="return_volume_liters" step="0.001" min="0.001" max="${maxVolume}"
                       value="${maxVolume}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <p class="text-xs text-gray-500 mt-1">Maximum available: ${maxVolume.toLocaleString()}L</p>
            </div>

            <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800 mb-2">RTT Process</h4>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• Creates a new delivery record in the system</li>
                    <li>• Updates tank volume and FIFO layers</li>
                    <li>• Reduces overflow storage accordingly</li>
                    <li>• Operation cannot be undone</li>
                </ul>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeRTTModal()"
                        class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>Process RTT
                </button>
            </div>
        </form>
    `;
}

function closeRTTModal() {
    document.getElementById('rtt-modal').classList.add('hidden');
    document.getElementById('rtt-modal').classList.remove('flex');
    document.getElementById('rtt-content').innerHTML = '';
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
                title: 'RTT Operation Completed',
                html: `
                    <div class="text-left">
                        <p class="mb-2">${data.message}</p>
                        <div class="bg-green-50 p-3 rounded mt-3">
                            <h4 class="font-medium text-green-800 mb-2">Operation Summary:</h4>
                            <ul class="text-sm text-green-700 space-y-1">
                                <li>• New delivery ID: #${data.delivery_id}</li>
                                <li>• Remaining overflow: ${data.remaining_overflow.toLocaleString()}L</li>
                                <li>• Tank fill: ${data.tank_status.fill_percentage}%</li>
                            </ul>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#059669',
                confirmButtonText: 'View Delivery'
            }).then((result) => {
                if (result.isConfirmed && data.delivery_id) {
                    window.location.href = `/deliveries/${data.delivery_id}`;
                } else {
                    window.location.reload();
                }
            });
        } else {
            throw new Error(data.error || 'RTT operation failed');
        }
    })
    .catch(error => {
        console.error('RTT Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'RTT Operation Failed',
            text: error.message || 'RTT operation failed. Please try again.',
            confirmButtonColor: '#dc2626'
        });
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Auto-focus functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add data attributes for overflow IDs to enable focusing
    const overflowRows = document.querySelectorAll('tbody tr');
    overflowRows.forEach((row, index) => {
        const rttButton = row.querySelector('button[onclick*="initiateRTT"]');
        if (rttButton) {
            const onclickStr = rttButton.getAttribute('onclick');
            const overflowIdMatch = onclickStr.match(/initiateRTT\((\d+),/);
            if (overflowIdMatch) {
                row.setAttribute('data-overflow-id', overflowIdMatch[1]);
            }
        }
    });
});
</script>
@endsection
