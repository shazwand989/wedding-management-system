<?php
require_once 'includes/config.php';

// Check if user is logged in and redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin':
            redirectTo('admin/dashboard.php');
            break;
        case 'customer':
            redirectTo('customer/dashboard.php');
            break;
        case 'vendor':
            redirectTo('vendor/dashboard.php');
            break;
    }
}

// Get wedding packages for display
try {
    $stmt = $pdo->query("SELECT * FROM wedding_packages WHERE status = 'active' ORDER BY price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}

// Get some vendor statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_vendors FROM vendors WHERE status = 'active'");
    $vendor_count = $stmt->fetch()['total_vendors'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
    $booking_count = $stmt->fetch()['total_bookings'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as satisfied_customers FROM bookings WHERE booking_status = 'completed'");
    $customer_count = $stmt->fetch()['satisfied_customers'];
} catch (PDOException $e) {
    $vendor_count = 0;
    $booking_count = 0;
    $customer_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Your Dream Wedding Awaits</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-heart"></i> Wedding Management
            </a>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#packages">Packages</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="btn btn-secondary">Login</a></li>
                <li><a href="register.php" class="btn">Register</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <h1>Your Perfect Wedding Starts Here</h1>
            <p>Professional wedding planning and management system to make your special day unforgettable. From booking to celebration, we handle everything with care and precision.</p>
            <div style="margin-top: 2rem;">
                <a href="register.php" class="btn" style="margin-right: 1rem;">Start Planning</a>
                <a href="#packages" class="btn btn-secondary">View Packages</a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section style="padding: 4rem 0; background-color: var(--secondary-color);">
        <div class="container">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $vendor_count; ?>+</span>
                    <span class="stat-label">Trusted Vendors</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $booking_count; ?>+</span>
                    <span class="stat-label">Weddings Planned</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $customer_count; ?>+</span>
                    <span class="stat-label">Happy Couples</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">5</span>
                    <span class="stat-label">Years Experience</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Wedding Packages Section -->
    <section id="packages" style="padding: 4rem 0;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; color: var(--primary-color); font-size: 2.5rem;">Wedding Packages</h2>
            <div class="grid grid-3">
                <?php foreach ($packages as $package): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                        <div style="font-size: 2rem; color: var(--primary-color); font-weight: bold;">
                            RM <?php echo number_format($package['price'], 2); ?>
                        </div>
                    </div>
                    <div>
                        <p style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($package['description']); ?></p>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Package Details:</strong>
                            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                <li>Duration: <?php echo $package['duration_hours']; ?> hours</li>
                                <li>Max Guests: <?php echo $package['max_guests']; ?> people</li>
                            </ul>
                        </div>

                        <?php 
                        $features = json_decode($package['features'], true);
                        if ($features): 
                        ?>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Features Included:</strong>
                            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                <?php foreach ($features as $feature): ?>
                                <li><i class="fas fa-check" style="color: var(--success-color); margin-right: 0.5rem;"></i><?php echo htmlspecialchars($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <a href="register.php" class="btn" style="width: 100%; text-align: center;">Choose This Package</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="about" style="padding: 4rem 0; background-color: var(--light-gray);">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; color: var(--primary-color); font-size: 2.5rem;">Why Choose Us?</h2>
            <div class="grid grid-3">
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-calendar-check" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">Easy Booking</h3>
                    <p style="text-align: center;">Simple and intuitive booking system that makes planning your wedding effortless. Book your date, select packages, and manage everything online.</p>
                </div>
                
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">Professional Vendors</h3>
                    <p style="text-align: center;">Network of verified and experienced vendors including photographers, caterers, decorators, and more to make your day special.</p>
                </div>
                
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-headset" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">24/7 Support</h3>
                    <p style="text-align: center;">Dedicated customer support team available round the clock to assist you with any queries or concerns during your wedding planning.</p>
                </div>
                
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">Real-time Tracking</h3>
                    <p style="text-align: center;">Track your wedding preparations in real-time. Monitor vendor progress, payment status, and timeline updates all in one place.</p>
                </div>
                
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-mobile-alt" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">Mobile Friendly</h3>
                    <p style="text-align: center;">Access your wedding plans anytime, anywhere with our responsive design that works perfectly on all devices.</p>
                </div>
                
                <div class="card">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 1rem;">Secure & Reliable</h3>
                    <p style="text-align: center;">Your data and payments are protected with industry-standard security measures. Plan your wedding with complete peace of mind.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section style="padding: 4rem 0;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; color: var(--primary-color); font-size: 2.5rem;">How It Works</h2>
            <div class="grid grid-4">
                <div style="text-align: center;">
                    <div style="background: var(--primary-color); color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; font-weight: bold;">1</div>
                    <h3 style="margin-bottom: 1rem;">Register</h3>
                    <p>Create your account and tell us about your dream wedding preferences and requirements.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-color); color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; font-weight: bold;">2</div>
                    <h3 style="margin-bottom: 1rem;">Choose Package</h3>
                    <p>Select from our carefully crafted wedding packages or customize one according to your needs.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-color); color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; font-weight: bold;">3</div>
                    <h3 style="margin-bottom: 1rem;">Book & Plan</h3>
                    <p>Book your date, select vendors, and plan every detail of your wedding through our platform.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-color); color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; font-weight: bold;">4</div>
                    <h3 style="margin-bottom: 1rem;">Celebrate</h3>
                    <p>Relax and enjoy your perfect wedding day while we coordinate everything behind the scenes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" style="padding: 4rem 0; background-color: var(--secondary-color);">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; color: var(--primary-color); font-size: 2.5rem;">Get In Touch</h2>
            <div class="grid grid-2">
                <div>
                    <h3 style="margin-bottom: 1.5rem;">Contact Information</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 1rem;"></i>
                        <strong>Address:</strong><br>
                        123 Wedding Street, Kuala Lumpur, Malaysia 50450
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <i class="fas fa-phone" style="color: var(--primary-color); margin-right: 1rem;"></i>
                        <strong>Phone:</strong><br>
                        +60 3-1234 5678
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 1rem;"></i>
                        <strong>Email:</strong><br>
                        info@weddingmanagement.com
                    </div>
                    <div>
                        <i class="fas fa-clock" style="color: var(--primary-color); margin-right: 1rem;"></i>
                        <strong>Business Hours:</strong><br>
                        Monday - Friday: 9:00 AM - 6:00 PM<br>
                        Saturday: 10:00 AM - 4:00 PM<br>
                        Sunday: Closed
                    </div>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 1.5rem;">Send us a Message</h3>
                    <form action="includes/contact.php" method="POST" class="validate-form">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p>Your trusted partner in creating unforgettable wedding memories.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
