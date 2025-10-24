@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-tachometer-alt text-warning"></i> Dashboard</h2>
            <p class="text-muted">Welcome back, {{ Auth::user()->name }}!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Bookings</h6>
                            <h3 class="mb-0 fw-bold">{{ $totalBookings }}</h3>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending Bookings</h6>
                            <h3 class="mb-0 fw-bold">{{ $pendingBookings }}</h3>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h3 class="mb-0 fw-bold">RM {{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Vendors</h6>
                            <h3 class="mb-0 fw-bold">{{ $totalVendors }}</h3>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Event Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentBookings as $booking)
                                    <tr>
                                        <td>#{{ $booking->id }}</td>
                                        <td>{{ $booking->customer->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</td>
                                        <td>RM {{ number_format($booking->total_amount, 2) }}</td>
                                        <td>
                                            @if($booking->booking_status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($booking->booking_status === 'confirmed')
                                                <span class="badge bg-info">Confirmed</span>
                                            @elseif($booking->booking_status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-danger">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-gold">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No bookings found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Vendor Approvals -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-clock"></i> Pending Vendors</h5>
                </div>
                <div class="card-body">
                    @forelse($pendingVendors as $vendor)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">{{ $vendor->business_name }}</h6>
                                <small class="text-muted">{{ $vendor->user->email }}</small>
                            </div>
                            <div>
                                <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.vendors.reject', $vendor->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No pending vendors</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
