<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'Our Therapists';
$therapists = getAllTherapists();

// Get user's location for pricing
$userLocation = $_SESSION['user_city'] ?? detectUserLocation()['city'];
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section-inner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4">Our Expert Therapists</h1>
                <p class="lead mb-4">Meet our professional team of certified therapists dedicated to your wellness and relaxation journey.</p>
                
                <!-- Pricing Toggle Buttons -->
                <div class="pricing-toggle mb-4">
                    <div class="btn-group" role="group" aria-label="Pricing options">
                        <input type="radio" class="btn-check" name="pricingType" id="inCityPricing" value="in_city" checked>
                        <label class="btn btn-outline-light" for="inCityPricing">
                            <i class="bi bi-building me-2"></i>In-City Pricing
                        </label>
                        
                        <input type="radio" class="btn-check" name="pricingType" id="outCityPricing" value="out_city">
                        <label class="btn btn-outline-light" for="outCityPricing">
                            <i class="bi bi-geo-alt me-2"></i>Out-City Pricing
                        </label>
                    </div>
                    <div class="mt-2">
                        <small class="text-light opacity-75">
                            <i class="bi bi-info-circle me-1"></i>
                            Your location: <strong><?php echo htmlspecialchars($userLocation); ?></strong>
                            <?php if (function_exists('isInCityLocation') && isInCityLocation($userLocation)): ?>
                                (In-City rates apply)
                            <?php else: ?>
                                (Out-City rates apply)
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($therapists); ?>+</span>
                        <span class="stat-label">Expert Therapists</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Therapists Grid -->
<section class="py-5 bg-white">
    <div class="container">
        <?php if (empty($therapists)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-exclamation display-1 text-muted"></i>
                <h4 class="text-muted mt-3">No therapists available</h4>
                <p class="text-muted">Please check back later for available therapists.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($therapists as $therapist): 
                    $images = getTherapistImages($therapist['id']);
                    $therapistServices = getTherapistServices($therapist['id']);
                    
                    // Calculate pricing based on user location - with safety check
                    $isInCity = function_exists('isInCityLocation') ? isInCityLocation($userLocation) : true;
                    $displayPrice = $isInCity ? ($therapist['in_city_price'] ?? $therapist['price_per_session']) : ($therapist['out_city_price'] ?? $therapist['price_per_session']);
                ?>
                    <div class="col-lg-4 col-md-6">
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
                                          data-in-city="<?php echo $therapist['in_city_price'] ?? $therapist['price_per_session']; ?>"
                                          data-out-city="<?php echo $therapist['out_city_price'] ?? $therapist['price_per_session']; ?>">
                                        <?php echo formatPrice($displayPrice); ?>
                                    </span>/session
                                    <?php if (($therapist['night_fee_enabled'] ?? false)): ?>
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
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/booking_modal.php'; ?>

<?php 
$extraScripts = '<script>
    // Pricing toggle functionality
    document.querySelectorAll("input[name=\"pricingType\"]").forEach(radio => {
        radio.addEventListener("change", function() {
            const priceType = this.value;
            
            // Update all dynamic prices
            document.querySelectorAll(".dynamic-price").forEach(priceElement => {
                const inCityPrice = parseFloat(priceElement.dataset.inCity);
                const outCityPrice = parseFloat(priceElement.dataset.outCity);
                const price = priceType === "in_city" ? inCityPrice : outCityPrice;
                
                priceElement.textContent = "₹" + new Intl.NumberFormat("en-IN").format(price);
            });
            
            // Store preference in localStorage
            localStorage.setItem("preferredPricingType", priceType);
        });
    });
    
    // Load saved pricing preference
    document.addEventListener("DOMContentLoaded", function() {
        const savedPricingType = localStorage.getItem("preferredPricingType");
        if (savedPricingType) {
            const radio = document.getElementById(savedPricingType === "in_city" ? "inCityPricing" : "outCityPricing");
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event("change"));
            }
        }
    });
    
    // Set user location for pricing calculations
    window.userLocation = "<?php echo htmlspecialchars($userLocation); ?>";
    window.isInCity = <?php echo (function_exists('isInCityLocation') && isInCityLocation($userLocation)) ? 'true' : 'false'; ?>;
</script>';

include 'includes/footer.php'; 
?>