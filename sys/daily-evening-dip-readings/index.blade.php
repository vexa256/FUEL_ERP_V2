@extends('layouts.app')

@section('title', 'Evening Dip Readings')

@section('breadcrumb')
<span class="text-muted-foreground">Operations</span>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">Evening Dip Readings</span>
@endsection

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-2xl font-bold text-foreground">Evening Dip Readings</h1>
        <p class="text-muted-foreground mt-1">Record closing fuel levels for {{ $today }}</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-green-700 text-sm font-medium">Auto-Reconciliation Active</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="eveningReadingsWizard()" x-init="init()" class="space-y-6">
    <!-- Station Context Bar - Always Visible -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <i class="fas fa-building text-gray-600"></i>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Active Station</label>
                    @if(count($stations) > 1)
                        <!-- Admin Multi-Station Selector -->
                        <select x-model="selectedStation" @change="onStationChange()"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent font-medium">
                            <option value="">Select Station...</option>
                            @foreach($stations as $station)
                            <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                            @endforeach
                        </select>
                    @else
                        <!-- Single Station Display -->
                        <div class="text-lg font-semibold text-gray-900">{{ $stations[0]->name }}</div>
                        <div class="text-sm text-gray-600">{{ $stations[0]->location }}</div>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-green-700 text-sm font-medium">Auto-Reconciliation Active</span>
                </div>
                <div class="text-sm text-gray-500">{{ $today }}</div>
            </div>
        </div>
    </div>

    <!-- Data Filter Controls -->
    <div x-show="selectedStation" x-transition class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Filters & Search</h3>
            <button @click="resetFilters()" class="text-sm text-gray-600 hover:text-gray-800">
                <i class="fas fa-undo mr-1"></i>Reset
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <input x-model="filters.search" @input="applyFilters()" type="text"
                       placeholder="Search tanks..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
            </div>
            <!-- Fuel Type Filter -->
            <div>
                <select x-model="filters.fuelType" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Fuel Types</option>
                    <option value="petrol">Petrol</option>
                    <option value="diesel">Diesel</option>
                    <option value="kerosene">Kerosene</option>
                </select>
            </div>
            <!-- Capacity Range -->
            <div>
                <select x-model="filters.capacityRange" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Capacities</option>
                    <option value="small">Small (< 30,000L)</option>
                    <option value="medium">Medium (30,000L - 70,000L)</option>
                    <option value="large">Large (> 70,000L)</option>
                </select>
            </div>
            <!-- Status Filter -->
            <div>
                <select x-model="filters.status" @change="applyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div x-show="selectedStation" x-transition class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <nav class="flex items-center space-x-8">
                <button @click="setTab('pending')" :class="tabClasses('pending')" class="wizard-tab flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    Pending Tanks
                    <span x-show="filteredPendingCount > 0" x-text="filteredPendingCount" class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full"></span>
                </button>
                <button @click="setTab('completed')" :class="tabClasses('completed')" class="wizard-tab flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Completed
                    <span x-show="filteredCompletedCount > 0" x-text="filteredCompletedCount" class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full"></span>
                </button>
            </nav>
        </div>

        <div class="p-6">

            <!-- Pending Tanks Tab -->
            <div x-show="activeTab === 'pending'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Tanks Requiring Evening Readings</h3>
                    <div class="text-sm text-gray-600">
                        <span x-text="filteredPendingCount"></span> of <span x-text="pendingTanks.length"></span> tanks
                    </div>
                </div>

                <div x-show="filteredPendingTanks.length === 0" class="text-center py-12">
                    <div x-show="pendingTanks.length === 0">
                        <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">All Evening Readings Complete</h4>
                        <p class="text-gray-600">No tanks require evening dip readings for today.</p>
                    </div>
                    <div x-show="pendingTanks.length > 0 && filteredPendingTanks.length === 0">
                        <i class="fas fa-filter text-gray-400 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">No Tanks Match Filters</h4>
                        <p class="text-gray-600">Try adjusting your search criteria.</p>
                        <button @click="resetFilters()" class="mt-3 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <div x-show="filteredPendingTanks.length > 0" class="space-y-4">
                    <template x-for="tank in filteredPendingTanks" :key="tank.id">
                        <div @click="openModal(tank)" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-all duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="font-medium text-gray-900" x-text="`Tank ${tank.tank_number}`"></h4>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full capitalize" x-text="tank.fuel_type"></span>
                                        <!-- Capacity Indicator -->
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="getCapacityClass(tank.capacity_liters)"
                                              x-text="getCapacityLabel(tank.capacity_liters)"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                        <div>Morning Reading: <span class="font-medium" x-text="formatNumber(tank.morning_dip_liters) + ' L'"></span></div>
                                        <div>Capacity: <span class="font-medium" x-text="formatNumber(tank.capacity_liters) + ' L'"></span></div>
                                        <div x-show="tank.water_level_mm">Water Level: <span class="font-medium" x-text="tank.water_level_mm + ' mm'"></span></div>
                                        <div x-show="tank.temperature_celsius">Temperature: <span class="font-medium" x-text="tank.temperature_celsius + '°C'"></span></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button class="px-4 py-2 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                        Record Reading
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Completed Tab -->
            <div x-show="activeTab === 'completed'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Completed Evening Readings</h3>
                    <div class="text-sm text-gray-600">
                        <span x-text="filteredCompletedCount"></span> of <span x-text="completedReadings.length"></span> readings
                    </div>
                </div>

                <div x-show="filteredCompletedReadings.length === 0" class="text-center py-12">
                    <div x-show="completedReadings.length === 0">
                        <i class="fas fa-clock text-gray-400 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">No Completed Readings</h4>
                        <p class="text-gray-600">No evening readings have been recorded yet today.</p>
                    </div>
                    <div x-show="completedReadings.length > 0 && filteredCompletedReadings.length === 0">
                        <i class="fas fa-filter text-gray-400 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">No Readings Match Filters</h4>
                        <p class="text-gray-600">Try adjusting your search criteria.</p>
                        <button @click="resetFilters()" class="mt-3 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <div x-show="filteredCompletedReadings.length > 0" class="space-y-4">
                    <template x-for="reading in filteredCompletedReadings" :key="`${reading.tank_number}-${reading.fuel_type}`">
                        <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <h4 class="font-medium text-gray-900" x-text="`Tank ${reading.tank_number}`"></h4>
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full capitalize" x-text="reading.fuel_type"></span>
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="text-sm text-gray-600" x-text="`By ${reading.first_name} ${reading.last_name}`"></div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div><span class="text-gray-600">Morning:</span> <span class="font-medium" x-text="formatNumber(reading.morning_dip_liters) + ' L'"></span></div>
                                <div><span class="text-gray-600">Evening:</span> <span class="font-medium" x-text="formatNumber(reading.evening_dip_liters) + ' L'"></span></div>
                                <div x-show="reading.water_level_mm"><span class="text-gray-600">Water:</span> <span class="font-medium" x-text="reading.water_level_mm + ' mm'"></span></div>
                                <div x-show="reading.temperature_celsius"><span class="text-gray-600">Temp:</span> <span class="font-medium" x-text="reading.temperature_celsius + '°C'"></span></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Reading Entry Modal -->
    <div x-show="showModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">

            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Evening Dip Reading</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitReading" class="p-6 space-y-6">
                <!-- Tank Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-600">Tank:</span> <span class="font-medium ml-2" x-text="modalData.tank_number"></span></div>
                        <div><span class="text-gray-600">Fuel Type:</span> <span class="font-medium ml-2 capitalize" x-text="modalData.fuel_type"></span></div>
                        <div><span class="text-gray-600">Capacity:</span> <span class="font-medium ml-2" x-text="formatNumber(modalData.capacity_liters) + ' L'"></span></div>
                        <div><span class="text-gray-600">Morning Reading:</span> <span class="font-medium ml-2" x-text="formatNumber(modalData.morning_dip_liters) + ' L'"></span></div>
                    </div>
                </div>

                <!-- Evening Reading -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-900">
                        Evening Dip Reading (Liters) <span class="text-red-500">*</span>
                    </label>
                    <input x-model="form.evening_dip_liters" @input="validateReading"
                           type="number" step="0.001" min="0" :max="modalData.capacity_liters" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                           placeholder="0.000">
                    <p class="text-xs text-gray-500">Enter the current fuel level in the tank</p>
                </div>

                <!-- Water Level -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-900">Water Level (mm)</label>
                    <input x-model="form.water_level_mm" type="number" step="0.01" min="0" max="99999.99"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                           placeholder="0.00">
                    <p class="text-xs text-gray-500">Optional: Water contamination level</p>
                </div>

                <!-- Temperature -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-900">Temperature (°C)</label>
                    <input x-model="form.temperature_celsius" type="number" step="0.01" min="-10" max="60"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                           placeholder="25.00">
                    <p class="text-xs text-gray-500">Optional: Current fuel temperature</p>
                </div>

                <!-- Validation Messages -->
                <div x-show="validationError" x-transition class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-red-600 text-sm" x-text="validationError"></p>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="closeModal"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 bg-black text-white hover:bg-gray-800 rounded-lg font-medium transition-colors disabled:opacity-50">
                        <span x-show="!isSubmitting">Record Reading</span>
                        <span x-show="isSubmitting" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Recording...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wizard-tab {
    @apply pb-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200;
}
.wizard-tab.tab-active {
    @apply text-black border-black;
}
.wizard-tab.tab-disabled {
    @apply text-gray-400 border-transparent cursor-not-allowed opacity-50;
}
.wizard-tab.tab-inactive {
    @apply text-gray-500 border-transparent hover:text-gray-700;
}
</style>

<script>
function eveningReadingsWizard() {
    return {
        // State
        activeTab: 'pending',
        selectedStation: {{ count($stations) === 1 ? $stations[0]->id : ($selectedStation ?? 'null') }},
        selectedStationName: '{{ count($stations) === 1 ? $stations[0]->name : "" }}',
        pendingTanks: @json($pendingTanks),
        completedReadings: @json($completedReadings),
        showModal: false,
        isSubmitting: false,
        validationError: '',

        // Filters
        filters: {
            search: '',
            fuelType: '',
            capacityRange: '',
            status: ''
        },

        // Filtered data
        filteredPendingTanks: [],
        filteredCompletedReadings: [],

        // Form data
        form: {
            station_id: '',
            tank_id: '',
            reading_date: '{{ $today }}',
            evening_dip_liters: '',
            water_level_mm: '',
            temperature_celsius: ''
        },

        // Modal data
        modalData: {},

        // Computed
        get pendingCount() { return this.pendingTanks.length; },
        get completedCount() { return this.completedReadings.length; },
        get filteredPendingCount() { return this.filteredPendingTanks.length; },
        get filteredCompletedCount() { return this.filteredCompletedReadings.length; },

        // Methods
        init() {
            console.log('🚀 Evening Readings Wizard Initialized');
            console.log('📊 Station Count:', {{ count($stations) }});

            // Initialize filtered data
            this.filteredPendingTanks = [...this.pendingTanks];
            this.filteredCompletedReadings = [...this.completedReadings];

            @if(count($stations) === 1)
                // Single station - auto-load data
                this.loadStationData();
            @elseif($selectedStation)
                // Admin with selected station
                this.loadStationData();
            @else
                // Admin - require station selection
                this.selectedStation = null;
            @endif
        },

        tabClasses(tab) {
            return this.activeTab === tab ? 'wizard-tab tab-active' : 'wizard-tab tab-inactive';
        },

        setTab(tab) {
            if (!this.selectedStation) {
                this.showError('Please select a station first');
                return;
            }
            this.activeTab = tab;
        },

        onStationChange() {
            if (this.selectedStation) {
                this.loadStationData();
                this.resetFilters();
            }
        },

        async loadStationData() {
            if (!this.selectedStation) return;

            try {
                const response = await fetch(`{{ route('daily-evening-dip-readings.pending') }}?station_id=${this.selectedStation}&reading_date={{ $today }}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                if (data.success) {
                    this.pendingTanks = data.data.pending_tanks || [];
                    this.applyFilters();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('❌ Load Error:', error);
                this.showError(`Failed to load station data: ${error.message}`);
            }
        },

        // DOM-Based Filtering Functions
        applyFilters() {
            console.log('🔍 Applying filters:', this.filters);

            // Filter pending tanks
            this.filteredPendingTanks = this.pendingTanks.filter(tank => {
                return this.matchesSearch(tank) &&
                       this.matchesFuelType(tank) &&
                       this.matchesCapacityRange(tank) &&
                       this.matchesStatus(tank, 'pending');
            });

            // Filter completed readings
            this.filteredCompletedReadings = this.completedReadings.filter(reading => {
                return this.matchesSearch(reading) &&
                       this.matchesFuelType(reading) &&
                       this.matchesStatus(reading, 'completed');
            });

            console.log(`📊 Filtered Results: ${this.filteredPendingTanks.length} pending, ${this.filteredCompletedReadings.length} completed`);
        },

        matchesSearch(item) {
            if (!this.filters.search) return true;

            const searchTerm = this.filters.search.toLowerCase();
            const tankNumber = (item.tank_number || '').toLowerCase();
            const fuelType = (item.fuel_type || '').toLowerCase();

            return tankNumber.includes(searchTerm) || fuelType.includes(searchTerm);
        },

        matchesFuelType(item) {
            if (!this.filters.fuelType) return true;
            return item.fuel_type === this.filters.fuelType;
        },

        matchesCapacityRange(item) {
            if (!this.filters.capacityRange || !item.capacity_liters) return true;

            const capacity = parseFloat(item.capacity_liters);
            switch (this.filters.capacityRange) {
                case 'small': return capacity < 30000;
                case 'medium': return capacity >= 30000 && capacity <= 70000;
                case 'large': return capacity > 70000;
                default: return true;
            }
        },

        matchesStatus(item, expectedStatus) {
            if (!this.filters.status) return true;
            return this.filters.status === expectedStatus;
        },

        resetFilters() {
            this.filters = {
                search: '',
                fuelType: '',
                capacityRange: '',
                status: ''
            };
            this.applyFilters();
        },

        // Capacity helper functions
        getCapacityClass(capacity) {
            const cap = parseFloat(capacity);
            if (cap < 30000) return 'bg-orange-100 text-orange-800';
            if (cap <= 70000) return 'bg-blue-100 text-blue-800';
            return 'bg-purple-100 text-purple-800';
        },

        getCapacityLabel(capacity) {
            const cap = parseFloat(capacity);
            if (cap < 30000) return 'Small';
            if (cap <= 70000) return 'Medium';
            return 'Large';
        },

        openModal(tank) {
            this.modalData = tank;
            this.form = {
                station_id: this.selectedStation,
                tank_id: tank.id,
                reading_date: '{{ $today }}',
                evening_dip_liters: '',
                water_level_mm: tank.water_level_mm || '',
                temperature_celsius: tank.temperature_celsius || ''
            };
            this.validationError = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.validationError = '';
        },

        validateReading() {
            const reading = parseFloat(this.form.evening_dip_liters);
            const capacity = parseFloat(this.modalData.capacity_liters);

            if (reading > capacity) {
                this.validationError = `Reading cannot exceed tank capacity (${this.formatNumber(capacity)} L)`;
                return false;
            }

            if (reading < 0) {
                this.validationError = 'Reading cannot be negative';
                return false;
            }

            this.validationError = '';
            return true;
        },

        async submitReading() {
            if (!this.validateReading()) return;

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                Object.keys(this.form).forEach(key => {
                    if (this.form[key] !== '') formData.append(key, this.form[key]);
                });
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch('{{ route("daily-evening-dip-readings.store") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message);
                    this.closeModal();
                    await this.loadStationData();
                } else {
                    this.validationError = data.error || 'Failed to record reading';
                }
            } catch (error) {
                console.error('❌ Submit Error:', error);
                this.validationError = `Network error: ${error.message}`;
            } finally {
                this.isSubmitting = false;
            }
        },

        formatNumber(num) {
            return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
        },

        showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        },

        showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#000000'
            });
        }
    };
}
</script>
@endsection
