@extends('layouts.app')

@section('title', 'Edit Meter - ' . $meter->meter_number)

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div class="flex items-center space-x-4">
        <div class="p-3 bg-slate-100 rounded-xl">
            <i class="fas fa-edit text-slate-700 w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Edit Meter</h1>
            <p class="text-slate-600 mt-1">{{ $meter->station_name }} • {{ $meter->tank_number }} ({{ ucfirst($meter->fuel_type) }})</p>
        </div>
    </div>
    <div class="flex items-center space-x-3">
        <a href="{{ route('meters.show', $meter->id) }}"
           class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-all duration-200">
            <i class="fas fa-eye w-4 h-4 mr-2"></i>
            View Details
        </a>
        <a href="{{ route('meters.index', ['station_id' => $meter->station_id]) }}"
           class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-all duration-200">
            <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
            Back to Meters
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="meterEditWizard" x-init="initializeData(@js([
    'meter' => [
        'id' => $meter->id,
        'meter_number' => $meter->meter_number,
        'current_reading_liters' => $meter->current_reading_liters,
        'is_active' => $meter->is_active,
        'tank_number' => $meter->tank_number,
        'fuel_type' => $meter->fuel_type,
        'station_name' => $meter->station_name,
        'station_location' => $meter->station_location
    ],
    'hasReadings' => $has_readings,
    'existingMeterNumbers' => $existing_meter_numbers ?? []
]))" class="space-y-6">

    <!-- Context Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl border border-slate-700 shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-tachometer-alt w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">{{ $meter->meter_number }}</h3>
                    <p class="text-slate-300 text-sm">{{ $meter->station_name }} • {{ $meter->tank_number }} ({{ ucfirst($meter->fuel_type) }})</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-lg font-bold">Current: {{ number_format($meter->current_reading_liters, 3) }}L</div>
                <div class="text-slate-300 text-sm">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $meter->is_active ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ $meter->is_active ? 'bg-green-400' : 'bg-red-400' }} mr-1"></div>
                        {{ $meter->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Rules Warning -->
    @if($has_readings)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-amber-600 w-5 h-5"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">Edit Restrictions Apply</h3>
                <div class="mt-2 text-sm text-amber-700">
                    <p>This meter has recorded readings. To preserve data integrity:</p>
                    <ul class="mt-1 list-disc list-inside space-y-1">
                        <li>Current reading cannot be reduced below {{ number_format($meter->current_reading_liters, 3) }}L</li>
                        <li>Historical data will remain unchanged</li>
                        <li>All changes are logged for audit compliance</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Form Wizard -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Tab Navigation -->
        <div class="border-b border-slate-200">
            <nav class="flex space-x-0" aria-label="Tabs">
                <button @click="activeTab = 'basic'"
                        :class="activeTab === 'basic' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-info-circle w-4 h-4 mr-2"></i>
                    Basic Information
                    <div x-show="activeTab === 'basic'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900"></div>
                </button>

                <button @click="activeTab = 'reading'"
                        :class="activeTab === 'reading' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-gauge w-4 h-4 mr-2"></i>
                    Reading Update
                    <div x-show="activeTab === 'reading'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900"></div>
                </button>

                <button @click="activeTab = 'status'"
                        :class="activeTab === 'status' ? 'border-slate-900 text-slate-900 bg-slate-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                        class="relative min-w-0 flex-1 px-6 py-4 text-sm font-medium text-center border-b-2 transition-all duration-200 focus:outline-none">
                    <i class="fas fa-power-off w-4 h-4 mr-2"></i>
                    Status & Settings
                    <div x-show="activeTab === 'status'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-900"></div>
                </button>
            </nav>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="submitForm" class="p-6">
            @csrf
            @method('PUT')

            <!-- Basic Information Tab -->
            <div x-show="activeTab === 'basic'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-6">Basic Meter Information</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Meter Number -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Meter Number</label>
                            <div class="relative">
                                <input type="text"
                                       x-model="formData.meter_number"
                                       @input="validateMeterNumber"
                                       placeholder="e.g., MTR-001, PUMP-A1"
                                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200"
                                       :class="errors.meter_number ? 'border-red-300 bg-red-50' : 'border-slate-300'"
                                       maxlength="50">
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-check text-green-500 w-4 h-4" x-show="formData.meter_number && !errors.meter_number"></i>
                                    <i class="fas fa-times text-red-500 w-4 h-4" x-show="errors.meter_number"></i>
                                </div>
                            </div>
                            <div x-show="errors.meter_number" class="text-sm text-red-600" x-text="errors.meter_number"></div>
                            <div class="text-xs text-slate-500">
                                Use uppercase letters, numbers, hyphens, and underscores only
                            </div>
                        </div>

                        <!-- Tank Assignment (Read-only) -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Assigned Tank</label>
                            <div class="relative">
                                <div class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                                    {{ $meter->tank_number }} ({{ ucfirst($meter->fuel_type) }})
                                </div>
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-lock text-slate-400 w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="text-xs text-slate-500">
                                Tank assignment cannot be changed after creation
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reading Update Tab -->
            <div x-show="activeTab === 'reading'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-6">Update Meter Reading</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Current Reading Display -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Current Reading</label>
                            <div class="relative">
                                <div class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium">
                                    {{ number_format($meter->current_reading_liters, 3) }} L
                                </div>
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-info-circle text-slate-400 w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="text-xs text-slate-500">
                                This is the current meter reading on file
                            </div>
                        </div>

                        <!-- New Reading Input -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">New Reading (Liters)</label>
                            <div class="relative">
                                <input type="number"
                                       x-model="formData.current_reading_liters"
                                       @input="validateReading"
                                       placeholder="{{ number_format($meter->current_reading_liters, 3) }}"
                                       step="0.001"
                                       min="{{ $has_readings ? $meter->current_reading_liters : 0 }}"
                                       max="999999999.999"
                                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200"
                                       :class="errors.current_reading_liters ? 'border-red-300 bg-red-50' : 'border-slate-300'">
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 text-sm">
                                    L
                                </div>
                            </div>
                            <div x-show="errors.current_reading_liters" class="text-sm text-red-600" x-text="errors.current_reading_liters"></div>
                            <div class="text-xs text-slate-500">
                                @if($has_readings)
                                Cannot be less than current reading ({{ number_format($meter->current_reading_liters, 3) }}L)
                                @else
                                Enter the current meter reading with up to 3 decimal places
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Reading Change Preview -->
                    <div x-show="readingChange !== 0" class="mt-6 p-4 rounded-xl border" :class="readingChange > 0 ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200'">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="w-5 h-5" :class="readingChange > 0 ? 'fas fa-arrow-up text-green-600' : 'fas fa-arrow-down text-amber-600'"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium" :class="readingChange > 0 ? 'text-green-800' : 'text-amber-800'">
                                    Reading Change Preview
                                </h4>
                                <p class="text-sm" :class="readingChange > 0 ? 'text-green-700' : 'text-amber-700'">
                                    <span x-text="readingChange > 0 ? 'Increase' : 'Decrease'"></span> of
                                    <span class="font-medium" x-text="Math.abs(readingChange).toFixed(3)"></span>L
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Settings Tab -->
            <div x-show="activeTab === 'status'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-6">Status & Settings</h3>

                    <div class="space-y-6">
                        <!-- Meter Status -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Meter Status</label>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="formData.is_active" class="sr-only">
                                    <div class="relative">
                                        <div class="w-10 h-6 bg-slate-200 rounded-full transition-colors duration-200"
                                             :class="formData.is_active ? 'bg-slate-900' : 'bg-slate-200'"></div>
                                        <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"
                                             :class="formData.is_active ? 'transform translate-x-4' : ''"></div>
                                    </div>
                                    <span class="ml-3 text-sm font-medium text-slate-700">
                                        <span x-text="formData.is_active ? 'Active' : 'Inactive'"></span>
                                    </span>
                                </label>
                            </div>
                            <div class="text-xs text-slate-500">
                                Inactive meters cannot be used for daily readings
                            </div>
                        </div>

                        <!-- Status Change Impact -->
                        <div x-show="statusChanged" class="p-4 rounded-xl border" :class="formData.is_active ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200'">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="w-5 h-5" :class="formData.is_active ? 'fas fa-check-circle text-green-600' : 'fas fa-pause-circle text-amber-600'"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium" :class="formData.is_active ? 'text-green-800' : 'text-amber-800'">
                                        Status Change Impact
                                    </h4>
                                    <p class="text-sm" :class="formData.is_active ? 'text-green-700' : 'text-amber-700'" x-text="formData.is_active ? 'Meter will be available for daily readings' : 'Meter will be disabled from daily readings'"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-200 mt-8">
                <div class="flex items-center space-x-4">
                    <button type="button" @click="resetForm"
                            class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-all duration-200">
                        <i class="fas fa-undo w-4 h-4 mr-2"></i>
                        Reset Changes
                    </button>
                </div>

                <div class="flex items-center space-x-3">
                    <span x-show="hasChanges" class="text-sm text-slate-600">
                        <i class="fas fa-circle text-orange-500 w-2 h-2 mr-2"></i>
                        Unsaved changes
                    </span>

                    <button type="submit"
                            :disabled="!canSubmit || isSubmitting"
                            :class="canSubmit && !isSubmitting ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                            class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-200 shadow-sm">
                        <div x-show="!isSubmitting" class="flex items-center">
                            <i class="fas fa-save w-4 h-4 mr-2"></i>
                            Update Meter
                        </div>
                        <div x-show="isSubmitting" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2"></div>
                            Updating...
                        </div>
                    </button>
                </div>
            </div>

            <!-- Hidden form inputs -->
            <input type="hidden" name="meter_number" :value="formData.meter_number">
            <input type="hidden" name="current_reading_liters" :value="formData.current_reading_liters">
            <input type="hidden" name="is_active" :value="formData.is_active ? '1' : '0'">
        </form>
    </div>
</div>

<script>
function meterEditWizard() {
    return {
        activeTab: 'basic',
        isSubmitting: false,
        meter: {},
        hasReadings: false,
        existingMeterNumbers: [],

        formData: {
            meter_number: '',
            current_reading_liters: '',
            is_active: true
        },

        originalData: {},

        errors: {
            meter_number: '',
            current_reading_liters: ''
        },

        initializeData(config) {
            this.meter = config.meter;
            this.hasReadings = config.hasReadings;
            this.existingMeterNumbers = config.existingMeterNumbers || [];

            // Initialize form data
            this.formData = {
                meter_number: this.meter.meter_number,
                current_reading_liters: this.meter.current_reading_liters,
                is_active: Boolean(this.meter.is_active)
            };

            // Store original data for comparison
            this.originalData = JSON.parse(JSON.stringify(this.formData));
        },

        get hasChanges() {
            return JSON.stringify(this.formData) !== JSON.stringify(this.originalData);
        },

        get statusChanged() {
            return this.formData.is_active !== Boolean(this.meter.is_active);
        },

        get readingChange() {
            const newReading = parseFloat(this.formData.current_reading_liters) || 0;
            const originalReading = parseFloat(this.meter.current_reading_liters) || 0;
            return newReading - originalReading;
        },

        get canSubmit() {
            return this.hasChanges &&
                   !this.errors.meter_number &&
                   !this.errors.current_reading_liters &&
                   this.formData.meter_number.trim() !== '' &&
                   this.formData.current_reading_liters !== '';
        },

        validateMeterNumber() {
            this.errors.meter_number = '';

            if (!this.formData.meter_number.trim()) {
                this.errors.meter_number = 'Meter number is required';
                return;
            }

            // Check format
            if (!/^[A-Z0-9\-\_]+$/.test(this.formData.meter_number)) {
                this.errors.meter_number = 'Use only uppercase letters, numbers, hyphens, and underscores';
                return;
            }

            // Check length
            if (this.formData.meter_number.length > 50) {
                this.errors.meter_number = 'Meter number cannot exceed 50 characters';
                return;
            }

            // Check uniqueness (excluding current meter)
            if (this.existingMeterNumbers.includes(this.formData.meter_number.toUpperCase()) &&
                this.formData.meter_number.toUpperCase() !== this.meter.meter_number.toUpperCase()) {
                this.errors.meter_number = 'This meter number already exists';
                return;
            }
        },

        validateReading() {
            this.errors.current_reading_liters = '';

            if (this.formData.current_reading_liters === '' || this.formData.current_reading_liters === null) {
                this.errors.current_reading_liters = 'Reading is required';
                return;
            }

            const reading = parseFloat(this.formData.current_reading_liters);

            if (isNaN(reading)) {
                this.errors.current_reading_liters = 'Must be a valid number';
                return;
            }

            if (reading < 0) {
                this.errors.current_reading_liters = 'Reading cannot be negative';
                return;
            }

            if (reading > 999999999.999) {
                this.errors.current_reading_liters = 'Reading cannot exceed 999,999,999.999';
                return;
            }

            // Business rule: Cannot reduce reading if meter has readings
            if (this.hasReadings && reading < parseFloat(this.meter.current_reading_liters)) {
                this.errors.current_reading_liters = `Cannot reduce reading below ${Number(this.meter.current_reading_liters).toFixed(3)}L`;
                return;
            }

            // Check decimal places
            const decimalPlaces = (this.formData.current_reading_liters.toString().split('.')[1] || '').length;
            if (decimalPlaces > 3) {
                this.errors.current_reading_liters = 'Maximum 3 decimal places allowed';
                return;
            }
        },

        resetForm() {
            this.formData = JSON.parse(JSON.stringify(this.originalData));
            this.errors = {
                meter_number: '',
                current_reading_liters: ''
            };
        },

        async submitForm() {
            // Final validation
            this.validateMeterNumber();
            this.validateReading();

            if (this.errors.meter_number || this.errors.current_reading_liters) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix the errors before submitting'
                });
                return;
            }

            if (!this.hasChanges) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Changes',
                    text: 'No changes detected to save'
                });
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('_method', 'PUT');
                formData.append('meter_number', this.formData.meter_number.toUpperCase());
                formData.append('current_reading_liters', this.formData.current_reading_liters);
                formData.append('is_active', this.formData.is_active ? '1' : '0');

                const response = await fetch(`/meters/${this.meter.id}`, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Meter Updated!',
                        text: 'The meter has been successfully updated.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        window.location.href = `/meters/${this.meter.id}`;
                    }, 2000);
                } else {
                    const errorData = await response.text();
                    throw new Error('Failed to update meter');
                }

            } catch (error) {
                console.error('Submit error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Failed to update meter. Please try again.'
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>
@endsection
