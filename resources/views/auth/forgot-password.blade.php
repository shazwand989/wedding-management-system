<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - Wedding Management System</title>

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

        .login-page {
            background: linear-gradient(135deg, var(--cream) 0%, #fff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .forgot-left {
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

        .forgot-left::before {
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

        .forgot-left h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--gold-primary);
            position: relative;
            z-index: 1;
        }

        .forgot-left p {
            font-size: 16px;
            color: var(--gold-light);
            text-align: center;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .forgot-right {
            flex: 1;
            padding: 60px 50px;
        }

        .forgot-header {
            margin-bottom: 30px;
        }

        .forgot-header h1 {
            color: var(--dark-brown);
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .forgot-header p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
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

        .back-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .back-link a {
            color: var(--gold-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .back-link a:hover {
            color: var(--gold-primary);
            text-decoration: underline;
        }

        .invalid-feedback {
            display: block;
            font-size: 13px;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .forgot-container {
                flex-direction: column;
            }

            .forgot-left {
                padding: 40px 20px;
            }

            .forgot-right {
                padding: 30px 20px;
            }

            .logo-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
            }

            .forgot-left h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="forgot-container">
        <!-- Left Side - Branding -->
        <div class="forgot-left">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Reset Password</h2>
                <p>Don't worry! It happens to the best of us. Enter your email and we'll send you a link to reset your password.</p>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="forgot-right">
            <div class="forgot-header">
                <h1>Forgot Password?</h1>
                <p>Enter your email address and we'll send you a password reset link.</p>
            </div>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                @if (session('status'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('status') }}
                    </div>
                @endif

                <!-- Email -->
                <div class="mb-4">
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
                               required
                               autofocus>
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-gold">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>

                <!-- Back Link -->
                <div class="back-link">
                    <a href="{{ route('login') }}">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </form>
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
