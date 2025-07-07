<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize database and default data
try {
    initializeDatabase();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}

$pageTitle = 'Home';

// Get data with error handling
try {
    $therapists = getAllTherapists();
    $services = getAllServices();
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $therapists = [];
    $services = [];
}

// Get user's location for pricing
$userLocation = $_SESSION['user_city'] ?? detectUserLocation()['city'];
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="display-4 fw-bold mb-4 fade-in">Welcome to Hammam Spa</h1>
                <p class="lead mb-4 fade-in">Experience ultimate relaxation with our professional therapists and premium spa services. Rejuvenate your mind, body, and soul in our tranquil sanctuary.</p>
                
                <!-- Location Toggle Buttons in Hero -->
                <div class="location-toggle-hero mb-4 fade-in">
                    <div class="btn-group" role="group" aria-label="Location pricing">
                        <button class="btn btn-outline-light location-toggle-btn active" data-type="in_city">
                            <i class="bi bi-building me-2"></i>In-City Pricing
                        </button>
                        <button class="btn btn-outline-light location-toggle-btn" data-type="out_city">
                            <i class="bi bi-geo-alt me-2"></i>Out-City Pricing
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-white-50">
                            <i class="bi bi-info-circle me-1"></i>
                            Switch between In-City (Delhi & nearby) and Out-City pricing
                        </small>
                    </div>
                </div>
                
                <div class="d-flex gap-3 fade-in">
                    <a href="models.php" class="btn btn-light btn-lg">
                        <i class="bi bi-people me-2"></i>View Therapists
                    </a>
                    <a href="services.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-spa me-2"></i>Our Services
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center fade-in">
                    <img src="https://images.pexels.com/photos/3757942/pexels-photo-3757942.jpeg?auto=compress&cs=tinysrgb&w=600" 
                         class="img-fluid rounded-xl shadow-lg" alt="Spa Experience" style="max-height: 500px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card scale-in">
                    <span class="stats-number"><?php echo count($therapists); ?>+</span>
                    <span class="stats-label">Expert Therapists</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card scale-in">
                    <span class="stats-number">500+</span>
                    <span class="stats-label">Happy Clients</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card scale-in">
                    <span class="stats-number"><?php echo count($services); ?>+</span>
                    <span class="stats-label">Spa Services</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card scale-in">
                    <span class="stats-number">15+</span>
                    <span class="stats-label">Years Experience</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="services-section">
    <div class="container">
        <h2 class="section-title display-5 fw-bold">Our Premium Services</h2>
        <div class="row g-4">
            <?php 
            $serviceIcons = [
                'Swedish Massage' => 'bi-heart-pulse',
                'Deep Tissue Massage' => 'bi-activity',
                'Hot Stone Therapy' => 'bi-fire',
                'Aromatherapy' => 'bi-flower1',
                'Reflexology' => 'bi-hand-thumbs-up',
                'Thai Massage' => 'bi-person-arms-up'
            ];
            
            foreach (array_slice($services, 0, 6) as $service): 
                $icon = $serviceIcons[$service['name']] ?? 'bi-spa';
                
                // Use service icon if available
                if ($service['icon_type'] === 'bootstrap' && $service['icon_value']) {
                    $icon = $service['icon_value'];
                }
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card slide-up">
                        <div class="service-icon">
                            <?php if ($service['icon_type'] === 'upload' && $service['icon_image']): ?>
                                <img src="<?php echo UPLOAD_URL . $service['icon_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>" 
                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="bi <?php echo $icon; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($service['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                        <a href="services.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-right me-1"></i>Learn More
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="services.php" class="btn btn-primary btn-lg">
                <i class="bi bi-grid me-2"></i>View All Services
            </a>
        </div>
    </div>
</section>

<!-- Featured Therapists Section -->
<section id="therapists" class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title display-5 fw-bold">Meet Our Expert Therapists</h2>
        
        <?php if (empty($therapists)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-exclamation display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No therapists available at the moment</h4>
                <p class="text-muted">Please check back later for available therapists.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach (array_slice($therapists, 0, 3) as $therapist): 
                    $images = getTherapistImages($therapist['id']);
                    $therapistServices = getTherapistServices($therapist['id']);
                    
                    // Calculate pricing based on user location
                    $isInCity = isInCityLocation($userLocation);
                    $displayPrice = $isInCity ? $therapist['in_city_price'] : $therapist['out_city_price'];
                ?>
                    <div class="col-lg-4">
                        <div class="therapist-card-modern" data-therapist-id="<?php echo $therapist['id']; ?>">
                            <!-- Image Slider -->
                            <div class="therapist-slider" id="slider-<?php echo $therapist['id']; ?>">
                                <div class="slider-container">
                                    <?php if (!empty($images)): ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                                <img src="<?php echo UPLOAD_URL . $image['image_path']; ?>" 
                                                     alt="<?php echo htmlspecialchars($therapist['name']); ?>" 
                                                     class="therapist-image">
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($images) > 1): ?>
                                            <div class="slider-nav">
                                                <button class="slider-btn prev" onclick="changeSlide(<?php echo $therapist['id']; ?>, -1)">
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <button class="slider-btn next" onclick="changeSlide(<?php echo $therapist['id']; ?>, 1)">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>
                                            
                                            <div class="slider-dots">
                                                <?php foreach ($images as $index => $image): ?>
                                                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                          onclick="currentSlide(<?php echo $therapist['id']; ?>, <?php echo $index + 1; ?>)"></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="slide active">
                                            <img src="https://images.pexels.com/photos/3757942/pexels-photo-3757942.jpeg?auto=compress&cs=tinysrgb&w=400" 
                                                 alt="<?php echo htmlspecialchars($therapist['name']); ?>" 
                                                 class="therapist-image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Card Content -->
                            <div class="card-content">
                                <h5 class="therapist-name"><?php echo htmlspecialchars($therapist['name']); ?></h5>
                                
                                <div class="price-display">
                                    <span class="dynamic-price" 
                                          data-in-city="<?php echo $therapist['in_city_price']; ?>"
                                          data-out-city="<?php echo $therapist['out_city_price']; ?>">
                                        <?php echo formatPrice($displayPrice); ?>
                                    </span>/session
                                    <?php if ($therapist['night_fee_enabled']): ?>
                                        <br><small class="text-muted">+₹1500 night fee (10 PM - 6 AM)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="services-tags">
                                    <?php foreach (array_slice($therapistServices, 0, 3) as $service): ?>
                                        <span class="service-tag"><?php echo htmlspecialchars($service['name']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($therapistServices) > 3): ?>
                                        <span class="service-tag">+<?php echo count($therapistServices) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="therapist-description">
                                    <?php echo htmlspecialchars(substr($therapist['description'] ?? 'Professional therapist specializing in wellness and relaxation treatments.', 0, 100)); ?>
                                    <?php if (strlen($therapist['description'] ?? '') > 100) echo '...'; ?>
                                </p>
                                
                                <div class="card-actions">
                                    <a href="therapist-details.php?id=<?php echo $therapist['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-eye me-2"></i>View Details
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="openBookingModal(<?php echo $therapist['id']; ?>)">
                                        <i class="bi bi-calendar-check me-2"></i>Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="models.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-people me-2"></i>View All Therapists
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 services-section">
    <div class="container">
        <h2 class="section-title display-5 fw-bold">Get In Touch</h2>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="contact-card scale-in">
                    <div class="contact-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h5 class="fw-bold">Visit Our Spa</h5>
                    <p class="text-muted"> A-35 Block A2, Rajouri Garden New Delhi, 110027</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="contact-card scale-in">
                    <div class="contact-icon">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <h5 class="fw-bold">Call Us</h5>
                    <p class="text-muted">+91 9560656913<br>Available 9 AM - 8 PM<br>7 Days a Week</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="contact-card scale-in">
                    <div class="contact-icon">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <h5 class="fw-bold">Email Us</h5>
                    <p class="text-muted"> info@hammammensspa.com<br>We'll respond within 24 hours<br>Professional support</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="contact.php" class="btn btn-primary btn-lg">
                <i class="bi bi-envelope me-2"></i>Contact Us
            </a>
        </div>
    </div>
</section>

<?php include 'includes/booking_modal.php'; ?>

<?php include 'includes/footer.php'; ?>