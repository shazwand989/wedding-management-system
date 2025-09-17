<?php
define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    redirectTo('../login.php');
}

// Page variables
$page_title = 'Booking Management';
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

// Include layout header
include 'layouts/header.php';
?>

<div class="container-fluid">

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-warning">
                <div class="inner">
                    <h3><?php echo number_format($stats['total_bookings']); ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <a href="#" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-info">
                <div class="inner">
                    <h3><?php echo number_format($stats['pending_bookings']); ?></h3>
                    <p>Pending</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="?status=pending" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-success">
                <div class="inner">
                    <h3><?php echo number_format($stats['confirmed_bookings']); ?></h3>
                    <p>Confirmed</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="?status=confirmed" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-danger">
                <div class="inner">
                    <h3>RM <?php echo number_format($stats['total_revenue'], 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <a href="#" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
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
                    <div>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
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
                <table id="bookingsTable" class="table table-striped table-hover">
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
                                    <td data-order="<?php echo strtotime($booking['event_date']); ?>">
                                        <?php echo date('M j, Y', strtotime($booking['event_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($booking['event_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['venue_name'] ?: 'Not specified'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['package_name'] ?: 'Custom'); ?></td>
                                    <td><?php echo number_format($booking['guest_count']); ?></td>
                                    <td data-order="<?php echo $booking['total_amount']; ?>">RM <?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td data-order="<?php echo $booking['paid_amount']; ?>">
                                        <div>
                                            RM <?php echo number_format($booking['paid_amount'], 2); ?><br>
                                            <span class="badge badge-<?php echo $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $booking['booking_status'] === 'confirmed' ? 'success' : ($booking['booking_status'] === 'pending' ? 'warning' : ($booking['booking_status'] === 'completed' ? 'info' : 'danger'));
                                                                    ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- View button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- Edit button -->
                                            <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-info" title="Edit Booking">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Status dropdown -->
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Update Status">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'pending')">
                                                        <i class="fas fa-clock text-warning"></i> Mark Pending</a>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                        <i class="fas fa-check text-success"></i> Mark Confirmed</a>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'completed')">
                                                        <i class="fas fa-check-circle text-info"></i> Mark Completed</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                        <i class="fas fa-times text-danger"></i> Mark Cancelled</a>
                                                </div>
                                            </div>

                                            <!-- Delete button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBooking(<?php echo $booking['id']; ?>)" title="Delete Booking">
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

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Booking Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Content loaded via AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-warning" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading booking details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>

    <?php include 'layouts/footer.php'; ?>

    <script>
        function updateStatus(bookingId, status) {
            Swal.fire({
                title: 'Update Booking Status?',
                text: `Are you sure you want to change the booking status to "${status}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Updating...',
                        text: 'Please wait while we update the booking status.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'bookings.php',
                        type: 'POST',
                        data: {
                            action: 'update_status',
                            booking_id: bookingId,
                            status: status
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Updated!',
                                    text: 'Booking status has been updated successfully.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', data.message || 'An error occurred while updating the booking status.', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            Swal.fire('Error!', 'An error occurred while updating the booking status.', 'error');
                        }
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
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the booking.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'bookings.php',
                        type: 'POST',
                        data: {
                            action: 'delete_booking',
                            booking_id: bookingId
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Booking has been deleted successfully.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', data.message || 'An error occurred while deleting the booking.', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            Swal.fire('Error!', 'An error occurred while deleting the booking.', 'error');
                        }
                    });
                }
            });
        }

        function viewBooking(bookingId) {
            $.ajax({
                url: '../includes/ajax_handler.php',
                type: 'GET',
                data: {
                    action: 'get_booking_details',
                    id: bookingId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        $('#bookingDetails').html(data.html);
                        $('#bookingModal').modal('show');
                    } else {
                        Swal.fire('Error!', data.message || 'Unknown error', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    Swal.fire('Error!', 'Error loading booking details. Please try again.', 'error');
                }
            });
        }

        function exportBookings() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.location.href = 'bookings.php?' + params.toString();
        }

        // Initialize DataTable
        $(document).ready(function() {
            $('#bookingsTable').DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "pageLength": 25,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                "order": [
                    [2, "desc"]
                ], // Sort by event date descending
                "columnDefs": [{
                        "targets": [9], // Actions column
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": [0], // ID column
                        "width": "80px"
                    },
                    {
                        "targets": [6, 7], // Amount and Payment columns
                        "className": "text-right"
                    },
                    {
                        "targets": [8], // Status column
                        "className": "text-center"
                    },
                    {
                        "targets": [9], // Actions column
                        "className": "text-center",
                        "width": "150px"
                    }
                ],
                "language": {
                    "search": "Search bookings:",
                    "lengthMenu": "Show _MENU_ bookings per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ bookings",
                    "infoEmpty": "No bookings available",
                    "infoFiltered": "(filtered from _MAX_ total bookings)",
                    "zeroRecords": "No matching bookings found",
                    "emptyTable": "No bookings available in table",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                },
                "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            // Custom filter integration
            const table = $('#bookingsTable').DataTable();

            // Clear any existing DataTable search when using custom filters
            $('form[method="GET"]').on('submit', function() {
                table.search('').draw();
            });
        });
    </script>