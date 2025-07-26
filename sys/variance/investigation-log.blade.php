@extends('layouts.app')

@section('title', 'Variance Investigation Log')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Investigation Log</h1>
        <p class="text-sm text-gray-500 mt-1">Complete history of variance investigations and resolutions</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('variance.index') }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="investigationLog()" x-init="init()" class="space-y-6">

    <!-- Filter Controls -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Station Selection -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Station</label>
                    <select x-model="selectedStation" @change="applyFilters()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        @foreach($accessible_stations as $station)
                            <option value="{{ $station->id }}" {{ $station->id == $station_id ? 'selected' : '' }}>
                                {{ $station->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Status</label>
                    <select x-model="statusFilter" @change="applyFilters()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="investigating">Investigating</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <!-- Severity Filter -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Severity</label>
                    <select x-model="severityFilter" @change="applyFilters()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                        <option value="">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-900">Search</label>
                    <input type="text" x-model="searchQuery" @input="applyFilters()"
                           placeholder="Search by tank, notes..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900 sm:text-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Timeline -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Investigation History</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Showing {{ $investigations->total() }} investigations
                        ({{ $investigations->firstItem() ?? 0 }}-{{ $investigations->lastItem() ?? 0 }})
                    </p>
                </div>
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span>Critical</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                        <span>High</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span>Medium</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span>Low</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            @forelse($investigations as $investigation)
                <div class="p-6 hover:bg-gray-50 transition-colors duration-150" x-data="{ expanded: false }">
                    <div class="flex items-start space-x-4">
                        <!-- Timeline Indicator -->
                        <div class="flex-shrink-0 mt-1">
                            @switch($investigation->severity)
                                @case('critical')
                                    <div class="w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    @break
                                @case('high')
                                    <div class="w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    @break
                                @case('medium')
                                    <div class="w-4 h-4 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    @break
                                @default
                                    <div class="w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                            @endswitch
                        </div>

                        <!-- Investigation Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Header -->
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-base font-semibold text-gray-900">{{ $investigation->title }}</h3>

                                        <!-- Severity Badge -->
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @switch($investigation->severity)
                                                @case('critical') bg-red-100 text-red-800 @break
                                                @case('high') bg-amber-100 text-amber-800 @break
                                                @case('medium') bg-yellow-100 text-yellow-800 @break
                                                @default bg-blue-100 text-blue-800
                                            @endswitch">
                                            {{ ucfirst($investigation->severity) }}
                                        </span>

                                        <!-- Status Badge -->
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @switch($investigation->status)
                                                @case('open') bg-red-100 text-red-800 @break
                                                @case('investigating') bg-blue-100 text-blue-800 @break
                                                @case('resolved') bg-green-100 text-green-800 @break
                                                @default bg-gray-100 text-gray-800
                                            @endswitch">
                                            {{ ucfirst($investigation->status) }}
                                        </span>
                                    </div>

                                    <!-- Tank and Variance Info -->
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</p>
                                            <p class="text-sm font-medium text-gray-900">Tank {{ $investigation->tank_number }}</p>
                                            <p class="text-xs text-gray-500 capitalize">{{ $investigation->fuel_type }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</p>
                                            <p class="text-sm font-semibold {{ $investigation->variance_percentage > 0 ? 'text-red-600' : 'text-blue-600' }}">
                                                {{ number_format($investigation->variance_percentage ?? 0, 2) }}%
                                            </p>
                                            <p class="text-xs text-gray-500">{{ number_format(abs($investigation->variance_magnitude ?? 0), 1) }}L</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date</p>
                                            <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($investigation->notification_date)->format('M j, Y') }}</p>
                                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($investigation->created_at)->format('g:i A') }}</p>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <p class="text-sm text-gray-600 mb-3">{{ $investigation->message }}</p>

                                    <!-- Resolution Info (if resolved) -->
                                    @if($investigation->status === 'resolved' && $investigation->resolution_notes)
                                        <div class="bg-green-50 border border-green-200 rounded-md p-3 mb-3">
                                            <div class="flex items-start space-x-2">
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-green-800">Resolution</p>
                                                    <p class="text-sm text-green-700">{{ $investigation->resolution_notes }}</p>
                                                    @if($investigation->resolver_first_name)
                                                        <p class="text-xs text-green-600 mt-1">
                                                            Resolved by {{ $investigation->resolver_first_name }} {{ $investigation->resolver_last_name }}
                                                            on {{ \Carbon\Carbon::parse($investigation->resolved_at)->format('M j, Y g:i A') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Timeline Footer -->
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span>Created {{ \Carbon\Carbon::parse($investigation->created_at)->diffForHumans() }}</span>
                                        @if($investigation->status !== 'resolved')
                                            <button @click="expanded = !expanded"
                                                    class="text-gray-900 hover:text-gray-700 font-medium">
                                                <span x-show="!expanded">Show Details</span>
                                                <span x-show="expanded">Hide Details</span>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Expanded Details -->
                                    <div x-show="expanded" x-transition.opacity class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p class="font-medium text-gray-900 mb-2">Investigation Status</p>
                                                <p class="text-gray-600">Current status: <span class="font-medium">{{ ucfirst($investigation->status) }}</span></p>
                                                @if($investigation->status === 'investigating')
                                                    <p class="text-blue-600 text-xs mt-1">Investigation in progress...</p>
                                                @elseif($investigation->status === 'open')
                                                    <p class="text-red-600 text-xs mt-1">Awaiting investigation</p>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 mb-2">Station Information</p>
                                                <p class="text-gray-600">{{ $investigation->station_name }}</p>
                                                <p class="text-xs text-gray-500 mt-1">Tank {{ $investigation->tank_number }} - {{ ucfirst($investigation->fuel_type) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Investigations Found</h3>
                    <p class="text-gray-500 mb-6 max-w-sm mx-auto">No variance investigations match your current filter criteria.</p>
                    <button @click="clearFilters()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors duration-200">
                        Clear All Filters
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($investigations->hasPages())
            <div class="bg-white px-6 py-3 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        @if($investigations->onFirstPage())
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-500 bg-white cursor-not-allowed">
                                Previous
                            </span>
                        @else
                            <a href="{{ $investigations->previousPageUrl() }}"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        @endif

                        @if($investigations->hasMorePages())
                            <a href="{{ $investigations->nextPageUrl() }}"
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        @else
                            <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-500 bg-white cursor-not-allowed">
                                Next
                            </span>
                        @endif
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ $investigations->firstItem() ?? 0 }}</span>
                                to <span class="font-medium">{{ $investigations->lastItem() ?? 0 }}</span>
                                of <span class="font-medium">{{ $investigations->total() }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                @if($investigations->onFirstPage())
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 cursor-not-allowed">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @else
                                    <a href="{{ $investigations->previousPageUrl() }}"
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @endif

                                @foreach($investigations->getUrlRange(1, $investigations->lastPage()) as $page => $url)
                                    @if($page == $investigations->currentPage())
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $url }}"
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach

                                @if($investigations->hasMorePages())
                                    <a href="{{ $investigations->nextPageUrl() }}"
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 cursor-not-allowed">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
function investigationLog() {
    return {
        selectedStation: '{{ $station_id }}',
        statusFilter: '',
        severityFilter: '',
        searchQuery: '',
        loading: false,

        init() {
            // Initialize any components if needed
        },

        applyFilters() {
            this.loading = true;

            // Build query parameters
            const params = new URLSearchParams();
            if (this.selectedStation) params.append('station_id', this.selectedStation);
            if (this.statusFilter) params.append('status', this.statusFilter);
            if (this.severityFilter) params.append('severity', this.severityFilter);
            if (this.searchQuery) params.append('search', this.searchQuery);

            // Navigate with filters
            window.location.href = `{{ route('variance.investigation-log') }}?${params.toString()}`;
        },

        clearFilters() {
            this.statusFilter = '';
            this.severityFilter = '';
            this.searchQuery = '';
            this.applyFilters();
        }
    }
}
</script>
@endsection
