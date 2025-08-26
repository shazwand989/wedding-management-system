<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'New Booking';
$page_header = 'Create New Booking';
$page_description = 'Plan your perfect wedding day';

$customer_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get wedding packages
try {
    $stmt = $pdo->query("SELECT * FROM wedding_packages WHERE status = 'active' ORDER BY price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}

// Get active vendors
try {
    $stmt = $pdo->query("
        SELECT v.*, u.full_name 
        FROM vendors v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.status = 'active' 
        ORDER BY v.service_type, v.business_name
    ");
    $vendors = $stmt->fetchAll();

    // Group vendors by service type
    $grouped_vendors = [];
    foreach ($vendors as $vendor) {
        $grouped_vendors[$vendor['service_type']][] = $vendor;
    }
} catch (PDOException $e) {
    $vendors = [];
    $grouped_vendors = [];
}

// Handle form submission
if ($_POST) {
    $package_id = $_POST['package_id'] ? (int)$_POST['package_id'] : null;
    $event_date = sanitize($_POST['event_date']);
    $event_time = sanitize($_POST['event_time']);
    $venue_name = sanitize($_POST['venue_name']);
    $venue_address = sanitize($_POST['venue_address']);
    $guest_count = (int)$_POST['guest_count'];
    $special_requests = sanitize($_POST['special_requests']);
    $selected_vendors = $_POST['vendors'] ?? [];

    // Validation
    if (empty($event_date) || empty($event_time) || empty($venue_name)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($event_date) < time()) {
        $error = 'Event date must be in the future.';
    } else {
        try {
            // Calculate total amount
            $total_amount = 0;
            if ($package_id) {
                $stmt = $pdo->prepare("SELECT price FROM wedding_packages WHERE id = ? AND status = 'active'");
                $stmt->execute([$package_id]);
                $package = $stmt->fetch();
                if ($package) {
                    $total_amount = $package['price'];
                } else {
                    $error = 'Selected package is not available.';
                }
            } else {
                // Custom package - calculate based on vendors
                $total_amount = 5000; // Base amount for custom package
            }

            if (!$error) {
                // Create booking
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (customer_id, package_id, event_date, event_time, venue_name, venue_address, guest_count, special_requests, total_amount, booking_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                if ($stmt->execute([$customer_id, $package_id, $event_date, $event_time, $venue_name, $venue_address, $guest_count, $special_requests, $total_amount])) {
                    $booking_id = $pdo->lastInsertId();

                    // Add selected vendors to booking
                    if (!empty($selected_vendors)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO booking_vendors (booking_id, vendor_id, service_type, status)
                            VALUES (?, ?, ?, 'pending')
                        ");

                        foreach ($selected_vendors as $vendor_id) {
                            $vendor_id = (int)$vendor_id;
                            // Get vendor service type
                            $vendor_stmt = $pdo->prepare("SELECT service_type FROM vendors WHERE id = ?");
                            $vendor_stmt->execute([$vendor_id]);
                            $vendor_data = $vendor_stmt->fetch();

                            if ($vendor_data) {
                                $stmt->execute([$booking_id, $vendor_id, $vendor_data['service_type']]);
                            }
                        }
                    }

                    $success = 'Booking created successfully! You will be contacted soon for confirmation.';

                    // Redirect after 2 seconds
                    header("refresh:2;url=bookings.php");
                } else {
                    $error = 'Failed to create booking. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}

// Pre-select package if provided in URL
$selected_package = isset($_GET['package']) ? (int)$_GET['package'] : null;

// Include layout components
include 'layouts/header.php';
include 'layouts/sidebar.php';
?>
<form method="POST" class="validate-form">
    <!-- Package Selection -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">1. Choose Your Package</h3>
        </div>

        <div class="form-group">
            <label>Wedding Package (Optional)</label>
            <div class="grid grid-3" style="margin-top: 1rem;">
                <!-- Custom Package Option -->
                <div class="card" style="margin: 0; cursor: pointer; transition: all 0.3s;" onclick="selectPackage(null)">
                    <input type="radio" name="package_id" value="" id="package_custom"
                        <?php echo $selected_package === null ? 'checked' : ''; ?> style="margin-bottom: 1rem;">
                    <label for="package_custom" style="cursor: pointer;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Custom Package</h4>
                        <p style="font-size: 0.9rem; color: #666;">Create your own package by selecting individual vendors and services.</p>
                        <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary-color); margin-top: 1rem;">
                            Starting from RM 5,000
                        </div>
                    </label>
                </div>

                <?php foreach ($packages as $package): ?>
                    <div class="card" style="margin: 0; cursor: pointer; transition: all 0.3s;" onclick="selectPackage(<?php echo $package['id']; ?>)">
                        <input type="radio" name="package_id" value="<?php echo $package['id']; ?>"
                            id="package_<?php echo $package['id']; ?>"
                            <?php echo $selected_package == $package['id'] ? 'checked' : ''; ?> style="margin-bottom: 1rem;">
                        <label for="package_<?php echo $package['id']; ?>" style="cursor: pointer;">
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);"><?php echo htmlspecialchars($package['name']); ?></h4>
                            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;"><?php echo htmlspecialchars($package['description']); ?></p>

                            <?php
                            $features = json_decode($package['features'], true);
                            if ($features):
                            ?>
                                <ul style="font-size: 0.8rem; color: #666; margin-bottom: 1rem;">
                                    <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                                        <li><i class="fas fa-check" style="color: var(--success-color);"></i> <?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($features) > 3): ?>
                                        <li><em>+ <?php echo count($features) - 3; ?> more features</em></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>

                            <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary-color);">
                                RM <?php echo number_format($package['price'], 2); ?>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Event Details -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">2. Event Details</h3>
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label for="event_date">
                    <i class="fas fa-calendar"></i> Event Date *
                </label>
                <input type="date" id="event_date" name="event_date" class="form-control future-date" required
                    value="<?php echo isset($_POST['event_date']) ? $_POST['event_date'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="event_time">
                    <i class="fas fa-clock"></i> Event Time *
                </label>
                <input type="time" id="event_time" name="event_time" class="form-control" required
                    value="<?php echo isset($_POST['event_time']) ? $_POST['event_time'] : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="venue_name">
                <i class="fas fa-building"></i> Venue Name *
            </label>
            <input type="text" id="venue_name" name="venue_name" class="form-control" required
                value="<?php echo isset($_POST['venue_name']) ? htmlspecialchars($_POST['venue_name']) : ''; ?>"
                placeholder="Enter venue name">
        </div>

        <div class="form-group">
            <label for="venue_address">
                <i class="fas fa-map-marker-alt"></i> Venue Address
            </label>
            <textarea id="venue_address" name="venue_address" class="form-control" rows="3"
                placeholder="Enter complete venue address"><?php echo isset($_POST['venue_address']) ? htmlspecialchars($_POST['venue_address']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="guest_count">
                <i class="fas fa-users"></i> Expected Number of Guests
            </label>
            <input type="number" id="guest_count" name="guest_count" class="form-control" min="1" max="1000"
                value="<?php echo isset($_POST['guest_count']) ? $_POST['guest_count'] : '50'; ?>">
        </div>
    </div>

    <!-- Vendor Selection -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">3. Select Vendors (Optional)</h3>
            <p style="margin: 0; font-size: 0.9rem; color: #666;">Choose vendors for your wedding. You can skip this and add vendors later.</p>
        </div>

        <?php if (empty($grouped_vendors)): ?>
            <p style="text-align: center; color: #666; padding: 2rem;">No vendors available at the moment.</p>
        <?php else: ?>
            <?php foreach ($grouped_vendors as $service_type => $service_vendors): ?>
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color); text-transform: capitalize;">
                        <i class="fas fa-<?php echo $service_type === 'photography' ? 'camera' : ($service_type === 'catering' ? 'utensils' : ($service_type === 'decoration' ? 'palette' : ($service_type === 'music' ? 'music' : 'store'))); ?>"></i>
                        <?php echo ucfirst($service_type); ?>
                    </h4>
                    <div class="grid grid-2">
                        <?php foreach ($service_vendors as $vendor): ?>
                            <div class="card" style="margin: 0;">
                                <label style="display: flex; align-items: flex-start; cursor: pointer;">
                                    <input type="checkbox" name="vendors[]" value="<?php echo $vendor['id']; ?>"
                                        style="margin-right: 1rem; margin-top: 0.2rem;">
                                    <div style="flex: 1;">
                                        <h5 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($vendor['business_name']); ?></h5>
                                        <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #666;">
                                            <?php echo htmlspecialchars($vendor['description'] ?: 'Professional ' . $service_type . ' services'); ?>
                                        </p>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 0.8rem; color: #666;">
                                                <i class="fas fa-star" style="color: var(--primary-color);"></i>
                                                <?php echo number_format($vendor['rating'], 1); ?> (<?php echo $vendor['total_reviews']; ?> reviews)
                                            </span>
                                            <span style="font-weight: bold; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($vendor['price_range'] ?: 'Contact for pricing'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Special Requests -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">4. Special Requests</h3>
        </div>

        <div class="form-group">
            <label for="special_requests">
                <i class="fas fa-comment"></i> Additional Notes or Special Requests
            </label>
            <textarea id="special_requests" name="special_requests" class="form-control" rows="4"
                placeholder="Tell us about any special requirements, dietary restrictions, themes, or other preferences..."><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
        </div>
    </div>

    <!-- Submit -->
    <div class="card">
        <div style="text-align: center; padding: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Ready to Book Your Dream Wedding?</h3>
            <p style="margin-bottom: 2rem; color: #666;">Our team will review your booking and contact you within 24 hours for confirmation and further details.</p>

            <button type="submit" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem;">
                <i class="fas fa-heart"></i> Create Booking
            </button>

            <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                By creating this booking, you agree to our terms and conditions.
            </p>
        </div>
    </div>
</form>
</div>
<?php include 'layouts/footer.php'; ?>
<script>
    function selectPackage(packageId) {
        // Update radio button
        const radio = packageId ? document.getElementById('package_' + packageId) : document.getElementById('package_custom');
        radio.checked = true;

        // Visual feedback
        document.querySelectorAll('.card').forEach(card => {
            if (card.onclick) {
                card.style.borderColor = '#ddd';
                card.style.backgroundColor = '#fff';
            }
        });

        radio.closest('.card').style.borderColor = 'var(--primary-color)';
        radio.closest('.card').style.backgroundColor = 'var(--secondary-color)';
    }
    // Initialize package selection styling
    document.addEventListener('DOMContentLoaded', function() {
        const selectedRadio = document.querySelector('input[name=\"package_id\"]:checked');
        if (selectedRadio) {
            selectedRadio.closest('.card').style.borderColor = 'var(--primary-color)';
            selectedRadio.closest('.card').style.backgroundColor = 'var(--secondary-color)';
        }
    });
</script>