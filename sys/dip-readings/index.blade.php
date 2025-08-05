@extends('layouts.app')

@section('title', 'Daily Dip Readings')

@section('content')
<div x-data="dipReadingsApp()" x-init="init()" class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <!-- Premium ShadCN Header -->
    <div class="border-b bg-white/95 backdrop-blur-sm supports-[backdrop-filter]:bg-white/80 dark:bg-slate-900/95 dark:border-slate-800 shadow-sm">
        <div class="container mx-auto px-6 flex h-20 items-center justify-between">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Daily Dip Readings</h1>
                </div>
                <div class="flex items-center gap-4 text-sm text-slate-600 dark:text-slate-400">
                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-slate-700 dark:text-slate-300">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="formatDisplayDate(readingDate)"></span>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 text-emerald-700 dark:text-emerald-400">
                        <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="font-medium">0.001L Precision</span>
                    </div>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-4">
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 shadow-sm">
                    <div class="text-center">
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wider">Active Stations</div>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white" x-text="stations.length"></div>
                    </div>
                </div>
                <div x-show="loading" class="flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 px-4 py-3 text-blue-700 dark:text-blue-400">
                    <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-400 border-t-transparent"></div>
                    <span class="font-medium">Processing...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Enhanced Station Selection Card -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm mb-8">
            <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold leading-none tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8h5"/>
                            </svg>
                            Station & Date Selection
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Configure your reading parameters</p>
                    </div>
                    <div x-show="selectedStationId && tankReadings.length > 0" class="flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-900/20 px-3 py-2 text-green-700 dark:text-green-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm font-medium" x-text="`${tankReadings.length} Tanks Loaded`"></span>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 8h5"/>
                            </svg>
                            Station
                        </label>
                        <select x-model="selectedStationId" @change="loadTankReadings()"
                                class="flex h-11 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 dark:placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">Select Station</option>
                            <template x-for="station in stations" :key="station.id">
                                <option :value="station.id" x-text="`${station.name} - ${station.location}`"></option>
                            </template>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium leading-none text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2 2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Reading Date
                        </label>
                        <input type="date" x-model="readingDate" @change="selectedStationId && loadTankReadings()"
                               :max="maxDate"
                               class="flex h-11 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Tabs Interface -->
        <div x-show="selectedStationId" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <!-- Tab Navigation -->
            <div class="border-b border-slate-200 dark:border-slate-700 p-1">
                <nav class="flex space-x-1" role="tablist">
                    <button @click="activeTab = 'entry'"
                            :class="activeTab === 'entry' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Data Entry
                        <span x-show="pendingCount > 0" class="ml-1 inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-1 text-xs font-bold text-white" x-text="pendingCount"></span>
                    </button>
                    <button @click="activeTab = 'review'"
                            :class="activeTab === 'review' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Review & Summary
                    </button>
                    <button @click="activeTab = 'history'; loadHistoryData()"
                            :class="activeTab === 'history' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                            class="inline-flex items-center justify-center whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        History
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Data Entry Tab -->
                <div x-show="activeTab === 'entry'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <template x-if="tankReadings.length === 0 && !loading">
                        <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">No Tanks Available</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Select a station and date to view available tanks</p>
                        </div>
                    </template>

                    <div x-show="tankReadings.length > 0" class="space-y-4">
                        <template x-for="tank in tankReadings" :key="tank.tank_id">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 p-6 shadow-sm hover:shadow-md transition-all duration-200">
                                <!-- Tank Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-slate-900 dark:text-white" x-text="`Tank ${tank.tank_number}`"></h3>
                                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                                <span :class="getFuelTypeClass(tank.fuel_type)" class="px-2 py-0.5 text-xs font-medium rounded-full" x-text="tank.fuel_type.toUpperCase()"></span>
                                                <span x-text="`${formatNumber(tank.capacity_liters)}L capacity`"></span>
                                                <span x-text="`${formatNumber(tank.current_volume_liters)}L current`"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Indicators -->
                                    <div class="flex items-center gap-2">
                                        <div x-show="!tank.has_meter_readings" class="flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400 rounded-lg text-xs font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                            No Meter Readings
                                        </div>
                                        <div x-show="tank.existing_reading" class="flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400 rounded-lg text-xs font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Already Recorded
                                        </div>
                                    </div>
                                </div>

                                <!-- Reading Form -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Morning Dip (L) <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input type="number"
                                                   x-model="tank.morning_dip_liters"
                                                   @input="validateTankInput(tank)"
                                                   required step="0.001" min="0" :max="tank.capacity_liters"
                                                   :disabled="!tank.can_submit"
                                                   class="h-10 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                                            <div x-show="tank.suggested_morning_dip && !tank.morning_dip_liters" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <button @click="tank.morning_dip_liters = tank.suggested_morning_dip; validateTankInput(tank)"
                                                        type="button" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                                    Use: <span x-text="formatNumber(tank.suggested_morning_dip)"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <p x-show="tank.morning_error" class="text-xs text-red-500" x-text="tank.morning_error"></p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Evening Dip (L) <span class="text-red-500">*</span></label>
                                        <input type="number"
                                               x-model="tank.evening_dip_liters"
                                               @input="validateTankInput(tank)"
                                               required step="0.001" min="0" :max="tank.capacity_liters"
                                               :disabled="!tank.can_submit"
                                               class="h-10 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                                        <p x-show="tank.evening_error" class="text-xs text-red-500" x-text="tank.evening_error"></p>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Water Level (mm)</label>
                                        <input type="number"
                                               x-model="tank.water_level_mm"
                                               step="0.01" min="0" :disabled="!tank.can_submit"
                                               class="h-10 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Temperature (°C)</label>
                                        <input type="number"
                                               x-model="tank.temperature_celsius"
                                               step="0.01" min="-10" max="60" :disabled="!tank.can_submit"
                                               class="h-10 w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed">
                                    </div>

                                    <button @click="saveTankReading(tank)"
                                            :disabled="!canSaveTank(tank)"
                                            class="h-10 px-4 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all duration-200 shadow-sm hover:shadow-md">
                                        <svg x-show="!tank.saving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <div x-show="tank.saving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                        <span x-text="tank.saving ? 'Saving...' : (tank.existing_reading ? 'Update' : 'Save')"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Review Tab -->
                <div x-show="activeTab === 'review'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                            <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <h3 class="tracking-tight text-sm font-medium text-slate-600 dark:text-slate-400">Total Tanks</h3>
                                <svg class="h-4 w-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white" x-text="tankReadings.length || 0"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                            <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <h3 class="tracking-tight text-sm font-medium text-slate-600 dark:text-slate-400">Completed</h3>
                                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-green-600" x-text="completedCount"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                            <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <h3 class="tracking-tight text-sm font-medium text-slate-600 dark:text-slate-400">Pending</h3>
                                <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-amber-600" x-text="pendingCount"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
                            <div class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <h3 class="tracking-tight text-sm font-medium text-slate-600 dark:text-slate-400">Total Volume</h3>
                                <svg class="h-4 w-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">
                                <span x-text="formatNumber(totalCurrentVolume)"></span>L
                            </div>
                        </div>
                    </div>

                    <!-- Tank Status Table -->
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-6 py-4">
                            <h3 class="font-semibold leading-none tracking-tight text-slate-900 dark:text-white">Tank Status Overview</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                    <tr class="border-b border-slate-200 dark:border-slate-700">
                                        <th class="h-12 px-4 text-left align-middle font-medium text-slate-600 dark:text-slate-400">Tank</th>
                                        <th class="h-12 px-4 text-left align-middle font-medium text-slate-600 dark:text-slate-400">Fuel Type</th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Capacity</th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Current</th>
                                        <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Fill %</th>
                                        <th class="h-12 px-4 text-center align-middle font-medium text-slate-600 dark:text-slate-400">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="tank in tankReadings" :key="tank.tank_id">
                                        <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                            <td class="p-4 align-middle font-medium text-slate-900 dark:text-white" x-text="tank.tank_number"></td>
                                            <td class="p-4 align-middle">
                                                <span :class="getFuelTypeClass(tank.fuel_type)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" x-text="tank.fuel_type.toUpperCase()"></span>
                                            </td>
                                            <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="formatNumber(tank.capacity_liters) + 'L'"></td>
                                            <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="formatNumber(tank.current_volume_liters) + 'L'"></td>
                                            <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white">
                                                <span x-text="((tank.current_volume_liters / tank.capacity_liters) * 100).toFixed(1) + '%'"></span>
                                            </td>
                                            <td class="p-4 align-middle text-center">
                                                <span :class="tank.existing_reading ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'"
                                                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                                    <span x-text="tank.existing_reading ? 'Completed' : 'Pending'"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div x-show="activeTab === 'history'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold leading-none tracking-tight text-slate-900 dark:text-white">Tank History Analysis</h3>
                            <select x-model="selectedTankForHistory" @change="loadTankHistory()"
                                    class="flex h-10 w-[200px] rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Tank</option>
                                <template x-for="tank in tankReadings" :key="tank.tank_id">
                                    <option :value="tank.tank_id" x-text="`Tank ${tank.tank_number} - ${tank.fuel_type.toUpperCase()}`"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="!selectedTankForHistory" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-4 text-lg font-medium text-slate-900 dark:text-white">Select a tank to view history</p>
                        </div>

                        <div x-show="selectedTankForHistory && loadingHistory" class="flex justify-center py-16">
                            <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 dark:border-slate-700 border-t-blue-500"></div>
                        </div>

                        <div x-show="selectedTankForHistory && !loadingHistory && historyData.length > 0" x-transition>
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-6 py-4">
                                    <h4 class="font-semibold leading-none tracking-tight text-slate-900 dark:text-white">30-Day Reading History</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="h-12 px-4 text-left align-middle font-medium text-slate-600 dark:text-slate-400">Date</th>
                                                <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Morning Dip</th>
                                                <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Evening Dip</th>
                                                <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Water Level</th>
                                                <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Temperature</th>
                                                <th class="h-12 px-4 text-right align-middle font-medium text-slate-600 dark:text-slate-400">Variance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="record in historyData" :key="record.id">
                                                <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                                    <td class="p-4 align-middle font-medium text-slate-900 dark:text-white" x-text="formatDisplayDate(record.reading_date)"></td>
                                                    <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="record.morning_dip_liters > 0 ? formatNumber(record.morning_dip_liters) + 'L' : '-'"></td>
                                                    <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="record.evening_dip_liters > 0 ? formatNumber(record.evening_dip_liters) + 'L' : '-'"></td>
                                                    <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="record.water_level_mm ? record.water_level_mm + 'mm' : '-'"></td>
                                                    <td class="p-4 align-middle text-right font-medium text-slate-900 dark:text-white" x-text="record.temperature_celsius ? record.temperature_celsius + '°C' : '-'"></td>
                                                    <td class="p-4 align-middle text-right font-medium">
                                                        <span x-show="record.variance_percentage"
                                                              :class="Math.abs(record.variance_percentage) > 2 ? 'text-red-600 font-bold' : 'text-slate-600 dark:text-slate-400'"
                                                              x-text="record.variance_percentage ? record.variance_percentage.toFixed(2) + '%' : '-'">
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div x-show="selectedTankForHistory && !loadingHistory && historyData.length === 0" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 py-16 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-4 text-lg font-medium text-slate-900 dark:text-white">No history data available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dipReadingsApp() {
    return {
        // Controller data - EXACT match with DipReadingsController->index()
        stations: {!! json_encode($stations ?? []) !!},
        selectedStationId: {!! json_encode($selectedStationId ?? '') !!},
        readingDate: {!! json_encode($readingDate ?? date('Y-m-d')) !!},
        user: {!! json_encode($user ?? []) !!},

        // UI state
        activeTab: 'entry',
        loading: false,
        loadingHistory: false,

        // Data from controller methods - EXACT response structures
        tankReadings: [],
        historyData: [],
        selectedTankForHistory: '',

        // Computed date constraints
        maxDate: new Date().toISOString().split('T')[0],

        init() {
            // Auto-load if station pre-selected (non-admin users)
            if (this.selectedStationId) {
                this.loadTankReadings();
            }
        },

        // Controller method: getTankReadings - EXACT match
        async loadTankReadings() {
            if (!this.selectedStationId || !this.readingDate) return;

            this.loading = true;
            this.tankReadings = [];

            try {
                const response = await fetch('{{ route("dip-readings.tank-readings") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        station_id: this.selectedStationId,
                        reading_date: this.readingDate
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Use EXACT controller response structure from getTankReadings()
                    this.tankReadings = data.tank_readings.map(tank => ({
                        ...tank,
                        // Initialize form fields using existing_reading data structure
                        morning_dip_liters: tank.existing_reading?.morning_dip_liters || '',
                        evening_dip_liters: tank.existing_reading?.evening_dip_liters || '',
                        water_level_mm: tank.existing_reading?.water_level_mm || '',
                        temperature_celsius: tank.existing_reading?.temperature_celsius || '',
                        // UI state
                        morning_error: '',
                        evening_error: '',
                        saving: false
                    }));
                } else {
                    throw new Error(data.error || 'Failed to load tank readings');
                }
            } catch (error) {
                console.error('Tank readings error:', error);
                Swal.fire('Error', error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        // Controller method: store - EXACT match with FuelERP service integration
        async saveTankReading(tank) {
            if (!this.canSaveTank(tank)) return;

            tank.saving = true;

            try {
                const response = await fetch('{{ route("dip-readings.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        // EXACT daily_readings schema fields from controller validation
                        tank_id: tank.tank_id,
                        reading_date: this.readingDate,
                        morning_dip_liters: parseFloat(tank.morning_dip_liters),
                        evening_dip_liters: parseFloat(tank.evening_dip_liters),
                        water_level_mm: tank.water_level_mm ? parseFloat(tank.water_level_mm) : null,
                        temperature_celsius: tank.temperature_celsius ? parseFloat(tank.temperature_celsius) : null
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 3000,
                        showConfirmButton: false
                    });

                    // Update tank status to reflect saved state
                    tank.existing_reading = {
                        morning_dip_liters: parseFloat(tank.morning_dip_liters),
                        evening_dip_liters: parseFloat(tank.evening_dip_liters),
                        water_level_mm: tank.water_level_mm ? parseFloat(tank.water_level_mm) : null,
                        temperature_celsius: tank.temperature_celsius ? parseFloat(tank.temperature_celsius) : null
                    };

                    // Log service result for debugging (FuelERP automation triggered)
                    if (data.service_result) {
                        console.log('FuelERP Service Result:', data.service_result);
                    }
                } else {
                    throw new Error(data.error || 'Failed to save reading');
                }
            } catch (error) {
                console.error('Save reading error:', error);
                Swal.fire('Error', error.message, 'error');
            } finally {
                tank.saving = false;
            }
        },

        // Controller method: getReadingHistory - EXACT match
        async loadTankHistory() {
            if (!this.selectedTankForHistory) {
                this.historyData = [];
                return;
            }

            this.loadingHistory = true;

            try {
                const response = await fetch('{{ route("dip-readings.history") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tank_id: this.selectedTankForHistory,
                        days: 30
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Use EXACT controller response structure from getReadingHistory()
                    this.historyData = data.readings || [];
                } else {
                    throw new Error(data.error || 'Failed to load tank history');
                }
            } catch (error) {
                console.error('Tank history error:', error);
                Swal.fire('Error', error.message, 'error');
                this.historyData = [];
            } finally {
                this.loadingHistory = false;
            }
        },

        // Load history when tab is activated
        loadHistoryData() {
            if (this.selectedTankForHistory) {
                this.loadTankHistory();
            }
        },

        // Validation matching controller constraints exactly
        validateTankInput(tank) {
            tank.morning_error = '';
            tank.evening_error = '';

            const morning = parseFloat(tank.morning_dip_liters);
            const evening = parseFloat(tank.evening_dip_liters);

            // Database constraint validation matching chk_reading_dips
            if (tank.morning_dip_liters && morning < 0) {
                tank.morning_error = 'Morning dip cannot be negative';
            }

            if (tank.evening_dip_liters && evening < 0) {
                tank.evening_error = 'Evening dip cannot be negative';
            }

            // Tank capacity validation matching controller logic
            if (tank.morning_dip_liters && morning > tank.capacity_liters) {
                tank.morning_error = 'Exceeds tank capacity';
            }

            if (tank.evening_dip_liters && evening > tank.capacity_liters) {
                tank.evening_error = 'Exceeds tank capacity';
            }
        },

        // Check if tank can be saved - matches controller validation exactly
        canSaveTank(tank) {
            return tank.can_submit &&
                   tank.morning_dip_liters &&
                   tank.evening_dip_liters &&
                   !tank.morning_error &&
                   !tank.evening_error &&
                   !tank.saving;
        },

        // Computed properties for UI reactivity - based on controller data structures
        get pendingCount() {
            return this.tankReadings.filter(t => !t.existing_reading).length;
        },

        get completedCount() {
            return this.tankReadings.filter(t => t.existing_reading).length;
        },

        get totalCurrentVolume() {
            return this.tankReadings.reduce((sum, tank) => sum + parseFloat(tank.current_volume_liters || 0), 0);
        },

        // Utility functions
        getFuelTypeClass(fuelType) {
            const classes = {
                'petrol': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300',
                'diesel': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                'kerosene': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'
            };
            return classes[fuelType] || 'bg-slate-100 text-slate-800 dark:bg-slate-900/20 dark:text-slate-300';
        },

        formatNumber(num) {
            return parseFloat(num || 0).toLocaleString('en-US', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3
            });
        },

        formatDisplayDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>

@endsection
