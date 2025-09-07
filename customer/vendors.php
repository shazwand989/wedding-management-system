<?php
define('CUSTOMER_ACCESS', true);
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    redirectTo('../login.php');
}

// Page configuration
$page_title = 'Find Vendors';
$breadcrumbs = [
    ['title' => 'Find Vendors']
];

// Get filter parameters
$service_type = $_GET['service_type'] ?? 'all';
$location = $_GET['location'] ?? '';
$min_rating = $_GET['min_rating'] ?? 0;
$sort = $_GET['sort'] ?? 'rating';

// Build query based on filters
$where_conditions = ["v.status = 'active'"];
$params = [];

if ($service_type !== 'all') {
    $where_conditions[] = "v.service_type = ?";
    $params[] = $service_type;
}

if (!empty($location)) {
    $where_conditions[] = "(v.location LIKE ? OR v.business_name LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if ($min_rating > 0) {
    $where_conditions[] = "v.rating >= ?";
    $params[] = $min_rating;
}

$where_clause = implode(' AND ', $where_conditions);

// Order clause
$order_clause = "ORDER BY ";
switch ($sort) {
    case 'name':
        $order_clause .= "v.business_name ASC";
        break;
    case 'rating':
        $order_clause .= "v.rating DESC, v.total_reviews DESC";
        break;
    case 'price_low':
        $order_clause .= "v.price_range ASC, v.rating DESC";
        break;
    case 'price_high':
        $order_clause .= "v.price_range DESC, v.rating DESC";
        break;
    default:
        $order_clause .= "v.rating DESC, v.total_reviews DESC";
}

// Get vendors
try {
    $stmt = $pdo->prepare("
        SELECT v.*, u.full_name, u.email, u.phone
        FROM vendors v
        JOIN users u ON v.user_id = u.id
        WHERE $where_clause
        $order_clause
    ");
    $stmt->execute($params);
    $vendors = $stmt->fetchAll();

    // Get service types for filter
    $service_types_stmt = $pdo->query("SELECT DISTINCT service_type FROM vendors WHERE status = 'active' ORDER BY service_type");
    $service_types = $service_types_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error_message = "Error loading vendors: " . $e->getMessage();
    $vendors = [];
    $service_types = [];
}

include 'layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error_message; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Find Your Perfect Wedding Vendors</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Service Type</label>
                            <select name="service_type" class="form-control">
                                <option value="all" <?php echo $service_type === 'all' ? 'selected' : ''; ?>>All Services</option>
                                <?php foreach ($service_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $service_type === $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" 
                                   placeholder="Enter city or area" 
                                   value="<?php echo htmlspecialchars($location); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Min Rating</label>
                            <select name="min_rating" class="form-control">
                                <option value="0" <?php echo $min_rating == 0 ? 'selected' : ''; ?>>Any Rating</option>
                                <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4+ Stars</option>
                                <option value="4.5" <?php echo $min_rating == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Sort By</label>
                            <select name="sort" class="form-control">
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Section -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    Available Vendors 
                    <span class="badge badge-primary"><?php echo count($vendors); ?> found</span>
                </h3>
                <div>
                    <a href="new-booking.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($vendors)): ?>
                <div class="text-center p-5">
                    <i class="fas fa-search fa-4x text-primary mb-4"></i>
                    <h4>No Vendors Found</h4>
                    <p class="text-muted">Try adjusting your search criteria or browse all vendors.</p>
                    <a href="vendors.php" class="btn btn-primary">View All Vendors</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($vendors as $vendor): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 vendor-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($vendor['business_name']); ?></h5>
                                            <small class="text-muted"><?php echo ucfirst($vendor['service_type']); ?></small>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= round($vendor['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($vendor['rating'], 1); ?> (<?php echo $vendor['total_reviews']; ?> reviews)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if ($vendor['description']): ?>
                                        <p class="text-muted mb-3">
                                            <?php echo htmlspecialchars(substr($vendor['description'], 0, 120)); ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                        <small><?php echo htmlspecialchars($vendor['location'] ?: 'Location not specified'); ?></small>
                                    </div>
                                    
                                    <?php if ($vendor['price_range']): ?>
                                        <div class="mb-2">
                                            <i class="fas fa-dollar-sign text-success"></i>
                                            <small><strong><?php echo htmlspecialchars($vendor['price_range']); ?></strong></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-user text-info"></i>
                                        <small><?php echo htmlspecialchars($vendor['full_name']); ?></small>
                                    </div>

                                    <?php if ($vendor['specialties']): ?>
                                        <div class="mb-3">
                                            <strong>Specialties:</strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($vendor['specialties']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-primary btn-sm" onclick="viewVendorDetails(<?php echo $vendor['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <a href="new-booking.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-heart"></i> Book Now
                                        </a>
                                        <button class="btn btn-info btn-sm" onclick="contactVendor('<?php echo $vendor['email']; ?>', '<?php echo $vendor['phone']; ?>')">
                                            <i class="fas fa-phone"></i> Contact
                                        </button>
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

<!-- Vendor Details Modal -->
<div class="modal fade" id="vendorDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="vendorDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Vendor</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="contactContent">
                <!-- Contact info will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.vendor-card {
    transition: transform 0.2s;
}

.vendor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<script>
function viewVendorDetails(vendorId) {
    $('#vendorDetailsModal').modal('show');
    
    $.ajax({
        url: '../includes/ajax_handler.php',
        method: 'POST',
        data: {
            action: 'get_vendor_details',
            vendor_id: vendorId
        },
        success: function(response) {
            $('#vendorDetailsContent').html(response);
        },
        error: function() {
            $('#vendorDetailsContent').html('<div class="alert alert-danger">Error loading vendor details.</div>');
        }
    });
}

function contactVendor(email, phone) {
    const content = `
        <div class="text-center">
            <h4>Contact Information</h4>
            <div class="mb-3">
                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                <p><strong>Email:</strong><br><a href="mailto:${email}">${email}</a></p>
            </div>
            <div class="mb-3">
                <i class="fas fa-phone fa-2x text-success mb-2"></i>
                <p><strong>Phone:</strong><br><a href="tel:${phone}">${phone}</a></p>
            </div>
            <div class="btn-group">
                <a href="mailto:${email}" class="btn btn-primary">Send Email</a>
                <a href="tel:${phone}" class="btn btn-success">Call Now</a>
            </div>
        </div>
    `;
    
    $('#contactContent').html(content);
    $('#contactModal').modal('show');
}
</script>

<?php include 'layouts/footer.php'; ?>
