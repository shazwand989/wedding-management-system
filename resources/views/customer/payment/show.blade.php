@extends('layouts.customer')
@section('title', 'Payment')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Payment for Booking #{{ $booking->id }}</h5></div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Booking Details</h6>
                        <p class="mb-1"><strong>Package:</strong> {{ $booking->package->name }}</p>
                        <p class="mb-1"><strong>Event Date:</strong> {{ date('d M Y', strtotime($booking->event_date)) }}</p>
                        <p class="mb-1"><strong>Venue:</strong> {{ $booking->venue_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>RM {{ number_format($booking->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (6%):</span>
                            <span>RM {{ number_format($booking->total_amount * 0.06, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-gold">RM {{ number_format($booking->total_amount * 1.06, 2) }}</strong>
                        </div>
                    </div>
                </div>

                <form action="{{ route('customer.payment.process', $booking->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="toyyibpay">Online Banking (ToyyibPay)</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You will be redirected to a secure payment gateway to complete your transaction.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-lock"></i> Proceed to Payment</button>
                        <a href="{{ route('customer.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
