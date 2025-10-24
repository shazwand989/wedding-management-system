@extends('layouts.customer')

@section('title', 'Create Event')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create New Event</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.events.index') }}">Events</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="{{ route('customer.events.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Event Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                       id="title" name="title" value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="event_type" class="form-label">Event Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('event_type') is-invalid @enderror"
                                        id="event_type" name="event_type" required>
                                    <option value="">Select type...</option>
                                    <option value="Wedding">Wedding</option>
                                    <option value="Engagement">Engagement</option>
                                    <option value="Reception">Reception</option>
                                    <option value="Rehearsal Dinner">Rehearsal Dinner</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('event_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="booking_id" class="form-label">Link to Booking (Optional)</label>
                                <select class="form-select" id="booking_id" name="booking_id">
                                    <option value="">None</option>
                                    @foreach($bookings as $booking)
                                        <option value="{{ $booking->id }}">
                                            Booking #{{ $booking->id }} - {{ $booking->event_date->format('M d, Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('event_date') is-invalid @enderror"
                                               id="event_date" name="event_date" value="{{ old('event_date') }}" required>
                                        @error('event_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_time" class="form-label">Event Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control @error('event_time') is-invalid @enderror"
                                               id="event_time" name="event_time" value="{{ old('event_time') }}" required>
                                        @error('event_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="venue_name" class="form-label">Venue Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('venue_name') is-invalid @enderror"
                                       id="venue_name" name="venue_name" value="{{ old('venue_name') }}" required>
                                @error('venue_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="venue_address" class="form-label">Venue Address <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('venue_address') is-invalid @enderror"
                                          id="venue_address" name="venue_address" rows="3" required>{{ old('venue_address') }}</textarea>
                                @error('venue_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="venue_google_maps" class="form-label">Google Maps Link (Optional)</label>
                                <input type="url" class="form-control" id="venue_google_maps"
                                       name="venue_google_maps" value="{{ old('venue_google_maps') }}"
                                       placeholder="https://goo.gl/maps/...">
                            </div>

                            <div class="mb-3">
                                <label for="special_instructions" class="form-label">Special Instructions</label>
                                <textarea class="form-control" id="special_instructions"
                                          name="special_instructions" rows="3">{{ old('special_instructions') }}</textarea>
                                <small class="text-muted">e.g., Dress code, parking instructions, etc.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">RSVP Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="max_guests" class="form-label">Maximum Guests</label>
                                <input type="number" class="form-control" id="max_guests"
                                       name="max_guests" value="{{ old('max_guests') }}" min="1">
                                <small class="text-muted">Leave empty for unlimited</small>
                            </div>

                            <div class="mb-3">
                                <label for="rsvp_deadline" class="form-label">RSVP Deadline</label>
                                <input type="date" class="form-control" id="rsvp_deadline"
                                       name="rsvp_deadline" value="{{ old('rsvp_deadline') }}">
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="allow_plus_one"
                                           name="allow_plus_one" value="1" {{ old('allow_plus_one') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_plus_one">
                                        Allow Plus One
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-gold w-100">
                                <i class="fas fa-save"></i> Create Event
                            </button>
                            <a href="{{ route('customer.events.index') }}" class="btn btn-secondary w-100 mt-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
