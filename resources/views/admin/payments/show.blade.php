@extends('layouts.admin')
@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-credit-card"></i> Payment #{{ $payment->id }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Booking ID:</strong> <a href="{{ route('admin.bookings.show', $payment->booking_id) }}">#{{ $payment->booking_id }}</a></p>
                        <p><strong>Customer:</strong> {{ $payment->booking->customer->name }}</p>
                        <p><strong>Amount:</strong> RM {{ number_format($payment->amount, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method ?? 'N/A') }}</p>
                        <p><strong>Status:</strong>
                            @if($payment->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($payment->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </p>
                        <p><strong>Date:</strong> {{ $payment->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
                @if($payment->transaction_id)
                <hr>
                <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
