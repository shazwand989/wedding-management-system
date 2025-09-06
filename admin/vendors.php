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
$page_header = 'Vendor Management';
$page_description = 'Manage vendor accounts and approvals';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'approve_vendor':
                $vendor_id = (int)$_POST['vendor_id'];

                $stmt = $pdo->prepare("UPDATE vendors SET status = 'active' WHERE id = ?");
                $stmt->execute([$vendor_id]);

                // Update user status as well
                $stmt = $pdo->prepare("UPDATE users u JOIN vendors v ON u.id = v.user_id SET u.status = 'active' WHERE v.id = ?");
                $stmt->execute([$vendor_id]);

                echo json_encode(['success' => true, 'message' => 'Vendor approved successfully']);
                exit();

            case 'reject_vendor':
                $vendor_id = (int)$_POST['vendor_id'];

                $stmt = $pdo->prepare("UPDATE vendors SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$vendor_id]);

                echo json_encode(['success' => true, 'message' => 'Vendor rejected']);
                exit();

            case 'toggle_status':
                $vendor_id = (int)$_POST['vendor_id'];
                $current_status = $_POST['current_status'];
                $new_status = $current_status === 'active' ? 'inactive' : 'active';

                $stmt = $pdo->prepare("UPDATE vendors SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $vendor_id]);

                // Update user status as well
                $stmt = $pdo->prepare("UPDATE users u JOIN vendors v ON u.id = v.user_id SET u.status = ? WHERE v.id = ?");
                $stmt->execute([$new_status, $vendor_id]);

                echo json_encode(['success' => true, 'message' => 'Vendor status updated successfully', 'new_status' => $new_status]);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$service_filter = $_GET['service'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "v.status = ?";
    $params[] = $status_filter;
}

if ($service_filter) {
    $where_conditions[] = "v.service_type = ?";
    $params[] = $service_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR v.business_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get vendors with user details and booking statistics
$query = "
    SELECT v.*, u.full_name, u.email, u.phone, u.address, u.created_at as user_created_at,
           COUNT(bv.id) as total_bookings,
           COALESCE(SUM(CASE WHEN bv.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_bookings,
           AVG(r.rating) as avg_rating,
           COUNT(r.id) as review_count
    FROM vendors v
    LEFT JOIN users u ON v.user_id = u.id
    LEFT JOIN booking_vendors bv ON v.id = bv.vendor_id
    LEFT JOIN reviews r ON v.id = r.vendor_id
    $where_clause
    GROUP BY v.id
    ORDER BY v.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_vendors,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_vendors,
        COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_vendors,
        COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) as inactive_vendors
    FROM vendors
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get service types for filter
$service_types = ['photography', 'catering', 'decoration', 'music', 'venue', 'other'];
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
                        <h4><?php echo number_format($stats['total_vendors']); ?></h4>
                        <p class="mb-0">Total Vendors</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-store fa-2x"></i>
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
                        <h4><?php echo number_format($stats['pending_vendors']); ?></h4>
                        <p class="mb-0">Pending Approval</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4><?php echo number_format($stats['active_vendors']); ?></h4>
                        <p class="mb-0">Active</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['inactive_vendors']); ?></h4>
                        <p class="mb-0">Inactive</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
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
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="service" class="form-label">Service Type</label>
                <select class="form-select" id="service" name="service">
                    <option value="">All Services</option>
                    <?php foreach ($service_types as $service): ?>
                        <option value="<?php echo $service; ?>" <?php echo $service_filter === $service ? 'selected' : ''; ?>>
                            <?php echo ucfirst($service); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Vendor name, business name, or email...">
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

<!-- Vendors Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Vendors</h5>
        <div>
            <button class="btn btn-success btn-sm" onclick="exportVendors()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vendor</th>
                        <th>Business</th>
                        <th>Service Type</th>
                        <th>Contact</th>
                        <th>Rating</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendors)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No vendors found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td>#<?php echo $vendor['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($vendor['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($vendor['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($vendor['business_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($vendor['price_range'] ?: 'Price not set'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst($vendor['service_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($vendor['phone'] ?: 'Not provided'); ?><br>
                                        <?php if ($vendor['address']): ?>
                                            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($vendor['address'], 0, 30) . '...'); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($vendor['avg_rating']): ?>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= round($vendor['avg_rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($vendor['avg_rating'], 1); ?> (<?php echo $vendor['review_count']; ?> reviews)</small>
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo number_format($vendor['total_bookings']); ?></strong> total<br>
                                        <small class="text-success"><?php echo number_format($vendor['confirmed_bookings']); ?> confirmed</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $vendor['status'] === 'active' ? 'success' : ($vendor['status'] === 'pending' ? 'warning' : 'danger');
                                                            ?>">
                                        <?php echo ucfirst($vendor['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($vendor['user_created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewVendor(<?php echo $vendor['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($vendor['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="approveVendor(<?php echo $vendor['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectVendor(<?php echo $vendor['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-<?php echo $vendor['status'] === 'active' ? 'warning' : 'success'; ?>" onclick="toggleStatus(<?php echo $vendor['id']; ?>, '<?php echo $vendor['status']; ?>')">
                                                <i class="fas fa-<?php echo $vendor['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Vendor Details Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorModalLabel">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="vendorDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<style>
/* Modal styling fixes */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1060 !important;
}

.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 1rem);
}

.modal-content {
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-body {
    position: relative;
    padding: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize vendor modal with proper event handlers
    const vendorModal = document.getElementById('vendorModal');
    
    if (vendorModal) {
        vendorModal.addEventListener('show.bs.modal', function () {
            document.body.classList.add('modal-open');
        });
        
        vendorModal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            // Clear modal content when closed
            document.getElementById('vendorDetails').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
        });
    }
});

function approveVendor(vendorId) {
        Swal.fire({
            title: 'Approve Vendor?',
            text: 'Are you sure you want to approve this vendor?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('vendors.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=approve_vendor&vendor_id=${vendorId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Approved!',
                                text: 'Vendor has been approved successfully.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'An error occurred while approving the vendor.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while approving the vendor.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        });
    }

    function rejectVendor(vendorId) {
        Swal.fire({
            title: 'Reject Vendor?',
            text: 'Are you sure you want to reject this vendor?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reject it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('vendors.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=reject_vendor&vendor_id=${vendorId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Rejected!',
                                text: 'Vendor has been rejected successfully.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'An error occurred while rejecting the vendor.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while rejecting the vendor.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        });
    }

    function toggleStatus(vendorId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activate' : 'deactivate';

        Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} Vendor?`,
            text: `Are you sure you want to ${action} this vendor?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'active' ? '#28a745' : '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${action} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('vendors.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=toggle_status&vendor_id=${vendorId}&current_status=${currentStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: `Vendor has been ${action}d successfully.`,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'An error occurred while updating the vendor status.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating the vendor status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        });
    }

    function viewVendor(vendorId) {
        // Show loading state
        const vendorDetails = document.getElementById('vendorDetails');
        vendorDetails.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading vendor details...</p>
            </div>
        `;
        
        // Show modal immediately with loading state
        const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'), {
            backdrop: 'static',
            keyboard: false
        });
        vendorModal.show();
        
        // Load vendor details via AJAX
        fetch(`../includes/ajax_handler.php?action=get_vendor_details&id=${vendorId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    vendorDetails.innerHTML = data.html;
                    // Re-enable backdrop and keyboard after loading
                    vendorModal._config.backdrop = true;
                    vendorModal._config.keyboard = true;
                } else {
                    vendorDetails.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Error Loading Vendor Details</h6>
                            <p>${data.message || 'Unknown error occurred'}</p>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                vendorDetails.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Connection Error</h6>
                        <p>Failed to load vendor details. Please check your connection and try again.</p>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="viewVendor(${vendorId})">Retry</button>
                    </div>
                `;
            });
    }

    function exportVendors() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = 'vendors.php?' + params.toString();
    }
</script>