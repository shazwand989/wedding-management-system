@extends('layouts.vendor')
@section('title', 'Booking Details')
@section('page-title', 'Booking #' . $booking->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('vendor.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><h5>Booking Information</h5></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Customer Name:</strong></div>
                    <div class="col-md-8">{{ $booking->customer->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Contact:</strong></div>
                    <div class="col-md-8">{{ $booking->customer->email }} | {{ $booking->customer->phone }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Package:</strong></div>
                    <div class="col-md-8">{{ $booking->package->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Event Date:</strong></div>
                    <div class="col-md-8">{{ date('d M Y', strtotime($booking->event_date)) }} at {{ $booking->event_time }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Venue:</strong></div>
                    <div class="col-md-8">{{ $booking->venue_name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Guest Count:</strong></div>
                    <div class="col-md-8">{{ $booking->guest_count }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Total Amount:</strong></div>
                    <div class="col-md-8">RM {{ number_format($booking->total_amount, 2) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Status:</strong></div>
                    <div class="col-md-8"><span class="badge text-bg-{{ $booking->status == 'confirmed' ? 'success' : ($booking->status == 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($booking->status) }}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Payment Status:</strong></div>
                    <div class="col-md-8"><span class="badge text-bg-{{ $booking->payment_status == 'paid' ? 'success' : 'danger' }}">{{ ucfirst($booking->payment_status) }}</span></div>
                </div>
                @if($booking->special_requests)
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Special Requests:</strong></div>
                    <div class="col-md-8">{{ $booking->special_requests }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($booking->status == 'pending')
        <div class="card mb-3">
            <div class="card-header"><h6>Respond to Request</h6></div>
            <div class="card-body">
                <form action="{{ route('vendor.bookings.accept', $booking->id) }}" method="POST" class="d-grid gap-2 mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Accept this booking?')"><i class="fas fa-check"></i> Accept Booking</button>
                </form>
                <form action="{{ route('vendor.bookings.reject', $booking->id) }}" method="POST" class="d-grid gap-2">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this booking?')"><i class="fas fa-times"></i> Reject Booking</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><h6>Actions</h6></div>
            <div class="card-body d-grid gap-2">
                <a href="mailto:{{ $booking->customer->email }}" class="btn btn-outline-gold"><i class="fas fa-envelope"></i> Email Customer</a>
                <a href="tel:{{ $booking->customer->phone }}" class="btn btn-outline-gold"><i class="fas fa-phone"></i> Call Customer</a>
                <a href="{{ route('vendor.calendar') }}" class="btn btn-outline-secondary"><i class="fas fa-calendar"></i> View Calendar</a>
                <a href="{{ route('vendor.bookings.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection
