@extends('layouts.app')

@section('title', 'Meter Readings')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-foreground">Meter Readings</h1>
        <p class="text-muted-foreground">Record morning and evening meter readings</p>
    </div>
    <div class="flex items-center gap-2 text-sm">
        <div class="w-2 h-2 rounded-full bg-green-500"></div>
        <span class="text-muted-foreground">{{ $today }}</span>
    </div>
</div>
@endsection

@section('content')
<div x-data="meterReadings()" class="space-y-6">
    <!-- Station Selector -->
    <div class="card p-6">
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-foreground">Station:</label>
            <select x-model="selectedStation" @change="changeStation()" class="select w-auto min-w-[300px]">
                @foreach($stations as $station)
                <option value="{{ $station->id }}" {{ $station->id == $selectedStation ? 'selected' : '' }}>
                    {{ $station->name }} - {{ $station->location }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Wizard Tabs -->
    <div class="card">
        <div class="border-b border-border">
            <nav class="flex space-x-8 px-6" role="tablist">
                <button @click="activeTab = 'morning'"
                        :class="activeTab === 'morning' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-sun mr-2"></i>Morning Readings
                </button>
                <button @click="activeTab = 'evening'"
                        :class="activeTab === 'evening' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-moon mr-2"></i>Evening Readings
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-history mr-2"></i>Today's History
                </button>
            </nav>
        </div>

        <!-- Morning Tab -->
        <div x-show="activeTab === 'morning'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($meters as $meter)
                @php
                    $meterReading = $readings->where('meter_id', $meter->id)->first();
                    $hasReading = $meterReading !== null;
                @endphp
                <div class="border border-border rounded-lg p-4 {{ $hasReading ? 'bg-green-50 border-green-200' : 'bg-background' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-foreground">{{ $meter->meter_number }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $meter->tank_number }} - {{ ucfirst($meter->fuel_type) }}</p>
                        </div>
                        @if($hasReading)
                        <div class="flex items-center gap-2 text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span class="text-sm font-medium">Recorded</span>
                        </div>
                        @endif
                    </div>

                    @if(!$hasReading)
                    <div x-data="{ reading: '{{ $meter->current_reading_liters }}' }">
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-foreground">Opening Reading (Liters)</label>
                                <input type="number"
                                       x-model="reading"
                                       step="0.001"
                                       min="{{ $meter->current_reading_liters }}"
                                       class="input w-full mt-1"
                                       placeholder="Enter reading...">
                                <p class="text-xs text-muted-foreground mt-1">Previous: {{ number_format($meter->current_reading_liters, 3) }}L</p>
                            </div>
                            <button @click="submitMorning({{ $meter->id }}, reading)"
                                    :disabled="!reading || reading < {{ $meter->current_reading_liters }}"
                                    class="btn btn-primary w-full">
                                <i class="fas fa-save mr-2"></i>Record Morning Reading
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="bg-background rounded p-3">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Opening:</span>
                                <span class="font-medium">{{ number_format($meterReading->opening_reading_liters, 3) }}L</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Evening Tab -->
        <div x-show="activeTab === 'evening'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($meters as $meter)
                @php
                    $meterReading = $readings->where('meter_id', $meter->id)->first();
                    $needsEvening = $meterReading && $meterReading->opening_reading_liters == $meterReading->closing_reading_liters;
                    $completed = $meterReading && $meterReading->opening_reading_liters != $meterReading->closing_reading_liters;
                @endphp
                <div class="border border-border rounded-lg p-4 {{ $completed ? 'bg-green-50 border-green-200' : ($needsEvening ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200') }}">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-foreground">{{ $meter->meter_number }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $meter->tank_number }} - {{ ucfirst($meter->fuel_type) }}</p>
                        </div>
                        @if($completed)
                        <div class="flex items-center gap-2 text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span class="text-sm font-medium">Complete</span>
                        </div>
                        @elseif($needsEvening)
                        <div class="flex items-center gap-2 text-blue-600">
                            <i class="fas fa-clock"></i>
                            <span class="text-sm font-medium">Pending</span>
                        </div>
                        @else
                        <div class="flex items-center gap-2 text-gray-500">
                            <i class="fas fa-minus-circle"></i>
                            <span class="text-sm font-medium">No Morning</span>
                        </div>
                        @endif
                    </div>

                    @if($needsEvening)
                    <div x-data="{ closing: '{{ $meterReading->opening_reading_liters }}' }">
                        <div class="space-y-3">
                            <div class="bg-background rounded p-3 mb-3">
                                <div class="text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Opening:</span>
                                        <span class="font-medium">{{ number_format($meterReading->opening_reading_liters, 3) }}L</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-foreground">Closing Reading (Liters)</label>
                                <input type="number"
                                       x-model="closing"
                                       step="0.001"
                                       min="{{ $meterReading->opening_reading_liters }}"
                                       class="input w-full mt-1"
                                       placeholder="Enter closing reading...">
                                <p class="text-xs text-muted-foreground mt-1">Must be ≥ {{ number_format($meterReading->opening_reading_liters, 3) }}L</p>
                            </div>
                            <button @click="submitEvening({{ $meter->id }}, closing)"
                                    :disabled="!closing || closing < {{ $meterReading->opening_reading_liters }}"
                                    class="btn btn-primary w-full">
                                <i class="fas fa-save mr-2"></i>Record Evening Reading
                            </button>
                        </div>
                    </div>
                    @elseif($completed)
                    <div class="bg-background rounded p-3">
                        <div class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Opening:</span>
                                <span class="font-medium">{{ number_format($meterReading->opening_reading_liters, 3) }}L</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Closing:</span>
                                <span class="font-medium">{{ number_format($meterReading->closing_reading_liters, 3) }}L</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-muted-foreground">Dispensed:</span>
                                <span class="font-bold text-primary">{{ number_format($meterReading->dispensed_liters, 3) }}L</span>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center text-muted-foreground py-4">
                        <i class="fas fa-info-circle mb-2"></i>
                        <p class="text-sm">Record morning reading first</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" class="p-6">
            @if($readings->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left py-3 px-4 font-medium text-foreground">Meter</th>
                            <th class="text-left py-3 px-4 font-medium text-foreground">Tank</th>
                            <th class="text-right py-3 px-4 font-medium text-foreground">Opening</th>
                            <th class="text-right py-3 px-4 font-medium text-foreground">Closing</th>
                            <th class="text-right py-3 px-4 font-medium text-foreground">Dispensed</th>
                            <th class="text-left py-3 px-4 font-medium text-foreground">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readings as $reading)
                        <tr class="border-b border-border hover:bg-accent/50">
                            <td class="py-3 px-4 font-medium">{{ $reading->meter_number }}</td>
                            <td class="py-3 px-4">{{ $reading->tank_number }} - {{ ucfirst($reading->fuel_type) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($reading->opening_reading_liters, 3) }}L</td>
                            <td class="py-3 px-4 text-right font-mono">{{ number_format($reading->closing_reading_liters, 3) }}L</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-primary">{{ number_format($reading->dispensed_liters, 3) }}L</td>
                            <td class="py-3 px-4">{{ $reading->first_name }} {{ $reading->last_name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center text-muted-foreground py-12">
                <i class="fas fa-clipboard-list text-4xl mb-4"></i>
                <h3 class="text-lg font-medium mb-2">No Readings Today</h3>
                <p>Start by recording morning readings</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function meterReadings() {
    return {
        activeTab: 'morning',
        selectedStation: {{ $selectedStation }},

        changeStation() {
            window.location.href = `{{ route('meter-readings.index') }}?station_id=${this.selectedStation}`;
        },

        async submitMorning(meterId, reading) {
            if (!reading || reading < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Reading',
                    text: 'Please enter a valid reading'
                });
                return;
            }

            try {
                const response = await fetch('{{ route("meter-readings.store-morning") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        station_id: this.selectedStation,
                        meter_id: meterId,
                        reading_date: '{{ $today }}',
                        opening_reading_liters: parseFloat(reading)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: error.message
                });
            }
        },

        async submitEvening(meterId, closing) {
            if (!closing || closing < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Reading',
                    text: 'Please enter a valid closing reading'
                });
                return;
            }

            try {
                const response = await fetch('{{ route("meter-readings.store-evening") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        station_id: this.selectedStation,
                        meter_id: meterId,
                        reading_date: '{{ $today }}',
                        closing_reading_liters: parseFloat(closing)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 3000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: error.message
                });
            }
        }
    }
}
</script>
@endsection
