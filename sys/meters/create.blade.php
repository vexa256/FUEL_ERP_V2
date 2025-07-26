@extends('layouts.app')

@section('title', 'Create New Meter')

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Create New Meter</h1>
        <p class="text-slate-600 mt-2">Add a new fuel dispensing meter to your station</p>
    </div>
    <a href="{{ route('meters.index', $station_id ? ['station_id' => $station_id] : []) }}"
       class="inline-flex items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all duration-200 shadow-sm">
        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
        Back to Meters
    </a>
</div>
@endsection

@section('content')
<div x-data="meterCreateWizard"
     x-init="initializeData(@js([
        'userRole' => auth()->user()->role,
        'userStationId' => auth()->user()->station_id ?? '',
        'preselectedStationId' => $station_id ?? '',
        'preselectedTankId' => $tank_id ?? '',
        'existingMeterNumbers' => $existing_meter_numbers ?? [],
        'availableStations' => $accessible_stations ?? [],
        'availableTanks' => $tanks ?? []
     ]))" class="space-y-8">

    <!-- MANDATORY STATION SELECTION INTERFACE -->
    @if(auth()->user()->role === 'admin')
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl border border-slate-700 shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-building w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Station Context</h3>
                    <p class="text-slate-300 text-sm">Select the station where this meter will be installed</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right mr-4" x-show="selectedStation">
                    <div class="text-lg font-bold" x-text="selectedStation?.name || ''"></div>
                    <div class="text-slate-300 text-sm" x-text="selectedStation?.location || ''"></div>
                </div>
                <div class="px-3 py-2 bg-yellow-500/20 border border-yellow-500/30 rounded-lg">
                    <span class="text-yellow-200 text-xs font-semibold">ADMIN</span>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl border border-blue-500 shadow-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-map-marker-alt w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Your Station</h3>
                    <p class="text-blue-100 text-sm">Creating meter for your assigned station</p>
                </div>
            </div>
            <div class="text-right">
                @php $user_station = $accessible_stations->first(); @endphp
                @if($user_station)
                <div class="text-lg font-bold">{{ $user_station->name }}</div>
                <div class="text-blue-200 text-sm">{{ $user_station->location }}</div>
                @endif
                <div class="mt-2 px-3 py-1 bg-green-500/20 border border-green-400/30 rounded-lg">
                    <span class="text-green-200 text-xs font-semibold">{{ strtoupper(auth()->user()->role) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Wizard Progress Indicator -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-slate-900">Meter Creation Progress</h3>
            <div class="text-sm text-slate-500">
                Step <span x-text="currentStep"></span> of 4
            </div>
        </div>

        <div class="flex items-center justify-between">
            <!-- Step 1: Station Selection -->
            <div class="flex items-center" :class="currentStep >= 1 ? 'text-slate-900' : 'text-slate-400'">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200"
                     :class="currentStep >= 1 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300'">
                    <i class="fas fa-building w-4 h-4" x-show="currentStep > 1"></i>
                    <span class="text-sm font-semibold" x-show="currentStep <= 1">1</span>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium">Station</div>
                    <div class="text-xs text-slate-500">Select location</div>
                </div>
            </div>

            <div class="flex-1 h-0.5 mx-4 transition-all duration-200"
                 :class="currentStep >= 2 ? 'bg-slate-900' : 'bg-slate-300'"></div>

            <!-- Step 2: Tank Selection -->
            <div class="flex items-center" :class="currentStep >= 2 ? 'text-slate-900' : 'text-slate-400'">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200"
                     :class="currentStep >= 2 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300'">
                    <i class="fas fa-gas-pump w-4 h-4" x-show="currentStep > 2"></i>
                    <span class="text-sm font-semibold" x-show="currentStep <= 2">2</span>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium">Tank</div>
                    <div class="text-xs text-slate-500">Choose tank</div>
                </div>
            </div>

            <div class="flex-1 h-0.5 mx-4 transition-all duration-200"
                 :class="currentStep >= 3 ? 'bg-slate-900' : 'bg-slate-300'"></div>

            <!-- Step 3: Meter Details -->
            <div class="flex items-center" :class="currentStep >= 3 ? 'text-slate-900' : 'text-slate-400'">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200"
                     :class="currentStep >= 3 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300'">
                    <i class="fas fa-tachometer-alt w-4 h-4" x-show="currentStep > 3"></i>
                    <span class="text-sm font-semibold" x-show="currentStep <= 3">3</span>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium">Details</div>
                    <div class="text-xs text-slate-500">Meter info</div>
                </div>
            </div>

            <div class="flex-1 h-0.5 mx-4 transition-all duration-200"
                 :class="currentStep >= 4 ? 'bg-slate-900' : 'bg-slate-300'"></div>

            <!-- Step 4: Review -->
            <div class="flex items-center" :class="currentStep >= 4 ? 'text-slate-900' : 'text-slate-400'">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200"
                     :class="currentStep >= 4 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300'">
                    <i class="fas fa-check w-4 h-4" x-show="currentStep > 4"></i>
                    <span class="text-sm font-semibold" x-show="currentStep <= 4">4</span>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium">Review</div>
                    <div class="text-xs text-slate-500">Confirm & create</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Creation Form -->
    <form @submit.prevent="submitForm" class="space-y-8">
        @csrf

        <!-- Step 1: Station Selection -->
        <div x-show="currentStep === 1" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Step 1: Select Station</h3>
                <p class="text-sm text-slate-600 mt-1">Choose the station where this meter will be installed</p>
            </div>

            <div class="p-6">
                @if(auth()->user()->role === 'admin')
                <div class="space-y-4">
                    <label class="block text-sm font-medium text-slate-700">Available Stations</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($accessible_stations as $station)
                        <div class="station-card cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 hover:shadow-md"
                             :class="selectedStationId === '{{ $station->id }}' ? 'border-slate-900 bg-slate-50' : 'border-slate-200 hover:border-slate-300'"
                             @click="selectStation('{{ $station->id }}', @js($station->name), @js($station->location))">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-slate-900">{{ $station->name }}</h4>
                                    <p class="text-sm text-slate-600 mt-1">{{ $station->location }}</p>
                                </div>
                                <div class="ml-3">
                                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-200"
                                         :class="selectedStationId === '{{ $station->id }}' ? 'border-slate-900 bg-slate-900' : 'border-slate-300'">
                                        <i class="fas fa-check text-white w-3 h-3" x-show="selectedStationId === '{{ $station->id }}'"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    @php $user_station = $accessible_stations->first(); @endphp
                    @if($user_station)
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                        <i class="fas fa-building text-blue-600 w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">{{ $user_station->name }}</h3>
                    <p class="text-slate-600 mb-6">{{ $user_station->location }}</p>
                    <div class="max-w-md mx-auto bg-slate-50 rounded-xl p-4">
                        <p class="text-sm text-slate-700">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            You are creating a meter for your assigned station. This cannot be changed.
                        </p>
                    </div>
                    @endif
                </div>
                @endif

                <div class="flex justify-end pt-6">
                    <button type="button" @click="nextStep"
                            :disabled="!canProceedFromStep1"
                            :class="canProceedFromStep1 ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                            class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-200 shadow-sm">
                        Continue to Tank Selection
                        <i class="fas fa-arrow-right w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Tank Selection -->
        <div x-show="currentStep === 2" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Step 2: Select Tank</h3>
                <p class="text-sm text-slate-600 mt-1">Choose which tank this meter will monitor</p>
            </div>

            <div class="p-6">
                <div x-show="!loadingTanks">
                    <div x-show="availableTanks.length === 0" class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-4">
                            <i class="fas fa-gas-pump text-slate-400 w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 mb-2">No Tanks Available</h3>
                        <p class="text-slate-600">No tanks found for the selected station.</p>
                    </div>

                    <div x-show="availableTanks.length > 0" class="space-y-4">
                        <label class="block text-sm font-medium text-slate-700">Available Tanks</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="tank in availableTanks" :key="tank.id">
                                <div class="tank-card cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 hover:shadow-md"
                                     :class="selectedTankId === tank.id.toString() ? 'border-slate-900 bg-slate-50' : 'border-slate-200 hover:border-slate-300'"
                                     @click="selectTank(tank)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <h4 class="font-semibold text-slate-900" x-text="tank.tank_number || ''"></h4>
                                                <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
                                                      :class="{
                                                          'bg-green-100 text-green-800': tank.fuel_type === 'petrol',
                                                          'bg-blue-100 text-blue-800': tank.fuel_type === 'diesel',
                                                          'bg-orange-100 text-orange-800': tank.fuel_type === 'kerosene'
                                                      }"
                                                      x-text="tank.fuel_type ? tank.fuel_type.charAt(0).toUpperCase() + tank.fuel_type.slice(1) : ''"></span>
                                            </div>
                                            <div class="space-y-1 text-sm text-slate-600">
                                                <div class="flex items-center">
                                                    <i class="fas fa-weight w-3 h-3 mr-2"></i>
                                                    <span x-text="tank.capacity_liters ? Number(tank.capacity_liters).toLocaleString() : '0'"></span>L capacity
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="fas fa-tachometer-alt w-3 h-3 mr-2"></i>
                                                    <span x-text="tank.meter_count || 0"></span> existing meters
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-200"
                                                 :class="selectedTankId === tank.id.toString() ? 'border-slate-900 bg-slate-900' : 'border-slate-300'">
                                                <i class="fas fa-check text-white w-3 h-3" x-show="selectedTankId === tank.id.toString()"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-show="loadingTanks" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900"></div>
                    <p class="text-slate-600 mt-2">Loading tanks...</p>
                </div>

                <div class="flex justify-between pt-6">
                    <button type="button" @click="previousStep"
                            class="inline-flex items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all duration-200">
                        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                        Back to Station
                    </button>
                    <button type="button" @click="nextStep"
                            :disabled="!canProceedFromStep2"
                            :class="canProceedFromStep2 ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                            class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-200 shadow-sm">
                        Continue to Meter Details
                        <i class="fas fa-arrow-right w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Meter Details -->
        <div x-show="currentStep === 3" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Step 3: Meter Details</h3>
                <p class="text-sm text-slate-600 mt-1">Enter the meter information and initial reading</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Meter Number -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Meter Number</label>
                    <div class="relative">
                        <input type="text"
                               x-model="meterNumber"
                               @input="validateMeterNumber"
                               placeholder="e.g., MTR-001, PUMP-A1"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200"
                               :class="meterNumberError ? 'border-red-300 bg-red-50' : 'border-slate-300'"
                               maxlength="50">
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-check text-green-500 w-4 h-4" x-show="meterNumber && !meterNumberError"></i>
                            <i class="fas fa-times text-red-500 w-4 h-4" x-show="meterNumberError"></i>
                        </div>
                    </div>
                    <div x-show="meterNumberError" class="text-sm text-red-600" x-text="meterNumberError"></div>
                    <div class="text-xs text-slate-500">
                        Use uppercase letters, numbers, hyphens, and underscores only
                    </div>
                </div>

                <!-- Current Reading -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Initial Reading (Liters)</label>
                    <div class="relative">
                        <input type="number"
                               x-model="currentReading"
                               @input="validateCurrentReading"
                               placeholder="0.000"
                               step="0.001"
                               min="0"
                               max="999999999.999"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-200"
                               :class="currentReadingError ? 'border-red-300 bg-red-50' : 'border-slate-300'">
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 text-sm">
                            L
                        </div>
                    </div>
                    <div x-show="currentReadingError" class="text-sm text-red-600" x-text="currentReadingError"></div>
                    <div class="text-xs text-slate-500">
                        Enter the current meter reading with up to 3 decimal places
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Initial Status</label>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="isActive" class="sr-only">
                            <div class="relative">
                                <div class="w-10 h-6 bg-slate-200 rounded-full transition-colors duration-200"
                                     :class="isActive ? 'bg-slate-900' : 'bg-slate-200'"></div>
                                <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"
                                     :class="isActive ? 'transform translate-x-4' : ''"></div>
                            </div>
                            <span class="ml-3 text-sm font-medium text-slate-700">
                                <span x-text="isActive ? 'Active' : 'Inactive'"></span>
                            </span>
                        </label>
                    </div>
                    <div class="text-xs text-slate-500">
                        Inactive meters cannot be used for readings until activated
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4">
                <div class="flex justify-between">
                    <button type="button" @click="previousStep"
                            class="inline-flex items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all duration-200">
                        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                        Back to Tank Selection
                    </button>
                    <button type="button" @click="nextStep"
                            :disabled="!canProceedFromStep3"
                            :class="canProceedFromStep3 ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                            class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-200 shadow-sm">
                        Review & Create Meter
                        <i class="fas fa-arrow-right w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: Review & Submit -->
        <div x-show="currentStep === 4" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Step 4: Review & Confirm</h3>
                <p class="text-sm text-slate-600 mt-1">Please review the meter details before creating</p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Station Summary -->
                    <div class="bg-slate-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-900 mb-3">Station Information</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-building text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Name:</span>
                                <span class="ml-2 font-medium" x-text="selectedStation?.name || ''"></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Location:</span>
                                <span class="ml-2 font-medium" x-text="selectedStation?.location || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tank Summary -->
                    <div class="bg-slate-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-900 mb-3">Tank Information</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-gas-pump text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Tank:</span>
                                <span class="ml-2 font-medium" x-text="selectedTank?.tank_number || ''"></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-oil-can text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Fuel Type:</span>
                                <span class="ml-2 font-medium" x-text="selectedTank?.fuel_type ? selectedTank.fuel_type.charAt(0).toUpperCase() + selectedTank.fuel_type.slice(1) : ''"></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-weight text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Capacity:</span>
                                <span class="ml-2 font-medium" x-text="selectedTank?.capacity_liters ? Number(selectedTank.capacity_liters).toLocaleString() + 'L' : ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Meter Summary -->
                    <div class="bg-slate-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-900 mb-3">Meter Details</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-tachometer-alt text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Number:</span>
                                <span class="ml-2 font-medium" x-text="meterNumber || ''"></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-gauge text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Initial Reading:</span>
                                <span class="ml-2 font-medium" x-text="Number(currentReading || 0).toFixed(3) + 'L'"></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-power-off text-slate-400 w-4 h-4 mr-3"></i>
                                <span class="text-slate-600">Status:</span>
                                <span class="ml-2 font-medium" :class="isActive ? 'text-green-600' : 'text-red-600'" x-text="isActive ? 'Active' : 'Inactive'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                        <h4 class="font-semibold text-blue-900 mb-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            Important Notes
                        </h4>
                        <ul class="space-y-1 text-sm text-blue-800">
                            <li>• This meter will be permanently assigned to the selected tank</li>
                            <li>• The initial reading cannot be reduced once set</li>
                            <li>• Meter number must be unique across all stations</li>
                            <li>• Only active meters can be used for daily readings</li>
                        </ul>
                    </div>
                </div>

                <!-- Hidden form inputs -->
                <input type="hidden" name="tank_id" :value="selectedTankId">
                <input type="hidden" name="meter_number" :value="meterNumber">
                <input type="hidden" name="current_reading_liters" :value="currentReading">
                <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
            </div>

            <div class="bg-slate-50 px-6 py-4">
                <div class="flex justify-between">
                    <button type="button" @click="previousStep"
                            class="inline-flex items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all duration-200">
                        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                        Back to Details
                    </button>
                    <button type="submit"
                            :disabled="isSubmitting"
                            :class="isSubmitting ? 'bg-slate-400 cursor-not-allowed' : 'bg-slate-900 hover:bg-slate-800'"
                            class="inline-flex items-center px-8 py-3 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg">
                        <div x-show="!isSubmitting" class="flex items-center">
                            <i class="fas fa-plus w-4 h-4 mr-2"></i>
                            Create Meter
                        </div>
                        <div x-show="isSubmitting" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                            Creating...
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function meterCreateWizard() {
    return {
        // Initialize all variables with defaults to prevent undefined errors
        userRole: 'user',
        userStationId: '',
        existingMeterNumbers: [],
        availableStations: [],
        availableTanks: [],

        // Wizard state
        currentStep: 1,
        isSubmitting: false,
        loadingTanks: false,

        // Form data with safe defaults
        selectedStationId: '',
        selectedTankId: '',
        selectedStation: null,
        selectedTank: null,
        meterNumber: '',
        currentReading: '0.000',
        isActive: true,

        // Validation errors
        meterNumberError: '',
        currentReadingError: '',

        // Initialize data after Alpine component is ready
        initializeData(config) {
            this.userRole = config.userRole || 'user';
            this.userStationId = config.userStationId || '';
            this.existingMeterNumbers = config.existingMeterNumbers || [];
            this.availableStations = config.availableStations || [];
            this.availableTanks = config.availableTanks || [];

            // Set initial station selection
            this.selectedStationId = config.preselectedStationId || (config.userRole !== 'admin' ? config.userStationId : '');
            this.selectedTankId = config.preselectedTankId || '';

            // Initialize after data is set
            this.$nextTick(() => {
                this.initializeComponent();
            });
        },

        initializeComponent() {
            // Auto-select station for non-admin users
            if (this.userRole !== 'admin' && this.availableStations.length > 0) {
                const userStation = this.availableStations[0];
                this.selectStation(userStation.id, userStation.name, userStation.location);
                this.loadTanksForStation(userStation.id);
            } else if (this.selectedStationId) {
                // Handle preselected station
                const station = this.availableStations.find(s => s.id.toString() === this.selectedStationId);
                if (station) {
                    this.selectStation(station.id, station.name, station.location);
                    this.loadTanksForStation(station.id);
                }
            }

            // Handle preselected tank
            if (this.selectedTankId && this.availableTanks.length > 0) {
                const tank = this.availableTanks.find(t => t.id.toString() === this.selectedTankId);
                if (tank) {
                    this.selectTank(tank);
                }
            }
        },

        get canProceedFromStep1() {
            return this.selectedStationId && this.selectedStation;
        },

        get canProceedFromStep2() {
            return this.selectedTankId && this.selectedTank;
        },

        get canProceedFromStep3() {
            return this.meterNumber &&
                   !this.meterNumberError &&
                   this.currentReading !== '' &&
                   !this.currentReadingError;
        },

        selectStation(stationId, stationName, stationLocation) {
            this.selectedStationId = stationId;
            this.selectedStation = {
                id: stationId,
                name: stationName,
                location: stationLocation
            };

            // Load tanks for this station
            this.loadTanksForStation(stationId);
        },

        async loadTanksForStation(stationId) {
            if (!stationId) return;

            this.loadingTanks = true;
            this.availableTanks = [];
            this.selectedTankId = '';
            this.selectedTank = null;

            try {
                const response = await fetch(`/meters/stations/${stationId}/tanks`);
                const data = await response.json();

                if (data.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: data.error
                    });
                    return;
                }

                this.availableTanks = data.tanks || [];

                // Auto-select tank if only one available
                if (this.availableTanks.length === 1) {
                    this.selectTank(this.availableTanks[0]);
                }

            } catch (error) {
                console.error('Failed to load tanks:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to load tanks for selected station'
                });
            } finally {
                this.loadingTanks = false;
            }
        },

        selectTank(tank) {
            this.selectedTankId = tank.id.toString();
            this.selectedTank = tank;
        },

        validateMeterNumber() {
            this.meterNumberError = '';

            if (!this.meterNumber) {
                this.meterNumberError = 'Meter number is required';
                return;
            }

            // Check format
            if (!/^[A-Z0-9\-\_]+$/.test(this.meterNumber)) {
                this.meterNumberError = 'Use only uppercase letters, numbers, hyphens, and underscores';
                return;
            }

            // Check length
            if (this.meterNumber.length > 50) {
                this.meterNumberError = 'Meter number cannot exceed 50 characters';
                return;
            }

            // Check uniqueness
            if (this.existingMeterNumbers.includes(this.meterNumber.toUpperCase())) {
                this.meterNumberError = 'This meter number already exists';
                return;
            }
        },

        validateCurrentReading() {
            this.currentReadingError = '';

            if (this.currentReading === '' || this.currentReading === null) {
                this.currentReadingError = 'Initial reading is required';
                return;
            }

            const reading = parseFloat(this.currentReading);

            if (isNaN(reading)) {
                this.currentReadingError = 'Must be a valid number';
                return;
            }

            if (reading < 0) {
                this.currentReadingError = 'Reading cannot be negative';
                return;
            }

            if (reading > 999999999.999) {
                this.currentReadingError = 'Reading cannot exceed 999,999,999.999';
                return;
            }

            // Check decimal places
            const decimalPlaces = (this.currentReading.toString().split('.')[1] || '').length;
            if (decimalPlaces > 3) {
                this.currentReadingError = 'Maximum 3 decimal places allowed';
                return;
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

        async submitForm() {
            // Final validation
            this.validateMeterNumber();
            this.validateCurrentReading();

            if (this.meterNumberError || this.currentReadingError) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix the errors before submitting'
                });
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('tank_id', this.selectedTankId);
                formData.append('meter_number', this.meterNumber.toUpperCase());
                formData.append('current_reading_liters', this.currentReading);
                formData.append('is_active', this.isActive ? '1' : '0');

                const response = await fetch('/meters', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const returnUrl = this.userRole === 'admin' && this.selectedStationId
                        ? `/meters?station_id=${this.selectedStationId}`
                        : '/meters';

                    Swal.fire({
                        icon: 'success',
                        title: 'Meter Created!',
                        text: 'The new meter has been successfully created.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        window.location.href = returnUrl;
                    }, 2000);
                } else {
                    const errorData = await response.text();
                    throw new Error('Failed to create meter');
                }

            } catch (error) {
                console.error('Submit error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Creation Failed',
                    text: 'Failed to create meter. Please try again.'
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>
@endsection
