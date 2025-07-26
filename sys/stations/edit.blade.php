@extends('layouts.app')

@section('title', 'Edit Station')

@section('breadcrumb')
<a href="{{ route('stations.index') }}" class="text-muted-foreground hover:text-primary transition-colors">
    Stations
</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<a href="{{ route('stations.show', $station->id) }}" class="text-muted-foreground hover:text-primary transition-colors">
    {{ $station->name }}
</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">Edit</span>
@endsection

@section('page-header')
<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Edit Station</h1>
        <p class="text-sm text-gray-600 mt-1">Modify station configuration and settings</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('stations.show', $station->id) }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-eye w-4 h-4"></i>
            View Details
        </a>
        <a href="{{ route('stations.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-arrow-left w-4 h-4"></i>
            Back to Stations
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="stationEditor()" class="max-w-2xl mx-auto">
    <!-- Progress Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div :class="step >= 1 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                         class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors">
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Edit Information</span>
                </div>
                <div class="w-16 h-px bg-gray-300"></div>
                <div class="flex items-center">
                    <div :class="step >= 2 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                         class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Review Changes</span>
                </div>
                <div class="w-16 h-px bg-gray-300"></div>
                <div class="flex items-center">
                    <div :class="step >= 3 ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                         class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors">
                        3
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900">Confirm</span>
                </div>
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div :style="`width: ${(step / 3) * 100}%`"
                 class="bg-gray-900 h-2 rounded-full transition-all duration-300"></div>
        </div>
    </div>

    <!-- Current Station Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 w-5 h-5 mt-0.5 mr-3"></i>
            <div>
                <h4 class="text-sm font-medium text-blue-900 mb-2">Current Station Information</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700">Name:</span>
                        <span class="font-medium text-blue-900 ml-1">{{ $station->name }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Location:</span>
                        <span class="font-medium text-blue-900 ml-1">{{ $station->location }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Currency:</span>
                        <span class="font-medium text-blue-900 ml-1">{{ $station->currency_code }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700">Timezone:</span>
                        <span class="font-medium text-blue-900 ml-1">{{ $station->timezone }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <form @submit.prevent="submitForm" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Step 1: Edit Information -->
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Station Information</h3>
                        <p class="text-sm text-gray-600">Modify the station details below. Changes will be highlighted.</p>
                    </div>

                    <!-- Station Name -->
                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Station Name <span class="text-red-500">*</span>
                            <span x-show="hasChanged('name')" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-edit w-3 h-3 mr-1"></i>
                                Modified
                            </span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               x-model="formData.name"
                               @input="validateName"
                               :class="[
                                   errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' :
                                   hasChanged('name') ? 'border-orange-300 focus:border-orange-500 focus:ring-orange-500' :
                                   'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                               ]"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-0 transition-colors"
                               placeholder="Enter station name"
                               maxlength="255"
                               required
                               autocomplete="off">

                        <div x-show="errors.name" x-text="errors.name"
                             class="text-sm text-red-600 mt-1"></div>

                        @error('name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Location -->
                    <div class="space-y-2">
                        <label for="location" class="block text-sm font-medium text-gray-700">
                            Location <span class="text-red-500">*</span>
                            <span x-show="hasChanged('location')" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-edit w-3 h-3 mr-1"></i>
                                Modified
                            </span>
                        </label>
                        <input type="text"
                               id="location"
                               name="location"
                               x-model="formData.location"
                               @input="validateLocation"
                               :class="[
                                   errors.location ? 'border-red-300 focus:border-red-500 focus:ring-red-500' :
                                   hasChanged('location') ? 'border-orange-300 focus:border-orange-500 focus:ring-orange-500' :
                                   'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                               ]"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-0 transition-colors"
                               placeholder="Enter location"
                               maxlength="255"
                               required
                               autocomplete="off">

                        <div x-show="errors.location" x-text="errors.location"
                             class="text-sm text-red-600 mt-1"></div>

                        @error('location')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Currency -->
                    <div class="space-y-2">
                        <label for="currency_code" class="block text-sm font-medium text-gray-700">
                            Currency <span class="text-red-500">*</span>
                            <span x-show="hasChanged('currency_code')" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-edit w-3 h-3 mr-1"></i>
                                Modified
                            </span>
                        </label>
                        <select id="currency_code"
                                name="currency_code"
                                x-model="formData.currency_code"
                                @change="validateCurrency"
                                :class="[
                                    errors.currency_code ? 'border-red-300 focus:border-red-500 focus:ring-red-500' :
                                    hasChanged('currency_code') ? 'border-orange-300 focus:border-orange-500 focus:ring-orange-500' :
                                    'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                                ]"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-0 transition-colors"
                                required>
                            <option value="">Select Currency</option>
                            @foreach($currencies as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Currency Change Warning -->
                        <div x-show="hasChanged('currency_code') && formData.currency_code !== originalData.currency_code"
                             class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-600 w-4 h-4 mt-0.5 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-yellow-800">Currency Change Warning</p>
                                    <p class="text-yellow-700 mt-1">
                                        Changing currency may be restricted if this station has existing financial data
                                        (deliveries, sales, prices). The system will validate this before saving.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div x-show="errors.currency_code" x-text="errors.currency_code"
                             class="text-sm text-red-600 mt-1"></div>

                        @error('currency_code')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Timezone -->
                    <div class="space-y-2">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">
                            Timezone <span class="text-red-500">*</span>
                            <span x-show="hasChanged('timezone')" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <i class="fas fa-edit w-3 h-3 mr-1"></i>
                                Modified
                            </span>
                        </label>
                        <select id="timezone"
                                name="timezone"
                                x-model="formData.timezone"
                                @change="validateTimezone"
                                :class="[
                                    errors.timezone ? 'border-red-300 focus:border-red-500 focus:ring-red-500' :
                                    hasChanged('timezone') ? 'border-orange-300 focus:border-orange-500 focus:ring-orange-500' :
                                    'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                                ]"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-0 transition-colors"
                                required>
                            <option value="">Select Timezone</option>
                            @foreach($timezones as $tz => $label)
                                <option value="{{ $tz }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <div x-show="errors.timezone" x-text="errors.timezone"
                             class="text-sm text-red-600 mt-1"></div>

                        @error('timezone')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Step 2: Review Changes -->
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Review Changes</h3>
                        <p class="text-sm text-gray-600">Please review the changes you've made before saving</p>
                    </div>

                    <!-- Changes Summary -->
                    <div class="space-y-4">
                        <template x-for="change in getChanges()" :key="change.field">
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-orange-400">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 capitalize" x-text="change.label"></h4>
                                        <div class="mt-2 space-y-1">
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-500 mr-2">From:</span>
                                                <span class="font-medium text-red-600" x-text="change.from"></span>
                                            </div>
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-500 mr-2">To:</span>
                                                <span class="font-medium text-green-600" x-text="change.to"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <i class="fas fa-edit text-orange-500 w-4 h-4 mt-1"></i>
                                </div>
                            </div>
                        </template>

                        <div x-show="getChanges().length === 0" class="text-center py-6">
                            <i class="fas fa-check-circle text-green-500 w-8 h-8 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-600">No changes detected</p>
                        </div>
                    </div>

                    <!-- Impact Warning -->
                    <div x-show="hasChanged('currency_code')"
                         class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-600 w-5 h-5 mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-red-900 mb-1">Critical Change Warning</h4>
                                <p class="text-sm text-red-800">
                                    You are changing the currency code. This operation may fail if the station has
                                    existing financial data (deliveries, sales, price records) as it would corrupt
                                    financial calculations and audit trails.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirm -->
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm Update</h3>
                        <p class="text-sm text-gray-600">You're about to update the station with the following changes</p>
                    </div>

                    <!-- Final Review -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Station Name</label>
                                <p class="text-sm font-medium text-gray-900" x-text="formData.name"></p>
                                <span x-show="hasChanged('name')" class="text-xs text-orange-600">Changed</span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Location</label>
                                <p class="text-sm font-medium text-gray-900" x-text="formData.location"></p>
                                <span x-show="hasChanged('location')" class="text-xs text-orange-600">Changed</span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Currency</label>
                                <p class="text-sm font-medium text-gray-900" x-text="getCurrencyLabel(formData.currency_code)"></p>
                                <span x-show="hasChanged('currency_code')" class="text-xs text-red-600 font-medium">Changed</span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Timezone</label>
                                <p class="text-sm font-medium text-gray-900" x-text="getTimezoneLabel(formData.timezone)"></p>
                                <span x-show="hasChanged('timezone')" class="text-xs text-orange-600">Changed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-shield-alt text-blue-600 w-5 h-5 mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-blue-900 mb-1">Audit Trail</h4>
                                <p class="text-sm text-blue-800">
                                    All changes will be logged in the system audit trail with your user ID,
                                    timestamp, and IP address for security and compliance purposes.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button type="button"
                        @click="previousStep"
                        x-show="step > 1"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left w-4 h-4"></i>
                    Previous
                </button>

                <div x-show="step < 3">
                    <button type="button"
                            @click="nextStep"
                            :disabled="!canProceed"
                            :class="canProceed ? 'bg-gray-900 hover:bg-gray-800 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors">
                        <span x-text="step === 2 && getChanges().length === 0 ? 'No Changes to Review' : 'Next'"></span>
                        <i class="fas fa-arrow-right w-4 h-4"></i>
                    </button>
                </div>

                <div x-show="step === 3">
                    <button type="submit"
                            :disabled="isSubmitting || !isFormValid"
                            :class="(isSubmitting || !isFormValid) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-gray-900 hover:bg-gray-800 text-white'"
                            class="inline-flex items-center gap-2 px-6 py-2 text-sm font-medium rounded-lg transition-colors">
                        <i x-show="isSubmitting" class="fas fa-spinner fa-spin w-4 h-4"></i>
                        <i x-show="!isSubmitting" class="fas fa-save w-4 h-4"></i>
                        <span x-text="isSubmitting ? 'Updating...' : 'Update Station'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function stationEditor() {
    return {
        step: 1,
        isSubmitting: false,
        originalData: {
            name: '{{ $station->name }}',
            location: '{{ $station->location }}',
            currency_code: '{{ $station->currency_code }}',
            timezone: '{{ $station->timezone }}'
        },
        formData: {
            name: '{{ old('name', $station->name) }}',
            location: '{{ old('location', $station->location) }}',
            currency_code: '{{ old('currency_code', $station->currency_code) }}',
            timezone: '{{ old('timezone', $station->timezone) }}'
        },
        errors: {},
        currencies: @json($currencies),
        timezones: @json($timezones),

        get canProceed() {
            if (this.step === 1) {
                return this.formData.name && this.formData.location &&
                       this.formData.currency_code && this.formData.timezone &&
                       Object.keys(this.errors).length === 0;
            }
            if (this.step === 2) {
                return true; // Can always proceed from review
            }
            return true;
        },

        get isFormValid() {
            const hasAllData = this.formData.name && this.formData.name.trim() !== '' &&
                              this.formData.location && this.formData.location.trim() !== '' &&
                              this.formData.currency_code && this.formData.currency_code !== '' &&
                              this.formData.timezone && this.formData.timezone !== '';

            const hasNoErrors = Object.keys(this.errors).length === 0 ||
                               Object.values(this.errors).every(error => !error);

            console.log('Form validation check:', {
                hasAllData,
                hasNoErrors,
                formData: this.formData,
                errors: this.errors
            });

            return hasAllData && hasNoErrors;
        },

        hasChanged(field) {
            return this.formData[field] !== this.originalData[field];
        },

        getChanges() {
            const changes = [];
            const fieldLabels = {
                name: 'Station Name',
                location: 'Location',
                currency_code: 'Currency',
                timezone: 'Timezone'
            };

            for (const [field, label] of Object.entries(fieldLabels)) {
                if (this.hasChanged(field)) {
                    changes.push({
                        field,
                        label,
                        from: field === 'currency_code' ? this.getCurrencyLabel(this.originalData[field]) :
                              field === 'timezone' ? this.getTimezoneLabel(this.originalData[field]) :
                              this.originalData[field],
                        to: field === 'currency_code' ? this.getCurrencyLabel(this.formData[field]) :
                            field === 'timezone' ? this.getTimezoneLabel(this.formData[field]) :
                            this.formData[field]
                    });
                }
            }
            return changes;
        },

        nextStep() {
            if (this.canProceed && this.step < 3) {
                this.step++;
            }
        },

        previousStep() {
            if (this.step > 1) {
                this.step--;
            }
        },

        validateName() {
            this.errors = { ...this.errors }; // Ensure reactivity
            delete this.errors.name;

            if (!this.formData.name || this.formData.name.trim() === '') {
                this.errors.name = 'Station name is required';
                return false;
            }
            if (this.formData.name.length > 255) {
                this.errors.name = 'Station name cannot exceed 255 characters';
                return false;
            }
            if (!/^[a-zA-Z0-9\s\-\.\_]+$/.test(this.formData.name)) {
                this.errors.name = 'Station name can only contain letters, numbers, spaces, hyphens, dots, and underscores';
                return false;
            }
            return true;
        },

        validateLocation() {
            this.errors = { ...this.errors }; // Ensure reactivity
            delete this.errors.location;

            if (!this.formData.location || this.formData.location.trim() === '') {
                this.errors.location = 'Location is required';
                return false;
            }
            if (this.formData.location.length > 255) {
                this.errors.location = 'Location cannot exceed 255 characters';
                return false;
            }
            if (!/^[a-zA-Z0-9\s\-\.\,\_]+$/.test(this.formData.location)) {
                this.errors.location = 'Location can only contain letters, numbers, spaces, hyphens, dots, commas, and underscores';
                return false;
            }
            return true;
        },

        validateCurrency() {
            this.errors = { ...this.errors }; // Ensure reactivity
            delete this.errors.currency_code;

            if (!this.formData.currency_code) {
                this.errors.currency_code = 'Currency is required';
                return false;
            }
            if (!this.currencies[this.formData.currency_code]) {
                this.errors.currency_code = 'Invalid currency selection';
                return false;
            }
            return true;
        },

        validateTimezone() {
            this.errors = { ...this.errors }; // Ensure reactivity
            delete this.errors.timezone;

            if (!this.formData.timezone) {
                this.errors.timezone = 'Timezone is required';
                return false;
            }
            if (!this.timezones[this.formData.timezone]) {
                this.errors.timezone = 'Invalid timezone selection';
                return false;
            }
            return true;
        },

        getCurrencyLabel(code) {
            return this.currencies[code] || code || 'Not specified';
        },

        getTimezoneLabel(tz) {
            return this.timezones[tz] || tz || 'Not specified';
        },

        async submitForm() {
            console.log('Submit form called', this.formData);

            // Final validation
            const nameValid = this.validateName();
            const locationValid = this.validateLocation();
            const currencyValid = this.validateCurrency();
            const timezoneValid = this.validateTimezone();

            console.log('Validation results:', {nameValid, locationValid, currencyValid, timezoneValid});

            if (!this.isFormValid) {
                await Swal.fire({
                    title: 'Validation Error',
                    text: 'Please fix the errors before submitting',
                    icon: 'error',
                    confirmButtonColor: '#374151'
                });
                return;
            }

            // Check if no changes
            if (this.getChanges().length === 0) {
                await Swal.fire({
                    title: 'No Changes',
                    text: 'No changes were made to the station',
                    icon: 'info',
                    confirmButtonColor: '#374151'
                });
                return;
            }

            this.isSubmitting = true;

            try {
                // Create FormData object manually to ensure all data is included
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('_method', 'PUT');
                formData.append('name', this.formData.name.trim());
                formData.append('location', this.formData.location.trim());
                formData.append('currency_code', this.formData.currency_code);
                formData.append('timezone', this.formData.timezone);

                console.log('Submitting form data:', {
                    name: this.formData.name.trim(),
                    location: this.formData.location.trim(),
                    currency_code: this.formData.currency_code,
                    timezone: this.formData.timezone
                });

                const response = await fetch('{{ route('stations.update', $station->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                if (response.ok) {
                    // Check if response is JSON or redirect
                    const contentType = response.headers.get('content-type');

                    if (contentType && contentType.includes('application/json')) {
                        const data = await response.json();
                        console.log('JSON response:', data);

                        if (data.success || data.redirect) {
                            await Swal.fire({
                                title: 'Success!',
                                text: 'Station updated successfully',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            window.location.href = data.redirect || '{{ route('stations.index') }}';
                        } else {
                            throw new Error(data.message || 'Unknown error occurred');
                        }
                    } else {
                        // Likely a redirect response, follow it
                        await Swal.fire({
                            title: 'Success!',
                            text: 'Station updated successfully',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.href = '{{ route('stations.index') }}';
                    }
                } else {
                    // Handle error responses
                    const data = await response.json().catch(() => ({}));
                    console.log('Error response:', data);

                    if (data.errors) {
                        // Validation errors from server
                        this.errors = data.errors;
                        this.step = 1; // Go back to first step to show errors

                        await Swal.fire({
                            title: 'Validation Error',
                            text: 'Please fix the errors and try again',
                            icon: 'error',
                            confirmButtonColor: '#374151'
                        });
                    } else {
                        let errorTitle = 'Update Failed';
                        let errorText = data.message || `Server error: ${response.status}`;

                        // Handle specific currency change error
                        if (errorText.includes('currency') || errorText.includes('financial data')) {
                            errorTitle = 'Currency Change Blocked';
                            errorText = 'Cannot change currency: This station has existing financial data. Currency changes would corrupt financial calculations.';
                        }

                        throw new Error(errorText);
                    }
                }

            } catch (error) {
                console.error('Update station error:', error);

                let errorTitle = 'Update Failed';
                let errorText = error.message || 'Failed to update station. Please try again.';

                // Handle specific currency change error
                if (error.message && (error.message.includes('currency') || error.message.includes('financial data'))) {
                    errorTitle = 'Currency Change Blocked';
                    errorText = 'Cannot change currency: This station has existing financial data. Currency changes would corrupt financial calculations.';
                }

                await Swal.fire({
                    title: errorTitle,
                    text: errorText,
                    icon: 'error',
                    confirmButtonColor: '#374151'
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>
@endsection
