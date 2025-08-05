@extends('layouts.app')

@section('title', 'Meter Readings V2')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">Meter Readings</h1>
        <p class="text-slate-600 font-medium">Record complete meter readings with automated FIFO processing</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-sm">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-emerald-700 font-medium">{{ $today ?? date('Y-m-d') }}</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-sm">
            <i class="fas fa-cogs text-blue-600"></i>
            <span class="text-blue-700 font-medium">Automation Active</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<div x-data="meterReadingsV2()" class="space-y-6">
    <!-- Station Selector - MANDATORY for Access Control -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="fas fa-building text-blue-600"></i>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-800 block">Station Selection</label>
                    <p class="text-xs text-slate-500">Required for station-level access control</p>
                </div>
            </div>
            <select x-model="selectedStation" @change="changeStation()"
                    class="px-4 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 font-medium min-w-[300px] focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @foreach($stations ?? [] as $station)
                <option value="{{ $station->id }}" {{ ($station->id == ($selectedStation ?? '')) ? 'selected' : '' }}>
                    {{ $station->name }} - {{ $station->location }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Wizard Navigation -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50">
            <nav class="flex" role="tablist">
                <button @click="activeTab = 'readings'"
                        :class="activeTab === 'readings' ?
                            'border-blue-500 text-blue-600 bg-white' :
                            'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-100'"
                        class="flex-1 py-4 px-6 border-b-2 font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-tachometer-alt"></i>Record Readings
                </button>
                <button @click="activeTab = 'preview'"
                        :class="activeTab === 'preview' ?
                            'border-blue-500 text-blue-600 bg-white' :
                            'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-100'"
                        class="flex-1 py-4 px-6 border-b-2 font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-search"></i>Validate & Preview
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ?
                            'border-blue-500 text-blue-600 bg-white' :
                            'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-100'"
                        class="flex-1 py-4 px-6 border-b-2 font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-history"></i>Today's History
                </button>
                <button @click="activeTab = 'automation'"
                        :class="activeTab === 'automation' ?
                            'border-blue-500 text-blue-600 bg-white' :
                            'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-100'"
                        class="flex-1 py-4 px-6 border-b-2 font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-robot"></i>Automation Health
                </button>
            </nav>
        </div>

        <!-- Reading Entry Tab -->
        <div x-show="activeTab === 'readings'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($meters ?? [] as $meter)
                @php
                    $hasReading = isset($readings) && $readings->where('meter_id', $meter->id)->first();
                    $reading = $hasReading ? $readings->where('meter_id', $meter->id)->first() : null;
                @endphp
                <div class="border border-slate-200 rounded-xl p-6 hover:shadow-md transition-shadow duration-200 {{ $hasReading ? 'bg-emerald-50 border-emerald-200' : 'bg-white' }}">
                    <!-- Meter Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-slate-100 rounded-lg">
                                <i class="fas fa-gas-pump text-slate-600"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">{{ $meter->meter_number }}</h3>
                                <p class="text-sm text-slate-600">{{ $meter->tank_number }} - {{ ucfirst($meter->fuel_type) }}</p>
                            </div>
                        </div>
                        @if($hasReading)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-100 rounded-lg">
                            <i class="fas fa-check-circle text-emerald-600"></i>
                            <span class="text-sm font-semibold text-emerald-700">Recorded</span>
                        </div>
                        @endif
                    </div>

                    @if(!$hasReading)
                    <!-- Reading Entry Form -->
                    <div x-data="{
                        openingReading: '{{ $meter->current_reading_liters ?? 0 }}',
                        closingReading: '{{ $meter->current_reading_liters ?? 0 }}',
                        get dispensedAmount() {
                            return (parseFloat(this.closingReading) - parseFloat(this.openingReading)).toFixed(3)
                        },
                        get isValid() {
                            return this.openingReading && this.closingReading &&
                                   parseFloat(this.closingReading) >= parseFloat(this.openingReading)
                        }
                    }">
                        <div class="space-y-4">
                            <!-- Previous Reading Reference -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-600">Current Meter Reading:</span>
                                    <span class="font-mono font-semibold text-slate-800">{{ number_format($meter->current_reading_liters ?? 0, 3) }}L</span>
                                </div>
                            </div>

                            <!-- Opening Reading -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Opening Reading (Liters)</label>
                                <input type="number"
                                       x-model="openingReading"
                                       step="0.001"
                                       min="{{ $meter->current_reading_liters ?? 0 }}"
                                       class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white font-mono text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter opening reading...">
                                <p class="text-xs text-slate-500 mt-1">Must be ≥ {{ number_format($meter->current_reading_liters ?? 0, 3) }}L</p>
                            </div>

                            <!-- Closing Reading -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Closing Reading (Liters)</label>
                                <input type="number"
                                       x-model="closingReading"
                                       step="0.001"
                                       :min="openingReading"
                                       class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white font-mono text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter closing reading...">
                                <p class="text-xs text-slate-500 mt-1">Must be ≥ opening reading</p>
                            </div>

                            <!-- Dispensed Preview -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-blue-700">Dispensed Volume:</span>
                                    <span class="font-mono font-bold text-lg text-blue-800" x-text="dispensedAmount + 'L'"></span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button @click="submitReading({{ $meter->id }}, openingReading, closingReading)"
                                    :disabled="!isValid"
                                    :class="isValid ?
                                        'bg-blue-600 hover:bg-blue-700 text-white' :
                                        'bg-slate-300 text-slate-500 cursor-not-allowed'"
                                    class="w-full py-3 px-4 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i>Record Complete Reading
                            </button>
                        </div>
                    </div>
                    @else
                    <!-- Completed Reading Display -->
                    <div class="space-y-3">
                        <div class="bg-white border border-emerald-200 rounded-lg p-4">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Opening Reading:</span>
                                    <span class="font-mono font-semibold text-slate-800">{{ number_format($reading->opening_reading_liters, 3) }}L</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Closing Reading:</span>
                                    <span class="font-mono font-semibold text-slate-800">{{ number_format($reading->closing_reading_liters, 3) }}L</span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-emerald-700 font-medium">Total Dispensed:</span>
                                    <span class="font-mono font-bold text-lg text-emerald-800">{{ number_format($reading->dispensed_liters, 3) }}L</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500 text-center">
                            Recorded by {{ $reading->first_name ?? 'User' }} {{ $reading->last_name ?? '' }}
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Preview & Validation Tab -->
        <div x-show="activeTab === 'preview'" class="p-6">
            <div class="text-center py-12">
                <div class="p-4 bg-blue-50 rounded-lg inline-block mb-4">
                    <i class="fas fa-search text-4xl text-blue-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Validation & Preview</h3>
                <p class="text-slate-600 mb-6">Preview automation results before final submission</p>
                <button class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    <i class="fas fa-play mr-2"></i>Run Validation Preview
                </button>
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" class="p-6">
            @if(isset($readings) && $readings->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Meter</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Tank & Fuel</th>
                            <th class="text-right py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Opening</th>
                            <th class="text-right py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Closing</th>
                            <th class="text-right py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Dispensed</th>
                            <th class="text-left py-3 px-4 font-semibold text-slate-700 border-b border-slate-200">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $reading)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 font-medium text-slate-800">{{ $reading->meter_number }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $reading->tank_number }} - {{ ucfirst($reading->fuel_type) }}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-800">{{ number_format($reading->opening_reading_liters, 3) }}L</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-800">{{ number_format($reading->closing_reading_liters, 3) }}L</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-blue-600">{{ number_format($reading->dispensed_liters, 3) }}L</td>
                            <td class="py-3 px-4 text-slate-600">{{ $reading->first_name }} {{ $reading->last_name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-16">
                <div class="p-4 bg-slate-50 rounded-lg inline-block mb-4">
                    <i class="fas fa-clipboard-list text-4xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">No Readings Today</h3>
                <p class="text-slate-600">Start by recording complete meter readings</p>
            </div>
            @endif
        </div>

        <!-- Automation Health Tab -->
        <div x-show="activeTab === 'automation'" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-600 font-semibold text-sm">FIFO Processing</p>
                            <p class="text-2xl font-bold text-emerald-800">Active</p>
                        </div>
                        <i class="fas fa-cogs text-2xl text-emerald-600"></i>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-600 font-semibold text-sm">Trigger Health</p>
                            <p class="text-2xl font-bold text-blue-800">98.5%</p>
                        </div>
                        <i class="fas fa-heartbeat text-2xl text-blue-600"></i>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-600 font-semibold text-sm">Processing Time</p>
                            <p class="text-2xl font-bold text-amber-800">1.2s</p>
                        </div>
                        <i class="fas fa-clock text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6">
                <h4 class="font-bold text-slate-800 mb-4">Database Triggers Status</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                        <div>
                            <p class="font-medium text-slate-800">tr_validate_meter_progression</p>
                            <p class="text-sm text-slate-600">Prevents meter fraud and detects resets</p>
                        </div>
                        <div class="flex items-center gap-2 text-emerald-600">
                            <i class="fas fa-check-circle"></i>
                            <span class="font-semibold">Active</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                        <div>
                            <p class="font-medium text-slate-800">tr_enhanced_meter_fifo_automation</p>
                            <p class="text-sm text-slate-600">Calculates sales and triggers FIFO processing</p>
                        </div>
                        <div class="flex items-center gap-2 text-emerald-600">
                            <i class="fas fa-check-circle"></i>
                            <span class="font-semibold">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function meterReadingsV2() {
    return {
        activeTab: 'readings',
        selectedStation: {{ $selectedStation ?? 'null' }},

        changeStation() {
            if (this.selectedStation) {
                window.location.href = `{{ route('meter-readings.index') }}?station_id=${this.selectedStation}`;
            }
        },

        async submitReading(meterId, openingReading, closingReading) {
            // Validation
            if (!openingReading || !closingReading) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Both opening and closing readings are required'
                });
                return;
            }

            const opening = parseFloat(openingReading);
            const closing = parseFloat(closingReading);

            if (closing < opening) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Reading',
                    text: 'Closing reading cannot be less than opening reading'
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Submitting reading and triggering automation',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('{{ route("meter-readings.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        meter_id: meterId,
                        reading_date: '{{ $today ?? date("Y-m-d") }}',
                        opening_reading_liters: opening,
                        closing_reading_liters: closing
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: `
                            <div class="text-left">
                                <p class="font-semibold mb-2">Reading recorded successfully</p>
                                <p class="text-sm text-gray-600">${data.message}</p>
                            </div>
                        `,
                        timer: 4000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: data.error || 'Failed to record reading'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to connect to server. Please try again.'
                });
            }
        }
    }
}
</script>
@endsection
