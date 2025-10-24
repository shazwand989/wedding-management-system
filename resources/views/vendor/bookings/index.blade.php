@extends('layouts.vendor')
@section('title', 'My Bookings')
@section('page-title', 'Bookings')
@section('breadcrumb')
    <li class="breadcrumb-item active">Bookings</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Booking Requests</h5>
        <div class="card-tools">
            <select class="form-select form-select-sm" onchange="window.location.href='?status='+this.value">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <table id="vendorBookingsTable" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Event Date</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                <tr>
                    <td>#{{ $booking->id }}</td>
                    <td>{{ $booking->customer->name }}</td>
                    <td>{{ $booking->package->name }}</td>
                    <td>{{ date('d M Y', strtotime($booking->event_date)) }}</td>
                    <td>{{ $booking->venue_name }}</td>
                    <td><span class="badge text-bg-{{ $booking->status == 'confirmed' ? 'success' : ($booking->status == 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($booking->status) }}</span></td>
                    <td><span class="badge text-bg-{{ $booking->payment_status == 'paid' ? 'success' : 'danger' }}">{{ ucfirst($booking->payment_status) }}</span></td>
                    <td><a href="{{ route('vendor.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-gold">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#vendorBookingsTable').DataTable({
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
