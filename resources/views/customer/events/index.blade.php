@extends('customer.layouts.app')

@section('title', 'My Events & Invitations')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Events & Invitations</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Events</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <a href="{{ route('customer.events.create') }}" class="btn btn-gold">
                    <i class="fas fa-plus"></i> Create New Event
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Events</h3>
            </div>
            <div class="card-body">
                <table id="eventsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>RSVPs</th>
                            <th>Guests</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->title }}</strong><br>
                                <small class="text-muted">{{ $event->event_type }}</small>
                            </td>
                            <td>
                                {{ $event->event_date->format('M d, Y') }}<br>
                                <small class="text-muted">{{ $event->event_time }}</small>
                            </td>
                            <td>{{ $event->venue_name }}</td>
                            <td>
                                @if($event->status === 'draft')
                                    <span class="badge text-bg-secondary">Draft</span>
                                @elseif($event->status === 'published')
                                    <span class="badge text-bg-success">Published</span>
                                @else
                                    <span class="badge text-bg-dark">Closed</span>
                                @endif
                            </td>
                            <td>{{ $event->rsvps_count }}</td>
                            <td>{{ $event->guests_count }}</td>
                            <td>
                                <a href="{{ route('customer.events.show', $event->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('customer.events.edit', $event->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#eventsTable').DataTable({
        responsive: true,
        order: [[1, 'desc']]
    });
});
</script>
@endpush
