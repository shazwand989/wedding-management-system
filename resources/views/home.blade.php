<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wedding Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --gold-primary: #D4AF37;
            --gold-dark: #B8941D;
            --gold-light: #F4E4C1;
            --cream: #FFF8E7;
            --dark-brown: #3E2723;
        }

        body {
            font-family: 'Georgia', serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #D4AF37 0%, #B8941D 50%, #8B6914 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="rgba(255,255,255,0.1)"/></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .feature-card {
            transition: all 0.3s;
            border: 2px solid var(--gold-light);
            box-shadow: 0 4px 6px rgba(212, 175, 55, 0.1);
            background: linear-gradient(to bottom, #ffffff, var(--cream));
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--gold-primary);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-dark) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(212, 175, 55, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .package-card {
            border: 3px solid var(--gold-light);
            border-radius: 15px;
            transition: all 0.3s;
            background: white;
        }

        .package-card:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 12px 24px rgba(212, 175, 55, 0.3);
            transform: scale(1.02);
        }

        .navbar-custom {
            background: linear-gradient(to right, rgba(255, 248, 231, 0.98), rgba(244, 228, 193, 0.98));
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(212, 175, 55, 0.2);
            border-bottom: 2px solid var(--gold-primary);
        }

        .navbar-brand {
            color: var(--gold-dark) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--dark-brown) !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--gold-primary) !important;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 10px 25px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            letter-spacing: 0.5px;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, var(--gold-dark), #8B6914);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
        }

        .btn-outline-gold {
            border: 2px solid var(--gold-primary);
            color: var(--gold-dark);
            background: white;
            font-weight: 600;
            transition: all 0.3s;
            padding: 10px 25px;
            border-radius: 25px;
            letter-spacing: 0.5px;
        }

        .btn-outline-gold:hover {
            background: var(--gold-primary);
            color: white;
            border-color: var(--gold-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-hero-light {
            background: white;
            color: var(--gold-dark);
            border: 2px solid white;
            font-weight: 600;
            padding: 15px 35px;
            border-radius: 30px;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-hero-light:hover {
            background: var(--gold-primary);
            color: white;
            border-color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
            font-weight: 600;
            padding: 15px 35px;
            border-radius: 30px;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-hero-outline:hover {
            background: white;
            color: var(--gold-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .text-gold {
            color: var(--gold-primary) !important;
        }

        .bg-gold-light {
            background-color: var(--cream) !important;
        }

        .icon-gold {
            color: var(--gold-primary);
        }

        h2, h3 {
            color: var(--dark-brown);
            font-weight: bold;
        }

        .section-title {
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--gold-primary), transparent);
        }

        footer {
            background: linear-gradient(135deg, var(--dark-brown) 0%, #2C1810 100%);
            border-top: 3px solid var(--gold-primary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="fas fa-rings-wedding icon-gold"></i> Wedding Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#packages">Packages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    @auth
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a>
                            </li>
                        @elseif(auth()->user()->role === 'customer')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('customer.dashboard') }}">Dashboard</a>
                            </li>
                        @elseif(auth()->user()->role === 'vendor')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('vendor.dashboard') }}">Dashboard</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-gold btn-sm ms-2">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-outline-gold btn-sm ms-2" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-gold btn-sm ms-2" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">Plan Your Perfect Wedding</h1>
                    <p class="lead mb-4" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">Comprehensive wedding management system to make your special day unforgettable. Connect with top vendors, manage bookings, and track your budget all in one place.</p>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-hero-light me-3">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="#packages" class="btn btn-hero-outline">
                            <i class="fas fa-box me-2"></i>View Packages
                        </a>
                    @else
                        <a href="{{ auth()->user()->role === 'customer' ? route('customer.bookings.create') : '#' }}" class="btn btn-hero-light">
                            <i class="fas fa-calendar-plus me-2"></i>Create Booking
                        </a>
                    @endguest
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-heart fa-10x" style="opacity: 0.3; color: rgba(255,255,255,0.5);"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-gold-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h3>{{ $vendorCount }}+</h3>
                        <p class="mb-0">Active Vendors</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <i class="fas fa-calendar-check fa-3x mb-3"></i>
                        <h3>{{ $bookingCount }}+</h3>
                        <p class="mb-0">Bookings Made</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <i class="fas fa-smile fa-3x mb-3"></i>
                        <h3>{{ $customerCount }}+</h3>
                        <p class="mb-0">Happy Couples</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 section-title">Why Choose Us?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-store fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">Browse Vendors</h5>
                            <p class="card-text">Access a wide network of verified wedding vendors including photographers, caterers, decorators, and more.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">Easy Booking</h5>
                            <p class="card-text">Book your wedding services online with ease. Manage all your bookings from one convenient dashboard.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-wallet fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">Budget Tracking</h5>
                            <p class="card-text">Keep track of your wedding expenses and stay within budget with our comprehensive budget management tools.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">Timeline Management</h5>
                            <p class="card-text">Plan and organize your wedding tasks with our intuitive timeline feature to ensure nothing is forgotten.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">Secure Payments</h5>
                            <p class="card-text">Make payments securely through our integrated payment gateway with multiple payment options.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-headset fa-3x icon-gold mb-3"></i>
                            <h5 class="card-title">24/7 Support</h5>
                            <p class="card-text">Get help whenever you need it with our dedicated customer support team available around the clock.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="py-5 bg-gold-light">
        <div class="container">
            <h2 class="text-center mb-5 section-title">Our Wedding Packages</h2>
            <div class="row">
                @forelse($packages as $package)
                    <div class="col-md-4 mb-4">
                        <div class="card package-card h-100">
                            <div class="card-body">
                                <h3 class="card-title text-center mb-3 text-gold">{{ $package->name }}</h3>
                                <h2 class="text-center text-gold mb-4">RM {{ number_format($package->price, 0) }}</h2>
                                <p class="text-muted text-center mb-4">{{ $package->description }}</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-clock icon-gold"></i> {{ $package->duration_hours }} hours coverage</li>
                                    <li class="mb-2"><i class="fas fa-users icon-gold"></i> Up to {{ $package->max_guests }} guests</li>
                                    @if($package->features)
                                        @foreach($package->features as $feature)
                                            <li class="mb-2"><i class="fas fa-check icon-gold"></i> {{ $feature }}</li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                @auth
                                    @if(auth()->user()->role === 'customer')
                                        <a href="{{ route('customer.bookings.create', ['package' => $package->id]) }}" class="btn btn-gold w-100">
                                            <i class="fas fa-shopping-cart"></i> Book Now
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('register') }}" class="btn btn-gold w-100">
                                        <i class="fas fa-user-plus"></i> Register to Book
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center">
                        <p class="text-muted">No packages available at the moment.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-rings-wedding icon-gold"></i> Wedding Management System</h5>
                    <p style="color: #D4AF37;">Making your dream wedding a reality.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; {{ date('Y') }} Wedding Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
