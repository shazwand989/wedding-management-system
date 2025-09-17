<?php
define('ADMIN_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || getUserRole() !== 'admin') {
    redirectTo('../login.php');
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    redirectTo('bookings.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic booking details
        $package_id = $_POST['package_id'] ?: null;
        $guest_count = (int)$_POST['guest_count'];
        $event_date = $_POST['event_date'];
        $venue_preference = $_POST['venue_preference'];
        $special_requirements = $_POST['special_requirements'];
        $total_amount = (float)$_POST['total_amount'];
        $deposit_amount = (float)$_POST['deposit_amount'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        
        // Update booking
        $stmt = $pdo->prepare("
            UPDATE bookings SET 
                package_id = ?, 
                guest_count = ?, 
                event_date = ?, 
                venue_preference = ?, 
                special_requirements = ?, 
                total_amount = ?, 
                deposit_amount = ?, 
                status = ?, 
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $package_id, $guest_count, $event_date, $venue_preference, 
            $special_requirements, $total_amount, $deposit_amount, $status, $notes, $booking_id
        ]);
        
        // Handle vendor assignments
        if (isset($_POST['assigned_vendors']) && is_array($_POST['assigned_vendors'])) {
            // First, remove all existing vendor assignments for this booking
            $stmt = $pdo->prepare("DELETE FROM booking_vendors WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            
            // Then add the new vendor assignments
            foreach ($_POST['assigned_vendors'] as $vendor_assignment) {
                if (!empty($vendor_assignment['vendor_id'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO booking_vendors 
                        (booking_id, vendor_id, service_type, agreed_price, status, notes) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $booking_id,
                        $vendor_assignment['vendor_id'],
                        $vendor_assignment['service_type'],
                        $vendor_assignment['agreed_price'] ?: null,
                        $vendor_assignment['status'],
                        $vendor_assignment['notes']
                    ]);
                }
            }
        }
        
        $_SESSION['success'] = 'Booking updated successfully!';
        redirectTo('bookings.php');
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating booking: ' . $e->getMessage();
    }
}

// Fetch booking details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.name as package_name, c.name as customer_name, c.email as customer_email
        FROM bookings b 
        LEFT JOIN packages p ON b.package_id = p.id 
        LEFT JOIN customers c ON b.customer_id = c.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $_SESSION['error'] = 'Booking not found';
        redirectTo('bookings.php');
    }
    
    // Fetch packages for dropdown
    $stmt = $pdo->prepare("SELECT id, name, base_price FROM packages ORDER BY name");
    $stmt->execute();
    $packages = $stmt->fetchAll();
    
    // Fetch assigned vendors
    $stmt = $pdo->prepare("
        SELECT bv.*, v.business_name, v.contact_name 
        FROM booking_vendors bv 
        JOIN vendors v ON bv.vendor_id = v.id 
        WHERE bv.booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    $assigned_vendors = $stmt->fetchAll();
    
    // Fetch available vendors (not yet assigned to this booking)
    $stmt = $pdo->prepare("
        SELECT v.* FROM vendors v 
        WHERE v.id NOT IN (
            SELECT vendor_id FROM booking_vendors WHERE booking_id = ?
        ) AND v.status = 'active'
        ORDER BY v.business_name
    ");
    $stmt->execute([$booking_id]);
    $available_vendors = $stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error fetching booking details: ' . $e->getMessage();
    redirectTo('bookings.php');
}
include 'layouts/header.php';

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Booking</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="bookings.php">Bookings</a></li>
                        <li class="breadcrumb-item active">Edit Booking</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Booking Summary -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Booking Summary</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>#<?php echo $booking['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Customer:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($booking['customer_email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-info btn-sm btn-block mb-2" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm btn-block" onclick="printBooking()">
                                <i class="fas fa-print"></i> Print Booking
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-md-8">
                    <form method="POST" id="editBookingForm">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Edit Booking Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="package_id">Package</label>
                                            <select class="form-control" id="package_id" name="package_id">
                                                <option value="">No Package</option>
                                                <?php foreach ($packages as $package): ?>
                                                    <option value="<?php echo $package['id']; ?>" 
                                                            data-price="<?php echo $package['base_price']; ?>"
                                                            <?php echo ($booking['package_id'] == $package['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($package['name']); ?> - RM<?php echo number_format($package['base_price'], 2); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="guest_count">Guest Count <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="guest_count" name="guest_count" 
                                                   value="<?php echo $booking['guest_count']; ?>" min="1" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="event_date">Event Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="event_date" name="event_date" 
                                                   value="<?php echo $booking['event_date']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="pending" <?php echo ($booking['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($booking['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo ($booking['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo ($booking['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="venue_preference">Venue Preference</label>
                                    <input type="text" class="form-control" id="venue_preference" name="venue_preference" 
                                           value="<?php echo htmlspecialchars($booking['venue_preference']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="special_requirements">Special Requirements</label>
                                    <textarea class="form-control" id="special_requirements" name="special_requirements" rows="3"><?php echo htmlspecialchars($booking['special_requirements']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="total_amount">Total Amount (RM) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                                   value="<?php echo $booking['total_amount']; ?>" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="deposit_amount">Deposit Amount (RM)</label>
                                            <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" 
                                                   value="<?php echo $booking['deposit_amount']; ?>" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Admin Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Internal notes for this booking..."><?php echo htmlspecialchars($booking['notes']); ?></textarea>
                                </div>

                                <!-- Vendor Assignment Section -->
                                <div class="bg-light p-3 rounded mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Assigned Vendors</h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="add-vendor">
                                            <i class="fas fa-plus"></i> Add Vendor
                                        </button>
                                    </div>

                                    <div id="vendor-assignments">
                                        <?php if (empty($assigned_vendors)): ?>
                                            <p class="text-muted">No vendors assigned yet.</p>
                                        <?php else: ?>
                                            <?php foreach ($assigned_vendors as $index => $vendor): ?>
                                                <div class="vendor-assignment mb-3 p-3 border rounded bg-light" data-index="<?php echo $index; ?>">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="mb-0 text-info"><?php echo htmlspecialchars($vendor['business_name']); ?></h6>
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-vendor">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <input type="hidden" name="assigned_vendors[<?php echo $index; ?>][vendor_id]" value="<?php echo $vendor['vendor_id']; ?>">
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-2">
                                                                <label class="small font-weight-bold">Service Type</label>
                                                                <select class="form-control form-control-sm" name="assigned_vendors[<?php echo $index; ?>][service_type]">
                                                                    <option value="photography" <?php echo ($vendor['service_type'] == 'photography') ? 'selected' : ''; ?>>Photography</option>
                                                                    <option value="catering" <?php echo ($vendor['service_type'] == 'catering') ? 'selected' : ''; ?>>Catering</option>
                                                                    <option value="decoration" <?php echo ($vendor['service_type'] == 'decoration') ? 'selected' : ''; ?>>Decoration</option>
                                                                    <option value="music" <?php echo ($vendor['service_type'] == 'music') ? 'selected' : ''; ?>>Music</option>
                                                                    <option value="venue" <?php echo ($vendor['service_type'] == 'venue') ? 'selected' : ''; ?>>Venue</option>
                                                                    <option value="other" <?php echo ($vendor['service_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-2">
                                                                <label class="small font-weight-bold">Status</label>
                                                                <select class="form-control form-control-sm" name="assigned_vendors[<?php echo $index; ?>][status]">
                                                                    <option value="pending" <?php echo ($vendor['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="confirmed" <?php echo ($vendor['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                                    <option value="cancelled" <?php echo ($vendor['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-2">
                                                        <label class="small font-weight-bold">Agreed Price (RM)</label>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="assigned_vendors[<?php echo $index; ?>][agreed_price]" 
                                                               value="<?php echo $vendor['agreed_price']; ?>" min="0" step="0.01">
                                                    </div>
                                                    
                                                    <div class="form-group mb-0">
                                                        <label class="small font-weight-bold">Notes</label>
                                                        <textarea class="form-control form-control-sm" 
                                                                  name="assigned_vendors[<?php echo $index; ?>][notes]" 
                                                                  rows="2" placeholder="Additional notes for this vendor..."><?php echo htmlspecialchars($vendor['notes']); ?></textarea>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="bookings.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Bookings
                                        </a>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Update Booking
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vendor to Booking</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="select_vendor">Select Vendor</label>
                    <select class="form-control" id="select_vendor">
                        <option value="">Choose a vendor...</option>
                        <?php foreach ($available_vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($vendor['business_name']); ?>"
                                    data-service="<?php echo $vendor['service_type']; ?>">
                                <?php echo htmlspecialchars($vendor['business_name']); ?> - <?php echo ucfirst($vendor['service_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="initial_price">Initial Agreed Price (RM)</label>
                    <input type="number" class="form-control" id="initial_price" min="0" step="0.01" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-add-vendor">Add Vendor</button>
            </div>
        </div>
    </div>
</div>

<script>
let vendorIndex = <?php echo count($assigned_vendors); ?>;

// Package selection auto-fill price
$('#package_id').change(function() {
    const selectedOption = $(this).find('option:selected');
    const price = selectedOption.data('price');
    if (price) {
        $('#total_amount').val(price);
    }
});

// Add vendor functionality
$('#add-vendor').click(function() {
    $('#addVendorModal').modal('show');
});

$('#confirm-add-vendor').click(function() {
    const vendorSelect = $('#select_vendor');
    const vendorId = vendorSelect.val();
    const vendorName = vendorSelect.find('option:selected').data('name');
    const serviceType = vendorSelect.find('option:selected').data('service');
    const initialPrice = $('#initial_price').val() || 0;
    
    if (!vendorId) {
        alert('Please select a vendor');
        return;
    }
    
    // Check if vendor is already assigned
    let alreadyAssigned = false;
    $('input[name*="[vendor_id]"]').each(function() {
        if ($(this).val() == vendorId) {
            alreadyAssigned = true;
            return false;
        }
    });
    
    if (alreadyAssigned) {
        alert('This vendor is already assigned to this booking');
        return;
    }
    
    const vendorHtml = `
        <div class="vendor-assignment mb-3 p-3 border rounded bg-light" data-index="${vendorIndex}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0 text-info">${vendorName}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-vendor">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <input type="hidden" name="assigned_vendors[${vendorIndex}][vendor_id]" value="${vendorId}">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Service Type</label>
                        <select class="form-control form-control-sm" name="assigned_vendors[${vendorIndex}][service_type]">
                            <option value="photography" ${serviceType === 'photography' ? 'selected' : ''}>Photography</option>
                            <option value="catering" ${serviceType === 'catering' ? 'selected' : ''}>Catering</option>
                            <option value="decoration" ${serviceType === 'decoration' ? 'selected' : ''}>Decoration</option>
                            <option value="music" ${serviceType === 'music' ? 'selected' : ''}>Music</option>
                            <option value="venue" ${serviceType === 'venue' ? 'selected' : ''}>Venue</option>
                            <option value="other" ${serviceType === 'other' ? 'selected' : ''}>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Status</label>
                        <select class="form-control form-control-sm" name="assigned_vendors[${vendorIndex}][status]">
                            <option value="pending" selected>Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group mb-2">
                <label class="small font-weight-bold">Agreed Price (RM)</label>
                <input type="number" class="form-control form-control-sm" 
                       name="assigned_vendors[${vendorIndex}][agreed_price]" 
                       value="${initialPrice}" min="0" step="0.01">
            </div>
            
            <div class="form-group mb-0">
                <label class="small font-weight-bold">Notes</label>
                <textarea class="form-control form-control-sm" 
                          name="assigned_vendors[${vendorIndex}][notes]" 
                          rows="2" placeholder="Additional notes for this vendor..."></textarea>
            </div>
        </div>
    `;
    
    // If this is the first vendor, remove the "No vendors assigned" message
    if ($('#vendor-assignments p.text-muted').length > 0) {
        $('#vendor-assignments').empty();
    }
    
    $('#vendor-assignments').append(vendorHtml);
    vendorIndex++;
    
    // Reset and close modal
    vendorSelect.val('');
    $('#initial_price').val('');
    $('#addVendorModal').modal('hide');
});

// Remove vendor functionality
$(document).on('click', '.remove-vendor', function() {
    if (confirm('Are you sure you want to remove this vendor from the booking?')) {
        $(this).closest('.vendor-assignment').remove();
        
        // If no vendors left, show the "No vendors assigned" message
        if ($('#vendor-assignments .vendor-assignment').length === 0) {
            $('#vendor-assignments').html('<p class="text-muted">No vendors assigned yet.</p>');
        }
    }
});

// Form validation
$('#editBookingForm').submit(function(e) {
    const eventDate = $('#event_date').val();
    const guestCount = $('#guest_count').val();
    const totalAmount = $('#total_amount').val();
    
    if (!eventDate || !guestCount || !totalAmount) {
        e.preventDefault();
        alert('Please fill in all required fields (Event Date, Guest Count, Total Amount)');
        return false;
    }
    
    if (parseInt(guestCount) <= 0) {
        e.preventDefault();
        alert('Guest count must be greater than 0');
        return false;
    }
    
    if (parseFloat(totalAmount) < 0) {
        e.preventDefault();
        alert('Total amount cannot be negative');
        return false;
    }
    
    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving Changes...');
    submitBtn.prop('disabled', true);
});

// Additional utility functions
function viewBookingDetails(bookingId) {
    // You can implement a modal or redirect to view booking details
    window.open('bookings.php?view=' + bookingId, '_blank');
}

function printBooking() {
    window.print();
}

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php include 'layouts/footer.php'; ?>