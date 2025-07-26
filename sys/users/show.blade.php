@extends('layouts.app')

@section('title', $user->full_name)

@section('breadcrumb')
<a href="{{ route('users.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Users</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">{{ $user->full_name }}</span>
@endsection

@section('page-header')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center space-x-4">
        <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
            <span class="text-xl font-medium text-gray-700">{{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}</span>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $user->full_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $user->email }} • {{ ucfirst($user->role) }}</p>
            <div class="flex items-center mt-1">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="ml-2 text-xs text-gray-500">ID: {{ $user->employee_id }}</span>
            </div>
        </div>
    </div>
    <div class="flex space-x-3">
        <button onclick="toggleUserStatus({{ $user->id }}, {{ $user->is_active ? 'true' : 'false' }})"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
            <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }} w-4 h-4 mr-2"></i>
            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
        </button>
        <a href="{{ route('users.edit', $user->id) }}"
           class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
            <i class="fas fa-edit w-4 h-4 mr-2"></i>
            Edit User
        </a>
        <button onclick="deleteUser({{ $user->id }}, '{{ $user->full_name }}')"
                class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 text-sm font-medium rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <i class="fas fa-trash w-4 h-4 mr-2"></i>
            Delete
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ activeTab: 'overview' }" class="space-y-6">
    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-user mr-2"></i>
                Overview
            </button>
            <button @click="activeTab = 'details'"
                    :class="activeTab === 'details' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                Details
            </button>
            <button @click="activeTab = 'activity'"
                    :class="activeTab === 'activity' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-chart-line mr-2"></i>
                Activity
            </button>
        </nav>
    </div>

    <!-- Overview Tab -->
    <div x-show="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Personal Information Card -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">First Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->first_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->last_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->phone ?: 'Not provided' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Quick Stats Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Account Status</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Role</span>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                        @switch($user->role)
                            @case('admin') bg-red-100 text-red-800 @break
                            @case('manager') bg-blue-100 text-blue-800 @break
                            @case('supervisor') bg-green-100 text-green-800 @break
                            @case('attendant') bg-yellow-100 text-yellow-800 @break
                        @endswitch">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Last Login</span>
                    <span class="text-sm text-gray-900">{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Member Since</span>
                    <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Tab -->
    <div x-show="activeTab === 'details'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Station Assignment Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Station Assignment</h3>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Station Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->station_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Location</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->station_location }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Employee ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->employee_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Currency</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->currency_code }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Timezone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->timezone }}</dd>
                </div>
            </dl>
        </div>

        <!-- Account Details Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Account Details</h3>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">User ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">#{{ $user->id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y \a\t g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($user->updated_at)->format('F d, Y \a\t g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($user->last_login_at)
                            {{ \Carbon\Carbon::parse($user->last_login_at)->format('F d, Y \a\t g:i A') }}
                            <span class="text-gray-500">({{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }})</span>
                        @else
                            <span class="text-gray-500">Never logged in</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Activity Tab -->
    <div x-show="activeTab === 'activity'" class="space-y-6">
        <!-- Activity Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-sign-in-alt text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Logins</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $activityStats['total_logins'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Last Activity</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $activityStats['last_activity'] ? \Carbon\Carbon::parse($activityStats['last_activity'])->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Days Since Login</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $activityStats['days_since_last_login'] ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
            @if($recentActivities->isEmpty())
            <div class="text-center py-8">
                <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                <p class="text-lg font-medium text-gray-500">No recent activity</p>
                <p class="text-sm text-gray-400">Activity logs will appear here when available.</p>
            </div>
            @else
            <div class="space-y-4">
                @foreach($recentActivities as $activity)
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleUserStatus(userId, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';

        Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} User?`,
            text: `Are you sure you want to ${action} this user?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000000',
            cancelButtonColor: '#6b7280',
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ route('users.index') }}/${userId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000000'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.error || 'Failed to update user status');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to update user status',
                        icon: 'error',
                        confirmButtonColor: '#000000'
                    });
                });
            }
        });
    }

    function deleteUser(userId, userName) {
        Swal.fire({
            title: 'Delete User?',
            text: `Are you sure you want to delete ${userName}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('users.index') }}/${userId}`;

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';

                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';

                form.appendChild(methodField);
                form.appendChild(tokenField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush
