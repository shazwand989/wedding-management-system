@extends('layouts.customer')
@section('title', 'My Bookings')
@section('content')
<div class="row mb-3">
    <div class="col-12 text-end">
        <a href="{{ route('customer.bookings.create') }}" class="btn btn-gold"><i class="fas fa-plus"></i> New Booking</a>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customerBookingsTable" class="table table-striped">
                <thead><tr><th>ID</th><th>Package</th><th>Event Date</th><th>Venue</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($bookings as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->package->name ?? 'Custom' }}</td>
                            <td>{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</td>
                            <td>{{ $booking->venue_name }}</td>
                            <td>RM {{ number_format($booking->total_amount, 2) }}</td>
                            <td><span class="badge bg-{{ $booking->booking_status === 'confirmed' ? 'success' : 'warning' }}">{{ ucfirst($booking->booking_status) }}</span></td>
                            <td>
                                <a href="{{ route('customer.bookings.show', $booking->id) }}" class="btn btn-sm btn-gold"><i class="fas fa-eye"></i></a>
                                @if($booking->payment_status !== 'paid')
                                    <a href="{{ route('customer.payment.show', $booking->id) }}" class="btn btn-sm btn-success"><i class="fas fa-credit-card"></i> Pay</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#customerBookingsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search bookings..."
        }
    });
});
</script>
@endpush
