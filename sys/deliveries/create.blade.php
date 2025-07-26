@extends('layouts.app')

@section('title', 'New Delivery')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">New Delivery</h1>
        <p class="text-sm text-gray-600 mt-1">Record fuel delivery with automatic FIFO processing</p>
    </div>
    <a href="{{ route('deliveries.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
        <i class="fas fa-arrow-left mr-2"></i>Back to Deliveries
    </a>
</div>
@endsection

@section('content')
<div x-data="deliveryWizard()" class="max-w-2xl mx-auto">
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

        <!-- Step 2: Tank Selection -->
        <div x-show="currentStep === 1" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Tank</h3>

            <div class="grid gap-3">
                @if(isset($available_tanks))
                @foreach($available_tanks as $tank)
                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input type="radio" name="tank_id" value="{{ $tank->id }}" x-model="delivery.tank_id" @change="selectTank({{ json_encode($tank) }})" class="sr-only">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">Tank {{ $tank->tank_number }}</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $tank->fuel_type === 'petrol' ? 'bg-green-100 text-green-800' :
                                   ($tank->fuel_type === 'diesel' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($tank->fuel_type) }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            Capacity: {{ number_format($tank->capacity_liters, 0) }}L |
                            Current: {{ number_format($tank->current_volume_liters, 0) }}L
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($tank->current_volume_liters / $tank->capacity_liters) * 100 }}%"></div>
                        </div>
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

        <!-- Step 3: Delivery Details -->
        <div x-show="currentStep === 2" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delivery Details</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference*</label>
                    <input type="text" x-model="delivery.delivery_reference" @input="validateReference()" @blur="validateReference()"
                           required maxlength="100" pattern="[A-Z0-9\-_]+"
                           placeholder="DEL-2024-001"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Auto-formatted: Letters, numbers, dash, underscore only</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume (L)*</label>
                    <input type="number" x-model="delivery.volume_liters" @input="validateVolume()" @blur="validateVolume()"
                           required step="0.001" min="0.001" max="999999999.999"
                           :class="capacityWarning ? 'border-yellow-500 bg-yellow-50' : ''"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Max precision: 3 decimal places</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost per Liter (UGX)*</label>
                    <input type="number" x-model="delivery.cost_per_liter_ugx" @input="validateCostPerLiter(); calculateTotal()" @blur="validateCostPerLiter()"
                           required step="0.0001" min="0.0001" max="99999.9999"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Max precision: 4 decimal places</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost (UGX)</label>
                    <input type="text" :value="formatNumber(totalCost, 4) + ' UGX'" readonly
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-600 font-medium">
                    <p class="text-xs text-gray-500 mt-1">Auto-calculated</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date*</label>
                    <input type="date" x-model="delivery.delivery_date" @change="validateDate()" required :max="maxDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Cannot be future date</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time*</label>
                    <input type="time" x-model="delivery.delivery_time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                    <p class="text-xs text-gray-500 mt-1">24-hour format</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                    <input type="text" x-model="delivery.invoice_number" maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                </div>
            </div>

            <div x-show="capacityWarning" class="p-3 rounded-lg" :class="capacityWarning.includes('EXCEEDS') ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'">
                <div class="flex items-start">
                    <i :class="capacityWarning.includes('EXCEEDS') ? 'fas fa-times-circle text-red-600' : 'fas fa-exclamation-triangle text-yellow-600'" class="mt-0.5 mr-2"></i>
                    <div class="text-sm" :class="capacityWarning.includes('EXCEEDS') ? 'text-red-800' : 'text-yellow-800'" x-text="capacityWarning"></div>
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

        delivery: {
            station_id: '{{ $selected_station->id ?? "" }}',
            tank_id: '',
            delivery_reference: '',
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
            // Debug logging
            const checks = {
                tank_id: !!this.delivery.tank_id,
                delivery_reference: !!this.delivery.delivery_reference,
                reference_length: this.delivery.delivery_reference && this.delivery.delivery_reference.length >= 3,
                volume_liters: !!this.delivery.volume_liters,
                volume_positive: this.delivery.volume_liters && parseFloat(this.delivery.volume_liters) > 0,
                cost_per_liter: !!this.delivery.cost_per_liter_ugx,
                cost_positive: this.delivery.cost_per_liter_ugx && parseFloat(this.delivery.cost_per_liter_ugx) > 0,
                delivery_date: !!this.delivery.delivery_date,
                delivery_time: !!this.delivery.delivery_time,
                not_submitting: !this.submitting,
                capacity_ok: !this.capacityWarning || !this.capacityWarning.includes('EXCEEDS')
            };

            console.log('canSubmit checks:', checks);

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
        },

        validateVolume() {
            if (!this.selectedTank || !this.delivery.volume_liters) {
                this.capacityWarning = '';
                return;
            }

            const volume = parseFloat(this.delivery.volume_liters);
            const available = this.selectedTank.capacity_liters - this.selectedTank.current_volume_liters;

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

            // Prevent capacity overflow
            if (volume > available) {
                this.capacityWarning = `⚠️ EXCEEDS CAPACITY: Available space only ${this.formatNumber(available, 3)}L`;
                this.delivery.volume_liters = available.toFixed(3);
                this.showError(`Volume reduced to maximum available: ${this.formatNumber(available, 3)}L`);
            } else if (volume > (available * 0.95)) {
                this.capacityWarning = `⚠️ Near capacity limit. Available: ${this.formatNumber(available, 3)}L`;
            } else {
                this.capacityWarning = '';
            }
        },

        validateReference() {
            if (!this.delivery.delivery_reference) return;

            // Auto-format and sanitize
            let ref = this.delivery.delivery_reference
                .toUpperCase()
                .replace(/[^A-Z0-9\-_]/g, '') // Only alphanumeric, dash, underscore
                .substring(0, 100); // Enforce max length

            this.delivery.delivery_reference = ref;

            // Prevent empty references
            if (!ref.trim()) {
                this.delivery.delivery_reference = '';
                this.showError('Reference cannot be empty or contain only invalid characters');
            }
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
            console.log('submitDelivery called');
            console.log('canSubmit:', this.canSubmit);
            console.log('delivery data:', this.delivery);

            if (!this.canSubmit) {
                console.log('Submission blocked by canSubmit check');
                return;
            }

            this.submitting = true;
            console.log('Starting submission...');

            try {
                const formData = new FormData();
                Object.entries(this.delivery).forEach(([key, value]) => {
                    if (value !== null && value !== '') {
                        formData.append(key, value);
                        console.log(`FormData: ${key} = ${value}`);
                    }
                });
                formData.append('_token', '{{ csrf_token() }}');

                console.log('Sending request to:', '{{ route('deliveries.store') }}');

                const response = await fetch('{{ route('deliveries.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);

                if (response.ok) {
                    const result = await response.json();
                    console.log('Success response:', result);

                    if (result.redirect) {
                        console.log('Redirecting to:', result.redirect);
                        window.location.href = result.redirect;
                        return;
                    }

                    // Fallback redirect
                    window.location.href = '{{ route('deliveries.index') }}';

                } else {
                    const errorData = await response.json().catch(() => null);
                    console.log('Error response:', errorData);

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
        }
    }
}
</script>
@endsection
