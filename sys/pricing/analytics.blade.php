@extends('layouts.app')

@section('title', 'Pricing Analytics')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pricing Analytics Wizard</h1>
        <p class="text-sm text-gray-600 mt-1">FIFO-integrated analytics for {{ $selected_station->name }}</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('pricing.index', ['station_id' => $selected_station->id]) }}"
           class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
        <a href="{{ route('pricing.create', ['station_id' => $selected_station->id]) }}"
           class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Update Price
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="analyticsWizard()" x-init="init()" class="space-y-6">
    <!-- Wizard Progress -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Analytics Progress</h2>
            <div class="text-sm text-gray-500">Step <span x-text="currentStep"></span> of 4</div>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-2" @click="setStep(1)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium cursor-pointer transition-all duration-300"
                     :class="currentStep >= 1 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'">1</div>
                <span class="text-sm font-medium cursor-pointer"
                      :class="currentStep >= 1 ? 'text-gray-900' : 'text-gray-500'">Filters</span>
            </div>

            <div class="flex-1 h-1 rounded-full bg-gray-200 mx-4">
                <div class="h-1 rounded-full bg-gray-900 transition-all duration-500"
                     :style="`width: ${Math.max(0, (currentStep - 1) * 33.33)}%`"></div>
            </div>

            <div class="flex items-center space-x-2" @click="setStep(2)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium cursor-pointer transition-all duration-300"
                     :class="currentStep >= 2 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'">2</div>
                <span class="text-sm font-medium cursor-pointer"
                      :class="currentStep >= 2 ? 'text-gray-900' : 'text-gray-500'">Trends</span>
            </div>

            <div class="flex-1 h-1 rounded-full bg-gray-200 mx-4">
                <div class="h-1 rounded-full bg-gray-900 transition-all duration-500"
                     :style="`width: ${Math.max(0, (currentStep - 2) * 50)}%`"></div>
            </div>

            <div class="flex items-center space-x-2" @click="setStep(3)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium cursor-pointer transition-all duration-300"
                     :class="currentStep >= 3 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'">3</div>
                <span class="text-sm font-medium cursor-pointer"
                      :class="currentStep >= 3 ? 'text-gray-900' : 'text-gray-500'">Margins</span>
            </div>

            <div class="flex-1 h-1 rounded-full bg-gray-200 mx-4">
                <div class="h-1 rounded-full bg-gray-900 transition-all duration-500"
                     :style="`width: ${Math.max(0, (currentStep - 3) * 100)}%`"></div>
            </div>

            <div class="flex items-center space-x-2" @click="setStep(4)">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium cursor-pointer transition-all duration-300"
                     :class="currentStep >= 4 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'">4</div>
                <span class="text-sm font-medium cursor-pointer"
                      :class="currentStep >= 4 ? 'text-gray-900' : 'text-gray-500'">Impact</span>
            </div>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between">
            <button @click="previousStep()" x-show="currentStep > 1" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Previous
            </button>
            <div x-show="currentStep === 1"></div>

            <button @click="nextStep()" x-show="currentStep < 4" class="btn btn-primary">
                Next<i class="fas fa-arrow-right ml-2"></i>
            </button>
            <div x-show="currentStep === 4"></div>
        </div>
    </div>

    <!-- Step 1: Filters & Period Selection -->
    <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0">

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-filter text-white text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Analysis Filters</h2>
                        <p class="text-sm text-gray-600">Configure analytics parameters</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <form method="GET" action="{{ route('pricing.analytics') }}" class="space-y-6">
                    <input type="hidden" name="station_id" value="{{ $selected_station->id }}">

                    <!-- Station Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $selected_station->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $selected_station->location }}</p>
                            </div>
                            <div class="text-sm text-gray-500">{{ $selected_station->currency_code }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Fuel Type Filter -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Fuel Type</label>
                            <select name="fuel_type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent transition-all">
                                <option value="">All Fuel Types</option>
                                <option value="petrol" {{ $fuel_type === 'petrol' ? 'selected' : '' }}>Petrol</option>
                                <option value="diesel" {{ $fuel_type === 'diesel' ? 'selected' : '' }}>Diesel</option>
                                <option value="kerosene" {{ $fuel_type === 'kerosene' ? 'selected' : '' }}>Kerosene</option>
                            </select>
                        </div>

                        <!-- Period Selection -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Analysis Period</label>
                            <select name="period_days"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent transition-all">
                                <option value="7" {{ $period_days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="14" {{ $period_days == 14 ? 'selected' : '' }}>Last 2 Weeks</option>
                                <option value="30" {{ $period_days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="60" {{ $period_days == 60 ? 'selected' : '' }}>Last 60 Days</option>
                                <option value="90" {{ $period_days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>

                <!-- Current Filter Status -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center space-x-2 text-sm">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span class="text-blue-800 font-medium">Current Analysis:</span>
                        <span class="text-blue-700">
                            {{ $fuel_type ? ucfirst($fuel_type) : 'All Fuels' }} •
                            {{ $period_days }} days •
                            {{ $selected_station->name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Price Trends -->
    <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0">

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Price Trends</h2>
                        <p class="text-sm text-gray-600">Historical price movements</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if(isset($analytics['price_trends']) && $analytics['price_trends']->isNotEmpty())
                    @foreach($analytics['price_trends'] as $fuel_type_key => $trends)
                    <div class="mb-8 last:mb-0">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 capitalize flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3
                                    {{ $fuel_type_key === 'petrol' ? 'bg-green-500' : '' }}
                                    {{ $fuel_type_key === 'diesel' ? 'bg-blue-500' : '' }}
                                    {{ $fuel_type_key === 'kerosene' ? 'bg-yellow-500' : '' }}">
                                </div>
                                {{ $fuel_type_key }} Trends
                            </h3>
                            <span class="text-sm text-gray-500">{{ $trends->count() }} data points</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Latest Price</div>
                                <div class="text-xl font-bold text-gray-900">
                                    {{ number_format($trends->last()->new_price_ugx ?? 0, 2) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $selected_station->currency_code }}/L</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Average Change</div>
                                <div class="text-xl font-bold {{ $trends->avg('price_change_percentage') >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($trends->avg('price_change_percentage'), 1) }}%
                                </div>
                                <div class="text-xs text-gray-500">per change</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Price Range</div>
                                <div class="text-xl font-bold text-gray-900">
                                    {{ number_format($trends->min('old_price_ugx'), 0) }} - {{ number_format($trends->max('new_price_ugx'), 0) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $selected_station->currency_code }}/L</div>
                            </div>
                        </div>

                        <!-- Trend Timeline -->
                        <div class="space-y-3">
                            @foreach($trends->take(10) as $trend)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($trend->effective_date)->format('M j, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ number_format($trend->old_price_ugx, 2) }} → {{ number_format($trend->new_price_ugx, 2) }}
                                    </div>
                                </div>
                                <div class="text-sm font-medium {{ $trend->price_change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $trend->price_change_percentage >= 0 ? '+' : '' }}{{ number_format($trend->price_change_percentage, 1) }}%
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Price Trends</h3>
                    <p class="text-gray-600 mb-6">No price changes found for the selected period.</p>
                    <a href="{{ route('pricing.create', ['station_id' => $selected_station->id]) }}"
                       class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Create First Price
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Step 3: Margin Analysis -->
    <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0">

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">FIFO Margin Analysis</h2>
                        <p class="text-sm text-gray-600">Real-time profitability metrics</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if(isset($analytics['margin_analysis']) && $analytics['margin_analysis']->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($analytics['margin_analysis'] as $margin)
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center
                                {{ $margin->fuel_type === 'petrol' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $margin->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $margin->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                <i class="fas fa-tint text-lg"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Margin</div>
                                <div class="text-2xl font-bold {{ $margin->margin_percentage >= 15 ? 'text-green-600' : ($margin->margin_percentage >= 5 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($margin->margin_percentage, 1) }}%
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 capitalize mb-3">{{ $margin->fuel_type }}</h3>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Selling Price</span>
                                <span class="font-medium text-gray-900">{{ number_format($margin->price_per_liter_ugx, 2) }}</span>
                            </div>

                            @if($margin->avg_fifo_cost > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">FIFO Cost</span>
                                <span class="font-medium text-gray-900">{{ number_format($margin->avg_fifo_cost, 2) }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Profit/Liter</span>
                                <span class="font-medium text-green-600">{{ number_format($margin->price_per_liter_ugx - $margin->avg_fifo_cost, 2) }}</span>
                            </div>
                            @else
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">FIFO Cost</span>
                                <span class="text-gray-400">No data</span>
                            </div>
                            @endif

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Stock Volume</span>
                                <span class="font-medium text-gray-900">{{ number_format($margin->total_volume ?? 0, 0) }}L</span>
                            </div>
                        </div>

                        <!-- Margin Status Bar -->
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Margin Health</span>
                                <span>{{ $margin->margin_percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300 {{ $margin->margin_percentage >= 15 ? 'bg-green-500' : ($margin->margin_percentage >= 5 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                     style="width: {{ min(100, $margin->margin_percentage * 4) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-percentage text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Margin Data</h3>
                    <p class="text-gray-600">No pricing or inventory data available for margin analysis.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Step 4: Volume Impact -->
    <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0">

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-bar text-purple-600 text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Volume Impact Analysis</h2>
                        <p class="text-sm text-gray-600">Sales performance & daily margins</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if(isset($analytics['volume_impact']) && $analytics['volume_impact']->isNotEmpty())
                    @foreach($analytics['volume_impact'] as $fuel_type_key => $impacts)
                    <div class="mb-8 last:mb-0">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 capitalize flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3
                                    {{ $fuel_type_key === 'petrol' ? 'bg-green-500' : '' }}
                                    {{ $fuel_type_key === 'diesel' ? 'bg-blue-500' : '' }}
                                    {{ $fuel_type_key === 'kerosene' ? 'bg-yellow-500' : '' }}">
                                </div>
                                {{ $fuel_type_key }} Performance
                            </h3>
                            <div class="text-sm text-gray-500">{{ $impacts->count() }} days analyzed</div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Total Volume</div>
                                <div class="text-xl font-bold text-gray-900">
                                    {{ number_format($impacts->sum('total_dispensed_liters'), 0) }}L
                                </div>
                                <div class="text-xs text-gray-500">{{ $period_days }} days</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Total Revenue</div>
                                <div class="text-xl font-bold text-gray-900">
                                    {{ number_format($impacts->sum('total_sales_ugx'), 0) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $selected_station->currency_code }}</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Total Profit</div>
                                <div class="text-xl font-bold text-green-600">
                                    {{ number_format($impacts->sum('gross_profit_ugx'), 0) }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $selected_station->currency_code }}</div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Avg Daily Margin</div>
                                <div class="text-xl font-bold text-blue-600">
                                    {{ number_format($impacts->avg('daily_margin_percentage'), 1) }}%
                                </div>
                                <div class="text-xs text-gray-500">FIFO-based</div>
                            </div>
                        </div>

                        <!-- Daily Performance Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($impacts->sortByDesc('reconciliation_date')->take(15) as $impact)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($impact->reconciliation_date)->format('M j, Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ number_format($impact->total_dispensed_liters, 0) }}L
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ number_format($impact->total_sales_ugx, 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-green-600 font-medium">
                                            {{ number_format($impact->gross_profit_ugx, 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium {{ $impact->daily_margin_percentage >= 15 ? 'text-green-600' : ($impact->daily_margin_percentage >= 5 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($impact->daily_margin_percentage, 1) }}%
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-bar text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Volume Data</h3>
                    <p class="text-gray-600">No sales data available for the selected period.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function analyticsWizard() {
    return {
        currentStep: 1,

        init() {
            this.setupEventListeners();
        },

        setStep(step) {
            if (step >= 1 && step <= 4) {
                this.currentStep = step;
            }
        },

        nextStep() {
            if (this.currentStep < 4) {
                this.currentStep++;
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        setupEventListeners() {
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight' && this.currentStep < 4) {
                    this.nextStep();
                } else if (e.key === 'ArrowLeft' && this.currentStep > 1) {
                    this.previousStep();
                } else if (e.key >= '1' && e.key <= '4') {
                    this.setStep(parseInt(e.key));
                }
            });
        }
    }
}

// Enhanced error handling
document.addEventListener('DOMContentLoaded', function() {
    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Analytics Error',
        text: '{{ session('error') }}',
        confirmButtonColor: '#1f2937'
    });
    @endif

    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: '{{ session('success') }}',
        confirmButtonColor: '#1f2937'
    });
    @endif
});
</script>
@endsection
