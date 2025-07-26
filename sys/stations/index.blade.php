@extends('layouts.app')

@section('title', 'Stations Management')

@section('page-header')
<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Stations Management</h1>
        <p class="text-sm text-gray-600 mt-1">Manage fuel stations, locations, and configurations</p>
    </div>
    <a href="{{ route('stations.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors shadow-sm">
        <i class="fas fa-plus w-4 h-4"></i>
        Add Station
    </a>
</div>
@endsection

@section('content')
<div x-data="stationsManager()" class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Stations</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats->total_stations ?? 0 }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-gas-pump text-blue-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Currencies</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats->total_currencies ?? 0 }}</p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-coins text-green-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Timezones</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats->total_timezones ?? 0 }}</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-globe text-purple-600 w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <form method="GET" class="space-y-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <!-- Search -->
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Stations</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search by name or location..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors">
                    </div>
                </div>

                <!-- Currency Filter -->
                <div class="w-full md:w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                    <select name="currency"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors">
                        <option value="">All Currencies</option>
                        @foreach($currencies as $curr)
                            <option value="{{ $curr }}" {{ $currency == $curr ? 'selected' : '' }}>
                                {{ $curr }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Timezone Filter -->
                <div class="w-full md:w-56">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                    <select name="timezone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-colors">
                        <option value="">All Timezones</option>
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" {{ $timezone == $tz ? 'selected' : '' }}>
                                {{ $tz }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <i class="fas fa-filter w-4 h-4 mr-2"></i>
                        Filter
                    </button>
                    <a href="{{ route('stations.index') }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times w-4 h-4 mr-2"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Stations Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($stations as $station)
            <div class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                <!-- Card Header -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $station->name }}</h3>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="fas fa-map-marker-alt w-4 h-4 mr-1"></i>
                                {{ $station->location }}
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <a href="{{ route('stations.show', $station->id) }}"
                               class="p-2 text-gray-400 hover:text-gray-600 rounded-lg transition-colors"
                               title="View Details">
                                <i class="fas fa-eye w-4 h-4"></i>
                            </a>
                            <a href="{{ route('stations.edit', $station->id) }}"
                               class="p-2 text-gray-400 hover:text-blue-600 rounded-lg transition-colors"
                               title="Edit Station">
                                <i class="fas fa-edit w-4 h-4"></i>
                            </a>
                            <button @click="deleteStation({{ $station->id }}, '{{ $station->name }}')"
                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg transition-colors"
                                    title="Delete Station">
                                <i class="fas fa-trash w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-6 space-y-4">
                    <!-- Config Info -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Currency</span>
                        <span class="font-medium text-gray-900">{{ $station->currency_code }}</span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Timezone</span>
                        <span class="font-medium text-gray-900">{{ $station->timezone }}</span>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-2xl font-semibold text-gray-900">{{ $station->total_tanks ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Tanks</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-semibold text-gray-900">{{ $station->active_users ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Active Users</div>
                        </div>
                    </div>

                    <!-- Status Indicators -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            @if($station->today_deliveries > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-truck w-3 h-3 mr-1"></i>
                                    {{ $station->today_deliveries }} Today
                                </span>
                            @endif

                            @if($station->open_notifications > 0)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-triangle w-3 h-3 mr-1"></i>
                                    {{ $station->open_notifications }} Alerts
                                </span>
                            @endif
                        </div>

                        <span class="text-xs text-gray-500">
                            Created {{ \Carbon\Carbon::parse($station->created_at)->format('M j, Y') }}
                        </span>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="px-6 py-3 bg-gray-50 rounded-b-lg">
                    <a href="{{ route('stations.show', $station->id) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-right w-4 h-4"></i>
                        View Details
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-gas-pump text-gray-400 w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No stations found</h3>
                    <p class="text-gray-600 mb-6">
                        @if($search || $currency || $timezone)
                            No stations match your current filters. Try adjusting your search criteria.
                        @else
                            Get started by creating your first fuel station.
                        @endif
                    </p>
                    @if(!$search && !$currency && !$timezone)
                        <a href="{{ route('stations.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            <i class="fas fa-plus w-4 h-4"></i>
                            Add First Station
                        </a>
                    @else
                        <a href="{{ route('stations.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times w-4 h-4"></i>
                            Clear Filters
                        </a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($stations->hasPages())
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            {{ $stations->links() }}
        </div>
    @endif
</div>

<script>
function stationsManager() {
    return {
        init() {
            // Initialize any required state
        },

        async deleteStation(stationId, stationName) {
            const result = await Swal.fire({
                title: 'Delete Station?',
                html: `Are you sure you want to delete <strong>${stationName}</strong>?<br><br>
                       <small class="text-gray-600">This action cannot be undone if the station has existing data.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/stations/${stationId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to delete station');
                }

                if (data.success) {
                    await Swal.fire({
                        title: 'Deleted!',
                        text: data.message || 'Station deleted successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload page to update list
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }

            } catch (error) {
                console.error('Delete station error:', error);

                let errorMessage = 'Failed to delete station';
                let errorDetails = '';

                if (error.message.includes('dependencies')) {
                    errorMessage = 'Cannot Delete Station';
                    errorDetails = 'This station has existing data and cannot be deleted.';
                } else {
                    errorDetails = error.message;
                }

                await Swal.fire({
                    title: errorMessage,
                    text: errorDetails,
                    icon: 'error',
                    confirmButtonColor: '#374151'
                });
            }
        }
    }
}
</script>
@endsection
