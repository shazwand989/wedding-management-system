@extends('layouts.customer')
@section('title', 'Browse Vendors')
@section('content')
<div class="card">
    <div class="card-header">
        <h5>Vendors Directory</h5>
        <div class="card-tools">
            <input type="search" class="form-control" placeholder="Search vendors..." id="vendorSearch">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="row g-3 p-3">
            @foreach($vendors as $vendor)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>{{ $vendor->business_name }}</h5>
                        <p class="text-muted mb-2">{{ $vendor->category }}</p>
                        <p class="mb-2">{{ Str::limit($vendor->description, 100) }}</p>
                        <div class="mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $vendor->average_rating ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                            <span class="ms-1">({{ $vendor->reviews_count }} reviews)</span>
                        </div>
                        <a href="{{ route('customer.vendors.show', $vendor->id) }}" class="btn btn-sm btn-outline-gold">View Details</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer">{{ $vendors->links() }}</div>
</div>
@endsection
