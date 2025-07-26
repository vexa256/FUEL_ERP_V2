<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FuelStation ERP') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script src="https://cdn.jsdelivr.net/npm/echarts@5.6.0/dist/echarts.min.js"></script>
    <!-- Include custom CSS framework -->
    @include('layouts.customcss')

    <!-- Page-specific styles -->
    @stack('styles')

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    // Global error handler for all fetch requests
window.addEventListener('unhandledrejection', function(event) {
    console.error('🚨 Unhandled Promise Rejection:', event.reason);

    // Show a toast notification for unhandled errors
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Unexpected Error',
            text: event.reason?.message || 'An unexpected error occurred',
            timer: 5000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }
});

// Global JavaScript error handler
window.addEventListener('error', function(event) {
    console.error('🚨 JavaScript Error:', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error
    });
});
</script>
</head>

<body class="h-full bg-background font-['Inter'] antialiased">
    <!-- Mobile Overlay -->
    <div id="mobile-overlay" class="mobile-overlay lg:hidden"></div>

    <!-- Include Sidebar -->
    @include('layouts.sidebar')

    <!-- Main Content Wrapper -->
    <div class="lg:ml-64 transition-all duration-300 ease-in-out">
        <!-- Header -->
        <header
            class="sticky top-0 z-40 flex h-16 items-center gap-4 border-b border-border bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/80 px-4 lg:px-6 shadow-sm">
            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="btn btn-ghost btn-sm lg:hidden hover:bg-accent">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <!-- Breadcrumb Navigation -->
            <nav
                class="hidden flex-col gap-6 text-lg font-medium md:flex md:flex-row md:items-center md:gap-5 md:text-sm lg:gap-6">
                <a href="#"
                    class="flex items-center gap-2 text-lg font-semibold md:text-base text-foreground hover:text-primary transition-colors duration-200">
                    <i class="fas fa-tachometer-alt h-4 w-4"></i>
                    <span>Dashboard</span>
                </a>
                @hasSection('breadcrumb')
                <i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
                @yield('breadcrumb')
                @endif
            </nav>

            <!-- Status Indicators -->
            <div class="hidden md:flex items-center gap-4 ml-6">
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse shadow-sm"></div>
                    <span class="text-muted-foreground font-medium">System Online</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <span class="text-muted-foreground font-medium">3 Active</span>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="ml-auto flex items-center gap-3">
                <!-- Search -->
                <div class="relative">
                    <i
                        class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                    <input type="search" placeholder="Search transactions..."
                        class="input pl-10 w-[200px] lg:w-[300px] text-sm border-input focus:ring-2 focus:ring-ring focus:border-transparent transition-all duration-200"
                        autocomplete="off" />
                </div>

                {{--
                <!-- Action Buttons -->
                <button class="btn btn-primary btn-sm gap-2 shadow-sm hover:shadow-md transition-all duration-200"
                    title="New Entry">
                    <i class="fas fa-plus h-3 w-3"></i>
                    <span class="hidden sm:inline">New</span>
                </button> --}}

                <!-- Notifications -->
                {{-- <div class="relative">
                    <button class="btn btn-ghost btn-sm relative hover:bg-accent transition-colors duration-200"
                        onclick="toggleTopDropdown('notifications-dropdown')">
                        <i class="fas fa-bell h-4 w-4"></i>
                        <span
                            class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-xs text-destructive-foreground font-medium shadow-sm">
                            3
                        </span>
                    </button>

                    <div class="top-dropdown" id="notifications-dropdown">
                        <div class="p-4 border-b border-border">
                            <h3 class="font-semibold text-foreground">Notifications</h3>
                            <p class="text-sm text-muted-foreground">3 unread notifications</p>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <div class="notification-item">
                                <div class="notification-icon notification-critical">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm text-foreground">Critical Variance Alert</p>
                                    <p class="text-xs text-muted-foreground">Tank variance exceeds threshold</p>
                                    <p class="text-xs text-muted-foreground mt-1">2 minutes ago</p>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-icon notification-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm text-foreground">Approval Pending</p>
                                    <p class="text-xs text-muted-foreground">Monthly report awaiting approval</p>
                                    <p class="text-xs text-muted-foreground mt-1">1 hour ago</p>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-icon notification-info">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm text-foreground">System Update</p>
                                    <p class="text-xs text-muted-foreground">Maintenance window scheduled</p>
                                    <p class="text-xs text-muted-foreground mt-1">3 hours ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-t border-border">
                            <a href="#" class="text-sm text-primary hover:underline font-medium">View all
                                notifications</a>
                        </div>
                    </div>
                </div> --}}

                <!-- User Menu -->
                <div class="relative">
                    <button
                        class="btn btn-ghost btn-sm rounded-full p-0 h-10 w-10 hover:bg-accent transition-colors duration-200"
                        onclick="toggleTopDropdown('user-dropdown')">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-medium shadow-sm">
                            SA
                        </div>
                    </button>

                    <div class="top-dropdown user-dropdown" id="user-dropdown">
                        <div class="p-4 border-b border-border">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground font-medium shadow-sm">
                                    SA
                                </div>
                                <div>
    <p class="font-medium text-sm text-foreground">{{ auth()->user()->name }}</p>
    <p class="text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground mt-1">
        {{ strtoupper(auth()->user()->role) }}
    </span>
</div>

                            </div>
                        </div>
                        <div>
                            {{-- <a href="#" class="user-menu-item">
                                <i class="fas fa-user user-menu-icon"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#" class="user-menu-item">
                                <i class="fas fa-cog user-menu-icon"></i>
                                <span>Settings</span>
                            </a>
                            <a href="#" class="user-menu-item">
                                <i class="fas fa-shield-alt user-menu-icon"></i>
                                <span>Security</span>
                            </a>
                            <a href="#" class="user-menu-item">
                                <i class="fas fa-history user-menu-icon"></i>
                                <span>Activity Log</span>
                            </a> --}}
                           <a href="#" class="user-menu-item text-destructive" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
    <i class="fas fa-sign-out-alt user-menu-icon"></i>
    <span>Sign Out</span>
</a>
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mx-4 mt-4 alert alert-success shadow-sm animate-in" id="flash-success">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle h-5 w-5 text-green-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="ml-4 flex-shrink-0 text-green-600 hover:text-green-800 transition-colors duration-200">
                    <i class="fas fa-times h-4 w-4"></i>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mx-4 mt-4 alert alert-error shadow-sm animate-in" id="flash-error">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle h-5 w-5 text-red-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="ml-4 flex-shrink-0 text-red-600 hover:text-red-800 transition-colors duration-200">
                    <i class="fas fa-times h-4 w-4"></i>
                </button>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="mx-4 mt-4 alert alert-warning shadow-sm animate-in" id="flash-warning">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle h-5 w-5 text-yellow-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="ml-4 flex-shrink-0 text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                    <i class="fas fa-times h-4 w-4"></i>
                </button>
            </div>
        </div>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 p-4 lg:p-6 space-y-6 min-h-[calc(100vh-4rem)]">
            <!-- Page Header -->
            @hasSection('page-header')
            <div class="flex items-center justify-between pb-4 border-b border-border">
                @yield('page-header')
            </div>
            @endif

            <!-- Content -->
            @yield('content')
        </main>
    </div>

    <!-- Quick Action Modal -->
    <div id="quick-action-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-background/80 backdrop-blur-sm">
        <div class="card mx-4 w-full max-w-lg p-6 shadow-xl border border-border animate-in">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-foreground">Quick Action</h2>
                <button onclick="closeQuickAction()"
                    class="btn btn-ghost btn-sm hover:bg-accent transition-colors duration-200">
                    <i class="fas fa-times h-4 w-4"></i>
                </button>
            </div>
            <form class="space-y-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Action Type</label>
                    <select class="select w-full">
                        <option value="">Select Action</option>
                        <option value="reading">New Reading</option>
                        <option value="delivery">Record Delivery</option>
                        <option value="variance">Report Variance</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-foreground">Details</label>
                    <textarea class="input min-h-[80px] w-full" placeholder="Enter details..."></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeQuickAction()" class="btn btn-secondary px-4 py-2">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script>
        // Mobile menu functionality
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        const menuToggle = document.getElementById('mobile-menu-toggle');

        function toggleSidebar() {
            sidebar?.classList.toggle('show');
            overlay?.classList.toggle('show');
        }

        function closeSidebar() {
            sidebar?.classList.remove('show');
            overlay?.classList.remove('show');
        }

        menuToggle?.addEventListener('click', toggleSidebar);
        overlay?.addEventListener('click', closeSidebar);

        // Sidebar dropdown functionality
        function toggleInnovativeDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const arrow = document.getElementById(dropdownId + '-arrow');
            const header = arrow?.closest('.menu-section-header');

            // Close other dropdowns
            document.querySelectorAll('.menu-dropdown').forEach(d => {
                if (d.id !== dropdownId) d.classList.remove('open');
            });
            document.querySelectorAll('.menu-arrow').forEach(a => {
                if (a.id !== dropdownId + '-arrow') a.classList.remove('open');
            });
            document.querySelectorAll('.menu-section-header').forEach(h => {
                if (h !== header) h.classList.remove('active');
            });

            // Toggle current
            dropdown?.classList.toggle('open');
            arrow?.classList.toggle('open');
            header?.classList.toggle('active');
        }

        // Top dropdown functionality
        function toggleTopDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);

            // Close other top dropdowns
            document.querySelectorAll('.top-dropdown').forEach(d => {
                if (d.id !== dropdownId) d.classList.remove('show');
            });

            dropdown?.classList.toggle('show');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.relative')) {
                document.querySelectorAll('.top-dropdown').forEach(d => d.classList.remove('show'));
            }
        });

        // Global keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSidebar();
                closeQuickAction();
                document.querySelectorAll('.menu-dropdown, .top-dropdown').forEach(d => d.classList.remove('open', 'show'));
                document.querySelectorAll('.menu-arrow').forEach(a => a.classList.remove('open'));
                document.querySelectorAll('.menu-section-header').forEach(h => h.classList.remove('active'));
            }
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[type="search"]')?.focus();
            }
        });

        // Modal functions
        function openQuickAction() {
            const modal = document.getElementById('quick-action-modal');
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }

        function closeQuickAction() {
            const modal = document.getElementById('quick-action-modal');
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }

        // Auto-hide flash messages
        setTimeout(() => {
            const flashMessages = document.querySelectorAll('[id^="flash-"]');
            flashMessages.forEach(msg => {
                msg.style.transition = 'all 0.5s ease';
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        // Enhanced interactions
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.98)';
            });
            btn.addEventListener('mouseup', function() {
                this.style.transform = 'scale(1)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>

    <!-- Page-specific scripts -->
    @stack('scripts')
</body>

</html>
