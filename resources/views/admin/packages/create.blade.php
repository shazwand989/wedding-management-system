@extends('layouts.admin')

@section('title', 'Create Package')
@section('page-title', 'Create Package')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Packages</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)); color: white;">
                <h3 class="card-title"><i class="fas fa-plus"></i> Create New Package</h3>
            </div>
            <form action="{{ route('admin.packages.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Package Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (RM) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                       id="price" name="price" value="{{ old('price') }}" required min="0" step="0.01">
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="duration_hours" class="form-label">Duration (Hours)</label>
                                <input type="number" class="form-control @error('duration_hours') is-invalid @enderror"
                                       id="duration_hours" name="duration_hours" value="{{ old('duration_hours', 8) }}" min="1">
                                @error('duration_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_guests" class="form-label">Max Guests</label>
                                <input type="number" class="form-control @error('max_guests') is-invalid @enderror"
                                       id="max_guests" name="max_guests" value="{{ old('max_guests', 100) }}" min="1">
                                @error('max_guests')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="features" class="form-label">Package Features & Inclusions</label>
                        <small class="text-muted d-block mb-2">Enter each feature on a new line</small>
                        <textarea class="form-control @error('features') is-invalid @enderror"
                                  id="features" name="features" rows="6" placeholder="Example:&#10;Professional Photography (8 hours)&#10;Videography with highlights reel&#10;Decoration and setup&#10;Bridal makeup and hairstyling">{{ old('features') }}</textarea>
                        @error('features')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">These features will be displayed as a checklist to customers</small>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Create Package</button>
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
