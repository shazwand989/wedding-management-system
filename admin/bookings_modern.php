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
$page_header = 'Booking Management';
$page_description = 'Manage all wedding bookings and reservations';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'update_status':
                $booking_id = (int)$_POST['booking_id'];
                $status = $_POST['status'];

                $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
                if (!in_array($status, $allowed_statuses)) {
                    throw new Exception('Invalid status');
                }

                $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$status, $booking_id]);

                echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
                exit();

            case 'delete_booking':
                $booking_id = (int)$_POST['booking_id'];

                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([$booking_id]);

                echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "b.booking_status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(b.event_date) = ?";
    $params[] = $date_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR b.venue_name LIKE ? OR wp.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get bookings with customer and package details
$query = "
    SELECT b.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
           wp.name as package_name, wp.price as package_price
    FROM bookings b
    LEFT JOIN users u ON b.customer_id = u.id
    LEFT JOIN wedding_packages wp ON b.package_id = wp.id
    $where_clause
    ORDER BY b.event_date DESC, b.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END), 0) as pending_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END), 0) as completed_bookings,
        COALESCE(SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_bookings,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(paid_amount), 0) as paid_revenue
    FROM bookings
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
                        <h4><?php echo number_format($stats['total_bookings']); ?></h4>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-check fa-2x"></i>
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
                        <h4><?php echo number_format($stats['pending_bookings']); ?></h4>
                        <p class="mb-0">Pending</p>
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
                        <h4><?php echo number_format($stats['confirmed_bookings']); ?></h4>
                        <p class="mb-0">Confirmed</p>
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
                        <h4>RM <?php echo number_format($stats['total_revenue'], 2); ?></h4>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
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
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Event Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer, venue, or package name...">
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

<!-- Bookings Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Bookings</h5>
        <div>
            <button class="btn btn-success btn-sm" onclick="exportBookings()">
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
                        <th>Customer</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th>Package</th>
                        <th>Guests</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No bookings found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($booking['event_date'])); ?><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['event_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['venue_name'] ?: 'Not specified'); ?></td>
                                <td><?php echo htmlspecialchars($booking['package_name'] ?: 'Custom'); ?></td>
                                <td><?php echo number_format($booking['guest_count']); ?></td>
                                <td>RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <div>
                                        RM <?php echo number_format($booking['paid_amount'], 2); ?><br>
                                        <span class="badge bg-<?php echo $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'completed' ? 'info' : 'danger'));
                                                            ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'pending')">Mark Pending</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">Mark Confirmed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'completed')">Mark Completed</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">Mark Cancelled</a></li>
                                            </ul>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBooking(<?php echo $booking['id']; ?>)">
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

<!-- Modern Booking Details Modal -->
<div id="bookingModal" class="modern-modal" style="display: none;">
    <div class="modern-modal-overlay" onclick="closeBookingModal()"></div>
    <div class="modern-modal-container">
        <div class="modern-modal-header">
            <h3 class="modern-modal-title">
                <i class="fas fa-calendar-check"></i> Booking Details
            </h3>
            <button type="button" class="modern-modal-close" onclick="closeBookingModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modern-modal-body" id="bookingDetails">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading booking details...</p>
            </div>
        </div>
        <div class="modern-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<style>
.modern-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.modern-modal.show {
    opacity: 1;
    visibility: visible;
}

.modern-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modern-modal-container {
    position: relative;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    margin: 5vh auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    transform: translateY(-50px);
    transition: transform 0.3s ease;
}

.modern-modal.show .modern-modal-container {
    transform: translateY(0);
}

.modern-modal-header {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modern-modal-title {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.modern-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.modern-modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.modern-modal-body {
    padding: 2rem;
    max-height: calc(90vh - 160px);
    overflow-y: auto;
}

.modern-modal-footer {
    padding: 1rem 2rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    background: #f8f9fa;
}

.loading-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.loading-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

/* Modal content styling */
.modern-modal-body h6 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-weight: 600;
    border-bottom: 2px solid var(--secondary-color);
    padding-bottom: 0.5rem;
}

.modern-modal-body p {
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.modern-modal-body .badge {
    font-size: 0.8rem;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

/* Responsive design */
@media (max-width: 768px) {
    .modern-modal-container {
        width: 95%;
        margin: 2vh auto;
        max-height: 96vh;
    }
    
    .modern-modal-header {
        padding: 1rem 1.5rem;
    }
    
    .modern-modal-title {
        font-size: 1.1rem;
    }
    
    .modern-modal-body {
        padding: 1.5rem;
    }
    
    .modern-modal-footer {
        padding: 1rem 1.5rem;
    }
}

/* Custom SweetAlert2 styling to match the theme */
.modern-swal-popup {
    border-radius: 15px !important;
    font-family: inherit !important;
}

.modern-swal-confirm {
    background-color: var(--primary-color) !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    padding: 0.5rem 1.5rem !important;
}

.modern-swal-cancel {
    background-color: #6c757d !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    padding: 0.5rem 1.5rem !important;
}

.modern-swal-danger {
    background-color: #dc3545 !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    padding: 0.5rem 1.5rem !important;
}
</style>

<?php include 'layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modern modal functionality
    window.closeBookingModal = function() {
        const modal = document.getElementById('bookingModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    };

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeBookingModal();
        }
    });
});

function updateStatus(bookingId, status) {
    Swal.fire({
        title: 'Update Booking Status?',
        text: `Are you sure you want to change the booking status to "${status}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary-color)',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'modern-swal-popup',
            confirmButton: 'modern-swal-confirm',
            cancelButton: 'modern-swal-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the booking status.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'modern-swal-popup'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('bookings_modern.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&booking_id=${bookingId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Updated!',
                            text: 'Booking status has been updated successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'modern-swal-popup'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'An error occurred while updating the booking status.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'modern-swal-popup',
                                confirmButton: 'modern-swal-confirm'
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while updating the booking status.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'modern-swal-popup',
                            confirmButton: 'modern-swal-confirm'
                        }
                    });
                });
        }
    });
}

function deleteBooking(bookingId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You won\'t be able to revert this! This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: 'var(--primary-color)',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'modern-swal-popup',
            confirmButton: 'modern-swal-danger',
            cancelButton: 'modern-swal-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the booking.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'modern-swal-popup'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('bookings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_booking&booking_id=${bookingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Booking has been deleted successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'modern-swal-popup'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'An error occurred while deleting the booking.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'modern-swal-popup',
                                confirmButton: 'modern-swal-confirm'
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while deleting the booking.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'modern-swal-popup',
                            confirmButton: 'modern-swal-confirm'
                        }
                    });
                });
        }
    });
}

function viewBooking(bookingId) {
    const modal = document.getElementById('bookingModal');
    const modalBody = document.getElementById('bookingDetails');
    
    if (!modal || !modalBody) return;
    
    // Show modal with loading state
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Reset content to loading state
    modalBody.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading booking details...</p>
        </div>
    `;
    
    // Load booking details via AJAX
    fetch(`../includes/ajax_handler.php?action=get_booking_details&id=${bookingId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = data.html;
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading booking details: ${data.message || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading booking details. Please try again.
                </div>
            `;
        });
}

function exportBookings() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = 'bookings.php?' + params.toString();
}
</script>
