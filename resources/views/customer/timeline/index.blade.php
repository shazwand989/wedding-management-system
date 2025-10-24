@extends('layouts.customer')
@section('title', 'Wedding Timeline')
@section('content')
<div class="card">
    <div class="card-header">
        <h5>Wedding Planning Timeline</h5>
        <button type="button" class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="fas fa-plus"></i> Add Task</button>
    </div>
    <div class="card-body">
        @foreach($timeline as $phase => $tasks)
        <div class="mb-4">
            <h6 class="text-gold">{{ $phase }}</h6>
            <div class="list-group">
                @foreach($tasks as $task)
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" class="form-check-input me-3" {{ $task->completed ? 'checked' : '' }} onchange="toggleTask({{ $task->id }})">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 {{ $task->completed ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</h6>
                            <p class="mb-1 text-muted small">{{ $task->description }}</p>
                            <small class="text-muted"><i class="fas fa-calendar"></i> Due: {{ date('d M Y', strtotime($task->due_date)) }}</small>
                        </div>
                        <span class="badge text-bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'secondary') }}">{{ ucfirst($task->priority) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('customer.timeline.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Task Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold">Add Task</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleTask(id) {
    fetch(`/customer/timeline/${id}/toggle`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'}
    }).then(r => r.json()).then(data => {
        if(data.success) location.reload();
    });
}
</script>
@endpush
@endsection
