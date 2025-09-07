<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Budget Tracker';
$breadcrumbs = [
    ['title' => 'Budget Tracker']
];

$customer_id = $_SESSION['user_id'];

// Handle budget actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'set_budget') {
        $total_budget = (float)($_POST['total_budget'] ?? 0);
        
        if ($total_budget > 0) {
            try {
                // Check if budget already exists
                $stmt = $pdo->prepare("SELECT id FROM wedding_budgets WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                
                if ($stmt->fetch()) {
                    // Update existing budget
                    $stmt = $pdo->prepare("UPDATE wedding_budgets SET total_budget = ?, updated_at = NOW() WHERE customer_id = ?");
                    $stmt->execute([$total_budget, $customer_id]);
                } else {
                    // Insert new budget
                    $stmt = $pdo->prepare("INSERT INTO wedding_budgets (customer_id, total_budget, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$customer_id, $total_budget]);
                }
                
                $success_message = "Budget updated successfully!";
            } catch (PDOException $e) {
                $error_message = "Error updating budget: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'add_expense') {
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $vendor_name = $_POST['vendor_name'] ?? '';
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        
        if (!empty($category) && !empty($description) && $amount > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO budget_expenses (customer_id, category, description, amount, vendor_name, expense_date, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$customer_id, $category, $description, $amount, $vendor_name, $expense_date]);
                $success_message = "Expense added successfully!";
            } catch (PDOException $e) {
                $error_message = "Error adding expense: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_expense') {
        $expense_id = (int)$_POST['expense_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM budget_expenses WHERE id = ? AND customer_id = ?");
            $stmt->execute([$expense_id, $customer_id]);
            $success_message = "Expense deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting expense: " . $e->getMessage();
        }
    }
}

// Get budget information
try {
    $stmt = $pdo->prepare("SELECT * FROM wedding_budgets WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $budget = $stmt->fetch();
    
    // Get expenses
    $stmt = $pdo->prepare("
        SELECT * FROM budget_expenses 
        WHERE customer_id = ? 
        ORDER BY expense_date DESC, created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $expenses = $stmt->fetchAll();
    
    // Calculate category totals
    $category_totals = [];
    $total_spent = 0;
    
    foreach ($expenses as $expense) {
        $category = $expense['category'];
        if (!isset($category_totals[$category])) {
            $category_totals[$category] = 0;
        }
        $category_totals[$category] += $expense['amount'];
        $total_spent += $expense['amount'];
    }
    
    // Budget categories with recommended percentages
    $recommended_categories = [
        'venue' => ['name' => 'Venue & Catering', 'percentage' => 40],
        'photography' => ['name' => 'Photography & Videography', 'percentage' => 15],
        'attire' => ['name' => 'Wedding Attire', 'percentage' => 10],
        'flowers' => ['name' => 'Flowers & Decorations', 'percentage' => 10],
        'music' => ['name' => 'Music & Entertainment', 'percentage' => 10],
        'transportation' => ['name' => 'Transportation', 'percentage' => 5],
        'stationery' => ['name' => 'Invitations & Stationery', 'percentage' => 3],
        'rings' => ['name' => 'Wedding Rings', 'percentage' => 3],
        'miscellaneous' => ['name' => 'Miscellaneous', 'percentage' => 4]
    ];

} catch (PDOException $e) {
    $error_message = "Error loading budget: " . $e->getMessage();
    $budget = null;
    $expenses = [];
    $category_totals = [];
    $total_spent = 0;
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Budget Overview -->
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-piggy-bank fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($budget['total_budget'] ?? 0, 2); ?></h3>
                    <p>Total Budget</p>
                    <?php if (!$budget): ?>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#setBudgetModal">
                            Set Budget
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#setBudgetModal">
                            Update Budget
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-credit-card fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format($total_spent, 2); ?></h3>
                    <p>Total Spent</p>
                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addExpenseModal">
                        Add Expense
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-<?php echo ($budget && $total_spent > $budget['total_budget']) ? 'danger' : 'success'; ?>">
                <div class="card-body text-center">
                    <i class="fas fa-wallet fa-2x mb-3"></i>
                    <h3>RM <?php echo number_format(($budget['total_budget'] ?? 0) - $total_spent, 2); ?></h3>
                    <p>Remaining</p>
                    <?php if ($budget && $total_spent > $budget['total_budget']): ?>
                        <small class="text-danger">Over Budget!</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($budget): ?>
        <!-- Budget Progress -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Budget Progress</h3>
            </div>
            <div class="card-body">
                <?php
                $spent_percentage = $budget['total_budget'] > 0 ? ($total_spent / $budget['total_budget']) * 100 : 0;
                $progress_color = $spent_percentage > 100 ? 'danger' : ($spent_percentage > 80 ? 'warning' : 'success');
                ?>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-<?php echo $progress_color; ?>" 
                         style="width: <?php echo min($spent_percentage, 100); ?>%">
                        <?php echo number_format($spent_percentage, 1); ?>%
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>Budget:</strong> RM <?php echo number_format($budget['total_budget'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Spent:</strong> RM <?php echo number_format($total_spent, 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Remaining:</strong> RM <?php echo number_format($budget['total_budget'] - $total_spent, 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Budget by Category</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recommended_categories as $key => $category): ?>
                        <?php
                        $recommended_amount = ($budget['total_budget'] * $category['percentage']) / 100;
                        $spent_amount = $category_totals[$key] ?? 0;
                        $spent_percentage = $recommended_amount > 0 ? ($spent_amount / $recommended_amount) * 100 : 0;
                        $status_color = $spent_percentage > 100 ? 'danger' : ($spent_percentage > 80 ? 'warning' : 'success');
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $category['name']; ?></h6>
                                    <div class="mb-2">
                                        <small class="text-muted">Recommended: <?php echo $category['percentage']; ?>%</small>
                                    </div>
                                    <div class="progress mb-2" style="height: 15px;">
                                        <div class="progress-bar bg-<?php echo $status_color; ?>" 
                                             style="width: <?php echo min($spent_percentage, 100); ?>%">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small>
                                            <strong>Budget:</strong><br>
                                            RM <?php echo number_format($recommended_amount, 0); ?>
                                        </small>
                                        <small>
                                            <strong>Spent:</strong><br>
                                            RM <?php echo number_format($spent_amount, 0); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Expenses -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Expenses</h3>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addExpenseModal">
                    <i class="fas fa-plus"></i> Add Expense
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($expenses)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                    <h4>No Expenses Recorded</h4>
                    <p class="text-muted">Start tracking your wedding expenses to stay within budget.</p>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addExpenseModal">
                        <i class="fas fa-plus"></i> Add Your First Expense
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo ucfirst(str_replace('_', ' ', $expense['category'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['vendor_name'] ?: '-'); ?></td>
                                    <td>
                                        <strong class="text-danger">RM <?php echo number_format($expense['amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deleteExpense(<?php echo $expense['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="4" class="text-right">Total Expenses:</th>
                                <th>RM <?php echo number_format($total_spent, 2); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Set Budget Modal -->
<div class="modal fade" id="setBudgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $budget ? 'Update' : 'Set'; ?> Wedding Budget</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="set_budget">
                    
                    <div class="form-group">
                        <label for="total_budget">Total Wedding Budget (RM) *</label>
                        <input type="number" class="form-control" id="total_budget" name="total_budget" 
                               min="1" step="0.01" 
                               value="<?php echo $budget['total_budget'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Budget Tip:</strong> Include a 10-15% contingency for unexpected expenses.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><?php echo $budget ? 'Update' : 'Set'; ?> Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add Expense</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_expense">
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($recommended_categories as $key => $category): ?>
                                <option value="<?php echo $key; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <input type="text" class="form-control" id="description" name="description" 
                               placeholder="e.g., Venue deposit payment" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Amount (RM) *</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       min="0.01" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expense_date">Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="vendor_name">Vendor/Supplier Name</label>
                        <input type="text" class="form-control" id="vendor_name" name="vendor_name" 
                               placeholder="Name of vendor or supplier">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteExpense(expenseId) {
    if (confirm('Are you sure you want to delete this expense?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_expense">
            <input type="hidden" name="expense_id" value="${expenseId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'layouts/footer.php'; ?>
