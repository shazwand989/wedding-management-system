@extends('layouts.customer')
@section('title', 'Budget Tracker')
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Expenses</h5>
                <button class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="fas fa-plus"></i> Add Expense</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        <tr>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>RM {{ number_format($expense->amount, 2) }}</td>
                            <td><span class="badge text-bg-{{ $expense->paid ? 'success' : 'warning' }}">{{ $expense->paid ? 'Paid' : 'Pending' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editExpense({{ $expense->id }})"><i class="fas fa-edit"></i></button>
                                <form action="{{ route('customer.budget.destroy', $expense->id) }}" method="POST" class="d-inline">
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
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <h6>Budget Summary</h6>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Budget:</span>
                        <strong>RM {{ number_format($totalBudget, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Spent:</span>
                        <strong class="text-danger">RM {{ number_format($totalSpent, 2) }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Remaining:</span>
                        <strong class="text-{{ $remaining >= 0 ? 'success' : 'danger' }}">RM {{ number_format($remaining, 2) }}</strong>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 25px;">
                    <div class="progress-bar bg-gold" style="width: {{ min(($totalSpent / $totalBudget) * 100, 100) }}%">{{ round(($totalSpent / $totalBudget) * 100) }}%</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6>Expenses by Category</h6></div>
            <div class="card-body">
                @foreach($expensesByCategory as $category => $amount)
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ $category }}:</span>
                    <strong>RM {{ number_format($amount, 2) }}</strong>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('customer.budget.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Add Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Venue">Venue</option>
                            <option value="Catering">Catering</option>
                            <option value="Photography">Photography</option>
                            <option value="Decoration">Decoration</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Amount (RM)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="paid" class="form-check-input" id="paidCheck">
                        <label class="form-check-label" for="paidCheck">Mark as Paid</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold">Add Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
