<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor Dashboard') - Wedding Management</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AdminLTE 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
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
        .app-sidebar {
            background: linear-gradient(180deg, var(--dark-brown) 0%, #1a0f0a 100%) !important;
        }

        .sidebar-brand .brand-link {
            border-bottom: 2px solid var(--gold-primary);
            padding: 1rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .sidebar-brand .brand-link:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        .brand-image {
            color: var(--gold-primary);
            font-size: 2rem;
            margin-right: 0.5rem;
        }

        .brand-text {
            color: var(--gold-primary) !important;
            font-weight: bold;
        }

        .sidebar-menu .nav-link {
            color: #c2c7d0;
            margin: 0.125rem 0.5rem;
            border-radius: 0.25rem;
        }

        .sidebar-menu .nav-link:hover {
            background-color: rgba(212, 175, 55, 0.15);
            color: var(--gold-light);
        }

        .sidebar-menu .nav-link.active {
            background-color: rgba(212, 175, 55, 0.25);
            color: var(--gold-primary);
        }

        .sidebar-menu .nav-icon {
            color: inherit;
        }

        /* Main Header Gold Theme */
        .app-header {
            border-bottom: 3px solid var(--gold-primary);
        }

        /* Custom Gold Buttons */
        .btn-gold {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));
            color: white;
            border: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, var(--gold-dark), #8B6914);
            color: white;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
            transform: translateY(-1px);
        }

        .btn-outline-gold {
            background: transparent;
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-gold:hover {
            background: var(--gold-primary);
            color: white;
            border-color: var(--gold-primary);
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.3);
        }

        /* Breadcrumb */
        .breadcrumb {
            padding: 0;
            margin-bottom: 0;
            line-height: 2.5rem;
            background: transparent;
        }

        .breadcrumb-item a {
            text-decoration: none;
            color: var(--gold-primary);
        }

        .breadcrumb-item a:hover {
            color: var(--gold-dark);
        }

        .breadcrumb-item.active {
            color: var(--dark-brown);
        }

        .breadcrumb-item + .breadcrumb-item::before {
            color: #6c757d;
        }

        /* App Content Header */
        .app-content-header {
            padding: 1rem 0.5rem;
        }

        .app-content-header h3 {
            color: var(--dark-brown);
            font-weight: 600;
        }

        /* Footer */
        .app-footer {
            border-top: 2px solid var(--gold-primary);
        }
    </style>
    @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

        <!-- Navbar -->
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <!-- Left navbar links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="fas fa-bars"></i></a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ route('home') }}" class="nav-link">Home</a>
                    </li>
                </ul>

                <!-- Right navbar links -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                            <i class="far fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <span class="dropdown-item dropdown-header">{{ Auth::user()->name }}</span>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('vendor.profile.edit') }}" class="dropdown-item">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item dropdown-footer">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <!-- Brand Logo -->
            <div class="sidebar-brand">
                <a href="{{ route('vendor.dashboard') }}" class="brand-link">
                    <i class="fas fa-store brand-image opacity-75"></i>
                    <span class="brand-text fw-light">Vendor Panel</span>
                </a>
            </div>

            <!-- Sidebar -->
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <!-- Sidebar Menu -->
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('vendor.dashboard') }}" class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vendor.bookings.index') }}" class="nav-link {{ request()->routeIs('vendor.bookings.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>Bookings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vendor.calendar') }}" class="nav-link {{ request()->routeIs('vendor.calendar') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar"></i>
                                <p>Calendar</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vendor.services.index') }}" class="nav-link {{ request()->routeIs('vendor.services.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-concierge-bell"></i>
                                <p>Services</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vendor.earnings.index') }}" class="nav-link {{ request()->routeIs('vendor.earnings.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-money-bill-wave"></i>
                                <p>Earnings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vendor.profile.edit') }}" class="nav-link {{ request()->routeIs('vendor.profile.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>Profile</p>
                            </a>
                        </li>
                    </ul>
                    <!-- /.sidebar-menu -->
                </nav>
            </div>
            <!-- /.sidebar-wrapper -->
        </aside>

        <!-- Content Wrapper -->
        <main class="app-main">
            <!-- Content Header (Page header) -->
            <div class="app-content-header">
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">@yield('page-title', 'Dashboard')</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                @yield('breadcrumb')
                            </ol>
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <div class="app-content">
                <div class="container-fluid">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <i class="icon fas fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <i class="icon fas fa-ban"></i> {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
            <!-- /.content -->
        </main>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        <footer class="app-footer">
            <strong>Copyright &copy; {{ date('Y') }} <a href="{{ route('home') }}" style="color: var(--gold-primary);">Wedding Management System</a>.</strong>
            All rights reserved.
            <div class="float-end">
                <b>Version</b> 1.0.0
            </div>
        </footer>

    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>

    @stack('scripts')
</body>
</html>
