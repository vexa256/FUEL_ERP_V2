<!-- Sidebar -->
<div id="sidebar"
    class="sidebar fixed top-0 left-0 z-50 h-screen w-64 bg-card/95 backdrop-blur-sm border-r border-border/50 lg:translate-x-0 flex flex-col shadow-xl">
    <!-- Header -->
    <div
        class="flex h-16 items-center border-b border-border/30 px-4 flex-shrink-0 bg-gradient-to-r from-primary/5 to-primary/10">
        <div class="flex items-center space-x-3">
            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary/80 text-primary-foreground shadow-lg">
                <i class="fas fa-gas-pump text-sm"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-foreground">FuelStation ERP</span>
                <span class="text-xs text-muted-foreground font-medium">Enterprise</span>
            </div>
        </div>
        <button id="sidebar-close"
            class="ml-auto button button-ghost h-8 w-8 lg:hidden hover:bg-accent/50 transition-all">
            <i class="fas fa-x text-xs"></i>
        </button>
    </div>

    <!-- Navigation - Scrollable Content -->
    <div class="sidebar-content flex-1 p-3">
        <nav class="space-y-2">
            <!-- 1. OVERVIEW - All Users -->
            <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-blue-500 to-blue-600">Core</span>
                        Overview
                    </span>
                </div>
                <a href="#"
                    class="menu-section-header active bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-primary/10 rounded-lg p-1.5">
                            <i class="fas fa-chart-line text-primary"></i>
                        </div>
                        <span class="font-medium">Dashboard</span>
                    </div>
                    <span class="badge-status bg-green-100 text-green-700 border border-green-200">Live</span>
                </a>
            </div>

              <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-red-500 to-red-600">Alert</span>
                        Monitoring & Alerts
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('variance-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-red-50 rounded-lg p-1.5">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <span class="font-medium">Accounting Module</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-count bg-red-100 text-red-700 border border-red-200">3</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="variance-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="variance-dropdown">
                    <a href="{{ route('reports.daily-profit-loss') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-bell w-3 h-3 mr-2 opacity-60 text-red-600"></i>
                        <span> Profit & Loss Statement</span>
                    </a>
                    <a href="{{ route('reconciliation.analytics') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-line w-3 h-3 mr-2 opacity-60"></i>
                        <span>Reconciliation Dashboard</span>
                    </a>
                    <a href="{{ route('cogs.dashboard') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-check-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Cost of Goods (COGS)</span>
                    </a>

                     <a href="{{ route('reports.inventory-analysis') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-check-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Inventory Analysis</span>
                    </a>



                </div>

                {{-- <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('notifications-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-yellow-50 rounded-lg p-1.5">
                            <i class="fas fa-bell text-yellow-600"></i>
                        </div>
                        <span class="font-medium">Notifications</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-count bg-yellow-100 text-yellow-700 border border-yellow-200">7</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="notifications-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="notifications-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-inbox w-3 h-3 mr-2 opacity-60 text-yellow-600"></i>
                        <span>All Notifications</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-exclamation-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Critical Alerts</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-oil-well w-3 h-3 mr-2 opacity-60"></i>
                        <span>Low Stock Alerts</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-cog w-3 h-3 mr-2 opacity-60"></i>
                        <span>Alert Settings</span>
                    </a>
                </div> --}}

                {{-- <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('system-health-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-pink-50 rounded-lg p-1.5">
                            <i class="fas fa-heartbeat text-pink-600"></i>
                        </div>
                        <span class="font-medium">System Health</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-green-100 text-green-700 border border-green-200">Online</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="system-health-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="system-health-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-pie w-3 h-3 mr-2 opacity-60 text-pink-600"></i>
                        <span>System Monitor</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-database w-3 h-3 mr-2 opacity-60"></i>
                        <span>Data Integrity</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-shield-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Security Logs</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-tools w-3 h-3 mr-2 opacity-60"></i>
                        <span>System Maintenance</span>
                    </a>
                </div> --}}
            </div>
            <!-- PHASE 1: Foundation Data Setup -->
            <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-blue-500 to-blue-600">Setup</span>
                        Foundation Setup
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('stations-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-blue-50 rounded-lg p-1.5">
                            <i class="fas fa-building text-blue-600"></i>
                        </div>
                        <span class="font-medium">Stations</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-blue-100 text-blue-700 border border-blue-200">Core</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="stations-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="stations-dropdown">
                    <a href="{{ route('stations.create') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-plus w-3 h-3 mr-2 opacity-60 text-blue-600"></i>
                        <span>Add Station</span>
                    </a>
                    <a href="{{ route('stations.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-list w-3 h-3 mr-2 opacity-60"></i>
                        <span>Manage Stations</span>
                    </a>
                    <a href="{{ route('stations.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-cog w-3 h-3 mr-2 opacity-60"></i>
                        <span>Station Settings</span>
                    </a>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('users-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-purple-50 rounded-lg p-1.5">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                        <span class="font-medium">Users & Employees</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-purple-100 text-purple-700 border border-purple-200">Core</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="users-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="users-dropdown">
                    <a href="{{ route('users.create') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-user-plus w-3 h-3 mr-2 opacity-60 text-purple-600"></i>
                        <span>Add Employee</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-users-cog w-3 h-3 mr-2 opacity-60"></i>
                        <span>Manage Employees</span>
                    </a>
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-shield-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Role Management</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-key w-3 h-3 mr-2 opacity-60"></i>
                        <span>Access Control</span>
                    </a> --}}
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('infrastructure-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-indigo-50 rounded-lg p-1.5">
                            <i class="fas fa-oil-well text-indigo-600"></i>
                        </div>
                        <span class="font-medium">Infrastructure</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-indigo-100 text-indigo-700 border border-indigo-200">Core</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="infrastructure-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="infrastructure-dropdown">
                    <a href="{{ route('tanks.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-plus w-3 h-3 mr-2 opacity-60 text-indigo-600"></i>
                        <span>Add Tank</span>
                    </a>
                    <a href="{{ route('tanks.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-list w-3 h-3 mr-2 opacity-60"></i>
                        <span>Manage Tanks</span>
                    </a>
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-tachometer-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Add Meter</span>
                    </a> --}}
                    <a href="{{ route('meters.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-tools w-3 h-3 mr-2 opacity-60"></i>
                        <span>Manage Meters</span>
                    </a>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('pricing-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-green-50 rounded-lg p-1.5">
                            <i class="fas fa-tags text-green-600"></i>
                        </div>
                        <span class="font-medium">Pricing Management</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-green-100 text-green-700 border border-green-200">Core</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="pricing-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="pricing-dropdown">
                    <a href="{{ route('pricing.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-dollar-sign w-3 h-3 mr-2 opacity-60 text-green-600"></i>
                        <span> Selling Prices</span>
                    </a>
                    <a href="{{ route('price-analysis.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-history w-3 h-3 mr-2 opacity-60"></i>
                        <span>Price Analytics</span>
                    </a>

                </div>
            </div>

            <!-- PHASE 2: Core Operations -->
            <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-emerald-500 to-emerald-600">Ops</span>
                        Daily Operations
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('deliveries-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-orange-50 rounded-lg p-1.5">
                            <i class="fas fa-truck text-orange-600"></i>
                        </div>
                        <span class="font-medium">Deliveries</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="badge-status bg-orange-100 text-orange-700 border border-orange-200">Auto-FIFO</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="deliveries-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="deliveries-dropdown">
                    <a href="{{ route('deliveries.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-plus w-3 h-3 mr-2 opacity-60 text-orange-600"></i>
                        <span> Deliveries</span>
                    </a>
                    <a href="{{ route('deliveries.create') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-list w-3 h-3 mr-2 opacity-60"></i>
                        <span>Create Delivery</span>
                    </a>
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-check-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Pending Approvals</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-layer-group w-3 h-3 mr-2 opacity-60"></i>
                        <span>View FIFO Layers</span>
                    </a> --}}
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('daily-readings-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-emerald-50 rounded-lg p-1.5">
                            <i class="fas fa-calendar-day text-emerald-600"></i>
                        </div>
                        <span class="font-medium">Daily Readings</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="badge-status bg-emerald-100 text-emerald-700 border border-emerald-200">Auto-Reconcile</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="daily-readings-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="daily-readings-dropdown">
                    <a href="{{ route('dip-readings.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-sun w-3 h-3 mr-2 opacity-60 text-emerald-600"></i>
                        <span>Daily Dip Readings</span>
                    </a>
                    {{-- <a href="{{ route('dip-readings.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-moon w-3 h-3 mr-2 opacity-60"></i>
                        <span>Evening Dip Readings</span>
                    </a> --}}
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-thermometer-half w-3 h-3 mr-2 opacity-60"></i>
                        <span>Temperature & Water</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-history w-3 h-3 mr-2 opacity-60"></i>
                        <span>Reading History</span>
                    </a> --}}
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('meter-readings-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-cyan-50 rounded-lg p-1.5">
                            <i class="fas fa-tachometer-alt text-cyan-600"></i>
                        </div>
                        <span class="font-medium">Meter Readings</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-cyan-100 text-cyan-700 border border-cyan-200">Auto-Sales</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="meter-readings-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="meter-readings-dropdown">
                    {{-- <a href="{{ route('meter-readings.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-plus w-3 h-3 mr-2 opacity-60 text-cyan-600"></i>
                        <span> Meter Reading</span>
                    </a> --}}
                    <a href="{{ route('meter-readings.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-list w-3 h-3 mr-2 opacity-60"></i>
                        <span>Daily Meter Readings</span>
                    </a>
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-exclamation-triangle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Meter Reset Detection</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-bar w-3 h-3 mr-2 opacity-60"></i>
                        <span>Sales Analytics</span>
                    </a> --}}
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('reconciliation-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-amber-50 rounded-lg p-1.5">
                            <i class="fas fa-balance-scale text-amber-600"></i>
                        </div>
                        <span class="font-medium">Reconciliation</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="badge-status bg-amber-100 text-amber-700 border border-amber-200">Auto-Generated</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="reconciliation-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="reconciliation-dropdown">
                    <a href="{{ route('reports.daily-reconciliation') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-eye w-3 h-3 mr-2 opacity-60 text-amber-600"></i>
                        <span>View Daily Reconciliation</span>
                    </a>
                    <a href="{{ route('reports.weekly-summary') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calendar-week w-3 h-3 mr-2 opacity-60"></i>
                        <span>Weekly Summary</span>
                    </a>
                    <a href="{{ route('reports.monthly-summary') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calendar-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Monthly Summary</span>
                    </a>
                    <a href="{{ route('reconciliation-analysis.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calculator w-3 h-3 mr-2 opacity-60"></i>
                        <span>Reconcliation Analysis</span>
                    </a>
                </div>
            </div>

            <!-- PHASE 3: Monitoring & Alerts -->
            <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-red-500 to-red-600">Alert</span>
                        Monitoring & Alerts
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('variance-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-red-50 rounded-lg p-1.5">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <span class="font-medium">Variance Management</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-count bg-red-100 text-red-700 border border-red-200">3</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="variance-dropdown2-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="variance-dropdown2">
                    <a href="{{ route('variance.index') }}" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-bell w-3 h-3 mr-2 opacity-60 text-red-600"></i>
                        <span>Variance Dashboard</span>
                    </a>
                    {{-- <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-line w-3 h-3 mr-2 opacity-60"></i>
                        <span>Variance Trends</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-check-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Approve Variances</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-search w-3 h-3 mr-2 opacity-60"></i>
                        <span>Investigation Log</span>
                    </a> --}}
                </div>

                {{-- <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('notifications-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-yellow-50 rounded-lg p-1.5">
                            <i class="fas fa-bell text-yellow-600"></i>
                        </div>
                        <span class="font-medium">Notifications</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-count bg-yellow-100 text-yellow-700 border border-yellow-200">7</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="notifications-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="notifications-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-inbox w-3 h-3 mr-2 opacity-60 text-yellow-600"></i>
                        <span>All Notifications</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-exclamation-circle w-3 h-3 mr-2 opacity-60"></i>
                        <span>Critical Alerts</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-oil-well w-3 h-3 mr-2 opacity-60"></i>
                        <span>Low Stock Alerts</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-cog w-3 h-3 mr-2 opacity-60"></i>
                        <span>Alert Settings</span>
                    </a>
                </div> --}}

                {{-- <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('system-health-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-pink-50 rounded-lg p-1.5">
                            <i class="fas fa-heartbeat text-pink-600"></i>
                        </div>
                        <span class="font-medium">System Health</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-green-100 text-green-700 border border-green-200">Online</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="system-health-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="system-health-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-pie w-3 h-3 mr-2 opacity-60 text-pink-600"></i>
                        <span>System Monitor</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-database w-3 h-3 mr-2 opacity-60"></i>
                        <span>Data Integrity</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-shield-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Security Logs</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-tools w-3 h-3 mr-2 opacity-60"></i>
                        <span>System Maintenance</span>
                    </a>
                </div> --}}
            </div>

            <!-- PHASE 4: Financial Reporting -->
            {{-- <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-violet-500 to-violet-600">Report</span>
                        Financial Reports
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('financial-reports-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-violet-50 rounded-lg p-1.5">
                            <i class="fas fa-chart-bar text-violet-600"></i>
                        </div>
                        <span class="font-medium">Financial Reports</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="badge-status bg-violet-100 text-violet-700 border border-violet-200">Auto-Gen</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="financial-reports-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="financial-reports-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-file-invoice-dollar w-3 h-3 mr-2 opacity-60 text-violet-600"></i>
                        <span>P&L Statements</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calendar-day w-3 h-3 mr-2 opacity-60"></i>
                        <span>Daily Summaries</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calendar-week w-3 h-3 mr-2 opacity-60"></i>
                        <span>Weekly Reports</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-calendar-alt w-3 h-3 mr-2 opacity-60"></i>
                        <span>Monthly Reports</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-line w-3 h-3 mr-2 opacity-60"></i>
                        <span>Yearly Analysis</span>
                    </a>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('financial-ledger-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-slate-50 rounded-lg p-1.5">
                            <i class="fas fa-book text-slate-600"></i>
                        </div>
                        <span class="font-medium">Financial Ledger</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-slate-100 text-slate-700 border border-slate-200">Live</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="financial-ledger-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="financial-ledger-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-list w-3 h-3 mr-2 opacity-60 text-slate-600"></i>
                        <span>General Ledger</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-coins w-3 h-3 mr-2 opacity-60"></i>
                        <span>Revenue Entries</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-receipt w-3 h-3 mr-2 opacity-60"></i>
                        <span>COGS Entries</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-warehouse w-3 h-3 mr-2 opacity-60"></i>
                        <span>Inventory Entries</span>
                    </a>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('cost-analysis-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-teal-50 rounded-lg p-1.5">
                            <i class="fas fa-calculator text-teal-600"></i>
                        </div>
                        <span class="font-medium">Cost Analysis</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-teal-100 text-teal-700 border border-teal-200">FIFO</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="cost-analysis-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="cost-analysis-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-layer-group w-3 h-3 mr-2 opacity-60 text-teal-600"></i>
                        <span>FIFO Cost Tracking</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-chart-pie w-3 h-3 mr-2 opacity-60"></i>
                        <span>Margin Analysis</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-trending-up w-3 h-3 mr-2 opacity-60"></i>
                        <span>Profit Trends</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-dollar-sign w-3 h-3 mr-2 opacity-60"></i>
                        <span>Price Impact Analysis</span>
                    </a>
                </div>
            </div> --}}

            <!-- PHASE 5: Audit & Compliance -->
            {{-- <div class="menu-section">
                <div class="mb-3 px-2">
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center">
                        <span class="phase-badge mr-2 bg-gradient-to-r from-gray-500 to-gray-600">Audit</span>
                        Audit & Compliance
                    </span>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('audit-trails-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-gray-50 rounded-lg p-1.5">
                            <i class="fas fa-search text-gray-600"></i>
                        </div>
                        <span class="font-medium">Audit Trails</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="badge-status bg-gray-100 text-gray-700 border border-gray-200">Secure</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="audit-trails-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="audit-trails-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-clipboard-list w-3 h-3 mr-2 opacity-60 text-gray-600"></i>
                        <span>Audit Logs</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-user-check w-3 h-3 mr-2 opacity-60"></i>
                        <span>User Activity</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-fingerprint w-3 h-3 mr-2 opacity-60"></i>
                        <span>Hash Chain Integrity</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-shield-virus w-3 h-3 mr-2 opacity-60"></i>
                        <span>Fraud Detection</span>
                    </a>
                </div>

                <div class="menu-section-header hover:bg-accent/50 transition-all"
                    onclick="toggleInnovativeDropdown('compliance-dropdown')">
                    <div class="flex items-center space-x-3">
                        <div class="menu-icon bg-emerald-50 rounded-lg p-1.5">
                            <i class="fas fa-check-double text-emerald-600"></i>
                        </div>
                        <span class="font-medium">Compliance</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="badge-status bg-emerald-100 text-emerald-700 border border-emerald-200">Verified</span>
                        <i class="fas fa-chevron-right menu-arrow transition-transform duration-200"
                            id="compliance-dropdown-arrow"></i>
                    </div>
                </div>
                <div class="menu-dropdown bg-accent/10 rounded-lg" id="compliance-dropdown">
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-file-contract w-3 h-3 mr-2 opacity-60 text-emerald-600"></i>
                        <span>Regulatory Reports</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-balance-scale w-3 h-3 mr-2 opacity-60"></i>
                        <span>Compliance Status</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-certificate w-3 h-3 mr-2 opacity-60"></i>
                        <span>Audit Certificates</span>
                    </a>
                    <a href="#" class="menu-dropdown-item hover:bg-accent/30 transition-colors">
                        <i class="fas fa-download w-3 h-3 mr-2 opacity-60"></i>
                        <span>Export Data</span>
                    </a>
                </div>
            </div> --}}


        </nav>
    </div>
</div>
