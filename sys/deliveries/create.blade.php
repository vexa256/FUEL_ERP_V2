@extends('layouts.app')

@section('title', 'New Delivery')

@section('page-header')
<div class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex-1">
        <h1 class="text-3xl font-bold text-gray-900">New Delivery</h1>
        <p class="text-sm text-gray-600 mt-1">Record fuel delivery with automatic FIFO processing and overflow handling</p>
    </div>
    <div class="flex-shrink-0">
        <a href="{{ route('deliveries.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Deliveries
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="deliveryWizard()" class="max-w-4xl mx-auto">
    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <template x-for="(step, index) in steps" :key="index">
                <div class="flex items-center">
                    <div :class="currentStep >= index ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                         class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">
                        <span x-text="index + 1"></span>
                    </div>
                    <div x-show="index < steps.length - 1" class="w-16 h-0.5 bg-gray-200 mx-4"></div>
                </div>
            </template>
        </div>
        <div class="flex justify-between mt-2">
            <template x-for="step in steps" :key="step">
                <span class="text-xs text-gray-600" x-text="step"></span>
            </template>
        </div>
    </div>

    <form @submit.prevent="submitDelivery()" class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
        <!-- Step 1: Station Selection -->
        <div x-show="currentStep === 0" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Station</h3>

            @if(!isset($selected_station))
            <div>
                <select x-model="delivery.station_id" @change="loadTanks()" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <option value="">Choose Station</option>
                    @foreach($accessible_stations as $station)
                    <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" @click="currentStep = 1" :disabled="!delivery.station_id"
                    class="w-full py-2 bg-gray-900 text-white rounded-lg font-medium disabled:bg-gray-300 disabled:cursor-not-allowed">
                Continue to Tank Selection
            </button>
            @else
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="font-medium text-gray-900">{{ $selected_station->name }}</p>
                <p class="text-sm text-gray-600">{{ $selected_station->location }}</p>
                <input type="hidden" name="station_id" value="{{ $selected_station->id }}">
            </div>
            <button type="button" @click="currentStep = 1; delivery.station_id = '{{ $selected_station->id }}'"
                    class="w-full py-2 bg-gray-900 text-white rounded-lg font-medium">
                Continue to Tank Selection
            </button>
            @endif
        </div>

        <!-- Step 2: Tank Selection with Overflow Information -->
        <div x-show="currentStep === 1" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Tank</h3>

            <div class="grid gap-4">
                @if(isset($available_tanks))
                @foreach($available_tanks as $tank)
                <label class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50 transition-all">
                    <input type="radio" name="tank_id" value="{{ $tank->id }}" x-model="delivery.tank_id" @change="selectTank({{ json_encode($tank) }})" class="sr-only">

                    <div class="flex-1">
                        <!-- Tank Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-gray-900 text-lg">Tank {{ $tank->tank_number }}</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    {{ $tank->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' :
                                       ($tank->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' :
                                       ($tank->fuel_type === 'kerosene' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $tank->fuel_type)) }}
                                </span>
                            </div>
                            @if($tank->overflow_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">
                                    <i class="fas fa-warehouse"></i>
                                    {{ $tank->overflow_count }} Overflow
                                </span>
                            @endif
                        </div>

                        <!-- Tank Capacity Information -->
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Capacity:</span> {{ number_format($tank->capacity_liters, 0) }}L
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Current:</span> {{ number_format($tank->current_volume_liters, 0) }}L
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">Available:</span> {{ number_format($tank->available_space, 0) }}L
                                </div>
                            </div>
                            @if($tank->overflow_volume > 0)
                            <div class="bg-orange-50 rounded-lg p-2">
                                <div class="text-sm text-orange-800">
                                    <span class="font-medium">Overflow:</span> {{ number_format($tank->overflow_volume, 0) }}L
                                </div>
                                <div class="text-xs text-orange-600 mt-1">
                                    {{ $tank->overflow_count }} record(s) awaiting RTT
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Visual Fill Indicator -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Tank Fill</span>
                                <span>{{ number_format(($tank->current_volume_liters / $tank->capacity_liters) * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full transition-all"
                                     style="width: {{ min(100, ($tank->current_volume_liters / $tank->capacity_liters) * 100) }}%"></div>
                            </div>
                        </div>

                        <!-- Overflow Warning -->
                        @if($tank->overflow_volume > 0)
                        <div class="mt-3 p-2 bg-orange-50 border border-orange-200 rounded-lg">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-orange-600 mt-0.5"></i>
                                <div class="text-sm text-orange-800">
                                    <span class="font-medium">Note:</span> This tank has overflow storage. Consider using
                                    <a href="{{ route('deliveries.overflow.dashboard') }}" class="underline hover:no-underline">RTT operations</a>
                                    to return overflow fuel before new deliveries.
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </label>
                @endforeach
                @endif
            </div>

            <div class="flex gap-3">
                <button type="button" @click="currentStep = 0" class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium">
                    Back
                </button>
                <button type="button" @click="currentStep = 2" :disabled="!delivery.tank_id"
                        class="flex-1 py-2 bg-gray-900 text-white rounded-lg font-medium disabled:bg-gray-300 disabled:cursor-not-allowed">
                    Continue to Details
                </button>
            </div>
        </div>

        <!-- Step 3: Delivery Details with Overflow Predictions -->
        <div x-show="currentStep === 2" class="space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delivery Details</h3>

            <!-- Pre-validation Panel -->
            <div x-show="preValidationResult" class="p-4 rounded-lg border"
                 :class="preValidationResult && preValidationResult.can_proceed ? 'bg-green-50 border-green-200' : 'bg-orange-50 border-orange-200'">
                <div class="flex items-start gap-3">
                    <i :class="preValidationResult && preValidationResult.can_proceed ? 'fas fa-check-circle text-green-600' : 'fas fa-exclamation-triangle text-orange-600'" class="mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="font-medium mb-2" :class="preValidationResult && preValidationResult.can_proceed ? 'text-green-800' : 'text-orange-800'">
                            Delivery Impact Analysis
                        </h4>
                        <div x-show="preValidationResult && preValidationResult.message"
                             class="text-sm mb-3"
                             :class="preValidationResult && preValidationResult.can_proceed ? 'text-green-700' : 'text-orange-700'"
                             x-text="preValidationResult ? preValidationResult.message : ''"></div>

                        <!-- Tank Information -->
                        <div x-show="preValidationResult && preValidationResult.tank_info" class="grid grid-cols-2 gap-4 mb-3">
                            <div class="text-xs space-y-1">
                                <div><span class="font-medium">Tank:</span> <span x-text="preValidationResult && preValidationResult.tank_info ? preValidationResult.tank_info.tank_number : ''"></span></div>
                                <div><span class="font-medium">Capacity:</span> <span x-text="preValidationResult && preValidationResult.tank_info ? formatNumber(preValidationResult.tank_info.capacity_liters, 0) + 'L' : ''"></span></div>
                                <div><span class="font-medium">Available Space:</span> <span x-text="preValidationResult && preValidationResult.tank_info ? formatNumber(preValidationResult.tank_info.available_space_liters, 0) + 'L' : ''"></span></div>
                            </div>
                            <div x-show="preValidationResult && preValidationResult.overflow_info && preValidationResult.overflow_info.has_reserves" class="text-xs space-y-1">
                                <div><span class="font-medium">Existing Overflow:</span> <span x-text="preValidationResult && preValidationResult.overflow_info ? formatNumber(preValidationResult.overflow_info.total_overflow_volume, 0) + 'L' : ''"></span></div>
                                <div><span class="font-medium">Suggested RTT:</span> <span x-text="preValidationResult && preValidationResult.overflow_info ? formatNumber(preValidationResult.overflow_info.suggested_rtt_volume, 0) + 'L' : ''"></span></div>
                            </div>
                        </div>

                        <!-- RTT Options -->
                        <div x-show="preValidationResult && preValidationResult.rtt_options && preValidationResult.rtt_options.length > 0" class="mt-3">
                            <h5 class="text-sm font-medium text-gray-800 mb-2">Available RTT Options:</h5>
                            <div class="space-y-2">
                                <template x-for="option in (preValidationResult ? preValidationResult.rtt_options : [])" :key="option.overflow_id">
                                    <div class="flex items-center justify-between p-2 bg-white rounded border text-xs">
                                        <span x-text="option.delivery_reference"></span>
                                        <span x-text="formatNumber(option.max_returnable, 0) + 'L available'"></span>
                                        <button type="button" @click="navigateToRTT(option.overflow_id)"
                                                class="px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded hover:bg-orange-200">
                                            RTT Now
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume (L)*</label>
                    <input type="number" x-model="delivery.volume_liters"
                           @input="validateVolume(); debouncePreValidation()"
                           @blur="validateVolume()"
                           required step="0.001" min="0.001" max="999999999.999"
                           :class="capacityWarning ? 'border-yellow-500 bg-yellow-50' : ''"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Max precision: 3 decimal places</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost per Liter (UGX)*</label>
                    <input type="number" x-model="delivery.cost_per_liter_ugx"
                           @input="validateCostPerLiter(); calculateTotal()"
                           @blur="validateCostPerLiter()"
                           required step="0.0001" min="0.0001" max="99999.9999"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Max precision: 4 decimal places</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost (UGX)</label>
                    <input type="text" :value="formatNumber(totalCost, 4) + ' UGX'" readonly
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-600 font-medium">
                    <p class="text-xs text-gray-500 mt-1">Auto-calculated</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date*</label>
                    <input type="date" x-model="delivery.delivery_date" @change="validateDate()" required :max="maxDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Cannot be future date</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Time*</label>
                    <input type="time" x-model="delivery.delivery_time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">24-hour format</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input type="text" x-model="delivery.supplier_name" maxlength="255" list="suppliers"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <datalist id="suppliers">
                        @if(isset($suppliers))
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier }}">
                        @endforeach
                        @endif
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                <input type="text" x-model="delivery.invoice_number" maxlength="100"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
            </div>

            <div x-show="capacityWarning" class="p-3 rounded-lg" :class="capacityWarning && capacityWarning.includes('EXCEEDS') ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'">
                <div class="flex items-start">
                    <i :class="capacityWarning && capacityWarning.includes('EXCEEDS') ? 'fas fa-times-circle text-red-600' : 'fas fa-exclamation-triangle text-yellow-600'" class="mt-0.5 mr-2"></i>
                    <div class="text-sm" :class="capacityWarning && capacityWarning.includes('EXCEEDS') ? 'text-red-800' : 'text-yellow-800'" x-text="capacityWarning"></div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="currentStep = 1" class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium">
                    Back
                </button>
                <button type="submit" :disabled="!canSubmit || submitting"
                        :class="canSubmit && !submitting ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-300 text-gray-600 cursor-not-allowed'"
                        class="flex-1 py-2 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <span x-show="!submitting">
                        <i class="fas fa-save"></i>
                        Create Delivery
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function deliveryWizard() {
    return {
        currentStep: {{ isset($selected_station) ? 1 : 0 }},
        steps: ['Station', 'Tank', 'Details'],
        submitting: false,
        capacityWarning: '',
        selectedTank: null,
        maxDate: new Date().toISOString().split('T')[0],
        preValidationResult: null,
        preValidationTimeout: null,

        delivery: {
            station_id: '{{ $selected_station->id ?? "" }}',
            tank_id: '',
            volume_liters: '',
            cost_per_liter_ugx: '',
            delivery_date: new Date().toISOString().split('T')[0],
            delivery_time: new Date().toTimeString().slice(0,5),
            supplier_name: '',
            invoice_number: ''
        },

        errorTimeout: null,

        get totalCost() {
            return (parseFloat(this.delivery.volume_liters) || 0) * (parseFloat(this.delivery.cost_per_liter_ugx) || 0);
        },

        get canSubmit() {
            const checks = {
                tank_id: !!this.delivery.tank_id,
                volume_liters: !!this.delivery.volume_liters,
                volume_positive: this.delivery.volume_liters && parseFloat(this.delivery.volume_liters) > 0,
                cost_per_liter: !!this.delivery.cost_per_liter_ugx,
                cost_positive: this.delivery.cost_per_liter_ugx && parseFloat(this.delivery.cost_per_liter_ugx) > 0,
                delivery_date: !!this.delivery.delivery_date,
                delivery_time: !!this.delivery.delivery_time,
                not_submitting: !this.submitting,
                validation_ok: !this.preValidationResult || this.preValidationResult.can_proceed !== false
            };

            return Object.values(checks).every(check => check === true);
        },

        loadTanks() {
            if (this.delivery.station_id) {
                window.location.href = `{{ route('deliveries.create') }}?station_id=${this.delivery.station_id}`;
            }
        },

        selectTank(tank) {
            this.selectedTank = tank;
            this.validateVolume();
            this.runPreValidation();
        },

        debouncePreValidation() {
            if (this.preValidationTimeout) {
                clearTimeout(this.preValidationTimeout);
            }
            this.preValidationTimeout = setTimeout(() => {
                this.runPreValidation();
            }, 500);
        },

        async runPreValidation() {
            if (!this.delivery.tank_id || !this.delivery.volume_liters || parseFloat(this.delivery.volume_liters) <= 0) {
                this.preValidationResult = null;
                return;
            }

            try {
                const formData = new FormData();
                formData.append('tank_id', this.delivery.tank_id);
                formData.append('volume_liters', this.delivery.volume_liters);
                formData.append('fuel_type', this.selectedTank ? this.selectedTank.fuel_type : '');
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route('api.deliveries.prevalidate') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.preValidationResult = await response.json();
                } else {
                    const errorData = await response.json().catch(() => null);
                    console.error('Pre-validation error:', errorData);
                    this.preValidationResult = null;
                }
            } catch (error) {
                console.error('Pre-validation failed:', error);
                this.preValidationResult = null;
            }
        },

        navigateToRTT(overflowId) {
            // Store current form data in session storage for recovery
            sessionStorage.setItem('delivery_form_data', JSON.stringify(this.delivery));

            // Navigate to overflow dashboard or RTT interface
            window.location.href = `{{ route('deliveries.overflow.dashboard') }}?focus=${overflowId}`;
        },

        validateVolume() {
            if (!this.selectedTank || !this.delivery.volume_liters) {
                this.capacityWarning = '';
                return;
            }

            const volume = parseFloat(this.delivery.volume_liters);

            // Prevent impossible volumes
            if (volume <= 0) {
                this.delivery.volume_liters = '';
                this.showError('Volume must be greater than zero');
                return;
            }

            // Prevent precision violations (max 3 decimals)
            if (volume.toString().includes('.') && volume.toString().split('.')[1].length > 3) {
                this.delivery.volume_liters = parseFloat(volume).toFixed(3);
                this.showError('Volume precision limited to 3 decimal places');
            }

            // Clear any previous warnings - let pre-validation handle capacity checks
            this.capacityWarning = '';
        },

        validateCostPerLiter() {
            if (!this.delivery.cost_per_liter_ugx) return;

            const cost = parseFloat(this.delivery.cost_per_liter_ugx);

            // Prevent impossible costs
            if (cost <= 0) {
                this.delivery.cost_per_liter_ugx = '';
                this.showError('Cost per liter must be greater than zero');
                return;
            }

            // Enforce precision (max 4 decimals)
            if (cost.toString().includes('.') && cost.toString().split('.')[1].length > 4) {
                this.delivery.cost_per_liter_ugx = cost.toFixed(4);
                this.showError('Cost precision limited to 4 decimal places');
            }

            // Sanity check for unrealistic prices
            if (cost > 50000) {
                this.showError('Cost seems unusually high. Please verify.');
            } else if (cost < 100) {
                this.showError('Cost seems unusually low. Please verify.');
            }
        },

        validateDate() {
            if (!this.delivery.delivery_date) return;

            const selectedDate = new Date(this.delivery.delivery_date);
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

            // Prevent future dates
            if (selectedDate > today) {
                this.delivery.delivery_date = today.toISOString().split('T')[0];
                this.showError('Delivery date cannot be in the future');
                return;
            }

            // Warn about old dates (business rule compliance)
            if (selectedDate < thirtyDaysAgo) {
                this.showError('Date is more than 30 days old. May require special approval.');
            }
        },

        calculateTotal() {
            // Real-time calculation with validation
            const volume = parseFloat(this.delivery.volume_liters) || 0;
            const cost = parseFloat(this.delivery.cost_per_liter_ugx) || 0;

            if (volume > 0 && cost > 0) {
                const total = volume * cost;
                // Ensure total doesn't exceed reasonable business limits
                if (total > 100000000) { // 100M UGX sanity check
                    this.showError('Total cost seems unreasonably high. Please verify inputs.');
                }
            }
        },

        showError(message) {
            // Non-blocking error notification
            if (this.errorTimeout) clearTimeout(this.errorTimeout);

            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            toast.fire({
                icon: 'warning',
                title: message
            });
        },

        formatNumber(value, decimals = 0) {
            return parseFloat(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        async submitDelivery() {
            if (!this.canSubmit) {
                return;
            }

            this.submitting = true;

            try {
                const formData = new FormData();
                Object.entries(this.delivery).forEach(([key, value]) => {
                    if (value !== null && value !== '') {
                        formData.append(key, value);
                    }
                });
                formData.append('_token', '{{ csrf_token() }}');

                const response = await fetch('{{ route('deliveries.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const result = await response.json();

                    // Handle different response scenarios
                    if (result.success) {
                        // Clear stored form data on success
                        sessionStorage.removeItem('delivery_form_data');

                        // Show success message
                        await Swal.fire({
                            icon: 'success',
                            title: 'Delivery Created Successfully',
                            text: result.message,
                            confirmButtonColor: '#1f2937'
                        });

                        // Redirect to the appropriate page
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        } else if (result.delivery_id) {
                            window.location.href = '/deliveries/' + result.delivery_id;
                        } else {
                            window.location.href = '{{ route('deliveries.index') }}';
                        }
                        return;
                    }

                    // Handle RTT recommendation case
                    if (result.rtt_required || result.rtt_recommended) {
                        const action = await Swal.fire({
                            icon: 'warning',
                            title: result.rtt_required ? 'RTT Required' : 'RTT Recommended',
                            text: result.message,
                            showCancelButton: true,
                            confirmButtonText: 'Process RTT First',
                            cancelButtonText: result.rtt_required ? 'Cancel' : 'Continue Anyway',
                            confirmButtonColor: '#ea580c',
                            cancelButtonColor: '#6b7280'
                        });

                        if (action.isConfirmed) {
                            // Store form data and navigate to overflow management
                            sessionStorage.setItem('delivery_form_data', JSON.stringify(this.delivery));
                            window.location.href = '{{ route('deliveries.overflow.dashboard') }}';
                            return;
                        } else if (result.rtt_required) {
                            // Can't proceed if RTT is required
                            this.submitting = false;
                            return;
                        }
                        // Continue with delivery if RTT was only recommended
                    }

                    throw new Error(result.message || 'Unexpected response format');

                } else {
                    const errorData = await response.json().catch(() => null);
                    let errorMessage = 'An error occurred while creating the delivery';

                    if (errorData && errorData.errors) {
                        const firstError = Object.values(errorData.errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    } else if (errorData && errorData.message) {
                        errorMessage = errorData.message;
                    }

                    throw new Error(errorMessage);
                }

            } catch (error) {
                console.error('Submission error:', error);
                this.submitting = false;

                Swal.fire({
                    icon: 'error',
                    title: 'Delivery Creation Failed',
                    text: error.message || 'Please check your inputs and try again',
                    confirmButtonColor: '#1f2937'
                });
            }
        },

        init() {
            // Restore form data if available
            const storedData = sessionStorage.getItem('delivery_form_data');
            if (storedData) {
                try {
                    const parsed = JSON.parse(storedData);
                    Object.assign(this.delivery, parsed);
                } catch (e) {
                    console.error('Failed to restore form data:', e);
                }
            }
        }
    }
}
</script>
@endsection
