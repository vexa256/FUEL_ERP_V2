<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FuelStation ERP') }} - Secure Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    @include('layouts.customcss')

    <script>
        // Tailwind config for shadcn/ui compatibility
        tailwind.config = {
            darkMode: ['class'],
            theme: {
                extend: {
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: {
                            DEFAULT: 'hsl(var(--primary))',
                            foreground: 'hsl(var(--primary-foreground))'
                        },
                        secondary: {
                            DEFAULT: 'hsl(var(--secondary))',
                            foreground: 'hsl(var(--secondary-foreground))'
                        },
                        destructive: {
                            DEFAULT: 'hsl(var(--destructive))',
                            foreground: 'hsl(var(--destructive-foreground))'
                        },
                        muted: {
                            DEFAULT: 'hsl(var(--muted))',
                            foreground: 'hsl(var(--muted-foreground))'
                        },
                        accent: {
                            DEFAULT: 'hsl(var(--accent))',
                            foreground: 'hsl(var(--accent-foreground))'
                        },
                        popover: {
                            DEFAULT: 'hsl(var(--popover))',
                            foreground: 'hsl(var(--popover-foreground))'
                        },
                        card: {
                            DEFAULT: 'hsl(var(--card))',
                            foreground: 'hsl(var(--card-foreground))'
                        }
                    },
                    borderRadius: {
                        lg: 'var(--radius)',
                        md: 'calc(var(--radius) - 2px)',
                        sm: 'calc(var(--radius) - 4px)'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 20px rgba(59, 130, 246, 0.3)' },
                            '100%': { boxShadow: '0 0 40px rgba(59, 130, 246, 0.6)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Premium Background Gradient */
        .premium-bg {
            background: linear-gradient(135deg,
                    hsl(var(--background)) 0%,
                    hsl(var(--muted)) 25%,
                    hsl(var(--accent)) 50%,
                    hsl(var(--secondary)) 75%,
                    hsl(var(--background)) 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Premium Glass Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow:
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Premium Input Focus Effects */
        .premium-input:focus {
            transform: scale(1.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow:
                0 0 0 3px hsl(var(--ring)),
                0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Premium Button Effects */
        .premium-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .premium-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }

        .premium-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .premium-btn:hover::before {
            left: 100%;
        }

        /* Premium Logo Glow */
        .logo-glow {
            filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.5));
        }

        /* Floating Particles */
        .floating-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: hsl(var(--primary));
            border-radius: 50%;
            opacity: 0.6;
            animation: float 4s infinite ease-in-out;
        }

        .floating-particle:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-particle:nth-child(2) {
            top: 60%;
            left: 20%;
            animation-delay: 1s;
        }

        .floating-particle:nth-child(3) {
            top: 40%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-particle:nth-child(4) {
            bottom: 30%;
            right: 25%;
            animation-delay: 3s;
        }

        .floating-particle:nth-child(5) {
            bottom: 20%;
            left: 30%;
            animation-delay: 1.5s;
        }

        /* Security Badge Styles */
        .security-badge {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        /* Premium Status Indicators */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }

        .status-online {
            background: #10b981;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>

<body class="h-full premium-bg">
    <!-- Floating Particles -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
    </div>

    <!-- Main Container -->
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
        <!-- Background Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-transparent via-black/5 to-transparent"></div>

        <!-- Login Card Container -->
        <div class="relative w-full max-w-md space-y-8 animate-fade-in">
            <!-- Logo and Header Section -->
            <div class="text-center animate-slide-up">
                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    <div class="logo-glow">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-2xl">
                            <i class="fas fa-gas-pump text-2xl animate-float"></i>
                        </div>
                    </div>
                </div>

                <!-- Company Title -->

                {{--
                <!-- Security Badge -->
                <div class="flex justify-center items-center space-x-2 mb-6">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt mr-1"></i>
                        ISO 27001 CERTIFIED
                    </div>
                </div> --}}
            </div>

            <!-- Login Form Card -->
            <div class="glass-card rounded-2xl p-8 space-y-6 animate-slide-up shadow-2xl">
                <!-- Form Header -->
                <div class="text-center space-y-2">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground mb-2">
                        FuelStation ERP
                    </h1>
                    <p class="text-sm text-muted-foreground mb-1">
                        Login to your account
                    </p>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                <div role="alert" class="alert alert-error mb-4 animate-fade-in">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3 class="font-bold">Authentication Failed</h3>
                        <div class="text-xs">
                            @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Success Messages -->
                @if (session('status'))
                <div role="alert" class="alert alert-success mb-4 animate-fade-in">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('status') }}</span>
                </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div class="form-control">
                        <label class="label" for="email">
                            <span class="label-text font-medium text-foreground">
                                <i class="fas fa-envelope w-4 h-4 mr-2 text-muted-foreground"></i>
                                Email Address
                            </span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="email" placeholder="Enter your email address"
                            class="input input-bordered premium-input w-full bg-background border-border focus:border-ring focus:ring-2 focus:ring-ring/20 transition-all duration-300 @error('email') input-error @enderror" />
                        @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="form-control">
                        <label class="label" for="password">
                            <span class="label-text font-medium text-foreground">
                                <i class="fas fa-lock w-4 h-4 mr-2 text-muted-foreground"></i>
                                Password
                            </span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                autocomplete="current-password" placeholder="Enter your password"
                                class="input input-bordered premium-input w-full bg-background border-border focus:border-ring focus:ring-2 focus:ring-ring/20 transition-all duration-300 pr-12 @error('password') input-error @enderror" />
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground transition-colors">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                        @enderror
                    </div>

                    <!-- Remember Me and Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="form-control">
                            <label class="label cursor-pointer justify-start space-x-2">
                                <input type="checkbox" name="remember" id="remember"
                                    class="checkbox checkbox-primary checkbox-sm" {{ old('remember') ? 'checked' : ''
                                    }} />
                                <span class="label-text text-sm text-muted-foreground">Remember me</span>
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="link link-primary text-sm font-medium hover:text-primary/80 transition-colors">
                            Forgot password?
                        </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div class="form-control mt-8">
                        <button type="submit"
                            class="btn btn-primary premium-btn w-full h-12 text-base font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In to Dashboard
                        </button>
                    </div>
                </form>

                <!-- System Status -->
                <div class="pt-6 border-t border-border">
                    <div class="flex items-center justify-center space-x-4 text-xs text-muted-foreground">
                        <div class="flex items-center">
                            <span class="status-indicator status-online"></span>
                            <span>System Online</span>
                        </div>
                        <div class="w-px h-4 bg-border"></div>
                        <div class="flex items-center">
                            <i class="fas fa-shield-check mr-1"></i>
                            <span>SSL Secured</span>
                        </div>
                        <div class="w-px h-4 bg-border"></div>
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Info -->
            {{-- <div class="text-center text-xs text-muted-foreground space-y-2 animate-fade-in">
                <p>© {{ date('Y') }} FuelStation ERP. All rights reserved.</p>
                <p>Uganda Revenue Authority Compliant • Multi-Tenant Architecture</p>
                <div class="flex justify-center items-center space-x-4 mt-3">
                    <span class="flex items-center">
                        <i class="fas fa-globe mr-1"></i>
                        Global Standards
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-database mr-1"></i>
                        Real-time Sync
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-chart-line mr-1"></i>
                        Advanced Analytics
                    </span>
                </div>
            </div> --}}
        </div>
    </div>

    <!-- JavaScript for Enhanced Interactions -->
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Authenticating...';
            submitButton.disabled = true;

            // Re-enable if form validation fails
            setTimeout(() => {
                if (!submitButton.disabled) return;
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 5000);
        });

        // Enhanced input focus effects
        document.querySelectorAll('.premium-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('transform', 'scale-105', 'transition-transform', 'duration-200');
            });

            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('transform', 'scale-105', 'transition-transform', 'duration-200');
            });
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + L to focus login
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                document.getElementById('email').focus();
            }
        });

        // Security monitoring (basic)
        let loginAttempts = 0;
        document.querySelector('form').addEventListener('submit', function() {
            loginAttempts++;
            if (loginAttempts > 3) {
                console.log('Multiple login attempts detected');
            }
        });
    </script>
</body>

</html>
