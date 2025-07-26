@extends('layouts.app')

@section('title', 'Variance Management')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Variance Management</h1>
        <p class="text-sm text-gray-500 mt-1">Monitor, investigate, and resolve fuel inventory variances</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-amber-50 text-amber-700 border border-amber-200">
            {{ $summary_stats->open_alerts ?? 0 }} Open
        </div>
        <div class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-red-50 text-red-700 border border-red-200">
            {{ $summary_stats->critical_alerts ?? 0 }} Critical
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="varianceManager()" x-init="init()" class="space-y-6">

    <!-- Filter Controls Card -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Station</label>
                    <select x-model="filters.station_id" @change="loadData()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}" {{ $station->id == $station_id ? 'selected' : '' }}>
                                {{ $station->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">From Date</label>
                    <input type="date" x-model="filters.date_from" @change="loadData()"
                           value="{{ $date_from }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">To Date</label>
                    <input type="date" x-model="filters.date_to" @change="loadData()"
                           value="{{ $date_to }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Severity</label>
                    <select x-model="filters.severity" @change="loadData()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        <option value="">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Status</label>
                    <select x-model="filters.status" @change="loadData()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        <option value="">All Status</option>
                        <option value="open" {{ $status_filter == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="investigating">Investigating</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card with Tabs -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'alerts'"
                        :class="activeTab === 'alerts' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                    Active Alerts
                    <span x-show="summary.open_alerts > 0"
                          class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                          x-text="summary.open_alerts"></span>
                </button>

                <button @click="activeTab = 'trends'"
                        :class="activeTab === 'trends' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                    Variance Trends
                </button>

                <button @click="activeTab = 'investigation'"
                        :class="activeTab === 'investigation' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                    Investigation Log
                </button>

                <button @click="activeTab = 'analytics'"
                        :class="activeTab === 'analytics' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                    Tank Analysis
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">

            <!-- Active Alerts Tab -->
            <div x-show="activeTab === 'alerts'" class="space-y-6">

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                    <div class="w-4 h-4 bg-red-600 rounded-full"></div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-red-800">Critical Alerts</p>
                                <p class="text-2xl font-bold text-red-900" x-text="summary.critical_alerts || 0">{{ $summary_stats->critical_alerts ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-amber-100 rounded-md flex items-center justify-center">
                                    <div class="w-4 h-4 bg-amber-600 rounded-full"></div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-amber-800">High Alerts</p>
                                <p class="text-2xl font-bold text-amber-900" x-text="summary.high_alerts || 0">{{ $summary_stats->high_alerts ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <div class="w-4 h-4 bg-blue-600 rounded-full"></div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-blue-800">Investigating</p>
                                <p class="text-2xl font-bold text-blue-900" x-text="summary.investigating_alerts || 0">{{ $summary_stats->investigating_alerts ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <div class="w-4 h-4 bg-green-600 rounded-full"></div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-green-800">Resolved</p>
                                <p class="text-2xl font-bold text-green-900" x-text="summary.resolved_alerts || 0">{{ $summary_stats->resolved_alerts ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts Table -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert Details</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="alert in alerts" :key="alert.id">
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div :class="getSeverityClass(alert.severity)" class="w-2.5 h-2.5 rounded-full mt-2"></div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900" x-text="alert.title"></p>
                                                <p class="text-sm text-gray-500" x-text="alert.message.substring(0, 80) + '...'"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900" x-text="'Tank ' + alert.tank_number"></div>
                                        <div class="text-sm text-gray-500 capitalize" x-text="alert.fuel_type"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold" :class="alert.variance_percentage > 0 ? 'text-red-600' : 'text-blue-600'">
                                            <span x-text="(alert.variance_percentage || 0).toFixed(2)"></span>%
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <span x-text="Math.abs(alert.variance_magnitude || 0).toFixed(1)"></span>L
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(alert.notification_date)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(alert.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            <span x-text="alert.status.charAt(0).toUpperCase() + alert.status.slice(1)"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button @click="investigateAlert(alert)"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors duration-200">
                                            Investigate
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="alerts.length === 0" class="text-center py-12">
                        <div class="w-12 h-12 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                        </div>
                        <p class="text-gray-500 text-sm">No variance alerts found for the selected criteria</p>
                    </div>
                </div>
            </div>

            <!-- Variance Trends Tab -->
            <div x-show="activeTab === 'trends'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Chart Container -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Variance Trends</h3>
                        <div id="varianceChart" class="h-64"></div>
                    </div>

                    <!-- Fuel Type Summary -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Variance by Fuel Type</h3>
                        <div class="space-y-3">
                            <template x-for="fuelType in getFuelTypeBreakdown()" :key="fuelType.type">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                    <div class="flex items-center space-x-3">
                                        <div :class="getFuelTypeColor(fuelType.type)" class="w-3 h-3 rounded-full"></div>
                                        <span class="text-sm font-medium text-gray-900 capitalize" x-text="fuelType.type"></span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900" x-text="fuelType.avg_variance.toFixed(2) + '%'"></div>
                                        <div class="text-xs text-gray-500" x-text="fuelType.count + ' alerts'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Trends Table -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance %</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume Variance</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispensed</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="trend in trends" :key="trend.reconciliation_date + trend.tank_number">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(trend.reconciliation_date)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900" x-text="'Tank ' + trend.tank_number"></div>
                                        <div class="text-sm text-gray-500 capitalize" x-text="trend.fuel_type"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="trend.variance_percentage > 0 ? 'text-red-600' : 'text-blue-600'"
                                              class="text-sm font-semibold" x-text="(trend.variance_percentage || 0).toFixed(2) + '%'"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="trend.volume_variance_liters > 0 ? 'text-red-600' : 'text-blue-600'"
                                              class="text-sm font-semibold" x-text="(trend.volume_variance_liters || 0).toFixed(1) + 'L'"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="(trend.total_dispensed_liters || 0).toFixed(1) + 'L'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Investigation Log Tab -->
            <div x-show="activeTab === 'investigation'" class="text-center py-16">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <div class="w-8 h-8 bg-gray-400 rounded-sm"></div>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Investigation Log</h3>
                <p class="text-gray-500 mb-6 max-w-sm mx-auto">View and manage detailed investigation history in the dedicated interface</p>
                <a href="{{ route('variance.investigation-log') }}?station_id={{ $station_id }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors duration-200">
                    Open Investigation Log
                </a>
            </div>

            <!-- Tank Analysis Tab -->
            <div x-show="activeTab === 'analytics'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <template x-for="tank in tankAnalysis" :key="tank.tank_id">
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900" x-text="'Tank ' + tank.tank_number"></h4>
                                    <p class="text-sm text-gray-500 capitalize" x-text="tank.fuel_type"></p>
                                </div>
                                <div :class="getAlertBadgeClass(tank.critical_alerts)"
                                     class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <span x-text="tank.alert_count + ' alerts'"></span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Avg Variance:</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="(tank.avg_variance_percentage || 0).toFixed(2) + '%'"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Max Variance:</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="(tank.max_variance_percentage || 0).toFixed(2) + '%'"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Total Volume Variance:</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="(tank.total_variance_liters || 0).toFixed(1) + 'L'"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Resolution Rate:</span>
                                    <span class="text-sm font-medium text-green-600" x-text="tank.alert_count > 0 ? Math.round((tank.resolved_alerts / tank.alert_count) * 100) + '%' : '0%'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Modal -->
    <div x-show="showInvestigationModal" x-transition.opacity
         class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeInvestigationModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                <template x-if="selectedAlert">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900" id="modal-title">Investigate Variance Alert</h3>
                            <button @click="closeInvestigationModal()" type="button"
                                    class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Alert Details -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</label>
                                    <p class="mt-1 text-sm font-medium text-gray-900" x-text="'Tank ' + selectedAlert.tank_number + ' (' + selectedAlert.fuel_type + ')'"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date</label>
                                    <p class="mt-1 text-sm font-medium text-gray-900" x-text="formatDate(selectedAlert.notification_date)"></p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</label>
                                    <p class="mt-1 text-sm font-medium text-gray-900">
                                        <span x-text="(selectedAlert.variance_percentage || 0).toFixed(2) + '% (' + Math.abs(selectedAlert.variance_magnitude || 0).toFixed(1) + 'L)'"></span>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</label>
                                    <span :class="getSeverityBadgeClass(selectedAlert.severity)"
                                          class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        <span x-text="selectedAlert.severity.charAt(0).toUpperCase() + selectedAlert.severity.slice(1)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Investigation Form -->
                        <form @submit.prevent="submitInvestigation()" class="space-y-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-900">Investigation Status</label>
                                <select x-model="investigationForm.status" id="status" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                                    <option value="investigating">Mark as Investigating</option>
                                    <option value="resolved">Mark as Resolved</option>
                                    <option value="open">Reopen</option>
                                </select>
                            </div>

                            <div x-show="investigationForm.status === 'resolved'">
                                <label for="resolution_notes" class="block text-sm font-medium text-gray-900">Resolution Notes *</label>
                                <textarea x-model="investigationForm.resolution_notes" id="resolution_notes"
                                          :required="investigationForm.status === 'resolved'"
                                          rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm"
                                          placeholder="Describe the investigation findings and resolution..."></textarea>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" @click="closeInvestigationModal()"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="loading"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!loading">Update Status</span>
                                    <span x-show="loading">Updating...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function varianceManager() {
    return {
        activeTab: 'alerts',
        loading: false,
        showInvestigationModal: false,
        selectedAlert: null,

        // Data from controller
        alerts: @json($active_alerts),
        trends: @json($variance_trends),
        tankAnalysis: @json($tank_analysis),
        summary: @json($summary_stats),

        // Filters
        filters: {
            station_id: '{{ $station_id }}',
            date_from: '{{ $date_from }}',
            date_to: '{{ $date_to }}',
            severity: '{{ $severity_filter }}',
            status: '{{ $status_filter }}'
        },

        // Investigation form
        investigationForm: {
            status: '',
            resolution_notes: ''
        },

        init() {
            this.initChart();
        },

        async loadData() {
            this.loading = true;
            try {
                const response = await fetch(`{{ route('variance.index') }}?${new URLSearchParams(this.filters)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Failed to load data');

                const data = await response.json();
                if (data.success) {
                    this.alerts = data.data.active_alerts;
                    this.trends = data.data.variance_trends;
                    this.tankAnalysis = data.data.tank_analysis;
                    this.summary = data.data.summary_stats;
                    this.initChart();
                }
            } catch (error) {
                console.error('Error loading data:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load variance data',
                    icon: 'error',
                    confirmButtonColor: '#1f2937'
                });
            } finally {
                this.loading = false;
            }
        },

        investigateAlert(alert) {
            this.selectedAlert = alert;
            this.investigationForm = {
                status: alert.status === 'open' ? 'investigating' : alert.status,
                resolution_notes: ''
            };
            this.showInvestigationModal = true;
        },

        closeInvestigationModal() {
            this.showInvestigationModal = false;
            this.selectedAlert = null;
        },

        async submitInvestigation() {
            this.loading = true;
            try {
                const response = await fetch(`{{ url('/variance') }}/${this.selectedAlert.id}/investigation`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        ...this.investigationForm,
                        station_id: this.filters.station_id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        title: 'Success',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#1f2937'
                    });
                    this.closeInvestigationModal();
                    this.loadData();
                } else {
                    throw new Error(data.error || 'Failed to update investigation');
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#1f2937'
                });
            } finally {
                this.loading = false;
            }
        },

        initChart() {
            if (typeof echarts === 'undefined') return;

            const chartDom = document.getElementById('varianceChart');
            if (!chartDom) return;

            const myChart = echarts.init(chartDom);

            const dates = [...new Set(this.trends.map(t => t.reconciliation_date))].sort();
            const series = [];

            const fuelTypes = [...new Set(this.trends.map(t => t.fuel_type))];
            const colors = ['#ef4444', '#3b82f6', '#10b981'];

            fuelTypes.forEach((fuelType, index) => {
                const data = dates.map(date => {
                    const trend = this.trends.find(t => t.reconciliation_date === date && t.fuel_type === fuelType);
                    return trend ? Math.abs(trend.variance_percentage) : 0;
                });

                series.push({
                    name: fuelType.charAt(0).toUpperCase() + fuelType.slice(1),
                    type: 'line',
                    data: data,
                    smooth: true,
                    lineStyle: { color: colors[index] },
                    itemStyle: { color: colors[index] }
                });
            });

            myChart.setOption({
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'white',
                    borderColor: '#e5e7eb',
                    textStyle: { color: '#374151' }
                },
                legend: {
                    data: fuelTypes.map(f => f.charAt(0).toUpperCase() + f.slice(1)),
                    textStyle: { color: '#6b7280' }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    data: dates,
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    axisTick: { lineStyle: { color: '#e5e7eb' } },
                    axisLabel: { color: '#6b7280' }
                },
                yAxis: {
                    type: 'value',
                    name: 'Variance %',
                    nameTextStyle: { color: '#6b7280' },
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    axisTick: { lineStyle: { color: '#e5e7eb' } },
                    axisLabel: { color: '#6b7280' },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: series
            });
        },

        // Helper methods
        getSeverityClass(severity) {
            const classes = {
                critical: 'bg-red-500',
                high: 'bg-amber-500',
                medium: 'bg-yellow-500',
                low: 'bg-blue-500'
            };
            return classes[severity] || 'bg-gray-500';
        },

        getSeverityBadgeClass(severity) {
            const classes = {
                critical: 'bg-red-100 text-red-800',
                high: 'bg-amber-100 text-amber-800',
                medium: 'bg-yellow-100 text-yellow-800',
                low: 'bg-blue-100 text-blue-800'
            };
            return classes[severity] || 'bg-gray-100 text-gray-800';
        },

        getStatusClass(status) {
            const classes = {
                open: 'bg-red-100 text-red-800',
                investigating: 'bg-blue-100 text-blue-800',
                resolved: 'bg-green-100 text-green-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getAlertBadgeClass(criticalCount) {
            if (criticalCount > 0) return 'bg-red-100 text-red-800';
            return 'bg-green-100 text-green-800';
        },

        getFuelTypeColor(fuelType) {
            const colors = {
                petrol: 'bg-red-500',
                diesel: 'bg-blue-500',
                kerosene: 'bg-green-500'
            };
            return colors[fuelType] || 'bg-gray-500';
        },

        getFuelTypeBreakdown() {
            const breakdown = {};
            this.trends.forEach(trend => {
                if (!breakdown[trend.fuel_type]) {
                    breakdown[trend.fuel_type] = { type: trend.fuel_type, total: 0, count: 0 };
                }
                breakdown[trend.fuel_type].total += Math.abs(trend.variance_percentage || 0);
                breakdown[trend.fuel_type].count++;
            });

            return Object.values(breakdown).map(item => ({
                ...item,
                avg_variance: item.count > 0 ? item.total / item.count : 0
            }));
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endsection
