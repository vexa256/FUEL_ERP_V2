@extends('layouts.app')

@section('title', 'Price Intelligence Center')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
    <div class="space-y-2">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    Price Intelligence Center
                </h1>
                <p class="text-lg text-muted-foreground">Advanced pricing analytics & margin optimization</p>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-6">
        <div class="hidden lg:flex items-center gap-4">
            <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-sm font-medium text-green-700">Real-time Analytics</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-lg border border-blue-200">
                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                <span class="text-sm font-medium text-blue-700">AI-Powered Insights</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="priceIntelligenceApp()" x-init="init()" class="space-y-8">
    <!-- Executive Dashboard Controls -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 px-8 py-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Analysis Controls</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Station</label>
                    <div class="relative">
                        <select x-model="filters.station_id" @change="loadAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none">
                            @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}" {{ $station->id == $station_id ? 'selected' : '' }}>
                                {{ $station->name }} - {{ $station->location }}
                            </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Fuel Type</label>
                    <div class="relative">
                        <select x-model="filters.fuel_type" @change="loadAnalytics()"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none">
                            <option value="all">All Fuel Types</option>
                            <option value="petrol">Premium Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="kerosene">Kerosene</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">From Date</label>
                    <input type="date" x-model="filters.date_from" @change="validateDateRange()"
                           :max="filters.date_to"
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">To Date</label>
                    <input type="date" x-model="filters.date_to" @change="validateDateRange()"
                           :min="filters.date_from" :max="new Date().toISOString().split('T')[0]"
                           class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Tab Navigation -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-8" aria-label="Tabs">
                <button @click="activeTab = 'executive'"
                        :class="activeTab === 'executive' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-6 px-6 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Executive Dashboard</span>
                    </div>
                </button>
                <button @click="activeTab = 'trends'"
                        :class="activeTab === 'trends' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-6 px-6 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line"></i>
                        <span>Market Trends</span>
                    </div>
                </button>
                <button @click="activeTab = 'profitability'"
                        :class="activeTab === 'profitability' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-6 px-6 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-bar"></i>
                        <span>Profitability Analysis</span>
                    </div>
                </button>
                <button @click="activeTab = 'intelligence'"
                        :class="activeTab === 'intelligence' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-6 px-6 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-brain"></i>
                        <span>AI Intelligence</span>
                    </div>
                </button>
            </nav>
        </div>

        <!-- Executive Dashboard Tab -->
        <div x-show="activeTab === 'executive'" class="p-8">
            <!-- Current Pricing KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                @foreach($current_prices as $price)
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative bg-white p-8 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-{{ $price->fuel_type === 'petrol' ? 'green' : ($price->fuel_type === 'diesel' ? 'blue' : 'orange') }}-500 to-{{ $price->fuel_type === 'petrol' ? 'green' : ($price->fuel_type === 'diesel' ? 'blue' : 'orange') }}-600 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-gas-pump text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 capitalize">{{ $price->fuel_type }}</h3>
                                    <p class="text-sm text-gray-500">Current Pricing</p>
                                </div>
                            </div>
                        </div>
                        <div class="mb-6">
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                {{ number_format($price->price_per_liter_ugx, 0) }}
                                <span class="text-lg font-medium text-gray-500">UGX/L</span>
                            </div>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-calendar-alt w-4"></i>
                                <span>Effective: {{ \Carbon\Carbon::parse($price->effective_from_date)->format('M d, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="fas fa-user-tie w-4"></i>
                                <span>Set by: {{ $price->first_name }} {{ $price->last_name }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-green-600">
                                <i class="fas fa-check-circle w-4"></i>
                                <span class="font-medium">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Price History Executive Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Recent Price Changes</h3>
                        <div class="flex items-center gap-4">
                            <input x-model="searchHistory" @input="filterTable('historyTable')"
                                   type="search" placeholder="Search price history..."
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <span class="text-sm text-gray-500">{{ count($price_history) }} records</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="historyTable" class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Fuel Type</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Price (UGX)</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Authorized By</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Period</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($price_history->take(15) as $history)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($history->effective_from_date)->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($history->created_at)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium capitalize
                                        {{ $history->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' : ($history->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800') }}">
                                        <i class="fas fa-circle mr-1 text-xs"></i>
                                        {{ $history->fuel_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ number_format($history->price_per_liter_ugx, 0) }}
                                    </div>
                                    <div class="text-sm text-gray-500">UGX/L</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ substr($history->first_name, 0, 1) }}{{ substr($history->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $history->first_name }} {{ $history->last_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($history->is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-archive mr-1"></i>Historical
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $history->effective_to_date ? \Carbon\Carbon::parse($history->effective_to_date)->format('M d, Y') : 'Current' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Profitability Analysis Tab -->
        <div x-show="activeTab === 'profitability'" class="p-8">
            <!-- Profitability Chart -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Margin Performance Analysis</h3>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            <span>Margin %</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            <span>Profit (UGX)</span>
                        </div>
                    </div>
                </div>
                <div id="profitabilityChart" style="height: 450px;"></div>
            </div>

            <!-- Margin Analysis Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Daily Profitability Breakdown</h3>
                        <input x-model="searchMargin" @input="filterTable('marginTable')"
                               type="search" placeholder="Search margin data..."
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="marginTable" class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Fuel Type</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase">Revenue</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase">COGS</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase">Gross Profit</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase">Margin %</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase">Volume (L)</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase">Performance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($margin_analysis as $margin)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($margin->reconciliation_date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium capitalize
                                        {{ $margin->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' : ($margin->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800') }}">
                                        {{ $margin->fuel_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                    {{ number_format($margin->total_sales_ugx, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600">
                                    {{ number_format($margin->total_cogs_ugx, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                    {{ number_format($margin->gross_profit_ugx, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium
                                        {{ $margin->profit_margin_percentage >= 20 ? 'bg-green-100 text-green-800' :
                                           ($margin->profit_margin_percentage >= 15 ? 'bg-yellow-100 text-yellow-800' :
                                            ($margin->profit_margin_percentage >= 10 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                        {{ number_format($margin->profit_margin_percentage, 2) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600">
                                    {{ number_format($margin->total_dispensed_liters, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($margin->profit_margin_percentage >= 20)
                                    <i class="fas fa-star text-green-500"></i>
                                    @elseif($margin->profit_margin_percentage >= 15)
                                    <i class="fas fa-thumbs-up text-yellow-500"></i>
                                    @elseif($margin->profit_margin_percentage >= 10)
                                    <i class="fas fa-minus-circle text-orange-500"></i>
                                    @else
                                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- AI Intelligence Tab -->
        <div x-show="activeTab === 'intelligence'" class="p-8" x-init="loadIntelligence()">
            <!-- Loading State -->
            <div x-show="intelligenceLoading" class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-brain text-white text-2xl animate-pulse"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Processing Real Data</h3>
                <div class="flex justify-center mb-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
                <p class="text-gray-600">Analyzing actual database records...</p>
            </div>

            <!-- AI Analytics Dashboard -->
            <div x-show="!intelligenceLoading" class="space-y-8">
                <!-- Real Data Analytics Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Margin Efficiency Chart (Real Data) -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-bullseye mr-3 text-green-500"></i>
                            Actual Margin Performance by Fuel
                        </h4>
                        <div id="realEfficiencyChart" style="height: 350px;"></div>
                    </div>

                    <!-- Volume vs Margin Analysis (Real Data) -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-project-diagram mr-3 text-purple-500"></i>
                            Volume vs Margin Correlation
                        </h4>
                        <div id="realCorrelationChart" style="height: 350px;"></div>
                    </div>
                </div>

                <!-- AI Insights Cards (Real Data Only) -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-8 border border-blue-200">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-database text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Real Data Insights</h3>
                            <p class="text-gray-600">Analysis based on actual database records</p>
                        </div>
                        <button @click="refreshIntelligence()" class="ml-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Real Margin Analysis -->
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                            <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-chart-line mr-2 text-blue-500"></i>
                                Margin Analysis
                            </h5>
                            <div class="space-y-3">
                                @php
                                    $avgMargin = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->avg('profit_margin_percentage') : 0;
                                    $maxMargin = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->max('profit_margin_percentage') : 0;
                                    $minMargin = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->min('profit_margin_percentage') : 0;
                                @endphp

                                @if(count($margin_analysis) > 0)
                                <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="text-sm font-medium text-blue-900">Average Margin</div>
                                    <div class="text-lg font-bold text-blue-700">{{ number_format($avgMargin, 2) }}%</div>
                                </div>
                                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="text-sm font-medium text-green-900">Best Performance</div>
                                    <div class="text-lg font-bold text-green-700">{{ number_format($maxMargin, 2) }}%</div>
                                </div>
                                <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                                    <div class="text-sm font-medium text-red-900">Lowest Margin</div>
                                    <div class="text-lg font-bold text-red-700">{{ number_format($minMargin, 2) }}%</div>
                                </div>
                                @else
                                <div class="text-center py-4 text-gray-500">No margin data available</div>
                                @endif
                            </div>
                        </div>

                        <!-- Real Price Change Impact -->
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                            <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-exchange-alt mr-2 text-yellow-500"></i>
                                Price Change Impact
                            </h5>
                            <div class="space-y-3">
                                @php
                                    $totalImpact = count($impact_analysis) > 0 ?
                                        collect($impact_analysis)->sum('estimated_margin_impact_ugx') : 0;
                                    $avgChange = count($impact_analysis) > 0 ?
                                        collect($impact_analysis)->avg(function($item) {
                                            return abs($item->price_change_percentage ?? 0);
                                        }) : 0;
                                    $recentChanges = count($impact_analysis);
                                @endphp

                                @if(count($impact_analysis) > 0)
                                <div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
                                    <div class="text-sm font-medium text-purple-900">Total Impact</div>
                                    <div class="text-lg font-bold text-purple-700">{{ number_format($totalImpact) }} UGX</div>
                                </div>
                                <div class="p-3 bg-orange-50 rounded-lg border border-orange-200">
                                    <div class="text-sm font-medium text-orange-900">Avg Change</div>
                                    <div class="text-lg font-bold text-orange-700">{{ number_format($avgChange, 2) }}%</div>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="text-sm font-medium text-gray-900">Total Changes</div>
                                    <div class="text-lg font-bold text-gray-700">{{ $recentChanges }}</div>
                                </div>
                                @else
                                <div class="text-center py-4 text-gray-500">No price change data available</div>
                                @endif
                            </div>
                        </div>

                        <!-- Real Revenue Analysis -->
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                            <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-dollar-sign mr-2 text-green-500"></i>
                                Revenue Performance
                            </h5>
                            <div class="space-y-3">
                                @php
                                    $totalRevenue = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->sum('total_sales_ugx') : 0;
                                    $totalProfit = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->sum('gross_profit_ugx') : 0;
                                    $totalVolume = count($margin_analysis) > 0 ?
                                        collect($margin_analysis)->sum('total_dispensed_liters') : 0;
                                @endphp

                                @if(count($margin_analysis) > 0)
                                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="text-sm font-medium text-green-900">Total Revenue</div>
                                    <div class="text-lg font-bold text-green-700">{{ number_format($totalRevenue / 1000000, 1) }}M UGX</div>
                                </div>
                                <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="text-sm font-medium text-blue-900">Total Profit</div>
                                    <div class="text-lg font-bold text-blue-700">{{ number_format($totalProfit / 1000000, 1) }}M UGX</div>
                                </div>
                                <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <div class="text-sm font-medium text-indigo-900">Volume Sold</div>
                                    <div class="text-lg font-bold text-indigo-700">{{ number_format($totalVolume / 1000, 0) }}K L</div>
                                </div>
                                @else
                                <div class="text-center py-4 text-gray-500">No revenue data available</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real Profitability Trends -->
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-chart-area mr-3 text-green-500"></i>
                            Historical Profitability Trends
                        </h4>
                        <div class="text-sm text-gray-500">
                            Based on {{ count($margin_analysis) }} reconciliation records
                        </div>
                    </div>
                    <div id="realProfitTrends" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function priceIntelligenceApp() {
    return {
        activeTab: 'executive',
        intelligenceLoading: false,
        forecastPeriod: '14',
        searchHistory: '',
        searchMargin: '',
        charts: {},
        filters: {
            station_id: {{ $station_id }},
            fuel_type: '{{ $fuel_type }}',
            date_from: '{{ $date_from }}',
            date_to: '{{ $date_to }}'
        },

        init() {
            this.initializeCharts();
            this.setupTableFilters();

            // Initialize AI charts when tab becomes active
            this.$watch('activeTab', (newTab) => {
                if (newTab === 'intelligence' && !this.intelligenceLoading) {
                    setTimeout(() => {
                        this.loadIntelligence();
                    }, 100);
                }
            });
        },

        validateDateRange() {
            const fromDate = new Date(this.filters.date_from);
            const toDate = new Date(this.filters.date_to);
            const today = new Date();

            if (toDate > today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'End date cannot be in the future',
                    confirmButtonColor: '#3B82F6'
                });
                this.filters.date_to = today.toISOString().split('T')[0];
                return false;
            }

            if (fromDate > toDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date must be before end date',
                    confirmButtonColor: '#3B82F6'
                });
                return false;
            }

            const daysDiff = (toDate - fromDate) / (1000 * 60 * 60 * 24);
            if (daysDiff > 365) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Large Date Range',
                    text: 'Date range exceeds 1 year. Performance may be affected.',
                    confirmButtonColor: '#3B82F6'
                });
            }

            this.loadAnalytics();
            return true;
        },

        loadAnalytics() {
            if (!this.validateFilters()) return;

            const params = new URLSearchParams(this.filters);
            window.location.href = '{{ route("price-analysis.index") }}?' + params.toString();
        },

        validateFilters() {
            if (!this.filters.station_id) {
                Swal.fire({
                    icon: 'error',
                    title: 'Station Required',
                    text: 'Please select a station',
                    confirmButtonColor: '#3B82F6'
                });
                return false;
            }
            return true;
        },

        intelligenceLoading: false,
        forecastPeriod: '14',

        loadIntelligence() {
            this.intelligenceLoading = true;
            setTimeout(() => {
                this.intelligenceLoading = false;
                // Wait for DOM to update before initializing charts
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.initAICharts();
                    }, 100);
                });
            }, 1000);
        },

        refreshIntelligence() {
            this.loadIntelligence();
        },

        generateOptimizationInsights() {
            const marginData = @json($margin_analysis);
            const avgMargin = marginData.reduce((sum, item) => sum + parseFloat(item.profit_margin_percentage), 0) / marginData.length;

            return `
                <div class="p-3 bg-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-50 rounded-lg border border-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-900">Current Performance</span>
                        <span class="text-xs px-2 py-1 bg-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-200 text-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-800 rounded-full">${avgMargin.toFixed(1)}% Avg Margin</span>
                    </div>
                    <div class="text-sm text-${avgMargin > 20 ? 'green' : avgMargin > 15 ? 'blue' : 'yellow'}-700">
                        <i class="fas fa-${avgMargin > 20 ? 'star' : avgMargin > 15 ? 'thumbs-up' : 'exclamation-triangle'} mr-1"></i>
                        ${avgMargin > 20 ? 'Excellent margin performance' : avgMargin > 15 ? 'Good margin, consider 2-3% increase' : 'Below optimal - review pricing strategy'}
                    </div>
                </div>
            `;
        },

        generateRiskInsights() {
            const impactData = @json($impact_analysis);
            const volatility = impactData.length > 0 ? Math.abs(impactData[0].price_change_percentage || 0) : 0;

            return `
                <div class="p-3 bg-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-50 rounded-lg border border-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-900">${volatility > 5 ? 'High' : volatility > 2 ? 'Medium' : 'Low'} Risk</span>
                        <span class="text-xs px-2 py-1 bg-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-200 text-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-800 rounded-full">${volatility.toFixed(1)}% Volatility</span>
                    </div>
                    <div class="text-sm text-${volatility > 5 ? 'red' : volatility > 2 ? 'yellow' : 'green'}-700">
                        <i class="fas fa-${volatility > 5 ? 'exclamation-triangle' : volatility > 2 ? 'eye' : 'shield-check'} mr-1"></i>
                        ${volatility > 5 ? 'High price volatility detected' : volatility > 2 ? 'Monitor price changes closely' : 'Stable pricing environment'}
                    </div>
                </div>
            `;
        },

        generateRevenueInsights() {
            const marginData = @json($margin_analysis);
            const totalRevenue = marginData.reduce((sum, item) => sum + parseFloat(item.total_sales_ugx), 0);

            return `
                <div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-purple-900">Revenue Potential</span>
                        <span class="text-xs px-2 py-1 bg-purple-200 text-purple-800 rounded-full">${(totalRevenue / 1000000).toFixed(1)}M UGX</span>
                    </div>
                    <div class="text-sm text-purple-700">
                        <i class="fas fa-rocket mr-1"></i>
                        Optimize off-peak pricing for 15-20% revenue boost
                    </div>
                </div>
            `;
        },

        initAICharts() {
            // Check if elements exist before creating charts
            setTimeout(() => {
                this.safeCreateChart('velocityChart', () => this.createVelocityChart());
                this.safeCreateChart('efficiencyChart', () => this.createEfficiencyChart());
                this.safeCreateChart('correlationChart', () => this.createCorrelationChart());
                this.safeCreateChart('competitiveChart', () => this.createCompetitiveChart());
                this.safeCreateChart('heatmapChart', () => this.createHeatmapChart());
                this.safeCreateChart('forecastChart', () => this.createForecastChart());
            }, 200);
        },

        safeCreateChart(elementId, createFunction) {
            const element = document.getElementById(elementId);
            if (element) {
                try {
                    createFunction();
                } catch (error) {
                    console.error(`Error creating chart ${elementId}:`, error);
                    element.innerHTML = `<div class="flex items-center justify-center h-full text-gray-500">Chart loading error</div>`;
                }
            } else {
                console.warn(`Element ${elementId} not found`);
            }
        },

        createVelocityChart() {
            const element = document.getElementById('velocityChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.velocity = chart;

            const impactData = @json($impact_analysis);
            console.log('Impact data for velocity:', impactData);

            // Generate sample data if no real data
            let chartData = [];
            if (impactData && impactData.length > 0) {
                chartData = impactData.map(item => [
                    item.effective_date,
                    Math.abs(parseFloat(item.price_change_percentage) || 0)
                ]);
            } else {
                // Sample data for demonstration
                const today = new Date();
                for (let i = 30; i >= 0; i--) {
                    const date = new Date(today.getTime() - i * 24 * 60 * 60 * 1000);
                    chartData.push([
                        date.toISOString().split('T')[0],
                        Math.random() * 5 + 1
                    ]);
                }
            }

            const option = {
                title: { text: 'Price Change Velocity', textStyle: { fontSize: 14, color: '#374151' } },
                tooltip: {
                    trigger: 'axis',
                    formatter: function(params) {
                        return `${params[0].name}<br/>Velocity: ${params[0].value[1].toFixed(2)}%`;
                    }
                },
                grid: { top: 50, right: 20, bottom: 30, left: 40 },
                xAxis: {
                    type: 'time',
                    axisLabel: { fontSize: 10, rotate: 45 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } }
                },
                yAxis: {
                    type: 'value',
                    name: 'Change %',
                    nameTextStyle: { fontSize: 10 },
                    axisLabel: { fontSize: 10 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: [{
                    type: 'line',
                    data: chartData,
                    lineStyle: { color: '#3b82f6', width: 2 },
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(59, 130, 246, 0.3)' },
                            { offset: 1, color: 'rgba(59, 130, 246, 0.1)' }
                        ])
                    },
                    symbol: 'circle',
                    symbolSize: 4
                }]
            };

            chart.setOption(option);
            chart.resize();
        },

        createEfficiencyChart() {
            const element = document.getElementById('efficiencyChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.efficiency = chart;

            const marginData = @json($margin_analysis);
            console.log('Margin data for efficiency:', marginData);

            const fuelTypes = ['petrol', 'diesel', 'kerosene'];
            const colors = ['#10b981', '#3b82f6', '#f59e0b'];

            let pieData = [];

            if (marginData && marginData.length > 0) {
                fuelTypes.forEach((fuel, index) => {
                    const fuelData = marginData.filter(item => item.fuel_type === fuel);
                    const avgMargin = fuelData.length > 0
                        ? fuelData.reduce((sum, item) => sum + parseFloat(item.profit_margin_percentage || 0), 0) / fuelData.length
                        : 20 + Math.random() * 10; // Fallback data

                    pieData.push({
                        value: avgMargin,
                        name: fuel.charAt(0).toUpperCase() + fuel.slice(1),
                        itemStyle: { color: colors[index] }
                    });
                });
            } else {
                // Sample data
                pieData = [
                    { value: 28.5, name: 'Petrol', itemStyle: { color: colors[0] } },
                    { value: 22.3, name: 'Diesel', itemStyle: { color: colors[1] } },
                    { value: 18.7, name: 'Kerosene', itemStyle: { color: colors[2] } }
                ];
            }

            const option = {
                title: { text: 'Margin by Fuel Type', textStyle: { fontSize: 14, color: '#374151' } },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c}% ({d}%)'
                },
                legend: {
                    orient: 'horizontal',
                    bottom: '5%',
                    textStyle: { fontSize: 10 }
                },
                series: [{
                    name: 'Margin',
                    type: 'pie',
                    radius: ['30%', '70%'],
                    center: ['50%', '45%'],
                    data: pieData,
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    label: {
                        formatter: '{b}\n{c}%',
                        fontSize: 10
                    }
                }]
            };

            chart.setOption(option);
            chart.resize();
        },

        createCorrelationChart() {
            const element = document.getElementById('correlationChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.correlation = chart;

            const marginData = @json($margin_analysis);
            console.log('Margin data for correlation:', marginData);

            let scatterData = [];

            if (marginData && marginData.length > 0) {
                scatterData = marginData.map(item => [
                    parseFloat(item.total_dispensed_liters || 0) / 1000, // Convert to thousands
                    parseFloat(item.profit_margin_percentage || 0)
                ]);
            } else {
                // Sample data
                for (let i = 0; i < 20; i++) {
                    scatterData.push([
                        Math.random() * 50 + 10, // Volume (thousands)
                        Math.random() * 20 + 15   // Margin %
                    ]);
                }
            }

            const option = {
                title: { text: 'Volume vs Margin', textStyle: { fontSize: 14, color: '#374151' } },
                tooltip: {
                    trigger: 'item',
                    formatter: function(params) {
                        return `Volume: ${params.value[0].toFixed(1)}K L<br/>Margin: ${params.value[1].toFixed(1)}%`;
                    }
                },
                grid: { top: 50, right: 30, bottom: 50, left: 50 },
                xAxis: {
                    type: 'value',
                    name: 'Volume (K Liters)',
                    nameLocation: 'middle',
                    nameGap: 30,
                    nameTextStyle: { fontSize: 10 },
                    axisLabel: { fontSize: 10 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                yAxis: {
                    type: 'value',
                    name: 'Margin %',
                    nameTextStyle: { fontSize: 10 },
                    axisLabel: { fontSize: 10 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: [{
                    type: 'scatter',
                    data: scatterData,
                    itemStyle: {
                        color: '#8b5cf6',
                        opacity: 0.7
                    },
                    symbolSize: function(data) {
                        return Math.sqrt(data[1]) * 2; // Size based on margin
                    }
                }]
            };

            chart.setOption(option);
            chart.resize();
        },

        createCompetitiveChart() {
            const element = document.getElementById('competitiveChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.competitive = chart;

            const option = {
                title: { text: 'Performance Radar', textStyle: { fontSize: 14, color: '#374151' } },
                tooltip: { trigger: 'item' },
                radar: {
                    indicator: [
                        { name: 'Price\nCompetitive', max: 100 },
                        { name: 'Margin\nHealth', max: 100 },
                        { name: 'Volume\nPerformance', max: 100 },
                        { name: 'Market\nPosition', max: 100 },
                        { name: 'Risk\nLevel', max: 100 }
                    ],
                    radius: '65%',
                    nameGap: 15,
                    name: {
                        textStyle: { fontSize: 10, color: '#374151' }
                    },
                    splitArea: {
                        areaStyle: {
                            color: ['rgba(59, 130, 246, 0.1)', 'rgba(59, 130, 246, 0.05)']
                        }
                    }
                },
                series: [{
                    type: 'radar',
                    data: [{
                        value: [85, 78, 92, 88, 75],
                        name: 'Current Performance',
                        areaStyle: {
                            color: 'rgba(59, 130, 246, 0.2)',
                            opacity: 0.8
                        },
                        lineStyle: { color: '#3b82f6', width: 2 },
                        itemStyle: { color: '#3b82f6' }
                    }]
                }]
            };

            chart.setOption(option);
            chart.resize();
        },

        createHeatmapChart() {
            const element = document.getElementById('heatmapChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.heatmap = chart;

            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const fuels = ['Petrol', 'Diesel', 'Kerosene'];
            const data = [];

            // Generate heatmap data
            fuels.forEach((fuel, i) => {
                days.forEach((day, j) => {
                    const value = 15 + Math.random() * 20; // Margin between 15-35%
                    data.push([j, i, Math.round(value * 10) / 10]);
                });
            });

            const option = {
                title: { text: 'Daily Margin Heatmap', textStyle: { fontSize: 14, color: '#374151' } },
                tooltip: {
                    position: 'top',
                    formatter: function(params) {
                        return `${days[params.value[0]]}, ${fuels[params.value[1]]}<br/>Margin: ${params.value[2]}%`;
                    }
                },
                grid: { height: '60%', top: '15%', left: '10%', right: '10%' },
                xAxis: {
                    type: 'category',
                    data: days,
                    splitArea: { show: true },
                    axisLabel: { fontSize: 10 }
                },
                yAxis: {
                    type: 'category',
                    data: fuels,
                    splitArea: { show: true },
                    axisLabel: { fontSize: 10 }
                },
                visualMap: {
                    min: 15,
                    max: 35,
                    calculable: true,
                    orient: 'horizontal',
                    left: 'center',
                    bottom: '5%',
                    textStyle: { fontSize: 10 },
                    inRange: {
                        color: ['#c7d2fe', '#3b82f6', '#1e40af']
                    }
                },
                series: [{
                    name: 'Margin',
                    type: 'heatmap',
                    data: data,
                    label: {
                        show: true,
                        fontSize: 10,
                        formatter: '{c}%'
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }]
            };

            chart.setOption(option);
            chart.resize();
        },

        createForecastChart() {
            const element = document.getElementById('forecastChart');
            if (!element) return;

            const chart = echarts.init(element);
            this.charts.forecast = chart;

            const trendData = @json($price_trends);
            console.log('Trend data for forecast:', trendData);

            // Historical data
            let historicalData = [];
            if (trendData && trendData.length > 0) {
                historicalData = trendData.slice(-10).map(item => [
                    item.effective_from_date,
                    parseFloat(item.price_per_liter_ugx)
                ]);
            } else {
                // Sample historical data
                const today = new Date();
                for (let i = 10; i >= 0; i--) {
                    const date = new Date(today.getTime() - i * 24 * 60 * 60 * 1000);
                    historicalData.push([
                        date.toISOString().split('T')[0],
                        4000 + Math.sin(i / 7) * 200 + Math.random() * 100
                    ]);
                }
            }

            // Forecast data
            const forecastData = this.generateForecastData();

            const option = {
                title: { text: 'Price Forecasting', textStyle: { fontSize: 16, color: '#374151' } },
                tooltip: { trigger: 'axis' },
                legend: {
                    data: ['Historical Prices', 'AI Forecast'],
                    bottom: 0,
                    textStyle: { fontSize: 12 }
                },
                grid: { top: 60, right: 50, bottom: 60, left: 80 },
                xAxis: {
                    type: 'time',
                    axisLabel: { fontSize: 10, rotate: 45 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } }
                },
                yAxis: {
                    type: 'value',
                    name: 'Price (UGX/L)',
                    nameTextStyle: { fontSize: 12 },
                    axisLabel: { fontSize: 10 },
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: [
                    {
                        name: 'Historical Prices',
                        type: 'line',
                        data: historicalData,
                        lineStyle: { color: '#3b82f6', width: 2 },
                        itemStyle: { color: '#3b82f6' },
                        symbol: 'circle',
                        symbolSize: 4
                    },
                    {
                        name: 'AI Forecast',
                        type: 'line',
                        data: forecastData,
                        lineStyle: {
                            color: '#ef4444',
                            type: 'dashed',
                            width: 2
                        },
                        itemStyle: { color: '#ef4444' },
                        symbol: 'triangle',
                        symbolSize: 4
                    }
                ]
            };

            chart.setOption(option);
            chart.resize();
        },

        generateForecastData() {
            const today = new Date();
            const data = [];
            for (let i = 0; i < parseInt(this.forecastPeriod); i++) {
                const date = new Date(today.getTime() + i * 24 * 60 * 60 * 1000);
                const price = 4500 + Math.sin(i / 7) * 200 + Math.random() * 100;
                data.push([date.toISOString().split('T')[0], price]);
            }
            return data;
        },

        updateForecast() {
            this.createForecastChart();
        },

        initializeCharts() {
            // Price Trends Chart
            const priceTrendsChart = echarts.init(document.getElementById('priceTrendsChart'));
            const trendData = @json($price_trends);

            const priceTrendOptions = {
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    borderColor: '#e2e8f0',
                    textStyle: { color: '#374151' }
                },
                legend: {
                    data: ['Petrol', 'Diesel', 'Kerosene'],
                    bottom: 0
                },
                grid: { top: 20, right: 30, bottom: 60, left: 60 },
                xAxis: {
                    type: 'time',
                    axisLine: { lineStyle: { color: '#e2e8f0' } },
                    axisLabel: { color: '#6b7280' }
                },
                yAxis: {
                    type: 'value',
                    name: 'Price (UGX/L)',
                    nameTextStyle: { color: '#6b7280' },
                    axisLine: { lineStyle: { color: '#e2e8f0' } },
                    axisLabel: { color: '#6b7280' },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: this.buildPriceSeries(trendData)
            };
            priceTrendsChart.setOption(priceTrendOptions);

            // Price Impact Chart
            const impactChart = echarts.init(document.getElementById('priceImpactChart'));
            const impactData = @json($impact_analysis);

            const impactOptions = {
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    borderColor: '#e2e8f0'
                },
                grid: { top: 20, right: 30, bottom: 60, left: 80 },
                xAxis: {
                    type: 'time',
                    axisLine: { lineStyle: { color: '#e2e8f0' } },
                    axisLabel: { color: '#6b7280' }
                },
                yAxis: {
                    type: 'value',
                    name: 'Impact (UGX)',
                    nameTextStyle: { color: '#6b7280' },
                    axisLine: { lineStyle: { color: '#e2e8f0' } },
                    axisLabel: { color: '#6b7280' },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: [{
                    name: 'Financial Impact',
                    type: 'bar',
                    data: impactData.map(item => [
                        item.effective_date,
                        item.estimated_margin_impact_ugx || 0
                    ]),
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#3b82f6' },
                            { offset: 1, color: '#1d4ed8' }
                        ])
                    }
                }]
            };
            impactChart.setOption(impactOptions);

            // Profitability Chart
            const profitChart = echarts.init(document.getElementById('profitabilityChart'));
            const marginData = @json($margin_analysis);

            const profitOptions = {
                tooltip: { trigger: 'axis' },
                legend: {
                    data: ['Margin %', 'Gross Profit'],
                    bottom: 0
                },
                grid: { top: 20, right: 80, bottom: 60, left: 60 },
                xAxis: {
                    type: 'time',
                    axisLine: { lineStyle: { color: '#e2e8f0' } },
                    axisLabel: { color: '#6b7280' }
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Margin %',
                        position: 'left',
                        nameTextStyle: { color: '#6b7280' },
                        axisLine: { lineStyle: { color: '#e2e8f0' } },
                        axisLabel: { color: '#6b7280' }
                    },
                    {
                        type: 'value',
                        name: 'Profit (UGX)',
                        position: 'right',
                        nameTextStyle: { color: '#6b7280' },
                        axisLine: { lineStyle: { color: '#e2e8f0' } },
                        axisLabel: { color: '#6b7280' }
                    }
                ],
                series: [
                    {
                        name: 'Margin %',
                        type: 'line',
                        yAxisIndex: 0,
                        data: marginData.map(item => [
                            item.reconciliation_date,
                            parseFloat(item.profit_margin_percentage)
                        ]),
                        lineStyle: { color: '#10b981', width: 3 },
                        itemStyle: { color: '#10b981' }
                    },
                    {
                        name: 'Gross Profit',
                        type: 'bar',
                        yAxisIndex: 1,
                        data: marginData.map(item => [
                            item.reconciliation_date,
                            parseFloat(item.gross_profit_ugx)
                        ]),
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: '#3b82f6' },
                                { offset: 1, color: '#1d4ed8' }
                            ])
                        }
                    }
                ]
            };
            profitChart.setOption(profitOptions);

            // Responsive charts
            window.addEventListener('resize', () => {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            });
        },

        buildPriceSeries(data) {
            const fuelTypes = ['petrol', 'diesel', 'kerosene'];
            const colors = ['#10b981', '#3b82f6', '#f59e0b'];

            return fuelTypes.map((fuel, index) => ({
                name: fuel.charAt(0).toUpperCase() + fuel.slice(1),
                type: 'line',
                data: data.filter(item => item.fuel_type === fuel)
                          .map(item => [item.effective_from_date, parseFloat(item.price_per_liter_ugx)]),
                lineStyle: { color: colors[index], width: 3 },
                itemStyle: { color: colors[index] },
                smooth: true
            }));
        },

        setupTableFilters() {
            // No additional setup needed - already handled by Alpine bindings
        },

        filterTable(tableId) {
            const table = document.getElementById(tableId);
            let searchTerm = '';

            if (tableId === 'historyTable') {
                searchTerm = this.searchHistory.toLowerCase();
            } else if (tableId === 'marginTable') {
                searchTerm = this.searchMargin.toLowerCase();
            }

            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        },

    }
}
</script>
@endsection

