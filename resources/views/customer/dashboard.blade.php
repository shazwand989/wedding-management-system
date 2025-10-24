@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <h2>Welcome back, {{ Auth::user()->name }}!</h2>
        <p class="text-muted">Manage your wedding plans from here</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark));">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0">{{ $totalBookings }}</h4>
                    <p class="text-muted mb-0">My Bookings</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon" style="background: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0">{{ $confirmedBookings }}</h4>
                    <p class="text-muted mb-0">Confirmed</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon" style="background: #17a2b8;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0">RM {{ number_format($totalSpent, 2) }}</h4>
                    <p class="text-muted mb-0">Total Spent</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon" style="background: #ffc107;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="ms-3">
                    <h4 class="mb-0">{{ $pendingTasks }}</h4>
                    <p class="text-muted mb-0">Pending Tasks</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar"></i> Upcoming Events</h5>
            </div>
            <div class="card-body">
                @forelse($upcomingBookings as $booking)
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3" style="background: var(--cream); border-radius: 10px;">
                        <div>
                            <h6 class="mb-1">{{ $booking->package->name ?? 'Custom Package' }}</h6>
                            <p class="text-muted mb-0"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }} at {{ $booking->event_time }}</p>
                        </div>
                        <a href="{{ route('customer.bookings.show', $booking->id) }}" class="btn btn-sm btn-gold">View</a>
                    </div>
                @empty
                    <p class="text-center text-muted">No upcoming events</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('customer.bookings.create') }}" class="btn btn-gold w-100 mb-2">
                    <i class="fas fa-plus"></i> New Booking
                </a>
                <a href="{{ route('customer.vendors.index') }}" class="btn btn-outline-gold w-100 mb-2">
                    <i class="fas fa-store"></i> Browse Vendors
                </a>
                <a href="{{ route('customer.timeline.index') }}" class="btn btn-outline-gold w-100 mb-2">
                    <i class="fas fa-tasks"></i> View Timeline
                </a>
                <a href="{{ route('customer.budget.index') }}" class="btn btn-outline-gold w-100">
                    <i class="fas fa-wallet"></i> Manage Budget
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-outline-gold {
    background: transparent;
    color: var(--gold-primary);
    border: 1px solid var(--gold-primary);
}
.btn-outline-gold:hover {
    background: var(--gold-primary);
    color: white;
}
</style>
@endpush
