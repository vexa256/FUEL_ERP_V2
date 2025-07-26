@extends('layouts.app')

@section('title', 'Edit Tank - ' . ($tank->tank_number ?? 'Unknown'))

@section('breadcrumb')
<a href="{{ route('tanks.index') }}" class="text-muted-foreground hover:text-foreground">Tank Management</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<a href="{{ route('tanks.show', $tank->id ?? 0) }}" class="text-muted-foreground hover:text-foreground">{{ $tank->tank_number ?? 'Tank' }}</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground">Edit</span>
@endsection

@section('page-header')
<div class="flex items-center justify-between w-full">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Edit Tank {{ $tank->tank_number ?? '' }}</h1>
        <p class="text-muted-foreground mt-2">{{ $tank->station_name ?? '' }} - {{ $tank->station_location ?? '' }}</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('tanks.show', $tank->id ?? 0) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Tank
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="tankEditForm()" x-init="init()" class="max-w-4xl mx-auto">

    <!-- Dependency Warnings -->
    @if(($has_deliveries ?? false) || ($has_reconciliations ?? false) || ($has_meters ?? false) || ($has_fifo_layers ?? false))
    <div class="card border-l-4 border-l-orange-500 bg-orange-50 mb-6">
        <div class="p-4">
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-orange-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-orange-800 mb-2">Editing Restrictions</h3>
                    <p class="text-orange-700 text-sm mb-3">This tank has operational data that limits certain modifications:</p>
                    <ul class="text-orange-700 text-sm space-y-1">
                        @if($has_deliveries ?? false)
                        <li class="flex items-center gap-2">
                            <i class="fas fa-truck text-orange-600"></i>
                            <span>Has delivery history - fuel type changes blocked</span>
                        </li>
                        @endif
                        @if($has_reconciliations ?? false)
                        <li class="flex items-center gap-2">
                            <i class="fas fa-balance-scale text-orange-600"></i>
                            <span>Has reconciliation history - fuel type changes blocked</span>
                        </li>
                        @endif
                        @if($has_fifo_layers ?? false)
                        <li class="flex items-center gap-2">
                            <i class="fas fa-layer-group text-orange-600"></i>
                            <span>Has active inventory - capacity reduction limited</span>
                        </li>
                        @endif
                        @if($has_meters ?? false)
                        <li class="flex items-center gap-2">
                            <i class="fas fa-gas-pump text-orange-600"></i>
                            <span>Has connected meters - consider impact on operations</span>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Form -->
    <div class="card border-0 shadow-xl bg-background/95 backdrop-blur-sm">
        <div class="border-b border-border bg-muted/30 px-6 py-4">
            <h2 class="text-lg font-semibold text-foreground">Tank Configuration</h2>
            <p class="text-sm text-muted-foreground">Modify tank settings within business rule constraints</p>
        </div>

        <form method="POST" action="{{ route('tanks.update', $tank->id ?? 0) }}" @submit="submitting = true" class="p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Tank Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tank Number -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Tank Number *</label>
                        <input type="text"
                               name="tank_number"
                               x-model="form.tank_number"
                               @input="validateTankNumber()"
                               value="{{ old('tank_number', $tank->tank_number ?? '') }}"
                               class="input w-full"
                               placeholder="e.g., T-001, TANK-01"
                               required>
                        <div x-show="errors.tank_number" class="text-sm text-red-600" x-text="errors.tank_number"></div>
                        <p class="text-xs text-muted-foreground">Use uppercase letters, numbers, hyphens, and underscores only</p>
                    </div>

                    <!-- Fuel Type -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Fuel Type *</label>
                        @if(($has_deliveries ?? false) || ($has_reconciliations ?? false))
                        <div class="relative">
                            <input type="text"
                                   value="{{ ucfirst($tank->fuel_type ?? '') }}"
                                   class="input w-full bg-muted cursor-not-allowed"
                                   disabled>
                            <input type="hidden" name="fuel_type" value="{{ $tank->fuel_type ?? '' }}">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="fas fa-lock text-muted-foreground"></i>
                            </div>
                        </div>
                        <p class="text-xs text-orange-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Cannot change fuel type - tank has operational history
                        </p>
                        @else
                        <select name="fuel_type"
                                x-model="form.fuel_type"
                                class="select w-full"
                                required>
                            @foreach(($fuel_types ?? []) as $type)
                            <option value="{{ $type }}" {{ old('fuel_type', $tank->fuel_type ?? '') === $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground">Fuel type can be changed for new tanks only</p>
                        @endif
                    </div>
                </div>

                <!-- Capacity Management -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-foreground">Capacity Management</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Current Capacity -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Tank Capacity (Liters) *</label>
                            <input type="number"
                                   name="capacity_liters"
                                   x-model="form.capacity_liters"
                                   @input="validateCapacity()"
                                   value="{{ old('capacity_liters', $tank->capacity_liters ?? '') }}"
                                   class="input w-full"
                                   min="1000"
                                   step="0.001"
                                   required>
                            <div x-show="errors.capacity_liters" class="text-sm text-red-600" x-text="errors.capacity_liters"></div>

                            <!-- Capacity Constraints Info -->
                            <div class="text-xs text-muted-foreground space-y-1">
                                <div>Current Volume: {{ number_format($tank->current_volume_liters ?? 0, 3) }}L</div>
                                @if(($has_fifo_layers ?? false))
                                <div class="text-orange-600">
                                    <i class="fas fa-layer-group mr-1"></i>
                                    FIFO inventory limits capacity reduction
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Visual Capacity -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-foreground">Current Utilization</label>
                            <div class="p-4 rounded-lg bg-muted/30">
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span>Current Volume:</span>
                                        <span class="font-medium">{{ number_format($tank->current_volume_liters ?? 0, 0) }}L</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Current Capacity:</span>
                                        <span class="font-medium">{{ number_format($tank->capacity_liters ?? 0, 0) }}L</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Fill Level:</span>
                                        <span class="font-medium">{{ $tank->capacity_liters > 0 ? number_format(($tank->current_volume_liters / $tank->capacity_liters) * 100, 1) : 0 }}%</span>
                                    </div>

                                    <!-- Fill Progress Bar -->
                                    <div class="w-full bg-muted rounded-full h-2">
                                        @php
                                            $fillPercentage = $tank->capacity_liters > 0 ? ($tank->current_volume_liters / $tank->capacity_liters) * 100 : 0;
                                        @endphp
                                        <div class="h-2 rounded-full transition-all duration-300
                                            @if($fillPercentage > 80) bg-green-500
                                            @elseif($fillPercentage > 50) bg-orange-500
                                            @else bg-red-500 @endif"
                                            style="width: {{ min($fillPercentage, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Impact Analysis -->
                <div class="card bg-blue-50 border-l-4 border-l-blue-500">
                    <div class="p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">Business Impact Analysis</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-blue-700 mb-2">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Current Configuration:
                                </p>
                                <ul class="text-blue-600 space-y-1 ml-4">
                                    <li>Tank: {{ $tank->tank_number ?? 'Unknown' }}</li>
                                    <li>Fuel: {{ ucfirst($tank->fuel_type ?? 'unknown') }}</li>
                                    <li>Capacity: {{ number_format($tank->capacity_liters ?? 0, 0) }}L</li>
                                    <li>Current: {{ number_format($tank->current_volume_liters ?? 0, 0) }}L</li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-blue-700 mb-2">
                                    <i class="fas fa-shield-alt mr-2"></i>
                                    Safety Constraints:
                                </p>
                                <ul class="text-blue-600 space-y-1 ml-4">
                                    <li>Minimum capacity: 1,000L</li>
                                    <li>Cannot reduce below current volume</li>
                                    <li>FIFO automation protected</li>
                                    <li>Historical data preserved</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-border">
                    <div class="text-sm text-muted-foreground">
                        <i class="fas fa-info-circle mr-2"></i>
                        Changes will be validated against business rules before saving
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('tanks.show', $tank->id ?? 0) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit"
                                :disabled="submitting || hasValidationErrors()"
                                class="btn btn-primary"
                                :class="{'opacity-50 cursor-not-allowed': submitting || hasValidationErrors()}">
                            <span x-show="!submitting">
                                <i class="fas fa-save mr-2"></i>
                                Update Tank
                            </span>
                            <span x-show="submitting">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Updating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Alpine.js Component -->
<script>
function tankEditForm() {
    return {
        submitting: false,

        form: {
            tank_number: '{{ old('tank_number', $tank->tank_number ?? '') }}',
            fuel_type: '{{ old('fuel_type', $tank->fuel_type ?? '') }}',
            capacity_liters: '{{ old('capacity_liters', $tank->capacity_liters ?? '') }}'
        },

        errors: {},

        // Controller data for validation
        existingTankNumbers: @json($existing_tank_numbers ?? []),
        currentVolume: {{ $tank->current_volume_liters ?? 0 }},
        hasDependencies: {
            deliveries: {{ ($has_deliveries ?? false) ? 'true' : 'false' }},
            reconciliations: {{ ($has_reconciliations ?? false) ? 'true' : 'false' }},
            meters: {{ ($has_meters ?? false) ? 'true' : 'false' }},
            fifoLayers: {{ ($has_fifo_layers ?? false) ? 'true' : 'false' }}
        },

        init() {
            // Initialize with current values
            this.validateAll();
        },

        validateTankNumber() {
            this.errors.tank_number = '';
            if (this.form.tank_number) {
                const cleaned = this.form.tank_number.toUpperCase().trim();
                this.form.tank_number = cleaned;

                if (!/^[A-Z0-9\-\_]+$/.test(cleaned)) {
                    this.errors.tank_number = 'Only uppercase letters, numbers, hyphens, and underscores allowed';
                } else if (this.existingTankNumbers.includes(cleaned)) {
                    this.errors.tank_number = 'Tank number already exists for this station';
                }
            } else {
                this.errors.tank_number = 'Tank number is required';
            }
        },

        validateCapacity() {
            this.errors.capacity_liters = '';
            if (this.form.capacity_liters) {
                const capacity = parseFloat(this.form.capacity_liters);

                if (capacity < 1000) {
                    this.errors.capacity_liters = 'Minimum capacity is 1,000 liters';
                } else if (capacity < this.currentVolume) {
                    this.errors.capacity_liters = `Cannot reduce capacity below current volume (${this.currentVolume.toLocaleString()}L)`;
                } else if (this.hasDependencies.fifoLayers && capacity < this.currentVolume) {
                    this.errors.capacity_liters = 'Cannot reduce capacity below FIFO inventory volume';
                }
            } else {
                this.errors.capacity_liters = 'Capacity is required';
            }
        },

        validateAll() {
            this.validateTankNumber();
            this.validateCapacity();
        },

        hasValidationErrors() {
            return Object.keys(this.errors).some(key => this.errors[key] !== '');
        }
    }
}

// Success/Error Flash Message Handling
@if(session('success'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Success!',
        text: '{{ session("success") }}',
        icon: 'success',
        confirmButtonText: 'View Tank'
    }).then(() => {
        window.location.href = '{{ route("tanks.show", $tank->id ?? 0) }}';
    });
});
@endif

@if(session('error'))
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Error',
        text: '{{ session("error") }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
});
@endif

// Validation Error Handling
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    const errorList = @json($errors->all());
    Swal.fire({
        title: 'Validation Errors',
        html: errorList.map(error => `<p class="text-left">${error}</p>`).join(''),
        icon: 'error',
        confirmButtonText: 'Fix Errors',
        customClass: {
            htmlContainer: 'text-left'
        }
    });
});
@endif
</script>
@endsection
