@extends('layouts.admin')
@section('title', 'Payments Management')
@section('page-title', 'Payments')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">Payments</li>
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-hover table-striped">
                        <thead>
                            <tr><th>ID</th><th>Booking</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>#{{ $payment->id }}</td>
                                    <td><a href="{{ route('admin.bookings.show', $payment->booking_id) }}">#{{ $payment->booking_id }}</a></td>
                                    <td>{{ $payment->booking->customer->name }}</td>
                                    <td><strong>RM {{ number_format($payment->amount, 2) }}</strong></td>
                                    <td>{{ ucfirst($payment->payment_method ?? 'N/A') }}</td>
                                    <td>
                                        @if($payment->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($payment->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->created_at->format('d M Y') }}</td>
                                    <td><a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-sm btn-outline-gold"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#paymentsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search payments..."
        }
    });
});
</script>
@endpush
