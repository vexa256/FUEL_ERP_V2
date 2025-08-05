@extends('layouts.app')

@section('content')
<!-- ECharts CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>

<div class="min-h-screen bg-background">
    <!-- Header Section -->
    <div class="border-b bg-background">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Reconciliation Analytics</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ $analytics['filter_info']['month_name'] }} Performance Overview
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <form method="GET" action="{{ route('reconciliation.analytics.export') }}" class="inline">
                        <input type="hidden" name="station_id" value="{{ $stationId }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="rounded-2xl border bg-card shadow-sm p-6">
            <form method="GET" action="{{ route('reconciliation.analytics') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Station Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Station</label>
                        <select name="station_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="">All Stations</option>
                            @foreach($stations as $station)
                                <option value="{{ $station->id }}" {{ $stationId == $station->id ? 'selected' : '' }}>
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Month</label>
                        <select name="month" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Year</label>
                        <select name="year" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            @for($y = 2023; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-transparent">Filter</label>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="max-w-7xl mx-auto px-6">
        <div class="border-b border-border">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('overview')" id="tab-overview" class="tab-button active border-b-2 border-primary py-2 px-1 text-sm font-medium text-primary">
                    Executive Overview
                </button>
                <button onclick="showTab('performance')" id="tab-performance" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-muted-foreground hover:text-foreground hover:border-border">
                    Performance Analysis
                </button>
                <button onclick="showTab('variance')" id="tab-variance" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-muted-foreground hover:text-foreground hover:border-border">
                    Variance Control
                </button>
                <button onclick="showTab('financial')" id="tab-financial" class="tab-button border-b-2 border-transparent py-2 px-1 text-sm font-medium text-muted-foreground hover:text-foreground hover:border-border">
                    Financial Details
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="max-w-7xl mx-auto px-6 py-6">
        <!-- Executive Overview Tab -->
        <div id="content-overview" class="tab-content">
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="rounded-2xl border bg-card shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                            <p class="text-2xl font-bold text-foreground">
                                UGX {{ number_format($analytics['monthly_summary']['totals']['monthly_sales_ugx']) }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Net Profit</p>
                            <p class="text-2xl font-bold {{ $analytics['monthly_summary']['totals']['monthly_profit_ugx'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                UGX {{ number_format($analytics['monthly_summary']['totals']['monthly_profit_ugx']) }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full {{ $analytics['monthly_summary']['totals']['monthly_profit_ugx'] >= 0 ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center">
                            <svg class="h-6 w-6 {{ $analytics['monthly_summary']['totals']['monthly_profit_ugx'] >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Volume Sold</p>
                            <p class="text-2xl font-bold text-foreground">
                                {{ number_format($analytics['monthly_summary']['totals']['monthly_volume_sold']) }}L
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Risk Incidents</p>
                            <p class="text-2xl font-bold text-foreground">
                                {{ $analytics['monthly_summary']['totals']['monthly_variance_incidents'] }}
                            </p>
                        </div>
                        <div class="h-12 w-12 rounded-full {{ $analytics['monthly_summary']['totals']['monthly_variance_incidents'] > 0 ? 'bg-red-100' : 'bg-green-100' }} flex items-center justify-center">
                            <svg class="h-6 w-6 {{ $analytics['monthly_summary']['totals']['monthly_variance_incidents'] > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Executive Story Charts -->
            <div class="space-y-8 mb-8">
                <!-- Simple Station Profit Story -->
                <div class="rounded-2xl border bg-card shadow-sm p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-foreground">Which Stations Make Money?</h3>
                        <p class="text-sm text-muted-foreground mt-2">Green = Profit | Red = Loss | Bigger bar = More money</p>
                    </div>
                    <div id="profit-story-chart" style="height: 500px; width: 100%;"></div>
                </div>

                <!-- Daily Performance Trend -->
                <div class="rounded-2xl border bg-card shadow-sm p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-foreground">Are We Getting Better?</h3>
                        <p class="text-sm text-muted-foreground mt-2">Daily profit trend - Is the line going up or down?</p>
                    </div>
                    <div id="trend-story-chart" style="height: 400px; width: 100%;"></div>
                </div>

                <!-- Risk Alert Dashboard -->
                <div class="rounded-2xl border bg-card shadow-sm p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-foreground">Where Are The Problems?</h3>
                        <p class="text-sm text-muted-foreground mt-2">Red = Urgent attention needed | Yellow = Watch closely | Green = All good</p>
                    </div>
                    <div id="risk-alert-chart" style="height: 500px; width: 100%;"></div>
                </div>
            </div>

            <!-- Enhanced Station Performance Table -->
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-foreground">Executive Performance Dashboard</h3>
                    <p class="text-sm text-muted-foreground">Comprehensive station metrics with profit/loss analysis</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Station</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Revenue (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">COGS (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Gross Profit</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Net Position</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Margin %</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Volume (L)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Efficiency</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">Risk Level</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['monthly_summary']['data'] as $station)
                            @php
                                $netPosition = $station->monthly_profit_ugx;
                                $efficiency = $station->monthly_volume_sold > 0 ? $station->monthly_profit_ugx / $station->monthly_volume_sold : 0;
                                $riskLevel = $station->monthly_variance_incidents;
                                $performance = $station->avg_margin_percentage >= 15 ? 'Excellent' : ($station->avg_margin_percentage >= 10 ? 'Good' : ($station->avg_margin_percentage >= 5 ? 'Fair' : 'Poor'));
                            @endphp
                            <tr class="border-b hover:bg-muted/50">
                                <td class="p-4 font-medium text-foreground">{{ $station->station_name }}</td>
                                <td class="p-4 text-right text-sm font-medium text-foreground">{{ number_format($station->monthly_sales_ugx) }}</td>
                                <td class="p-4 text-right text-sm text-muted-foreground">{{ number_format($station->monthly_sales_ugx - $station->monthly_profit_ugx) }}</td>
                                <td class="p-4 text-right text-sm font-medium {{ $station->monthly_profit_ugx >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $station->monthly_profit_ugx >= 0 ? '+' : '' }}{{ number_format($station->monthly_profit_ugx) }}
                                </td>
                                <td class="p-4 text-right text-sm font-bold {{ $netPosition >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    @if($netPosition >= 0)
                                        <span class="flex items-center justify-end">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ number_format($netPosition) }}
                                        </span>
                                    @else
                                        <span class="flex items-center justify-end">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ number_format($netPosition) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-right text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $station->avg_margin_percentage >= 15 ? 'bg-green-100 text-green-800' : ($station->avg_margin_percentage >= 10 ? 'bg-yellow-100 text-yellow-800' : ($station->avg_margin_percentage >= 0 ? 'bg-red-100 text-red-800' : 'bg-red-200 text-red-900')) }}">
                                        {{ number_format($station->avg_margin_percentage, 1) }}%
                                    </span>
                                </td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($station->monthly_volume_sold) }}</td>
                                <td class="p-4 text-right text-sm font-medium {{ $efficiency >= 50 ? 'text-green-600' : ($efficiency >= 20 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($efficiency, 1) }} UGX/L
                                </td>
                                <td class="p-4 text-center text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $riskLevel == 0 ? 'bg-green-100 text-green-800' : ($riskLevel <= 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $riskLevel == 0 ? 'Low' : ($riskLevel <= 2 ? 'Medium' : 'High') }}
                                    </span>
                                </td>
                                <td class="p-4 text-center text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $performance === 'Excellent' ? 'bg-green-100 text-green-800' : ($performance === 'Good' ? 'bg-blue-100 text-blue-800' : ($performance === 'Fair' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                        {{ $performance }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-muted-foreground">
                                    No data available for selected period
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Analysis Tab -->
        <div id="content-performance" class="tab-content hidden">
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-foreground">Tank Performance Analysis</h3>
                    <p class="text-sm text-muted-foreground">Individual tank operational metrics and efficiency</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Station</th>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Tank</th>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Fuel Type</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Days Active</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Sales (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Profit (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Volume (L)</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">Avg Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['tank_performance'] as $tank)
                            <tr class="border-b hover:bg-muted/50">
                                <td class="p-4 text-sm text-foreground">{{ $tank->station_name }}</td>
                                <td class="p-4 font-medium text-foreground">{{ $tank->tank_number }}</td>
                                <td class="p-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tank->fuel_type === 'petrol' ? 'bg-blue-100 text-blue-800' : ($tank->fuel_type === 'diesel' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                        {{ ucfirst($tank->fuel_type) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right text-sm text-foreground">{{ $tank->reconciliation_days }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($tank->total_sales) }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($tank->total_profit) }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($tank->total_volume_sold) }}</td>
                                <td class="p-4 text-center text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tank->avg_variance_pct <= 2 ? 'bg-green-100 text-green-800' : ($tank->avg_variance_pct <= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($tank->avg_variance_pct, 2) }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-muted-foreground">
                                    No performance data available
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Variance Control Tab -->
        <div id="content-variance" class="tab-content hidden">
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-foreground">Variance Control Dashboard</h3>
                    <p class="text-sm text-muted-foreground">Operational variance tracking and risk assessment</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Station</th>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Fuel Type</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Total Days</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Avg Variance</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">Low Risk</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">Medium Risk</th>
                                <th class="text-center p-4 text-sm font-medium text-muted-foreground">High Risk</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Volume Impact</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['variance_analysis'] as $variance)
                            <tr class="border-b hover:bg-muted/50">
                                <td class="p-4 text-sm text-foreground">{{ $variance->station_name }}</td>
                                <td class="p-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $variance->fuel_type === 'petrol' ? 'bg-blue-100 text-blue-800' : ($variance->fuel_type === 'diesel' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                        {{ ucfirst($variance->fuel_type) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right text-sm text-foreground">{{ $variance->total_reconciliations }}</td>
                                <td class="p-4 text-right text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $variance->avg_abs_variance_pct <= 2 ? 'bg-green-100 text-green-800' : ($variance->avg_abs_variance_pct <= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($variance->avg_abs_variance_pct, 2) }}%
                                    </span>
                                </td>
                                <td class="p-4 text-center text-sm text-green-700">{{ $variance->low_variance_days }}</td>
                                <td class="p-4 text-center text-sm text-yellow-700">{{ $variance->medium_variance_days }}</td>
                                <td class="p-4 text-center text-sm text-red-700">{{ $variance->high_variance_days }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($variance->total_variance_volume) }}L</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-muted-foreground">
                                    No variance data available
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Details Tab -->
        <div id="content-financial" class="tab-content hidden">
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-foreground">Financial Performance Detail</h3>
                    <p class="text-sm text-muted-foreground">Comprehensive financial metrics and pricing analysis</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="text-left p-4 text-sm font-medium text-muted-foreground">Station</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Revenue (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">COGS (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Profit (UGX)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Margin %</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Volume (L)</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Avg Price/L</th>
                                <th class="text-right p-4 text-sm font-medium text-muted-foreground">Days Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($analytics['financial_overview'] as $financial)
                            <tr class="border-b hover:bg-muted/50">
                                <td class="p-4 font-medium text-foreground">{{ $financial->station_name }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($financial->total_revenue) }}</td>
                                <td class="p-4 text-right text-sm text-muted-foreground">{{ number_format($financial->total_cogs) }}</td>
                                <td class="p-4 text-right text-sm font-medium text-green-700">{{ number_format($financial->total_profit) }}</td>
                                <td class="p-4 text-right text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $financial->avg_margin_pct >= 15 ? 'bg-green-100 text-green-800' : ($financial->avg_margin_pct >= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($financial->avg_margin_pct, 1) }}%
                                    </span>
                                </td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($financial->total_volume) }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ number_format($financial->avg_price_per_liter ?? 0) }}</td>
                                <td class="p-4 text-right text-sm text-foreground">{{ $financial->operating_days }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-muted-foreground">
                                    No financial data available
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active state from all tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-primary', 'text-primary');
        button.classList.add('border-transparent', 'text-muted-foreground');
    });

    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.add('active', 'border-primary', 'text-primary');
    activeTab.classList.remove('border-transparent', 'text-muted-foreground');
}

// Initialize ECharts on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeExecutiveCharts();
});

function initializeExecutiveCharts() {
    // Prepare data from PHP
    const stationsData = @json($analytics['monthly_summary']['data']);
    const dailyData = @json($analytics['daily_performance']['data']);

    // Simple Story Charts
    initProfitStoryChart(stationsData);
    initTrendStoryChart(dailyData);
    initRiskAlertChart(stationsData);
}

function initProfitStoryChart(data) {
    const chart = echarts.init(document.getElementById('profit-story-chart'));

    const option = {
        tooltip: {
            trigger: 'item',
            backgroundColor: '#fff',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            textStyle: { color: '#1f2937', fontSize: 14 },
            formatter: function(params) {
                const profit = params.data.value[0];
                const profitFormatted = (profit / 1000000).toFixed(1);
                const status = profit >= 0 ? 'MAKING MONEY' : 'LOSING MONEY';
                const color = profit >= 0 ? '#22c55e' : '#ef4444';
                return `<div style="font-weight: bold; color: ${color};">${params.data.name}</div>
                        <div style="margin: 8px 0; font-size: 16px; color: ${color};">${status}</div>
                        <div>Profit: <strong>UGX ${profitFormatted}M</strong></div>`;
            }
        },
        grid: {
            left: '25%',
            right: '10%',
            top: '10%',
            bottom: '10%'
        },
        xAxis: {
            type: 'value',
            name: 'PROFIT (Millions UGX)',
            nameLocation: 'middle',
            nameGap: 25,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 12,
                color: '#6b7280',
                formatter: function(value) {
                    return value.toFixed(1) + 'M';
                }
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } },
            splitLine: { lineStyle: { color: '#f3f4f6', type: 'dashed' } }
        },
        yAxis: {
            type: 'category',
            data: data.map(station => station.station_name),
            name: '',
            nameLocation: 'middle',
            nameGap: 80,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 12,
                color: '#6b7280',
                width: 120,
                overflow: 'truncate'
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } }
        },
        series: [{
            type: 'bar',
            barHeight: '60%',
            data: data.map(station => ({
                name: station.station_name,
                value: [parseFloat(station.monthly_profit_ugx), station.station_name],
                itemStyle: {
                    color: parseFloat(station.monthly_profit_ugx) >= 0 ? '#22c55e' : '#ef4444',
                    borderRadius: [0, 4, 4, 0]
                }
            })),
            markLine: {
                data: [{
                    xAxis: 0,
                    name: 'Break Even',
                    lineStyle: { color: '#f59e0b', width: 3, type: 'solid' },
                    label: {
                        formatter: 'BREAK EVEN',
                        fontSize: 12,
                        fontWeight: 'bold',
                        color: '#f59e0b',
                        position: 'insideEndTop'
                    }
                }]
            }
        }]
    };

    chart.setOption(option);

    // Make responsive
    window.addEventListener('resize', function() {
        chart.resize();
    });
}

function initTrendStoryChart(data) {
    const chart = echarts.init(document.getElementById('trend-story-chart'));

    // Group daily data and calculate totals per day
    const dailyTotals = {};
    data.forEach(item => {
        const date = item.reconciliation_date;
        if (!dailyTotals[date]) {
            dailyTotals[date] = 0;
        }
        dailyTotals[date] += parseFloat(item.gross_profit_ugx || 0);
    });

    const dates = Object.keys(dailyTotals).sort();
    const profits = dates.map(date => (dailyTotals[date] / 1000000).toFixed(1));

    // Calculate trend
    const firstHalf = profits.slice(0, Math.floor(profits.length/2));
    const secondHalf = profits.slice(Math.floor(profits.length/2));
    const firstAvg = firstHalf.reduce((a, b) => parseFloat(a) + parseFloat(b), 0) / firstHalf.length;
    const lastAvg = secondHalf.reduce((a, b) => parseFloat(a) + parseFloat(b), 0) / secondHalf.length;
    const trendDirection = lastAvg > firstAvg ? 'GETTING BETTER' : 'GETTING WORSE';
    const trendColor = lastAvg > firstAvg ? '#22c55e' : '#ef4444';

    const option = {
        tooltip: {
            trigger: 'axis',
            backgroundColor: '#fff',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            textStyle: { color: '#1f2937', fontSize: 14 },
            formatter: function(params) {
                const date = params[0].axisValue;
                const profit = params[0].value;
                const formattedDate = new Date(date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
                return `<div style="font-weight: bold;">${formattedDate}</div>
                        <div style="margin: 8px 0;">Daily Profit: <strong>UGX ${profit}M</strong></div>
                        <div style="color: ${trendColor}; font-weight: bold;">${trendDirection}</div>`;
            }
        },
        grid: {
            left: '10%',
            right: '10%',
            top: '15%',
            bottom: '25%'
        },
        xAxis: {
            type: 'category',
            data: dates,
            name: 'DAYS THIS MONTH',
            nameLocation: 'middle',
            nameGap: 30,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 11,
                color: '#6b7280',
                formatter: function(value) {
                    return new Date(value).getDate();
                },
                rotate: 0
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } }
        },
        yAxis: {
            type: 'value',
            name: 'DAILY PROFIT (Millions UGX)',
            nameLocation: 'middle',
            nameGap: 50,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 12,
                color: '#6b7280',
                formatter: function(value) {
                    return value.toFixed(1) + 'M';
                }
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } },
            splitLine: { lineStyle: { color: '#f3f4f6', type: 'dashed' } }
        },
        series: [{
            type: 'line',
            data: profits,
            lineStyle: {
                color: trendColor,
                width: 4,
                shadowColor: trendColor,
                shadowBlur: 10
            },
            itemStyle: {
                color: trendColor,
                borderWidth: 3,
                borderColor: '#fff'
            },
            areaStyle: {
                color: {
                    type: 'linear',
                    x: 0, y: 0, x2: 0, y2: 1,
                    colorStops: [
                        { offset: 0, color: trendColor + '40' },
                        { offset: 1, color: trendColor + '10' }
                    ]
                }
            },
            smooth: true,
            symbol: 'circle',
            symbolSize: 8
        }]
    };

    chart.setOption(option);

    // Make responsive
    window.addEventListener('resize', function() {
        chart.resize();
    });
}

function initRiskAlertChart(data) {
    const chart = echarts.init(document.getElementById('risk-alert-chart'));

    // Categorize stations by risk level
    const riskData = data.map(station => {
        const variance = parseInt(station.monthly_variance_incidents || 0);
        const margin = parseFloat(station.avg_margin_percentage || 0);

        let riskLevel = 'LOW RISK';
        let color = '#22c55e';
        let riskScore = Math.max(1, variance);

        if (margin < 0) {
            riskLevel = 'URGENT - LOSING MONEY';
            color = '#dc2626';
            riskScore = Math.max(10, Math.abs(margin));
        } else if (variance >= 5 || margin < 5) {
            riskLevel = 'HIGH RISK';
            color = '#ef4444';
            riskScore = Math.max(5, variance);
        } else if (variance >= 2 || margin < 10) {
            riskLevel = 'MEDIUM RISK';
            color = '#f59e0b';
            riskScore = Math.max(2, variance);
        }

        return {
            name: station.station_name,
            value: [riskScore, station.station_name],
            riskLevel: riskLevel,
            margin: margin.toFixed(1),
            variance: variance,
            itemStyle: { color: color }
        };
    }).sort((a, b) => b.value[0] - a.value[0]); // Sort by risk level, highest first

    const option = {
        tooltip: {
            trigger: 'item',
            backgroundColor: '#fff',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            textStyle: { color: '#1f2937', fontSize: 14 },
            formatter: function(params) {
                const data = params.data;
                return `<div style="font-weight: bold;">${data.name}</div>
                        <div style="margin: 8px 0; font-weight: bold; color: ${params.color};">${data.riskLevel}</div>
                        <div>Profit Margin: <strong>${data.margin}%</strong></div>
                        <div>Risk Incidents: <strong>${data.variance}</strong></div>`;
            }
        },
        grid: {
            left: '25%',
            right: '10%',
            top: '10%',
            bottom: '10%'
        },
        xAxis: {
            type: 'value',
            name: 'RISK LEVEL',
            nameLocation: 'middle',
            nameGap: 25,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 12,
                color: '#6b7280',
                formatter: function(value) {
                    if (value >= 10) return 'URGENT';
                    if (value >= 5) return 'HIGH';
                    if (value >= 2) return 'MEDIUM';
                    return 'LOW';
                }
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } },
            splitLine: { lineStyle: { color: '#f3f4f6', type: 'dashed' } }
        },
        yAxis: {
            type: 'category',
            data: riskData.map(item => item.name),
            name: '',
            nameLocation: 'middle',
            nameGap: 80,
            nameTextStyle: { fontSize: 13, fontWeight: 'bold', color: '#6b7280' },
            axisLabel: {
                fontSize: 12,
                color: '#6b7280',
                width: 120,
                overflow: 'truncate'
            },
            axisLine: { lineStyle: { color: '#e5e7eb', width: 2 } }
        },
        series: [{
            type: 'bar',
            barHeight: '60%',
            data: riskData,
            itemStyle: {
                borderRadius: [0, 4, 4, 0]
            }
        }]
    };

    chart.setOption(option);

    // Make responsive
    window.addEventListener('resize', function() {
        chart.resize();
    });
}
</script>
@endsection
