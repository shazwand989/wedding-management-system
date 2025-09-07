<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'New Booking';
$breadcrumbs = [
    ['title' => 'New Booking']
];

$customer_id = $_SESSION['user_id'];

// Handle form submission
if ($_POST && isset($_POST['create_booking'])) {
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $venue_name = $_POST['venue_name'] ?? '';
    $venue_address = $_POST['venue_address'] ?? '';
    $guest_count = (int)($_POST['guest_count'] ?? 0);
    $package_id = $_POST['package_id'] ? (int)$_POST['package_id'] : null;
    $special_requests = $_POST['special_requests'] ?? '';
    $budget = (float)($_POST['budget'] ?? 0);
    
    $errors = [];
    
    // Validation
    if (empty($event_date)) $errors[] = "Event date is required.";
    if (empty($event_time)) $errors[] = "Event time is required.";
    if (empty($venue_name)) $errors[] = "Venue name is required.";
    if ($guest_count <= 0) $errors[] = "Guest count must be greater than 0.";
    if ($budget <= 0) $errors[] = "Budget must be greater than 0.";
    
    // Check if event date is in the future
    if (!empty($event_date) && strtotime($event_date) < strtotime('today')) {
        $errors[] = "Event date must be in the future.";
    }
    
    if (empty($errors)) {
        try {
            // Calculate total amount based on package or custom budget
            $total_amount = $budget;
            if ($package_id) {
                $stmt = $pdo->prepare("SELECT price FROM wedding_packages WHERE id = ?");
                $stmt->execute([$package_id]);
                $package = $stmt->fetch();
                if ($package) {
                    $total_amount = $package['price'];
                }
            }
            
            // Insert booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (
                    customer_id, event_date, event_time, venue_name, venue_address,
                    guest_count, package_id, special_requests, total_amount, 
                    paid_amount, booking_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending', NOW())
            ");
            
            $stmt->execute([
                $customer_id, $event_date, $event_time, $venue_name, $venue_address,
                $guest_count, $package_id, $special_requests, $total_amount
            ]);
            
            $booking_id = $pdo->lastInsertId();
            
            // If vendors were selected, create booking_vendors entries
            if (isset($_POST['vendors']) && is_array($_POST['vendors'])) {
                $vendor_stmt = $pdo->prepare("
                    INSERT INTO booking_vendors (booking_id, vendor_id, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                ");
                
                foreach ($_POST['vendors'] as $vendor_id) {
                    $vendor_stmt->execute([$booking_id, (int)$vendor_id]);
                }
            }
            
            $success_message = "Booking created successfully! Booking ID: #" . $booking_id;
            
            // Redirect to bookings page after a delay
            header("refresh:3;url=bookings.php");
            
        } catch (PDOException $e) {
            $errors[] = "Error creating booking: " . $e->getMessage();
        }
    }
}

// Get available packages
try {
    $stmt = $pdo->query("SELECT * FROM wedding_packages WHERE status = 'active' ORDER BY price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}

// Get available vendors
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.full_name, u.email, u.phone
        FROM vendors v
        JOIN users u ON v.user_id = u.id
        WHERE v.status = 'active'
        ORDER BY v.business_name
    ");
    $stmt->execute();
    $vendors = $stmt->fetchAll();
} catch (PDOException $e) {
    $vendors = [];
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

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <!-- Event Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Event Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="event_date">Event Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                           value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="event_time">Event Time *</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time" 
                                           value="<?php echo htmlspecialchars($_POST['event_time'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="venue_name">Venue Name *</label>
                            <input type="text" class="form-control" id="venue_name" name="venue_name" 
                                   placeholder="Enter venue name" 
                                   value="<?php echo htmlspecialchars($_POST['venue_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="venue_address">Venue Address</label>
                            <textarea class="form-control" id="venue_address" name="venue_address" rows="3" 
                                      placeholder="Enter complete venue address"><?php echo htmlspecialchars($_POST['venue_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_count">Number of Guests *</label>
                                    <input type="number" class="form-control" id="guest_count" name="guest_count" 
                                           min="1" placeholder="Enter expected number of guests" 
                                           value="<?php echo htmlspecialchars($_POST['guest_count'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="budget">Budget (RM) *</label>
                                    <input type="number" class="form-control" id="budget" name="budget" 
                                           min="1" step="0.01" placeholder="Enter your budget" 
                                           value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="special_requests">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="4" 
                                      placeholder="Any special requirements or requests for your wedding..."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Vendor Selection -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select Vendors (Optional)</h3>
                        <p class="card-text">Choose vendors you'd like to invite for your wedding</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vendors)): ?>
                            <p class="text-muted">No vendors available at the moment.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($vendors as $vendor): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="vendor_<?php echo $vendor['id']; ?>" 
                                                   name="vendors[]" value="<?php echo $vendor['id']; ?>"
                                                   <?php echo (isset($_POST['vendors']) && in_array($vendor['id'], $_POST['vendors'])) ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="vendor_<?php echo $vendor['id']; ?>">
                                                <strong><?php echo htmlspecialchars($vendor['business_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($vendor['service_type']); ?> | 
                                                    Rating: <?php echo number_format($vendor['rating'], 1); ?>/5.0
                                                    <?php if ($vendor['price_range']): ?>
                                                        | <?php echo htmlspecialchars($vendor['price_range']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Package Selection -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Wedding Packages</h3>
                        <p class="card-text">Choose a package or create custom booking</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($packages)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No packages available. You can create a custom booking.
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <div class="custom-control custom-radio">
                                    <input type="radio" class="custom-control-input" id="custom_package" 
                                           name="package_id" value="" 
                                           <?php echo empty($_POST['package_id']) ? 'checked' : ''; ?>
                                           onchange="toggleBudget()">
                                    <label class="custom-control-label" for="custom_package">
                                        <strong>Custom Package</strong><br>
                                        <small class="text-muted">Set your own budget and requirements</small>
                                    </label>
                                </div>
                            </div>

                            <?php foreach ($packages as $package): ?>
                                <div class="form-group">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" class="custom-control-input" 
                                               id="package_<?php echo $package['id']; ?>" 
                                               name="package_id" value="<?php echo $package['id']; ?>"
                                               <?php echo (isset($_POST['package_id']) && $_POST['package_id'] == $package['id']) ? 'checked' : ''; ?>
                                               onchange="selectPackage(<?php echo $package['price']; ?>)">
                                        <label class="custom-control-label" for="package_<?php echo $package['id']; ?>">
                                            <strong><?php echo htmlspecialchars($package['name']); ?></strong><br>
                                            <span class="text-success font-weight-bold">RM <?php echo number_format($package['price'], 2); ?></span><br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($package['description'], 0, 100)); ?>...</small>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" name="create_booking" class="btn btn-primary btn-lg">
                            <i class="fas fa-heart"></i> Create Booking
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                By creating a booking, you agree to our terms and conditions.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleBudget() {
    const budgetField = document.getElementById('budget');
    budgetField.disabled = false;
    budgetField.required = true;
}

function selectPackage(price) {
    const budgetField = document.getElementById('budget');
    budgetField.value = price;
    budgetField.disabled = true;
    budgetField.required = false;
}

// Initialize based on current selection
document.addEventListener('DOMContentLoaded', function() {
    const selectedPackage = document.querySelector('input[name="package_id"]:checked');
    if (selectedPackage && selectedPackage.value !== '') {
        const price = selectedPackage.getAttribute('onchange').match(/\d+/)[0];
        selectPackage(parseInt(price));
    } else {
        toggleBudget();
    }
});
</script>

<?php include 'layouts/footer.php'; ?>
