@extends('layouts.app')

@section('title', 'Meter Details - ' . $meter->meter_number)

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div class="flex items-center space-x-4">
        <div class="p-3 bg-slate-100 rounded-xl">
            <i class="fas fa-tachometer-alt text-slate-700 w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $meter->meter_number }}</h1>
            <p class="text-slate-600 mt-1">{{ $meter->station_name }} • {{ $meter->tank_number }} ({{ ucfirst($meter->fuel_type) }})</p>
        </div>
    </div>
    <div class="flex items-center space-x-3">
        <a href="{{ route('meters.edit', $meter->id) }}"
           class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg transition-all duration-200 shadow-sm">
            <i class="fas fa-edit w-4 h-4 mr-2"></i>
            Edit Meter
        </a>
        <a href="{{ route('meters.index', ['station_id' => $meter->station_id]) }}"
           class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-all duration-200">
            <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
            Back to Meters
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="meterDetailsManager" x-init="initializeData(@js([
    'meterId' => $meter->id,
    'isActive' => $meter->is_active,
    'hasReadings' => $meter->total_readings > 0
]))" class="space-y-6">

    <!-- Status & Quick Stats Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Status -->
                <div class="flex items-center space-x-4">
                    <div class="p-3 rounded-xl" :class="isActive ? 'bg-green-100' : 'bg-red-100'">
                        <i class="fas fa-power-off w-5 h-5" :class="isActive ? 'text-green-600' : 'text-red-600'"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-600">Status</div>
                        <div class="text-lg font-bold" :class="isActive ? 'text-green-600' : 'text-red-600'">
                            {{ $meter->is_active ? 'Active' : 'Inactive' }}
                        </div>
                    </div>
                </div>

                <!-- Total Readings -->
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <i class="fas fa-list-ol text-blue-600 w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-600">Total Readings</div>
                        <div class="text-lg font-bold text-slate-900">{{ number_format($meter->total_readings) }}</div>
                    </div>
                </div>

                <!-- Current Reading -->
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <i class="fas fa-gauge text-purple-600 w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-600">Current Reading</div>
                        <div class="text-lg font-bold text-slate-900">{{ number_format($meter->current_reading_liters, 3) }}L</div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-orange-100 rounded-xl">
                        <i class="fas fa-clock text-orange-600 w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-600">30-Day Readings</div>
                        <div class="text-lg font-bold text-slate-900">{{ $meter->readings_30days }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-b border-slate-200">
            <nav class="flex space-x-0" aria-label="Tabs">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-info-circle w-4 h-4 mr-2"></i>
                    Overview
                </button>

                <button @click="activeTab = 'readings'"
                        :class="activeTab === 'readings' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-list w-4 h-4 mr-2"></i>
                    Recent Readings
                    @if($recent_readings->count() > 0)
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-slate-900 text-white rounded-full">{{ $recent_readings->count() }}</span>
                    @endif
                </button>

                <button @click="activeTab = 'statistics'"
                        :class="activeTab === 'statistics' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-chart-line w-4 h-4 mr-2"></i>
                    Statistics
                </button>

                <button @click="activeTab = 'actions'"
                        :class="activeTab === 'actions' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-cog w-4 h-4 mr-2"></i>
                    Actions
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Meter Information -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Meter Information</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Meter Number</span>
                                    <span class="text-sm font-bold text-slate-900">{{ $meter->meter_number }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Current Reading</span>
                                    <span class="text-sm font-bold text-slate-900">{{ number_format($meter->current_reading_liters, 3) }} L</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Status</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $meter->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        <div class="w-1.5 h-1.5 rounded-full {{ $meter->is_active ? 'bg-green-600' : 'bg-red-600' }} mr-1"></div>
                                        {{ $meter->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Created</span>
                                    <span class="text-sm text-slate-900">{{ \Carbon\Carbon::parse($meter->created_at)->format('M j, Y g:i A') }}</span>
                                </div>
                                @if($meter->last_reading_date)
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-sm font-medium text-slate-600">Last Reading</span>
                                    <span class="text-sm text-slate-900">{{ \Carbon\Carbon::parse($meter->last_reading_date)->format('M j, Y') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Tank & Station Information -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Tank & Station Details</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Station</span>
                                    <span class="text-sm font-bold text-slate-900">{{ $meter->station_name }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Location</span>
                                    <span class="text-sm text-slate-900">{{ $meter->station_location }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Tank Number</span>
                                    <span class="text-sm font-bold text-slate-900">{{ $meter->tank_number }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Fuel Type</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $meter->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $meter->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $meter->fuel_type === 'kerosene' ? 'bg-orange-100 text-orange-800' : '' }}">
                                        {{ ucfirst($meter->fuel_type) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Tank Capacity</span>
                                    <span class="text-sm text-slate-900">{{ number_format($meter->capacity_liters) }} L</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-sm font-medium text-slate-600">Current Stock</span>
                                    <span class="text-sm font-bold text-slate-900">{{ number_format($meter->current_volume_liters, 3) }} L</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Readings Tab -->
            <div x-show="activeTab === 'readings'" class="space-y-6">
                @if($recent_readings->count() > 0)
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Recent Meter Readings</h3>
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Opening</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Closing</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Dispensed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @foreach($recent_readings as $reading)
                                <tr class="hover:bg-slate-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                        {{ \Carbon\Carbon::parse($reading->reading_date)->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                        {{ number_format($reading->opening_reading_liters, 3) }}L
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                        {{ number_format($reading->closing_reading_liters, 3) }}L
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                        {{ number_format($reading->dispensed_liters, 3) }}L
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $reading->first_name }} {{ $reading->last_name }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-4">
                        <i class="fas fa-list text-slate-400 w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">No Readings Yet</h3>
                    <p class="text-slate-600 mb-4">This meter hasn't recorded any readings yet.</p>
                </div>
                @endif
            </div>

            <!-- Statistics Tab -->
            <div x-show="activeTab === 'statistics'" class="space-y-6">
                @if($reading_stats && $reading_stats->total_reading_days > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-calendar-alt text-blue-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Total Reading Days</p>
                                <p class="text-2xl font-bold text-slate-900">{{ $reading_stats->total_reading_days }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-gas-pump text-green-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Total Dispensed</p>
                                <p class="text-2xl font-bold text-slate-900">{{ number_format($reading_stats->total_dispensed, 0) }}L</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-chart-line text-purple-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Daily Average</p>
                                <p class="text-2xl font-bold text-slate-900">{{ number_format($reading_stats->avg_daily_dispensed, 1) }}L</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <i class="fas fa-arrow-up text-orange-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Peak Daily</p>
                                <p class="text-2xl font-bold text-slate-900">{{ number_format($reading_stats->max_daily_dispensed, 0) }}L</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-indigo-100 rounded-lg">
                                <i class="fas fa-calendar-day text-indigo-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">First Reading</p>
                                <p class="text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($reading_stats->first_reading_date)->format('M j, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-teal-100 rounded-lg">
                                <i class="fas fa-calendar-check text-teal-600 w-5 h-5"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Last Reading</p>
                                <p class="text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($reading_stats->last_reading_date)->format('M j, Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-4">
                        <i class="fas fa-chart-line text-slate-400 w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-2">No Statistics Available</h3>
                    <p class="text-slate-600">Statistics will appear once readings are recorded for this meter.</p>
                </div>
                @endif
            </div>

            <!-- Actions Tab -->
            <div x-show="activeTab === 'actions'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Primary Actions -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-slate-900">Primary Actions</h3>

                        <a href="{{ route('meters.edit', $meter->id) }}"
                           class="flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors duration-200 border border-slate-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-slate-900 rounded-lg">
                                    <i class="fas fa-edit text-white w-5 h-5"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="font-medium text-slate-900">Edit Meter</h4>
                                    <p class="text-sm text-slate-600">Update meter details and settings</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-400 w-4 h-4"></i>
                        </a>

                        <button @click="toggleMeterStatus"
                                :disabled="isUpdating"
                                class="flex items-center justify-between w-full p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors duration-200 border border-slate-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-lg" :class="isActive ? 'bg-red-100' : 'bg-green-100'">
                                    <i class="fas fa-power-off w-5 h-5" :class="isActive ? 'text-red-600' : 'text-green-600'"></i>
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="font-medium text-slate-900" x-text="isActive ? 'Deactivate Meter' : 'Activate Meter'"></h4>
                                    <p class="text-sm text-slate-600" x-text="isActive ? 'Temporarily disable this meter' : 'Enable this meter for readings'"></p>
                                </div>
                            </div>
                            <div x-show="!isUpdating">
                                <i class="fas fa-chevron-right text-slate-400 w-4 h-4"></i>
                            </div>
                            <div x-show="isUpdating" class="animate-spin rounded-full h-4 w-4 border-b-2 border-slate-900"></div>
                        </button>
                    </div>

                    <!-- Danger Zone -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-red-600">Danger Zone</h3>

                        <button @click="confirmDelete"
                                :disabled="hasReadings"
                                :class="hasReadings ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-50'"
                                class="flex items-center justify-between w-full p-4 bg-red-50 rounded-xl transition-colors duration-200 border border-red-200">
                            <div class="flex items-center">
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <i class="fas fa-trash text-red-600 w-5 h-5"></i>
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="font-medium text-red-900">Delete Meter</h4>
                                    <p class="text-sm text-red-600" x-text="hasReadings ? 'Cannot delete - has readings' : 'Permanently remove this meter'"></p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-red-400 w-4 h-4" x-show="!hasReadings"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function meterDetailsManager() {
    return {
        activeTab: 'overview',
        meterId: null,
        isActive: true,
        hasReadings: false,
        isUpdating: false,

        initializeData(config) {
            this.meterId = config.meterId;
            this.isActive = Boolean(config.isActive);
            this.hasReadings = Boolean(config.hasReadings);
        },

        async toggleMeterStatus() {
            if (this.isUpdating) return;

            const action = this.isActive ? 'deactivate' : 'activate';
            const confirmResult = await Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Meter?`,
                text: `Are you sure you want to ${action} this meter?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e293b',
                cancelButtonColor: '#64748b',
                confirmButtonText: `Yes, ${action}`
            });

            if (!confirmResult.isConfirmed) return;

            this.isUpdating = true;

            try {
                const response = await fetch(`/meters/${this.meterId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.isActive = data.new_status;

                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Refresh page to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Failed to update status');
                }

            } catch (error) {
                console.error('Toggle status error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: error.message || 'Failed to update meter status'
                });
            } finally {
                this.isUpdating = false;
            }
        },

        async confirmDelete() {
            if (this.hasReadings) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Delete',
                    text: 'This meter has readings and cannot be deleted. Data integrity must be preserved.'
                });
                return;
            }

            const confirmResult = await Swal.fire({
                title: 'Delete Meter?',
                text: 'This action cannot be undone. The meter will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete permanently'
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const response = await fetch(`/meters/${this.meterId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Meter Deleted',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        window.location.href = '/meters';
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Failed to delete meter');
                }

            } catch (error) {
                console.error('Delete error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Deletion Failed',
                    text: error.message || 'Failed to delete meter'
                });
            }
        }
    }
}
</script>
@endsection
