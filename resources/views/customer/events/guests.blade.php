@extends('customer.layouts.app')

@section('title', 'Manage Guest List - ' . $event->title)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Manage Guest List</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.events.index') }}">Events</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.events.show', $event->id) }}">{{ $event->title }}</a></li>
                    <li class="breadcrumb-item active">Guest List</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Add New Guest</h3>
                    </div>
                    <form action="{{ route('customer.events.guests.add', $event->id) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone"
                                       name="phone" value="{{ old('phone') }}">
                            </div>

                            <div class="mb-3">
                                <label for="group_name" class="form-label">Group</label>
                                <select class="form-select" id="group_name" name="group_name">
                                    <option value="">None</option>
                                    <option value="Family">Family</option>
                                    <option value="Friends">Friends</option>
                                    <option value="Colleagues">Colleagues</option>
                                    <option value="VIP">VIP</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-gold w-100">
                                <i class="fas fa-user-plus"></i> Add Guest
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Guest List ({{ $event->guests->count() }})</h3>
                    </div>
                    <div class="card-body">
                        <table id="guestsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Group</th>
                                    <th>Invitation</th>
                                    <th>RSVP Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($event->guests as $guest)
                                <tr>
                                    <td>{{ $guest->name }}</td>
                                    <td>{{ $guest->email }}</td>
                                    <td>
                                        @if($guest->group_name)
                                            <span class="badge text-bg-info">{{ $guest->group_name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($guest->invitation_sent)
                                            <span class="badge text-bg-success">
                                                <i class="fas fa-check"></i> Sent {{ $guest->invitation_sent_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="badge text-bg-secondary">Not sent</span>
                                        @endif
                                        <br>
                                        @if($guest->viewed_invitation)
                                            <small class="text-muted">
                                                <i class="fas fa-eye"></i> Viewed {{ $guest->viewed_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($guest->rsvp)
                                            @if($guest->rsvp->response_status === 'yes')
                                                <span class="badge text-bg-success">
                                                    <i class="fas fa-check"></i> Attending ({{ $guest->rsvp->number_of_guests }})
                                                </span>
                                            @elseif($guest->rsvp->response_status === 'no')
                                                <span class="badge text-bg-danger">
                                                    <i class="fas fa-times"></i> Not Attending
                                                </span>
                                            @else
                                                <span class="badge text-bg-warning">
                                                    <i class="fas fa-question"></i> Maybe
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge text-bg-secondary">No response</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('customer.events.show', $event->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Event Dashboard
                        </a>

                        @if($event->status === 'draft' && $event->guests->count() > 0)
                        <form action="{{ route('customer.events.send-invitations', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Send invitations to all {{ $event->guests->count() }} guests?')">
                                <i class="fas fa-paper-plane"></i> Send Invitations to All
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#guestsTable').DataTable({
        responsive: true,
        order: [[0, 'asc']]
    });
});
</script>
@endpush
