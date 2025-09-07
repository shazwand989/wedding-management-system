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
$page_header = 'Wedding Packages';
$page_description = 'Manage wedding packages and pricing';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clean any potential output buffer
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'add_package':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = (float)$_POST['price'];
                $duration_hours = (int)$_POST['duration_hours'];
                $max_guests = (int)$_POST['max_guests'];
                $features = json_encode(array_filter(explode("\n", trim($_POST['features']))));
                $status = $_POST['status'];

                if (empty($name) || $price <= 0) {
                    throw new Exception('Package name and valid price are required');
                }

                $stmt = $pdo->prepare("INSERT INTO wedding_packages (name, description, price, duration_hours, max_guests, features, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $duration_hours, $max_guests, $features, $status]);

                echo json_encode(['success' => true, 'message' => 'Package added successfully']);
                exit();

            case 'update_package':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = (float)$_POST['price'];
                $duration_hours = (int)$_POST['duration_hours'];
                $max_guests = (int)$_POST['max_guests'];
                $features = json_encode(array_filter(explode("\n", trim($_POST['features']))));
                $status = $_POST['status'];

                if (empty($name) || $price <= 0) {
                    throw new Exception('Package name and valid price are required');
                }

                $stmt = $pdo->prepare("UPDATE wedding_packages SET name = ?, description = ?, price = ?, duration_hours = ?, max_guests = ?, features = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $duration_hours, $max_guests, $features, $status, $id]);

                echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
                exit();

            case 'delete_package':
                $id = (int)$_POST['id'];

                // Check if package is used in any bookings
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE package_id = ?");
                $stmt->execute([$id]);
                $booking_count = $stmt->fetchColumn();

                if ($booking_count > 0) {
                    throw new Exception('Cannot delete package that is used in bookings');
                }

                $stmt = $pdo->prepare("DELETE FROM wedding_packages WHERE id = ?");
                $stmt->execute([$id]);

                echo json_encode(['success' => true, 'message' => 'Package deleted successfully']);
                exit();

            case 'toggle_status':
                $id = (int)$_POST['id'];
                $current_status = $_POST['current_status'];
                $new_status = $current_status === 'active' ? 'inactive' : 'active';

                $stmt = $pdo->prepare("UPDATE wedding_packages SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);

                echo json_encode(['success' => true, 'message' => 'Package status updated successfully', 'new_status' => $new_status]);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$price_filter = $_GET['price'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($price_filter) {
    switch ($price_filter) {
        case 'low':
            $where_conditions[] = "price < 10000";
            break;
        case 'medium':
            $where_conditions[] = "price BETWEEN 10000 AND 20000";
            break;
        case 'high':
            $where_conditions[] = "price > 20000";
            break;
    }
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get packages with booking statistics
$query = "
    SELECT wp.*, 
           COUNT(b.id) as booking_count,
           COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END), 0) as completed_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status IN ('pending', 'confirmed') THEN 1 ELSE 0 END), 0) as active_bookings
    FROM wedding_packages wp
    LEFT JOIN bookings b ON wp.id = b.package_id
    $where_clause
    GROUP BY wp.id
    ORDER BY wp.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_packages,
        COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_packages,
        COALESCE(SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END), 0) as inactive_packages,
        COALESCE(AVG(price), 0) as avg_price,
        COALESCE(MIN(price), 0) as min_price,
        COALESCE(MAX(price), 0) as max_price
    FROM wedding_packages
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
                            <h4><?php echo number_format($stats['total_packages']); ?></h4>
                            <p class="mb-0">Total Packages</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-gift fa-2x"></i>
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
                            <h4><?php echo number_format($stats['active_packages']); ?></h4>
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
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>RM <?php echo number_format($stats['avg_price'], 0); ?></h4>
                            <p class="mb-0">Average Price</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
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
                            <h4>RM <?php echo number_format($stats['min_price'], 0); ?> - <?php echo number_format($stats['max_price'], 0); ?></h4>
                            <p class="mb-0">Price Range</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
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
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="price2" class="form-label">Price Range</label>
                    <select class="form-control" id="price2" name="price2">
                        <option value="">All Prices</option>
                        <option value="low" <?php echo $price_filter === 'low' ? 'selected' : ''; ?>>Under RM 10,000</option>
                        <option value="medium" <?php echo $price_filter === 'medium' ? 'selected' : ''; ?>>RM 10,000 - 20,000</option>
                        <option value="high" <?php echo $price_filter === 'high' ? 'selected' : ''; ?>>Over RM 20,000</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Package name or description...">
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

    <!-- Packages -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Wedding Packages</h5>
            <div>
                <button class="btn btn-success btn-sm" onclick="exportPackages()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-primary btn-sm" onclick="addPackage()">
                    <i class="fas fa-plus"></i> Add Package
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (empty($packages)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No packages found</h5>
                            <p class="text-muted">Create your first wedding package to get started.</p>
                            <button class="btn btn-primary" onclick="addPackage()">
                                <i class="fas fa-plus"></i> Add Package
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $package): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 <?php echo $package['status'] === 'inactive' ? 'opacity-75' : ''; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($package['name']); ?></h6>
                                    <span class="badge bg-<?php echo $package['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($package['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h3 class="text-primary">RM <?php echo number_format($package['price'], 2); ?></h3>
                                    </div>

                                    <p class="text-muted small"><?php echo htmlspecialchars($package['description']); ?></p>

                                    <div class="row text-center mb-3">
                                        <div class="col-6">
                                            <div class="border-end">
                                                <small class="text-muted d-block">Duration</small>
                                                <strong><?php echo $package['duration_hours']; ?> hours</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Max Guests</small>
                                            <strong><?php echo number_format($package['max_guests']); ?></strong>
                                        </div>
                                    </div>

                                    <?php if ($package['features']): ?>
                                        <?php $features = json_decode($package['features'], true); ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-2">Features:</small>
                                            <ul class="list-unstyled small">
                                                <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                                                    <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                                                <?php endforeach; ?>
                                                <?php if (count($features) > 4): ?>
                                                    <li class="text-muted">... and <?php echo count($features) - 4; ?> more</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row text-center small text-muted">
                                        <div class="col-6">
                                            <div class="border-end">
                                                <span class="d-block">Total Bookings</span>
                                                <strong class="text-primary"><?php echo number_format($package['booking_count']); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <span class="d-block">Active Bookings</span>
                                            <strong class="text-warning"><?php echo number_format($package['active_bookings']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewPackage(<?php echo $package['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="editPackage(<?php echo $package['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-<?php echo $package['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm" onclick="toggleStatus(<?php echo $package['id']; ?>, '<?php echo $package['status']; ?>')">
                                            <i class="fas fa-<?php echo $package['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePackage(<?php echo $package['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Package Form Modal -->
    <div class="modal fade" id="packageModal" tabindex="-1" role="dialog" aria-labelledby="packageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalTitle">Add Package</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="packageForm">
                    <div class="modal-body">
                        <input type="hidden" id="package_id" name="id">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name" class="control-label">Package Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status" class="control-label">Status</label>
                                    <select class="form-control" id="package_status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="control-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price" class="control-label">Price (RM) *</label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_hours" class="control-label">Duration (Hours)</label>
                                    <input type="number" class="form-control" id="duration_hours" name="duration_hours" min="1" value="8">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_guests" class="control-label">Max Guests</label>
                                    <input type="number" class="form-control" id="max_guests" name="max_guests" min="1" value="100">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="features" class="control-label">Features</label>
                            <textarea class="form-control" id="features" name="features" rows="6" placeholder="Enter each feature on a new line..."></textarea>
                            <small class="form-text text-muted">Enter each feature on a separate line</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Package</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Package Details Modal -->
    <div class="modal fade" id="packageDetailsModal" tabindex="-1" role="dialog" aria-labelledby="packageDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageDetailsModalLabel">Package Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="packageDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'layouts/footer.php'; ?>



<script>
    $(document).ready(function() {
        // Initialize package modals with proper event handlers
        const $packageModal = $('#packageModal');
        const $packageDetailsModal = $('#packageDetailsModal');

        if ($packageModal.length) {
            $packageModal.on('show.bs.modal', function() {
                $('body').addClass('modal-open');
            });

            $packageModal.on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('#packageForm')[0].reset();
                $('#package_id').val('');
            });
        }

        if ($packageDetailsModal.length) {
            $packageDetailsModal.on('show.bs.modal', function() {
                $('body').addClass('modal-open');
            });

            $packageDetailsModal.on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('#packageDetails').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `);
            });
        }

        window.addPackage = function() {
            $('#packageModalTitle').text('Add Package');
            $('#packageForm')[0].reset();
            $('#package_id').val('');
            const packageModal = new bootstrap.Modal($packageModal[0]);
            packageModal.show();
        };

        window.editPackage = function(packageId) {
            $('#packageModalTitle').text('Edit Package');
            const packageModal = new bootstrap.Modal($packageModal[0]);
            packageModal.show();

            const $form = $('#packageForm');
            const $inputs = $form.find('input, select, textarea');
            $inputs.prop('disabled', true);

            $.ajax({
                    url: `../includes/ajax_handler.php?action=get_package_data&id=${packageId}`,
                    method: 'GET',
                    dataType: 'json'
                })
                .done(function(data) {
                    if (data.success) {
                        const pkg = data.package;
                        $('#package_id').val(pkg.id);
                        $('#name').val(pkg.name || '');
                        $('#description').val(pkg.description || '');
                        $('#price').val(pkg.price || '');
                        $('#duration_hours').val(pkg.duration_hours || '');
                        $('#max_guests').val(pkg.max_guests || '');
                        $('#package_status').val(pkg.status || 'active');

                        // Handle features
                        try {
                            const features = pkg.features ? JSON.parse(pkg.features) : [];
                            $('#features').val(features.join('\n'));
                        } catch (e) {
                            $('#features').val('');
                        }

                        $inputs.prop('disabled', false);
                    } else {
                        alert('Error loading package data: ' + (data.message || 'Unknown error'));
                        packageModal.hide();
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    alert('Error loading package data. Please try again.');
                    packageModal.hide();
                });
        };

        window.viewPackage = function(packageId) {
            const $packageDetails = $('#packageDetails');
            $packageDetails.html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading package details...</p>
            </div>
        `);

            const packageDetailsModal = new bootstrap.Modal($packageDetailsModal[0], {
                backdrop: 'static',
                keyboard: false
            });
            packageDetailsModal.show();

            $.ajax({
                    url: `../includes/ajax_handler.php?action=get_package_details&id=${packageId}`,
                    method: 'GET',
                    dataType: 'json'
                })
                .done(function(data) {
                    if (data.success) {
                        $packageDetails.html(data.html);
                        packageDetailsModal._config.backdrop = true;
                        packageDetailsModal._config.keyboard = true;
                    } else {
                        $packageDetails.html(`
                    <div class="alert alert-danger">
                        <h6>Error Loading Package Details</h6>
                        <p>${data.message || 'Unknown error occurred'}</p>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-dismiss="modal">Close</button>
                    </div>
                `);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    $packageDetails.html(`
                <div class="alert alert-danger">
                    <h6>Connection Error</h6>
                    <p>Failed to load package details. Please check your connection and try again.</p>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewPackage(${packageId})">Retry</button>
                </div>
            `);
                });
        };

        window.toggleStatus = function(packageId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';

            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Package?`,
                text: `Are you sure you want to ${action} this package?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'active' ? '#28a745' : '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: `${action.charAt(0).toUpperCase() + action.slice(1)}ing...`,
                        text: 'Please wait while we update the package status.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                            url: 'packages.php',
                            method: 'POST',
                            contentType: 'application/x-www-form-urlencoded',
                            data: `action=toggle_status&id=${packageId}&current_status=${currentStatus}`,
                            dataType: 'json'
                        })
                        .done(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: `Package has been ${action}d successfully.`,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'An error occurred while updating the package status.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            console.error('Error:', errorThrown);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating the package status.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        };

        window.deletePackage = function(packageId) {
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
                        text: 'Please wait while we delete the package.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                            url: 'packages.php',
                            method: 'POST',
                            contentType: 'application/x-www-form-urlencoded',
                            data: `action=delete_package&id=${packageId}`,
                            dataType: 'json'
                        })
                        .done(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Package has been deleted successfully.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'An error occurred while deleting the package.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            console.error('Error:', errorThrown);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while deleting the package.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        };

        window.exportPackages = function() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.location.href = 'packages.php?' + params.toString();
        };

        $('#packageForm').on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $(this).find('button[type="submit"]');
            const originalText = $submitButton.text();
            $submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

            const formData = $(this).serialize();
            const isEdit = $('#package_id').val() !== '';
            const action = isEdit ? 'update_package' : 'add_package';

            $.ajax({
                    url: 'packages.php',
                    method: 'POST',
                    data: formData + `&action=${action}`,
                    dataType: 'json'
                })
                .done(function(data) {
                    console.log('Response received:', data);
                    if (data.success) {
                        const packageModal = bootstrap.Modal.getInstance($packageModal[0]);
                        if (packageModal) {
                            packageModal.hide();
                        }

                        const $alertDiv = $('<div>').addClass('alert alert-success alert-dismissible fade show mb-3').html(`
                    <strong>Success!</strong> Package ${isEdit ? 'updated' : 'added'} successfully.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                `);

                        const $mainContent = $('.main-content');
                        if ($mainContent.length) {
                            const $firstCard = $mainContent.find('.card, .row').first();
                            if ($firstCard.length) {
                                $alertDiv.insertBefore($firstCard);
                            } else {
                                $mainContent.append($alertDiv);
                            }
                        } else {
                            const $header = $('h1');
                            if ($header.length) {
                                $alertDiv.insertAfter($header);
                            }
                        }

                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', errorThrown);
                    alert('Error saving package: ' + errorThrown + '. Please try again.');
                })
                .always(function() {
                    $submitButton.prop('disabled', false).text(originalText);
                });
        });
    });
</script>