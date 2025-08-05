@extends('layouts.app')
@section('title', 'Cost of Goods Sold Analytics')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Cost Intelligence Dashboard</h1>
        <p class="text-sm text-slate-600 font-medium mt-1">Unlock the story behind every liter sold</p>
    </div>
    {{-- <div class="flex items-center space-x-3">
        <button onclick="exportCogsData()" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl text-sm font-medium shadow-sm hover:shadow-md transition-all duration-200">
            <i class="fas fa-download mr-2 text-xs"></i>Export Data
        </button>
    </div> --}}
</div>
@endsection

@section('content')
<div class="bg-gradient-to-br from-slate-50 via-white to-slate-100 min-h-screen -m-4 lg:-m-6 p-4 lg:p-6" x-data="cogsWizard()">

    <!-- Station Selector - MANDATORY ACCESS CONTROL -->
    <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 mb-8 hover:shadow-md transition-all duration-200">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-blue-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
        <div class="relative z-10">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center shadow-sm">
                    <i class="fas fa-building text-blue-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-900">Station Selection</h2>
                    <p class="text-xs text-slate-500 font-medium">Choose your operational focus</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <label class="text-xs font-medium text-slate-600 uppercase tracking-wide">Station</label>
                    <select x-model="selectedStationId" @change="loadCogsData()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        <option value="">All Stations</option>
                        @foreach($available_stations as $station)
                            <option value="{{ $station->id }}">{{ $station->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-medium text-slate-600 uppercase tracking-wide">Date Range</label>
                    <select x-model="dateRange" @change="loadCogsData()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="space-y-2" x-show="dateRange === 'custom'">
                    <label class="text-xs font-medium text-slate-600 uppercase tracking-wide">Start Date</label>
                    <input type="date" x-model="startDate" @change="loadCogsData()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                </div>
                <div class="space-y-2" x-show="dateRange === 'custom'">
                    <label class="text-xs font-medium text-slate-600 uppercase tracking-wide">End Date</label>
                    <input type="date" x-model="endDate" @change="loadCogsData()" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-16">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500 mx-auto mb-4"></div>
            <p class="text-sm text-slate-600 font-medium">Analyzing cost dynamics...</p>
        </div>
    </div>

    <!-- Wizard Navigation -->
    <div x-show="!loading && cogsData" class="mb-8">
        <div class="flex flex-wrap justify-center gap-2 mb-6">
            <template x-for="(step, index) in wizardSteps" :key="index">
                <button
                    @click="currentStep = index"
                    :class="currentStep === index ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-xl text-xs font-medium transition-all duration-200 border border-slate-200"
                    x-text="step.title"
                ></button>
            </template>
        </div>
    </div>

    <!-- Step 1: Executive Story -->
    <div x-show="!loading && currentStep === 0 && cogsData" class="space-y-8">

        <!-- Executive Headline -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50/30 via-transparent to-slate-100/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-slate-100 to-slate-200 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-chart-line text-sm text-slate-600"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-slate-900">Executive Summary</h2>
                        <p class="text-xs text-slate-600 font-medium">Key performance indicators</p>
                    </div>
                </div>
                <!-- Clean headline with smaller text -->
                <div class="text-sm font-medium mb-5 text-slate-800 break-words leading-relaxed" x-text="cogsData?.executive_summary?.headline || 'Loading executive summary...'"></div>
                <!-- Compact metrics grid -->
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-1">Revenue</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(cogsData?.executive_summary?.raw_metrics?.totals?.total_revenue))"></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-1">COGS</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(cogsData?.executive_summary?.raw_metrics?.totals?.total_cogs))"></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-1">Profit</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(cogsData?.executive_summary?.raw_metrics?.totals?.total_gross_profit))"></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="text-xs font-medium text-slate-500 mb-1">Margin</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatPercentage(getNumericValue(cogsData?.executive_summary?.raw_metrics?.totals?.avg_margin))"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Stories -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-blue-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                            <i class="fas fa-trophy text-blue-600 text-sm"></i>
                        </div>
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Performance Excellence</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-sm text-slate-700 font-medium leading-relaxed break-words" x-text="cogsData?.executive_summary?.performance_story?.narrative || 'Loading performance analysis...'"></p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="text-center p-3 bg-blue-50 rounded-xl">
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Daily Average</div>
                                <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(cogsData?.executive_summary?.performance_story?.key_metrics?.daily_average))"></div>
                            </div>
                            <div class="text-center p-3 bg-blue-50 rounded-xl">
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Revenue per Tank</div>
                                <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(cogsData?.executive_summary?.performance_story?.key_metrics?.revenue_per_tank))"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 via-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                            <i class="fas fa-cogs text-purple-600 text-sm"></i>
                        </div>
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Operational Efficiency</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-sm text-slate-700 font-medium leading-relaxed break-words" x-text="cogsData?.executive_summary?.efficiency_narrative?.narrative || 'Loading efficiency analysis...'"></p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="text-center p-3 bg-purple-50 rounded-xl">
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Efficiency Score</div>
                                <div class="text-sm font-bold text-slate-900 break-words" x-text="formatPercentage(getNumericValue(cogsData?.executive_summary?.efficiency_narrative?.efficiency_score))"></div>
                            </div>
                            <div class="text-center p-3 bg-purple-50 rounded-xl">
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Layer Velocity</div>
                                <div class="text-sm font-bold text-slate-900 break-words" x-text="formatDecimal(getNumericValue(cogsData?.executive_summary?.efficiency_narrative?.layer_velocity))"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategic Insights -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-orange-50/30 via-transparent to-orange-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-lightbulb text-orange-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Strategic Intelligence</h3>
                </div>
                <div class="space-y-3" x-show="cogsData?.executive_summary?.strategic_insights?.length > 0">
                    <template x-for="insight in cogsData?.executive_summary?.strategic_insights || []" :key="insight.type">
                        <div class="flex items-start space-x-3 p-4 bg-slate-50 rounded-xl">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="insight.priority === 'CRITICAL' ? 'bg-red-100 text-red-600' :
                                         insight.priority === 'HIGH' ? 'bg-orange-100 text-orange-600' :
                                         insight.priority === 'MEDIUM' ? 'bg-yellow-100 text-yellow-600' :
                                         'bg-blue-100 text-blue-600'"
                                 x-text="insight.priority.substring(0,1)">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1" x-text="insight.type.replace('_', ' ')"></div>
                                <p class="text-sm text-slate-700 font-medium break-words" x-text="insight.insight"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: COGS Breakdown -->
    <div x-show="!loading && currentStep === 1 && cogsData" class="space-y-8">

        <!-- Section Header -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/30 via-transparent to-emerald-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-2xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-calculator text-emerald-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Cost Structure Deep Dive</h2>
                        <p class="text-sm text-slate-600 font-medium">Where every shilling is invested and how it performs</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fuel Type Breakdown Table -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/30 via-transparent to-emerald-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="relative z-10">
                <div class="p-6 border-b border-slate-200/60">
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Cost Performance by Fuel Type</h3>
                    <p class="text-sm text-slate-600 font-medium">Detailed analysis of cost efficiency across product lines</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Fuel Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Total COGS</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Volume (L)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Cost/L</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">FIFO Layers</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Stock Age</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="fuel in cogsData?.cogs_breakdown?.fuel_type_breakdown || []" :key="fuel.fuel_type">
                                <tr class="hover:bg-slate-50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                 :class="getFuelTypeColor(fuel.fuel_type)">
                                                <span x-text="fuel.fuel_type.substring(0,1).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 break-words" x-text="fuel.fuel_type.replace('_', ' ').toUpperCase()"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(fuel.total_cogs))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatVolume(getNumericValue(fuel.total_volume))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(fuel.avg_cost_per_liter))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="px-2 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-medium">
                                            <span x-text="getNumericValue(fuel.layers_used)"></span> layers
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-xs text-slate-500 font-medium break-words">
                                            <div x-text="formatDate(fuel.oldest_stock_date)"></div>
                                            <div class="text-slate-400">to</div>
                                            <div x-text="formatDate(fuel.newest_stock_date)"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="showFuelDetails(fuel)" class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-medium hover:shadow-sm transition-all duration-200">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Monthly Trends Table -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 via-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="relative z-10">
                <div class="p-6 border-b border-slate-200/60">
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Monthly Cost Evolution</h3>
                    <p class="text-sm text-slate-600 font-medium">Track how your cost structure changes over time</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Month</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Monthly COGS</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Monthly Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Margin</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Cost Ratio</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Performance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="month in cogsData?.cogs_breakdown?.monthly_trends || []" :key="month.month">
                                <tr class="hover:bg-slate-50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatMonth(month.month)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(month.monthly_cogs))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatCurrency(getNumericValue(month.monthly_revenue))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold break-words"
                                             :class="getNumericValue(month.avg_margin) > 15 ? 'text-emerald-600' : getNumericValue(month.avg_margin) > 10 ? 'text-blue-600' : 'text-orange-600'"
                                             x-text="formatPercentage(getNumericValue(month.avg_margin))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatPercentage((getNumericValue(month.monthly_cogs) / getNumericValue(month.monthly_revenue)) * 100)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="px-2 py-1 rounded-lg text-xs font-medium"
                                             :class="getNumericValue(month.avg_margin) > 15 ? 'bg-emerald-100 text-emerald-800' :
                                                     getNumericValue(month.avg_margin) > 10 ? 'bg-blue-100 text-blue-800' :
                                                     'bg-orange-100 text-orange-800'"
                                             x-text="getNumericValue(month.avg_margin) > 15 ? 'EXCELLENT' : getNumericValue(month.avg_margin) > 10 ? 'GOOD' : 'NEEDS ATTENTION'">
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: FIFO Efficiency -->
    <div x-show="!loading && currentStep === 2 && cogsData" class="space-y-8">

        <!-- Section Header -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-blue-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-layer-group text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">FIFO Inventory Intelligence</h2>
                        <p class="text-sm text-slate-600 font-medium">First-In-First-Out cost optimization and aging analysis</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Efficiency Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/30 via-transparent to-emerald-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-trophy text-emerald-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Overall Score</div>
                    <div class="text-base font-bold text-slate-900 break-words" x-text="formatPercentage(getNumericValue(cogsData?.fifo_efficiency?.efficiency_score?.overall_score))"></div>
                    <div class="text-xs text-slate-500 font-medium break-words" x-text="cogsData?.fifo_efficiency?.efficiency_score?.interpretation || 'Calculating...'"></div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-blue-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-clock text-blue-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Freshness Score</div>
                    <div class="text-base font-bold text-slate-900 break-words" x-text="formatPercentage(getNumericValue(cogsData?.fifo_efficiency?.efficiency_score?.freshness_score))"></div>
                    <div class="text-xs text-slate-500 font-medium">Inventory age optimization</div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 via-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-tachometer-alt text-purple-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Velocity Score</div>
                    <div class="text-base font-bold text-slate-900 break-words" x-text="formatPercentage(getNumericValue(cogsData?.fifo_efficiency?.efficiency_score?.velocity_score))"></div>
                    <div class="text-xs text-slate-500 font-medium">Turnover optimization</div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50/30 via-transparent to-orange-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-chart-line text-orange-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Turnover</div>
                    <div class="text-base font-bold text-slate-900 break-words" x-text="formatDays(getNumericValue(cogsData?.fifo_efficiency?.turnover_velocity?.[0]?.avg_days_to_consumption))"></div>
                    <div class="text-xs text-slate-500 font-medium">Days to consumption</div>
                </div>
            </div>
        </div>

        <!-- Aging Analysis Table -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/30 via-transparent to-blue-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="relative z-10">
                <div class="p-6 border-b border-slate-200/60">
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Inventory Aging Analysis</h3>
                    <p class="text-sm text-slate-600 font-medium">Current inventory layers by age and value at risk</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Fuel Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Delivery Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Age (Days)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Remaining Volume</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Remaining Value</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Cost/Liter</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Age Category</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="layer in cogsData?.fifo_efficiency?.aging_analysis || []" :key="layer.delivery_date + layer.fuel_type">
                                <tr class="hover:bg-slate-50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                 :class="getFuelTypeColor(layer.fuel_type)">
                                                <span x-text="layer.fuel_type.substring(0,1).toUpperCase()"></span>
                                            </div>
                                            <div class="text-sm font-bold text-slate-900 break-words" x-text="layer.fuel_type.replace('_', ' ').toUpperCase()"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatDate(layer.delivery_date)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold break-words"
                                             :class="getNumericValue(layer.days_in_inventory) > 90 ? 'text-red-600' : getNumericValue(layer.days_in_inventory) > 60 ? 'text-orange-600' : 'text-slate-900'"
                                             x-text="getNumericValue(layer.days_in_inventory)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatVolume(getNumericValue(layer.remaining_volume_liters))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(layer.remaining_value_ugx))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatCurrency(getNumericValue(layer.cost_per_liter_ugx))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="px-2 py-1 rounded-lg text-xs font-medium"
                                             :class="layer.aging_category === 'Fresh' ? 'bg-emerald-100 text-emerald-800' :
                                                     layer.aging_category === 'Aging' ? 'bg-yellow-100 text-yellow-800' :
                                                     'bg-red-100 text-red-800'"
                                             x-text="layer.aging_category">
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Turnover Velocity Table -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50/30 via-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="relative z-10">
                <div class="p-6 border-b border-slate-200/60">
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Turnover Velocity Analysis</h3>
                    <p class="text-sm text-slate-600 font-medium">How quickly each fuel type moves through FIFO layers</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Fuel Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Days to Consumption</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Total Consumed</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Layers Consumed</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Cost/Liter</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Velocity Rating</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="velocity in cogsData?.fifo_efficiency?.turnover_velocity || []" :key="velocity.fuel_type">
                                <tr class="hover:bg-slate-50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                 :class="getFuelTypeColor(velocity.fuel_type)">
                                                <span x-text="velocity.fuel_type.substring(0,1).toUpperCase()"></span>
                                            </div>
                                            <div class="text-sm font-bold text-slate-900 break-words" x-text="velocity.fuel_type.replace('_', ' ').toUpperCase()"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold break-words"
                                             :class="getNumericValue(velocity.avg_days_to_consumption) < 30 ? 'text-emerald-600' : getNumericValue(velocity.avg_days_to_consumption) < 60 ? 'text-blue-600' : 'text-orange-600'"
                                             x-text="formatDays(getNumericValue(velocity.avg_days_to_consumption))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-slate-600 break-words" x-text="formatVolume(getNumericValue(velocity.total_consumed))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="px-2 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-medium">
                                            <span x-text="getNumericValue(velocity.layers_consumed)"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(velocity.avg_consumption_cost))"></div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="px-2 py-1 rounded-lg text-xs font-medium"
                                             :class="getNumericValue(velocity.avg_days_to_consumption) < 30 ? 'bg-emerald-100 text-emerald-800' :
                                                     getNumericValue(velocity.avg_days_to_consumption) < 60 ? 'bg-blue-100 text-blue-800' :
                                                     'bg-orange-100 text-orange-800'"
                                             x-text="getNumericValue(velocity.avg_days_to_consumption) < 30 ? 'FAST' : getNumericValue(velocity.avg_days_to_consumption) < 60 ? 'MODERATE' : 'SLOW'">
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Operational Alerts -->
    <div x-show="!loading && currentStep === 3 && cogsData" class="space-y-8">

        <!-- Section Header -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-red-50/30 via-transparent to-red-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-100 to-red-200 rounded-2xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Critical Alerts & Actions</h2>
                        <p class="text-sm text-slate-600 font-medium">Issues requiring immediate management attention</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-red-50/30 via-transparent to-red-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-red-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-exclamation text-red-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Alerts</div>
                    <div class="text-base font-bold text-slate-900 break-words" x-text="getNumericValue(cogsData?.operational_alerts?.total_alerts)"></div>
                    <div class="text-xs text-slate-500 font-medium">Active issues detected</div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-red-50/30 via-transparent to-red-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-red-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-fire text-red-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">High Priority</div>
                    <div class="text-base font-bold text-red-600 break-words" x-text="getNumericValue(cogsData?.operational_alerts?.alerts_by_priority?.HIGH)"></div>
                    <div class="text-xs text-slate-500 font-medium">Immediate attention</div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50/30 via-transparent to-orange-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-clock text-orange-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Medium Priority</div>
                    <div class="text-base font-bold text-orange-600 break-words" x-text="getNumericValue(cogsData?.operational_alerts?.alerts_by_priority?.MEDIUM)"></div>
                    <div class="text-xs text-slate-500 font-medium">Review required</div>
                </div>
            </div>
            <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/30 via-transparent to-emerald-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-2xl"></div>
                <div class="relative z-10 space-y-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-all duration-200">
                        <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
                    </div>
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Status Summary</div>
                    <div class="text-xs font-bold break-words"
                         :class="getNumericValue(cogsData?.operational_alerts?.alerts_by_priority?.HIGH) === 0 ? 'text-emerald-600' : 'text-red-600'"
                         x-text="cogsData?.operational_alerts?.alert_summary || 'All systems operational'"></div>
                </div>
            </div>
        </div>

        <!-- Detailed Alerts Table -->
        <div class="group relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden hover:shadow-md transition-all duration-200">
            <div class="absolute inset-0 bg-gradient-to-br from-red-50/30 via-transparent to-red-50/20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <div class="relative z-10">
                <div class="p-6 border-b border-slate-200/60">
                    <h3 class="text-base font-bold tracking-tight text-slate-900">Active Alert Details</h3>
                    <p class="text-sm text-slate-600 font-medium">Complete breakdown of all operational issues</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Station</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Alert Message</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="alert in cogsData?.operational_alerts?.alerts || []" :key="alert.type + alert.station + (alert.tank || '')">
                                <tr class="hover:bg-slate-50 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                 :class="alert.priority === 'HIGH' ? 'bg-red-100 text-red-600' :
                                                         alert.priority === 'MEDIUM' ? 'bg-orange-100 text-orange-600' :
                                                         'bg-blue-100 text-blue-600'">
                                                <i :class="alert.priority === 'HIGH' ? 'fas fa-exclamation' :
                                                           alert.priority === 'MEDIUM' ? 'fas fa-clock' :
                                                           'fas fa-info'"></i>
                                            </div>
                                            <span class="text-xs font-medium uppercase tracking-wide"
                                                  :class="alert.priority === 'HIGH' ? 'text-red-600' :
                                                          alert.priority === 'MEDIUM' ? 'text-orange-600' :
                                                          'text-blue-600'"
                                                  x-text="alert.priority"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="px-2 py-1 rounded-lg text-xs font-medium"
                                             :class="alert.type === 'CRITICAL_VARIANCE' ? 'bg-red-100 text-red-800' :
                                                     alert.type === 'LOW_MARGIN' ? 'bg-orange-100 text-orange-800' :
                                                     'bg-blue-100 text-blue-800'"
                                             x-text="alert.type.replace('_', ' ')">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-bold text-slate-900 break-words" x-text="alert.station || 'N/A'"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-slate-600 break-words">
                                            <span x-show="alert.tank" x-text="'Tank ' + alert.tank"></span>
                                            <span x-show="alert.fuel_type" x-text="' - ' + alert.fuel_type.replace('_', ' ').toUpperCase()"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-700 font-medium break-words" x-text="alert.message"></div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="showAlertDetails(alert)" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-medium hover:shadow-sm transition-all duration-200">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!cogsData?.operational_alerts?.alerts?.length">
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center space-y-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-check-circle text-emerald-600"></i>
                                        </div>
                                        <div class="text-base font-bold text-slate-900">Excellent! No Critical Issues</div>
                                        <div class="text-sm text-slate-600 font-medium">All COGS metrics are within acceptable parameters</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Fuel Details Modal -->
    <div x-show="showFuelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-transition>
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200/60 w-full max-w-4xl max-h-[90vh] overflow-hidden m-4" @click.away="showFuelModal = false">
            <div class="p-6 border-b border-slate-200/60 bg-gradient-to-r from-emerald-50 to-blue-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-xl flex items-center justify-center shadow-sm">
                            <i class="fas fa-gas-pump text-emerald-600 text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold tracking-tight text-slate-900" x-text="'Fuel Analysis: ' + (selectedFuel?.fuel_type?.replace('_', ' ')?.toUpperCase() || 'Details')"></h3>
                            <p class="text-sm text-slate-600 font-medium">Comprehensive cost breakdown and performance metrics</p>
                        </div>
                    </div>
                    <button @click="showFuelModal = false" class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm hover:shadow-md transition-all duration-200">
                        <i class="fas fa-times text-slate-400 text-sm"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]" x-show="selectedFuel">
                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Total COGS</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(selectedFuel?.total_cogs))"></div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Volume Sold</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatVolume(getNumericValue(selectedFuel?.total_volume))"></div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Avg Cost/Liter</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="formatCurrency(getNumericValue(selectedFuel?.avg_cost_per_liter))"></div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-4">
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">FIFO Layers</div>
                        <div class="text-sm font-bold text-slate-900 break-words" x-text="getNumericValue(selectedFuel?.layers_used) + ' layers'"></div>
                    </div>
                </div>
                <!-- Stock Age Analysis -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-5">
                    <h4 class="text-base font-bold tracking-tight text-slate-900 mb-4">Stock Age Distribution</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Oldest Stock</div>
                            <div class="text-sm font-bold text-slate-900 break-words" x-text="formatDate(selectedFuel?.oldest_stock_date)"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Newest Stock</div>
                            <div class="text-sm font-bold text-slate-900 break-words" x-text="formatDate(selectedFuel?.newest_stock_date)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Details Modal -->
    <div x-show="showAlertModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" x-transition>
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200/60 w-full max-w-2xl max-h-[90vh] overflow-hidden m-4" @click.away="showAlertModal = false">
            <div class="p-6 border-b border-slate-200/60 bg-gradient-to-r from-red-50 to-orange-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                             :class="selectedAlert?.priority === 'HIGH' ? 'bg-gradient-to-br from-red-100 to-red-200' :
                                     selectedAlert?.priority === 'MEDIUM' ? 'bg-gradient-to-br from-orange-100 to-orange-200' :
                                     'bg-gradient-to-br from-blue-100 to-blue-200'">
                            <i :class="selectedAlert?.priority === 'HIGH' ? 'fas fa-exclamation-triangle text-red-600' :
                                       selectedAlert?.priority === 'MEDIUM' ? 'fas fa-clock text-orange-600' :
                                       'fas fa-info-circle text-blue-600'" class="text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold tracking-tight text-slate-900" x-text="(selectedAlert?.type || 'Alert').replace('_', ' ') + ' Details'"></h3>
                            <p class="text-sm text-slate-600 font-medium">Comprehensive alert information and recommended actions</p>
                        </div>
                    </div>
                    <button @click="showAlertModal = false" class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm hover:shadow-md transition-all duration-200">
                        <i class="fas fa-times text-slate-400 text-sm"></i>
                    </button>
                </div>
            </div>
            <div class="p-6" x-show="selectedAlert">
                <div class="space-y-5">
                    <!-- Alert Summary -->
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-5">
                        <h4 class="text-base font-bold tracking-tight text-slate-900 mb-4">Alert Summary</h4>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Priority Level</div>
                                    <div class="px-2 py-1 rounded-lg text-xs font-medium inline-block"
                                         :class="selectedAlert?.priority === 'HIGH' ? 'bg-red-100 text-red-800' :
                                                 selectedAlert?.priority === 'MEDIUM' ? 'bg-orange-100 text-orange-800' :
                                                 'bg-blue-100 text-blue-800'"
                                         x-text="selectedAlert?.priority"></div>
                                </div>
                                <div>
                                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Station</div>
                                    <div class="text-sm font-bold text-slate-900 break-words" x-text="selectedAlert?.station || 'N/A'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Description</div>
                                <p class="text-sm text-slate-700 font-medium break-words" x-text="selectedAlert?.message"></p>
                            </div>
                        </div>
                    </div>
                    <!-- Recommended Actions -->
                    <div class="bg-gradient-to-br from-emerald-50 to-blue-50 rounded-xl p-5">
                        <h4 class="text-base font-bold tracking-tight text-slate-900 mb-4">Recommended Actions</h4>
                        <div class="space-y-3">
                            <template x-if="selectedAlert?.type === 'CRITICAL_VARIANCE'">
                                <div class="space-y-2">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">1</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Immediately investigate variance source - check for measurement errors, theft, or equipment malfunction</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">2</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Review tank calibration and meter accuracy</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">3</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Implement additional monitoring procedures</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selectedAlert?.type === 'LOW_MARGIN'">
                                <div class="space-y-2">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">1</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Review pricing strategy for this fuel type</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">2</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Analyze supplier cost trends and negotiate better rates</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">3</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Consider operational efficiency improvements</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selectedAlert?.type === 'AGED_INVENTORY'">
                                <div class="space-y-2">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">1</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Implement promotional pricing to move aged inventory</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">2</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Review demand forecasting and ordering patterns</p>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-5 h-5 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                                            <span class="text-xs font-bold text-emerald-600">3</span>
                                        </div>
                                        <p class="text-sm text-slate-700 font-medium">Consider transfer to higher-volume locations</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cogsWizard() {
    return {
        // State Management
        selectedStationId: '{{ $selected_station_id ?? "" }}',
        dateRange: '30',
        startDate: '',
        endDate: '',
        loading: false,
        cogsData: null,
        currentStep: 0,
        // Modal State
        showFuelModal: false,
        showAlertModal: false,
        selectedFuel: null,
        selectedAlert: null,
        // Wizard Configuration
        wizardSteps: [
            { title: 'Executive Story', icon: 'fas fa-chart-line' },
            { title: 'Cost Breakdown', icon: 'fas fa-calculator' },
            { title: 'FIFO Efficiency', icon: 'fas fa-layer-group' },
            { title: 'Critical Alerts', icon: 'fas fa-exclamation-triangle' }
        ],
        // Initialization
        init() {
            this.setDefaultDates();
            this.loadCogsData();
        },
        setDefaultDates() {
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);
            this.endDate = today.toISOString().split('T')[0];
            this.startDate = thirtyDaysAgo.toISOString().split('T')[0];
        },
        // Data Loading
        async loadCogsData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.selectedStationId) params.append('station_id', this.selectedStationId);
                if (this.dateRange === 'custom') {
                    params.append('start_date', this.startDate);
                    params.append('end_date', this.endDate);
                } else {
                    const endDate = new Date();
                    const startDate = new Date(endDate);
                    startDate.setDate(endDate.getDate() - parseInt(this.dateRange));
                    params.append('start_date', startDate.toISOString().split('T')[0]);
                    params.append('end_date', endDate.toISOString().split('T')[0]);
                }
                const response = await fetch(`{{ route('cogs.data') }}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                this.cogsData = await response.json();
            } catch (error) {
                console.error('COGS Data Loading Failed:', error);
                Swal.fire({
                    title: 'Data Loading Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#10b981'
                });
            } finally {
                this.loading = false;
            }
        },
        // Modal Functions
        showFuelDetails(fuel) {
            this.selectedFuel = fuel;
            this.showFuelModal = true;
        },
        showAlertDetails(alert) {
            this.selectedAlert = alert;
            this.showAlertModal = true;
        },
        // Utility Functions with Safe Data Handling
        getNumericValue(value) {
            if (value === null || value === undefined || value === '') return 0;
            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        },
        formatCurrency(amount) {
            const numericAmount = this.getNumericValue(amount);
            return new Intl.NumberFormat('en-UG', {
                style: 'currency',
                currency: 'UGX',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(numericAmount);
        },
        formatPercentage(value) {
            const numericValue = this.getNumericValue(value);
            return numericValue.toFixed(1) + '%';
        },
        formatDecimal(value, decimals = 2) {
            const numericValue = this.getNumericValue(value);
            return numericValue.toFixed(decimals);
        },
        formatDays(value) {
            const numericValue = this.getNumericValue(value);
            return Math.round(numericValue) + ' days';
        },
        formatVolume(volume) {
            const numericVolume = this.getNumericValue(volume);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 1
            }).format(numericVolume) + 'L';
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        formatMonth(monthString) {
            if (!monthString) return 'N/A';
            const [year, month] = monthString.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long'
            });
        },
        getFuelTypeColor(fuelType) {
            const colors = {
                'petrol': 'bg-red-100 text-red-600',
                'diesel': 'bg-blue-100 text-blue-600',
                'kerosene': 'bg-orange-100 text-orange-600',
                'fuelsave_unleaded': 'bg-green-100 text-green-600',
                'fuelsave_diesel': 'bg-teal-100 text-teal-600',
                'v_power_unleaded': 'bg-purple-100 text-purple-600',
                'v_power_diesel': 'bg-indigo-100 text-indigo-600'
            };
            return colors[fuelType] || 'bg-slate-100 text-slate-600';
        }
    };
}

// Export Function
function exportCogsData() {
    const wizard = document.querySelector('[x-data]').__x.$data;
    const params = new URLSearchParams();
    if (wizard.selectedStationId) params.append('station_id', wizard.selectedStationId);
    if (wizard.dateRange === 'custom') {
        params.append('start_date', wizard.startDate);
        params.append('end_date', wizard.endDate);
    }
    params.append('format', 'csv');
    fetch(`{{ route('cogs.export') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire({
            title: 'Export Ready',
            text: data.message || 'Data exported successfully',
            icon: 'success',
            confirmButtonColor: '#10b981'
        });
    })
    .catch(error => {
        Swal.fire({
            title: 'Export Failed',
            text: error.message,
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    });
}
</script>
@endsection
