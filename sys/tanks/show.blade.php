@extends('layouts.app')

@section('title', 'Tank Details - ' . $tank->tank_number)

@section('breadcrumb')
<a href="{{ route('tanks.index') }}" class="text-muted-foreground hover:text-foreground">Tank Management</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground">{{ $tank->tank_number }}</span>
@endsection

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <div class="flex items-center gap-4">
            <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $tank->tank_number }}</h1>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-sm font-medium rounded-full capitalize
                    @if($tank->fuel_type === 'petrol') bg-red-100 text-red-700
                    @elseif($tank->fuel_type === 'diesel') bg-blue-100 text-blue-700
                    @else bg-green-100 text-green-700 @endif">
                    {{ $tank->fuel_type }}
                </span>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    {{ $tank->business_status === 'OPERATIONAL' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $tank->business_status }}
                </span>
            </div>
        </div>
        <p class="text-muted-foreground mt-2">{{ $tank->station_name }} - {{ $tank->station_location }}</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="refreshDashboard()" class="btn btn-secondary" id="refresh-btn">
            <i class="fas fa-sync-alt mr-2"></i>
            Refresh
        </button>
        <a href="{{ route('tanks.edit', $tank->id) }}" class="btn btn-secondary">
            <i class="fas fa-edit mr-2"></i>
            Edit Tank
        </a>
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="btn btn-primary">
                <i class="fas fa-cog mr-2"></i>
                Actions
                <i class="fas fa-chevron-down ml-2"></i>
            </button>
            <div x-show="open" @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="absolute right-0 mt-2 w-48 bg-background border border-border rounded-md shadow-lg z-10">
                <div class="py-1">
                    <button onclick="updateThresholds()" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted">
                        <i class="fas fa-bell mr-2"></i>Update Alert Thresholds
                    </button>
                    <button onclick="exportTankData()" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted">
                        <i class="fas fa-download mr-2"></i>Export Data
                    </button>
                    <button onclick="viewHistory()" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted">
                        <i class="fas fa-history mr-2"></i>View Full History
                    </button>
                    <div class="border-t border-border"></div>
                    <button onclick="deleteTank()" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-trash mr-2"></i>Delete Tank
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="tankDetails()" x-init="init()" class="space-y-6">

    <!-- Key Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Current Volume -->
        <div class="card border-l-4 border-l-blue-500 hover:shadow-md transition-all duration-200">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Current Volume</p>
                        <p class="text-2xl font-bold text-foreground" x-text="metrics.current_volume || '{{ number_format($tank->current_volume_liters, 0) }}L'"></p>
                        <p class="text-xs text-muted-foreground" x-text="metrics.fill_percentage || '{{ $tank->fill_percentage }}%'">{{ $tank->fill_percentage }}%</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-fill-drip text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-3 w-full bg-muted rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300
                        @if($tank->fill_percentage > 80) bg-green-500
                        @elseif($tank->fill_percentage > 50) bg-orange-500
                        @else bg-red-500 @endif"
                        style="width: {{ min($tank->fill_percentage, 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Capacity -->
        <div class="card border-l-4 border-l-green-500 hover:shadow-md transition-all duration-200">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Capacity</p>
                        <p class="text-2xl font-bold text-foreground">{{ number_format($tank->capacity_liters, 0) }}L</p>
                        <p class="text-xs text-muted-foreground">Maximum storage</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-tachometer-alt text-green-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Price -->
        <div class="card border-l-4 border-l-purple-500 hover:shadow-md transition-all duration-200">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Selling Price</p>
                        <p class="text-2xl font-bold text-foreground">
                            @if($tank->current_selling_price)
                                {{ number_format($tank->current_selling_price, 0) }} {{ $tank->currency_code }}
                            @else
                                <span class="text-red-500">Not Set</span>
                            @endif
                        </p>
                        <p class="text-xs text-muted-foreground">Per liter</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-tag text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Activity -->
        <div class="card border-l-4 border-l-orange-500 hover:shadow-md transition-all duration-200">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Today's Sales</p>
                        <p class="text-2xl font-bold text-foreground" x-text="metrics.today_sales || '0'">0</p>
                        <p class="text-xs text-muted-foreground" x-text="metrics.today_volume || '0L dispensed'">0L dispensed</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-chart-line text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="card border-0 shadow-xl bg-background/95 backdrop-blur-sm">
        <!-- Tab Navigation -->
        <div class="border-b border-border bg-muted/30">
            <nav class="flex space-x-8 px-6" role="tablist">
                <button @click="activeTab = 'overview'"
                        :class="{'border-primary text-primary': activeTab === 'overview', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'overview'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Overview
                </button>
                <button @click="activeTab = 'fifo'"
                        :class="{'border-primary text-primary': activeTab === 'fifo', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'fifo'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-layer-group mr-2"></i>
                    FIFO Inventory
                </button>
                <button @click="activeTab = 'meters'"
                        :class="{'border-primary text-primary': activeTab === 'meters', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'meters'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-gas-pump mr-2"></i>
                    Meters
                </button>
                <button @click="activeTab = 'history'"
                        :class="{'border-primary text-primary': activeTab === 'history', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'history'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-history mr-2"></i>
                    Activity History
                </button>
                <button @click="activeTab = 'settings'"
                        :class="{'border-primary text-primary': activeTab === 'settings', 'border-transparent text-muted-foreground hover:text-foreground': activeTab !== 'settings'}"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <i class="fas fa-cog mr-2"></i>
                    Settings
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Tank Status -->
                    <div class="card">
                        <div class="p-4 border-b border-border">
                            <h3 class="font-semibold text-foreground">Tank Status</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Current Volume:</span>
                                <span class="font-medium">{{ number_format($tank->current_volume_liters, 3) }}L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Fill Percentage:</span>
                                <span class="font-medium">{{ $tank->fill_percentage }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Available Space:</span>
                                <span class="font-medium">{{ number_format($tank->capacity_liters - $tank->current_volume_liters, 0) }}L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Active Meters:</span>
                                <span class="font-medium">{{ $tank->active_meters ?? 0 }}/{{ $tank->total_meters ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">FIFO Layers:</span>
                                <span class="font-medium">{{ $tank->active_fifo_layers ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">FIFO Total:</span>
                                <span class="font-medium">{{ number_format($tank->fifo_total_volume ?? 0, 0) }}L</span>
                            </div>
                            @if(($tank->fifo_total_volume ?? 0) != $tank->current_volume_liters)
                            <div class="flex justify-between text-red-600">
                                <span>Volume Mismatch:</span>
                                <span class="font-medium">{{ number_format(abs(($tank->fifo_total_volume ?? 0) - $tank->current_volume_liters), 3) }}L</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Today's Activity -->
                    <div class="card">
                        <div class="p-4 border-b border-border">
                            <h3 class="font-semibold text-foreground">Today's Activity</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Deliveries:</span>
                                <span class="font-medium">{{ $tank->today_deliveries ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Reconciliations:</span>
                                <span class="font-medium">{{ $tank->today_reconciliations ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Open Notifications:</span>
                                <span class="font-medium {{ ($tank->open_notifications ?? 0) > 0 ? 'text-red-600' : '' }}">
                                    {{ $tank->open_notifications ?? 0 }}
                                </span>
                            </div>
                            @if($tank->current_selling_price)
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Stock Value:</span>
                                <span class="font-medium">
                                    {{ number_format($tank->current_volume_liters * $tank->current_selling_price, 0) }} {{ $tank->currency_code }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                @if($recent_notifications && $recent_notifications->count() > 0)
                <div class="mt-6 card">
                    <div class="p-4 border-b border-border">
                        <h3 class="font-semibold text-foreground">Recent Notifications</h3>
                    </div>
                    <div class="divide-y divide-border">
                        @foreach($recent_notifications->take(5) as $notification)
                        <div class="p-4 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                @if($notification->severity === 'critical') bg-red-100
                                @elseif($notification->severity === 'high') bg-orange-100
                                @elseif($notification->severity === 'medium') bg-yellow-100
                                @else bg-blue-100 @endif">
                                <i class="fas fa-bell text-sm
                                    @if($notification->severity === 'critical') text-red-600
                                    @elseif($notification->severity === 'high') text-orange-600
                                    @elseif($notification->severity === 'medium') text-yellow-600
                                    @else text-blue-600 @endif"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-foreground">{{ $notification->title }}</p>
                                <p class="text-sm text-muted-foreground">{{ $notification->message }}</p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- FIFO Inventory Tab -->
            <div x-show="activeTab === 'fifo'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                @if($fifo_layers && $fifo_layers->count() > 0)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-foreground">FIFO Inventory Layers</h3>
                        <button onclick="refreshFifoData()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Refresh
                        </button>
                    </div>

                    <!-- FIFO Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="card text-center">
                            <div class="p-4">
                                <p class="text-2xl font-bold text-foreground">{{ $fifo_layers->count() }}</p>
                                <p class="text-sm text-muted-foreground">Active Layers</p>
                            </div>
                        </div>
                        <div class="card text-center">
                            <div class="p-4">
                                <p class="text-2xl font-bold text-foreground">{{ number_format($fifo_layers->sum('remaining_volume_liters'), 0) }}L</p>
                                <p class="text-sm text-muted-foreground">Total Volume</p>
                            </div>
                        </div>
                        <div class="card text-center">
                            <div class="p-4">
                                <p class="text-2xl font-bold text-foreground">{{ number_format($fifo_layers->avg('cost_per_liter_ugx'), 0) }}</p>
                                <p class="text-sm text-muted-foreground">Avg Cost/L</p>
                            </div>
                        </div>
                        <div class="card text-center">
                            <div class="p-4">
                                <p class="text-2xl font-bold text-foreground">{{ number_format($fifo_layers->sum('remaining_value_ugx'), 0) }}</p>
                                <p class="text-sm text-muted-foreground">Total Value</p>
                            </div>
                        </div>
                    </div>

                    <!-- FIFO Layers Table -->
                    <div class="card">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b border-border bg-muted/30">
                                    <tr>
                                        <th class="text-left py-3 px-4 font-semibold text-foreground">Layer</th>
                                        <th class="text-left py-3 px-4 font-semibold text-foreground">Source</th>
                                        <th class="text-right py-3 px-4 font-semibold text-foreground">Original Volume</th>
                                        <th class="text-right py-3 px-4 font-semibold text-foreground">Remaining</th>
                                        <th class="text-right py-3 px-4 font-semibold text-foreground">Cost/L</th>
                                        <th class="text-right py-3 px-4 font-semibold text-foreground">Value</th>
                                        <th class="text-left py-3 px-4 font-semibold text-foreground">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($fifo_layers as $layer)
                                    <tr class="hover:bg-muted/50 transition-colors duration-200">
                                        <td class="py-3 px-4 font-medium text-foreground">#{{ $layer->layer_sequence }}</td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ $layer->source_reference }}</td>
                                        <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($layer->original_volume_liters, 3) }}L</td>
                                        <td class="py-3 px-4 text-right font-medium text-foreground">{{ number_format($layer->remaining_volume_liters, 3) }}L</td>
                                        <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($layer->cost_per_liter_ugx, 2) }}</td>
                                        <td class="py-3 px-4 text-right font-medium text-foreground">{{ number_format($layer->remaining_value_ugx, 0) }}</td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ \Carbon\Carbon::parse($layer->delivery_date)->format('M j, Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
                        <i class="fas fa-layer-group text-2xl text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No FIFO Layers</h3>
                    <p class="text-muted-foreground">This tank has no active inventory layers</p>
                </div>
                @endif
            </div>

            <!-- Meters Tab -->
            <div x-show="activeTab === 'meters'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                @if($meters && $meters->count() > 0)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-foreground">Tank Meters</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($meters as $meter)
                        <div class="card">
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-foreground">{{ $meter->meter_number }}</h4>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        {{ $meter->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $meter->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Current Reading:</span>
                                        <span class="font-medium">{{ number_format($meter->current_reading_liters, 3) }}L</span>
                                    </div>
                                    @if($meter->last_reading)
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Last Reading:</span>
                                        <span class="font-medium">{{ number_format($meter->last_reading, 3) }}L</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Last Date:</span>
                                        <span class="font-medium">{{ \Carbon\Carbon::parse($meter->last_reading_date)->format('M j, Y') }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Total Readings:</span>
                                        <span class="font-medium">{{ $meter->total_readings }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
                        <i class="fas fa-gas-pump text-2xl text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No Meters</h3>
                    <p class="text-muted-foreground">No meters have been configured for this tank</p>
                </div>
                @endif
            </div>

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                <div class="space-y-6">
                    <!-- Recent Deliveries -->
                    @if($recent_deliveries && $recent_deliveries->count() > 0)
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-4">Recent Deliveries</h3>
                        <div class="card">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="border-b border-border bg-muted/30">
                                        <tr>
                                            <th class="text-left py-3 px-4 font-semibold text-foreground">Reference</th>
                                            <th class="text-left py-3 px-4 font-semibold text-foreground">Date</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Volume</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Cost/L</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Total Cost</th>
                                            <th class="text-left py-3 px-4 font-semibold text-foreground">Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        @foreach($recent_deliveries as $delivery)
                                        <tr class="hover:bg-muted/50 transition-colors duration-200">
                                            <td class="py-3 px-4 font-medium text-foreground">{{ $delivery->delivery_reference }}</td>
                                            <td class="py-3 px-4 text-muted-foreground">
                                                {{ \Carbon\Carbon::parse($delivery->delivery_date)->format('M j, Y') }}
                                            </td>
                                            <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($delivery->volume_liters, 3) }}L</td>
                                            <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($delivery->cost_per_liter_ugx, 2) }}</td>
                                            <td class="py-3 px-4 text-right font-medium text-foreground">{{ number_format($delivery->total_cost_ugx, 0) }}</td>
                                            <td class="py-3 px-4 text-muted-foreground">{{ $delivery->supplier_name ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Recent Reconciliations -->
                    @if($recent_reconciliations && $recent_reconciliations->count() > 0)
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-4">Recent Reconciliations</h3>
                        <div class="card">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="border-b border-border bg-muted/30">
                                        <tr>
                                            <th class="text-left py-3 px-4 font-semibold text-foreground">Date</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Opening</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Dispensed</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Closing</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Variance</th>
                                            <th class="text-right py-3 px-4 font-semibold text-foreground">Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        @foreach($recent_reconciliations as $reconciliation)
                                        <tr class="hover:bg-muted/50 transition-colors duration-200">
                                            <td class="py-3 px-4 font-medium text-foreground">
                                                {{ \Carbon\Carbon::parse($reconciliation->reconciliation_date)->format('M j, Y') }}
                                            </td>
                                            <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($reconciliation->opening_stock_liters, 0) }}L</td>
                                            <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($reconciliation->total_dispensed_liters, 0) }}L</td>
                                            <td class="py-3 px-4 text-right text-muted-foreground">{{ number_format($reconciliation->actual_closing_stock_liters, 0) }}L</td>
                                            <td class="py-3 px-4 text-right font-medium
                                                @if(abs($reconciliation->variance_percentage) > 2) text-red-600
                                                @elseif(abs($reconciliation->variance_percentage) > 1) text-orange-600
                                                @else text-green-600 @endif">
                                                {{ number_format($reconciliation->variance_percentage, 2) }}%
                                            </td>
                                            <td class="py-3 px-4 text-right font-medium text-foreground">{{ number_format($reconciliation->total_sales_ugx, 0) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Settings Tab -->
            <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">

                <div class="space-y-6">
                    <!-- Stock Alert Thresholds -->
                    @if($stock_thresholds)
                    <div class="card">
                        <div class="p-4 border-b border-border">
                            <h3 class="font-semibold text-foreground">Stock Alert Thresholds</h3>
                        </div>
                        <div class="p-4">
                            <form @submit.prevent="updateStockThresholds()" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-foreground">Low Stock (%)</label>
                                        <input type="number" x-model="thresholds.low_stock_percentage"
                                               class="input w-full" min="5" max="50" step="0.1">
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-foreground">Critical Stock (%)</label>
                                        <input type="number" x-model="thresholds.critical_stock_percentage"
                                               class="input w-full" min="1" max="25" step="0.1">
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-foreground">Reorder Point (L)</label>
                                        <input type="number" x-model="thresholds.reorder_point_liters"
                                               class="input w-full" min="100" step="0.001">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    Update Thresholds
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Tank Information -->
                    <div class="card">
                        <div class="p-4 border-b border-border">
                            <h3 class="font-semibold text-foreground">Tank Information</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Created:</span>
                                <span class="font-medium">{{ \Carbon\Carbon::parse($tank->created_at)->format('M j, Y g:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Last Updated:</span>
                                <span class="font-medium">{{ \Carbon\Carbon::parse($tank->updated_at)->format('M j, Y g:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tank ID:</span>
                                <span class="font-medium font-mono">{{ $tank->id }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Component -->
<script>
function tankDetails() {
    return {
        activeTab: 'overview',
        metrics: {},
        thresholds: {
            low_stock_percentage: {{ $stock_thresholds->low_stock_percentage ?? 20 }},
            critical_stock_percentage: {{ $stock_thresholds->critical_stock_percentage ?? 10 }},
            reorder_point_liters: {{ $stock_thresholds->reorder_point_liters ?? 1000 }}
        },

        init() {
            this.loadDashboardData();
            // Auto-refresh every 30 seconds
            setInterval(() => {
                this.loadDashboardData();
            }, 30000);
        },

        async loadDashboardData() {
            try {
                const response = await fetch(`/tanks/{{ $tank->id }}/dashboard-data`);
                if (response.ok) {
                    const data = await response.json();
                    this.metrics = data.metrics;
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        },

        async updateStockThresholds() {
            try {
                const response = await fetch(`/tanks/{{ $tank->id }}/stock-thresholds`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.thresholds)
                });

                const data = await response.json();

                if (response.ok) {
                    Swal.fire('Success', data.success, 'success');
                } else {
                    throw new Error(data.error || 'Failed to update thresholds');
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }
}

// Global Functions
function refreshDashboard() {
    const btn = document.getElementById('refresh-btn');
    const icon = btn.querySelector('i');

    icon.classList.add('fa-spin');
    btn.disabled = true;

    // Force reload dashboard data
    Alpine.store('tankDetails') || window.dispatchEvent(new CustomEvent('refresh-dashboard'));

    setTimeout(() => {
        icon.classList.remove('fa-spin');
        btn.disabled = false;
    }, 1000);
}

async function refreshFifoData() {
    try {
        const response = await fetch(`/tanks/{{ $tank->id }}/fifo-status`);
        if (response.ok) {
            // Reload the page to show updated FIFO data
            window.location.reload();
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to refresh FIFO data', 'error');
    }
}

function updateThresholds() {
    // Trigger the settings tab and focus on thresholds
    const alpine = Alpine.$data(document.querySelector('[x-data="tankDetails()"]'));
    alpine.activeTab = 'settings';
}

function exportTankData() {
    Swal.fire({
        title: 'Export Tank Data',
        text: 'Choose export type:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Reconciliations',
        cancelButtonText: 'Deliveries',
        showDenyButton: true,
        denyButtonText: 'FIFO Data'
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(`/tanks/{{ $tank->id }}/export?type=reconciliations&days=30`, '_blank');
        } else if (result.isDenied) {
            window.open(`/tanks/{{ $tank->id }}/export?type=fifo`, '_blank');
        } else if (result.dismiss !== Swal.DismissReason.cancel) {
            window.open(`/tanks/{{ $tank->id }}/export?type=deliveries&days=30`, '_blank');
        } else {
            window.open(`/tanks/{{ $tank->id }}/export?type=deliveries&days=30`, '_blank');
        }
    });
}

function viewHistory() {
    window.location.href = `/tanks/{{ $tank->id }}/reconciliation-history?days=90`;
}

function deleteTank() {
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
            fetch(`/tanks/{{ $tank->id }}`, {
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
                        window.location.href = '/tanks';
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
</script>
@endsection
