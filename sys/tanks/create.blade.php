@extends('layouts.app')

@section('title', 'Create Tank')

@section('breadcrumb')
<a href="{{ route('tanks.index') }}" class="text-muted-foreground hover:text-foreground">Tank Management</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground">Create Tank</span>
@endsection

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Create New Tank</h1>
        <p class="text-muted-foreground mt-2">Add a new fuel tank with integrated pricing setup</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('tanks.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Tanks
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="tankCreateWizard()" x-init="init()" class="max-w-4xl mx-auto">

    @if(!request('station_id'))
    <!-- Station Selection Step -->
    <div class="card border-2 border-dashed border-border bg-background/50 backdrop-blur-sm">
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary/10 flex items-center justify-center">
                <i class="fas fa-building text-2xl text-primary"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">Select Station</h2>
            <p class="text-muted-foreground mb-6">Choose the station where this tank will be located</p>

            <div class="max-w-md mx-auto">
                <form method="GET" action="{{ route('tanks.create') }}" class="space-y-4">
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

    <!-- Wizard Container -->
    <div class="card border-0 shadow-xl bg-background/95 backdrop-blur-sm">
        <!-- Progress Indicator -->
        <div class="border-b border-border bg-muted/30 px-6 py-4">
            <div class="flex items-center justify-between">
                @php
                    $current_station = $accessible_stations->firstWhere('id', request('station_id'));
                @endphp
                <div>
                    <h2 class="text-lg font-semibold text-foreground">{{ $current_station->name ?? 'Station' }}</h2>
                    <p class="text-sm text-muted-foreground">{{ $current_station->location ?? '' }}</p>
                </div>

                <!-- Progress Steps -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div :class="{'bg-primary text-primary-foreground': currentStep >= 1, 'bg-muted text-muted-foreground': currentStep < 1}"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-200">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="{'text-foreground': currentStep >= 1, 'text-muted-foreground': currentStep < 1}">
                            Tank Details
                        </span>
                    </div>

                    <div class="w-8 h-0.5" :class="{'bg-primary': currentStep >= 2, 'bg-muted': currentStep < 2}"></div>

                    <div class="flex items-center">
                        <div :class="{'bg-primary text-primary-foreground': currentStep >= 2, 'bg-muted text-muted-foreground': currentStep < 2}"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-200">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="{'text-foreground': currentStep >= 2, 'text-muted-foreground': currentStep < 2}">
                            Pricing Setup
                        </span>
                    </div>

                    <div class="w-8 h-0.5" :class="{'bg-primary': currentStep >= 3, 'bg-muted': currentStep < 3}"></div>

                    <div class="flex items-center">
                        <div :class="{'bg-primary text-primary-foreground': currentStep >= 3, 'bg-muted text-muted-foreground': currentStep < 3}"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-200">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium" :class="{'text-foreground': currentStep >= 3, 'text-muted-foreground': currentStep < 3}">
                            Review
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm()" method="POST" action="{{ route('tanks.store') }}" class="p-6" id="tank-create-form">
            @csrf
            <input type="hidden" name="station_id" value="{{ request('station_id') }}">

            <!-- Step 1: Tank Details -->
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-4">Tank Configuration</h3>
                        <p class="text-sm text-muted-foreground mb-6">Configure the basic tank parameters and specifications</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tank Number -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Tank Number *</label>
                            <input type="text"
                                   x-model="form.tank_number"
                                   @input="validateTankNumber()"
                                   class="input w-full"
                                   placeholder="e.g., T-001, TANK-01"
                                   required>
                            <div x-show="errors.tank_number" class="text-sm text-red-600" x-text="errors.tank_number"></div>
                            <p class="text-xs text-muted-foreground">Use uppercase letters, numbers, hyphens, and underscores only</p>
                        </div>

                        <!-- Fuel Type -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Fuel Type *</label>
                            <select x-model="form.fuel_type" @change="updatePricingDefaults()" class="select w-full" required>
                                <option value="">Select Fuel Type</option>
                                @foreach($fuel_types as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            <div x-show="errors.fuel_type" class="text-sm text-red-600" x-text="errors.fuel_type"></div>
                        </div>

                        <!-- Capacity -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Tank Capacity (Liters) *</label>
                            <input type="number"
                                   x-model="form.capacity_liters"
                                   @input="validateCapacity(); calculateFillPercentage()"
                                   class="input w-full"
                                   min="1000"
                                   step="0.001"
                                   placeholder="50000"
                                   required>
                            <div x-show="errors.capacity_liters" class="text-sm text-red-600" x-text="errors.capacity_liters"></div>
                            <p class="text-xs text-muted-foreground">Minimum 1,000 liters required</p>
                        </div>

                        <!-- Current Volume -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Current Volume (Liters) *</label>
                            <input type="number"
                                   x-model="form.current_volume_liters"
                                   @input="validateCurrentVolume(); calculateFillPercentage()"
                                   class="input w-full"
                                   min="0"
                                   step="0.001"
                                   placeholder="0"
                                   required>
                            <div x-show="errors.current_volume_liters" class="text-sm text-red-600" x-text="errors.current_volume_liters"></div>
                            <div x-show="form.capacity_liters && form.current_volume_liters" class="text-xs text-muted-foreground">
                                Fill Level: <span x-text="fillPercentage"></span>%
                            </div>
                        </div>
                    </div>

                    <!-- Visual Fill Indicator -->
                    <div x-show="form.capacity_liters && form.current_volume_liters" class="card bg-muted/30">
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-foreground mb-3">Tank Fill Visualization</h4>
                            <div class="relative w-full h-8 bg-muted rounded-lg overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-300 rounded-lg"
                                     :style="`width: ${Math.min(fillPercentage, 100)}%`"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-xs font-medium text-foreground">
                                    <span x-text="`${fillPercentage}% (${Number(form.current_volume_liters).toLocaleString()}L / ${Number(form.capacity_liters).toLocaleString()}L)`"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1 Actions -->
                    <div class="flex justify-end">
                        <button type="button" @click="nextStep()" :disabled="!canProceedToStep2()"
                                class="btn btn-primary" :class="{'opacity-50 cursor-not-allowed': !canProceedToStep2()}">
                            Next: Pricing Setup
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Pricing Setup -->
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-4">Pricing Configuration</h3>
                        <p class="text-sm text-muted-foreground mb-6">Set initial cost and selling prices for this tank</p>
                    </div>

                    <!-- Pricing Context -->
                    <div x-show="form.fuel_type && pricingContext[form.fuel_type]" class="card bg-muted/30">
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-foreground mb-3">Current Market Context</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-muted-foreground">Latest Avg Cost:</p>
                                    <p class="font-medium" x-text="pricingContext[form.fuel_type]?.latest_cost || 'N/A'"></p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Current Selling Price:</p>
                                    <p class="font-medium" x-text="pricingContext[form.fuel_type]?.current_price || 'N/A'"></p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Current Margin:</p>
                                    <p class="font-medium" x-text="pricingContext[form.fuel_type]?.current_margin || 'N/A'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Initial Cost -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Initial Cost per Liter ({{ $current_station->currency_code ?? 'UGX' }}) *</label>
                            <input type="number"
                                   x-model="form.initial_cost_per_liter"
                                   @input="calculateMargin()"
                                   class="input w-full"
                                   min="1"
                                   step="0.0001"
                                   placeholder="4500"
                                   required>
                            <div x-show="errors.initial_cost_per_liter" class="text-sm text-red-600" x-text="errors.initial_cost_per_liter"></div>
                            <p class="text-xs text-muted-foreground">Cost price for initial inventory</p>
                        </div>

                        <!-- Selling Price -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Selling Price per Liter ({{ $current_station->currency_code ?? 'UGX' }}) *</label>
                            <input type="number"
                                   x-model="form.selling_price_per_liter"
                                   @input="calculateMargin(); validatePriceChange()"
                                   class="input w-full"
                                   min="1"
                                   step="0.0001"
                                   placeholder="5000"
                                   required>
                            <div x-show="errors.selling_price_per_liter" class="text-sm text-red-600" x-text="errors.selling_price_per_liter"></div>
                            <p class="text-xs text-muted-foreground">Public selling price</p>
                        </div>

                        <!-- Price Effective Date -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-foreground">Price Effective Date *</label>
                            <input type="date"
                                   x-model="form.price_effective_date"
                                   class="input w-full md:w-auto"
                                   :min="new Date().toISOString().split('T')[0]"
                                   required>
                            <div x-show="errors.price_effective_date" class="text-sm text-red-600" x-text="errors.price_effective_date"></div>
                        </div>
                    </div>

                    <!-- Margin Analysis -->
                    <div x-show="form.initial_cost_per_liter && form.selling_price_per_liter" class="card">
                        <div class="p-4">
                            <h4 class="text-sm font-medium text-foreground mb-3">Margin Analysis</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center p-3 rounded-lg" :class="marginStatus.color">
                                    <p class="text-2xl font-bold" x-text="marginPercentage"></p>
                                    <p class="text-sm">Margin Percentage</p>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-muted/50">
                                    <p class="text-2xl font-bold" x-text="Number(form.selling_price_per_liter - form.initial_cost_per_liter).toLocaleString()"></p>
                                    <p class="text-sm">Profit per Liter ({{ $current_station->currency_code ?? 'UGX' }})</p>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-muted/50">
                                    <p class="text-2xl font-bold" x-text="totalPotentialProfit"></p>
                                    <p class="text-sm">Total Potential Profit ({{ $current_station->currency_code ?? 'UGX' }})</p>
                                </div>
                            </div>
                            <div x-show="marginWarning" class="mt-3 text-sm" :class="marginStatus.textColor" x-text="marginWarning"></div>
                        </div>
                    </div>

                    <!-- Step 2 Actions -->
                    <div class="flex justify-between">
                        <button type="button" @click="previousStep()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back
                        </button>
                        <button type="button" @click="nextStep()" :disabled="!canProceedToStep3()"
                                class="btn btn-primary" :class="{'opacity-50 cursor-not-allowed': !canProceedToStep3()}">
                            Next: Review
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Review -->
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-4">Review & Submit</h3>
                        <p class="text-sm text-muted-foreground mb-6">Review all settings before creating the tank</p>
                    </div>

                    <!-- Review Summary -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Tank Configuration -->
                        <div class="card">
                            <div class="p-4">
                                <h4 class="font-semibold text-foreground mb-4">Tank Configuration</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Station:</span>
                                        <span class="font-medium">{{ $current_station->name ?? '' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Tank Number:</span>
                                        <span class="font-medium" x-text="form.tank_number"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Fuel Type:</span>
                                        <span class="font-medium capitalize" x-text="form.fuel_type"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Capacity:</span>
                                        <span class="font-medium" x-text="Number(form.capacity_liters).toLocaleString() + 'L'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Initial Volume:</span>
                                        <span class="font-medium" x-text="Number(form.current_volume_liters).toLocaleString() + 'L'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Fill Level:</span>
                                        <span class="font-medium" x-text="fillPercentage + '%'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Configuration -->
                        <div class="card">
                            <div class="p-4">
                                <h4 class="font-semibold text-foreground mb-4">Pricing Configuration</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Cost per Liter:</span>
                                        <span class="font-medium" x-text="Number(form.initial_cost_per_liter).toLocaleString() + ' {{ $current_station->currency_code ?? 'UGX' }}'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Selling Price:</span>
                                        <span class="font-medium" x-text="Number(form.selling_price_per_liter).toLocaleString() + ' {{ $current_station->currency_code ?? 'UGX' }}'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Margin:</span>
                                        <span class="font-medium" x-text="marginPercentage"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Profit per Liter:</span>
                                        <span class="font-medium" x-text="Number(form.selling_price_per_liter - form.initial_cost_per_liter).toLocaleString() + ' {{ $current_station->currency_code ?? 'UGX' }}'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Effective Date:</span>
                                        <span class="font-medium" x-text="form.price_effective_date"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Total Investment:</span>
                                        <span class="font-medium" x-text="Number(form.current_volume_liters * form.initial_cost_per_liter).toLocaleString() + ' {{ $current_station->currency_code ?? 'UGX' }}'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Validation Summary -->
                    <div class="card border-l-4 border-l-green-500 bg-green-50">
                        <div class="p-4">
                            <h4 class="font-semibold text-green-800 mb-2">Validation Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-green-700">
                                <div class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Tank number is unique
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Capacity meets minimum requirement
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Volume within capacity limits
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check mr-2"></i>
                                    Margin meets business requirements
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 Actions -->
                    <div class="flex justify-between">
                        <button type="button" @click="previousStep()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back
                        </button>
                        <button type="submit" :disabled="submitting" class="btn btn-primary">
                            <span x-show="!submitting">
                                <i class="fas fa-plus mr-2"></i>
                                Create Tank
                            </span>
                            <span x-show="submitting">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Creating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>

<!-- Alpine.js Component -->
<script>
function tankCreateWizard() {
    return {
        currentStep: 1,
        submitting: false,
        fillPercentage: 0,
        marginPercentage: '0%',
        marginWarning: '',
        totalPotentialProfit: '0',
        marginStatus: { color: 'bg-muted/50', textColor: 'text-muted-foreground' },

        form: {
            tank_number: '',
            fuel_type: '',
            capacity_liters: '',
            current_volume_liters: '',
            initial_cost_per_liter: '',
            selling_price_per_liter: '',
            price_effective_date: new Date().toISOString().split('T')[0]
        },

        errors: {},

        // Controller data
        existingTankNumbers: @json($existing_tank_numbers ?? []),
        latestCosts: @json($latest_costs ?? []),
        currentPrices: @json($current_prices ?? []),
        validationThresholds: @json($validation_thresholds ?? []),

        pricingContext: {},

        init() {
            this.setupPricingContext();

            // Check if we're on a fresh page load after successful creation
            // Look for success indicators in URL or check if we should redirect
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('created') === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Tank created successfully',
                    icon: 'success',
                    confirmButtonText: 'View Tanks'
                }).then(() => {
                    window.location.href = '{{ route("tanks.index") }}?station_id={{ request("station_id") }}';
                });
            }
        },

        setupPricingContext() {
            // Build pricing context from controller data
            @foreach($fuel_types as $type)
            this.pricingContext['{{ $type }}'] = {
                latest_cost: this.latestCosts['{{ $type }}']?.avg_cost ?
                    Number(this.latestCosts['{{ $type }}'].avg_cost).toLocaleString() + ' UGX' : 'N/A',
                current_price: this.currentPrices['{{ $type }}']?.price_per_liter_ugx ?
                    Number(this.currentPrices['{{ $type }}'].price_per_liter_ugx).toLocaleString() + ' UGX' : 'N/A',
                current_margin: this.currentPrices['{{ $type }}'] && this.latestCosts['{{ $type }}'] ?
                    this.calculateExistingMargin('{{ $type }}') : 'N/A'
            };
            @endforeach
        },

        calculateExistingMargin(fuelType) {
            const price = this.currentPrices[fuelType]?.price_per_liter_ugx;
            const cost = this.latestCosts[fuelType]?.avg_cost;
            if (price && cost) {
                const margin = ((price - cost) / price * 100);
                return margin.toFixed(1) + '%';
            }
            return 'N/A';
        },

        updatePricingDefaults() {
            if (this.form.fuel_type) {
                // Set defaults from latest costs
                if (this.latestCosts[this.form.fuel_type]?.avg_cost) {
                    this.form.initial_cost_per_liter = this.latestCosts[this.form.fuel_type].avg_cost;
                }
                if (this.currentPrices[this.form.fuel_type]?.price_per_liter_ugx) {
                    this.form.selling_price_per_liter = this.currentPrices[this.form.fuel_type].price_per_liter_ugx;
                }
                this.calculateMargin();
            }
        },

        validateTankNumber() {
            this.errors.tank_number = '';
            if (this.form.tank_number) {
                const cleaned = this.form.tank_number.toUpperCase().trim();
                this.form.tank_number = cleaned;

                if (!/^[A-Z0-9\-\_]+$/.test(cleaned)) {
                    this.errors.tank_number = 'Only uppercase letters, numbers, hyphens, and underscores allowed';
                } else if (this.existingTankNumbers.includes(cleaned)) {
                    this.errors.tank_number = 'Tank number already exists for this station';
                }
            }
        },

        validateCapacity() {
            this.errors.capacity_liters = '';
            if (this.form.capacity_liters) {
                const capacity = parseFloat(this.form.capacity_liters);
                if (capacity < 1000) {
                    this.errors.capacity_liters = 'Minimum capacity is 1,000 liters';
                }
            }
        },

        validateCurrentVolume() {
            this.errors.current_volume_liters = '';
            if (this.form.current_volume_liters && this.form.capacity_liters) {
                const current = parseFloat(this.form.current_volume_liters);
                const capacity = parseFloat(this.form.capacity_liters);
                if (current > capacity) {
                    this.errors.current_volume_liters = 'Current volume cannot exceed tank capacity';
                }
            }
        },

        calculateFillPercentage() {
            if (this.form.capacity_liters && this.form.current_volume_liters) {
                const percentage = (parseFloat(this.form.current_volume_liters) / parseFloat(this.form.capacity_liters)) * 100;
                this.fillPercentage = percentage.toFixed(1);
            } else {
                this.fillPercentage = 0;
            }
        },

        calculateMargin() {
            if (this.form.initial_cost_per_liter && this.form.selling_price_per_liter) {
                const cost = parseFloat(this.form.initial_cost_per_liter);
                const price = parseFloat(this.form.selling_price_per_liter);
                const margin = ((price - cost) / price) * 100;

                this.marginPercentage = margin.toFixed(1) + '%';

                // Calculate total potential profit
                if (this.form.current_volume_liters) {
                    const profit = (price - cost) * parseFloat(this.form.current_volume_liters);
                    this.totalPotentialProfit = profit.toLocaleString();
                }

                // Validate margin
                if (margin < 5) {
                    this.marginStatus = { color: 'bg-red-100', textColor: 'text-red-600' };
                    this.marginWarning = 'Margin below minimum requirement of 5%';
                    this.errors.selling_price_per_liter = 'Minimum 5% margin required';
                } else if (margin < 10) {
                    this.marginStatus = { color: 'bg-orange-100', textColor: 'text-orange-600' };
                    this.marginWarning = 'Low margin - consider increasing selling price';
                    this.errors.selling_price_per_liter = '';
                } else {
                    this.marginStatus = { color: 'bg-green-100', textColor: 'text-green-600' };
                    this.marginWarning = 'Good margin for sustainable business';
                    this.errors.selling_price_per_liter = '';
                }
            }
        },

        validatePriceChange() {
            if (this.form.fuel_type && this.form.selling_price_per_liter) {
                const existingPrice = this.currentPrices[this.form.fuel_type]?.price_per_liter_ugx;
                if (existingPrice) {
                    const changePercent = Math.abs((this.form.selling_price_per_liter - existingPrice) / existingPrice * 100);
                    if (changePercent > 20) {
                        this.errors.selling_price_per_liter = `Price change exceeds 20% limit (current: ${existingPrice})`;
                    }
                }
            }
        },

        canProceedToStep2() {
            return this.form.tank_number &&
                   this.form.fuel_type &&
                   this.form.capacity_liters &&
                   this.form.current_volume_liters !== '' &&
                   !this.errors.tank_number &&
                   !this.errors.capacity_liters &&
                   !this.errors.current_volume_liters;
        },

        canProceedToStep3() {
            return this.form.initial_cost_per_liter &&
                   this.form.selling_price_per_liter &&
                   this.form.price_effective_date &&
                   !this.errors.selling_price_per_liter;
        },

        nextStep() {
            if (this.currentStep < 3) {
                this.currentStep++;
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        async submitForm() {
            this.submitting = true;

            try {
                const formData = new FormData();
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });
                formData.append('station_id', '{{ request("station_id") }}');

                const response = await fetch('{{ route("tanks.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                // Always try to get text first to handle both JSON and HTML responses
                const responseText = await response.text();

                // Check if it's JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    // Not JSON, probably HTML redirect
                    if (response.ok) {
                        // Likely a successful redirect - show success and redirect
                        Swal.fire({
                            title: 'Success!',
                            text: 'Tank created successfully',
                            icon: 'success',
                            confirmButtonText: 'View Tanks'
                        }).then(() => {
                            window.location.href = `{{ route("tanks.index") }}?station_id={{ request("station_id") }}`;
                        });
                        return;
                    } else {
                        // HTML error response - fall back to traditional form
                        console.warn('Non-JSON error response, falling back to form submission');
                        this.fallbackFormSubmit();
                        return;
                    }
                }

                if (response.ok) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Tank created successfully',
                        icon: 'success',
                        confirmButtonText: 'View Tanks'
                    }).then(() => {
                        window.location.href = `{{ route("tanks.index") }}?station_id={{ request("station_id") }}`;
                    });
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        let errorMessages = [];
                        Object.keys(data.errors).forEach(field => {
                            if (Array.isArray(data.errors[field])) {
                                errorMessages = errorMessages.concat(data.errors[field]);
                            } else {
                                errorMessages.push(data.errors[field]);
                            }
                        });
                        throw new Error(errorMessages.join('\n'));
                    } else {
                        throw new Error(data.message || 'Validation failed');
                    }
                }
            } catch (error) {
                console.error('Submit error:', error);

                // If it's a network error, fall back to traditional form submission
                if (error.name === 'TypeError' || error.message.includes('fetch')) {
                    console.warn('Network error, falling back to traditional form submission');
                    this.fallbackFormSubmit();
                    return;
                }

                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } finally {
                this.submitting = false;
            }
        },

        fallbackFormSubmit() {
            // Set flag to track form submission
            setFormSubmittedFlag();

            // Create traditional form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("tanks.store") }}';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);

            // Add form data
            Object.keys(this.form).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = this.form[key];
                form.appendChild(input);
            });

            // Add station_id
            const stationInput = document.createElement('input');
            stationInput.type = 'hidden';
            stationInput.name = 'station_id';
            stationInput.value = '{{ request("station_id") }}';
            form.appendChild(stationInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Success/Error Flash Message Handling
@if(session('success'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Success!',
        text: '{{ session("success") }}',
        icon: 'success',
        confirmButtonText: 'View Tanks'
    }).then(() => {
        window.location.href = '{{ route("tanks.index") }}?station_id={{ request("station_id") }}';
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

// Error Handling for Validation Errors
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

// Detect if page refreshed after form submission (no flash messages)
document.addEventListener('DOMContentLoaded', function() {
    // Check if form was submitted (detect by checking if we have form data but no flash messages)
    const formSubmitted = localStorage.getItem('tank_form_submitted');
    const hasFlashMessages = {{ session()->has('success') || session()->has('error') || $errors->any() ? 'true' : 'false' }};

    if (formSubmitted && !hasFlashMessages) {
        // Form was submitted but no feedback - check if we're back on create page
        if (window.location.pathname === '{{ route("tanks.create") }}') {
            // Still on create page, something went wrong
            Swal.fire({
                title: 'Submission Status Unknown',
                text: 'The form was submitted but no response was received. Please check if the tank was created.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Check Tanks',
                cancelButtonText: 'Stay Here'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route("tanks.index") }}?station_id={{ request("station_id") }}';
                }
            });
        }
        localStorage.removeItem('tank_form_submitted');
    }
});

// Set flag when form is submitted via fallback
function setFormSubmittedFlag() {
    localStorage.setItem('tank_form_submitted', 'true');
}
</script>
@endsection
