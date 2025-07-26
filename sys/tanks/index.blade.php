@extends('layouts.app')

@section('title', 'Tank Management')

@section('breadcrumb')
<span class="text-muted-foreground">Tank Management</span>
@endsection

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Tank Management</h1>
        <p class="text-muted-foreground mt-2">Monitor and manage fuel tank operations across stations</p>
    </div>
    @if(request('station_id'))
    <div class="flex items-center gap-3">
        <button onclick="openCreateModal()" class="btn btn-primary shadow-sm hover:shadow-md transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            New Tank
        </button>
        <button onclick="exportData()" class="btn btn-secondary">
            <i class="fas fa-download mr-2"></i>
            Export
        </button>
    </div>
    @endif
</div>
@endsection

@section('content')
<div x-data="tankManagement()" x-init="init()" class="space-y-6">
    <!-- Station Selection Wizard Step -->
    @if(!request('station_id'))
    <div class="card border-2 border-dashed border-border bg-background/50 backdrop-blur-sm">
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="fas fa-building text-2xl text-primary"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">Select Station</h2>
            <p class="text-muted-foreground mb-6">Choose a station to view and manage tanks</p>

            <div class="max-w-md mx-auto">
                <form method="GET" action="{{ route('tanks.index') }}" class="space-y-4">
                    <select name="station_id" required class="select w-full" onchange="this.form.submit()">
                        <option value="">Choose Station...</option>
                        @foreach($accessible_stations as $station)
                        <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>
    @else

    <!-- Main Dashboard with Tabs -->
    <div class="card border-0 shadow-xl bg-background/95 backdrop-blur-sm">
        <!-- Tab Navigation -->
        <div class="border-b border-border bg-muted/30">
            <nav class="flex space-x-8 px-6" role="tablist">
                <button @click="activeTab = 'overview'"
                        :class="{'border-primary text-primary': activeTab === 'overview', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'overview'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-chart-line mr-2"></i>
                    Overview
                </button>
                <button @click="activeTab = 'tanks'"
                        :class="{'border-primary text-primary': activeTab === 'tanks', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'tanks'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-gas-pump mr-2"></i>
                    Tank List
                </button>
                <button @click="activeTab = 'analytics'"
                        :class="{'border-primary text-primary': activeTab === 'analytics', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'analytics'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-analytics mr-2"></i>
                    Analytics
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                <!-- Station Header -->
                <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-primary/5 to-secondary/5 border border-border/50">
                    <div class="flex items-center justify-between">
                        <div>
                            @php
                                $current_station = $accessible_stations->firstWhere('id', request('station_id'));
                            @endphp
                            <h2 class="text-lg font-semibold text-foreground">{{ $current_station->name ?? 'Station' }}</h2>
                            <p class="text-sm text-muted-foreground">{{ $current_station->location ?? '' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">Currency</p>
                            <p class="font-medium">{{ $current_station->currency_code ?? 'UGX' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                @if(isset($stats))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="card border-l-4 border-l-blue-500 hover:shadow-md transition-all duration-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Total Tanks</p>
                                    <p class="text-2xl font-bold text-foreground">{{ $stats->total_tanks ?? 0 }}</p>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-l-4 border-l-green-500 hover:shadow-md transition-all duration-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Total Capacity</p>
                                    <p class="text-2xl font-bold text-foreground">{{ number_format($stats->total_capacity ?? 0, 0) }}L</p>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-tachometer-alt text-green-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-l-4 border-l-orange-500 hover:shadow-md transition-all duration-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Current Volume</p>
                                    <p class="text-2xl font-bold text-foreground">{{ number_format($stats->total_current_volume ?? 0, 0) }}L</p>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                                    <i class="fas fa-fill-drip text-orange-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-l-4 border-l-purple-500 hover:shadow-md transition-all duration-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Avg Fill %</p>
                                    <p class="text-2xl font-bold text-foreground">{{ number_format($stats->avg_fill_percentage ?? 0, 1) }}%</p>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-percentage text-purple-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Fuel Type Breakdown -->
                @if(isset($fuel_breakdown) && $fuel_breakdown->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    @foreach($fuel_breakdown as $fuel)
                    <div class="card hover:shadow-lg transition-all duration-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold capitalize">{{ $fuel->fuel_type }}</h3>
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                                    @if($fuel->fuel_type === 'petrol') bg-red-100 text-red-700
                                    @elseif($fuel->fuel_type === 'diesel') bg-blue-100 text-blue-700
                                    @else bg-green-100 text-green-700 @endif">
                                    {{ $fuel->tank_count }} Tanks
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Capacity:</span>
                                    <span class="font-medium">{{ number_format($fuel->total_capacity, 0) }}L</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Current:</span>
                                    <span class="font-medium">{{ number_format($fuel->current_volume, 0) }}L</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Fill Level:</span>
                                    <span class="font-medium">{{ number_format($fuel->avg_fill_percentage, 1) }}%</span>
                                </div>

                                <!-- Fill Progress Bar -->
                                <div class="w-full bg-muted rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300
                                        @if($fuel->avg_fill_percentage > 80) bg-green-500
                                        @elseif($fuel->avg_fill_percentage > 50) bg-orange-500
                                        @else bg-red-500 @endif"
                                        style="width: {{ min($fuel->avg_fill_percentage, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Tank List Tab -->
            <div x-show="activeTab === 'tanks'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                <!-- Filters -->
                <div class="mb-6 card">
                    <div class="p-4">
                        <form method="GET" action="{{ route('tanks.index') }}" class="flex flex-wrap gap-4 items-end">
                            <input type="hidden" name="station_id" value="{{ request('station_id') }}">

                            <div class="flex-1 min-w-[200px]">
                                <label class="text-sm font-medium text-foreground mb-2 block">Search</label>
                                <input type="text" name="search" value="{{ $search }}"
                                       placeholder="Tank number, station name..."
                                       class="input w-full">
                            </div>

                            <div class="min-w-[150px]">
                                <label class="text-sm font-medium text-foreground mb-2 block">Fuel Type</label>
                                <select name="fuel_type" class="select w-full">
                                    <option value="">All Types</option>
                                    @foreach($fuel_types as $type)
                                    <option value="{{ $type }}" {{ $fuel_type === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="min-w-[150px]">
                                <label class="text-sm font-medium text-foreground mb-2 block">Status</label>
                                <select name="status" class="select w-full">
                                    <option value="">All Status</option>
                                    <option value="Critical" {{ $status === 'Critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="Low" {{ $status === 'Low' ? 'selected' : '' }}>Low</option>
                                    <option value="Normal" {{ $status === 'Normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="High" {{ $status === 'High' ? 'selected' : '' }}>High</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>

                            @if($search || $fuel_type || $status)
                            <a href="{{ route('tanks.index', ['station_id' => request('station_id')]) }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-2"></i>Clear
                            </a>
                            @endif
                        </form>
                    </div>
                </div>

                <!-- Tank Grid -->
                @if(isset($tanks) && $tanks->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($tanks as $tank)
                    <div class="card hover:shadow-lg transition-all duration-200 cursor-pointer"
                         onclick="window.location.href='{{ route('tanks.show', $tank->id) }}'">
                        <div class="p-6">
                            <!-- Tank Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-foreground">{{ $tank->tank_number }}</h3>
                                    <p class="text-sm text-muted-foreground capitalize">{{ $tank->fuel_type }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Business Status -->
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        {{ $tank->business_status === 'OPERATIONAL' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $tank->business_status }}
                                    </span>

                                    <!-- Stock Status -->
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($tank->stock_status === 'Critical') bg-red-100 text-red-700
                                        @elseif($tank->stock_status === 'Low') bg-orange-100 text-orange-700
                                        @elseif($tank->stock_status === 'High') bg-blue-100 text-blue-700
                                        @else bg-green-100 text-green-700 @endif">
                                        {{ $tank->stock_status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Volume Info -->
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Capacity:</span>
                                    <span class="font-medium">{{ number_format($tank->capacity_liters, 0) }}L</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Current:</span>
                                    <span class="font-medium">{{ number_format($tank->current_volume_liters, 0) }}L</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Fill Level:</span>
                                    <span class="font-medium">{{ number_format($tank->fill_percentage, 1) }}%</span>
                                </div>

                                @if($tank->current_selling_price)
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Price:</span>
                                    <span class="font-medium">{{ number_format($tank->current_selling_price, 0) }} {{ $tank->currency_code }}/L</span>
                                </div>
                                @endif
                            </div>

                            <!-- Fill Progress Bar -->
                            <div class="mt-4 w-full bg-muted rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300
                                    @if($tank->fill_percentage > 80) bg-green-500
                                    @elseif($tank->fill_percentage > 50) bg-orange-500
                                    @else bg-red-500 @endif"
                                    style="width: {{ min($tank->fill_percentage, 100) }}%"></div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="mt-4 pt-4 border-t border-border grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p class="text-xs text-muted-foreground">Meters</p>
                                    <p class="text-sm font-medium">{{ $tank->active_meters }}/{{ $tank->total_meters }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">FIFO Layers</p>
                                    <p class="text-sm font-medium">{{ $tank->active_fifo_layers }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Alerts</p>
                                    <p class="text-sm font-medium {{ $tank->open_notifications > 0 ? 'text-red-600' : '' }}">
                                        {{ $tank->open_notifications }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 flex gap-2">
                                <button onclick="event.stopPropagation(); editTank({{ $tank->id }})"
                                        class="btn btn-secondary btn-sm flex-1">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button onclick="event.stopPropagation(); viewTankDetails({{ $tank->id }})"
                                        class="btn btn-primary btn-sm flex-1">
                                    <i class="fas fa-eye mr-1"></i>View
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if(isset($tanks) && $tanks->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $tanks->appends(request()->query())->links() }}
                </div>
                @endif

                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
                        <i class="fas fa-gas-pump text-2xl text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No tanks found</h3>
                    <p class="text-muted-foreground mb-6">{{ $search || $fuel_type || $status ? 'Try adjusting your filters' : 'Get started by creating your first tank' }}</p>
                    @if(!$search && !$fuel_type && !$status)
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Create Tank
                    </button>
                    @endif
                </div>
                @endif
            </div>

            <!-- Analytics Tab -->
            <div x-show="activeTab === 'analytics'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                @if(auth()->user()->role === 'admin' && isset($station_breakdown))
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-foreground">Station Performance</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($station_breakdown as $station)
                        <div class="card hover:shadow-lg transition-all duration-200">
                            <div class="p-6">
                                <h4 class="font-semibold text-foreground mb-2">{{ $station->station_name }}</h4>
                                <p class="text-sm text-muted-foreground mb-4">{{ $station->location }}</p>

                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Tanks:</span>
                                        <span class="font-medium">{{ $station->tank_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Capacity:</span>
                                        <span class="font-medium">{{ number_format($station->total_capacity, 0) }}L</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Fill Level:</span>
                                        <span class="font-medium">{{ number_format($station->avg_fill_percentage, 1) }}%</span>
                                    </div>
                                    @if($station->incomplete_tanks > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-red-600">Incomplete:</span>
                                        <span class="font-medium text-red-600">{{ $station->incomplete_tanks }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
                        <i class="fas fa-chart-bar text-2xl text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">Analytics Dashboard</h3>
                    <p class="text-muted-foreground">Detailed analytics available for admin users</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Alpine.js Component -->
<script>
function tankManagement() {
    return {
        activeTab: 'overview',
        loading: false,

        init() {
            // Initialize with query parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab')) {
                this.activeTab = urlParams.get('tab');
            }
        }
    }
}

// Tank Actions
function editTank(tankId) {
    window.location.href = `/tanks/${tankId}/edit`;
}

function viewTankDetails(tankId) {
    window.location.href = `/tanks/${tankId}`;
}

function deleteTank(tankId) {
    Swal.fire({
        title: 'Delete Tank?',
        text: 'This action cannot be undone. All tank data will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/tanks/${tankId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to delete tank', 'error');
            });
        }
    });
}

function openCreateModal() {
    window.location.href = '{{ route("tanks.create") }}?station_id={{ request("station_id") }}';
}

function exportData() {
    const stationId = '{{ request("station_id") }}';
    if (!stationId) {
        Swal.fire('Error', 'Please select a station first', 'warning');
        return;
    }

    Swal.fire({
        title: 'Export Tank Data',
        text: 'Choose export format:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Excel',
        cancelButtonText: 'JSON',
        showDenyButton: true,
        denyButtonText: 'CSV'
    }).then((result) => {
        if (result.isConfirmed || result.isDenied) {
            const format = result.isConfirmed ? 'excel' : result.isDenied ? 'csv' : 'json';
            window.open(`/tanks/export?station_id=${stationId}&format=${format}`, '_blank');
        }
    });
}

// Error Handling
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Validation Error',
        html: '@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach',
        icon: 'error',
        confirmButtonText: 'OK'
    });
});
@endif

@if(session('error'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Error',
        text: '{{ session("error") }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
});
@endif
</script>
@endsection
