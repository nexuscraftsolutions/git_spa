<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Hammam Spa</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <!-- Pricing System JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/pricing-system.js"></script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WNRTPJJJ');</script>
    <!-- End Google Tag Manager -->
    
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNRTPJJJ"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <?php if (!isset($hideNavbar) || !$hideNavbar): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>">
                <img src="<?php echo SITE_URL; ?>/uploads/website/logo.png" width="150px">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <!-- Location Toggle Buttons in Navbar -->
                    <li class="nav-item dropdown d-none d-lg-block">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-geo-alt me-1"></i>Location
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item location-toggle-btn active" data-type="in_city">
                                    <i class="bi bi-building me-2"></i>In-City (Delhi & nearby)
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item location-toggle-btn" data-type="out_city">
                                    <i class="bi bi-geo-alt me-2"></i>Out-City (Outside Delhi)
                                </button>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/models.php">Therapists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/therapies.php">Therapies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if (isUserLoggedIn()): ?>
                        <div class="user-info d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <div class="text-start">
                                        <div class="fw-semibold">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></div>
                                        <?php if (!empty($_SESSION['user_city'])): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($_SESSION['user_city']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person me-2"></i>Profile
                                    </a></li>
                                    <li><a class="dropdown-item" href="my-bookings.php">
                                        <i class="bi bi-calendar-check me-2"></i>My Bookings
                                    </a></li>
                                    <?php if (isAdminUser()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="admin/index.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Admin Panel
                                        </a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Desktop Login/Signup -->
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link btn btn-primary text-white ms-2 px-3" href="<?php echo SITE_URL; ?>/signup.php">
                                <i class="bi bi-person-plus me-1"></i>Sign Up
                            </a>
                        </li>
                        
                        <!-- Mobile User Icon -->
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="#" onclick="openMobileLoginModal()">
                                <i class="bi bi-person-circle fs-4"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Add padding to body to account for fixed navbar -->
    <div style="padding-top: 80px;"></div>
    <?php endif; ?>

    <!-- Mobile Login Modal -->
    <?php if (!isUserLoggedIn()): ?>
    <div class="modal fade" id="mobileLoginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Account Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-person-circle display-1 text-primary"></i>
                        <h4 class="mt-3">Welcome to Serenity Spa</h4>
                        <p class="text-muted">Sign in to book appointments and manage your profile</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </a>
                        <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            Your data is secure with us
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openMobileLoginModal() {
            new bootstrap.Modal(document.getElementById('mobileLoginModal')).show();
        }
    </script>
    <?php endif; ?>