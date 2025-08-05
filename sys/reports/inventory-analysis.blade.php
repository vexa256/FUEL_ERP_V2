@extends('layouts.app')

@section('title', 'Inventory Analysis Dashboard')

@section('breadcrumb')
<span class="text-muted-foreground">Inventory Reports</span>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">Inventory Analysis</span>
@endsection

@section('page-header')
<div>
    <h1 class="text-2xl font-semibold text-foreground">Inventory Analysis Dashboard</h1>
    <p class="text-sm text-muted-foreground mt-1">Real-time inventory valuation and movement intelligence</p>
</div>
<div class="flex items-center gap-3">
    <div class="hidden sm:flex items-center gap-2 text-xs text-muted-foreground bg-green-50 px-3 py-1 rounded-full border border-green-200">
        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
        <span>Live FIFO Data</span>
    </div>
    <button id="export-btn" class="btn btn-secondary gap-2 shadow-sm" disabled>
        <i class="fas fa-download h-4 w-4"></i>
        <span class="hidden sm:inline">Export Analysis</span>
    </button>
    <button id="refresh-btn" class="btn btn-primary gap-2 shadow-sm">
        <i class="fas fa-sync-alt h-4 w-4"></i>
        <span class="hidden sm:inline">Refresh</span>
    </button>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="inventoryAnalysisWizard()" x-init="init()">
    <!-- Wizard Navigation -->
    <div class="card p-6 shadow-sm border">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-foreground">Inventory Intelligence Generator</h2>
            <div class="flex items-center gap-3">
                <div class="text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    <span x-text="loading ? '⏳ Processing...' : '✅ Ready'"></span>
                </div>
                <div class="text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded-full">
                    Inventory Mode
                </div>
            </div>
        </div>

        <!-- Executive Progress Tracker -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center flex-1">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                         :class="currentStep >= 0 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 0 ? 'text-foreground' : 'text-muted-foreground'">Configure</p>
                        <p class="text-xs text-muted-foreground">Set parameters</p>
                    </div>
                </div>
                <div class="flex-1 h-px bg-border mx-4"></div>
            </div>
            <div class="flex items-center flex-1">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                         :class="currentStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 1 ? 'text-foreground' : 'text-muted-foreground'">Valuation</p>
                        <p class="text-xs text-muted-foreground">Current value</p>
                    </div>
                </div>
                <div class="flex-1 h-px bg-border mx-4"></div>
            </div>
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                         :class="currentStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 2 ? 'text-foreground' : 'text-muted-foreground'">Movement</p>
                        <p class="text-xs text-muted-foreground">Flow analysis</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Filters Configuration -->
        <div x-show="currentStep === 0" class="space-y-4">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-filter h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Configure Analysis Parameters</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 1 of 3
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-4">Generate comprehensive inventory valuation and movement analysis with real-time FIFO calculations.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <label for="station_id" class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-gas-pump h-3 w-3"></i>
                        Station Selection
                    </label>
                    <select name="station_id" id="station_id" x-model="filters.station_id"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                        <option value="">All Stations</option>
                        <template x-for="station in filterOptions.stations" :key="station.id">
                            <option :value="station.id" x-text="station.name"></option>
                        </template>
                    </select>
                    <p class="text-xs text-muted-foreground">Choose station for analysis</p>
                </div>

                <div class="space-y-2">
                    <label for="fuel_type" class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-tint h-3 w-3"></i>
                        Fuel Type
                    </label>
                    <select name="fuel_type" id="fuel_type" x-model="filters.fuel_type"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                        <option value="">All Fuel Types</option>
                        <template x-for="fuelType in filterOptions.fuel_types" :key="fuelType">
                            <option :value="fuelType" x-text="formatFuelType(fuelType)"></option>
                        </template>
                    </select>
                    <p class="text-xs text-muted-foreground">Choose fuel type to filter</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-calendar-alt h-3 w-3"></i>
                        Date Range (NOT Optional)
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="start_date" x-model="filters.start_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm"
                               max="{{ date('Y-m-d') }}">
                        <input type="date" name="end_date" x-model="filters.end_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <p class="text-xs text-muted-foreground">Optional date filter for movements</p>
                </div>

                <div class="md:col-span-3 flex justify-end gap-2 pt-4">
                    <button type="button" @click="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-undo h-4 w-4 mr-2"></i>
                        Reset
                    </button>
                    <button type="button" @click="generateAnalysis" class="btn btn-primary" :disabled="loading">
                        <i class="fas fa-arrow-right h-4 w-4 mr-2"></i>
                        Generate Analysis
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Inventory Value by Station -->
        <div x-show="currentStep === 1 && analysisData" class="space-y-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-coins h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Inventory Value Analysis</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 2 of 3
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-6">Real-time FIFO inventory valuation with capacity utilization insights.</p>

            <!-- Station Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="station in analysisData.inventory_value_by_station?.station_summary || []" :key="station.station_id">
                    <div class="card p-4 shadow-sm border bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="font-medium text-blue-800" x-text="station.station_name"></h4>
                                <p class="text-xs text-blue-600" x-text="station.location"></p>
                            </div>
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-building h-5 w-5 text-white"></i>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-xs text-blue-600">Total Value:</span>
                                <span class="text-sm font-bold text-blue-800" x-text="formatCurrency(station.total_value_ugx)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-blue-600">Volume:</span>
                                <span class="text-sm font-medium text-blue-700" x-text="formatVolume(station.total_volume_liters)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-blue-600">Tanks:</span>
                                <span class="text-sm font-medium text-blue-700" x-text="`${station.total_tanks} (${station.active_layers} layers)`"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-blue-600">Avg Cost:</span>
                                <span class="text-sm font-medium text-blue-700" x-text="formatCurrency(station.weighted_avg_cost_per_liter_ugx) + '/L'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Capacity Utilization Chart -->
            <div class="card p-6 shadow-sm border">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-chart-bar h-4 w-4 text-green-500"></i>
                        Capacity Utilization by Station
                    </h4>
                    <div class="text-xs text-muted-foreground bg-green-50 px-3 py-1 rounded-full">
                        Real-time tank fill levels
                    </div>
                </div>
                <div id="capacity-chart" class="w-full h-80 border border-gray-200 rounded-md"></div>
                <div class="mt-4 text-xs text-muted-foreground text-center">
                    <div class="flex justify-center items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span>Good (0-40%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded"></div>
                            <span>Medium (40-60%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                            <span>High (60-80%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded"></div>
                            <span>Critical (80%+)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Fuel Type Breakdown -->
            <div class="card shadow-sm border">
                <div class="flex items-center justify-between p-4 border-b bg-muted/30">
                    <h4 class="font-medium text-foreground">Detailed Inventory Breakdown</h4>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground bg-background px-2 py-1 rounded-full"
                              x-text="`${analysisData.inventory_value_by_station?.fuel_type_breakdown?.length || 0} tank entries`"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/20">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Station</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Fuel Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Fill %</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Volume</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Value</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Avg Cost/L</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Age (Days)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <template x-for="item in analysisData.inventory_value_by_station?.fuel_type_breakdown || []" :key="`${item.station_id}-${item.tank_number}`">
                                <tr class="hover:bg-muted/20 transition-colors">
                                    <td class="px-4 py-3 text-sm text-foreground font-medium" x-text="item.station_name"></td>
                                    <td class="px-4 py-3 text-sm text-foreground" x-text="item.tank_number"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground"
                                              x-text="formatFuelType(item.fuel_type)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-12 h-2 bg-muted rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all"
                                                     :class="item.fill_percentage > 80 ? 'bg-green-500' :
                                                            item.fill_percentage > 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                                     :style="`width: ${Math.min(100, Math.max(0, item.fill_percentage))}%`"></div>
                                            </div>
                                            <span class="font-mono text-xs" x-text="`${item.fill_percentage}%`"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatVolume(item.fifo_total_volume_liters)"></td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(item.remaining_value_ugx)"></td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(item.weighted_avg_cost_per_liter_ugx)"></td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <span class="font-mono text-xs"
                                              :class="item.inventory_turnover_days > 180 ? 'text-red-600' :
                                                      item.inventory_turnover_days > 90 ? 'text-yellow-600' : 'text-green-600'"
                                              x-text="item.inventory_turnover_days"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button @click="previousStep" class="btn btn-secondary">
                    <i class="fas fa-arrow-left h-4 w-4 mr-2"></i>
                    Previous
                </button>
                <button @click="nextStep" class="btn btn-primary">
                    <i class="fas fa-arrow-right h-4 w-4 mr-2"></i>
                    View Movement
                </button>
            </div>
        </div>

        <!-- Step 3: Inventory Movement Analysis -->
        <div x-show="currentStep === 2 && analysisData" class="space-y-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-exchange-alt h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Inventory Movement Analysis</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 3 of 3
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-6">Comprehensive delivery and consumption patterns with FIFO turnover analysis.</p>

            <!-- Movement Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-4 shadow-sm border bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Total Deliveries</p>
                            <p class="text-xl font-bold text-green-700" x-text="getTotalDeliveries()"></p>
                            <p class="text-xs text-green-600 mt-1">Received</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-truck h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Volume Delivered</p>
                            <p class="text-xl font-bold text-blue-700" x-text="formatVolume(getTotalDeliveredVolume())"></p>
                            <p class="text-xs text-blue-600 mt-1">Total liters</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-tint h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-600 font-medium uppercase tracking-wide">Consumption Events</p>
                            <p class="text-xl font-bold text-purple-700" x-text="getTotalConsumptions()"></p>
                            <p class="text-xs text-purple-600 mt-1">FIFO events</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-line h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-orange-50 to-orange-100 border-orange-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-orange-600 font-medium uppercase tracking-wide">Avg Turnover</p>
                            <p class="text-xl font-bold text-orange-700" x-text="getAvgTurnoverDays() + ' days'"></p>
                            <p class="text-xs text-orange-600 mt-1">Inventory age</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movement Tabs -->
            <div class="card shadow-sm border">
                <div class="border-b">
                    <nav class="flex space-x-8 px-6" aria-label="Movement Tabs">
                        <button @click="movementTab = 'deliveries'"
                                :class="movementTab === 'deliveries' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <i class="fas fa-truck h-4 w-4 mr-2"></i>
                            Recent Deliveries
                        </button>
                        <button @click="movementTab = 'consumptions'"
                                :class="movementTab === 'consumptions' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <i class="fas fa-chart-line h-4 w-4 mr-2"></i>
                            FIFO Consumptions
                        </button>
                        <button @click="movementTab = 'turnover'"
                                :class="movementTab === 'turnover' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <i class="fas fa-sync-alt h-4 w-4 mr-2"></i>
                            Turnover Analysis
                        </button>
                    </nav>
                </div>

                <!-- Deliveries Tab -->
                <div x-show="movementTab === 'deliveries'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-muted/20">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Station</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Fuel</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Volume</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Cost/L</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Total Cost</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Supplier</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <template x-for="delivery in analysisData.inventory_movement_analysis?.delivery_movements?.slice(0, 20) || []" :key="delivery.id">
                                    <tr class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-3 text-sm text-foreground font-medium" x-text="formatDate(delivery.date)"></td>
                                        <td class="px-4 py-3 text-sm text-foreground font-mono" x-text="delivery.reference"></td>
                                        <td class="px-4 py-3 text-sm text-foreground" x-text="delivery.station_name"></td>
                                        <td class="px-4 py-3 text-sm text-foreground" x-text="delivery.tank_number"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground"
                                                  x-text="formatFuelType(delivery.fuel_type)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatVolume(delivery.volume_liters)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(delivery.cost_per_liter_ugx)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(delivery.total_cost_ugx)"></td>
                                        <td class="px-4 py-3 text-sm text-foreground" x-text="delivery.supplier_name || 'N/A'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Consumptions Tab -->
                <div x-show="movementTab === 'consumptions'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-muted/20">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Station</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Fuel</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Layer Seq</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Volume</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Cost/L</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Age (Days)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Impact</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <template x-for="consumption in analysisData.inventory_movement_analysis?.consumption_movements?.slice(0, 20) || []" :key="consumption.id">
                                    <tr class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-3 text-sm text-foreground font-medium" x-text="formatDate(consumption.reconciliation_date)"></td>
                                        <td class="px-4 py-3 text-sm text-foreground" x-text="consumption.station_name"></td>
                                        <td class="px-4 py-3 text-sm text-foreground" x-text="consumption.tank_number"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground"
                                                  x-text="formatFuelType(consumption.fuel_type)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="consumption.layer_sequence"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatVolume(consumption.volume_consumed_liters)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(consumption.cost_per_liter_ugx)"></td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <span class="font-mono text-xs"
                                                  :class="consumption.inventory_age_days > 180 ? 'text-red-600' :
                                                          consumption.inventory_age_days > 90 ? 'text-yellow-600' : 'text-green-600'"
                                                  x-text="consumption.inventory_age_days"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(consumption.valuation_impact_ugx)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Turnover Analysis Tab -->
                <div x-show="movementTab === 'turnover'" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-muted/20">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Station</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Fuel Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Events</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Volume</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Cost</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Avg Days</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Efficiency</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <template x-for="turnover in analysisData.inventory_movement_analysis?.turnover_analysis || []" :key="`${turnover.station_id}-${turnover.fuel_type}`">
                                    <tr class="hover:bg-muted/20 transition-colors">
                                        <td class="px-4 py-3 text-sm text-foreground font-medium" x-text="turnover.station_name"></td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground"
                                                  x-text="formatFuelType(turnover.fuel_type)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="turnover.consumption_events"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatVolume(turnover.total_consumed_volume)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(turnover.total_consumed_cost)"></td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <span class="font-mono text-xs"
                                                  :class="turnover.avg_inventory_age_days > 180 ? 'text-red-600' :
                                                          turnover.avg_inventory_age_days > 90 ? 'text-yellow-600' : 'text-green-600'"
                                                  x-text="turnover.avg_inventory_age_days"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="turnover.turnover_efficiency > 6 ? 'bg-green-100 text-green-700' :
                                                          turnover.turnover_efficiency > 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                                                  x-text="turnover.turnover_efficiency + 'x/year'">
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button @click="previousStep" class="btn btn-secondary">
                    <i class="fas fa-arrow-left h-4 w-4 mr-2"></i>
                    Previous
                </button>
                <button @click="exportAnalysis" class="btn btn-primary gap-2">
                    <i class="fas fa-download h-4 w-4"></i>
                    Export Analysis
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="card p-8 shadow-xl border text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-foreground mb-2">Processing Inventory Analysis</h3>
            <p class="text-sm text-muted-foreground">Calculating FIFO values and movement patterns...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" class="card p-6 border-destructive bg-destructive/5">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-triangle h-6 w-6 text-destructive"></i>
            <div>
                <h3 class="font-semibold text-destructive">Analysis Generation Failed</h3>
                <p class="text-sm text-destructive/80 mt-1" x-text="error"></p>
                <button @click="currentStep = 0; error = null" class="btn btn-sm btn-destructive mt-3">
                    <i class="fas fa-redo h-3 w-3 mr-1"></i>
                    Try Again
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function inventoryAnalysisWizard() {
    return {
        currentStep: 0,
        loading: false,
        error: null,
        analysisData: @json($data ?? null),
        movementTab: 'deliveries',

        filters: {
            station_id: '',
            fuel_type: '',
            start_date: '',
            end_date: ''
        },

        filterOptions: @json($data['filter_options'] ?? ['stations' => [], 'fuel_types' => []]),

        init() {
            console.log('🚀 INITIALIZING INVENTORY ANALYSIS WIZARD');
            console.log('📊 Initial Analysis Data:', this.analysisData);
            console.log('🔧 Filter Options:', this.filterOptions);

            // Check if echarts is available immediately
            if (typeof echarts !== 'undefined') {
                console.log('✅ ECharts library loaded, version:', echarts.version || 'Unknown');
            } else {
                console.error('❌ ECharts library not found - check if script is loaded');
            }

            // Initialize chart if data is available and we're on the right step
            if (this.analysisData && this.currentStep >= 1) {
                console.log('📈 Data available, initializing chart...');
                this.$nextTick(() => {
                    this.initializeCapacityChart();
                });
            } else {
                console.log('⏳ No data or wrong step, chart will initialize after data generation');
            }

            // Setup buttons
            const exportBtn = document.getElementById('export-btn');
            const refreshBtn = document.getElementById('refresh-btn');

            if (exportBtn) {
                exportBtn.addEventListener('click', () => this.exportAnalysis());
                console.log('✅ Export button listener attached');
            } else {
                console.warn('⚠️ Export button not found');
            }

            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.currentStep = 0;
                    this.error = null;
                    console.log('🔄 Refresh button clicked');
                });
                console.log('✅ Refresh button listener attached');
            } else {
                console.warn('⚠️ Refresh button not found');
            }

            // Enable export if data exists
            if (this.analysisData && exportBtn) {
                exportBtn.disabled = false;
                console.log('✅ Export button enabled');
            }
        },

        async generateAnalysis() {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams();

                // CONTROLLER EXPECTS ARRAYS - Convert single selections to arrays
                // If station selected, send as array; if empty, send all stations
                if (this.filters.station_id) {
                    params.append('station_ids[]', this.filters.station_id);
                } else {
                    // Send all available station IDs when "All Stations" is selected
                    this.filterOptions.stations.forEach(station => {
                        params.append('station_ids[]', station.id);
                    });
                }

                // If fuel type selected, send as array
                if (this.filters.fuel_type) {
                    params.append('fuel_types[]', this.filters.fuel_type);
                }
                // Note: Empty fuel_types means "all fuel types" in controller

                // Add date filters if present
                if (this.filters.start_date) {
                    params.append('start_date', this.filters.start_date);
                }
                if (this.filters.end_date) {
                    params.append('end_date', this.filters.end_date);
                }

                const requestUrl = `{{ route('reports.inventory-analysis') }}?${params}`;

                // 🔍 COMPREHENSIVE REQUEST LOGGING
                console.log('🚀 ===== INVENTORY ANALYSIS REQUEST =====');
                console.log('📡 REQUEST URL:', requestUrl);
                console.log('🔧 RAW PARAMETERS:', params.toString());
                console.log('📋 DECODED PARAMETERS:', Object.fromEntries(params));
                console.log('🎯 FILTER STATE:', {
                    station_id: this.filters.station_id,
                    fuel_type: this.filters.fuel_type,
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date
                });
                console.log('🏢 AVAILABLE STATIONS:', this.filterOptions.stations);
                console.log('⛽ AVAILABLE FUEL TYPES:', this.filterOptions.fuel_types);
                console.log('⏰ REQUEST TIMESTAMP:', new Date().toISOString());
                console.log('👤 USER CONTEXT:', {
                    user_agent: navigator.userAgent,
                    screen_resolution: `${screen.width}x${screen.height}`,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                });

                // 📤 REQUEST PAYLOAD BREAKDOWN
                console.log('📤 ===== REQUEST PAYLOAD BREAKDOWN =====');
                const stationIds = [];
                const fuelTypes = [];
                for (const [key, value] of params.entries()) {
                    if (key === 'station_ids[]') {
                        stationIds.push(value);
                    } else if (key === 'fuel_types[]') {
                        fuelTypes.push(value);
                    }
                    console.log(`📋 ${key}: ${value}`);
                }

                console.log('🏢 STATION IDS BEING SENT:', stationIds);
                console.log('⛽ FUEL TYPES BEING SENT:', fuelTypes);
                console.log('📅 DATE FILTERS:', {
                    start_date: params.get('start_date') || 'NOT SET',
                    end_date: params.get('end_date') || 'NOT SET'
                });

                const response = await fetch(requestUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                // 📥 RESPONSE LOGGING
                console.log('📥 ===== SERVER RESPONSE =====');
                console.log('📊 RESPONSE STATUS:', response.status);
                console.log('📊 RESPONSE STATUS TEXT:', response.statusText);
                console.log('📊 RESPONSE HEADERS:', Object.fromEntries(response.headers.entries()));
                console.log('🕐 RESPONSE TIME:', new Date().toISOString());

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('❌ HTTP ERROR RESPONSE BODY:', errorText);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                // 🗂️ COMPLETE DATA STRUCTURE LOGGING
                console.log('🗂️ ===== COMPLETE SERVER RESPONSE DATA =====');
                console.log('📊 RESPONSE SUCCESS FLAG:', data.success);
                console.log('📊 RESPONSE MESSAGE:', data.message);
                console.log('📊 RESPONSE FILTER SUMMARY:', data.filter_summary);
                console.log('📊 COMPLETE RESPONSE OBJECT:', data);

                if (data.data) {
                    console.log('🏗️ ===== DATA STRUCTURE ANALYSIS =====');
                    console.log('📁 TOP LEVEL KEYS:', Object.keys(data.data));

                    // INVENTORY VALUE BY STATION
                    if (data.data.inventory_value_by_station) {
                        console.log('🏢 INVENTORY VALUE BY STATION KEYS:', Object.keys(data.data.inventory_value_by_station));

                        if (data.data.inventory_value_by_station.station_summary) {
                            console.log('📊 STATION SUMMARY COUNT:', data.data.inventory_value_by_station.station_summary.length);
                            console.log('📊 FIRST STATION SUMMARY:', data.data.inventory_value_by_station.station_summary[0]);
                        }

                        if (data.data.inventory_value_by_station.capacity_utilization) {
                            console.log('📈 CAPACITY UTILIZATION COUNT:', data.data.inventory_value_by_station.capacity_utilization.length);
                            console.log('📈 CAPACITY UTILIZATION DATA:', data.data.inventory_value_by_station.capacity_utilization);
                            data.data.inventory_value_by_station.capacity_utilization.forEach((station, index) => {
                                console.log(`🏢 Station ${index + 1}:`, {
                                    name: station.station_name,
                                    utilization: station.utilization_percentage + '%',
                                    tanks: station.tank_count,
                                    volume: station.total_current_volume_liters + 'L',
                                    capacity: station.total_capacity_liters + 'L'
                                });
                            });
                        }

                        if (data.data.inventory_value_by_station.fuel_type_breakdown) {
                            console.log('⛽ FUEL TYPE BREAKDOWN COUNT:', data.data.inventory_value_by_station.fuel_type_breakdown.length);
                            console.log('⛽ FIRST FUEL TYPE ENTRY:', data.data.inventory_value_by_station.fuel_type_breakdown[0]);
                        }
                    }

                    // INVENTORY MOVEMENT ANALYSIS
                    if (data.data.inventory_movement_analysis) {
                        console.log('🔄 INVENTORY MOVEMENT KEYS:', Object.keys(data.data.inventory_movement_analysis));

                        if (data.data.inventory_movement_analysis.delivery_movements) {
                            console.log('🚛 DELIVERY MOVEMENTS COUNT:', data.data.inventory_movement_analysis.delivery_movements.length);
                        }

                        if (data.data.inventory_movement_analysis.consumption_movements) {
                            console.log('📉 CONSUMPTION MOVEMENTS COUNT:', data.data.inventory_movement_analysis.consumption_movements.length);
                        }
                    }

                    // FILTER OPTIONS
                    if (data.data.filter_options) {
                        console.log('🔧 FILTER OPTIONS:', data.data.filter_options);
                    }

                    // APPLIED FILTERS
                    if (data.data.applied_filters) {
                        console.log('✅ APPLIED FILTERS:', data.data.applied_filters);
                    }
                }

                if (!data.success) {
                    console.error('❌ SERVER RETURNED FAILURE:', data.error);
                    throw new Error(data.error || 'Analysis generation failed');
                }

                // 🎯 DATA ASSIGNMENT AND UI UPDATE
                console.log('🎯 ===== DATA ASSIGNMENT =====');
                console.log('📊 SETTING analysisData TO:', data.data);

                this.analysisData = data.data;
                this.currentStep = 1;

                // CRITICAL: Initialize chart AFTER data is set and DOM is updated
                console.log('📊 Data loaded, initializing chart in next tick...');
                this.$nextTick(() => {
                    setTimeout(() => {
                        console.log('🎯 Attempting chart initialization...');
                        console.log('🔍 analysisData at chart init:', this.analysisData);
                        this.initializeCapacityChart();
                    }, 100); // Small delay to ensure DOM is fully rendered
                });

                // Enable export button
                const exportBtn = document.getElementById('export-btn');
                if (exportBtn) {
                    exportBtn.disabled = false;
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Analysis Generated',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

            } catch (error) {
                console.error('❌ ===== REQUEST/RESPONSE ERROR =====');
                console.error('❌ ERROR MESSAGE:', error.message);
                console.error('❌ ERROR STACK:', error.stack);
                console.error('❌ ERROR OBJECT:', error);
                console.error('❌ CURRENT FILTERS:', this.filters);
                console.error('❌ CURRENT OPTIONS:', this.filterOptions);

                this.error = error.message;

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Generation Failed',
                    text: error.message,
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            } finally {
                this.loading = false;
                console.log('🏁 ===== REQUEST COMPLETED =====');
                console.log('⏰ FINAL TIMESTAMP:', new Date().toISOString());
            }
        },

        nextStep() {
            if (this.currentStep < 2) {
                this.currentStep++;

                // CRITICAL: Initialize charts when reaching appropriate steps
                if (this.currentStep === 1) {
                    console.log('📈 Moving to step 1, initializing chart...');
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initializeCapacityChart();
                        }, 200); // Longer delay for step navigation
                    });
                }
            }
        },

        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        resetFilters() {
            this.filters = {
                station_id: '',
                fuel_type: '',
                start_date: '',
                end_date: ''
            };
        },

        initializeCapacityChart() {
            console.log('🚀 INITIALIZING CAPACITY CHART');
            console.log('📊 Current Step:', this.currentStep);
            console.log('🗂️ Analysis Data Available:', !!this.analysisData);

            const chartDom = document.getElementById('capacity-chart');
            console.log('📊 Chart DOM Element:', chartDom);

            if (!chartDom) {
                console.error('❌ Chart DOM element not found');
                return;
            }

            // Check if echarts is available
            if (typeof echarts === 'undefined') {
                console.error('❌ ECharts library not loaded');
                chartDom.innerHTML = '<div class="flex items-center justify-center h-80 text-red-600 border border-red-200 rounded bg-red-50"><i class="fas fa-exclamation-triangle mr-2"></i>ECharts library not loaded. Check your scripts.</div>';
                return;
            }

            console.log('📈 ECharts version:', echarts.version || 'Unknown');

            // DETAILED DATA CHECKING
            if (!this.analysisData) {
                console.warn('⚠️ No analysis data available');
                chartDom.innerHTML = '<div class="flex items-center justify-center h-80 text-blue-600 border border-blue-200 rounded bg-blue-50"><i class="fas fa-info-circle mr-2"></i>No data available. Click "Generate Analysis" first.</div>';
                return;
            }

            console.log('🔍 Full Analysis Data Structure:', this.analysisData);

            if (!this.analysisData.inventory_value_by_station) {
                console.warn('⚠️ Missing inventory_value_by_station');
                console.log('📋 Available keys:', Object.keys(this.analysisData));
                chartDom.innerHTML = '<div class="flex items-center justify-center h-80 text-orange-600 border border-orange-200 rounded bg-orange-50"><i class="fas fa-database mr-2"></i>Missing inventory data structure</div>';
                return;
            }

            if (!this.analysisData.inventory_value_by_station.capacity_utilization) {
                console.warn('⚠️ Missing capacity_utilization');
                console.log('📋 Available inventory keys:', Object.keys(this.analysisData.inventory_value_by_station));
                chartDom.innerHTML = '<div class="flex items-center justify-center h-80 text-purple-600 border border-purple-200 rounded bg-purple-50"><i class="fas fa-chart-bar mr-2"></i>Missing capacity utilization data</div>';
                return;
            }

            const utilization = this.analysisData.inventory_value_by_station.capacity_utilization;
            console.log('📊 Raw Utilization Data:', utilization);
            console.log('📊 Utilization Array Length:', utilization.length);
            console.log('📊 First Item:', utilization[0]);

            if (!Array.isArray(utilization) || utilization.length === 0) {
                console.warn('⚠️ Utilization data is empty or invalid');
                chartDom.innerHTML = '<div class="flex items-center justify-center h-80 text-gray-600 border border-gray-200 rounded bg-gray-50"><i class="fas fa-exclamation-circle mr-2"></i>No stations have capacity data to display</div>';
                return;
            }

            try {
                // Clear any existing content
                chartDom.innerHTML = '';

                // Dispose existing chart if any
                const existingChart = echarts.getInstanceByDom(chartDom);
                if (existingChart) {
                    console.log('🗑️ Disposing existing chart');
                    existingChart.dispose();
                }

                console.log('🏗️ Creating new chart instance...');
                const myChart = echarts.init(chartDom, null, {
                    width: chartDom.offsetWidth,
                    height: 320
                });

                console.log('✅ Chart instance created:', myChart);
                console.log('📐 Chart dimensions:', chartDom.offsetWidth, 'x', chartDom.offsetHeight);

                // Prepare chart data with validation
                const chartData = utilization.map((u, index) => {
                    const utilizationPercent = parseFloat(u.utilization_percentage) || 0;
                    const stationName = u.station_name || `Station ${index + 1}`;
                    const tankCount = parseInt(u.tank_count) || 0;

                    console.log(`📊 Station ${index}: ${stationName} = ${utilizationPercent}% (${tankCount} tanks)`);

                    return {
                        name: stationName,
                        value: utilizationPercent,
                        tank_count: tankCount,
                        raw_data: u
                    };
                });

                console.log('📊 Final Chart Data:', chartData);

                const option = {
                    title: {
                        text: 'Station Capacity Utilization',
                        left: 'center',
                        top: '5%',
                        textStyle: {
                            fontSize: 16,
                            fontWeight: 'bold',
                            color: '#374151'
                        }
                    },
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow'
                        },
                        formatter: function(params) {
                            const data = params[0];
                            const stationData = utilization[data.dataIndex];
                            return `<div style="padding: 8px;">
                                    <strong style="color: #1f2937;">${data.name}</strong><br/>
                                    <div style="margin: 4px 0;">
                                        <span style="color: #059669;">●</span> Utilization: <strong>${data.value.toFixed(1)}%</strong><br/>
                                        <span style="color: #3b82f6;">●</span> Tanks: <strong>${stationData.tank_count}</strong><br/>
                                        <span style="color: #8b5cf6;">●</span> Current Volume: <strong>${(stationData.total_current_volume_liters / 1000).toFixed(1)}K L</strong><br/>
                                        <span style="color: #f59e0b;">●</span> Total Capacity: <strong>${(stationData.total_capacity_liters / 1000).toFixed(1)}K L</strong>
                                    </div>
                                    </div>`;
                        }
                    },
                    grid: {
                        left: '12%',
                        right: '8%',
                        bottom: '20%',
                        top: '20%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        data: chartData.map(d => d.name),
                        axisLabel: {
                            interval: 0,
                            rotate: 45,
                            fontSize: 11,
                            color: '#6b7280'
                        },
                        axisLine: {
                            lineStyle: {
                                color: '#d1d5db'
                            }
                        }
                    },
                    yAxis: {
                        type: 'value',
                        min: 0,
                        max: 100,
                        axisLabel: {
                            formatter: '{value}%',
                            fontSize: 11,
                            color: '#6b7280'
                        },
                        axisLine: {
                            lineStyle: {
                                color: '#d1d5db'
                            }
                        },
                        splitLine: {
                            lineStyle: {
                                color: '#f3f4f6'
                            }
                        }
                    },
                    series: [{
                        name: 'Utilization',
                        type: 'bar',
                        data: chartData.map(d => ({
                            value: d.value,
                            itemStyle: {
                                color: d.value > 80 ? '#ef4444' :
                                       d.value > 60 ? '#eab308' :
                                       d.value > 40 ? '#3b82f6' : '#22c55e',
                                borderRadius: [4, 4, 0, 0]
                            }
                        })),
                        label: {
                            show: true,
                            position: 'top',
                            formatter: '{c}%',
                            fontSize: 11,
                            fontWeight: 'bold',
                            color: '#374151'
                        },
                        barWidth: '60%'
                    }]
                };

                console.log('⚙️ Setting chart option...');
                myChart.setOption(option);
                console.log('✅ Chart rendered successfully!');

                // Handle window resize
                const resizeHandler = function() {
                    if (myChart && !myChart.isDisposed()) {
                        myChart.resize();
                    }
                };

                window.removeEventListener('resize', resizeHandler);
                window.addEventListener('resize', resizeHandler);

                // Store chart instance for cleanup
                this.capacityChart = myChart;

            } catch (error) {
                console.error('❌ Chart initialization error:', error);
                console.error('❌ Error stack:', error.stack);
                chartDom.innerHTML = `<div class="flex items-center justify-center h-80 text-red-600 border border-red-200 rounded bg-red-50">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle mb-2 text-2xl"></i>
                        <div class="font-semibold">Chart Error</div>
                        <div class="text-sm mt-1">${error.message}</div>
                        <div class="text-xs mt-2 opacity-75">Check console for details</div>
                    </div>
                </div>`;
            }
        },

        async exportAnalysis() {
            try {
                const params = new URLSearchParams();

                // CONTROLLER EXPECTS ARRAYS - Convert single selections to arrays
                if (this.filters.station_id) {
                    params.append('station_ids[]', this.filters.station_id);
                } else {
                    // Send all available station IDs when "All Stations" is selected
                    this.filterOptions.stations.forEach(station => {
                        params.append('station_ids[]', station.id);
                    });
                }

                if (this.filters.fuel_type) {
                    params.append('fuel_types[]', this.filters.fuel_type);
                }

                if (this.filters.start_date) {
                    params.append('start_date', this.filters.start_date);
                }
                if (this.filters.end_date) {
                    params.append('end_date', this.filters.end_date);
                }

                params.append('format', 'csv');

                // DEBUG: Log export parameters
                console.log('📤 EXPORT PARAMETERS:', {
                    url: `{{ route('reports.inventory-analysis') }}?${params}&export=true`,
                    station_selected: this.filters.station_id || 'ALL',
                    fuel_selected: this.filters.fuel_type || 'ALL',
                    raw_params: params.toString()
                });

                window.open(`{{ route('reports.inventory-analysis') }}?${params}&export=true`, '_blank');

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Export Started',
                    text: 'Download will begin shortly',
                    showConfirmButton: false,
                    timer: 3000
                });

            } catch (error) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Export Failed',
                    text: error.message,
                    showConfirmButton: false,
                    timer: 5000
                });
            }
        },

        // Data aggregation methods
        getTotalDeliveries() {
            return this.analysisData?.inventory_movement_analysis?.delivery_movements?.length || 0;
        },

        getTotalDeliveredVolume() {
            const movements = this.analysisData?.inventory_movement_analysis?.movement_summary || [];
            return movements.reduce((sum, m) => sum + (parseFloat(m.total_delivered_volume) || 0), 0);
        },

        getTotalConsumptions() {
            return this.analysisData?.inventory_movement_analysis?.consumption_movements?.length || 0;
        },

        getAvgTurnoverDays() {
            const turnover = this.analysisData?.inventory_movement_analysis?.turnover_analysis || [];
            if (turnover.length === 0) return 0;

            const totalDays = turnover.reduce((sum, t) => sum + (parseFloat(t.avg_inventory_age_days) || 0), 0);
            return Math.round(totalDays / turnover.length);
        },

        // Utility formatters
        formatCurrency(value) {
            const num = parseFloat(value) || 0;
            return 'UGX ' + num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },

        formatVolume(value) {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3
            }) + 'L';
        },

        formatFuelType(fuelType) {
            return fuelType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endsection

@push('styles')
<style>
/* Chart container styling */
#capacity-chart {
    min-height: 320px;
}

/* Step indicator animations */
.animate-in {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }

    table {
        min-width: 900px;
    }

    #capacity-chart {
        min-height: 280px;
    }
}

/* Custom progress bar styling */
.w-12.h-2 {
    background-color: rgb(var(--muted));
}

/* Tab transition animations */
.whitespace-nowrap {
    transition: all 0.2s ease-in-out;
}
</style>
@endpush
