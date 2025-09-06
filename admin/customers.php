<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Define access constant for layout
define('ADMIN_ACCESS', true);

// Page variables
$page_header = 'Customer Management';
$page_description = 'Manage customer accounts and information';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $status = $_POST['status'] === 'active' ? 'inactive' : 'active';

                $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND role = 'customer'");
                $stmt->execute([$status, $user_id]);

                echo json_encode(['success' => true, 'message' => 'Customer status updated successfully', 'new_status' => $status]);
                exit();

            case 'delete_customer':
                $user_id = (int)$_POST['user_id'];

                // Check if customer has active bookings
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND booking_status IN ('pending', 'confirmed')");
                $stmt->execute([$user_id]);
                $active_bookings = $stmt->fetchColumn();

                if ($active_bookings > 0) {
                    throw new Exception('Cannot delete customer with active bookings');
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
                $stmt->execute([$user_id]);

                echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["role = 'customer'"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get customers with booking statistics
$query = "
    SELECT u.*, 
           COUNT(b.id) as total_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END), 0) as completed_bookings,
           COALESCE(SUM(b.total_amount), 0) as total_spent,
           MAX(b.created_at) as last_booking_date
    FROM users u
    LEFT JOIN bookings b ON u.id = b.customer_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_customers,
        COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) as inactive_customers,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d
    FROM users 
    WHERE role = 'customer'
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);
?>


<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['total_customers']); ?></h4>
                        <p class="mb-0">Total Customers</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['active_customers']); ?></h4>
                        <p class="mb-0">Active</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['inactive_customers']); ?></h4>
                        <p class="mb-0">Inactive</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-times fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['new_customers_30d']); ?></h4>
                        <p class="mb-0">New (30 days)</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-7">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer name, email, or phone...">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Customers</h5>
        <div>
            <button class="btn btn-success btn-sm" onclick="exportCustomers()">
                <i class="fas fa-download"></i> Export
            </button>
            <button class="btn btn-primary btn-sm" onclick="addCustomer()">
                <i class="fas fa-plus"></i> Add Customer
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Bookings</th>
                        <th>Total Spent</th>
                        <th>Last Booking</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No customers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>#<?php echo $customer['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone'] ?: 'Not provided'); ?><br>
                                        <?php if ($customer['address']): ?>
                                            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($customer['address'], 0, 30) . '...'); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo number_format($customer['total_bookings']); ?></strong> total<br>
                                        <small class="text-success"><?php echo number_format($customer['completed_bookings']); ?> completed</small>
                                    </div>
                                </td>
                                <td>
                                    <strong>RM <?php echo number_format($customer['total_spent'] ?: 0, 2); ?></strong>
                                </td>
                                <td>
                                    <?php if ($customer['last_booking_date']): ?>
                                        <?php echo date('M j, Y', strtotime($customer['last_booking_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-<?php echo $customer['status'] === 'active' ? 'warning' : 'success'; ?>" onclick="toggleStatus(<?php echo $customer['id']; ?>, '<?php echo $customer['status']; ?>')">
                                            <i class="fas fa-<?php echo $customer['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalLabel">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="customerDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="customerFormModal" tabindex="-1" aria-labelledby="customerFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerFormModalLabel">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">`
                    <input type="hidden" id="customer_id" name="customer_id">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="customer_status" class="form-label">Status</label>
                        <select class="form-select" id="customer_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<style>
/* Ensure modal backdrop and z-index are correct */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1060 !important;
}

/* Ensure modal is properly centered and visible */
.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 1rem);
}

/* Fix modal content visibility */
.modal-content {
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

/* Ensure modal header is visible */
.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* Ensure proper spacing */
.modal-body {
    position: relative;
    padding: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap modals with proper configuration
    const customerModal = document.getElementById('customerModal');
    const customerFormModal = document.getElementById('customerFormModal');
    
    // Ensure modals are properly configured
    if (customerModal) {
        customerModal.addEventListener('show.bs.modal', function () {
            document.body.classList.add('modal-open');
        });
        
        customerModal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            // Clear modal content when closed
            document.getElementById('customerDetails').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
        });
    }
    
    if (customerFormModal) {
        customerFormModal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            // Reset form when modal is closed
            document.getElementById('customerForm').reset();
            document.getElementById('customer_id').value = '';
        });
    }
});

function toggleStatus(customerId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activate' : 'deactivate';

        if (confirm(`Are you sure you want to ${action} this customer?`)) {
            fetch('customers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_status&user_id=${customerId}&status=${currentStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error updating customer status');
                });
        }
    }

    function deleteCustomer(customerId) {
        if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
            fetch('customers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_customer&user_id=${customerId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting customer');
                });
        }
    }

    function viewCustomer(customerId) {
        // Show loading state
        const customerDetails = document.getElementById('customerDetails');
        customerDetails.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading customer details...</p>
            </div>
        `;
        
        // Show modal immediately with loading state
        const customerModal = new bootstrap.Modal(document.getElementById('customerModal'), {
            backdrop: 'static',
            keyboard: false
        });
        customerModal.show();
        
        // Load customer details via AJAX
        fetch(`../includes/ajax_handler.php?action=get_customer_details&id=${customerId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    customerDetails.innerHTML = data.html;
                    // Re-enable backdrop and keyboard after loading
                    customerModal._config.backdrop = true;
                    customerModal._config.keyboard = true;
                } else {
                    customerDetails.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Error Loading Customer Details</h6>
                            <p>${data.message || 'Unknown error occurred'}</p>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                customerDetails.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Connection Error</h6>
                        <p>Failed to load customer details. Please check your connection and try again.</p>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="viewCustomer(${customerId})">Retry</button>
                    </div>
                `;
            });
    }

    function addCustomer() {
        // Update modal title
        const modalTitle = document.getElementById('customerFormModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Add Customer';
        }
        
        // Reset form
        document.getElementById('customerForm').reset();
        document.getElementById('customer_id').value = '';
        
        // Show modal
        const customerFormModal = new bootstrap.Modal(document.getElementById('customerFormModal'));
        customerFormModal.show();
    }

    function editCustomer(customerId) {
        // Update modal title
        const modalTitle = document.getElementById('customerFormModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Edit Customer';
        }

        // Show modal with loading state
        const customerFormModal = new bootstrap.Modal(document.getElementById('customerFormModal'));
        customerFormModal.show();
        
        // Disable form while loading
        const form = document.getElementById('customerForm');
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => input.disabled = true);

        // Load customer data via AJAX
        fetch(`../includes/ajax_handler.php?action=get_customer_data&id=${customerId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const customer = data.customer;
                    document.getElementById('customer_id').value = customer.id;
                    document.getElementById('full_name').value = customer.full_name || '';
                    document.getElementById('email').value = customer.email || '';
                    document.getElementById('phone').value = customer.phone || '';
                    document.getElementById('address').value = customer.address || '';
                    document.getElementById('customer_status').value = customer.status || 'active';
                    
                    // Re-enable form inputs
                    inputs.forEach(input => input.disabled = false);
                } else {
                    alert('Error loading customer data: ' + (data.message || 'Unknown error'));
                    customerFormModal.hide();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading customer data. Please try again.');
                customerFormModal.hide();
            });
    }

    function exportCustomers() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = 'customers.php?' + params.toString();
    }

    // Handle form submission
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        const formData = new FormData(this);
        const isEdit = document.getElementById('customer_id').value !== '';
        formData.append('action', isEdit ? 'update_customer' : 'add_customer');

        fetch('../includes/ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Hide modal first
                    const customerFormModal = bootstrap.Modal.getInstance(document.getElementById('customerFormModal'));
                    if (customerFormModal) {
                        customerFormModal.hide();
                    }
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <strong>Success!</strong> Customer ${isEdit ? 'updated' : 'added'} successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
                    
                    // Reload page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving customer. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
    });
</script>