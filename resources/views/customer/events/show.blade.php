@extends('customer.layouts.app')

@section('title', $event->title)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $event->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.events.index') }}">Events</a></li>
                    <li class="breadcrumb-item active">{{ $event->title }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <!-- Event Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event Information</h3>
                        <div class="card-tools">
                            <a href="{{ route('customer.events.edit', $event->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Type:</strong> {{ $event->event_type }}</p>
                                <p><strong>Date:</strong> {{ $event->event_date->format('l, F j, Y') }}</p>
                                <p><strong>Time:</strong> {{ $event->event_time }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Venue:</strong> {{ $event->venue_name }}</p>
                                <p><strong>Status:</strong>
                                    @if($event->status === 'draft')
                                        <span class="badge text-bg-secondary">Draft</span>
                                    @elseif($event->status === 'published')
                                        <span class="badge text-bg-success">Published</span>
                                    @else
                                        <span class="badge text-bg-dark">Closed</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($event->description)
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p>{{ $event->description }}</p>
                        </div>
                        @endif

                        <div class="mb-3">
                            <strong>Address:</strong>
                            <p>{{ $event->venue_address }}</p>
                            @if($event->venue_google_maps)
                                <a href="{{ $event->venue_google_maps }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-map-marker-alt"></i> View on Google Maps
                                </a>
                            @endif
                        </div>

                        @if($event->special_instructions)
                        <div class="mb-3">
                            <strong>Special Instructions:</strong>
                            <p>{{ $event->special_instructions }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Invitation Link -->
                @if($event->status === 'published')
                <div class="card card-gold">
                    <div class="card-header">
                        <h3 class="card-title">Invitation Link</h3>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" id="invitationUrl"
                                   value="{{ $event->invitation_url }}" readonly>
                            <button class="btn btn-gold" type="button" onclick="copyInvitationLink()">
                                <i class="fas fa-copy"></i> Copy Link
                            </button>
                        </div>
                        <small class="text-muted">Share this link with your guests to send invitations</small>
                    </div>
                </div>
                @endif

                <!-- RSVP Responses -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">RSVP Responses</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Guest Name</th>
                                    <th>Email</th>
                                    <th>Response</th>
                                    <th>Guests</th>
                                    <th>Checked In</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->rsvps as $rsvp)
                                <tr>
                                    <td>{{ $rsvp->guest_name }}</td>
                                    <td>{{ $rsvp->guest_email }}</td>
                                    <td>
                                        @if($rsvp->response_status === 'yes')
                                            <span class="badge text-bg-success">Attending</span>
                                        @elseif($rsvp->response_status === 'no')
                                            <span class="badge text-bg-danger">Not Attending</span>
                                        @else
                                            <span class="badge text-bg-warning">Maybe</span>
                                        @endif
                                    </td>
                                    <td>{{ $rsvp->number_of_guests }}</td>
                                    <td>
                                        @if($rsvp->checked_in)
                                            <span class="badge text-bg-success">
                                                <i class="fas fa-check"></i> {{ $rsvp->checked_in_at->format('g:i A') }}
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">Not yet</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No RSVP responses yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- RSVP Statistics -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">RSVP Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total Invited:</span>
                                <strong>{{ $event->guests->count() }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total RSVPs:</span>
                                <strong>{{ $stats['total'] }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-check text-success"></i> Attending:</span>
                                <strong class="text-success">{{ $stats['yes'] }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-times text-danger"></i> Not Attending:</span>
                                <strong class="text-danger">{{ $stats['no'] }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-question text-warning"></i> Maybe:</span>
                                <strong class="text-warning">{{ $stats['maybe'] }}</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Pending:</span>
                                <strong class="text-muted">{{ $stats['pending'] }}</strong>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span><strong>Total Confirmed Guests:</strong></span>
                            <strong class="text-success">{{ $event->total_confirmed_guests }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('customer.events.guests', $event->id) }}" class="btn btn-gold w-100 mb-2">
                            <i class="fas fa-users"></i> Manage Guest List
                        </a>

                        @if($event->status === 'draft')
                        <form action="{{ route('customer.events.send-invitations', $event->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2"
                                    onclick="return confirm('Send invitations to all guests?')">
                                <i class="fas fa-paper-plane"></i> Send Invitations
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('customer.events.checkin', $event->id) }}" class="btn btn-info w-100 mb-2">
                            <i class="fas fa-qrcode"></i> Check-in Scanner
                        </a>

                        <form action="{{ route('customer.events.destroy', $event->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100"
                                    onclick="return confirm('Are you sure? This cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function copyInvitationLink() {
    const input = document.getElementById('invitationUrl');
    input.select();
    document.execCommand('copy');

    // Show toast notification
    $(document).Toasts('create', {
        class: 'bg-success',
        title: 'Success',
        body: 'Invitation link copied to clipboard!',
        autohide: true,
        delay: 3000
    });
}
</script>
@endpush
