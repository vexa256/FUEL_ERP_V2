@extends('layouts.app')

@section('title', 'Executive P&L Dashboard')

@section('breadcrumb')
<span class="text-muted-foreground">Executive Reports</span>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">P&L Dashboard</span>
@endsection

@section('page-header')
<div>
    <h1 class="text-2xl font-semibold text-foreground">Executive P&L Dashboard</h1>
    <p class="text-sm text-muted-foreground mt-1">Real-time business performance insights and actionable intelligence</p>
</div>
<div class="flex items-center gap-3">
    <div class="hidden sm:flex items-center gap-2 text-xs text-muted-foreground bg-green-50 px-3 py-1 rounded-full border border-green-200">
        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
        <span>Live Data</span>
    </div>
    <button id="export-btn" class="btn btn-secondary gap-2 shadow-sm" disabled>
        <i class="fas fa-download h-4 w-4"></i>
        <span class="hidden sm:inline">Export Report</span>
    </button>
    <button id="refresh-btn" class="btn btn-primary gap-2 shadow-sm">
        <i class="fas fa-sync-alt h-4 w-4"></i>
        <span class="hidden sm:inline">Refresh</span>
    </button>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="profitLossWizard()" x-init="init()">
    <!-- Wizard Navigation -->
    <div class="card p-6 shadow-sm border">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-foreground">Business Intelligence Generator</h2>
            <div class="flex items-center gap-3">
                <div class="text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    <span x-text="loading ? '⏳ Processing...' : '✅ Ready'"></span>
                </div>
                <div class="text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded-full">
                    Executive Mode
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
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 1 ? 'text-foreground' : 'text-muted-foreground'">Overview</p>
                        <p class="text-xs text-muted-foreground">Key metrics</p>
                    </div>
                </div>
                <div class="flex-1 h-px bg-border mx-4"></div>
            </div>
            <div class="flex items-center flex-1">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                         :class="currentStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 2 ? 'text-foreground' : 'text-muted-foreground'">Analytics</p>
                        <p class="text-xs text-muted-foreground">Visual insights</p>
                    </div>
                </div>
                <div class="flex-1 h-px bg-border mx-4"></div>
            </div>
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                         :class="currentStep >= 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                        <i class="fas fa-table"></i>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-medium" :class="currentStep >= 3 ? 'text-foreground' : 'text-muted-foreground'">Details</p>
                        <p class="text-xs text-muted-foreground">Raw data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Filters -->
        <div x-show="currentStep === 0" class="space-y-4">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-filter h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Configure Report Parameters</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 1 of 4
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-4">Generate comprehensive business intelligence reports with visual insights and actionable recommendations.</p>

            <form @submit.prevent="nextStep" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <label for="station_id" class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-gas-pump h-3 w-3"></i>
                        Station
                    </label>
                    <select name="station_id" id="station_id" x-model="filters.station_id" class="select w-full" required>
                        <option value="">Select Station</option>
                        @if($available_stations ?? false)
                            @foreach($available_stations as $station)
                                <option value="{{ $station->id }}" @if($default_station_id == $station->id) selected @endif>
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <p class="text-xs text-muted-foreground">Business unit for analysis</p>
                </div>

                <div class="space-y-2">
                    <label for="date_start" class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-calendar-alt h-3 w-3"></i>
                        Start Date
                    </label>
                    <input type="date" name="date_start" id="date_start" x-model="filters.date_start"
                           class="input w-full" max="{{ $today ?? date('Y-m-d') }}"
                           value="{{ $today ?? date('Y-m-d') }}" required>
                    <p class="text-xs text-muted-foreground">Report start date</p>
                </div>

                <div class="space-y-2">
                    <label for="date_end" class="text-sm font-medium text-foreground flex items-center gap-2">
                        <i class="fas fa-calendar-check h-3 w-3"></i>
                        End Date
                    </label>
                    <input type="date" name="date_end" id="date_end" x-model="filters.date_end"
                           class="input w-full" max="{{ $today ?? date('Y-m-d') }}"
                           value="{{ $today ?? date('Y-m-d') }}" required>
                    <p class="text-xs text-muted-foreground">Report end date (max 90 days)</p>
                </div>

                <div class="md:col-span-3 flex justify-end gap-2 pt-4">
                    <button type="button" @click="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-undo h-4 w-4 mr-2"></i>
                        Reset
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="!filters.station_id || !filters.date_start || !filters.date_end">
                        <i class="fas fa-arrow-right h-4 w-4 mr-2"></i>
                        Generate Analytics
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: Summary Overview -->
        <div x-show="currentStep === 1 && reportData" class="space-y-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-chart-bar h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Financial Overview</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 2 of 4
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-6">Key performance indicators and summary metrics for the selected period.</p>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-4 shadow-sm border bg-gradient-to-r from-green-50 to-green-100 border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Total Revenue</p>
                            <p class="text-xl font-bold text-green-700" x-text="formatCurrency(reportData?.data?.summary_totals?.total_revenue_ugx || 0)"></p>
                            <p class="text-xs text-green-600 mt-1" x-text="`${reportData?.data?.summary_totals?.reconciliation_count || 0} transactions`"></p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-coins h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-red-50 to-red-100 border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-red-600 font-medium uppercase tracking-wide">Total COGS</p>
                            <p class="text-xl font-bold text-red-700" x-text="formatCurrency(reportData?.data?.summary_totals?.total_cogs_ugx || 0)"></p>
                            <p class="text-xs text-red-600 mt-1">Cost of goods sold</p>
                        </div>
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-receipt h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-blue-50 to-blue-100 border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Gross Profit</p>
                            <p class="text-xl font-bold" :class="(reportData?.data?.summary_totals?.total_profit_ugx || 0) >= 0 ? 'text-blue-700' : 'text-red-600'"
                               x-text="formatCurrency(reportData?.data?.summary_totals?.total_profit_ugx || 0)"></p>
                            <p class="text-xs text-blue-600 mt-1" x-text="formatPercentage(reportData?.data?.summary_totals?.overall_profit_margin_pct || 0)"></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-line h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm border bg-gradient-to-r from-purple-50 to-purple-100 border-purple-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-purple-600 font-medium uppercase tracking-wide">Volume Sold</p>
                            <p class="text-xl font-bold text-purple-700" x-text="formatVolume(reportData?.data?.summary_totals?.total_volume_liters || 0)"></p>
                            <p class="text-xs text-purple-600 mt-1" x-text="`${reportData?.data?.summary_totals?.unique_tanks || 0} tanks active`"></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-tint h-6 w-6 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-6 shadow-sm border">
                    <h4 class="font-medium text-foreground mb-3 flex items-center gap-2">
                        <i class="fas fa-lightbulb h-4 w-4 text-yellow-500"></i>
                        Key Insights
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Revenue Performance</p>
                                <p class="text-xs text-muted-foreground" x-text="`Average daily revenue: ${formatCurrency((reportData?.data?.summary_totals?.total_revenue_ugx || 0) / Math.max(1, reportData?.meta?.date_range?.days_included || 1))}`"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Profit Efficiency</p>
                                <p class="text-xs text-muted-foreground" x-text="`Profit per liter: ${formatCurrency((reportData?.data?.summary_totals?.total_profit_ugx || 0) / Math.max(1, reportData?.data?.summary_totals?.total_volume_liters || 1))}/L`"></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-muted/30 rounded-lg">
                            <div class="w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Operational Health</p>
                                <p class="text-xs text-muted-foreground" x-text="`${reportData?.data?.kpi_metrics?.operational_efficiency_score || 0}% efficiency score`"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-6 shadow-sm border">
                    <h4 class="font-medium text-foreground mb-3 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle h-4 w-4 text-orange-500"></i>
                        Variance Analysis
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                            <span class="text-sm text-foreground">Average Variance</span>
                            <span class="text-sm font-mono" :class="(reportData?.data?.kpi_metrics?.avg_variance_pct || 0) > 5 ? 'text-red-600' :
                                      (reportData?.data?.kpi_metrics?.avg_variance_pct || 0) > 2 ? 'text-yellow-600' : 'text-green-600'"
                                  x-text="formatPercentage(reportData?.data?.kpi_metrics?.avg_variance_pct || 0)"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                            <span class="text-sm text-foreground">Max Variance</span>
                            <span class="text-sm font-mono" :class="(reportData?.data?.kpi_metrics?.max_variance_pct || 0) > 5 ? 'text-red-600' :
                                      (reportData?.data?.kpi_metrics?.max_variance_pct || 0) > 2 ? 'text-yellow-600' : 'text-green-600'"
                                  x-text="formatPercentage(reportData?.data?.kpi_metrics?.max_variance_pct || 0)"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                            <span class="text-sm text-foreground">Data Quality</span>
                            <span class="text-sm font-mono text-blue-600" x-text="formatPercentage(reportData?.data?.kpi_metrics?.data_quality_score_pct || 0)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button @click="previousStep" class="btn btn-secondary">
                    <i class="fas fa-arrow-left h-4 w-4 mr-2"></i>
                    Previous
                </button>
                <button @click="nextStep" class="btn btn-primary">
                    <i class="fas fa-arrow-right h-4 w-4 mr-2"></i>
                    View Analytics
                </button>
            </div>
        </div>

        <!-- Step 3: Detailed Analytics -->
        <div x-show="currentStep === 2 && reportData" class="space-y-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-chart-pie h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Detailed Analytics</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 3 of 4
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-6">Interactive charts and breakdowns showing performance trends and fuel type analysis.</p>

            <!-- Executive Dashboard Charts -->
            <div class="space-y-6">

                <!-- PRIMARY: Money Flow Waterfall Chart -->
                <div class="card p-6 shadow-sm border">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-medium text-foreground flex items-center gap-2">
                            <i class="fas fa-chart-waterfall h-4 w-4 text-blue-500"></i>
                            Money Flow: Where Your Revenue Goes
                        </h4>
                        <div class="text-xs text-muted-foreground bg-blue-50 px-3 py-1 rounded-full">
                            Shows exactly how much money you keep
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mb-4">Visual breakdown of revenue → costs → profit flow</p>
                    <div id="money-flow-chart" class="w-full h-96"></div>
                </div>

                <!-- SECONDARY: Performance Speedometer -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="card p-6 shadow-sm border">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-medium text-foreground flex items-center gap-2">
                                <i class="fas fa-tachometer-alt h-4 w-4 text-green-500"></i>
                                Station Health Score
                            </h4>
                            <div class="text-xs text-muted-foreground bg-green-50 px-3 py-1 rounded-full">
                                Overall performance grade
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground mb-4">Combines profit margin, variance control, and efficiency</p>
                        <div id="performance-gauge" class="w-full h-64"></div>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                            <div>
                                <div class="w-3 h-3 bg-red-500 rounded-full mx-auto mb-1"></div>
                                <span class="text-muted-foreground">Needs Work</span>
                            </div>
                            <div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mx-auto mb-1"></div>
                                <span class="text-muted-foreground">Good</span>
                            </div>
                            <div>
                                <div class="w-3 h-3 bg-green-500 rounded-full mx-auto mb-1"></div>
                                <span class="text-muted-foreground">Excellent</span>
                            </div>
                        </div>
                    </div>

                    <!-- TERTIARY: Issue Alert Heatmap -->
                    <div class="card p-6 shadow-sm border">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-medium text-foreground flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle h-4 w-4 text-orange-500"></i>
                                Tank Problem Areas
                            </h4>
                            <div class="text-xs text-muted-foreground bg-orange-50 px-3 py-1 rounded-full">
                                Red = needs attention
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground mb-4">Visual map showing which tanks have variance issues</p>
                        <div id="tank-heatmap" class="w-full h-64"></div>
                        <div class="mt-4 flex justify-between text-xs text-muted-foreground">
                            <span>Low Variance</span>
                            <span>High Variance</span>
                        </div>
                    </div>
                </div>

                <!-- QUATERNARY: Simple Profit Trend -->
                <div class="card p-6 shadow-sm border">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-medium text-foreground flex items-center gap-2">
                            <i class="fas fa-chart-line h-4 w-4 text-purple-500"></i>
                            Daily Profit Pattern
                        </h4>
                        <div class="text-xs text-muted-foreground bg-purple-50 px-3 py-1 rounded-full">
                            Are you making more or less money over time?
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground mb-4">Simple line showing if profits are trending up or down</p>
                    <div id="profit-trend-chart" class="w-full h-64"></div>
                </div>

                <!-- BUSINESS INSIGHTS PANEL -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="card p-4 shadow-sm border bg-gradient-to-r from-green-50 to-green-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-thumbs-up h-4 w-4 text-white"></i>
                            </div>
                            <h5 class="font-medium text-green-800">What's Working</h5>
                        </div>
                        <div class="space-y-2 text-sm text-green-700">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check h-3 w-3"></i>
                                <span x-text="`Top fuel: ${getBestFuelType()}`"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check h-3 w-3"></i>
                                <span x-text="`${getGoodVarianceTanks()} tanks performing well`"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check h-3 w-3"></i>
                                <span x-text="`Business grade: ${getPerformanceGrade().grade}`"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 shadow-sm border bg-gradient-to-r from-yellow-50 to-yellow-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle h-4 w-4 text-white"></i>
                            </div>
                            <h5 class="font-medium text-yellow-800">Watch Out</h5>
                        </div>
                        <div class="space-y-2 text-sm text-yellow-700">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-eye h-3 w-3"></i>
                                <span x-text="`${getHighVarianceTanks()} tanks with high variance`"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-eye h-3 w-3"></i>
                                <span x-text="`Max variance: ${formatPercentage(reportData?.data?.kpi_metrics?.max_variance_pct || 0)}`"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-eye h-3 w-3"></i>
                                <span>Data quality needs monitoring</span>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 shadow-sm border bg-gradient-to-r from-blue-50 to-blue-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-lightbulb h-4 w-4 text-white"></i>
                            </div>
                            <h5 class="font-medium text-blue-800">Quick Wins</h5>
                        </div>
                        <div class="space-y-2 text-sm text-blue-700">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-arrow-up h-3 w-3"></i>
                                <span>Focus on high-margin fuels</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-arrow-up h-3 w-3"></i>
                                <span>Fix variance issues first</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-arrow-up h-3 w-3"></i>
                                <span>Monitor daily patterns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button @click="previousStep" class="btn btn-secondary">
                    <i class="fas fa-arrow-left h-4 w-4 mr-2"></i>
                    Previous
                </button>
                <button @click="nextStep" class="btn btn-primary">
                    <i class="fas fa-arrow-right h-4 w-4 mr-2"></i>
                    View Data
                </button>
            </div>
        </div>

        <!-- Step 4: Detailed Data Table -->
        <div x-show="currentStep === 3 && reportData" class="space-y-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-table h-4 w-4 text-primary"></i>
                <h3 class="font-medium text-foreground">Transaction Details</h3>
                <div class="ml-auto text-xs text-muted-foreground bg-muted px-2 py-1 rounded-full">
                    Step 4 of 4
                </div>
            </div>
            <p class="text-sm text-muted-foreground mb-6">Complete reconciliation data with variance analysis and financial details.</p>

            <!-- Fuel Type Filter Tabs -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button @click="fuelFilter = 'all'"
                        :class="fuelFilter === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                        class="px-3 py-1 rounded-full text-xs font-medium transition-colors">
                    All Fuels
                </button>
                <template x-for="fuel in uniqueFuelTypes" :key="fuel">
                    <button @click="fuelFilter = fuel"
                            :class="fuelFilter === fuel ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-colors capitalize"
                            x-text="fuel.replace('_', ' ')">
                    </button>
                </template>
            </div>

            <!-- Data Table -->
            <div class="card shadow-sm border">
                <div class="flex items-center justify-between p-4 border-b bg-muted/30">
                    <div class="flex items-center gap-3">
                        <h4 class="font-medium text-foreground">Daily Reconciliations</h4>
                        <span class="text-xs text-muted-foreground bg-background px-2 py-1 rounded-full"
                              x-text="`${filteredReconciliations.length} of ${reportData?.data?.daily_reconciliations?.length || 0} records`"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="showVarianceOnly = !showVarianceOnly"
                                :class="showVarianceOnly ? 'bg-red-100 text-red-700' : 'bg-muted text-muted-foreground'"
                                class="px-2 py-1 rounded text-xs font-medium transition-colors">
                            <i class="fas fa-exclamation-triangle h-3 w-3 mr-1"></i>
                            High Variance Only
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/20">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Fuel</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Volume (L)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">COGS</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Profit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Margin</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Variance</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <template x-for="(record, index) in filteredReconciliations" :key="index">
                                <tr class="hover:bg-muted/20 transition-colors">
                                    <td class="px-4 py-3 text-sm text-foreground font-medium" x-text="formatDate(record.reconciliation_date)"></td>
                                    <td class="px-4 py-3 text-sm text-foreground" x-text="record.tank_number"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary text-secondary-foreground"
                                              x-text="record.fuel_type.replace('_', ' ')"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatVolume(record.total_dispensed_liters)"></td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(record.total_sales_ugx)"></td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatCurrency(record.total_cogs_ugx)"></td>
                                    <td class="px-4 py-3 text-sm text-right font-mono"
                                        :class="record.gross_profit_ugx >= 0 ? 'text-green-600' : 'text-red-600'"
                                        x-text="formatCurrency(record.gross_profit_ugx)"></td>
                                    <td class="px-4 py-3 text-sm text-right text-foreground font-mono" x-text="formatPercentage(record.profit_margin_percentage)"></td>
                                    <td class="px-4 py-3 text-sm text-right font-mono">
                                        <span :class="Math.abs(record.abs_variance_percentage) > 5 ? 'text-red-600' :
                                                      Math.abs(record.abs_variance_percentage) > 2 ? 'text-yellow-600' : 'text-green-600'"
                                              x-text="formatPercentage(record.variance_percentage, true)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="Math.abs(record.abs_variance_percentage) > 5 ? 'bg-red-100 text-red-700' :
                                                      Math.abs(record.abs_variance_percentage) > 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                              x-text="Math.abs(record.abs_variance_percentage) > 5 ? 'Critical' :
                                                      Math.abs(record.abs_variance_percentage) > 2 ? 'Warning' : 'Good'">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div x-show="!filteredReconciliations.length" class="text-center py-12 text-muted-foreground">
                    <i class="fas fa-filter h-12 w-12 mb-4 opacity-50"></i>
                    <p class="text-sm">No records match the current filters.</p>
                    <p class="text-xs mt-1">Try adjusting your filter criteria.</p>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button @click="previousStep" class="btn btn-secondary">
                    <i class="fas fa-arrow-left h-4 w-4 mr-2"></i>
                    Previous
                </button>
                <button @click="exportReport" class="btn btn-primary gap-2">
                    <i class="fas fa-download h-4 w-4"></i>
                    Export Report
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="card p-8 shadow-xl border text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-foreground mb-2">Processing Analytics</h3>
            <p class="text-sm text-muted-foreground">Generating comprehensive profit & loss report...</p>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="error && !loading" class="card p-6 border-destructive bg-destructive/5">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-triangle h-6 w-6 text-destructive"></i>
            <div>
                <h3 class="font-semibold text-destructive">Report Generation Failed</h3>
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
function profitLossWizard() {
    return {
        currentStep: 0,
        loading: false,
        error: null,
        reportData: @json($initial_data ?? null),
        fuelFilter: 'all',
        showVarianceOnly: false,

        steps: [
            { icon: 'fas fa-filter', title: 'Configure', subtitle: 'Set parameters' },
            { icon: 'fas fa-chart-bar', title: 'Overview', subtitle: 'Key metrics' },
            { icon: 'fas fa-chart-pie', title: 'Analytics', subtitle: 'Visual insights' },
            { icon: 'fas fa-table', title: 'Details', subtitle: 'Raw data' }
        ],

        filters: {
            station_id: '{{ $default_station_id ?? "" }}',
            date_start: '{{ $today ?? date("Y-m-d") }}',
            date_end: '{{ $today ?? date("Y-m-d") }}'
        },

        // Utility formatters

        revenueChart: null,
        performanceChart: null,
        heatmapChart: null,
        trendChart: null,

        init() {
            // Initialize charts if data is available
            if (this.reportData && this.currentStep >= 2) {
                this.$nextTick(() => {
                    this.initializeCharts();
                });
            }

            // Setup export and refresh buttons
            const exportBtn = document.getElementById('export-btn');
            const refreshBtn = document.getElementById('refresh-btn');

            if (exportBtn) {
                exportBtn.addEventListener('click', () => this.exportReport());
            }

            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.currentStep = 0;
                    this.error = null;
                });
            }

            // Enable export if data exists
            if (this.reportData && exportBtn) {
                exportBtn.disabled = false;
            }
        },

        get uniqueFuelTypes() {
            if (!this.reportData?.data?.daily_reconciliations) return [];
            return [...new Set(this.reportData.data.daily_reconciliations.map(r => r.fuel_type))];
        },

        get filteredReconciliations() {
            if (!this.reportData?.data?.daily_reconciliations) return [];

            let filtered = this.reportData.data.daily_reconciliations;

            // Apply fuel filter
            if (this.fuelFilter !== 'all') {
                filtered = filtered.filter(r => r.fuel_type === this.fuelFilter);
            }

            // Apply variance filter
            if (this.showVarianceOnly) {
                filtered = filtered.filter(r => Math.abs(r.abs_variance_percentage || 0) > 2);
            }

            return filtered;
        },

        async nextStep() {
            if (this.currentStep === 0) {
                // Generate report
                await this.generateReport();
            } else if (this.currentStep < 3) {
                this.currentStep++;

                // Initialize charts when reaching analytics step
                if (this.currentStep === 2) {
                    this.$nextTick(() => {
                        this.initializeCharts();
                    });
                }
            }
        },

        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        async generateReport() {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    station_id: this.filters.station_id,
                    date_start: this.filters.date_start,
                    date_end: this.filters.date_end
                });

                const response = await fetch(`{{ route('reports.daily-profit-loss.data') }}?${params}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Report generation failed');
                }

                this.reportData = data;
                this.currentStep = 1;

                // Enable export button
                const exportBtn = document.getElementById('export-btn');
                if (exportBtn) {
                    exportBtn.disabled = false;
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Analytics Generated',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

            } catch (error) {
                this.error = error.message;
                console.error('Report generation failed:', error);

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
            }
        },

        resetFilters() {
            this.filters = {
                station_id: '{{ $default_station_id ?? "" }}',
                date_start: '{{ $today ?? date("Y-m-d") }}',
                date_end: '{{ $today ?? date("Y-m-d") }}'
            };
        },

        initializeCharts() {
            this.initMoneyFlowChart();
            this.initPerformanceGauge();
            this.initTankHeatmap();
            this.initProfitTrendChart();
        },

        initMoneyFlowChart() {
            const chartDom = document.getElementById('money-flow-chart');
            if (!chartDom || !this.reportData?.data?.summary_totals) return;

            if (this.moneyFlowChart) {
                this.moneyFlowChart.dispose();
            }

            this.moneyFlowChart = echarts.init(chartDom);

            const summary = this.reportData.data.summary_totals;
            const revenue = parseFloat(summary.total_revenue_ugx) || 0;
            const cogs = parseFloat(summary.total_cogs_ugx) || 0;
            const profit = parseFloat(summary.total_profit_ugx) || 0;

            // Waterfall data: Revenue -> COGS -> Profit
            const data = [
                { name: 'Revenue', value: revenue, itemStyle: { color: '#22c55e' } },
                { name: 'Less: Cost of Goods', value: -cogs, itemStyle: { color: '#ef4444' } },
                { name: 'Net Profit', value: profit, itemStyle: { color: profit >= 0 ? '#3b82f6' : '#ef4444' } }
            ];

            const option = {
                title: {
                    text: `Total Revenue: ${this.formatCurrency(revenue)}`,
                    left: 'center',
                    textStyle: { fontSize: 16, fontWeight: 'bold' }
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        const param = params[0];
                        const absValue = Math.abs(param.value);
                        return `<strong>${param.name}</strong><br/>Amount: UGX ${absValue.toLocaleString()}`;
                    }
                },
                grid: {
                    left: '10%',
                    right: '10%',
                    bottom: '15%',
                    top: '20%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    data: data.map(d => d.name),
                    axisLabel: {
                        interval: 0,
                        rotate: 0,
                        fontSize: 12
                    }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        formatter: function(value) {
                            return 'UGX ' + (Math.abs(value) / 1000000).toFixed(1) + 'M';
                        }
                    }
                },
                series: [{
                    type: 'bar',
                    data: data,
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top',
                        formatter: function(params) {
                            const absValue = Math.abs(params.value);
                            return (absValue / 1000000).toFixed(1) + 'M';
                        },
                        fontSize: 12,
                        fontWeight: 'bold'
                    }
                }]
            };

            this.moneyFlowChart.setOption(option);
        },

        initPerformanceGauge() {
            const chartDom = document.getElementById('performance-gauge');
            if (!chartDom || !this.reportData?.data?.kpi_metrics) return;

            if (this.performanceChart) {
                this.performanceChart.dispose();
            }

            this.performanceChart = echarts.init(chartDom);

            const efficiency = parseFloat(this.reportData.data.kpi_metrics.operational_efficiency_score) || 0;
            const margin = parseFloat(this.reportData.data.kpi_metrics.avg_profit_margin_pct) || 0;
            const variance = parseFloat(this.reportData.data.kpi_metrics.avg_variance_pct) || 0;

            // Combined health score (weighted)
            const healthScore = Math.min(100, Math.max(0,
                (efficiency * 0.4) +
                (Math.min(margin * 2, 50) * 0.4) +
                (Math.max(0, 20 - variance) * 0.2)
            ));

            const option = {
                series: [{
                    type: 'gauge',
                    center: ['50%', '60%'],
                    startAngle: 200,
                    endAngle: -40,
                    min: 0,
                    max: 100,
                    splitNumber: 10,
                    itemStyle: {
                        color: healthScore > 75 ? '#22c55e' : healthScore > 50 ? '#eab308' : '#ef4444'
                    },
                    progress: {
                        show: true,
                        width: 20
                    },
                    pointer: {
                        show: false
                    },
                    axisLine: {
                        lineStyle: {
                            width: 20
                        }
                    },
                    axisTick: {
                        distance: -30,
                        splitNumber: 5,
                        lineStyle: {
                            width: 2,
                            color: '#999'
                        }
                    },
                    splitLine: {
                        distance: -30,
                        length: 14,
                        lineStyle: {
                            width: 3,
                            color: '#999'
                        }
                    },
                    axisLabel: {
                        distance: -20,
                        color: '#999',
                        fontSize: 12
                    },
                    anchor: {
                        show: false
                    },
                    title: {
                        show: false
                    },
                    detail: {
                        valueAnimation: true,
                        width: '60%',
                        lineHeight: 40,
                        borderRadius: 8,
                        offsetCenter: [0, '-15%'],
                        fontSize: 24,
                        fontWeight: 'bold',
                        formatter: '{value}%',
                        color: 'inherit'
                    },
                    data: [{
                        value: Math.round(healthScore)
                    }]
                }]
            };

            this.performanceChart.setOption(option);
        },

        initTankHeatmap() {
            const chartDom = document.getElementById('tank-heatmap');
            if (!chartDom || !this.reportData?.data?.tank_level_details) return;

            if (this.heatmapChart) {
                this.heatmapChart.dispose();
            }

            this.heatmapChart = echarts.init(chartDom);

            const tanks = this.reportData.data.tank_level_details || [];

            // Create grid data for heatmap
            const maxCols = 6;
            const rows = Math.ceil(tanks.length / maxCols);

            const data = tanks.map((tank, index) => {
                const variance = parseFloat(tank.tank_avg_variance_pct) || 0;
                const row = Math.floor(index / maxCols);
                const col = index % maxCols;

                return [col, row, variance, tank.tank_number, tank.fuel_type];
            });

            const option = {
                tooltip: {
                    formatter: function(params) {
                        const [col, row, variance, tankNum, fuelType] = params.data;
                        return `<strong>Tank ${tankNum}</strong><br/>
                                Fuel: ${fuelType}<br/>
                                Variance: ${variance.toFixed(2)}%`;
                    }
                },
                grid: {
                    height: '70%',
                    top: '10%'
                },
                xAxis: {
                    type: 'category',
                    data: Array.from({length: maxCols}, (_, i) => ''),
                    splitArea: {
                        show: true
                    },
                    axisLabel: { show: false },
                    axisTick: { show: false },
                    axisLine: { show: false }
                },
                yAxis: {
                    type: 'category',
                    data: Array.from({length: rows}, (_, i) => ''),
                    splitArea: {
                        show: true
                    },
                    axisLabel: { show: false },
                    axisTick: { show: false },
                    axisLine: { show: false }
                },
                visualMap: {
                    min: 0,
                    max: 10,
                    calculable: true,
                    orient: 'horizontal',
                    left: 'center',
                    bottom: '5%',
                    inRange: {
                        color: ['#50f', '#06f', '#0df', '#6f0', '#f90', '#f60', '#f30']
                    },
                    text: ['High', 'Low'],
                    textStyle: {
                        fontSize: 10
                    }
                },
                series: [{
                    name: 'Tank Variance',
                    type: 'heatmap',
                    data: data,
                    label: {
                        show: true,
                        formatter: function(params) {
                            return `T${params.data[3]}`;
                        },
                        fontSize: 10,
                        fontWeight: 'bold'
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }]
            };

            this.heatmapChart.setOption(option);
        },

        initProfitTrendChart() {
            const chartDom = document.getElementById('profit-trend-chart');
            if (!chartDom || !this.reportData?.data?.daily_reconciliations) return;

            if (this.trendChart) {
                this.trendChart.dispose();
            }

            this.trendChart = echarts.init(chartDom);

            const dailyData = this.reportData.data.daily_reconciliations;
            const dates = [...new Set(dailyData.map(d => d.reconciliation_date))].sort();

            const profitData = dates.map(date => {
                const dayProfit = dailyData
                    .filter(d => d.reconciliation_date === date)
                    .reduce((sum, d) => sum + (parseFloat(d.gross_profit_ugx) || 0), 0);
                return dayProfit;
            });

            // Calculate trend
            const avgProfit = profitData.reduce((a, b) => a + b, 0) / profitData.length;
            const trendColor = profitData[profitData.length - 1] > profitData[0] ? '#22c55e' : '#ef4444';

            const option = {
                title: {
                    text: profitData[profitData.length - 1] > profitData[0] ? '📈 Trending Up' : '📉 Trending Down',
                    left: 'center',
                    textStyle: {
                        fontSize: 14,
                        color: trendColor
                    }
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        const param = params[0];
                        return `<strong>${new Date(param.axisValue).toLocaleDateString()}</strong><br/>
                                Profit: UGX ${param.value.toLocaleString()}`;
                    }
                },
                grid: {
                    left: '5%',
                    right: '5%',
                    bottom: '10%',
                    top: '20%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    data: dates,
                    axisLabel: {
                        formatter: function(value) {
                            return new Date(value).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        formatter: function(value) {
                            return (value / 1000).toFixed(0) + 'K';
                        }
                    }
                },
                series: [{
                    data: profitData,
                    type: 'line',
                    smooth: true,
                    lineStyle: {
                        width: 4,
                        color: trendColor
                    },
                    itemStyle: {
                        color: trendColor
                    },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0,
                            y: 0,
                            x2: 0,
                            y2: 1,
                            colorStops: [{
                                offset: 0,
                                color: trendColor + '40'
                            }, {
                                offset: 1,
                                color: trendColor + '10'
                            }]
                        }
                    }
                }]
            };

            this.trendChart.setOption(option);
        },

        async exportReport() {
            try {
                const params = new URLSearchParams({
                    station_id: this.filters.station_id,
                    date_start: this.filters.date_start,
                    date_end: this.filters.date_end
                });

                window.open(`{{ route('reports.daily-profit-loss.export') }}?${params}`, '_blank');

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

        // Business intelligence methods
        getBestFuelType() {
            const fuelBreakdown = this.reportData?.data?.fuel_type_breakdown || [];
            if (fuelBreakdown.length === 0) return 'No data available';

            const best = fuelBreakdown.reduce((max, fuel) =>
                (parseFloat(fuel.avg_profit_margin_pct) || 0) > (parseFloat(max.avg_profit_margin_pct) || 0) ? fuel : max
            );

            return best.fuel_type?.replace('_', ' ').toUpperCase() || 'Unknown';
        },

        getGoodVarianceTanks() {
            const tanks = this.reportData?.data?.tank_level_details || [];
            return tanks.filter(tank => (parseFloat(tank.tank_avg_variance_pct) || 0) <= 2).length;
        },

        getHighVarianceTanks() {
            const tanks = this.reportData?.data?.tank_level_details || [];
            return tanks.filter(tank => (parseFloat(tank.tank_avg_variance_pct) || 0) > 5).length;
        },

        getBusinessHealthScore() {
            if (!this.reportData?.data?.kpi_metrics) return 0;

            const metrics = this.reportData.data.kpi_metrics;
            const profitMargin = parseFloat(metrics.avg_profit_margin_pct) || 0;
            const variance = parseFloat(metrics.avg_variance_pct) || 0;
            const efficiency = parseFloat(metrics.operational_efficiency_score) || 0;

            // Weighted business health calculation
            const marginScore = Math.min(profitMargin * 2, 40); // Cap at 40%
            const varianceScore = Math.max(0, 30 - (variance * 3)); // Lower variance = higher score
            const efficiencyScore = efficiency * 0.3; // Scale efficiency

            return Math.round(marginScore + varianceScore + efficiencyScore);
        },

        getPerformanceGrade() {
            const score = this.getBusinessHealthScore();
            if (score >= 85) return { grade: 'A+', color: 'text-green-600', bg: 'bg-green-100' };
            if (score >= 75) return { grade: 'A', color: 'text-green-600', bg: 'bg-green-100' };
            if (score >= 65) return { grade: 'B+', color: 'text-blue-600', bg: 'bg-blue-100' };
            if (score >= 55) return { grade: 'B', color: 'text-blue-600', bg: 'bg-blue-100' };
            if (score >= 45) return { grade: 'C', color: 'text-yellow-600', bg: 'bg-yellow-100' };
            if (score >= 35) return { grade: 'D', color: 'text-orange-600', bg: 'bg-orange-100' };
            return { grade: 'F', color: 'text-red-600', bg: 'bg-red-100' };
        },
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

        formatPercentage(value, showSign = false) {
            const num = parseFloat(value) || 0;
            const formatted = num.toFixed(2) + '%';
            return showSign && num > 0 ? '+' + formatted : formatted;
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
/* Executive chart styling */
#money-flow-chart, #performance-gauge, #tank-heatmap, #profit-trend-chart {
    min-height: 280px;
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

    #revenue-profit-chart, #fuel-type-chart {
        min-height: 280px;
    }
}
</style>
@endpush
