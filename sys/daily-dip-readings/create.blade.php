@extends('layouts.app')

@section('title', 'New Daily Reading')

@section('breadcrumb')
<span class="text-muted-foreground">Operations</span>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground mx-2"></i>
<a href="{{ route('daily-readings.index') }}" class="text-muted-foreground hover:text-foreground">Daily Readings</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground mx-2"></i>
<span class="text-foreground font-medium">New Reading</span>
@endsection

@section('content')
<div x-data="readingWizard()" class="max-w-4xl mx-auto">

    @if(!isset($selected_station))
    <!-- Step 1: Station Selection -->
    <div class="card p-8 text-center">
        <i class="fas fa-gas-pump h-12 w-12 text-muted-foreground mx-auto mb-4"></i>
        <h2 class="text-xl font-semibold mb-2">Select Station</h2>
        <p class="text-muted-foreground mb-6">Choose the station to record readings for</p>

        <div class="grid gap-3 max-w-md mx-auto">
            @foreach($accessible_stations as $station)
            <a href="{{ route('daily-readings.create', ['station_id' => $station->id, 'date' => $selected_date]) }}"
               class="card p-4 hover:shadow-md transition-all border-2 hover:border-primary group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 group-hover:bg-primary group-hover:text-white flex items-center justify-center transition-colors">
                        <i class="fas fa-building h-5 w-5"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-medium">{{ $station->name }}</p>
                        <p class="text-sm text-muted-foreground">{{ $station->location }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <!-- Main Reading Form -->
    <form method="POST" action="{{ route('daily-readings.store') }}" x-ref="mainForm" @submit="validateSubmit">
        @csrf
        <input type="hidden" name="station_id" value="{{ $selected_station->id }}">

        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ $selected_station->name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $selected_station->location }}</p>
                </div>
                <div class="text-right">
                    <input type="date" name="reading_date" x-model="readingDate" @change="validateDate"
                           value="{{ $selected_date }}" max="{{ date('Y-m-d') }}"
                           class="input text-lg font-mono" required>
                </div>
            </div>
        </div>

        <!-- Readings Grid -->
        <div class="space-y-4">
            @foreach($tanks as $tank)
            @php $existing = $existing_readings->get($tank->id); @endphp
            <div class="card p-6" x-data="{
                tankId: {{ $tank->id }},
                capacity: {{ $tank->capacity_liters }},
                morning: {{ $existing ? $existing->morning_dip_liters : 0 }},
                evening: {{ $existing ? $existing->evening_dip_liters : 0 }},
                water: {{ $existing ? ($existing->water_level_mm ?? 0) : 0 }},
                temp: {{ $existing ? ($existing->temperature_celsius ?? 0) : 0 }}
            }">

                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold">Tank {{ $tank->tank_number }}</h3>
                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ $tank->fuel_type === 'petrol' ? 'bg-blue-100 text-blue-800' :
                                   ($tank->fuel_type === 'diesel' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($tank->fuel_type) }}
                            </span>
                            <span>Capacity: {{ number_format($tank->capacity_liters) }}L</span>
                            <span>Current: {{ number_format($tank->current_volume_liters) }}L</span>
                        </div>
                    </div>
                    @if($tank->meter_count > 0)
                    <span class="text-xs bg-muted px-2 py-1 rounded">{{ $tank->meter_count }} Meters</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Morning Dip -->
                    <div>
                        <label class="text-sm font-medium">Morning Dip (L)</label>
                        <input type="number" name="readings[{{ $loop->index }}][tank_id]" value="{{ $tank->id }}" hidden>
                        <input type="number" name="readings[{{ $loop->index }}][morning_dip_liters]"
                               x-model="morning" @input="validateReading('morning', $event.target.value)"
                               step="0.001" min="0" :max="capacity" class="input w-full mt-1" required>
                    </div>

                    <!-- Evening Dip -->
                    <div>
                        <label class="text-sm font-medium">Evening Dip (L)</label>
                        <input type="number" name="readings[{{ $loop->index }}][evening_dip_liters]"
                               x-model="evening" @input="validateReading('evening', $event.target.value)"
                               step="0.001" min="0" :max="capacity" class="input w-full mt-1">
                    </div>

                    <!-- Water Level -->
                    <div>
                        <label class="text-sm font-medium">Water Level (mm)</label>
                        <input type="number" name="readings[{{ $loop->index }}][water_level_mm]"
                               x-model="water" step="0.01" min="0" max="99999.99" class="input w-full mt-1">
                    </div>

                    <!-- Temperature -->
                    <div>
                        <label class="text-sm font-medium">Temperature (°C)</label>
                        <input type="number" name="readings[{{ $loop->index }}][temperature_celsius]"
                               x-model="temp" step="0.01" min="-10" max="60" class="input w-full mt-1">
                    </div>
                </div>

                <!-- Meter Readings -->
                @if(isset($meters[$tank->id]))
                <div class="border-t pt-4 mt-4">
                    <h4 class="text-sm font-medium mb-3">Meter Readings</h4>
                    <div class="grid gap-3">
                        @foreach($meters[$tank->id] as $meter)
                        @php
                            $meterReading = $existing_meter_readings->get($tank->id)?->firstWhere('meter_id', $meter->id);
                        @endphp
                        <div class="grid grid-cols-3 gap-3 items-center" x-data="{
                            meterId: {{ $meter->id }},
                            current: {{ $meter->current_reading_liters }},
                            opening: {{ $meterReading ? $meterReading->opening_reading_liters : $meter->current_reading_liters }},
                            closing: {{ $meterReading ? $meterReading->closing_reading_liters : $meter->current_reading_liters }}
                        }">
                            <div class="text-sm">
                                <span class="font-medium">{{ $meter->meter_number }}</span>
                                <div class="text-xs text-muted-foreground">Current: {{ number_format($meter->current_reading_liters, 3) }}L</div>
                            </div>

                            <div>
                                <label class="text-xs text-muted-foreground">Opening</label>
                                <input type="hidden" name="meter_readings[{{ $meter->id }}][meter_id]" value="{{ $meter->id }}">
                                <input type="number" name="meter_readings[{{ $meter->id }}][opening_reading_liters]"
                                       x-model="opening" @input="validateMeter('opening', $event.target.value)"
                                       step="0.001" :min="current" class="input w-full text-sm" required>
                            </div>

                            <div>
                                <label class="text-xs text-muted-foreground">Closing</label>
                                <input type="number" name="meter_readings[{{ $meter->id }}][closing_reading_liters]"
                                       x-model="closing" @input="validateMeter('closing', $event.target.value)"
                                       step="0.001" :min="opening" class="input w-full text-sm" required>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
            @endforeach
        </div>

        <!-- Submit -->
        <div class="card p-6 mt-6 text-center">
            <button type="submit" :disabled="!formValid"
                    class="btn btn-primary px-8 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save h-4 w-4 mr-2"></i>
                Save Readings
            </button>
        </div>
    </form>
    @endif
</div>

<script>
function readingWizard() {
    return {
        readingDate: '{{ $selected_date }}',
        formValid: true,

        validateDate() {
            const selected = new Date(this.readingDate);
            const today = new Date();
            today.setHours(23, 59, 59, 999);

            if (selected > today) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Cannot enter readings for future dates',
                    toast: true,
                    position: 'top-end',
                    timer: 3000
                });
                this.readingDate = '{{ date("Y-m-d") }}';
                return false;
            }
            return true;
        },

        validateReading(type, value) {
            const val = parseFloat(value);
            if (isNaN(val) || val < 0) {
                this.formValid = false;
                return;
            }
            this.formValid = true;
        },

        validateMeter(type, value) {
            const val = parseFloat(value);
            if (isNaN(val) || val < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Meter Reading',
                    text: 'Meter readings cannot go backward',
                    toast: true,
                    position: 'top-end',
                    timer: 3000
                });
                this.formValid = false;
                return;
            }
            this.formValid = true;
        },

        validateSubmit(e) {
            if (!this.formValid || !this.validateDate()) {
                e.preventDefault();
                return;
            }

            // Final validation
            const form = e.target;
            const formData = new FormData(form);
            let hasError = false;

            // Check for required morning readings
            const readings = Array.from(form.querySelectorAll('input[name*="morning_dip_liters"]'));
            if (readings.some(input => !input.value || parseFloat(input.value) < 0)) {
                hasError = true;
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Readings',
                    text: 'All morning dip readings are required'
                });
            }

            if (hasError) {
                e.preventDefault();
            }
        }
    }
}
</script>
@endsection
