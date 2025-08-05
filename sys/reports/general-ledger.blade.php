@extends('layouts.app')

@section('title', 'General Ledger & Trial Balance')
@section('page-header')
    <div>
        <h1 class="text-2xl font-bold text-foreground">General Ledger & Trial Balance</h1>
        <p class="text-sm text-muted-foreground">Financial reporting with mathematical precision</p>
    </div>
@endsection

@section('content')
<div x-data="generalLedgerApp()" x-init="init()" class="space-y-6">
    <!-- Clean Traditional Filter Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-300">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
                <button @click="resetFilters()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 bg-white border border-gray-400 rounded hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-refresh w-3 h-3 mr-2"></i>
                    Reset
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Station Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Station</label>
                    <select x-model="filters.station_ids" @change="applyFilters()"
                            class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500"
                            multiple size="3">
                        <template x-for="station in filterOptions.stations" :key="station.id">
                            <option :value="station.id" x-text="station.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Account Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                    <select x-model="filters.account_types" @change="applyFilters()"
                            class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500"
                            multiple size="3">
                        <template x-for="type in filterOptions.account_types" :key="type">
                            <option :value="type" x-text="type.replace('_', ' ').toUpperCase()"></option>
                        </template>
                    </select>
                </div>

                <!-- Fuel Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fuel Type</label>
                    <select x-model="filters.fuel_types" @change="applyFilters()"
                            class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500"
                            multiple size="3">
                        <template x-for="fuel in filterOptions.fuel_types" :key="fuel">
                            <option :value="fuel" x-text="fuel.replace('_', ' ').toUpperCase()"></option>
                        </template>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" x-model="filters.start_date" @change="applyFilters()"
                           class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" x-model="filters.end_date" @change="applyFilters()"
                           class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500">
                </div>

                <!-- Year Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                    <select x-model="filters.year" @change="applyFilters()"
                            class="w-full border border-gray-400 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:border-blue-500">
                        <option value="">All Years</option>
                        <template x-for="year in filterOptions.years" :key="year">
                            <option :value="year" x-text="year"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Navigation Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200/60 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200/60">
            <nav class="flex space-x-1 p-1" role="tablist">
                <button @click="activeTab = 'general_ledger'"
                        :class="activeTab === 'general_ledger' ? 'bg-white text-primary shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="flex-1 whitespace-nowrap py-3 px-6 font-semibold text-sm rounded-lg transition-all duration-200">
                    <i class="fas fa-book mr-2"></i>
                    General Ledger
                </button>
                <button @click="activeTab = 'trial_balance'"
                        :class="activeTab === 'trial_balance' ? 'bg-white text-primary shadow-sm border border-gray-200' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                        class="flex-1 whitespace-nowrap py-3 px-6 font-semibold text-sm rounded-lg transition-all duration-200">
                    <i class="fas fa-balance-scale mr-2"></i>
                    Trial Balance
                </button>
            </nav>
        </div>

        <!-- General Ledger Tab -->
        <div x-show="activeTab === 'general_ledger'" class="p-6 space-y-6">
            <!-- Responsive Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 mb-1 truncate">Total Entries</p>
                            <p class="text-lg font-bold text-gray-900 truncate" x-text="data.general_ledger?.pagination?.total || 0"></p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                            <i class="fas fa-list text-blue-600 text-sm"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 mb-1 truncate">Total Debits</p>
                            <p class="text-sm font-bold text-green-600 truncate" x-text="formatCurrency(data.general_ledger?.account_summaries?.reduce((sum, acc) => sum + acc.total_debits, 0) || 0)"></p>
                        </div>
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                            <i class="fas fa-plus text-green-600 text-sm"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 mb-1 truncate">Total Credits</p>
                            <p class="text-sm font-bold text-red-600 truncate" x-text="formatCurrency(data.general_ledger?.account_summaries?.reduce((sum, acc) => sum + acc.total_credits, 0) || 0)"></p>
                        </div>
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                            <i class="fas fa-minus text-red-600 text-sm"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 mb-1 truncate">Net Balance</p>
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="formatCurrency(data.general_ledger?.account_summaries?.reduce((sum, acc) => sum + acc.net_balance, 0) || 0)"></p>
                        </div>
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                            <i class="fas fa-balance-scale text-gray-600 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Clean Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-lg font-semibold text-gray-900">Transaction Details</h3>
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="searchTerm" @input="filterTable()" placeholder="Search transactions..."
                                   class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2 pl-10 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700 min-w-[120px]">Date</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700 min-w-[140px]">Account</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700 min-w-[120px]">Fuel Type</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700 min-w-[200px]">Description</th>
                                <th class="text-right py-4 px-6 text-sm font-semibold text-gray-700 min-w-[140px]">Debit</th>
                                <th class="text-right py-4 px-6 text-sm font-semibold text-gray-700 min-w-[140px]">Credit</th>
                                <th class="text-left py-4 px-6 text-sm font-semibold text-gray-700 min-w-[120px]">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template x-for="entry in filteredEntries" :key="entry.id">
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="py-4 px-6 text-sm text-gray-900 whitespace-nowrap" x-text="formatDate(entry.entry_date)"></td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                              :class="getAccountBadgeClass(entry.account_type)"
                                              x-text="entry.account_type.replace('_', ' ').toUpperCase()">
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-sm text-gray-600 whitespace-nowrap" x-text="entry.fuel_type.replace('_', ' ').toUpperCase()"></td>
                                    <td class="py-4 px-6 text-sm text-gray-900" x-text="entry.description"></td>
                                    <td class="py-4 px-6 text-sm text-right font-mono whitespace-nowrap"
                                        :class="entry.debit_amount_ugx > 0 ? 'text-green-600 font-semibold' : 'text-gray-400'"
                                        x-text="entry.debit_amount_ugx > 0 ? formatCurrency(entry.debit_amount_ugx) : '—'"></td>
                                    <td class="py-4 px-6 text-sm text-right font-mono whitespace-nowrap"
                                        :class="entry.credit_amount_ugx > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'"
                                        x-text="entry.credit_amount_ugx > 0 ? formatCurrency(entry.credit_amount_ugx) : '—'"></td>
                                    <td class="py-4 px-6 text-sm text-gray-500 font-mono whitespace-nowrap" x-text="entry.reference_table + '#' + entry.reference_id"></td>
                                </tr>
                            </template>
                            <template x-if="filteredEntries.length === 0">
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-500">
                                        <i class="fas fa-search text-gray-300 text-3xl mb-3"></i>
                                        <p class="text-sm">No transactions found matching your search.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Clean Pagination -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-show="data.general_ledger?.pagination">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium text-gray-900" x-text="((data.general_ledger?.pagination?.current_page - 1) * data.general_ledger?.pagination?.per_page) + 1"></span>
                        to <span class="font-medium text-gray-900" x-text="Math.min(data.general_ledger?.pagination?.current_page * data.general_ledger?.pagination?.per_page, data.general_ledger?.pagination?.total)"></span>
                        of <span class="font-medium text-gray-900" x-text="data.general_ledger?.pagination?.total"></span> entries
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="changePage('gl_page', data.general_ledger?.pagination?.current_page - 1)"
                                :disabled="data.general_ledger?.pagination?.current_page <= 1"
                                :class="data.general_ledger?.pagination?.current_page <= 1 ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-200 bg-white'"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg transition-colors duration-200">
                            <i class="fas fa-chevron-left mr-1"></i>
                            Previous
                        </button>
                        <button @click="changePage('gl_page', data.general_ledger?.pagination?.current_page + 1)"
                                :disabled="data.general_ledger?.pagination?.current_page >= data.general_ledger?.pagination?.last_page"
                                :class="data.general_ledger?.pagination?.current_page >= data.general_ledger?.pagination?.last_page ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-200 bg-white'"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg transition-colors duration-200">
                            Next
                            <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trial Balance Tab -->
        <div x-show="activeTab === 'trial_balance'" class="p-6 space-y-6">
            <!-- Mathematical Validation Card -->
            <div class="card p-4" :class="data.mathematical_validation?.is_mathematically_balanced ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                             :class="data.mathematical_validation?.is_mathematically_balanced ? 'bg-green-100' : 'bg-red-100'">
                            <i :class="data.mathematical_validation?.is_mathematically_balanced ? 'fas fa-check text-green-600' : 'fas fa-exclamation-triangle text-red-600'"></i>
                        </div>
                        <div>
                            <p class="font-semibold" :class="data.mathematical_validation?.is_mathematically_balanced ? 'text-green-800' : 'text-red-800'" x-text="data.mathematical_validation?.validation_status || 'UNKNOWN'"></p>
                            <p class="text-sm" :class="data.mathematical_validation?.is_mathematically_balanced ? 'text-green-600' : 'text-red-600'" x-text="data.mathematical_validation?.validation_message || 'No validation data'"></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">Balance Difference</p>
                        <p class="font-mono font-bold" :class="data.mathematical_validation?.is_mathematically_balanced ? 'text-green-600' : 'text-red-600'" x-text="formatCurrency(data.mathematical_validation?.imbalance_amount || 0)"></p>
                    </div>
                </div>
            </div>

            <!-- Grand Totals Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="card p-4">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-muted-foreground">Total Debits</p>
                            <p class="text-2xl font-bold text-foreground" x-text="formatCurrency(data.trial_balance?.grand_totals?.total_debits || 0)"></p>
                        </div>
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-muted-foreground">Total Credits</p>
                            <p class="text-2xl font-bold text-foreground" x-text="formatCurrency(data.trial_balance?.grand_totals?.total_credits || 0)"></p>
                        </div>
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-minus text-red-600"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-muted-foreground">Total Entries</p>
                            <p class="text-2xl font-bold text-foreground" x-text="data.trial_balance?.grand_totals?.total_entries || 0"></p>
                        </div>
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trial Balance Table -->
            <div class="card">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold text-foreground">Trial Balance Summary</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-6 text-sm font-medium text-muted-foreground">Account Name</th>
                                <th class="text-center py-3 px-6 text-sm font-medium text-muted-foreground">Type</th>
                                <th class="text-right py-3 px-6 text-sm font-medium text-muted-foreground">Total Debits</th>
                                <th class="text-right py-3 px-6 text-sm font-medium text-muted-foreground">Total Credits</th>
                                <th class="text-right py-3 px-6 text-sm font-medium text-muted-foreground">Balance</th>
                                <th class="text-center py-3 px-6 text-sm font-medium text-muted-foreground">Entries</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="account in data.trial_balance?.trial_balance_accounts || []" :key="account.account_type + account.fuel_type">
                                <tr class="border-b border-border hover:bg-accent">
                                    <td class="py-3 px-6 text-sm text-foreground" x-text="account.account_name"></td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="account.balance_type === 'DEBIT' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                              x-text="account.balance_type">
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-sm text-right font-mono" x-text="formatCurrency(account.total_debits)"></td>
                                    <td class="py-3 px-6 text-sm text-right font-mono" x-text="formatCurrency(account.total_credits)"></td>
                                    <td class="py-3 px-6 text-sm text-right font-mono font-bold"
                                        :class="account.account_balance >= 0 ? 'text-green-600' : 'text-red-600'"
                                        x-text="formatCurrency(Math.abs(account.account_balance))"></td>
                                    <td class="py-3 px-6 text-sm text-center text-muted-foreground" x-text="account.entry_count"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-border bg-accent font-bold">
                                <td class="py-3 px-6 text-sm text-foreground">TOTALS</td>
                                <td class="py-3 px-6"></td>
                                <td class="py-3 px-6 text-sm text-right font-mono text-green-600" x-text="formatCurrency(data.trial_balance?.grand_totals?.total_debits || 0)"></td>
                                <td class="py-3 px-6 text-sm text-right font-mono text-red-600" x-text="formatCurrency(data.trial_balance?.grand_totals?.total_credits || 0)"></td>
                                <td class="py-3 px-6 text-sm text-right font-mono"
                                    :class="data.trial_balance?.grand_totals?.is_balanced ? 'text-green-600' : 'text-red-600'"
                                    x-text="formatCurrency(data.trial_balance?.grand_totals?.difference || 0)"></td>
                                <td class="py-3 px-6 text-sm text-center" x-text="data.trial_balance?.grand_totals?.total_entries || 0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-background p-6 rounded-lg shadow-lg border border-border">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                <p class="text-foreground">Loading financial data...</p>
            </div>
        </div>
    </div>
</div>

<script>
function generalLedgerApp() {
    return {
        activeTab: 'general_ledger',
        loading: false,
        data: {},
        filterOptions: {},
        searchTerm: '',
        filteredEntries: [],
        filters: {
            station_ids: [],
            account_types: [],
            fuel_types: [],
            start_date: '',
            end_date: '',
            year: ''
        },

        get displayEntries() {
            return this.data.general_ledger?.detailed_entries || [];
        },

        init() {
            this.loadInitialData();
        },

        filterTable() {
            if (!this.searchTerm) {
                this.filteredEntries = this.displayEntries;
                return;
            }

            const term = this.searchTerm.toLowerCase();
            this.filteredEntries = this.displayEntries.filter(entry =>
                entry.description.toLowerCase().includes(term) ||
                entry.account_type.toLowerCase().includes(term) ||
                entry.fuel_type.toLowerCase().includes(term) ||
                entry.reference_table.toLowerCase().includes(term) ||
                entry.reference_id.toString().includes(term) ||
                this.formatDate(entry.entry_date).toLowerCase().includes(term)
            );
        },

        async loadInitialData() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("reports.general-ledger") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                    this.filterOptions = result.data.filter_options;
                    this.filteredEntries = this.displayEntries;
                    this.filterTable();
                } else {
                    this.showError('Failed to load data');
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        async applyFilters() {
            this.loading = true;
            try {
                const params = new URLSearchParams();

                if (this.filters.station_ids.length) params.append('station_ids[]', this.filters.station_ids);
                if (this.filters.account_types.length) params.append('account_types[]', this.filters.account_types);
                if (this.filters.fuel_types.length) params.append('fuel_types[]', this.filters.fuel_types);
                if (this.filters.start_date) params.append('start_date', this.filters.start_date);
                if (this.filters.end_date) params.append('end_date', this.filters.end_date);
                if (this.filters.year) params.append('year', this.filters.year);

                const response = await fetch(`{{ route("reports.general-ledger") }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                    this.filteredEntries = this.displayEntries;
                    this.filterTable();
                } else {
                    this.showError('Failed to apply filters');
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        async changePage(pageParam, pageNumber) {
            if (pageNumber < 1) return;

            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append(pageParam, pageNumber);

                // Add current filters
                if (this.filters.station_ids.length) params.append('station_ids[]', this.filters.station_ids);
                if (this.filters.account_types.length) params.append('account_types[]', this.filters.account_types);
                if (this.filters.fuel_types.length) params.append('fuel_types[]', this.filters.fuel_types);
                if (this.filters.start_date) params.append('start_date', this.filters.start_date);
                if (this.filters.end_date) params.append('end_date', this.filters.end_date);
                if (this.filters.year) params.append('year', this.filters.year);

                const response = await fetch(`{{ route("reports.general-ledger") }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                    this.filteredEntries = this.displayEntries;
                    this.filterTable();
                } else {
                    this.showError('Failed to load page');
                }
            } catch (error) {
                this.showError('Network error: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                station_ids: [],
                account_types: [],
                fuel_types: [],
                start_date: '',
                end_date: '',
                year: ''
            };
            this.loadInitialData();
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-UG', {
                style: 'currency',
                currency: 'UGX',
                minimumFractionDigits: 2
            }).format(amount || 0);
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('en-UG');
        },

        getAccountBadgeClass(accountType) {
            const classes = {
                'revenue': 'bg-green-100 text-green-800 border border-green-200',
                'cogs': 'bg-red-100 text-red-800 border border-red-200',
                'inventory': 'bg-blue-100 text-blue-800 border border-blue-200',
                'variance_loss': 'bg-orange-100 text-orange-800 border border-orange-200',
                'variance_gain': 'bg-purple-100 text-purple-800 border border-purple-200'
            };
            return classes[accountType] || 'bg-gray-100 text-gray-800 border border-gray-200';
        },

        showError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    timer: 5000,
                    showConfirmButton: false
                });
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endsection
