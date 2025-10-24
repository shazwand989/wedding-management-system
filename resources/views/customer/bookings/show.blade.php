@extends('layouts.customer')
@section('title', 'Booking Details')
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><h5>Booking #{{ $booking->id }}</h5></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Package:</strong></div>
                    <div class="col-md-8">{{ $booking->package->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Event Date:</strong></div>
                    <div class="col-md-8">{{ date('d M Y', strtotime($booking->event_date)) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Event Time:</strong></div>
                    <div class="col-md-8">{{ $booking->event_time }}</div>
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
                    <div class="col-md-4"><strong>Payment:</strong></div>
                    <div class="col-md-8"><span class="badge text-bg-{{ $booking->payment_status == 'paid' ? 'success' : 'danger' }}">{{ ucfirst($booking->payment_status) }}</span></div>
                </div>
            </div>
        </div>

        @if($booking->special_requests)
        <div class="card">
            <div class="card-header"><h6>Special Requests</h6></div>
            <div class="card-body">{{ $booking->special_requests }}</div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6>Quick Actions</h6></div>
            <div class="card-body d-grid gap-2">
                @if($booking->payment_status !== 'paid')
                    <a href="{{ route('customer.payment.show', $booking->id) }}" class="btn btn-gold"><i class="fas fa-credit-card"></i> Make Payment</a>
                @endif
                <a href="{{ route('customer.timeline.index') }}" class="btn btn-outline-secondary"><i class="fas fa-tasks"></i> View Timeline</a>
                <a href="{{ route('customer.bookings.index') }}" class="btn btn-secondary">Back to Bookings</a>
            </div>
        </div>
    </div>
</div>
@endsection
