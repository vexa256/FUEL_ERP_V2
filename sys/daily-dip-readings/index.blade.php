@extends('layouts.app')

@section('title', 'Daily Dip Readings')

@section('content')
<div id="dailyDipApp" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Daily Morning Dip Readings</h1>
            <p class="text-gray-600 mt-1">Record tank dip readings for {{ $today }}</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fas fa-calendar-day"></i>
                <span>{{ Carbon\Carbon::parse($today)->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Station Selector -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Station Selection</h2>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fas fa-building"></i>
                <span>{{ count($stations) }} Station{{ count($stations) > 1 ? 's' : '' }} Available</span>
            </div>
        </div>
        <select id="stationSelector" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @foreach($stations as $station)
                <option value="{{ $station->id }}" {{ $station->id == $selectedStation ? 'selected' : '' }}>
                    {{ $station->name }} - {{ $station->location }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button id="morningTab"
                        class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-sun"></i>
                    <span>Morning Readings</span>
                    <span id="morningPendingBadge" class="badge hidden bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full"></span>
                </button>

                <button id="reviewTab"
                        class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Comprehensive Review</span>
                    <span id="reviewCountBadge" class="badge hidden bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"></span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Morning Readings Tab -->
            <div id="morningTabContent" class="tab-content space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Morning Readings</h3>
                    <div class="text-sm text-gray-500">
                        <span id="morningPendingCount">0</span> pending
                    </div>
                </div>

                <div id="morningReadingsList" class="space-y-4">
                    <!-- Morning readings will be populated by JavaScript -->
                </div>

                <div id="morningCompletedMessage" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-2"></i>
                    <p>All morning readings completed</p>
                </div>
            </div>

            <!-- Review Tab -->
            <div id="reviewTabContent" class="tab-content hidden space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Comprehensive Daily Review</h3>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-500">
                            <span id="reviewReadingCount">0</span> total readings
                        </div>
                        <div class="text-sm text-gray-500">
                            <span id="reviewCompletionRate">0%</span> completion rate
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-tachometer-alt text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-blue-900">Total Tanks</p>
                                <p class="text-xl font-bold text-blue-600" id="totalTanksCount">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-green-900">Morning Complete</p>
                                <p class="text-xl font-bold text-green-600" id="morningCompleteCount">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-orange-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-orange-900">Pending Morning</p>
                                <p class="text-xl font-bold text-orange-600" id="morningPendingCount">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-bar text-purple-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-900">Total Volume</p>
                                <p class="text-xl font-bold text-purple-600" id="totalVolumeReading">0L</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fuel Type Breakdown -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Fuel Type Breakdown</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="fuelTypeBreakdown">
                        <!-- Fuel type cards will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Tank Status Overview -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Tank Status Overview</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Tank</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Fuel Type</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Capacity</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Morning Reading</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Fill %</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-700">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody id="tankStatusTable">
                                <!-- Tank rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reading History for Today -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Reading Details</h4>
                    <div id="reviewReadingsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Reading cards will be populated by JavaScript -->
                    </div>

                    <div id="reviewEmptyMessage" class="hidden text-center py-8 text-gray-500">
                        <i class="fas fa-info-circle text-gray-400 text-4xl mb-2"></i>
                        <p class="text-lg font-medium">No readings recorded yet</p>
                        <p class="text-sm mt-1">Start by recording morning readings for your tanks</p>
                        <button id="goToMorningFromEmpty" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Record Morning Readings
                        </button>
                    </div>
                </div>

                <!-- Actions Panel -->
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h4>
                    <div class="flex flex-wrap gap-3">
                        <button id="refreshDataBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh Data</span>
                        </button>
                        <button id="exportDataBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-download"></i>
                            <span>Export Today's Data</span>
                        </button>
                        <button id="printReviewBtn" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-print"></i>
                            <span>Print Review</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg flex items-center gap-3">
            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
            <span class="text-gray-700">Processing...</span>
        </div>
    </div>
</div>

<!-- Hidden Data for JavaScript -->
<script type="application/json" id="appData">
{
    "stations": @json($stations),
    "selectedStation": {{ $selectedStation }},
    "tanks": @json($tanks),
    "readings": @json($readings),
    "today": "{{ $today }}",
    "routes": {
        "storeMorning": "{{ route('daily-dip-readings.store-morning') }}",
        "storeEvening": "{{ route('daily-dip-readings.store-evening') }}",
        "index": "{{ route('daily-dip-readings.index') }}"
    },
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<script>
// Daily Dip Readings Application
class DailyDipReadingsApp {
    constructor() {
        try {
            // Parse application data
            const appDataElement = document.getElementById('appData');
            if (!appDataElement) {
                throw new Error('Application data not found');
            }

            this.appData = JSON.parse(appDataElement.textContent);
            this.activeTab = 'morning';
            this.loading = false;
            this.morningForm = {};
            this.eveningForm = {};

            // Initialize application
            this.init();
        } catch (error) {
            console.error('Failed to initialize DailyDipReadingsApp:', error);
            this.showError('Failed to initialize application: ' + error.message);
        }
    }

    init() {
        try {
            this.bindEvents();
            this.updateUI();
        } catch (error) {
            console.error('Failed to initialize UI:', error);
            this.showError('Failed to initialize UI: ' + error.message);
        }
    }

    bindEvents() {
        try {
            // Station selector
            const stationSelector = document.getElementById('stationSelector');
            if (stationSelector) {
                stationSelector.addEventListener('change', (e) => {
                    this.changeStation(e.target.value);
                });
            }

            // Tab buttons
            document.getElementById('morningTab')?.addEventListener('click', () => this.switchTab('morning'));
            document.getElementById('reviewTab')?.addEventListener('click', () => this.switchTab('review'));

            // Action buttons
            document.getElementById('goToMorningFromEmpty')?.addEventListener('click', () => this.switchTab('morning'));
            document.getElementById('refreshDataBtn')?.addEventListener('click', () => this.refreshData());
            document.getElementById('exportDataBtn')?.addEventListener('click', () => this.exportData());
            document.getElementById('printReviewBtn')?.addEventListener('click', () => this.printReview());

        } catch (error) {
            console.error('Failed to bind events:', error);
            this.showError('Failed to bind events: ' + error.message);
        }
    }

    switchTab(tabName) {
        try {
            this.activeTab = tabName;
            this.updateTabUI();
            this.updateTabContent();
        } catch (error) {
            console.error('Failed to switch tab:', error);
            this.showError('Failed to switch tab: ' + error.message);
        }
    }

    updateTabUI() {
        try {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            const activeTabButton = document.getElementById(this.activeTab + 'Tab');
            if (activeTabButton) {
                activeTabButton.classList.add('active', 'border-blue-500', 'text-blue-600');
                activeTabButton.classList.remove('border-transparent', 'text-gray-500');
            }

            // Update tab content visibility
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            const activeTabContent = document.getElementById(this.activeTab + 'TabContent');
            if (activeTabContent) {
                activeTabContent.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Failed to update tab UI:', error);
            this.showError('Failed to update tab UI: ' + error.message);
        }
    }

    updateTabContent() {
        try {
            switch (this.activeTab) {
                case 'morning':
                    this.updateMorningContent();
                    break;
                case 'review':
                    this.updateReviewContent();
                    break;
            }
        } catch (error) {
            console.error('Failed to update tab content:', error);
            this.showError('Failed to update tab content: ' + error.message);
        }
    }

    updateMorningContent() {
        try {
            const morningPendingTanks = this.getMorningPendingTanks();
            const listContainer = document.getElementById('morningReadingsList');
            const completedMessage = document.getElementById('morningCompletedMessage');
            const pendingCount = document.getElementById('morningPendingCount');
            const pendingBadge = document.getElementById('morningPendingBadge');

            if (pendingCount) pendingCount.textContent = morningPendingTanks.length;

            if (pendingBadge) {
                if (morningPendingTanks.length > 0) {
                    pendingBadge.textContent = morningPendingTanks.length;
                    pendingBadge.classList.remove('hidden');
                } else {
                    pendingBadge.classList.add('hidden');
                }
            }

            if (listContainer) {
                if (morningPendingTanks.length > 0) {
                    listContainer.innerHTML = '';
                    morningPendingTanks.forEach(tank => {
                        listContainer.appendChild(this.createMorningReadingForm(tank));
                    });
                    listContainer.classList.remove('hidden');
                    if (completedMessage) completedMessage.classList.add('hidden');
                } else {
                    listContainer.classList.add('hidden');
                    if (completedMessage) completedMessage.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Failed to update morning content:', error);
            this.showError('Failed to update morning content: ' + error.message);
        }
    }

    updateEveningContent() {
        // Removed - evening readings handled by another controller
        return;
    }

    updateReviewContent() {
        try {
            // Update review badge
            const reviewCountBadge = document.getElementById('reviewCountBadge');
            if (reviewCountBadge) {
                if (this.appData.readings.length > 0) {
                    reviewCountBadge.textContent = this.appData.readings.length;
                    reviewCountBadge.classList.remove('hidden');
                } else {
                    reviewCountBadge.classList.add('hidden');
                }
            }

            // Calculate statistics
            const totalTanks = this.appData.tanks.length;
            const morningCompleted = this.appData.readings.filter(r => r.morning_dip_liters > 0).length;
            const morningPending = totalTanks - morningCompleted;
            const completionRate = totalTanks > 0 ? Math.round((morningCompleted / totalTanks) * 100) : 0;
            const totalVolume = this.appData.readings
                .filter(r => r.morning_dip_liters > 0)
                .reduce((sum, r) => sum + parseFloat(r.morning_dip_liters), 0);

            // Update summary statistics
            document.getElementById('totalTanksCount').textContent = totalTanks;
            document.getElementById('morningCompleteCount').textContent = morningCompleted;
            document.getElementById('morningPendingCount').textContent = morningPending;
            document.getElementById('reviewReadingCount').textContent = this.appData.readings.length;
            document.getElementById('reviewCompletionRate').textContent = completionRate + '%';
            document.getElementById('totalVolumeReading').textContent = totalVolume.toLocaleString() + 'L';

            // Update fuel type breakdown
            this.updateFuelTypeBreakdown();

            // Update tank status table
            this.updateTankStatusTable();

            // Update reading cards
            const listContainer = document.getElementById('reviewReadingsList');
            const emptyMessage = document.getElementById('reviewEmptyMessage');

            if (listContainer) {
                if (this.appData.readings.length > 0) {
                    listContainer.innerHTML = '';
                    this.appData.readings.forEach(reading => {
                        listContainer.appendChild(this.createReviewCard(reading));
                    });
                    listContainer.classList.remove('hidden');
                    if (emptyMessage) emptyMessage.classList.add('hidden');
                } else {
                    listContainer.classList.add('hidden');
                    if (emptyMessage) emptyMessage.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Failed to update review content:', error);
            this.showError('Failed to update review content: ' + error.message);
        }
    }

    updateFuelTypeBreakdown() {
        try {
            const container = document.getElementById('fuelTypeBreakdown');
            if (!container) return;

            const fuelTypes = ['petrol', 'diesel', 'kerosene'];
            container.innerHTML = '';

            fuelTypes.forEach(fuelType => {
                const tanks = this.appData.tanks.filter(t => t.fuel_type === fuelType);
                const readings = this.appData.readings.filter(r => r.fuel_type === fuelType && r.morning_dip_liters > 0);
                const totalVolume = readings.reduce((sum, r) => sum + parseFloat(r.morning_dip_liters), 0);
                const totalCapacity = tanks.reduce((sum, t) => sum + parseFloat(t.capacity_liters), 0);

                const fuelTypeClass = this.getFuelTypeClass(fuelType);
                const bgClass = fuelType === 'petrol' ? 'bg-green-50 border-green-200' :
                              fuelType === 'diesel' ? 'bg-blue-50 border-blue-200' :
                              'bg-purple-50 border-purple-200';

                const card = document.createElement('div');
                card.className = `${bgClass} rounded-lg p-4 border`;
                card.innerHTML = `
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="font-semibold ${fuelTypeClass}">${fuelType.toUpperCase()}</h5>
                        <span class="text-sm text-gray-600">${tanks.length} tank${tanks.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Readings:</span>
                            <span class="font-medium">${readings.length}/${tanks.length}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Volume:</span>
                            <span class="font-medium">${totalVolume.toLocaleString()}L</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Capacity:</span>
                            <span class="font-medium">${totalCapacity.toLocaleString()}L</span>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        } catch (error) {
            console.error('Failed to update fuel type breakdown:', error);
        }
    }

    updateTankStatusTable() {
        try {
            const tbody = document.getElementById('tankStatusTable');
            if (!tbody) return;

            tbody.innerHTML = '';

            this.appData.tanks.forEach(tank => {
                const reading = this.appData.readings.find(r => r.tank_id == tank.id);
                const morningReading = reading?.morning_dip_liters || 0;
                const fillPercentage = morningReading > 0 ? ((morningReading / parseFloat(tank.capacity_liters)) * 100).toFixed(1) : '0.0';
                const fuelTypeClass = this.getFuelTypeClass(tank.fuel_type);

                let status, statusClass;
                if (!reading || morningReading == 0) {
                    status = 'Pending';
                    statusClass = 'bg-orange-100 text-orange-800';
                } else {
                    status = 'Recorded';
                    statusClass = 'bg-green-100 text-green-800';
                }

                const row = document.createElement('tr');
                row.className = 'border-b border-gray-100 hover:bg-gray-50';
                row.innerHTML = `
                    <td class="py-3 px-4 font-medium text-gray-900">Tank ${tank.tank_number}</td>
                    <td class="py-3 px-4">
                        <span class="${fuelTypeClass} font-medium">${tank.fuel_type.toUpperCase()}</span>
                    </td>
                    <td class="py-3 px-4 text-gray-700">${parseFloat(tank.capacity_liters).toLocaleString()}L</td>
                    <td class="py-3 px-4 text-gray-700">
                        ${morningReading > 0 ? parseFloat(morningReading).toLocaleString() + 'L' : '-'}
                    </td>
                    <td class="py-3 px-4 text-gray-700">${fillPercentage}%</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                            ${status}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        ${reading?.first_name ? reading.first_name + ' ' + reading.last_name : '-'}
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (error) {
            console.error('Failed to update tank status table:', error);
        }
    }

    createMorningReadingForm(tank) {
        try {
            const div = document.createElement('div');
            div.className = 'bg-gray-50 rounded-lg p-4 border border-gray-200';

            const fuelTypeClass = this.getFuelTypeClass(tank.fuel_type);

            div.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-gray-900">Tank ${tank.tank_number}</h4>
                        <div class="flex items-center gap-4 text-sm text-gray-600 mt-1">
                            <span class="${fuelTypeClass}">${tank.fuel_type.toUpperCase()}</span>
                            <span>Capacity: ${parseFloat(tank.capacity_liters).toLocaleString()}L</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Morning Dip (Liters) *</label>
                        <input type="number" step="0.001" max="${tank.capacity_liters}" min="0"
                               id="morning_dip_${tank.id}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Water Level (mm)</label>
                        <input type="number" step="0.01" min="0" max="99999.99"
                               id="water_level_${tank.id}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button onclick="app.submitMorningReading(${tank.id})"
                            id="save_morning_${tank.id}"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        <span>Save Reading</span>
                    </button>
                </div>
            `;

            return div;
        } catch (error) {
            console.error('Failed to create morning reading form:', error);
            this.showError('Failed to create morning reading form: ' + error.message);
            return document.createElement('div');
        }
    }

    createEveningReadingForm(reading) {
        // Removed - evening readings handled by another controller
        return document.createElement('div');
    }

    createReviewCard(reading) {
        try {
            const div = document.createElement('div');
            div.className = 'bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow';

            const fuelTypeClass = this.getFuelTypeClass(reading.fuel_type);
            const tank = this.appData.tanks.find(t => t.id == reading.tank_id);
            const fillPercentage = reading.morning_dip_liters > 0 && tank ?
                ((parseFloat(reading.morning_dip_liters) / parseFloat(tank.capacity_liters)) * 100).toFixed(1) : '0.0';

            div.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-900">Tank ${reading.tank_number}</h4>
                    <span class="${fuelTypeClass} text-xs font-medium px-2 py-1 rounded-full bg-gray-100">
                        ${reading.fuel_type.toUpperCase()}
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Morning Reading:</span>
                            <span class="font-medium text-blue-600">
                                ${reading.morning_dip_liters > 0 ? parseFloat(reading.morning_dip_liters).toLocaleString() + 'L' : 'Not recorded'}
                            </span>
                        </div>
                        ${tank ? `
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tank Capacity:</span>
                                <span class="font-medium">${parseFloat(tank.capacity_liters).toLocaleString()}L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fill Level:</span>
                                <span class="font-medium">${fillPercentage}%</span>
                            </div>
                        ` : ''}
                        ${reading.water_level_mm ? `
                            <div class="flex justify-between">
                                <span class="text-gray-600">Water Level:</span>
                                <span class="font-medium">${parseFloat(reading.water_level_mm).toLocaleString()}mm</span>
                            </div>
                        ` : ''}
                    </div>

                    <div class="pt-3 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">Recorded by:</span>
                            <span class="text-xs font-medium text-gray-700">
                                ${reading.first_name ? reading.first_name + ' ' + reading.last_name : 'Unknown'}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500">Recorded at:</span>
                            <span class="text-xs font-medium text-gray-700">
                                ${reading.created_at ? new Date(reading.created_at).toLocaleTimeString() : 'Unknown'}
                            </span>
                        </div>
                    </div>
                </div>
            `;

            return div;
        } catch (error) {
            console.error('Failed to create review card:', error);
            this.showError('Failed to create review card: ' + error.message);
            return document.createElement('div');
        }
    }

    refreshData() {
        try {
            window.location.reload();
        } catch (error) {
            console.error('Failed to refresh data:', error);
            this.showError('Failed to refresh data: ' + error.message);
        }
    }

    exportData() {
        try {
            const data = {
                station: this.appData.stations.find(s => s.id == this.appData.selectedStation),
                date: this.appData.today,
                readings: this.appData.readings,
                tanks: this.appData.tanks,
                summary: {
                    totalTanks: this.appData.tanks.length,
                    morningComplete: this.appData.readings.filter(r => r.morning_dip_liters > 0).length,
                    completionRate: this.appData.tanks.length > 0 ?
                        Math.round((this.appData.readings.filter(r => r.morning_dip_liters > 0).length / this.appData.tanks.length) * 100) : 0
                }
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `daily-readings-${this.appData.today}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showSuccess('Data exported successfully');
        } catch (error) {
            console.error('Failed to export data:', error);
            this.showError('Failed to export data: ' + error.message);
        }
    }

    printReview() {
        try {
            window.print();
        } catch (error) {
            console.error('Failed to print review:', error);
            this.showError('Failed to print review: ' + error.message);
        }
    }

    showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert('Success: ' + message);
        }
    }

    getMorningPendingTanks() {
        try {
            return this.appData.tanks.filter(tank => {
                const reading = this.appData.readings.find(r => r.tank_id == tank.id);
                return !reading || reading.morning_dip_liters == 0;
            });
        } catch (error) {
            console.error('Failed to get morning pending tanks:', error);
            return [];
        }
    }

    getEveningPendingReadings() {
        // Removed - evening readings handled by another controller
        return [];
    }

    getTanksWithMorningReadings() {
        try {
            return this.appData.readings.filter(r => r.morning_dip_liters > 0);
        } catch (error) {
            console.error('Failed to get tanks with morning readings:', error);
            return [];
        }
    }

    getFuelTypeClass(fuelType) {
        const classes = {
            'petrol': 'text-green-600',
            'diesel': 'text-blue-600',
            'kerosene': 'text-purple-600'
        };
        return classes[fuelType] || 'text-gray-600';
    }

    async submitMorningReading(tankId) {
        try {
            const morningDipInput = document.getElementById(`morning_dip_${tankId}`);
            const waterLevelInput = document.getElementById(`water_level_${tankId}`);
            const saveButton = document.getElementById(`save_morning_${tankId}`);

            if (!morningDipInput || !morningDipInput.value) {
                this.showError('Morning dip reading is required');
                return;
            }

            const morningDip = parseFloat(morningDipInput.value);
            const tank = this.appData.tanks.find(t => t.id == tankId);

            if (morningDip > parseFloat(tank.capacity_liters)) {
                this.showError('Morning dip cannot exceed tank capacity');
                return;
            }

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
            }

            const response = await fetch(this.appData.routes.storeMorning, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.appData.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    station_id: this.appData.selectedStation,
                    tank_id: tankId,
                    reading_date: this.appData.today,
                    morning_dip_liters: morningDip,
                    water_level_mm: waterLevelInput ? parseFloat(waterLevelInput.value) || null : null
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Reload page to get updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Failed to save morning reading');
            }

        } catch (error) {
            console.error('Failed to submit morning reading:', error);
            this.showError('Failed to save morning reading: ' + error.message);

            const saveButton = document.getElementById(`save_morning_${tankId}`);
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save"></i> <span>Save Reading</span>';
            }
        }
    }

    async submitEveningReading(tankId) {
        try {
            const eveningDipInput = document.getElementById(`evening_dip_${tankId}`);
            const waterLevelInput = document.getElementById(`evening_water_${tankId}`);
            const saveButton = document.getElementById(`save_evening_${tankId}`);

            if (!eveningDipInput || !eveningDipInput.value) {
                this.showError('Evening dip reading is required');
                return;
            }

            const eveningDip = parseFloat(eveningDipInput.value);
            const tank = this.appData.tanks.find(t => t.id == tankId);

            if (eveningDip > parseFloat(tank.capacity_liters)) {
                this.showError('Evening dip cannot exceed tank capacity');
                return;
            }

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
            }

            const response = await fetch(this.appData.routes.storeEvening, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.appData.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    station_id: this.appData.selectedStation,
                    tank_id: tankId,
                    reading_date: this.appData.today,
                    evening_dip_liters: eveningDip,
                    water_level_mm: waterLevelInput ? parseFloat(waterLevelInput.value) || null : null
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Reload page to get updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Failed to save evening reading');
            }

        } catch (error) {
            console.error('Failed to submit evening reading:', error);
            this.showError('Failed to save evening reading: ' + error.message);

            const saveButton = document.getElementById(`save_evening_${tankId}`);
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="fas fa-save"></i> <span>Save Reading</span>';
            }
        }
    }

    changeStation(stationId) {
        try {
            window.location.href = `${this.appData.routes.index}?station_id=${stationId}`;
        } catch (error) {
            console.error('Failed to change station:', error);
            this.showError('Failed to change station: ' + error.message);
        }
    }

    updateUI() {
        try {
            this.updateTabUI();
            this.updateTabContent();
        } catch (error) {
            console.error('Failed to update UI:', error);
            this.showError('Failed to update UI: ' + error.message);
        }
    }

    showError(message) {
        console.error('Application Error:', message);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'OK'
            });
        } else {
            alert('Error: ' + message);
        }
    }
}

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        window.app = new DailyDipReadingsApp();
    } catch (error) {
        console.error('Failed to start application:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Startup Error',
                text: 'Failed to start application: ' + error.message,
                confirmButtonText: 'Reload Page'
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert('Failed to start application: ' + error.message);
        }
    }
});
</script>

<style>
.tab-button {
    transition: all 0.2s ease;
}

.tab-button.active {
    border-color: #3b82f6;
    color: #3b82f6;
}

.tab-button:not(.active) {
    border-color: transparent;
    color: #6b7280;
}

.tab-button:not(.active):hover {
    color: #374151;
    border-color: #d1d5db;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
}

.tab-content {
    min-height: 400px;
}

#loadingOverlay {
    backdrop-filter: blur(2px);
}
</style>
@endsection
