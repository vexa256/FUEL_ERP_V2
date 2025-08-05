@extends('layouts.app')

@section('title', 'Reconciliation Analysis')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
    <div class="space-y-1">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-gray-800 to-gray-700 bg-clip-text text-transparent">
            Reconciliation Analysis
        </h1>
        <p class="text-gray-600">Advanced reconciliation integrity monitoring and forensic analysis</p>
    </div>
    <div class="flex items-center gap-4">
        <div class="px-4 py-2 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-full">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-sm font-medium text-emerald-700">Real-time Analysis</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="reconciliationAnalysis()" x-init="init()" class="space-y-8">
    <!-- Premium Control Panel -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-gray-50 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/30 to-purple-50/30"></div>
        <div class="relative p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-gray-900 to-gray-700 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Analysis Configuration</h3>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Station Selection - Premium -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Station</label>
                    <div class="relative">
                        <select x-model="filters.station_id" @change="loadAnalysis()"
                                class="w-full appearance-none bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                            @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}" {{ $station->id == $station_id ? 'selected' : '' }}>
                                {{ $station->name }}
                            </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>

                <!-- Period Type - Premium -->
                <div class="space-y-3">
                    <label class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Period</label>
                    <div class="relative">
                        <select x-model="filters.filter_type" @change="updateDateFields()"
                                class="w-full appearance-none bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                            <option value="date_range">Custom Range</option>
                            <option value="month">Monthly View</option>
                            <option value="year">Annual View</option>
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>

                <!-- Date Range - Premium -->
                <div class="lg:col-span-2 space-y-3" x-show="filters.filter_type === 'date_range'">
                    <label class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Date Range</label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="date" x-model="filters.date_from"
                               class="bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                        <input type="date" x-model="filters.date_to"
                               class="bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                    </div>
                </div>

                <!-- Month Selection - Premium -->
                <div class="lg:col-span-2 space-y-3" x-show="filters.filter_type === 'month'">
                    <label class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Month</label>
                    <input type="month" x-model="filters.month"
                           class="w-full bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                </div>

                <!-- Year Selection - Premium -->
                <div class="lg:col-span-2 space-y-3" x-show="filters.filter_type === 'year'">
                    <label class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Year</label>
                    <input type="number" x-model="filters.year" min="2020" max="2030"
                           class="w-full bg-white/70 backdrop-blur-sm border-0 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 shadow-lg shadow-gray-900/5 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:shadow-xl transition-all duration-200">
                </div>

                <!-- Premium Action Button -->
                <div class="flex items-end">
                    <button @click="loadAnalysis()" :disabled="loading"
                            class="w-full bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white px-6 py-3 rounded-xl font-semibold text-sm shadow-xl shadow-gray-900/25 hover:shadow-2xl hover:shadow-gray-900/30 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        <span x-show="!loading" class="flex items-center justify-center gap-2">
                            <i class="fas fa-chart-bar"></i>
                            Analyze
                        </span>
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Wizard Navigation -->
    <div class="bg-white/70 backdrop-blur-xl rounded-2xl border border-gray-200/60 shadow-2xl shadow-gray-900/10">
        <div class="border-b border-gray-200/60">
            <nav class="flex" aria-label="Analysis Tabs">
                <template x-for="(tab, key) in tabs" :key="key">
                    <button @click="activeTab = key"
                            :class="activeTab === key ?
                                'bg-gradient-to-r from-gray-900 to-gray-800 text-white shadow-lg shadow-gray-900/25' :
                                'text-gray-600 hover:text-gray-900 hover:bg-gray-50/60'"
                            class="flex-1 px-6 py-4 text-sm font-semibold rounded-t-2xl transition-all duration-200 transform hover:scale-[1.02] flex items-center justify-center gap-2"
                            :disabled="loading">
                        <i :class="tab.icon" class="text-lg"></i>
                        <span x-text="tab.label" class="hidden sm:inline"></span>
                        <span x-show="tab.count > 0"
                              :class="activeTab === key ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700'"
                              class="ml-2 px-2 py-1 rounded-full text-xs font-bold"
                              x-text="tab.count"></span>
                    </button>
                </template>
            </nav>
        </div>

        <!-- Tab Content Area -->
        <div class="p-8">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-8">
                <!-- Premium Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template x-for="metric in overviewMetrics" :key="metric.key">
                        <div class="relative overflow-hidden bg-gradient-to-br from-white to-gray-50/50 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 hover:shadow-2xl hover:shadow-gray-900/10 transition-all duration-300 transform hover:scale-[1.02]">
                            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-gray-900/5 to-transparent rounded-bl-full"></div>
                            <div class="relative p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div :class="metric.iconBg" class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg">
                                        <i :class="metric.icon + ' text-white text-lg'"></i>
                                    </div>
                                    <span :class="metric.trend === 'up' ? 'text-emerald-600' : metric.trend === 'down' ? 'text-red-600' : 'text-gray-400'"
                                          class="text-sm font-medium">
                                        <i :class="metric.trend === 'up' ? 'fas fa-arrow-up' : metric.trend === 'down' ? 'fas fa-arrow-down' : 'fas fa-minus'"></i>
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider" x-text="metric.label"></h3>
                                    <p class="text-3xl font-bold text-gray-900" x-text="metric.value"></p>
                                    <p class="text-sm text-gray-500" x-text="metric.subtitle"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Reconciliation Summary -->
                    <div class="lg:col-span-2 bg-gradient-to-br from-white to-blue-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-chart-pie text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Reconciliation Overview</h3>
                        </div>
                        <template x-if="reconciliationSummary">
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Total Reconciliations</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reconciliationSummary.total_reconciliations || 0"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Total Sales</p>
                                    <p class="text-xl font-bold text-gray-900" x-text="formatCurrency(reconciliationSummary?.total_sales || 0)"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Gross Profit</p>
                                    <p class="text-xl font-bold text-emerald-600" x-text="formatCurrency(reconciliationSummary?.total_profit || 0)"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Avg Margin</p>
                                    <p class="text-xl font-bold text-blue-600" x-text="(reconciliationSummary?.avg_margin || 0).toFixed(2) + '%'"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Total Volume</p>
                                    <p class="text-xl font-bold text-gray-900" x-text="formatVolume(reconciliationSummary?.total_volume || 0)"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Variance Analysis -->
                    <div class="bg-gradient-to-br from-white to-orange-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Variance Summary</h3>
                        </div>
                        <div class="space-y-4">
                            <template x-for="variance in varianceAnalysis" :key="variance.fuel_type">
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-700 uppercase" x-text="variance.fuel_type"></span>
                                        <span class="text-xs px-2 py-1 bg-orange-100 text-orange-700 rounded-full font-medium"
                                              x-text="variance.high_variance_count + ' alerts'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <p class="text-gray-500">Avg Variance</p>
                                            <p class="font-bold text-gray-900" x-text="(variance.avg_variance || 0).toFixed(2) + '%'"></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Max Variance</p>
                                            <p class="font-bold text-red-600" x-text="(variance.max_variance || 0).toFixed(2) + '%'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Missing Reconciliations Tab -->
            <div x-show="activeTab === 'missing'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Missing Reconciliations</h3>
                    <button @click="loadMissingDetails()"
                            class="bg-black text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-800 transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>

                <div class="bg-gradient-to-br from-white to-red-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-900 text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Tank</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Fuel Type</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Morning Dip</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Evening Dip</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Meter Readings</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200/60">
                                <template x-for="item in missingReconciliations" :key="item.reading_date + '-' + item.tank_number">
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="item.tank_number"></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700"
                                                  x-text="item.fuel_type"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900" x-text="formatDate(item.reading_date)"></td>
                                        <td class="px-6 py-4 text-sm text-gray-900" x-text="item.morning_dip_liters + 'L'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-900" x-text="item.evening_dip_liters + 'L'"></td>
                                        <td class="px-6 py-4 text-sm text-gray-900" x-text="item.meter_readings_count"></td>
                                        <td class="px-6 py-4">
                                            <button @click="processManualReconciliation(item)"
                                                    class="bg-black text-white px-3 py-1 rounded-lg text-xs font-medium hover:bg-gray-800 transition-colors">
                                                Process
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="missingReconciliations.length === 0" class="p-12 text-center">
                        <i class="fas fa-check-circle text-6xl text-emerald-300 mb-4"></i>
                        <p class="text-lg font-medium text-gray-900">No Missing Reconciliations</p>
                        <p class="text-gray-500">All reconciliations are up to date</p>
                    </div>
                </div>
            </div>

            <!-- Faulty Reconciliations Tab -->
            <div x-show="activeTab === 'faulty'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Faulty Reconciliations</h3>
                    <button @click="loadFaultyDetails()"
                            class="bg-black text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-800 transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>

                <div class="bg-gradient-to-br from-white to-yellow-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-900 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Tank</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Fuel</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Variance %</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Volume Var.</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Gross Profit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Severity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200/60">
                                <template x-for="(item, index) in faultyReconciliations" :key="'faulty-' + index">
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <span x-text="item.tank_number || 'N/A'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700"
                                                  x-text="(item.fuel_type || 'unknown').toUpperCase()"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <span x-text="formatDate(item.reconciliation_date)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-bold">
                                            <span :class="getVarianceColor(item.variance_percentage)"
                                                  x-text="formatVariance(item.variance_percentage)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <span x-text="formatVolumeLiters(item.volume_variance_liters)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <span x-text="formatCurrency(item.gross_profit_ugx)"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span :class="getSeverityClass(item.severity)"
                                                  class="px-2 py-1 rounded-full text-xs font-medium"
                                                  x-text="item.severity || 'Unknown'"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <button @click="showFaultyDetails(item)"
                                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="faultyReconciliations.length === 0" class="p-12 text-center">
                        <i class="fas fa-check-circle text-6xl text-emerald-300 mb-4"></i>
                        <p class="text-lg font-medium text-gray-900">No Faulty Reconciliations</p>
                        <p class="text-gray-500">All reconciliations are mathematically correct</p>
                    </div>
                </div>
            </div>

            <!-- FIFO Integrity Tab -->
            <div x-show="activeTab === 'fifo'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">FIFO Integrity Analysis</h3>
                    <button @click="loadFifoDetails()"
                            class="bg-black text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-800 transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>

                <template x-if="fifoIntegrity">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-white to-purple-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-database text-white"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900">Total Records</h4>
                            </div>
                            <p class="text-3xl font-bold text-gray-900" x-text="fifoIntegrity.total_reconciliations || 0"></p>
                            <p class="text-sm text-gray-500 mt-1">Reconciliation records</p>
                        </div>

                        <div class="bg-gradient-to-br from-white to-green-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900">With FIFO</h4>
                            </div>
                            <p class="text-3xl font-bold text-emerald-600" x-text="fifoIntegrity.reconciliations_with_fifo || 0"></p>
                            <p class="text-sm text-gray-500 mt-1">Complete FIFO records</p>
                        </div>

                        <div class="bg-gradient-to-br from-white to-red-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-exclamation text-white"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900">Missing FIFO</h4>
                            </div>
                            <p class="text-3xl font-bold text-red-600" x-text="fifoIntegrity.missing_fifo_records || 0"></p>
                            <p class="text-sm text-gray-500 mt-1">Records without FIFO</p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Variance Analysis Tab -->
            <div x-show="activeTab === 'variance'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Advanced Variance Analysis</h3>
                    <button @click="loadVarianceDetails()"
                            class="bg-black text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-800 transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <template x-for="variance in varianceAnalysis" :key="variance.fuel_type">
                        <div class="bg-gradient-to-br from-white to-indigo-50/30 rounded-2xl border border-gray-200/60 shadow-xl shadow-gray-900/5 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-gas-pump text-white"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 uppercase" x-text="variance.fuel_type + ' Analysis'"></h4>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Total Records</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="variance.total_reconciliations || 0"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">High Variance</p>
                                    <p class="text-2xl font-bold text-red-600" x-text="variance.high_variance_count || 0"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Avg Variance</p>
                                    <p class="text-xl font-bold text-yellow-600" x-text="(variance.avg_variance || 0).toFixed(2) + '%'"></p>
                                </div>
                                <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 shadow-lg">
                                    <p class="text-sm font-medium text-gray-600">Max Variance</p>
                                    <p class="text-xl font-bold text-red-600" x-text="(variance.max_variance || 0).toFixed(2) + '%'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reconciliationAnalysis() {
    return {
        loading: false,
        activeTab: 'overview',
        filters: {
            station_id: {{ $station_id }},
            filter_type: '{{ $filter_type }}',
            date_from: '{{ $date_from }}',
            date_to: '{{ $date_to }}',
            month: '{{ $month }}',
            year: '{{ $year }}'
        },
        tabs: {
            overview: { label: 'Overview', icon: 'fas fa-chart-pie', count: 0 },
            missing: { label: 'Missing', icon: 'fas fa-exclamation-circle', count: 0 },
            faulty: { label: 'Faulty', icon: 'fas fa-bug', count: 0 },
            fifo: { label: 'FIFO', icon: 'fas fa-layer-group', count: 0 },
            variance: { label: 'Variance', icon: 'fas fa-chart-line', count: 0 }
        },
        overviewMetrics: [],
        missingReconciliations: @json($missing_reconciliations ?? []),
        faultyReconciliations: @json($faulty_reconciliations ?? []),
        varianceAnalysis: @json($variance_analysis ?? []),
        fifoIntegrity: @json($fifo_integrity ?? null),
        reconciliationSummary: @json($reconciliation_summary[0] ?? null),

        init() {
            this.updateCounts();
            this.generateOverviewMetrics();
        },

        updateCounts() {
            this.tabs.missing.count = this.missingReconciliations.length;
            this.tabs.faulty.count = this.faultyReconciliations.length;
            this.tabs.fifo.count = this.fifoIntegrity?.missing_fifo_records || 0;
            this.tabs.variance.count = this.varianceAnalysis.reduce((sum, v) => sum + (v.high_variance_count || 0), 0);
        },

        generateOverviewMetrics() {
            this.overviewMetrics = [
                {
                    key: 'total',
                    label: 'Total Reconciliations',
                    value: this.reconciliationSummary?.total_reconciliations || 0,
                    subtitle: 'Records processed',
                    icon: 'fas fa-calculator',
                    iconBg: 'bg-gradient-to-br from-blue-600 to-blue-700',
                    trend: 'up'
                },
                {
                    key: 'missing',
                    label: 'Missing Records',
                    value: this.missingReconciliations.length,
                    subtitle: 'Require attention',
                    icon: 'fas fa-exclamation-triangle',
                    iconBg: 'bg-gradient-to-br from-red-600 to-red-700',
                    trend: this.missingReconciliations.length > 0 ? 'down' : 'neutral'
                },
                {
                    key: 'faulty',
                    label: 'Faulty Records',
                    value: this.faultyReconciliations.length,
                    subtitle: 'Mathematical errors',
                    icon: 'fas fa-bug',
                    iconBg: 'bg-gradient-to-br from-yellow-600 to-yellow-700',
                    trend: this.faultyReconciliations.length > 0 ? 'down' : 'neutral'
                },
                {
                    key: 'integrity',
                    label: 'FIFO Integrity',
                    value: `${Math.round(((this.fifoIntegrity?.reconciliations_with_fifo || 0) / (this.fifoIntegrity?.total_reconciliations || 1)) * 100)}%`,
                    subtitle: 'System integrity',
                    icon: 'fas fa-shield-alt',
                    iconBg: 'bg-gradient-to-br from-emerald-600 to-emerald-700',
                    trend: 'up'
                }
            ];
        },

        updateDateFields() {
            // Clear date fields when filter type changes
            if (this.filters.filter_type === 'month') {
                this.filters.date_from = '';
                this.filters.date_to = '';
            } else if (this.filters.filter_type === 'year') {
                this.filters.date_from = '';
                this.filters.date_to = '';
                this.filters.month = '';
            }
        },

        async loadAnalysis() {
            if (this.loading) return;

            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`{{ route('reconciliation-analysis.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Analysis failed');

                // Update data
                this.missingReconciliations = data.data.missing_reconciliations || [];
                this.faultyReconciliations = data.data.faulty_reconciliations || [];
                this.varianceAnalysis = data.data.variance_analysis || [];
                this.fifoIntegrity = data.data.fifo_integrity || null;
                this.reconciliationSummary = data.data.reconciliation_summary?.[0] || null;

                this.updateCounts();
                this.generateOverviewMetrics();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Analysis Updated',
                    timer: 2000,
                    showConfirmButton: false
                });

            } catch (error) {
                console.error('Analysis error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Analysis Failed',
                    text: error.message
                });
            } finally {
                this.loading = false;
            }
        },

        async loadMissingDetails() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`{{ route('reconciliation-analysis.missing') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                this.missingReconciliations = data.data.missing_reconciliations || [];
                this.updateCounts();

            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Load Failed', text: error.message });
            } finally {
                this.loading = false;
            }
        },

        async loadFaultyDetails() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`{{ route('reconciliation-analysis.faulty') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                this.faultyReconciliations = data.data.faulty_reconciliations || [];
                this.updateCounts();

            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Load Failed', text: error.message });
            } finally {
                this.loading = false;
            }
        },

        async loadFifoDetails() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`{{ route('reconciliation-analysis.fifo-integrity') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                this.fifoIntegrity = data.data.integrity_summary || null;
                this.updateCounts();

            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Load Failed', text: error.message });
            } finally {
                this.loading = false;
            }
        },

        async loadVarianceDetails() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await fetch(`{{ route('reconciliation-analysis.variance-analysis') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                this.varianceAnalysis = data.data.variance_trends || [];
                this.updateCounts();

            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Load Failed', text: error.message });
            } finally {
                this.loading = false;
            }
        },

        async processManualReconciliation(item) {
            const result = await Swal.fire({
                title: 'Process Manual Reconciliation',
                text: `Process reconciliation for Tank ${item.tank_number} on ${this.formatDate(item.reading_date)}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#000000',
                confirmButtonText: 'Process'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`{{ route('reconciliation-analysis.process-manual') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        tank_id: item.tank_id,
                        reconciliation_date: item.reading_date
                    })
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                Swal.fire('Success', data.message, 'success');
                this.loadMissingDetails();

            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-UG', {
                style: 'currency',
                currency: 'UGX',
                minimumFractionDigits: 0
            }).format(amount || 0);
        },

        formatVolume(volume) {
            return `${(volume || 0).toLocaleString()} L`;
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('en-GB');
        },

        formatVolumeLiters(volume) {
            if (volume === null || volume === undefined) return 'N/A';
            return `${parseFloat(volume).toFixed(3)}L`;
        },

        formatVariance(variance) {
            if (variance === null || variance === undefined) return 'N/A';
            return `${parseFloat(variance).toFixed(2)}%`;
        },

        getVarianceColor(variance) {
            if (!variance) return 'text-gray-900';
            const abs_variance = Math.abs(parseFloat(variance));
            if (abs_variance > 10) return 'text-red-600 font-bold';
            if (abs_variance > 5) return 'text-red-500 font-semibold';
            if (abs_variance > 2) return 'text-yellow-600 font-medium';
            return 'text-gray-900';
        },

        getSeverityClass(severity) {
            const classes = {
                'CRITICAL': 'bg-red-100 text-red-700',
                'HIGH': 'bg-orange-100 text-orange-700',
                'MODERATE': 'bg-yellow-100 text-yellow-700',
                'LOW': 'bg-blue-100 text-blue-700',
                'MINOR': 'bg-gray-100 text-gray-700'
            };
            return classes[severity] || 'bg-gray-100 text-gray-700';
        },

        async showFaultyDetails(item) {
            const details = `
                <div class="text-left space-y-3">
                    <div><strong>Tank:</strong> ${item.tank_number || 'N/A'} (${item.fuel_type || 'Unknown'})</div>
                    <div><strong>Date:</strong> ${this.formatDate(item.reconciliation_date)}</div>
                    <div><strong>Opening Stock:</strong> ${this.formatVolumeLiters(item.opening_stock_liters)}</div>
                    <div><strong>Delivered:</strong> ${this.formatVolumeLiters(item.total_delivered_liters)}</div>
                    <div><strong>Dispensed:</strong> ${this.formatVolumeLiters(item.total_dispensed_liters)}</div>
                    <div><strong>Theoretical Closing:</strong> ${this.formatVolumeLiters(item.theoretical_closing_stock_liters)}</div>
                    <div><strong>Actual Closing:</strong> ${this.formatVolumeLiters(item.actual_closing_stock_liters)}</div>
                    <div class="border-t pt-2">
                        <div><strong>Volume Variance:</strong> <span class="${this.getVarianceColor(item.variance_percentage)}">${this.formatVolumeLiters(item.volume_variance_liters)}</span></div>
                        <div><strong>Variance %:</strong> <span class="${this.getVarianceColor(item.variance_percentage)}">${this.formatVariance(item.variance_percentage)}</span></div>
                    </div>
                    <div class="border-t pt-2">
                        <div><strong>Sales:</strong> ${this.formatCurrency(item.total_sales_ugx || 0)}</div>
                        <div><strong>COGS:</strong> ${this.formatCurrency(item.total_cogs_ugx || 0)}</div>
                        <div><strong>Gross Profit:</strong> ${this.formatCurrency(item.gross_profit_ugx || 0)}</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: `Reconciliation Details - ${item.severity || 'Unknown'} Issue`,
                html: details,
                icon: 'info',
                confirmButtonColor: '#000000',
                confirmButtonText: 'Close',
                width: '600px'
            });
        },
    }
}
</script>
@endsection
