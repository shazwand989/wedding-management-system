@extends('layouts.admin')

@section('title', 'Vendors Management')

@section('page-title', 'Vendors')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Vendors</li>
@endsection

@section('content')
<!--begin::Row-->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0">Manage all vendor accounts</p>
            </div>
            <div>
                <form action="{{ route('admin.vendors.index') }}" method="GET" class="d-inline-block">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search vendors..." value="{{ request('search') }}">
                        <select name="status" class="form-select" style="max-width: 150px;">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-gold">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Row-->

<!--begin::Row-->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="vendorsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Business Name</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendors as $vendor)
                                <tr>
                                    <td>#{{ $vendor->id }}</td>
                                    <td><strong>{{ $vendor->business_name }}</strong></td>
                                    <td>{{ $vendor->user->name }}</td>
                                    <td>{{ $vendor->user->email }}</td>
                                    <td>{{ $vendor->phone ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($vendor->category) }}</td>
                                    <td>
                                        @if($vendor->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($vendor->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vendor->status === 'pending')
                                            <form action="{{ route('admin.vendors.approve', $vendor->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.vendors.reject', $vendor->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-gold" disabled>
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Row-->
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#vendorsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search vendors..."
        }
    });
});
</script>
@endpush
