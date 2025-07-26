@extends('layouts.app')

@section('title', 'Pricing Management')

@section('page-header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Pricing Management</h1>
        <p class="text-gray-600 mt-1">{{ $selected_station->name }} • {{ $selected_station->location }}</p>
    </div>
    <a href="{{ route('pricing.index', ['station_id' => $selected_station->id]) }}"
       class="btn btn-secondary gap-2">
        <i class="fas fa-arrow-left"></i>Back
    </a>
</div>
@endsection

@section('content')
<div x-data="pricingManager()" x-init="init()" class="max-w-5xl mx-auto">

    <!-- Pricing Dashboard Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        @foreach(['petrol', 'diesel', 'kerosene'] as $fuel)
        <div class="card p-6 hover:shadow-lg transition-all duration-300 cursor-pointer border-2"
             :class="selectedFuel === '{{ $fuel }}' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/30'"
             @click="selectFuel('{{ $fuel }}')">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center
                        {{ $fuel === 'petrol' ? 'bg-emerald-100 text-emerald-600' : '' }}
                        {{ $fuel === 'diesel' ? 'bg-blue-100 text-blue-600' : '' }}
                        {{ $fuel === 'kerosene' ? 'bg-amber-100 text-amber-600' : '' }}">
                        <i class="fas fa-gas-pump text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg capitalize">{{ $fuel }}</h3>
                        <p class="text-sm text-muted-foreground">Current Pricing</p>
                    </div>
                </div>
                <div x-show="selectedFuel === '{{ $fuel }}'" class="text-primary">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>

            <div class="space-y-3">
                @if(isset($current_prices[$fuel]))
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Current Price</span>
                    <span class="font-mono font-bold text-lg">{{ number_format($current_prices[$fuel]->price_per_liter_ugx, 4) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Effective From</span>
                    <span class="text-sm">{{ $current_prices[$fuel]->effective_from_date }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Status</span>
                    <span class="text-sm font-medium text-green-600">Active</span>
                </div>
                @else
                <div class="text-center py-4">
                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <p class="text-sm font-medium text-orange-600">No Price Set</p>
                    <p class="text-xs text-muted-foreground">Click to configure</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Price Update Modal -->
    <div x-show="selectedFuel" x-transition class="card max-w-2xl mx-auto">
        <div class="border-b border-border px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                         :class="getFuelStyles(selectedFuel).bg + ' ' + getFuelStyles(selectedFuel).text">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold capitalize" x-text="selectedFuel + ' Pricing'"></h3>
                        <p class="text-sm text-muted-foreground">Update price with validation</p>
                    </div>
                </div>
                <button @click="selectedFuel = null" class="btn btn-ghost btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form @submit.prevent="submitPrice()" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="station_id" value="{{ $selected_station->id }}">
            <input type="hidden" name="validate_against_fifo" value="1">
            <input type="hidden" name="fuel_type" :value="selectedFuel">

            <!-- Current vs New Price Display -->
            <div x-show="getCurrentPrice() > 0" class="grid grid-cols-2 gap-4 p-4 bg-muted rounded-lg">
                <div class="text-center">
                    <div class="text-sm text-muted-foreground mb-1">Current Price</div>
                    <div class="text-2xl font-mono font-bold" x-text="formatPrice(getCurrentPrice())"></div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-muted-foreground mb-1">New Price</div>
                    <div class="text-2xl font-mono font-bold text-primary"
                         x-text="priceInput ? formatPrice(priceInput) : '---'"></div>
                </div>
            </div>

            <!-- Price Input -->
            <div class="space-y-3">
                <label class="block text-sm font-medium">New Price ({{ $selected_station->currency_code }}/L)</label>
                <div class="relative">
                    <input type="number"
                           name="price_per_liter_ugx"
                           x-model="priceInput"
                           step="0.0001"
                           min="1"
                           max="99999.9999"
                           required
                           @input="validatePrice()"
                           class="input text-xl font-mono text-center w-full py-4"
                           placeholder="0.0000">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        /L
                    </div>
                </div>
                <div x-show="priceValidation.message"
                     :class="priceValidation.valid ? 'text-emerald-600' : 'text-red-600'"
                     class="text-sm flex items-center gap-2">
                    <i :class="priceValidation.valid ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                    <span x-text="priceValidation.message"></span>
                </div>
            </div>

            <!-- Validation Dashboard -->
            <div x-show="priceInput && priceInput > 0" class="space-y-4">

                <!-- Price Change Analysis -->
                <div class="p-4 rounded-lg border" :class="changeAnalysis.valid ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium">Price Change Analysis</span>
                        <i :class="changeAnalysis.valid ? 'fas fa-check-circle text-emerald-600' : 'fas fa-exclamation-triangle text-red-600'"></i>
                    </div>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span>Change Amount:</span>
                            <span class="font-mono" x-text="changeAnalysis.amount"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Change Percentage:</span>
                            <span class="font-mono font-bold"
                                  :class="Math.abs(changeAnalysis.percentage) > 20 ? 'text-red-600' : 'text-emerald-600'"
                                  x-text="changeAnalysis.percentage + '%'"></span>
                        </div>
                    </div>
                </div>

                <!-- FIFO Margin Analysis -->
                <div class="p-4 rounded-lg border" :class="marginAnalysis.valid ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium">FIFO Margin Analysis</span>
                        <i :class="marginAnalysis.valid ? 'fas fa-check-circle text-emerald-600' : 'fas fa-exclamation-triangle text-red-600'"></i>
                    </div>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span>Average FIFO Cost:</span>
                            <span class="font-mono" x-text="formatPrice(marginAnalysis.avgCost)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Margin Percentage:</span>
                            <span class="font-mono font-bold"
                                  :class="marginAnalysis.percentage < 5 ? 'text-red-600' : 'text-emerald-600'"
                                  x-text="marginAnalysis.percentage + '%'"></span>
                        </div>
                    </div>
                </div>

                <!-- Tank Impact Summary -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span class="font-medium">Impact Summary</span>
                    </div>
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span>Affected Tanks:</span>
                            <span class="font-medium" x-text="getAffectedTanks().length"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Volume:</span>
                            <span class="font-mono" x-text="getTotalVolume().toLocaleString() + ' L'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Value Impact:</span>
                            <span class="font-mono font-bold text-blue-600" x-text="formatPrice(getValueImpact())"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Effective Date and Reason -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium">Effective Date</label>
                    <input type="date"
                           name="effective_from_date"
                           x-model="effectiveDate"
                           :min="new Date().toISOString().split('T')[0]"
                           required
                           class="input w-full">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium">Priority Level</label>
                    <select class="input w-full" x-model="priorityLevel">
                        <option value="normal">Normal Change</option>
                        <option value="urgent">Urgent Change</option>
                        <option value="emergency">Emergency Change</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium">Change Reason</label>
                <textarea name="change_reason"
                          x-model="changeReason"
                          required
                          rows="3"
                          maxlength="500"
                          placeholder="Explain the reason for this price change..."
                          class="input w-full"></textarea>
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span x-show="changeReason.length < 10" class="text-red-600">Minimum 10 characters required</span>
                    <span><span x-text="changeReason.length"></span>/500</span>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="flex gap-3 pt-4 border-t">
                <button type="button" @click="selectedFuel = null" class="btn btn-secondary flex-1">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="!isFormValid() || isSubmitting"
                        class="btn btn-primary flex-1"
                        :class="!isFormValid() || isSubmitting ? 'opacity-50 cursor-not-allowed' : ''">
                    <span x-show="!isSubmitting" class="flex items-center gap-2">
                        <i class="fas fa-save"></i>Update Price
                    </span>
                    <span x-show="isSubmitting" class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin"></i>Updating...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pricingManager() {
    return {
        selectedFuel: '{{ request('fuel_type', '') }}',
        priceInput: '',
        effectiveDate: new Date().toISOString().split('T')[0],
        changeReason: '',
        priorityLevel: 'normal',
        isSubmitting: false,

        // Validation states
        priceValidation: { valid: false, message: '' },
        changeAnalysis: { valid: true, amount: '0.0000', percentage: 0 },
        marginAnalysis: { valid: true, avgCost: 0, percentage: 0 },

        // Data from controller
        currentPrices: @json($current_prices),
        affectedTanks: @json($affected_tanks),
        recommendations: @json($pricing_recommendations ?? []),

        init() {
            if (this.selectedFuel) {
                this.loadRecommendedPrice();
            }
        },

        selectFuel(fuel) {
            this.selectedFuel = fuel;
            this.priceInput = '';
            this.loadRecommendedPrice();
            this.validatePrice();
        },

        loadRecommendedPrice() {
            if (this.recommendations[this.selectedFuel]) {
                this.priceInput = this.recommendations[this.selectedFuel].recommended_price;
                this.validatePrice();
            }
        },

        validatePrice() {
            if (!this.priceInput || this.priceInput <= 0) {
                this.priceValidation = { valid: false, message: 'Price required' };
                return;
            }

            const price = parseFloat(this.priceInput);

            if (price < 1) {
                this.priceValidation = { valid: false, message: 'Minimum 1.0000 required' };
                return;
            }

            if (price > 99999.9999) {
                this.priceValidation = { valid: false, message: 'Maximum 99,999.9999 allowed' };
                return;
            }

            // Check decimal places
            const priceStr = this.priceInput.toString();
            const decimalIndex = priceStr.indexOf('.');
            if (decimalIndex !== -1 && priceStr.length - decimalIndex - 1 > 4) {
                this.priceValidation = { valid: false, message: 'Maximum 4 decimal places' };
                return;
            }

            this.priceValidation = { valid: true, message: 'Valid price format' };
            this.analyzeChanges();
        },

        analyzeChanges() {
            const newPrice = parseFloat(this.priceInput);
            const currentPrice = this.getCurrentPrice();

            // Price change analysis
            if (currentPrice > 0) {
                const changeAmount = newPrice - currentPrice;
                const changePercentage = (changeAmount / currentPrice) * 100;

                this.changeAnalysis = {
                    valid: Math.abs(changePercentage) <= 20,
                    amount: (changeAmount >= 0 ? '+' : '') + changeAmount.toFixed(4),
                    percentage: changePercentage.toFixed(2)
                };
            } else {
                this.changeAnalysis = {
                    valid: true,
                    amount: 'New Price',
                    percentage: 0
                };
            }

            // FIFO margin analysis
            const avgCost = this.getAverageFifoCost();
            if (avgCost > 0) {
                const margin = ((newPrice - avgCost) / newPrice) * 100;
                this.marginAnalysis = {
                    valid: margin >= 5,
                    avgCost: avgCost,
                    percentage: margin.toFixed(2)
                };
            } else {
                this.marginAnalysis = {
                    valid: true,
                    avgCost: 0,
                    percentage: 0
                };
            }
        },

        async submitPrice() {
            if (!this.isFormValid()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Failed',
                    text: 'Please fix all validation errors before submitting.'
                });
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('station_id', '{{ $selected_station->id }}');
                formData.append('fuel_type', this.selectedFuel);
                formData.append('price_per_liter_ugx', parseFloat(this.priceInput).toFixed(4));
                formData.append('effective_from_date', this.effectiveDate);
                formData.append('change_reason', this.changeReason.trim());
                formData.append('validate_against_fifo', '1');

                const response = await fetch('{{ route('pricing.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Price Updated Successfully',
                        text: `${this.selectedFuel} price updated to ${this.formatPrice(this.priceInput)}`,
                        timer: 3000
                    }).then(() => {
                        window.location.href = '{{ route('pricing.index', ['station_id' => $selected_station->id]) }}';
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Update failed');
                }
            } catch (error) {
                this.isSubmitting = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: error.message
                });
            }
        },

        isFormValid() {
            return this.selectedFuel &&
                   this.priceInput &&
                   this.effectiveDate &&
                   this.changeReason.trim().length >= 10 &&
                   this.priceValidation.valid &&
                   this.changeAnalysis.valid &&
                   this.marginAnalysis.valid;
        },

        // Helper methods
        getCurrentPrice() {
            return this.currentPrices[this.selectedFuel]?.price_per_liter_ugx || 0;
        },

        getAffectedTanks() {
            return this.affectedTanks.filter(tank => tank.fuel_type === this.selectedFuel);
        },

        getTotalVolume() {
            return this.getAffectedTanks().reduce((sum, tank) => sum + parseFloat(tank.current_volume_liters || 0), 0);
        },

        getValueImpact() {
            const volumeImpact = this.getTotalVolume();
            const priceChange = parseFloat(this.priceInput || 0) - this.getCurrentPrice();
            return volumeImpact * priceChange;
        },

        getAverageFifoCost() {
            const tanks = this.getAffectedTanks().filter(tank => tank.avg_fifo_cost > 0);
            return tanks.length > 0 ? tanks.reduce((sum, tank) => sum + parseFloat(tank.avg_fifo_cost), 0) / tanks.length : 0;
        },

        getFuelStyles(fuel) {
            const styles = {
                petrol: { bg: 'bg-emerald-100', text: 'text-emerald-600' },
                diesel: { bg: 'bg-blue-100', text: 'text-blue-600' },
                kerosene: { bg: 'bg-amber-100', text: 'text-amber-600' }
            };
            return styles[fuel] || styles.petrol;
        },

        formatPrice(price) {
            return parseFloat(price || 0).toFixed(4);
        }
    }
}

// Error handling
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Validation Errors',
        html: '@foreach($errors->all() as $error)<div class="text-left">• {{ $error }}</div>@endforeach'
    });
});
@endif
</script>
@endsection
