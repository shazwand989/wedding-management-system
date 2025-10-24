@extends('layouts.admin')

@section('title', 'Booking Details')

@section('page-title', 'Booking Details')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active" aria-current="page">Details</li>
@endsection

@section('content')
<!--begin::Row-->
<div class="row">
    <div class="col-md-8">
        <!-- Booking Details Card -->
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-calendar-check"></i> Booking #{{ $booking->id }}</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Customer Information</h6>
                        <p class="mb-1"><strong>Name:</strong> {{ $booking->customer->name }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $booking->customer->email }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $booking->customer->phone ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Booking Status</h6>
                        <p class="mb-1">
                            @if($booking->booking_status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($booking->booking_status === 'confirmed')
                                <span class="badge bg-info">Confirmed</span>
                            @elseif($booking->booking_status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @else
                                <span class="badge bg-danger">Cancelled</span>
                            @endif
                        </p>
                        <p class="mb-1"><strong>Created:</strong> {{ $booking->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Event Details</h6>
                        <p class="mb-1"><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</p>
                        <p class="mb-1"><strong>Event Time:</strong> {{ $booking->event_time }}</p>
                        <p class="mb-1"><strong>Venue:</strong> {{ $booking->venue_name }}</p>
                        <p class="mb-1"><strong>Guest Count:</strong> {{ $booking->guest_count }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Package Information</h6>
                        <p class="mb-1"><strong>Package:</strong> {{ $booking->package->name ?? 'Custom Package' }}</p>
                        @if($booking->package)
                        <p class="mb-1"><strong>Package Price:</strong> RM {{ number_format($booking->package->price, 2) }}</p>
                        @endif
                    </div>
                </div>

                @if($booking->special_requests)
                <hr>
                <div class="mb-3">
                    <h6 class="text-muted">Special Requests</h6>
                    <p>{{ $booking->special_requests }}</p>
                </div>
                @endif

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-end">
                            <strong>Total Amount:</strong>
                            <span style="color: var(--gold-primary);">RM {{ number_format($booking->total_amount, 2) }}</span>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-gold">
                    <i class="fas fa-edit"></i> Edit Booking
                </a>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Quick Actions Card -->
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-tasks"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" class="mb-3">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Update Status</label>
                        <select name="booking_status" class="form-select">
                            <option value="pending" {{ $booking->booking_status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $booking->booking_status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="completed" {{ $booking->booking_status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $booking->booking_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gold w-100">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>

                <hr>

                <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-trash"></i> Delete Booking
                    </button>
                </form>
            </div>
        </div>

        <!-- Payment Status Card -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment Status</h3>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Payment Status:</strong>
                    @if($booking->payment_status === 'paid')
                        <span class="badge bg-success">Paid</span>
                    @elseif($booking->payment_status === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif
                </p>
                <p class="mb-1"><strong>Amount:</strong> RM {{ number_format($booking->total_amount, 2) }}</p>
                @if($booking->payment_method)
                <p class="mb-0"><strong>Method:</strong> {{ ucfirst($booking->payment_method) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
<!--end::Row-->
@endsection
