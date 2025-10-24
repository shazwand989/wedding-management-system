@extends('layouts.admin')
@section('title', 'Customer Details')
@section('page-title', 'Customer Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-user"></i> Customer Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $customer->name }}</p>
                <p><strong>Email:</strong> {{ $customer->email }}</p>
                <p><strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}</p>
                <p><strong>Joined:</strong> {{ $customer->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title"><i class="fas fa-calendar-check"></i> Bookings History</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>ID</th><th>Event Date</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($customer->bookings as $booking)
                                <tr>
                                    <td>#{{ $booking->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</td>
                                    <td>RM {{ number_format($booking->total_amount, 2) }}</td>
                                    <td><span class="badge bg-{{ $booking->booking_status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($booking->booking_status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">No bookings found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
