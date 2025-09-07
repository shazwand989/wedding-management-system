<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Wedding Timeline';
$breadcrumbs = [
    ['title' => 'Wedding Timeline']
];

$customer_id = $_SESSION['user_id'];

// Handle task actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task') {
        $task_title = $_POST['task_title'] ?? '';
        $description = $_POST['description'] ?? '';
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        
        if (!empty($task_title) && !empty($due_date)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO wedding_tasks (customer_id, task_title, description, due_date, priority, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$customer_id, $task_title, $description, $due_date, $priority]);
                $success_message = "Task added successfully!";
            } catch (PDOException $e) {
                $error_message = "Error adding task: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'load_template') {
        $wedding_date = $_POST['wedding_date'] ?? '';
        
        if (!empty($wedding_date)) {
            try {
                $pdo->beginTransaction();
                
                // Pre-defined wedding planning tasks template
                $template_tasks = [
                    ['title' => 'Set Wedding Date', 'description' => 'Choose your perfect wedding date', 'months_before' => 12, 'priority' => 'high'],
                    ['title' => 'Set Budget', 'description' => 'Determine overall wedding budget and allocations', 'months_before' => 12, 'priority' => 'high'],
                    ['title' => 'Create Guest List', 'description' => 'Make initial guest list and get contact information', 'months_before' => 11, 'priority' => 'high'],
                    ['title' => 'Book Venue', 'description' => 'Research and book ceremony and reception venues', 'months_before' => 10, 'priority' => 'high'],
                    ['title' => 'Choose Wedding Theme/Style', 'description' => 'Decide on wedding theme, color scheme, and overall style', 'months_before' => 10, 'priority' => 'medium'],
                    ['title' => 'Book Photographer', 'description' => 'Research and book wedding photographer', 'months_before' => 9, 'priority' => 'high'],
                    ['title' => 'Book Caterer', 'description' => 'Choose menu and book catering service', 'months_before' => 8, 'priority' => 'high'],
                    ['title' => 'Book Entertainment/DJ', 'description' => 'Book band, DJ, or entertainment for reception', 'months_before' => 8, 'priority' => 'medium'],
                    ['title' => 'Shop for Wedding Dress', 'description' => 'Start shopping for wedding dress and accessories', 'months_before' => 7, 'priority' => 'high'],
                    ['title' => 'Book Florist', 'description' => 'Choose and book florist for ceremony and reception', 'months_before' => 6, 'priority' => 'medium'],
                    ['title' => 'Send Save the Dates', 'description' => 'Design and send save the date cards', 'months_before' => 6, 'priority' => 'medium'],
                    ['title' => 'Book Transportation', 'description' => 'Arrange transportation for wedding day', 'months_before' => 5, 'priority' => 'low'],
                    ['title' => 'Order Wedding Cake', 'description' => 'Taste and order wedding cake', 'months_before' => 4, 'priority' => 'medium'],
                    ['title' => 'Send Invitations', 'description' => 'Design, print, and send wedding invitations', 'months_before' => 3, 'priority' => 'high'],
                    ['title' => 'Final Dress Fitting', 'description' => 'Final fitting and alterations for wedding dress', 'months_before' => 2, 'priority' => 'high'],
                    ['title' => 'Finalize Guest Count', 'description' => 'Get final RSVP count and inform vendors', 'months_before' => 1, 'priority' => 'high'],
                    ['title' => 'Wedding Rehearsal', 'description' => 'Conduct wedding rehearsal with wedding party', 'months_before' => 0, 'priority' => 'high'],
                ];

                $stmt = $pdo->prepare("
                    INSERT INTO wedding_tasks (customer_id, task_title, description, due_date, priority, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");

                $wedding_timestamp = strtotime($wedding_date);
                $tasks_added = 0;

                foreach ($template_tasks as $template) {
                    // Calculate due date based on wedding date
                    $due_timestamp = strtotime("-{$template['months_before']} months", $wedding_timestamp);
                    
                    // If the calculated date is in the past, set it to today + 1 week
                    if ($due_timestamp < time()) {
                        $due_timestamp = strtotime("+1 week");
                    }
                    
                    $due_date = date('Y-m-d', $due_timestamp);
                    
                    $stmt->execute([
                        $customer_id,
                        $template['title'],
                        $template['description'],
                        $due_date,
                        $template['priority']
                    ]);
                    $tasks_added++;
                }

                $pdo->commit();
                $success_message = "Wedding planning template loaded successfully! Added {$tasks_added} tasks to your timeline.";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Error loading template: " . $e->getMessage();
            }
        } else {
            $error_message = "Please provide your wedding date to load the template.";
        }
    } elseif ($_POST['action'] === 'update_task') {
        $task_id = (int)$_POST['task_id'];
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE wedding_tasks SET status = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?");
            $stmt->execute([$status, $task_id, $customer_id]);
            $success_message = "Task updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating task: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_task') {
        $task_id = (int)$_POST['task_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM wedding_tasks WHERE id = ? AND customer_id = ?");
            $stmt->execute([$task_id, $customer_id]);
            $success_message = "Task deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting task: " . $e->getMessage();
        }
    }
}

// Get customer's tasks
try {
    $stmt = $pdo->prepare("
        SELECT * FROM wedding_tasks 
        WHERE customer_id = ? 
        ORDER BY due_date ASC, priority DESC, created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $tasks = $stmt->fetchAll();

    // Get task statistics
    $stats = [
        'total' => count($tasks),
        'pending' => count(array_filter($tasks, fn($t) => $t['status'] === 'pending')),
        'in_progress' => count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')),
        'completed' => count(array_filter($tasks, fn($t) => $t['status'] === 'completed')),
        'overdue' => count(array_filter($tasks, fn($t) => $t['status'] !== 'completed' && strtotime($t['due_date']) < time()))
    ];

} catch (PDOException $e) {
    $error_message = "Error loading tasks: " . $e->getMessage();
    $tasks = [];
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];
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

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-2x mb-3"></i>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-info">
                <div class="card-body text-center">
                    <i class="fas fa-spinner fa-2x mb-3"></i>
                    <h3><?php echo $stats['in_progress']; ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($stats['overdue'] > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Attention!</strong> You have <?php echo $stats['overdue']; ?> overdue task(s). Please review and update them.
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Task List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Wedding Planning Tasks</h3>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addTaskModal">
                            <i class="fas fa-plus"></i> Add Task
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-list-ul fa-3x text-primary mb-3"></i>
                            <h4>No Tasks Yet!</h4>
                            <p class="text-muted">Start planning your perfect wedding by adding tasks to your timeline.</p>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addTaskModal">
                                <i class="fas fa-plus"></i> Add Your First Task
                            </button>
                            <hr class="my-4">
                            <button class="btn btn-info" data-toggle="modal" data-target="#templateModal">
                                <i class="fas fa-magic"></i> Use Wedding Planning Template
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($tasks as $index => $task): ?>
                                <?php
                                $is_overdue = $task['status'] !== 'completed' && strtotime($task['due_date']) < time();
                                $status_color = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info', 
                                    'completed' => 'success'
                                ][$task['status']] ?? 'secondary';
                                
                                if ($is_overdue) $status_color = 'danger';
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-<?php echo $status_color; ?>">
                                        <i class="fas fa-<?php echo $task['status'] === 'completed' ? 'check' : ($task['status'] === 'in_progress' ? 'spinner' : 'clock'); ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1">
                                                            <?php echo htmlspecialchars($task['task_title']); ?>
                                                            <span class="badge badge-<?php echo ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'][$task['priority']]; ?> ml-2">
                                                                <?php echo ucfirst($task['priority']); ?>
                                                            </span>
                                                        </h5>
                                                        <?php if ($task['description']): ?>
                                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                                        <?php endif; ?>
                                                        <div class="mb-2">
                                                            <i class="fas fa-calendar text-primary"></i>
                                                            <strong>Due:</strong> <?php echo date('F j, Y', strtotime($task['due_date'])); ?>
                                                            <?php if ($is_overdue): ?>
                                                                <span class="badge badge-danger ml-2">Overdue</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            Status: <strong><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></strong>
                                                        </small>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <button class="dropdown-item" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'pending')">
                                                                <i class="fas fa-clock text-warning"></i> Mark as Pending
                                                            </button>
                                                            <button class="dropdown-item" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in_progress')">
                                                                <i class="fas fa-spinner text-info"></i> Mark as In Progress
                                                            </button>
                                                            <button class="dropdown-item" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                                                                <i class="fas fa-check text-success"></i> Mark as Completed
                                                            </button>
                                                            <div class="dropdown-divider"></div>
                                                            <button class="dropdown-item text-danger" onclick="deleteTask(<?php echo $task['id']; ?>)">
                                                                <i class="fas fa-trash"></i> Delete Task
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Progress and Tips -->
        <div class="col-md-4">
            <!-- Progress Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Planning Progress</h3>
                </div>
                <div class="card-body">
                    <?php
                    $completion_percentage = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
                    ?>
                    <div class="text-center mb-3">
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $completion_percentage; ?>%">
                                <?php echo $completion_percentage; ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $stats['completed']; ?> of <?php echo $stats['total']; ?> tasks completed
                        </small>
                    </div>

                    <div class="mb-3">
                        <h6>Task Status Breakdown:</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-warning">
                                    <i class="fas fa-clock"></i><br>
                                    <strong><?php echo $stats['pending']; ?></strong><br>
                                    <small>Pending</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-info">
                                    <i class="fas fa-spinner"></i><br>
                                    <strong><?php echo $stats['in_progress']; ?></strong><br>
                                    <small>In Progress</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($tasks)): ?>
                        <div class="text-center">
                            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#templateModal">
                                <i class="fas fa-magic"></i> Use Template
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Wedding Tips -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Wedding Planning Tips</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-lightbulb text-warning"></i>
                        <strong>Start Early</strong><br>
                        <small class="text-muted">Begin planning 12-18 months before your wedding date for the best vendor availability.</small>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-calculator text-info"></i>
                        <strong>Budget Wisely</strong><br>
                        <small class="text-muted">Allocate your budget: 40% venue & catering, 20% photography, 10% flowers, 10% music.</small>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-users text-success"></i>
                        <strong>Guest List First</strong><br>
                        <small class="text-muted">Your guest count affects almost every other wedding decision, so finalize it early.</small>
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-heart text-danger"></i>
                        <strong>Stay Flexible</strong><br>
                        <small class="text-muted">Remember that not everything will go exactly as planned, and that's okay!</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="form-group">
                        <label for="task_title">Task Title *</label>
                        <input type="text" class="form-control" id="task_title" name="task_title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Wedding Planning Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-magic"></i> Wedding Planning Template
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="load_template">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>What is this?</strong> This template will add a comprehensive list of wedding planning tasks with recommended timelines based on your wedding date.
                    </div>
                    
                    <div class="form-group">
                        <label for="wedding_date">Your Wedding Date *</label>
                        <input type="date" class="form-control" id="wedding_date" name="wedding_date" 
                               min="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
                        <small class="form-text text-muted">
                            Tasks will be scheduled based on this date. We recommend planning 12-18 months ahead.
                        </small>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Template Includes (17 Tasks):</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Set Wedding Date</li>
                                        <li><i class="fas fa-check text-success"></i> Set Budget</li>
                                        <li><i class="fas fa-check text-success"></i> Create Guest List</li>
                                        <li><i class="fas fa-check text-success"></i> Book Venue</li>
                                        <li><i class="fas fa-check text-success"></i> Choose Wedding Theme</li>
                                        <li><i class="fas fa-check text-success"></i> Book Photographer</li>
                                        <li><i class="fas fa-check text-success"></i> Book Caterer</li>
                                        <li><i class="fas fa-check text-success"></i> Book Entertainment/DJ</li>
                                        <li><i class="fas fa-check text-success"></i> Shop for Wedding Dress</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Book Florist</li>
                                        <li><i class="fas fa-check text-success"></i> Send Save the Dates</li>
                                        <li><i class="fas fa-check text-success"></i> Book Transportation</li>
                                        <li><i class="fas fa-check text-success"></i> Order Wedding Cake</li>
                                        <li><i class="fas fa-check text-success"></i> Send Invitations</li>
                                        <li><i class="fas fa-check text-success"></i> Final Dress Fitting</li>
                                        <li><i class="fas fa-check text-success"></i> Finalize Guest Count</li>
                                        <li><i class="fas fa-check text-success"></i> Wedding Rehearsal</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Note:</strong> Tasks with due dates in the past will be scheduled for next week. You can always modify or delete tasks after loading the template.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-magic"></i> Load Template Tasks
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -18px;
    top: 40px;
    height: calc(100% + 20px);
    width: 2px;
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 16px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.timeline-content {
    margin-left: 20px;
}
</style>

<script>
function updateTaskStatus(taskId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_task">
        <input type="hidden" name="task_id" value="${taskId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_task">
            <input type="hidden" name="task_id" value="${taskId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'layouts/footer.php'; ?>
