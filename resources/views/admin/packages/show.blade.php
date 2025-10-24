@extends('layouts.admin')
@section('title', 'Package Details')
@section('page-title', 'Package Details')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Packages</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-box"></i> {{ $package->name }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted">Description</h6>
                    <p>{{ $package->description }}</p>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-dollar-sign text-gold"></i> Price:</strong><br>
                        <span class="h4 text-gold">RM {{ number_format($package->price, 2) }}</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-clock text-info"></i> Duration:</strong><br>
                        {{ $package->duration_hours }} Hours</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-users text-primary"></i> Max Guests:</strong><br>
                        {{ $package->max_guests }} People</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p><strong>Status:</strong>
                            @if($package->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($package->features && count($package->features) > 0)
                <hr>
                <div class="mb-3">
                    <h6 class="text-muted"><i class="fas fa-list-check"></i> Package Features & Inclusions</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($package->features as $feature)
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success"></i> {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.packages.edit', $package->id) }}" class="btn btn-gold"><i class="fas fa-edit"></i> Edit</a>
                <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Total Bookings</h6>
                    <h3 class="text-info">{{ $package->bookings->count() }}</h3>
                </div>
                <div class="mb-3">
                    <h6>Total Revenue</h6>
                    <h3 class="text-success">RM {{ number_format($package->bookings->sum('total_amount'), 2) }}</h3>
                </div>
                <div class="mb-3">
                    <h6>Active Bookings</h6>
                    <h3 class="text-warning">{{ $package->bookings->whereIn('booking_status', ['pending', 'confirmed'])->count() }}</h3>
                </div>
            </div>
        </div>

        @if($package->bookings->count() > 0)
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-check"></i> Recent Bookings</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($package->bookings->take(5) as $booking)
                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $booking->customer->name }}</strong>
                                <span class="badge bg-{{ $booking->booking_status === 'confirmed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($booking->booking_status) }}
                                </span>
                            </div>
                            <small class="text-muted">{{ $booking->event_date }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
