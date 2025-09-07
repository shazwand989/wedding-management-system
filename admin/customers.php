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


<div class="container-fluid">

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
                    <select class="form-control" id="status" name="status">
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
    <!-- Customer Details Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel">Customer Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="customerDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Customer Modal -->
    <div class="modal fade" id="customerFormModal" tabindex="-1" role="dialog" aria-labelledby="customerFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerFormModalLabel">Add Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="customerForm">
                    <div class="modal-body">
                        <input type="hidden" id="customer_id" name="customer_id">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="customer_status" class="form-label">Status</label>
                            <select class="form-control" id="customer_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Customer</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'layouts/footer.php'; ?>

<script>
    $(document).ready(function() {
        // Initialize Bootstrap modals with proper configuration
        const $customerModal = $('#customerModal');
        const $customerFormModal = $('#customerFormModal');

        // Configure customer modal events
        if ($customerModal.length) {
            $customerModal.on('show.bs.modal', function() {
                $('body').addClass('modal-open');
            });

            $customerModal.on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('#customerDetails').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);
            });
        }

        // Configure customer form modal events
        if ($customerFormModal.length) {
            $customerFormModal.on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('#customerForm')[0].reset();
                $('#customer_id').val('');
            });
        }

        // Toggle customer status
        window.toggleStatus = function(customerId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';

            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Customer?`,
                text: `Are you sure you want to ${action} this customer?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: currentStatus === 'active' ? '#ffc107' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: `${action.charAt(0).toUpperCase() + action.slice(1)}ing...`,
                        text: 'Please wait while we update the customer status.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                            url: 'customers.php',
                            method: 'POST',
                            contentType: 'application/x-www-form-urlencoded',
                            data: `action=toggle_status&user_id=${customerId}&status=${currentStatus}`,
                            dataType: 'json'
                        })
                        .done(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: `Customer has been ${action}d successfully.`,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'An error occurred while updating the customer status.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            console.error('Error:', errorThrown);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating the customer status.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        };

        // Delete customer
        window.deleteCustomer = function(customerId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this! This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the customer.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                            url: 'customers.php',
                            method: 'POST',
                            contentType: 'application/x-www-form-urlencoded',
                            data: `action=delete_customer&user_id=${customerId}`,
                            dataType: 'json'
                        })
                        .done(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Customer has been deleted successfully.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'An error occurred while deleting the customer.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            console.error('Error:', errorThrown);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while deleting the customer.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        };

        // View customer details
        window.viewCustomer = function(customerId) {
            const $customerDetails = $('#customerDetails');
            $customerDetails.html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading customer details...</p>
            </div>
        `);

            const customerModal = new bootstrap.Modal($customerModal[0], {
                backdrop: 'static',
                keyboard: false
            });
            customerModal.show();

            $.ajax({
                    url: `../includes/ajax_handler.php?action=get_customer_details&id=${customerId}`,
                    method: 'GET',
                    dataType: 'json'
                })
                .done(function(data) {
                    if (data.success) {
                        $customerDetails.html(data.html);
                        customerModal._config.backdrop = true;
                        customerModal._config.keyboard = true;
                    } else {
                        $customerDetails.html(`
                    <div class="alert alert-danger">
                        <h6>Error Loading Customer Details</h6>
                        <p>${data.message || 'Unknown error occurred'}</p>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-dismiss="modal">Close</button>
                    </div>
                `);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    $customerDetails.html(`
                <div class="alert alert-danger">
                    <h6>Connection Error</h6>
                    <p>Failed to load customer details. Please check your connection and try again.</p>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewCustomer(${customerId})">Retry</button>
                </div>
            `);
                });
        };

        // Add new customer
        window.addCustomer = function() {
            $('#customerFormModalLabel').text('Add Customer');
            $('#customerForm')[0].reset();
            $('#customer_id').val('');
            const customerFormModal = new bootstrap.Modal($customerFormModal[0]);
            customerFormModal.show();
        };

        // Edit customer
        window.editCustomer = function(customerId) {
            $('#customerFormModalLabel').text('Edit Customer');
            const customerFormModal = new bootstrap.Modal($customerFormModal[0]);
            customerFormModal.show();

            const $form = $('#customerForm');
            const $inputs = $form.find('input, select, textarea');
            $inputs.prop('disabled', true);

            $.ajax({
                    url: `../includes/ajax_handler.php?action=get_customer_data&id=${customerId}`,
                    method: 'GET',
                    dataType: 'json'
                })
                .done(function(data) {
                    if (data.success) {
                        const customer = data.customer;
                        $('#customer_id').val(customer.id);
                        $('#full_name').val(customer.full_name || '');
                        $('#email').val(customer.email || '');
                        $('#phone').val(customer.phone || '');
                        $('#address').val(customer.address || '');
                        $('#customer_status').val(customer.status || 'active');
                        $inputs.prop('disabled', false);
                    } else {
                        alert('Error loading customer data: ' + (data.message || 'Unknown error'));
                        customerFormModal.hide();
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    alert('Error loading customer data. Please try again.');
                    customerFormModal.hide();
                });
        };

        // Export customers
        window.exportCustomers = function() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.location.href = 'customers.php?' + params.toString();
        };

        // Handle form submission
        $('#customerForm').on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $(this).find('button[type="submit"]');
            const originalText = $submitButton.text();
            $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

            const formData = $(this).serialize();
            const isEdit = $('#customer_id').val() !== '';
            const action = isEdit ? 'update_customer' : 'add_customer';

            $.ajax({
                    url: '../includes/ajax_handler.php',
                    method: 'POST',
                    data: formData + `&action=${action}`,
                    dataType: 'json'
                })
                .done(function(data) {
                    if (data.success) {
                        const customerFormModal = bootstrap.Modal.getInstance($customerFormModal[0]);
                        if (customerFormModal) {
                            customerFormModal.hide();
                        }

                        const $alertDiv = $('<div>').addClass('alert alert-success alert-dismissible fade show').html(`
                    <strong>Success!</strong> Customer ${isEdit ? 'updated' : 'added'} successfully.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                `);
                        $('.container-fluid').prepend($alertDiv);

                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    alert('Error saving customer. Please try again.');
                })
                .always(function() {
                    $submitButton.prop('disabled', false).text(originalText);
                });
        });
    });
</script>