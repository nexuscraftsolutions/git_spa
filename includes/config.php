<?php
// Database configuration - UPDATE THESE WITH YOUR MYSQL DETAILS
define('DB_HOST', 'localhost');
define('DB_USER', 'u445351904_spa_karan'); // Change to your MySQL username
define('DB_PASS', 'pdQpmgD[9L'); // Change to your MySQL password
define('DB_NAME', 'u445351904_spa_karan');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Application configuration
define('SITE_URL', 'https://boyztown.in');
define('ADMIN_URL', SITE_URL . '/admin/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Image upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// Razorpay configuration
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_KEY_ID'); // Replace with your Razorpay Key ID
define('RAZORPAY_KEY_SECRET', 'YOUR_KEY_SECRET'); // Replace with your Razorpay Key Secret
define('RAZORPAY_ENABLED', false); // Set to false to disable payments

// Email configuration (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@spa.com');
define('SMTP_PASS', '');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'spa_csrf_token');

// Location API settings
define('LOCATION_API_URL', 'http://ip-api.com/json/');
define('LOCATION_FALLBACK_CITY', 'Delhi');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Test database connection
try {
    $test_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    // Connection successful
    unset($test_pdo);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>