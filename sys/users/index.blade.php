@extends('layouts.app')

@section('title', 'Users Management')

@section('page-header')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Users Management</h1>
        <p class="mt-1 text-sm text-gray-600">Manage system users and their permissions</p>
    </div>
    <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
        <i class="fas fa-plus w-4 h-4 mr-2"></i>
        Add User
    </a>
</div>
@endsection

@push('scripts')
<script>
    function usersManager() {
        return {
            activeTab: 'list',
            search: '{{ $search }}',
            stationFilter: '{{ $stationFilter }}',
            roleFilter: '{{ $roleFilter }}',
            statusFilter: '{{ $statusFilter }}',
            sortBy: '{{ $sortBy }}',
            sortOrder: '{{ $sortOrder }}',
            selectedUsers: [],

            applyFilters() {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                if (this.stationFilter) params.set('station_id', this.stationFilter);
                if (this.roleFilter) params.set('role', this.roleFilter);
                if (this.statusFilter !== '') params.set('status', this.statusFilter);
                if (this.sortBy !== 'created_at') params.set('sort_by', this.sortBy);
                if (this.sortOrder !== 'desc') params.set('sort_order', this.sortOrder);

                window.location.href = '{{ route('users.index') }}?' + params.toString();
            },

            resetFilters() {
                this.search = '';
                this.stationFilter = '';
                this.roleFilter = '';
                this.statusFilter = '';
                this.sortBy = 'created_at';
                this.sortOrder = 'desc';
                window.location.href = '{{ route('users.index') }}';
            },

            toggleStatus(userId, currentStatus) {
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
                        this.performStatusToggle(userId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    this.showSuccessMessage(data.message);
                                } else {
                                    throw new Error(data.error || 'Failed to update user status');
                                }
                            })
                            .catch(error => {
                                this.showErrorMessage(error.message || 'Failed to update user status');
                            });
                    }
                });
            },

            performStatusToggle(userId) {
                return fetch(`{{ route('users.index') }}/${userId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
            },

            deleteUser(userId, userName) {
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
                        this.performDelete(userId);
                    }
                });
            },

            performDelete(userId) {
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
            },

            showSuccessMessage(message) {
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonColor: '#000000'
                }).then(() => {
                    window.location.reload();
                });
            },

            showErrorMessage(message) {
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonColor: '#000000'
                });
            }
        }
    }
</script>
@endpush

@section('content')
<div x-data="usersManager()" class="space-y-6">

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'list'"
                    :class="activeTab === 'list' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-list mr-2"></i>
                User List
            </button>
            <button @click="activeTab = 'filters'"
                    :class="activeTab === 'filters' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-filter mr-2"></i>
                Filters & Search
            </button>
            <button @click="activeTab = 'analytics'"
                    :class="activeTab === 'analytics' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-chart-bar mr-2"></i>
                Analytics
            </button>
        </nav>
    </div>

    <!-- Filters Tab -->
    <div x-show="activeTab === 'filters'" class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                <input type="text"
                       x-model="search"
                       placeholder="Name, email, employee ID..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Station</label>
                <select x-model="stationFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Stations</option>
                    @foreach($stations as $station)
                    <option value="{{ $station->id }}">{{ $station->name }} - {{ $station->location }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select x-model="roleFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Roles</option>
                    <option value="admin">System Administrator</option>
                    <option value="manager">Station Manager</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="attendant">Fuel Attendant</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select x-model="sortBy" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                        <option value="created_at">Date Created</option>
                        <option value="first_name">First Name</option>
                        <option value="last_name">Last Name</option>
                        <option value="email">Email</option>
                        <option value="role">Role</option>
                        <option value="last_login_at">Last Login</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                    <select x-model="sortOrder" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
            </div>

            <div class="flex space-x-3">
                <button @click="resetFilters()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    <i class="fas fa-undo mr-2"></i>
                    Reset
                </button>
                <button @click="applyFilters()" class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                    <i class="fas fa-search mr-2"></i>
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Tab -->
    <div x-show="activeTab === 'analytics'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $pagination['total'] }}</p>
                </div>
            </div>
        </div>

        @foreach(['admin' => ['Shield Alt', 'red'], 'manager' => ['User Tie', 'blue'], 'supervisor' => ['User Check', 'green'], 'attendant' => ['User', 'yellow']] as $role => $config)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-{{ $config[1] }}-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-{{ strtolower(str_replace(' ', '-', $config[0])) }} text-{{ $config[1] }}-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ ucfirst($role) }}s</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $roleStats->get($role)->count ?? 0 }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Users List Tab -->
    <div x-show="activeTab === 'list'" class="bg-white rounded-lg border border-gray-200">
        <!-- Table Header with Results Info -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-medium text-gray-900">Users</h3>
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">{{ $pagination['total'] }} total</span>
                </div>

                @if($search || $stationFilter || $roleFilter || $statusFilter !== '')
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Filtered results</span>
                    <button @click="resetFilters()" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-times mr-1"></i>Clear filters
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Station</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700">{{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-400">ID: {{ $user->employee_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->station_name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->station_location }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @switch($user->role)
                                    @case('admin') bg-red-100 text-red-800 @break
                                    @case('manager') bg-blue-100 text-blue-800 @break
                                    @case('supervisor') bg-green-100 text-green-800 @break
                                    @case('attendant') bg-yellow-100 text-yellow-800 @break
                                @endswitch">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye w-4 h-4"></i>
                                </a>
                                <a href="{{ route('users.edit', $user->id) }}" class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-edit w-4 h-4"></i>
                                </a>
                                <button @click="toggleStatus({{ $user->id }}, {{ $user->is_active ? 'true' : 'false' }})"
                                        class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }} w-4 h-4"></i>
                                </button>
                                <button @click="deleteUser({{ $user->id }}, '{{ $user->full_name }}')"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No users found</p>
                                <p class="text-sm">Try adjusting your search criteria or add a new user.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($pagination['total_pages'] > 1)
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 }} to
                    {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} of
                    {{ $pagination['total'] }} results
                </div>

                <div class="flex items-center space-x-2">
                    @if($pagination['has_prev'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}"
                       class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    @endif

                    @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                       class="px-3 py-2 text-sm border {{ $i == $pagination['current_page'] ? 'bg-black text-white border-black' : 'border-gray-300 hover:bg-gray-50' }} rounded-md">
                        {{ $i }}
                    </a>
                    @endfor

                    @if($pagination['has_next'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}"
                       class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
