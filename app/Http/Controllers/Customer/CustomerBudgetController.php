<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerBudgetController extends Controller
{
    public function index()
    {
        $budget = Budget::where('customer_id', Auth::id())->first();
        $expenses = [];
        $totalSpent = 0;
        $remaining = 0;

        if ($budget) {
            $expenses = BudgetExpense::where('budget_id', $budget->id)
                ->orderBy('created_at', 'desc')
                ->get();
            $totalSpent = $expenses->sum('amount');
            $remaining = $budget->total_budget - $totalSpent;
        }

        return view('customer.budget.index', compact('budget', 'expenses', 'totalSpent', 'remaining'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_budget' => 'required|numeric|min:0',
        ]);

        Budget::updateOrCreate(
            ['customer_id' => Auth::id()],
            ['total_budget' => $validated['total_budget']]
        );

        return redirect()->route('customer.budget.index')
            ->with('success', 'Budget updated successfully');
    }

    public function addExpense(Request $request)
    {
        $budget = Budget::where('customer_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        BudgetExpense::create([
            'budget_id' => $budget->id,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
        ]);

        return redirect()->route('customer.budget.index')
            ->with('success', 'Expense added successfully');
    }

    public function deleteExpense($id)
    {
        $budget = Budget::where('customer_id', Auth::id())->firstOrFail();
        $expense = BudgetExpense::where('budget_id', $budget->id)->findOrFail($id);
        $expense->delete();

        return redirect()->route('customer.budget.index')
            ->with('success', 'Expense deleted successfully');
    }
}
