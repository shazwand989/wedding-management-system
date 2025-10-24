@extends('layouts.customer')
@section('title', $vendor->business_name)
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <h3>{{ $vendor->business_name }}</h3>
                <p class="text-muted">{{ $vendor->category }}</p>
                <div class="mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= $vendor->average_rating ? 'text-warning' : 'text-muted' }}"></i>
                    @endfor
                    <span class="ms-1">({{ $vendor->reviews_count }} reviews)</span>
                </div>
                <p>{{ $vendor->description }}</p>
                <div class="row mt-4">
                    <div class="col-md-4"><strong>Email:</strong></div>
                    <div class="col-md-8">{{ $vendor->email }}</div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4"><strong>Phone:</strong></div>
                    <div class="col-md-8">{{ $vendor->phone }}</div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4"><strong>Address:</strong></div>
                    <div class="col-md-8">{{ $vendor->address }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5>Services Offered</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($vendor->services as $service)
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>{{ $service->name }}</h6>
                                <p class="text-muted mb-2">{{ $service->description }}</p>
                                <strong class="text-gold">RM {{ number_format($service->price, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6>Contact Vendor</h6></div>
            <div class="card-body d-grid gap-2">
                <a href="mailto:{{ $vendor->email }}" class="btn btn-gold"><i class="fas fa-envelope"></i> Send Email</a>
                <a href="tel:{{ $vendor->phone }}" class="btn btn-outline-gold"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6>Recent Reviews</h6></div>
            <div class="card-body">
                @foreach($vendor->recent_reviews as $review)
                <div class="mb-3">
                    <div class="mb-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star small {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </div>
                    <p class="mb-1">{{ $review->comment }}</p>
                    <small class="text-muted">- {{ $review->customer->name }}</small>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
