<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - Wedding Management System</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <!-- Custom CSS for Gold Theme -->
    <style>
        :root {
            --gold-primary: #D4AF37;
            --gold-dark: #B8941D;
            --gold-light: #F4E4C1;
            --cream: #FFF8E7;
            --dark-brown: #3E2723;
        }

        .register-page {
            background: linear-gradient(135deg, var(--cream) 0%, #fff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .register-left {
            flex: 1;
            background: linear-gradient(135deg, var(--dark-brown) 0%, #1a0f0a 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }

        .register-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23D4AF37" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,197.3C1248,203,1344,149,1392,122.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .logo-container {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
        }

        .register-left h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--gold-primary);
            position: relative;
            z-index: 1;
        }

        .register-left p {
            font-size: 16px;
            color: var(--gold-light);
            text-align: center;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .register-right {
            flex: 1;
            padding: 50px 40px;
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: var(--dark-brown);
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .register-header p {
            color: #666;
            font-size: 14px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 2px solid #e0e0e0;
            border-right: none;
            background-color: #f8f9fa;
            color: var(--gold-dark);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, var(--gold-dark), #8B6914);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: var(--gold-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            color: var(--gold-primary);
            text-decoration: underline;
        }

        .brand-icon {
            font-size: 48px;
            color: white;
            margin-bottom: 10px;
        }

        .invalid-feedback {
            display: block;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }

            .register-left {
                padding: 40px 20px;
            }

            .register-right {
                padding: 30px 20px;
            }

            .logo-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
            }

            .register-left h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="hold-transition register-page">
    <div class="register-container">
        <!-- Left Side - Branding -->
        <div class="register-left">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-rings-wedding"></i>
                </div>
                <h2>Wedding Management</h2>
                <p>Plan your perfect day with us. From booking to celebration, we make every moment memorable.</p>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="register-right">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join our Wedding Management System</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Enter your full name"
                                   required
                                   autofocus>
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="Enter your email"
                                   required>
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="text"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone') }}"
                                   placeholder="Enter your phone number"
                                   required>
                        </div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   required>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="Re-enter your password"
                                   required>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>

                    <!-- Login Link -->
                    <div class="login-link">
                        Already have an account? <a href="{{ route('login') }}">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>
</body>
</html>
