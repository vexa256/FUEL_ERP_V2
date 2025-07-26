@extends('layouts.app')

@section('title', 'Meter Management')

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Meter Management</h1>
        <p class="text-slate-600 mt-2">Monitor and manage fuel dispensing meters across all stations</p>
    </div>
    <a href="{{ route('meters.create') }}" class="inline-flex items-center px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
        <i class="fas fa-plus w-4 h-4 mr-2"></i>
        Add New Meter
    </a>
</div>
@endsection

@section('content')
<div x-data="meterManager({
    totalMeters: {{ $stats->total_meters ?? 0 }},
    activeMeters: {{ $stats->active_meters ?? 0 }},
    inactiveMeters: {{ $stats->inactive_meters ?? 0 }},
    tanksWithMeters: {{ $stats->tanks_with_meters ?? 0 }},
    stationsWithMeters: {{ $stats->stations_with_meters ?? 0 }},
    currentSearch: '{{ $search ?? '' }}',
    currentStationId: '{{ $station_id ?? '' }}',
    currentTankId: '{{ $tank_id ?? '' }}',
    currentStatus: '{{ $status ?? '' }}',
    userRole: '{{ auth()->user()->role }}',
    userStationId: '{{ auth()->user()->station_id ?? '' }}'
})" class="space-y-8">

    <!-- MANDATORY STATION SELECTION INTERFACE -->
    @if(auth()->user()->role === 'admin')
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl border border-slate-700 shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-building w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Station Context</h3>
                    <p class="text-slate-300 text-sm">Select a station to view its meters or view all stations</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <select x-model="stationFilter" @change="onStationChange" class="px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/60 focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all duration-200 min-w-[300px]">
                    <option value="" class="text-slate-900">All Stations (Admin View)</option>
                    @foreach($accessible_stations as $station)
                    <option value="{{ $station->id }}" class="text-slate-900" {{ $station_id == $station->id ? 'selected' : '' }}>
                        {{ $station->name }} - {{ $station->location }}
                    </option>
                    @endforeach
                </select>
                <div class="px-3 py-2 bg-yellow-500/20 border border-yellow-500/30 rounded-lg">
                    <span class="text-yellow-200 text-xs font-semibold">ADMIN</span>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl border border-blue-500 shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-map-marker-alt w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Your Station</h3>
                    <p class="text-blue-100 text-sm">You are viewing meters for your assigned station</p>
                </div>
            </div>
            <div class="text-right">
                @php $user_station = $accessible_stations->first(); @endphp
                @if($user_station)
                <div class="text-lg font-bold">{{ $user_station->name }}</div>
                <div class="text-blue-200 text-sm">{{ $user_station->location }}</div>
                @endif
                <div class="mt-2 px-3 py-1 bg-green-500/20 border border-green-400/30 rounded-lg">
                    <span class="text-green-200 text-xs font-semibold">{{ strtoupper(auth()->user()->role) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Premium Statistics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Total Meters</p>
                    <p class="text-3xl font-bold text-slate-900 mt-1" x-text="totalMeters"></p>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl">
                    <i class="fas fa-tachometer-alt text-blue-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Active Meters</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1" x-text="activeMeters"></p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl">
                    <i class="fas fa-check-circle text-emerald-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Inactive Meters</p>
                    <p class="text-3xl font-bold text-red-600 mt-1" x-text="inactiveMeters"></p>
                </div>
                <div class="p-3 bg-red-50 rounded-xl">
                    <i class="fas fa-times-circle text-red-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Equipped Tanks</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1" x-text="tanksWithMeters"></p>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl">
                    <i class="fas fa-gas-pump text-blue-600 w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Active Stations</p>
                    <p class="text-3xl font-bold text-purple-600 mt-1" x-text="stationsWithMeters"></p>
                </div>
                <div class="p-3 bg-purple-50 rounded-xl">
                    <i class="fas fa-map-marker-alt text-purple-600 w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Filter Wizard -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="border-b border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Filter & Search</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Search Meters</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                        <input type="text"
                               x-model="searchTerm"
                               @input="applyFilters"
                               placeholder="Meter number, tank, station..."
                               class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200">
                    </div>
                </div>

                <!-- Station Filter -->
                @if(auth()->user()->role === 'admin')
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Station</label>
                    <select x-model="stationFilter" @change="onStationChange" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200">
                        <option value="">All Stations</option>
                        @foreach($accessible_stations as $station)
                        <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Station</label>
                    <div class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-600">
                        @php $user_station = $accessible_stations->first(); @endphp
                        {{ $user_station ? $user_station->name . ' - ' . $user_station->location : 'No Station Assigned' }}
                    </div>
                </div>
                @endif

                <!-- Tank Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Tank</label>
                    <select x-model="tankFilter" @change="applyFilters" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200" :disabled="!stationFilter">
                        <option value="">All Tanks</option>
                        @if($tanks && $tanks->isNotEmpty())
                            @foreach($tanks as $tank)
                            <option value="{{ $tank->id }}">{{ $tank->tank_number }} ({{ ucfirst($tank->fuel_type) }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Status</label>
                    <select x-model="statusFilter" @change="applyFilters" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-200">
                <div class="flex items-center space-x-4">
                    <button @click="clearFilters" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors duration-200">
                        <i class="fas fa-times w-4 h-4 mr-2"></i>
                        Clear Filters
                    </button>
                    <span x-show="filteredCount !== totalMeters" class="text-sm text-slate-500">
                        Showing <span x-text="filteredCount"></span> of <span x-text="totalMeters"></span> meters
                    </span>
                </div>
                <button @click="refreshData" class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-sync-alt w-4 h-4 mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Premium Meters Grid -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-semibold text-slate-900">Meters Overview</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Meter Details</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tank & Station</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Current Reading</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($meters as $meter)
                    <tr class="meter-row hover:bg-slate-50 transition-colors duration-200"
                        data-search="{{ strtolower($meter->meter_number . ' ' . $meter->tank_number . ' ' . $meter->station_name . ' ' . $meter->fuel_type) }}"
                        data-station="{{ $meter->station_id }}"
                        data-tank="{{ $meter->tank_id }}"
                        data-status="{{ $meter->is_active ? 'active' : 'inactive' }}">

                        <!-- Meter Details -->
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="p-2 bg-slate-100 rounded-lg mr-3">
                                    <i class="fas fa-tachometer-alt text-slate-600 w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $meter->meter_number }}</div>
                                    <div class="text-xs text-slate-500">ID: #{{ $meter->id }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Tank & Station -->
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div class="flex items-center">
                                    <i class="fas fa-gas-pump text-slate-400 w-3 h-3 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-900">{{ $meter->tank_number }}</span>
                                    <span class="ml-2 px-2 py-1 bg-{{ $meter->fuel_type === 'petrol' ? 'green' : ($meter->fuel_type === 'diesel' ? 'blue' : 'orange') }}-100 text-{{ $meter->fuel_type === 'petrol' ? 'green' : ($meter->fuel_type === 'diesel' ? 'blue' : 'orange') }}-800 text-xs font-medium rounded-full">
                                        {{ ucfirst($meter->fuel_type) }}
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-slate-400 w-3 h-3 mr-2"></i>
                                    <span class="text-xs text-slate-600">{{ $meter->station_name }}</span>
                                </div>
                                <div class="text-xs text-slate-500">{{ $meter->station_location }}</div>
                            </div>
                        </td>

                        <!-- Current Reading -->
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div class="text-lg font-bold text-slate-900">{{ number_format($meter->current_reading_liters, 3) }}L</div>
                                @if($meter->last_reading_value)
                                <div class="text-xs text-slate-500">
                                    Last: {{ number_format($meter->last_reading_value, 3) }}L
                                </div>
                                @endif
                                @if($meter->last_reading_date)
                                <div class="text-xs text-slate-500">
                                    {{ \Carbon\Carbon::parse($meter->last_reading_date)->format('M j, Y') }}
                                </div>
                                @endif
                            </div>
                        </td>

                        <!-- Activity -->
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <i class="fas fa-chart-line text-slate-400 w-3 h-3 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-700">{{ $meter->total_readings ?? 0 }}</span>
                                    <span class="text-xs text-slate-500 ml-1">total readings</span>
                                </div>
                                @if($meter->today_readings > 0)
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-green-500 w-3 h-3 mr-2"></i>
                                    <span class="text-sm font-medium text-green-600">{{ $meter->today_readings }}</span>
                                    <span class="text-xs text-slate-500 ml-1">today</span>
                                </div>
                                @endif
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4">
                            <button @click="toggleStatus({{ $meter->id }}, {{ $meter->is_active ? 'false' : 'true' }})"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold transition-colors duration-200
                                    {{ $meter->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                <div class="w-2 h-2 rounded-full mr-2 {{ $meter->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                                {{ $meter->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('meters.show', $meter->id) }}"
                                   class="inline-flex items-center px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-eye w-3 h-3 mr-1"></i>
                                    View
                                </a>
                                <a href="{{ route('meters.edit', $meter->id) }}"
                                   class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-edit w-3 h-3 mr-1"></i>
                                    Edit
                                </a>
                                @if($meter->total_readings == 0)
                                <button @click="deleteMeter({{ $meter->id }}, '{{ $meter->meter_number }}')"
                                        class="inline-flex items-center px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-trash w-3 h-3 mr-1"></i>
                                    Delete
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-tachometer-alt text-slate-300 w-12 h-12 mb-4"></i>
                                <h3 class="text-lg font-medium text-slate-900 mb-2">No meters found</h3>
                                <p class="text-slate-500 mb-4">Get started by adding your first meter.</p>
                                <a href="{{ route('meters.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus w-4 h-4 mr-2"></i>
                                    Add New Meter
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($meters->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            {{ $meters->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function meterManager(initialData = {}) {
    return {
        // Controller data mapping
        totalMeters: initialData.totalMeters || 0,
        activeMeters: initialData.activeMeters || 0,
        inactiveMeters: initialData.inactiveMeters || 0,
        tanksWithMeters: initialData.tanksWithMeters || 0,
        stationsWithMeters: initialData.stationsWithMeters || 0,

        // Filter state
        searchTerm: initialData.currentSearch || '',
        stationFilter: initialData.currentStationId || '',
        tankFilter: initialData.currentTankId || '',
        statusFilter: initialData.currentStatus || '',
        filteredCount: 0,

        init() {
            this.calculateFilteredCount();
        },

        applyFilters() {
            const rows = document.querySelectorAll('.meter-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.dataset.search || '';
                const stationId = row.dataset.station || '';
                const tankId = row.dataset.tank || '';
                const status = row.dataset.status || '';

                const matchesSearch = !this.searchTerm || searchData.includes(this.searchTerm.toLowerCase());
                const matchesStation = !this.stationFilter || stationId === this.stationFilter;
                const matchesTank = !this.tankFilter || tankId === this.tankFilter;
                const matchesStatus = !this.statusFilter || status === this.statusFilter;

                const visible = matchesSearch && matchesStation && matchesTank && matchesStatus;

                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            this.filteredCount = visibleCount;
        },

        calculateFilteredCount() {
            this.filteredCount = document.querySelectorAll('.meter-row:not([style*="display: none"])').length;
        },

        async onStationChange() {
            // For non-admin users, they can't change stations
            if (this.userRole !== 'admin') {
                return;
            }

            if (!this.stationFilter) {
                this.tankFilter = '';
                this.applyFilters();
                return;
            }

            try {
                const response = await fetch(`/meters/stations/${this.stationFilter}/tanks`);
                const data = await response.json();

                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: data.error
                    });
                    this.stationFilter = this.userStationId || '';
                    return;
                }

                if (data.tanks) {
                    // Update tank dropdown options dynamically
                    const tankSelect = document.querySelector('select[x-model="tankFilter"]');
                    tankSelect.innerHTML = '<option value="">All Tanks</option>';

                    data.tanks.forEach(tank => {
                        const option = document.createElement('option');
                        option.value = tank.id;
                        option.textContent = `${tank.tank_number} (${tank.fuel_type.charAt(0).toUpperCase() + tank.fuel_type.slice(1)})`;
                        tankSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load tanks:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to load tanks for selected station'
                });
            }

            this.tankFilter = '';
            this.applyFilters();
        },

        clearFilters() {
            this.searchTerm = '';
            this.stationFilter = '';
            this.tankFilter = '';
            this.statusFilter = '';
            this.applyFilters();
        },

        refreshData() {
            const params = new URLSearchParams();
            if (this.searchTerm) params.set('search', this.searchTerm);
            if (this.stationFilter && this.userRole === 'admin') params.set('station_id', this.stationFilter);
            if (this.tankFilter) params.set('tank_id', this.tankFilter);
            if (this.statusFilter) params.set('status', this.statusFilter);

            window.location.href = `${window.location.pathname}?${params.toString()}`;
        },

        async toggleStatus(meterId, newStatus) {
            try {
                const response = await fetch(`/meters/${meterId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Update status counts
                    if (newStatus) {
                        this.activeMeters++;
                        this.inactiveMeters--;
                    } else {
                        this.activeMeters--;
                        this.inactiveMeters++;
                    }

                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.error || 'Failed to update status');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: error.message
                });
            }
        },

        deleteMeter(meterId, meterNumber) {
            Swal.fire({
                title: 'Delete Meter',
                text: `Are you sure you want to delete meter ${meterNumber}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/meters/${meterId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            throw new Error(data.error || 'Failed to delete meter');
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: error.message
                        });
                    }
                }
            });
        }
    }
}
</script>
@endsection
