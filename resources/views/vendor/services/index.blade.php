@extends('layouts.vendor')
@section('title', 'My Services')
@section('page-title', 'Services')
@section('breadcrumb')
    <li class="breadcrumb-item active">Services</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <h5>Manage Services</h5>
        <button class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#addServiceModal"><i class="fas fa-plus"></i> Add Service</button>
    </div>
    <div class="card-body p-0">
        <table id="servicesTable" class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Price (RM)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td>{{ Str::limit($service->description, 50) }}</td>
                    <td>RM {{ number_format($service->price, 2) }}</td>
                    <td><span class="badge text-bg-{{ $service->is_active ? 'success' : 'secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-gold" onclick="editService({{ $service->id }})"><i class="fas fa-edit"></i></button>
                        <form action="{{ route('vendor.services.toggle', $service->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $service->is_active ? 'warning' : 'success' }}"><i class="fas fa-{{ $service->is_active ? 'pause' : 'play' }}"></i></button>
                        </form>
                        <form action="{{ route('vendor.services.destroy', $service->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('vendor.services.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Service Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Price (RM)</label>
                        <input type="number" name="price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label>Service Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" checked>
                        <label class="form-check-label" for="activeCheck">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold">Add Service</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editServiceForm" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Service Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Price (RM)</label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label>Service Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold">Update Service</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#servicesTable').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search services..."
        }
    });
});

function editService(id) {
    fetch(`/vendor/services/${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_price').value = data.price;
            document.getElementById('editServiceForm').action = `/vendor/services/${id}`;
            new bootstrap.Modal(document.getElementById('editServiceModal')).show();
        });
}
</script>
@endpush
@endsection
