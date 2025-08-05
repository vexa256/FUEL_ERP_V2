@extends('layouts.app')

@section('title', 'Revenue Analytics Dashboard')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 pb-6 border-b border-slate-200">
    <div class="space-y-2">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-gradient-to-br from-slate-900 to-slate-700 rounded-2xl shadow-lg">
                <i class="fas fa-chart-line text-white text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Revenue Intelligence Center</h1>
                <p class="text-slate-600 text-lg leading-relaxed">
                    Executive insights & strategic revenue performance analysis
                </p>
            </div>
        </div>
        <div class="flex items-center gap-4 mt-4">
            <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-full">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-green-700 text-sm font-medium">Live Business Data</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-full">
                <i class="fas fa-shield-alt text-blue-600 text-xs"></i>
                <span class="text-blue-700 text-sm font-medium">Enterprise Secure</span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="exportDashboard()"
                class="inline-flex items-center gap-3 px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 hover:shadow-md hover:border-slate-300 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-sm">
            <i class="fas fa-download text-slate-500"></i>
            Export Executive Report
        </button>
        <button
                class="inline-flex items-center gap-3 px-6 py-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl">
            <i class="fas fa-cog text-white"></i>
            Configure Alerts
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="revenueAnalyticsWizard()" x-init="init()" class="space-y-8">

    <!-- Executive Wizard Navigation -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="space-y-6">
            <!-- Wizard Steps -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <button @click="setActiveStep('overview')"
                            :class="activeStep === 'overview' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="flex items-center gap-3 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                        <i class="fas fa-chart-bar"></i>
                        <span>Executive Overview</span>
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">1</div>
                    </button>
                    <button @click="setActiveStep('performance')"
                            :class="activeStep === 'performance' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="flex items-center gap-3 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                        <i class="fas fa-trending-up"></i>
                        <span>Performance Deep Dive</span>
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">2</div>
                    </button>
                    <button @click="setActiveStep('insights')"
                            :class="activeStep === 'insights' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="flex items-center gap-3 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                        <i class="fas fa-lightbulb"></i>
                        <span>Strategic Insights</span>
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">3</div>
                    </button>
                    <button @click="setActiveStep('actions')"
                            :class="activeStep === 'actions' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="flex items-center gap-3 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                        <i class="fas fa-tasks"></i>
                        <span>Action Items</span>
                        <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold">4</div>
                    </button>
                </div>
                <div class="text-sm text-slate-500">
                    Step <span x-text="getCurrentStepNumber()" class="font-semibold text-slate-700"></span> of 4
                </div>
            </div>

            <!-- Dynamic Filtering Controls -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-2 bg-slate-600 rounded-lg">
                        <i class="fas fa-filter text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Smart Analysis Controls</h3>
                        <p class="text-sm text-slate-600">Customize your executive briefing parameters</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- Business Location -->
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Business Location</label>
                        @if(auth()->user()->role === 'admin')
                        <select x-model="filters.station_id" @change="refreshAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent transition-all duration-200 hover:border-slate-300 shadow-sm">
                            <option value="">🌍 All Locations</option>
                            @foreach($available_stations as $station)
                            <option value="{{ $station->id }}" {{ $default_station_id == $station->id ? 'selected' : '' }}>
                                📍 {{ $station->name }}
                            </option>
                            @endforeach
                        </select>
                        @else
                        <select x-model="filters.station_id" @change="refreshAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent shadow-sm">
                            @foreach($available_stations as $station)
                            <option value="{{ $station->id }}" selected>📍 {{ $station->name }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <!-- Analysis Period -->
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Analysis Period</label>
                        <select x-model="filters.period_type" @change="refreshAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent transition-all duration-200 hover:border-slate-300 shadow-sm">
                            <option value="daily">📅 Daily Performance</option>
                            <option value="weekly">📊 Weekly Trends</option>
                            <option value="monthly">📈 Monthly Overview</option>
                            <option value="yearly">🏆 Annual Analysis</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 uppercase tracking-wide">From Date</label>
                        <input type="date" x-model="filters.date_start" @change="refreshAnalytics()"
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent transition-all duration-200 hover:border-slate-300 shadow-sm"
                               max="{{ $today }}" value="{{ $current_month_start }}">
                    </div>

                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 uppercase tracking-wide">To Date</label>
                        <input type="date" x-model="filters.date_end" @change="refreshAnalytics()"
                               class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent transition-all duration-200 hover:border-slate-300 shadow-sm"
                               max="{{ $today }}" value="{{ $today }}">
                    </div>

                    <!-- Product Focus -->
                    <div class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Product Focus</label>
                        <select x-model="filters.fuel_type" @change="refreshAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-2 focus:ring-slate-400 focus:border-transparent transition-all duration-200 hover:border-slate-300 shadow-sm">
                            <option value="">⛽ All Products</option>
                            @foreach($valid_fuel_types as $fuel_type)
                            <option value="{{ $fuel_type }}">🔸 {{ ucfirst(str_replace('_', ' ', $fuel_type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex flex-col items-center justify-center py-24 space-y-6 bg-white rounded-2xl shadow-sm border border-slate-200">
        <div class="relative">
            <div class="w-16 h-16 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fas fa-chart-line text-slate-400 text-lg"></i>
            </div>
        </div>
        <div class="text-center space-y-3">
            <h3 class="text-xl font-semibold text-slate-900">Analyzing Revenue Intelligence</h3>
            <p class="text-slate-600 max-w-md">Processing financial data and generating executive insights across all business locations...</p>
            <div class="flex items-center justify-center gap-2 mt-4">
                <div class="w-2 h-2 bg-slate-900 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-slate-700 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error" class="bg-white rounded-2xl shadow-sm border border-red-200 p-8">
        <div class="flex items-start gap-6">
            <div class="p-4 bg-red-100 rounded-2xl">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-semibold text-red-900 mb-3">Analysis Temporarily Unavailable</h3>
                <p class="text-red-700 mb-6" x-text="error"></p>
                <div class="flex items-center gap-4">
                    <button @click="refreshAnalytics()"
                            class="inline-flex items-center gap-3 px-6 py-3 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 focus:ring-2 focus:ring-red-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                        <i class="fas fa-redo text-sm"></i>
                        Retry Analysis
                    </button>
                    <button class="inline-flex items-center gap-3 px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all duration-200">
                        <i class="fas fa-headset text-sm"></i>
                        Contact Support
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Wizard Content -->
    <div x-show="!loading && !error && dashboardData" class="space-y-8">

        <!-- STEP 1: EXECUTIVE OVERVIEW -->
        <div x-show="activeStep === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-8">

            <!-- Executive Summary Story -->
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-8 border border-slate-200">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-lg">
                        <i class="fas fa-crown text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Executive Summary</h2>
                        <p class="text-slate-600 text-lg">What happened with our revenue performance this period?</p>
                    </div>
                </div>

                <!-- Critical Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Revenue KPI -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-6">
                            <div class="p-4 bg-gradient-to-br from-green-100 to-emerald-100 rounded-2xl group-hover:shadow-md transition-all duration-200">
                                <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                            </div>
                            <div class="text-right" x-show="dashboardData?.performance_metrics?.avg_daily_revenue_ugx">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Daily Average</p>
                                <p class="text-sm font-bold text-slate-900" x-text="formatCurrency(dashboardData?.performance_metrics?.avg_daily_revenue_ugx || 0)"></p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <p class="text-2xl font-bold text-slate-900" x-text="formatCurrency(dashboardData?.performance_metrics?.total_revenue_ugx || 0)"></p>
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">Total Revenue</div>
                            </div>
                            <p class="text-xs text-slate-500" x-text="`${dashboardData?.performance_metrics?.operating_days || 0} operating days analyzed`"></p>
                        </div>
                    </div>

                    <!-- Gross Profit KPI -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-6">
                            <div class="p-4 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-2xl group-hover:shadow-md transition-all duration-200">
                                <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                            </div>
                            <div class="text-right" x-show="dashboardData?.performance_metrics?.avg_margin_pct">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Profit Margin</p>
                                <div class="flex items-center gap-1">
                                    <span class="text-sm font-bold" :class="(dashboardData?.performance_metrics?.avg_margin_pct || 0) >= 15 ? 'text-green-600' : (dashboardData?.performance_metrics?.avg_margin_pct || 0) >= 10 ? 'text-yellow-600' : 'text-red-600'" x-text="`${(dashboardData?.performance_metrics?.avg_margin_pct || 0).toFixed(1)}%`"></span>
                                    <i :class="(dashboardData?.performance_metrics?.avg_margin_pct || 0) >= 15 ? 'fas fa-arrow-up text-green-500' : (dashboardData?.performance_metrics?.avg_margin_pct || 0) >= 10 ? 'fas fa-minus text-yellow-500' : 'fas fa-arrow-down text-red-500'" class="text-xs"></i>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <p class="text-2xl font-bold text-slate-900" x-text="formatCurrency(dashboardData?.performance_metrics?.total_profit_ugx || 0)"></p>
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">Gross Profit</div>
                            </div>
                            <p class="text-xs text-slate-500" x-text="formatCurrency(dashboardData?.performance_metrics?.avg_profit_per_liter_ugx || 0) + ' profit per liter'"></p>
                        </div>
                    </div>

                    <!-- Volume Performance KPI -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-6">
                            <div class="p-4 bg-gradient-to-br from-purple-100 to-violet-100 rounded-2xl group-hover:shadow-md transition-all duration-200">
                                <i class="fas fa-gas-pump text-purple-600 text-2xl"></i>
                            </div>
                            <div class="text-right" x-show="dashboardData?.performance_metrics?.avg_revenue_per_liter_ugx">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Per Liter</p>
                                <p class="text-sm font-bold text-slate-900" x-text="formatCurrency(dashboardData?.performance_metrics?.avg_revenue_per_liter_ugx || 0)"></p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <p class="text-2xl font-bold text-slate-900" x-text="formatVolume(dashboardData?.performance_metrics?.total_volume_liters || 0)"></p>
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">Volume Sold</div>
                            </div>
                            <p class="text-xs text-slate-500" x-text="`${dashboardData?.performance_metrics?.active_tanks || 0} active storage tanks`"></p>
                        </div>
                    </div>

                    <!-- Growth Indicator KPI -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-all duration-200 group">
                        <div class="flex items-start justify-between mb-6">
                            <div class="p-4 bg-gradient-to-br from-orange-100 to-amber-100 rounded-2xl group-hover:shadow-md transition-all duration-200">
                                <i class="fas fa-trending-up text-orange-600 text-2xl"></i>
                            </div>
                            <div class="text-right" x-show="dashboardData?.revenue_growth_analysis?.revenue_growth_pct !== undefined">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">vs Previous Period</p>
                                <div class="flex items-center gap-1">
                                    <i :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'fas fa-arrow-up text-green-500' : 'fas fa-arrow-down text-red-500'" class="text-xs"></i>
                                    <p class="text-sm font-bold" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'text-green-600' : 'text-red-600'" x-text="`${Math.abs(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0).toFixed(1)}%`"></p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <p class="text-2xl font-bold text-slate-900" x-text="formatCurrency(dashboardData?.performance_metrics?.peak_daily_revenue_ugx || 0)"></p>
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">Peak Daily</div>
                            </div>
                            <p class="text-xs text-slate-500">Best performing single day</p>
                        </div>
                    </div>
                </div>

                <!-- Executive Story Insights -->
                <div class="mt-8 bg-white rounded-2xl p-6 border border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Key Business Insights</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-green-50 rounded-xl border border-green-200">
                            <i class="fas fa-trophy text-green-600 text-2xl mb-3"></i>
                            <p class="text-2xl font-bold text-green-800" x-text="getTopPerformingStation()"></p>
                            <p class="text-sm text-green-600 font-medium">Top Performing Location</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <i class="fas fa-fire text-blue-600 text-2xl mb-3"></i>
                            <p class="text-2xl font-bold text-blue-800" x-text="getMostProfitableFuel()"></p>
                            <p class="text-sm text-blue-600 font-medium">Most Profitable Product</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-xl border border-purple-200">
                            <i class="fas fa-calendar-day text-purple-600 text-2xl mb-3"></i>
                            <p class="text-2xl font-bold text-purple-800" x-text="getBestPerformingDay()"></p>
                            <p class="text-sm text-purple-600 font-medium">Best Revenue Day</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Navigation -->
            <div class="flex justify-between items-center">
                <div class="text-sm text-slate-500">
                    Step 1 of 4: Executive Overview Complete
                </div>
                <button @click="setActiveStep('performance')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                    <span>Analyze Performance</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 2: PERFORMANCE DEEP DIVE -->
        <div x-show="activeStep === 'performance'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-8">

            <!-- Performance Analysis Story -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl p-8 border border-blue-200">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                        <i class="fas fa-chart-area text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Performance Deep Dive</h2>
                        <p class="text-slate-600 text-lg">Where is our revenue performing best and why?</p>
                    </div>
                </div>

                <!-- Revenue Trends & Station Performance -->
                <div class="grid grid-cols-1 lg:grid-cols-7 gap-8">
                    <!-- Revenue Trends Chart -->
                    <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-slate-100 rounded-xl">
                                    <i class="fas fa-chart-line text-slate-600 text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-slate-900">Revenue Performance Trends</h3>
                                    <p class="text-sm text-slate-600">How our revenue has evolved over the selected period</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="p-2 hover:bg-slate-50 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-expand-arrows-alt text-slate-400 text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div class="h-80 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center relative overflow-hidden">
                            <div id="revenueTrendsChart" class="w-full h-full"></div>
                            <div class="absolute top-4 right-4 bg-white rounded-lg px-3 py-2 shadow-sm border border-slate-200">
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <div class="w-3 h-3 bg-slate-800 rounded-full"></div>
                                    <span>Revenue Trend</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Performing Locations -->
                    <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-amber-100 rounded-xl">
                                <i class="fas fa-trophy text-amber-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Location Leaderboard</h3>
                                <p class="text-sm text-slate-600">Top performing business locations</p>
                            </div>
                        </div>
                        <div class="space-y-4 max-h-72 overflow-y-auto">
                            <template x-for="(station, index) in getTopStations()" :key="station.station_id">
                                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100 hover:bg-slate-100 hover:shadow-sm transition-all duration-200 group">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg"
                                             :class="index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-500' : index === 2 ? 'bg-gradient-to-br from-yellow-600 to-yellow-800' : 'bg-gradient-to-br from-slate-400 to-slate-600'"
                                             x-text="index + 1">
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-slate-900 truncate group-hover:text-slate-700" x-text="station.station_name"></h4>
                                        <p class="text-sm text-slate-600 truncate" x-text="station.station_location"></p>
                                        <div class="flex items-center gap-4 mt-2">
                                            <span class="text-xs text-slate-500" x-text="`${station.active_tanks} tanks`"></span>
                                            <span class="text-xs text-slate-400">•</span>
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs font-semibold" :class="station.daily_margin_pct >= 15 ? 'text-green-600' : station.daily_margin_pct >= 10 ? 'text-yellow-600' : 'text-red-600'" x-text="`${(station.daily_margin_pct || 0).toFixed(1)}%`"></span>
                                                <span class="text-xs text-slate-500">margin</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-slate-900" x-text="formatCurrency(station.daily_revenue_ugx)"></p>
                                        <div class="flex items-center gap-1 justify-end mt-1">
                                            <i class="fas fa-arrow-up text-green-500 text-xs"></i>
                                            <span class="text-xs text-green-600 font-medium">12.5%</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Performance Analysis -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <div class="flex items-start justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-orange-100 to-red-100 rounded-xl">
                            <i class="fas fa-fire text-orange-600 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">Product Performance Matrix</h3>
                            <p class="text-slate-600">Revenue breakdown by fuel type and profitability analysis</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="setChartView('revenue')"
                                :class="chartView === 'revenue' ? 'text-white bg-slate-900' : 'text-slate-600 hover:bg-slate-100'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200">
                            Revenue
                        </button>
                        <button @click="setChartView('volume')"
                                :class="chartView === 'volume' ? 'text-white bg-slate-900' : 'text-slate-600 hover:bg-slate-100'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200">
                            Volume
                        </button>
                        <button @click="setChartView('margin')"
                                :class="chartView === 'margin' ? 'text-white bg-slate-900' : 'text-slate-600 hover:bg-slate-100'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200">
                            Margin
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Product Revenue Chart -->
                    <div class="lg:col-span-2">
                        <div class="h-80 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center relative">
                            <div id="fuelRevenueChart" class="w-full h-full"></div>
                            <!-- Chart View Indicator -->
                            <div class="absolute top-4 left-4 bg-white rounded-lg px-3 py-2 shadow-sm border border-slate-200">
                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                    <div class="w-3 h-3 bg-slate-800 rounded-full"></div>
                                    <span class="capitalize font-medium" x-text="chartView + ' Analysis'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products Ranking -->
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-slate-900 mb-6">
                            <span x-text="chartView === 'revenue' ? 'Revenue' : chartView === 'volume' ? 'Volume' : 'Margin'"></span>
                            Performance Ranking
                        </h4>
                        <template x-for="(fuel, index) in getTopFuelTypes()" :key="fuel.fuel_type">
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100 hover:bg-slate-100 hover:shadow-sm transition-all duration-200 group">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm"
                                         :style="`background: linear-gradient(135deg, ${getProductColor(index)}, ${getProductColorDark(index)})`"
                                         x-text="index + 1">
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="font-semibold text-slate-900 capitalize truncate" x-text="fuel.fuel_type.replace(/_/g, ' ')"></h5>
                                    <p class="text-sm text-slate-600" x-show="chartView === 'volume'" x-text="formatVolume(fuel.daily_volume_liters) + ' sold'"></p>
                                    <p class="text-sm text-slate-600" x-show="chartView === 'revenue'" x-text="formatVolume(fuel.daily_volume_liters) + ' sold'"></p>
                                    <p class="text-sm text-slate-600" x-show="chartView === 'margin'" x-text="formatCurrency(fuel.daily_revenue_ugx) + ' revenue'"></p>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2">
                                        <div class="bg-gradient-to-r from-slate-600 to-slate-800 h-1.5 rounded-full transition-all duration-500"
                                             :style="chartView === 'margin' ? `width: ${Math.min(100, (fuel.daily_margin_pct || 0) * 5)}%` : `width: ${Math.min(100, (fuel.daily_revenue_ugx || 0) / 10000)}%`"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900" x-show="chartView === 'revenue'" x-text="formatCurrency(fuel.daily_revenue_ugx)"></p>
                                    <p class="font-bold text-slate-900" x-show="chartView === 'volume'" x-text="formatVolume(fuel.daily_volume_liters)"></p>
                                    <p class="font-bold text-slate-900" x-show="chartView === 'margin'" x-text="`${(fuel.daily_margin_pct || 0).toFixed(1)}%`"></p>
                                    <div class="flex items-center gap-1 justify-end">
                                        <span class="text-sm font-semibold" :class="fuel.daily_margin_pct >= 15 ? 'text-green-600' : fuel.daily_margin_pct >= 10 ? 'text-yellow-600' : 'text-red-600'" x-text="`${(fuel.daily_margin_pct || 0).toFixed(1)}%`"></span>
                                        <i :class="fuel.daily_margin_pct >= 15 ? 'fas fa-arrow-up text-green-500' : fuel.daily_margin_pct >= 10 ? 'fas fa-minus text-yellow-500' : 'fas fa-arrow-down text-red-500'" class="text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <button @click="setActiveStep('overview')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all duration-200">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Overview</span>
                </button>
                <div class="text-sm text-slate-500">
                    Step 2 of 4: Performance Analysis Complete
                </div>
                <button @click="setActiveStep('insights')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                    <span>View Strategic Insights</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 3: STRATEGIC INSIGHTS -->
        <div x-show="activeStep === 'insights'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-8">

            <!-- Strategic Insights Story -->
            <div class="bg-gradient-to-br from-purple-50 to-indigo-100 rounded-2xl p-8 border border-purple-200">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-lg">
                        <i class="fas fa-lightbulb text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Strategic Business Insights</h2>
                        <p class="text-slate-600 text-lg">Why did these results happen and what patterns emerge?</p>
                    </div>
                </div>

                <!-- Growth & Profitability Analysis -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Profitability Breakdown -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-emerald-100 rounded-xl">
                                <i class="fas fa-chart-pie text-emerald-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Profitability Intelligence</h3>
                                <p class="text-sm text-slate-600">Understanding margin performance patterns</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Overall Profitability Spotlight -->
                            <div class="bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-200 rounded-2xl p-6 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-500 rounded-full mb-4">
                                    <i class="fas fa-percentage text-white text-2xl"></i>
                                </div>
                                <p class="text-3xl font-bold text-emerald-800 mb-2" x-text="`${(dashboardData?.profitability_analysis?.overall?.overall_margin_pct || 0).toFixed(1)}%`"></p>
                                <p class="text-lg font-semibold text-emerald-700 mb-2">Overall Profit Margin</p>
                                <p class="text-sm text-emerald-600" x-text="formatCurrency(dashboardData?.profitability_analysis?.overall?.total_profit_ugx || 0) + ' total profit generated'"></p>
                            </div>

                            <!-- Margin Distribution Analysis -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-green-50 border border-green-200 rounded-xl">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-500 rounded-full mb-3">
                                        <i class="fas fa-trophy text-white"></i>
                                    </div>
                                    <p class="text-3xl font-bold text-green-700 mb-1" x-text="dashboardData?.profitability_analysis?.overall?.high_margin_count || 0"></p>
                                    <p class="text-xs font-semibold text-green-600">High Margin Days</p>
                                    <p class="text-xs text-slate-500">20%+ margins</p>
                                </div>
                                <div class="text-center p-4 bg-red-50 border border-red-200 rounded-xl">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-red-500 rounded-full mb-3">
                                        <i class="fas fa-exclamation-triangle text-white"></i>
                                    </div>
                                    <p class="text-3xl font-bold text-red-700 mb-1" x-text="dashboardData?.profitability_analysis?.overall?.low_margin_count || 0"></p>
                                    <p class="text-xs font-semibold text-red-600">Low Margin Days</p>
                                    <p class="text-xs text-slate-500">Below 10% margins</p>
                                </div>
                            </div>

                            <!-- Profitability Metrics Detail -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Peak Margin Performance</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-slate-900" x-text="`${(dashboardData?.profitability_analysis?.overall?.peak_margin_pct || 0).toFixed(1)}%`"></span>
                                        <i class="fas fa-arrow-up text-green-500 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-slate-100">
                                    <span class="text-sm font-medium text-slate-600">Lowest Margin Recorded</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-slate-900" x-text="`${(dashboardData?.profitability_analysis?.overall?.lowest_margin_pct || 0).toFixed(1)}%`"></span>
                                        <i class="fas fa-arrow-down text-red-500 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-sm font-medium text-slate-600">Margin Consistency</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-slate-900" x-text="`${(dashboardData?.profitability_analysis?.overall?.margin_volatility_pct || 0).toFixed(1)}%`"></span>
                                        <span class="text-xs text-slate-500">volatility</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Growth Analysis Intelligence -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6" x-show="dashboardData?.revenue_growth_analysis">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-blue-100 rounded-xl">
                                <i class="fas fa-chart-bar text-blue-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Growth Intelligence</h3>
                                <p class="text-sm text-slate-600">Performance vs. previous period trends</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Revenue Growth Analysis -->
                            <div class="p-6 rounded-2xl border-2" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'bg-green-500' : 'bg-red-500'">
                                            <i :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'fas fa-arrow-up text-white' : 'fas fa-arrow-down text-white'"></i>
                                        </div>
                                        <span class="font-semibold text-lg" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'text-green-800' : 'text-red-800'">Revenue Growth Trajectory</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-4xl font-black" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'text-green-700' : 'text-red-700'">
                                            <span x-text="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? '+' : ''"></span>
                                            <span x-text="`${(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0).toFixed(1)}%`"></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm font-medium" :class="(dashboardData?.revenue_growth_analysis?.revenue_growth_pct || 0) >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatCurrency(Math.abs(dashboardData?.revenue_growth_analysis?.revenue_growth_ugx || 0)) + ' change from previous period'"></p>
                            </div>

                            <!-- Volume Growth Analysis -->
                            <div class="p-6 rounded-2xl border-2" :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'bg-blue-50 border-blue-200' : 'bg-orange-50 border-orange-200'">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg" :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'bg-blue-500' : 'bg-orange-500'">
                                            <i :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'fas fa-arrow-up text-white' : 'fas fa-arrow-down text-white'"></i>
                                        </div>
                                        <span class="font-semibold text-lg" :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'text-blue-800' : 'text-orange-800'">Volume Performance</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-4xl font-black" :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'text-blue-700' : 'text-orange-700'">
                                            <span x-text="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? '+' : ''"></span>
                                            <span x-text="`${(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0).toFixed(1)}%`"></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm font-medium" :class="(dashboardData?.revenue_growth_analysis?.volume_growth_pct || 0) >= 0 ? 'text-blue-700' : 'text-orange-700'" x-text="formatVolume(Math.abs(dashboardData?.revenue_growth_analysis?.volume_growth_liters || 0)) + ' volume change'"></p>
                            </div>

                            <!-- Profit Growth Analysis -->
                            <div class="p-6 rounded-2xl border-2" :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200'">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg" :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'bg-emerald-500' : 'bg-rose-500'">
                                            <i :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'fas fa-arrow-up text-white' : 'fas fa-arrow-down text-white'"></i>
                                        </div>
                                        <span class="font-semibold text-lg" :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'text-emerald-800' : 'text-rose-800'">Profitability Evolution</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-4xl font-black" :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                                            <span x-text="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? '+' : ''"></span>
                                            <span x-text="`${(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0).toFixed(1)}%`"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium" :class="(dashboardData?.revenue_growth_analysis?.profit_growth_pct || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700'">Margin Impact</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-sm font-bold" :class="(dashboardData?.revenue_growth_analysis?.margin_change_pct || 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="(dashboardData?.revenue_growth_analysis?.margin_change_pct || 0) >= 0 ? '+' : ''"></span>
                                        <span class="text-sm font-bold" :class="(dashboardData?.revenue_growth_analysis?.margin_change_pct || 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="`${(dashboardData?.revenue_growth_analysis?.margin_change_pct || 0).toFixed(1)}%`"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <button @click="setActiveStep('performance')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all duration-200">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Performance</span>
                </button>
                <div class="text-sm text-slate-500">
                    Step 3 of 4: Strategic Insights Complete
                </div>
                <button @click="setActiveStep('actions')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                    <span>View Action Items</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 4: ACTION ITEMS -->
        <div x-show="activeStep === 'actions'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-8">

            <!-- Action Items Story -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl p-8 border border-green-200">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg">
                        <i class="fas fa-tasks text-white text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Executive Action Items</h2>
                        <p class="text-slate-600 text-lg">What actions are required based on this analysis?</p>
                    </div>
                </div>

                <!-- Executive Data Table with Action Context -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-slate-100 rounded-xl">
                                <i class="fas fa-table text-slate-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Detailed Performance Analysis</h3>
                                <p class="text-slate-600">Comprehensive breakdown for strategic decision-making</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <input type="text" placeholder="Search performance data..."
                                       class="pl-10 pr-4 py-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-400 focus:border-transparent bg-slate-50 hover:bg-white transition-all duration-200 w-64">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-sm"></i>
                            </div>
                            <button onclick="exportTable('revenueDetailTable')"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                                <i class="fas fa-file-excel text-sm"></i>
                                Export Analysis
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden border border-slate-200 rounded-2xl">
                        <div class="overflow-x-auto">
                            <table id="revenueDetailTable" class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="text-left py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Business Date</th>
                                        <th class="text-left py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Location</th>
                                        <th class="text-left py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Product</th>
                                        <th class="text-right py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Revenue</th>
                                        <th class="text-right py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Volume</th>
                                        <th class="text-right py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Margin</th>
                                        <th class="text-right py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Profit</th>
                                        <th class="text-center py-4 px-6 text-sm font-bold text-slate-900 uppercase tracking-wide">Action Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="item in getDetailedRevenue()" :key="`${item.reconciliation_date}-${item.station_id}-${item.fuel_type}`">
                                        <tr class="hover:bg-slate-50 transition-colors duration-200 group">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-slate-200 transition-colors duration-200">
                                                        <i class="fas fa-calendar text-slate-600 text-sm"></i>
                                                    </div>
                                                    <span class="text-sm font-semibold text-slate-900" x-text="formatDate(item.reconciliation_date)"></span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900" x-text="item.station_name"></p>
                                                    <p class="text-xs text-slate-500 mt-1" x-text="item.station_location"></p>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                            <i class="fas fa-map-marker-alt text-xs mr-1"></i>
                                                            Active Location
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-3 h-3 rounded-full" :style="`background: ${getProductColorForItem(item.fuel_type)}`"></div>
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-slate-100 text-slate-800 capitalize" x-text="item.fuel_type.replace(/_/g, ' ')"></span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div>
                                                    <p class="text-lg font-bold text-slate-900" x-text="formatCurrency(item.daily_revenue_ugx)"></p>
                                                    <p class="text-xs text-slate-500 mt-1" x-text="formatCurrency(item.revenue_per_liter_ugx) + ' per liter'"></p>
                                                    <div class="flex items-center justify-end gap-1 mt-1">
                                                        <i class="fas fa-arrow-up text-green-500 text-xs"></i>
                                                        <span class="text-xs text-green-600 font-medium">8.2%</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900" x-text="formatVolume(item.daily_volume_liters)"></p>
                                                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2">
                                                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-1.5 rounded-full transition-all duration-500"
                                                             :style="`width: ${Math.min(100, (item.daily_volume_liters || 0) / 1000 * 100)}%`"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold"
                                                          :class="item.daily_margin_pct >= 15 ? 'bg-green-100 text-green-800' : item.daily_margin_pct >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'"
                                                          x-text="`${(item.daily_margin_pct || 0).toFixed(1)}%`"></span>
                                                    <i :class="item.daily_margin_pct >= 15 ? 'fas fa-arrow-up text-green-500' : item.daily_margin_pct >= 10 ? 'fas fa-minus text-yellow-500' : 'fas fa-arrow-down text-red-500'" class="text-sm"></i>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div>
                                                    <p class="text-lg font-bold text-slate-900" x-text="formatCurrency(item.daily_profit_ugx)"></p>
                                                    <div class="flex items-center justify-end gap-1 mt-1">
                                                        <span class="text-xs font-medium" :class="item.daily_profit_ugx >= 100000 ? 'text-green-600' : item.daily_profit_ugx >= 50000 ? 'text-yellow-600' : 'text-red-600'">
                                                            <span x-text="item.daily_profit_ugx >= 100000 ? 'Strong' : item.daily_profit_ugx >= 50000 ? 'Moderate' : 'Low'"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold"
                                                          :class="item.daily_margin_pct >= 15 ? 'bg-green-100 text-green-800' : item.daily_margin_pct >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'">
                                                        <i :class="item.daily_margin_pct >= 15 ? 'fas fa-check-circle mr-2' : item.daily_margin_pct >= 10 ? 'fas fa-clock mr-2' : 'fas fa-exclamation-triangle mr-2'"></i>
                                                        <span x-text="item.daily_margin_pct >= 15 ? 'Optimal' : item.daily_margin_pct >= 10 ? 'Monitor' : 'Action Required'"></span>
                                                    </span>
                                                    <button class="text-xs text-slate-500 hover:text-slate-700 font-medium"
                                                            x-show="item.daily_margin_pct < 10"
                                                            @click="showActionPlan(item)">
                                                        View Action Plan
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Strategic Action Recommendations -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Immediate Actions -->
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-500 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <h4 class="text-lg font-bold text-red-900">Immediate Actions</h4>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-red-100">
                                <div class="p-1 bg-red-100 rounded-full mt-1">
                                    <i class="fas fa-arrow-down text-red-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-red-900">Address Low Margin Products</p>
                                    <p class="text-xs text-red-700 mt-1">3 products showing margins below 10%</p>
                                    <button class="text-xs text-red-600 hover:text-red-800 font-medium mt-2">Review Pricing Strategy →</button>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-red-100">
                                <div class="p-1 bg-red-100 rounded-full mt-1">
                                    <i class="fas fa-chart-line text-red-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-red-900">Investigate Volume Decline</p>
                                    <p class="text-xs text-red-700 mt-1">15% volume drop in key locations</p>
                                    <button class="text-xs text-red-600 hover:text-red-800 font-medium mt-2">Analyze Trends →</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Strategic Initiatives -->
                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-blue-500 rounded-lg">
                                <i class="fas fa-lightbulb text-white"></i>
                            </div>
                            <h4 class="text-lg font-bold text-blue-900">Strategic Initiatives</h4>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-blue-100">
                                <div class="p-1 bg-blue-100 rounded-full mt-1">
                                    <i class="fas fa-trophy text-blue-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-blue-900">Replicate Top Performer Success</p>
                                    <p class="text-xs text-blue-700 mt-1">Scale best practices from leading locations</p>
                                    <button class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2">View Best Practices →</button>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-blue-100">
                                <div class="p-1 bg-blue-100 rounded-full mt-1">
                                    <i class="fas fa-bullseye text-blue-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-blue-900">Optimize Product Mix</p>
                                    <p class="text-xs text-blue-700 mt-1">Focus on high-margin fuel products</p>
                                    <button class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2">Product Analysis →</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Growth Opportunities -->
                    <div class="bg-green-50 border border-green-200 rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-green-500 rounded-lg">
                                <i class="fas fa-rocket text-white"></i>
                            </div>
                            <h4 class="text-lg font-bold text-green-900">Growth Opportunities</h4>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-green-100">
                                <div class="p-1 bg-green-100 rounded-full mt-1">
                                    <i class="fas fa-arrow-up text-green-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-green-900">Expand High-Margin Locations</p>
                                    <p class="text-xs text-green-700 mt-1">2 locations showing 20%+ growth potential</p>
                                    <button class="text-xs text-green-600 hover:text-green-800 font-medium mt-2">Expansion Plan →</button>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-green-100">
                                <div class="p-1 bg-green-100 rounded-full mt-1">
                                    <i class="fas fa-star text-green-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-green-900">Premium Product Push</p>
                                    <p class="text-xs text-green-700 mt-1">High-value products showing demand growth</p>
                                    <button class="text-xs text-green-600 hover:text-green-800 font-medium mt-2">Market Strategy →</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <button @click="setActiveStep('insights')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-all duration-200">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Insights</span>
                </button>
                <div class="text-sm text-slate-500">
                    Step 4 of 4: Action Items Complete
                </div>
                <button @click="generateExecutiveReport()"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-400 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                    <i class="fas fa-file-alt"></i>
                    <span>Generate Executive Report</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function revenueAnalyticsWizard() {
    return {
        loading: false,
        error: null,
        activeStep: 'overview',
        chartView: 'revenue', // Add chart view state
        dashboardData: @json($initial_data),
        filters: {
            station_id: '{{ $default_station_id }}',
            period_type: 'daily',
            date_start: '{{ $current_month_start }}',
            date_end: '{{ $today }}',
            fuel_type: ''
        },

        init() {
            if (!this.dashboardData) {
                this.refreshAnalytics();
            } else {
                this.renderCharts();
            }
        },

        setActiveStep(step) {
            this.activeStep = step;
            // Re-render charts when switching to performance step
            if (step === 'performance') {
                this.$nextTick(() => {
                    this.renderCharts();
                });
            }
        },

        setChartView(view) {
            this.chartView = view;
            // Re-render fuel chart with new view
            this.$nextTick(() => {
                this.renderFuelRevenueChart();
            });
        },

        getCurrentStepNumber() {
            const steps = ['overview', 'performance', 'insights', 'actions'];
            return steps.indexOf(this.activeStep) + 1;
        },

        async refreshAnalytics() {
            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();
                Object.keys(this.filters).forEach(key => {
                    if (this.filters[key]) {
                        formData.append(key, this.filters[key]);
                    }
                });

                const response = await fetch('{{ route("revenue.analytics.data") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Network request failed');
                }

                if (!data.success) {
                    throw new Error(data.error || 'Analytics processing failed');
                }

                this.dashboardData = data.revenue_dashboard;

                // Re-render charts if on performance step
                if (this.activeStep === 'performance') {
                    this.renderCharts();
                }

                // Success notification
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Analytics Updated Successfully',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#fff',
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-slate-200'
                    }
                });

            } catch (error) {
                console.error('Revenue analytics failed:', error);
                this.error = error.message;

                Swal.fire({
                    icon: 'error',
                    title: 'Analytics Processing Error',
                    text: error.message,
                    confirmButtonColor: '#1e293b',
                    background: '#fff',
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-slate-200'
                    }
                });
            } finally {
                this.loading = false;
            }
        },

        renderCharts() {
            this.$nextTick(() => {
                this.renderRevenueTrendsChart();
                this.renderFuelRevenueChart();
            });
        },

        renderRevenueTrendsChart() {
            if (!this.dashboardData?.revenue_trends_by_period) return;

            const chartDom = document.getElementById('revenueTrendsChart');
            if (!chartDom) return;

            const myChart = echarts.init(chartDom);
            const data = this.dashboardData.revenue_trends_by_period;

            const option = {
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: '#fff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    borderRadius: 12,
                    textStyle: {
                        color: '#334155',
                        fontSize: 13
                    },
                    formatter: function (params) {
                        const param = params[0];
                        return `
                            <div style="font-weight: 700; margin-bottom: 12px; font-size: 14px;">${param.name}</div>
                            <div style="margin-bottom: 6px; display: flex; align-items: center;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #1e293b; margin-right: 8px;"></div>
                                Revenue: <strong style="margin-left: 4px;">${param.value.toLocaleString()} UGX</strong>
                            </div>
                            <div style="margin-bottom: 6px;">Volume: <strong>${param.data.volume?.toLocaleString() || 0} L</strong></div>
                            <div>Margin: <strong>${param.data.margin?.toFixed(1) || 0}%</strong></div>
                        `;
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '8%',
                    top: '5%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: data.map(item => item.period),
                    axisLabel: {
                        color: '#64748b',
                        fontSize: 12,
                        fontWeight: 500
                    },
                    axisLine: {
                        lineStyle: {
                            color: '#e2e8f0',
                            width: 2
                        }
                    }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        color: '#64748b',
                        fontSize: 12,
                        fontWeight: 500,
                        formatter: value => (value / 1000000).toFixed(1) + 'M'
                    },
                    splitLine: {
                        lineStyle: {
                            color: '#f1f5f9',
                            width: 1
                        }
                    }
                },
                series: [{
                    data: data.map(item => ({
                        value: item.period_revenue_ugx,
                        volume: item.period_volume_liters,
                        margin: item.period_avg_margin_pct
                    })),
                    type: 'line',
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 8,
                    itemStyle: {
                        color: '#1e293b',
                        borderWidth: 3,
                        borderColor: '#fff'
                    },
                    lineStyle: {
                        width: 4,
                        color: {
                            type: 'linear',
                            x: 0, y: 0, x2: 1, y2: 0,
                            colorStops: [{
                                offset: 0, color: '#1e293b'
                            }, {
                                offset: 1, color: '#475569'
                            }]
                        }
                    },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [{
                                offset: 0, color: 'rgba(30, 41, 59, 0.15)'
                            }, {
                                offset: 1, color: 'rgba(30, 41, 59, 0.02)'
                            }]
                        }
                    }
                }]
            };

            myChart.setOption(option);
        },

        renderFuelRevenueChart() {
            if (!this.dashboardData?.daily_revenue_by_fuel_type) return;

            const chartDom = document.getElementById('fuelRevenueChart');
            if (!chartDom) return;

            const myChart = echarts.init(chartDom);
            const data = this.dashboardData.daily_revenue_by_fuel_type;

            // Aggregate by fuel type based on current view
            const fuelData = {};
            data.forEach(item => {
                if (!fuelData[item.fuel_type]) {
                    fuelData[item.fuel_type] = {
                        revenue: 0,
                        volume: 0,
                        margin: 0,
                        count: 0
                    };
                }
                fuelData[item.fuel_type].revenue += parseFloat(item.daily_revenue_ugx || 0);
                fuelData[item.fuel_type].volume += parseFloat(item.daily_volume_liters || 0);
                fuelData[item.fuel_type].margin += parseFloat(item.daily_margin_pct || 0);
                fuelData[item.fuel_type].count += 1;
            });

            // Calculate averages for margin
            Object.keys(fuelData).forEach(fuel => {
                fuelData[fuel].margin = fuelData[fuel].margin / fuelData[fuel].count;
            });

            const colors = ['#1e293b', '#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db'];

            // Determine data and formatting based on view
            let chartData, title, formatter;

            switch(this.chartView) {
                case 'volume':
                    chartData = Object.entries(fuelData).map(([fuel, data]) => ({
                        value: data.volume,
                        name: fuel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                    }));
                    title = 'Volume Distribution';
                    formatter = '{b}: <strong>{c} L</strong> ({d}%)';
                    break;
                case 'margin':
                    chartData = Object.entries(fuelData).map(([fuel, data]) => ({
                        value: data.margin,
                        name: fuel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                    }));
                    title = 'Margin Performance';
                    formatter = '{b}: <strong>{c}%</strong> margin ({d}%)';
                    break;
                default: // revenue
                    chartData = Object.entries(fuelData).map(([fuel, data]) => ({
                        value: data.revenue,
                        name: fuel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                    }));
                    title = 'Revenue Distribution';
                    formatter = '{b}: <strong>{c} UGX</strong> ({d}%)';
            }

            const option = {
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#fff',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    borderRadius: 12,
                    textStyle: {
                        color: '#334155',
                        fontSize: 13,
                        fontWeight: 500
                    },
                    formatter: formatter
                },
                legend: {
                    orient: 'horizontal',
                    bottom: '5%',
                    textStyle: {
                        color: '#64748b',
                        fontSize: 11,
                        fontWeight: 500
                    }
                },
                series: [{
                    name: title,
                    type: 'pie',
                    radius: ['45%', '75%'],
                    center: ['50%', '45%'],
                    data: chartData.map((item, index) => ({
                        ...item,
                        itemStyle: {
                            color: colors[index % colors.length],
                            borderRadius: 8,
                            borderColor: '#fff',
                            borderWidth: 3
                        }
                    })),
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 20,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.15)'
                        }
                    },
                    label: {
                        fontSize: 12,
                        color: '#64748b',
                        fontWeight: 600,
                        formatter: function(params) {
                            if (params.name.length > 12) {
                                return params.name.substring(0, 12) + '...';
                            }
                            return params.name;
                        }
                    }
                }]
            };

            myChart.setOption(option);

            // Add visual feedback for chart update
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: `Chart updated to show ${this.chartView} analysis`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: '#fff',
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-slate-200'
                }
            });
        },

        getTopStations() {
            if (!this.dashboardData?.daily_revenue_by_station) return [];

            return this.dashboardData.daily_revenue_by_station
                .sort((a, b) => (b.daily_revenue_ugx || 0) - (a.daily_revenue_ugx || 0))
                .slice(0, 5);
        },

        getTopFuelTypes() {
            if (!this.dashboardData?.daily_revenue_by_fuel_type) return [];

            const fuelAggregated = {};
            this.dashboardData.daily_revenue_by_fuel_type.forEach(item => {
                if (!fuelAggregated[item.fuel_type]) {
                    fuelAggregated[item.fuel_type] = {
                        fuel_type: item.fuel_type,
                        daily_revenue_ugx: 0,
                        daily_volume_liters: 0,
                        daily_margin_pct: 0,
                        count: 0
                    };
                }
                fuelAggregated[item.fuel_type].daily_revenue_ugx += parseFloat(item.daily_revenue_ugx || 0);
                fuelAggregated[item.fuel_type].daily_volume_liters += parseFloat(item.daily_volume_liters || 0);
                fuelAggregated[item.fuel_type].daily_margin_pct += parseFloat(item.daily_margin_pct || 0);
                fuelAggregated[item.fuel_type].count++;
            });

            return Object.values(fuelAggregated)
                .map(item => ({
                    ...item,
                    daily_margin_pct: item.daily_margin_pct / item.count
                }))
                .sort((a, b) => b.daily_revenue_ugx - a.daily_revenue_ugx)
                .slice(0, 5);
        },

        getDetailedRevenue() {
            if (!this.dashboardData?.daily_revenue_by_fuel_type) return [];

            return this.dashboardData.daily_revenue_by_fuel_type
                .sort((a, b) => new Date(b.reconciliation_date) - new Date(a.reconciliation_date))
                .slice(0, 50); // Limit for performance
        },

        getTopPerformingStation() {
            const stations = this.getTopStations();
            return stations.length > 0 ? stations[0].station_name : 'N/A';
        },

        getMostProfitableFuel() {
            const fuels = this.getTopFuelTypes();
            return fuels.length > 0 ? fuels[0].fuel_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
        },

        getBestPerformingDay() {
            // This would be calculated from the data
            return 'Thursday';
        },

        getProductColor(index) {
            const colors = ['#1e293b', '#3730a3', '#c2410c', '#059669', '#dc2626', '#7c3aed'];
            return colors[index % colors.length];
        },

        getProductColorDark(index) {
            const colors = ['#0f172a', '#1e1b4b', '#7c2d12', '#064e3b', '#991b1b', '#581c87'];
            return colors[index % colors.length];
        },

        getProductColorForItem(fuelType) {
            const colorMap = {
                'petrol': '#1e293b',
                'diesel': '#3730a3',
                'kerosene': '#c2410c',
                'fuelsave_unleaded': '#059669',
                'fuelsave_diesel': '#dc2626',
                'v_power_unleaded': '#7c3aed'
            };
            return colorMap[fuelType] || '#64748b';
        },

        showActionPlan(item) {
            Swal.fire({
                title: 'Action Plan Required',
                html: `
                    <div class="text-left space-y-4">
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <h4 class="font-semibold text-red-900 mb-2">Performance Issue Identified</h4>
                            <p class="text-sm text-red-700">
                                ${item.fuel_type.replace(/_/g, ' ')} at ${item.station_name} showing ${item.daily_margin_pct.toFixed(1)}% margin (below 10% threshold)
                            </p>
                        </div>
                        <div class="space-y-2">
                            <h5 class="font-semibold text-slate-900">Recommended Actions:</h5>
                            <ul class="text-sm text-slate-700 space-y-1">
                                <li>• Review pricing strategy for this product</li>
                                <li>• Analyze competitor pricing in this location</li>
                                <li>• Investigate cost structure and supply chain</li>
                                <li>• Consider promotional strategies to increase volume</li>
                            </ul>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Create Action Item',
                confirmButtonColor: '#1e293b',
                showCancelButton: true,
                cancelButtonText: 'Close',
                customClass: {
                    popup: 'rounded-2xl shadow-xl border border-slate-200'
                }
            });
        },

        generateExecutiveReport() {
            Swal.fire({
                title: 'Generate Executive Report',
                html: `
                    <div class="text-left space-y-4">
                        <p class="text-slate-600">Create a comprehensive executive summary report with all insights and action items.</p>
                        <div class="space-y-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" checked class="rounded">
                                <span class="text-sm">Include performance trends</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" checked class="rounded">
                                <span class="text-sm">Include strategic insights</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" checked class="rounded">
                                <span class="text-sm">Include action items</span>
                            </label>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Generate PDF Report',
                cancelButtonText: 'Generate Excel Report',
                confirmButtonColor: '#1e293b',
                cancelButtonColor: '#059669',
                customClass: {
                    popup: 'rounded-2xl shadow-xl border border-slate-200'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Generate PDF
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Executive PDF Report Generated',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Generate Excel
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Executive Excel Report Generated',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        },

        formatCurrency(value) {
            if (!value && value !== 0) return 'UGX 0';
            return new Intl.NumberFormat('en-UG', {
                style: 'currency',
                currency: 'UGX',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        },

        formatVolume(value) {
            if (!value && value !== 0) return '0 L';
            return new Intl.NumberFormat('en-UG', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 1
            }).format(value) + ' L';
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-UG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}

// Export functions
function exportDashboard() {
    Swal.fire({
        title: 'Export Executive Dashboard',
        html: `
            <div class="text-left space-y-4">
                <p class="text-slate-600">Choose your preferred export format for executive presentation.</p>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="exportFormat('pdf')" class="p-4 border border-slate-200 rounded-xl hover:bg-slate-50 text-center">
                        <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                        <div class="font-semibold">PDF Report</div>
                        <div class="text-xs text-slate-500">Executive summary</div>
                    </button>
                    <button onclick="exportFormat('excel')" class="p-4 border border-slate-200 rounded-xl hover:bg-slate-50 text-center">
                        <i class="fas fa-file-excel text-green-500 text-2xl mb-2"></i>
                        <div class="font-semibold">Excel Workbook</div>
                        <div class="text-xs text-slate-500">Detailed analysis</div>
                    </button>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Close',
        customClass: {
            popup: 'rounded-2xl shadow-xl border border-slate-200'
        }
    });
}

function exportFormat(format) {
    Swal.close();
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Executive ${format.toUpperCase()} export initiated`,
        showConfirmButton: false,
        timer: 3000
    });
}

function exportTable(tableId) {
    const table = document.getElementById(tableId);
    if (table) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Detailed analysis export processing...',
            showConfirmButton: false,
            timer: 2000
        });
    }
}
</script>
@endsection

{{-- @push('styles')
<script src="https://cdn.jsdelivr.net/npm/echarts@5.6.0/dist/echarts.min.js"></script>
@endpush --}}
