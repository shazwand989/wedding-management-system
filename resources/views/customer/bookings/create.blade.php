@extends('layouts.customer')
@section('title', 'Create Booking')
@section('content')
<div class="card">
    <div class="card-header"><h5>Create New Booking</h5></div>
    <form action="{{ route('customer.bookings.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Package</label>
                    <select name="package_id" class="form-select" required>
                        <option value="">Select Package</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }} - RM {{ number_format($package->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Event Date</label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Event Time</label>
                    <input type="time" name="event_time" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Guest Count</label>
                    <input type="number" name="guest_count" class="form-control" required min="1">
                </div>
            </div>
            <div class="mb-3">
                <label>Venue Name</label>
                <input type="text" name="venue_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Special Requests</label>
                <textarea name="special_requests" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Create Booking</button>
            <a href="{{ route('customer.bookings.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
