<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - Wedding Management</title>

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

        /* Sidebar Gold Theme */
        .sidebar-dark-primary {
            background: linear-gradient(180deg, var(--dark-brown) 0%, #2C1810 100%);
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link:hover {
            background-color: rgba(212, 175, 55, 0.2);
            color: var(--gold-primary);
        }

        .brand-link {
            border-bottom: 1px solid var(--gold-primary);
            background: rgba(212, 175, 55, 0.1);
        }

        .brand-text {
            color: var(--gold-primary) !important;
            font-weight: bold;
        }

        /* Main Header Gold Theme */
        .main-header {
            border-bottom: 2px solid var(--gold-primary);
        }

        /* Navbar */
        .navbar-light {
            background-color: white;
        }

        /* Custom Gold Buttons */
        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            font-weight: 600;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, var(--gold-dark), #8B6914);
            color: white;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
        }

        /* Info Box Colors */
        .info-box-icon-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
        }

        /* Card Headers */
        .card-primary:not(.card-outline) > .card-header {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
        }

        /* Small Box */
        .small-box {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Badge Gold */
        .badge-gold {
            background-color: var(--gold-primary);
            color: white;
        }

        /* Active Link */
        .nav-sidebar .nav-link.active {
            border-left: 3px solid var(--gold-primary);
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('home') }}" class="nav-link">Home</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- User Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="far fa-user"></i>
                        <span class="d-none d-md-inline ml-1">{{ Auth::user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user mr-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <i class="fas fa-rings-wedding brand-image" style="font-size: 2rem; margin-left: 0.8rem;"></i>
                <span class="brand-text font-weight-light">Admin Panel</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <i class="fas fa-user-circle" style="font-size: 2.1rem; color: var(--gold-primary);"></i>
                    </div>
                    <div class="info">
                        <a href="#" class="d-block" style="color: var(--gold-light);">{{ Auth::user()->name }}</a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.bookings.index') }}" class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>Bookings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Customers</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.vendors.index') }}" class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-store"></i>
                                <p>Vendors</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.packages.index') }}" class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-box"></i>
                                <p>Packages</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>Payments</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="icon fas fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="icon fas fa-ban"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; {{ date('Y') }} <a href="{{ route('home') }}" style="color: var(--gold-primary);">Wedding Management System</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0.0
            </div>
        </footer>

    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>

    @stack('scripts')
</body>
</html>
