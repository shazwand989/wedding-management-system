@extends('layouts.customer')
@section('title', 'Payment Status')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        @if($payment->status == 'success')
        <div class="card border-success">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h3>Payment Successful!</h3>
                <p class="text-muted">Your payment has been processed successfully.</p>

                <div class="my-4">
                    <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
                    <p><strong>Amount Paid:</strong> RM {{ number_format($payment->amount, 2) }}</p>
                    <p><strong>Date:</strong> {{ date('d M Y H:i', strtotime($payment->created_at)) }}</p>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('customer.bookings.show', $payment->booking_id) }}" class="btn btn-gold">View Booking</a>
                    <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary">Go to Dashboard</a>
                </div>
            </div>
        </div>
        @else
        <div class="card border-danger">
            <div class="card-body text-center py-5">
                <i class="fas fa-times-circle fa-5x text-danger mb-3"></i>
                <h3>Payment Failed</h3>
                <p class="text-muted">{{ $payment->error_message ?? 'Your payment could not be processed.' }}</p>

                <div class="d-grid gap-2 mt-4">
                    <a href="{{ route('customer.payment.show', $payment->booking_id) }}" class="btn btn-gold">Try Again</a>
                    <a href="{{ route('customer.bookings.show', $payment->booking_id) }}" class="btn btn-outline-secondary">Back to Booking</a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
