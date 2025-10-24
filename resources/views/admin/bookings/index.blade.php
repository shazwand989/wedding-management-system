@extends('layouts.admin')

@section('title', 'Bookings Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0"><i class="fas fa-calendar-check text-warning"></i> Bookings Management</h2>
            <p class="text-muted">Manage all wedding bookings</p>
        </div>
        <div class="col-md-6 text-end">
            <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-inline-block">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search bookings..." value="{{ request('search') }}">
                    <select name="status" class="form-select" style="max-width: 150px;">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Guests</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>
                                    <strong>{{ $booking->customer->name }}</strong><br>
                                    <small class="text-muted">{{ $booking->customer->email }}</small>
                                </td>
                                <td>{{ $booking->package->name ?? 'Custom' }}</td>
                                <td>{{ \Carbon\Carbon::parse($booking->event_date)->format('d M Y') }}</td>
                                <td>{{ $booking->venue_name }}</td>
                                <td>{{ $booking->guest_count }}</td>
                                <td><strong>RM {{ number_format($booking->total_amount, 2) }}</strong></td>
                                <td>
                                    @if($booking->booking_status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($booking->booking_status === 'confirmed')
                                        <span class="badge bg-info">Confirmed</span>
                                    @elseif($booking->booking_status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-gold" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection
