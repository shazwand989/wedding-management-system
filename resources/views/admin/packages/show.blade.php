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
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Price:</strong> RM {{ number_format($package->price, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong>
                            @if($package->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.packages.edit', $package->id) }}" class="btn btn-gold"><i class="fas fa-edit"></i> Edit</a>
                <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
