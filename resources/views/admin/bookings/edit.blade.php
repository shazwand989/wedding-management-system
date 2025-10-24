@extends('layouts.admin')

@section('title', 'Edit Booking')

@section('page-title', 'Edit Booking')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<!--begin::Row-->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-edit"></i> Edit Booking #{{ $booking->id }}</h3>
            </div>
            <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('event_date') is-invalid @enderror"
                                       id="event_date" name="event_date"
                                       value="{{ old('event_date', $booking->event_date) }}" required>
                                @error('event_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_time" class="form-label">Event Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('event_time') is-invalid @enderror"
                                       id="event_time" name="event_time"
                                       value="{{ old('event_time', $booking->event_time) }}" required>
                                @error('event_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="venue_name" class="form-label">Venue Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('venue_name') is-invalid @enderror"
                                       id="venue_name" name="venue_name"
                                       value="{{ old('venue_name', $booking->venue_name) }}" required>
                                @error('venue_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guest_count" class="form-label">Guest Count <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('guest_count') is-invalid @enderror"
                                       id="guest_count" name="guest_count"
                                       value="{{ old('guest_count', $booking->guest_count) }}" required min="1">
                                @error('guest_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="booking_status" class="form-label">Booking Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('booking_status') is-invalid @enderror"
                                        id="booking_status" name="booking_status" required>
                                    <option value="pending" {{ old('booking_status', $booking->booking_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ old('booking_status', $booking->booking_status) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="completed" {{ old('booking_status', $booking->booking_status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('booking_status', $booking->booking_status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('booking_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_status') is-invalid @enderror"
                                        id="payment_status" name="payment_status" required>
                                    <option value="pending" {{ old('payment_status', $booking->payment_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="paid" {{ old('payment_status', $booking->payment_status) === 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="failed" {{ old('payment_status', $booking->payment_status) === 'failed' ? 'selected' : '' }}>Failed</option>
                                </select>
                                @error('payment_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="total_amount" class="form-label">Total Amount (RM) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('total_amount') is-invalid @enderror"
                               id="total_amount" name="total_amount"
                               value="{{ old('total_amount', $booking->total_amount) }}"
                               required min="0" step="0.01">
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="special_requests" class="form-label">Special Requests</label>
                        <textarea class="form-control @error('special_requests') is-invalid @enderror"
                                  id="special_requests" name="special_requests" rows="4">{{ old('special_requests', $booking->special_requests) }}</textarea>
                        @error('special_requests')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-save"></i> Update Booking
                    </button>
                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Row-->
@endsection
