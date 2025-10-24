@extends('layouts.vendor')
@section('title', 'Business Profile')
@section('page-title', 'Edit Profile')
@section('breadcrumb')
    <li class="breadcrumb-item active">Profile</li>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Business Information</h5></div>
            <form action="{{ route('vendor.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="mb-3">
                        <label>Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="{{ $vendor->business_name }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $vendor->email }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $vendor->phone }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Venue" {{ $vendor->category == 'Venue' ? 'selected' : '' }}>Venue</option>
                            <option value="Catering" {{ $vendor->category == 'Catering' ? 'selected' : '' }}>Catering</option>
                            <option value="Photography" {{ $vendor->category == 'Photography' ? 'selected' : '' }}>Photography</option>
                            <option value="Decoration" {{ $vendor->category == 'Decoration' ? 'selected' : '' }}>Decoration</option>
                            <option value="Entertainment" {{ $vendor->category == 'Entertainment' ? 'selected' : '' }}>Entertainment</option>
                            <option value="Planning" {{ $vendor->category == 'Planning' ? 'selected' : '' }}>Wedding Planning</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required>{{ $vendor->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ $vendor->address }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>Business Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if($vendor->logo)
                            <small class="text-muted">Current logo: {{ basename($vendor->logo) }}</small>
                        @endif
                    </div>
                    <hr>
                    <h6>Change Password (Optional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>New Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($vendor->logo)
                    <img src="{{ asset($vendor->logo) }}" alt="Logo" class="img-fluid mb-3" style="max-height: 150px;">
                @else
                    <i class="fas fa-store fa-5x text-gold mb-3"></i>
                @endif
                <h5>{{ $vendor->business_name }}</h5>
                <p class="text-muted">{{ $vendor->category }}</p>
                <p><small>Member since {{ date('M Y', strtotime($vendor->created_at)) }}</small></p>
                <div class="mt-3">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= $vendor->average_rating ? 'text-warning' : 'text-muted' }}"></i>
                    @endfor
                    <p class="mt-1"><small>({{ $vendor->reviews_count }} reviews)</small></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
