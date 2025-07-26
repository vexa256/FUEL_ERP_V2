@extends('layouts.app')

@section('title', 'Add New User')

@section('breadcrumb')
<a href="{{ route('users.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Users</a>
<i class="fas fa-chevron-right h-3 w-3 text-muted-foreground"></i>
<span class="text-foreground font-medium">Add New User</span>
@endsection

@section('page-header')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Add New User</h1>
        <p class="mt-1 text-sm text-gray-600">Create a new user account with proper permissions</p>
    </div>
    <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
        Back to Users
    </a>
</div>
@endsection

@section('content')
<div x-data="userCreateWizard()" class="max-w-4xl mx-auto">
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
        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <!-- Step 1: Personal Information -->
            <div x-show="currentStep === 1" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                    <p class="text-sm text-gray-600">Enter the user's basic information and contact details.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="formData.first_name"
                               name="first_name"
                               maxlength="100"
                               value="{{ old('first_name') }}"
                               @input="syncFieldToAlpine('first_name', $event.target.value)"
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
                               x-model="formData.last_name"
                               name="last_name"
                               maxlength="100"
                               value="{{ old('last_name') }}"
                               @input="syncFieldToAlpine('last_name', $event.target.value)"
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
                               x-model="formData.email"
                               name="email"
                               maxlength="255"
                               value="{{ old('email') }}"
                               @input="syncFieldToAlpine('email', $event.target.value)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('email') ? 'border-red-300' : '' }}"
                               placeholder="user@example.com">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel"
                               x-model="formData.phone"
                               name="phone"
                               maxlength="20"
                               value="{{ old('phone') }}"
                               @input="syncFieldToAlpine('phone', $event.target.value)"
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
                    <p class="text-sm text-gray-600">Assign the user to a station and define their role and permissions.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Station <span class="text-red-500">*</span>
                        </label>
                        <select x-model="formData.station_id"
                                name="station_id"
                                @change="syncFormData()"
                                onchange="updateStationSelection()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('station_id') ? 'border-red-300' : '' }}">
                            <option value="">Select Station</option>
                            @foreach($stations as $station)
                            <option value="{{ $station->id }}" {{ old('station_id') == $station->id ? 'selected' : '' }}>{{ $station->name }} - {{ $station->location }}</option>
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
                               x-model="formData.employee_id"
                               name="employee_id"
                               maxlength="50"
                               value="{{ old('employee_id') }}"
                               @input="syncFieldToAlpine('employee_id', $event.target.value)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('employee_id') ? 'border-red-300' : '' }}"
                               placeholder="Auto-generated or custom">
                        <p class="mt-1 text-xs text-gray-500">Unique per station. Auto-generated if left empty.</p>
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
                                       {{ old('role') == $roleKey ? 'checked' : '' }}
                                       class="role-radio"
                                       onchange="updateRoleSelection('{{ $roleKey }}')">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full border-2 mr-3 transition-colors role-indicator {{ old('role') == $roleKey ? 'border-black bg-black' : 'border-gray-300' }}">
                                        <div class="w-1.5 h-1.5 bg-white rounded-full mx-auto mt-0.5 role-dot {{ old('role') == $roleKey ? '' : 'hidden' }}"></div>
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
                    <p class="text-sm text-gray-600">Set up the user's password and account status.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-model="formData.password"
                                   name="password"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('password') ? 'border-red-300' : '' }}"
                                   placeholder="Enter secure password">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password"
                               x-model="formData.password_confirmation"
                               name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-colors {{ $errors->has('password_confirmation') ? 'border-red-300' : '' }}"
                               placeholder="Confirm password">
                        @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                        <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox"
                                   x-model="formData.is_active"
                                   name="is_active"
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-black border-gray-300 rounded focus:ring-black">
                            <label for="is_active" class="ml-2 text-sm text-gray-700">
                                Activate user account immediately
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">If unchecked, the user will need to be activated manually before they can log in.</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review & Submit -->
            <div x-show="currentStep === 4" class="p-6 space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-medium text-gray-900">Review & Submit</h3>
                    <p class="text-sm text-gray-600">Please review all information before creating the user account.</p>
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
                                    <dd class="font-medium" x-text="formData.first_name + ' ' + formData.last_name"></dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Email:</dt>
                                    <dd class="font-medium" x-text="formData.email"></dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Phone:</dt>
                                    <dd class="font-medium" x-text="formData.phone || 'Not provided'"></dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Assignment</h4>
                            <dl class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Station:</dt>
                                    <dd class="font-medium" x-text="getStationName(formData.station_id)"></dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Employee ID:</dt>
                                    <dd class="font-medium" x-text="formData.employee_id"></dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Role:</dt>
                                    <dd class="font-medium" x-text="getRoleLabel(formData.role)"></dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-600">Status:</dt>
                                    <dd :class="formData.is_active ? 'text-green-600' : 'text-red-600'"
                                        class="font-medium"
                                        x-text="formData.is_active ? 'Active' : 'Inactive'"></dd>
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
                    <a href="{{ route('users.index') }}"
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
                        <i class="fas fa-user-plus w-4 h-4 mr-2"></i>
                        Create User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function userCreateWizard() {
        return {
            currentStep: 1,
            showPassword: false,
            passwordStrength: 0,

            steps: [
                { title: 'Personal Info', description: 'Basic details', icon: 'fas fa-user' },
                { title: 'Station & Role', description: 'Assignment', icon: 'fas fa-building' },
                { title: 'Security', description: 'Password setup', icon: 'fas fa-lock' },
                { title: 'Review', description: 'Final check', icon: 'fas fa-check' }
            ],

            formData: {
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                station_id: '',
                employee_id: '',
                role: '',
                password: '',
                password_confirmation: '',
                is_active: true
            },

            errors: {},

            stations: @json($stations),
            roles: @json($roles),

            nextStep() {
                if (this.canProceed()) {
                    this.currentStep++;
                }
            },

            previousStep() {
                if (this.currentStep > 1) {
                    this.currentStep--;
                }
            },

            canProceed() {
                switch(this.currentStep) {
                    case 1:
                        return this.validateStep1();
                    case 2:
                        return this.validateStep2();
                    case 3:
                        return this.validateStep3();
                    default:
                        return false;
                }
            },

            validateStep1() {
                const step1Valid = this.formData.first_name &&
                                  this.formData.last_name &&
                                  this.formData.email &&
                                  this.isValidEmail(this.formData.email);
                return step1Valid && !this.errors.first_name && !this.errors.last_name && !this.errors.email;
            },

            validateStep2() {
                const step2Valid = this.formData.station_id &&
                                  this.formData.employee_id &&
                                  this.formData.role;
                return step2Valid && !this.errors.station_id && !this.errors.employee_id && !this.errors.role;
            },

            validateStep3() {
                const step3Valid = this.formData.password &&
                                  this.formData.password_confirmation &&
                                  this.formData.password === this.formData.password_confirmation &&
                                  this.formData.password.length >= 8;
                return step3Valid && !this.errors.password && !this.errors.password_confirmation;
            },

            isFormValid() {
                // Force validation of all fields first
                this.validateAllFields();

                // Check if there are any errors
                return Object.keys(this.errors).length === 0 &&
                       this.formData.first_name.trim() !== '' &&
                       this.formData.last_name.trim() !== '' &&
                       this.formData.email.trim() !== '' &&
                       this.formData.station_id !== '' &&
                       this.formData.employee_id.trim() !== '' &&
                       this.formData.role !== '' &&
                       this.formData.password.trim() !== '' &&
                       this.formData.password_confirmation.trim() !== '';
            },

            validateField(field) {
                switch(field) {
                    case 'first_name':
                        if (!this.formData.first_name || this.formData.first_name.trim() === '') {
                            this.errors.first_name = 'First name is required';
                        } else if (this.formData.first_name.length > 100) {
                            this.errors.first_name = 'First name must not exceed 100 characters';
                        } else {
                            this.$delete(this.errors, 'first_name');
                        }
                        break;

                    case 'last_name':
                        if (!this.formData.last_name || this.formData.last_name.trim() === '') {
                            this.errors.last_name = 'Last name is required';
                        } else if (this.formData.last_name.length > 100) {
                            this.errors.last_name = 'Last name must not exceed 100 characters';
                        } else {
                            this.$delete(this.errors, 'last_name');
                        }
                        break;

                    case 'station_id':
                        if (!this.formData.station_id || this.formData.station_id === '') {
                            this.errors.station_id = 'Please select a station';
                        } else {
                            this.$delete(this.errors, 'station_id');
                        }
                        break;

                    case 'employee_id':
                        if (!this.formData.employee_id || this.formData.employee_id.trim() === '') {
                            this.errors.employee_id = 'Employee ID is required';
                        } else if (this.formData.employee_id.length > 50) {
                            this.errors.employee_id = 'Employee ID must not exceed 50 characters';
                        } else {
                            this.$delete(this.errors, 'employee_id');
                        }
                        break;

                    case 'role':
                        if (!this.formData.role || this.formData.role === '') {
                            this.errors.role = 'Please select a role';
                        } else {
                            this.$delete(this.errors, 'role');
                        }
                        break;

                    case 'phone':
                        if (this.formData.phone && this.formData.phone.length > 20) {
                            this.errors.phone = 'Phone number must not exceed 20 characters';
                        } else {
                            this.$delete(this.errors, 'phone');
                        }
                        break;
                }
            },

            validateEmail() {
                if (!this.formData.email || this.formData.email.trim() === '') {
                    this.errors.email = 'Email address is required';
                } else if (!this.isValidEmail(this.formData.email)) {
                    this.errors.email = 'Please enter a valid email address';
                } else if (this.formData.email.length > 255) {
                    this.errors.email = 'Email address must not exceed 255 characters';
                } else {
                    this.$delete(this.errors, 'email');
                }
            },

            validatePassword() {
                const password = this.formData.password;
                this.passwordStrength = this.calculatePasswordStrength(password);

                if (!password || password.trim() === '') {
                    this.errors.password = 'Password is required';
                } else if (password.length < 8) {
                    this.errors.password = 'Password must be at least 8 characters';
                } else {
                    this.$delete(this.errors, 'password');
                }
            },

            validatePasswordConfirmation() {
                if (!this.formData.password_confirmation || this.formData.password_confirmation.trim() === '') {
                    this.errors.password_confirmation = 'Please confirm your password';
                } else if (this.formData.password !== this.formData.password_confirmation) {
                    this.errors.password_confirmation = 'Passwords do not match';
                } else {
                    this.$delete(this.errors, 'password_confirmation');
                }
            },

            calculatePasswordStrength(password) {
                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z\d]/.test(password)) strength++;
                return strength;
            },

            isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            },

            generateEmployeeId() {
                if (!this.formData.employee_id && this.formData.station_id) {
                    const station = this.stations.find(s => s.id == this.formData.station_id);
                    if (station) {
                        const prefix = station.name.substring(0, 3).toUpperCase();
                        const timestamp = Date.now().toString().slice(-6);
                        this.formData.employee_id = `${prefix}${timestamp}`;
                    }
                }
            },

            validateEmployeeId() {
                this.validateField('employee_id');
                // Additional validation could include checking uniqueness via AJAX
            },

            getStationName(stationId) {
                const station = this.stations.find(s => s.id == stationId);
                return station ? `${station.name} - ${station.location}` : '';
            },

            validateBeforeSubmit(event) {
                // Force validation of all fields
                this.validateAllFields();

                if (!this.isFormValid()) {
                    event.preventDefault();
                    this.showValidationErrors();
                    return false;
                }

                // Form will submit naturally - no need to prevent default
                return true;
            },

            validateAllFields() {
                // Validate all required fields
                this.validateField('first_name');
                this.validateField('last_name');
                this.validateEmail();
                this.validateField('phone');
                this.validateField('station_id');
                this.validateField('employee_id');
                this.validateField('role');
                this.validatePassword();
                this.validatePasswordConfirmation();
            },

            showValidationErrors() {
                const errorFields = Object.keys(this.errors);
                if (errorFields.length > 0) {
                    const errorMessages = errorFields.map(field => {
                        return `• ${this.getFieldLabel(field)}: ${this.errors[field]}`;
                    }).join('<br>');

                    Swal.fire({
                        title: 'Validation Errors',
                        html: `Please correct the following errors:<br><br>${errorMessages}`,
                        icon: 'error',
                        confirmButtonColor: '#000000',
                        confirmButtonText: 'OK'
                    });
                }
            },

            getFieldLabel(field) {
                const labels = {
                    'first_name': 'First Name',
                    'last_name': 'Last Name',
                    'email': 'Email Address',
                    'phone': 'Phone Number',
                    'station_id': 'Station',
                    'employee_id': 'Employee ID',
                    'role': 'Role',
                    'password': 'Password',
                    'password_confirmation': 'Password Confirmation'
                };
                return labels[field] || field;
            },

            getRoleLabel(roleKey) {
                return this.roles[roleKey] || '';
            }
        }
    }

    // Role selection functionality
    function updateRoleSelection(selectedRole) {
        // Update all role options
        document.querySelectorAll('.role-option').forEach(option => {
            const role = option.dataset.role;
            const indicator = option.querySelector('.role-indicator');
            const dot = option.querySelector('.role-dot');

            if (role === selectedRole) {
                // Selected role
                option.classList.add('border-black', 'bg-gray-50');
                option.classList.remove('border-gray-200');
                indicator.classList.add('border-black', 'bg-black');
                indicator.classList.remove('border-gray-300');
                dot.classList.remove('hidden');
            } else {
                // Unselected roles
                option.classList.remove('border-black', 'bg-gray-50');
                option.classList.add('border-gray-200');
                indicator.classList.remove('border-black', 'bg-black');
                indicator.classList.add('border-gray-300');
                dot.classList.add('hidden');
            }
        });

        // Update Alpine data if component exists
        const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
        if (alpineComponent) {
            alpineComponent.formData.role = selectedRole;
        }
    }

    // Station selection handler
    function updateStationSelection() {
        const stationSelect = document.querySelector('select[name="station_id"]');
        const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));

        if (stationSelect && alpineComponent) {
            alpineComponent.formData.station_id = stationSelect.value;

            // Auto-generate employee ID if empty
            if (!alpineComponent.formData.employee_id && stationSelect.value) {
                generateEmployeeId(alpineComponent, stationSelect.value);
            }
        }
    }

    // Auto-generate employee ID
    function generateEmployeeId(component, stationId) {
        const station = component.stations.find(s => s.id == stationId);
        if (station) {
            const prefix = station.name.substring(0, 3).toUpperCase();
            const timestamp = Date.now().toString().slice(-6);
            const generatedId = `${prefix}${timestamp}`;

            // Update both Alpine data and DOM
            component.formData.employee_id = generatedId;
            const employeeInput = document.querySelector('input[name="employee_id"]');
            if (employeeInput) {
                employeeInput.value = generatedId;
            }
        }
    }

    // Form field sync handlers
    function syncFieldToAlpine(fieldName, value) {
        const alpineComponent = Alpine.$data(document.querySelector('[x-data]'));
        if (alpineComponent && alpineComponent.formData) {
            alpineComponent.formData[fieldName] = value;
        }
    }
</script>
@endpush
