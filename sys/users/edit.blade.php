@extends('layouts.app')

@section('title', 'Edit User')

@section('breadcrumb')
<a href="{{ route('users.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Users</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<a href="{{ route('users.show', $user->id) }}" class="text-muted-foreground hover:text-foreground transition-colors">{{ $user->first_name }} {{ $user->last_name }}</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">Edit</span>
@endsection

@section('page-header')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Edit User</h1>
        <p class="mt-1 text-sm text-gray-600">Update {{ $user->first_name }} {{ $user->last_name }}'s information</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('users.show', $user->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
            <i class="fas fa-eye w-4 h-4 mr-2"></i>
            View User
        </a>
        <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
            <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
            Back to Users
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="userEditWizard()" class="max-w-4xl mx-auto">
    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <template x-for="(step, index) in steps" :key="index">
                <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center">
                        <div :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-200',
                            currentStep >= index + 1 ? 'bg-black text-white' : 'bg-gray-200 text-gray-600'
                        ]">
                            <i :class="step.icon"></i>
                        </div>
                        <div class="ml-3">
                            <p :class="[
                                'text-sm font-medium',
                                currentStep >= index + 1 ? 'text-gray-900' : 'text-gray-500'
                            ]" x-text="step.title"></p>
                            <p class="text-xs text-gray-500" x-text="step.description"></p>
                        </div>
                    </div>
                    <div x-show="index < steps.length - 1" class="flex-1 mx-4">
                        <div :class="[
                            'h-0.5 transition-all duration-200',
                            currentStep > index + 1 ? 'bg-black' : 'bg-gray-200'
                        ]"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <form method="POST" action="{{ route('users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <!-- Step 1: Personal Information -->
            <div x-show="currentStep === 1" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                    <p class="text-sm text-gray-600">Update the user's basic information and contact details.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="first_name"
                               maxlength="100"
                               value="{{ old('first_name', $user->first_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('first_name') ? 'border-red-300' : '' }}"
                               placeholder="Enter first name">
                        @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="last_name"
                               maxlength="100"
                               value="{{ old('last_name', $user->last_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('last_name') ? 'border-red-300' : '' }}"
                               placeholder="Enter last name">
                        @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               maxlength="255"
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('email') ? 'border-red-300' : '' }}"
                               placeholder="user@example.com">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel"
                               name="phone"
                               maxlength="20"
                               value="{{ old('phone', $user->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('phone') ? 'border-red-300' : '' }}"
                               placeholder="+256 700 123 456">
                        @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Step 2: Station & Role Assignment -->
            <div x-show="currentStep === 2" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Station & Role Assignment</h3>
                    <p class="text-sm text-gray-600">Update the user's station assignment and role permissions.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Station <span class="text-red-500">*</span>
                        </label>
                        <select name="station_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('station_id') ? 'border-red-300' : '' }}">
                            <option value="">Select Station</option>
                            @foreach($stations as $station)
                            <option value="{{ $station->id }}" {{ old('station_id', $user->station_id) == $station->id ? 'selected' : '' }}>{{ $station->name }} - {{ $station->location }}</option>
                            @endforeach
                        </select>
                        @error('station_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Employee ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="employee_id"
                               maxlength="50"
                               value="{{ old('employee_id', $user->employee_id) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('employee_id') ? 'border-red-300' : '' }}"
                               placeholder="Enter employee ID">
                        <p class="mt-1 text-xs text-gray-500">Must be unique per station.</p>
                        @error('employee_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($roles as $roleKey => $roleLabel)
                            <label class="relative flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors role-option" data-role="{{ $roleKey }}">
                                <input type="radio"
                                       name="role"
                                       value="{{ $roleKey }}"
                                       {{ old('role', $user->role) == $roleKey ? 'checked' : '' }}
                                       class="role-radio"
                                       onchange="updateRoleSelection('{{ $roleKey }}')">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full border-2 mr-3 transition-colors role-indicator {{ old('role', $user->role) == $roleKey ? 'border-black bg-black' : 'border-gray-300' }}">
                                        <div class="w-1.5 h-1.5 bg-white rounded-full mx-auto mt-0.5 role-dot {{ old('role', $user->role) == $roleKey ? '' : 'hidden' }}"></div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $roleLabel }}</p>
                                        <p class="text-xs text-gray-500">
                                            @switch($roleKey)
                                                @case('admin') Full system access and management @break
                                                @case('manager') Station management and oversight @break
                                                @case('supervisor') Daily operations supervision @break
                                                @case('attendant') Fuel dispensing and customer service @break
                                            @endswitch
                                        </p>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Step 3: Security Settings -->
            <div x-show="currentStep === 3" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
                    <p class="text-sm text-gray-600">Update the user's password and account status. Leave password blank to keep current password.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Password <span class="text-gray-400">(optional)</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('password') ? 'border-red-300' : '' }}"
                                   placeholder="Enter new password or leave blank">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Leave blank to keep current password.</p>
                        @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('password_confirmation') ? 'border-red-300' : '' }}"
                               placeholder="Confirm new password">
                        @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black">
                            <label for="is_active" class="ml-2 text-sm text-gray-700">
                                User account is active
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Unchecking will prevent the user from logging in.</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review & Submit -->
            <div x-show="currentStep === 4" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Review & Submit</h3>
                    <p class="text-sm text-gray-600">Please review all changes before updating the user account.</p>
                </div>

                @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-100 border border-red-400 text-red-700 p-4 text-sm">
                    <strong class="font-medium">Please fix the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Personal Information</h4>
                            <dl class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Name:</dt>
                                    <dd class="font-medium">{{ old('first_name', $user->first_name) }} {{ old('last_name', $user->last_name) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Email:</dt>
                                    <dd class="font-medium">{{ old('email', $user->email) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Phone:</dt>
                                    <dd class="font-medium">{{ old('phone', $user->phone) ?: 'Not provided' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Assignment</h4>
                            <dl class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Station:</dt>
                                    <dd class="font-medium">
                                        @php
                                            $selectedStationId = old('station_id', $user->station_id);
                                            $selectedStation = $stations->where('id', $selectedStationId)->first();
                                        @endphp
                                        {{ $selectedStation ? $selectedStation->name . ' - ' . $selectedStation->location : 'None selected' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Employee ID:</dt>
                                    <dd class="font-medium">{{ old('employee_id', $user->employee_id) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Role:</dt>
                                    <dd class="font-medium">
                                        @php
                                            $selectedRole = old('role', $user->role);
                                        @endphp
                                        {{ $roles[$selectedRole] ?? 'None selected' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Status:</dt>
                                    <dd class="{{ old('is_active', $user->is_active) ? 'text-green-600' : 'text-red-600' }} font-medium">
                                        {{ old('is_active', $user->is_active) ? 'Active' : 'Inactive' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <button type="button"
                        @click="previousStep()"
                        x-show="currentStep > 1"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    <i class="fas fa-chevron-left w-4 h-4 mr-2"></i>
                    Previous
                </button>

                <div class="flex space-x-3 ml-auto">
                    <a href="{{ route('users.show', $user->id) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Cancel
                    </a>

                    <button type="button"
                            @click="nextStep()"
                            x-show="currentStep < 4"
                            class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        Next
                        <i class="fas fa-chevron-right w-4 h-4 ml-2"></i>
                    </button>

                    <button type="submit"
                            x-show="currentStep === 4"
                            class="inline-flex items-center px-4 py-2 bg-black text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        <i class="fas fa-save w-4 h-4 mr-2"></i>
                        Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function userEditWizard() {
        return {
            currentStep: 1,
            showPassword: false,

            steps: [
                { title: 'Personal Info', description: 'Basic details', icon: 'fas fa-user' },
                { title: 'Station & Role', description: 'Assignment', icon: 'fas fa-building' },
                { title: 'Security', description: 'Password & status', icon: 'fas fa-lock' },
                { title: 'Review', description: 'Final check', icon: 'fas fa-check' }
            ],

            init() {
                // If there are validation errors, go to step 4 to show them
                @if($errors->any())
                this.currentStep = 4;
                @endif
            },

            nextStep() {
                if (this.currentStep < 4) {
                    this.currentStep++;
                }
            },

            previousStep() {
                if (this.currentStep > 1) {
                    this.currentStep--;
                }
            }
        }
    }

    // Role selection functionality
    function updateRoleSelection(selectedRole) {
        document.querySelectorAll('.role-option').forEach(option => {
            const role = option.dataset.role;
            const indicator = option.querySelector('.role-indicator');
            const dot = option.querySelector('.role-dot');

            if (role === selectedRole) {
                option.classList.add('border-black', 'bg-gray-50');
                option.classList.remove('border-gray-200');
                indicator.classList.add('border-black', 'bg-black');
                indicator.classList.remove('border-gray-300');
                dot.classList.remove('hidden');
            } else {
                option.classList.remove('border-black', 'bg-gray-50');
                option.classList.add('border-gray-200');
                indicator.classList.remove('border-black', 'bg-black');
                indicator.classList.add('border-gray-300');
                dot.classList.add('hidden');
            }
        });
    }
</script>
@endpush
