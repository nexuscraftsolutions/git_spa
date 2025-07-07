<?php
require_once 'database.php';

// Security functions
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Enhanced Location detection functions
function detectUserLocation($ip = null) {
    if (!$ip) {
        $ip = getUserIP();
    }
    
    try {
        // Use ip-api.com for free location detection
        $url = "http://ip-api.com/json/{$ip}";
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (compatible; SpaApp/1.0)'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'success' => true,
                    'city' => $data['city'] ?? LOCATION_FALLBACK_CITY,
                    'region' => $data['regionName'] ?? '',
                    'country' => $data['country'] ?? 'India',
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null
                ];
            }
        }
    } catch (Exception $e) {
        // Fallback to default
    }
    
    return [
        'success' => false,
        'city' => LOCATION_FALLBACK_CITY,
        'region' => '',
        'country' => 'India'
    ];
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Pricing calculation functions
function calculateTherapistPrice($therapistId, $userLocation = 'Delhi', $bookingTime = null) {
    $therapist = getTherapistById($therapistId);
    if (!$therapist) {
        return ['success' => false, 'message' => 'Therapist not found'];
    }
    
    // Determine if user is in Delhi or nearby areas
    $isInCity = isInCityLocation($userLocation);
    
    // Get base price
    $basePrice = $isInCity ? $therapist['in_city_price'] : $therapist['out_city_price'];
    
    // Calculate night fee
    $nightFee = 0;
    $isNightBooking = false;
    
    if ($bookingTime && $therapist['night_fee_enabled']) {
        $isNightBooking = isNightTime($bookingTime);
        if ($isNightBooking) {
            $nightFee = 1500; // Fixed night fee
        }
    }
    
    $totalPrice = $basePrice + $nightFee;
    
    return [
        'success' => true,
        'base_price' => $basePrice,
        'night_fee' => $nightFee,
        'total_price' => $totalPrice,
        'is_in_city' => $isInCity,
        'is_night_booking' => $isNightBooking,
        'location_type' => $isInCity ? 'in_city' : 'out_city'
    ];
}

function isInCityLocation($location) {
    $inCityAreas = [
        'Delhi', 'New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi',
        'Central Delhi', 'Dwarka', 'Rohini', 'Janakpuri', 'Lajpat Nagar', 'Karol Bagh',
        'Connaught Place', 'Rajouri Garden', 'Pitampura', 'Vasant Kunj', 'Saket',
        'Greater Kailash', 'Nehru Place', 'Chandni Chowk', 'Punjabi Bagh'
    ];
    
    foreach ($inCityAreas as $area) {
        if (stripos($location, $area) !== false) {
            return true;
        }
    }
    
    return false;
}

function isNightTime($time) {
    // Convert time to 24-hour format for comparison
    $hour = (int)date('H', strtotime($time));
    
    // Night hours: 22:00 (10 PM) to 06:00 (6 AM)
    return ($hour >= 22 || $hour < 6);
}

// File upload functions
function uploadImage($file, $subfolder = '') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadDir = UPLOAD_PATH . ($subfolder ? $subfolder . '/' : '');
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'success' => true, 
            'filename' => $filename,
            'path' => ($subfolder ? $subfolder . '/' : '') . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}

function deleteImage($imagePath) {
    $fullPath = UPLOAD_PATH . $imagePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

// User authentication functions
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && (isset($_SESSION['user_email']) || isset($_SESSION['user_phone']));
}

function isAdminUser() {
    return isUserLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function registerUser($name, $email, $phone, $city, $password) {
    $db = getDB();
    
    // Validate that at least email or phone is provided
    if (empty($email) && empty($phone)) {
        return ['success' => false, 'message' => 'Either email or phone number is required'];
    }
    
    // Check if email or phone already exists
    $checkQuery = "SELECT id FROM users WHERE ";
    $checkParams = [];
    
    if (!empty($email) && !empty($phone)) {
        $checkQuery .= "email = ? OR phone = ?";
        $checkParams = [$email, $phone];
    } elseif (!empty($email)) {
        $checkQuery .= "email = ?";
        $checkParams = [$email];
    } else {
        $checkQuery .= "phone = ?";
        $checkParams = [$phone];
    }
    
    $stmt = $db->prepare($checkQuery);
    $stmt->execute($checkParams);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email or phone number already registered'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO users (name, email, phone, city, password, role, status) 
            VALUES (?, ?, ?, ?, ?, 'user', 'active')
        ");
        
        if ($stmt->execute([$name, $email ?: null, $phone ?: null, $city, $hashedPassword])) {
            return ['success' => true, 'message' => 'Account created successfully'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function loginUser($identifier, $password) {
    $db = getDB();
    
    // Check if identifier is email or phone
    $isEmail = validateEmail($identifier);
    $field = $isEmail ? 'email' : 'phone';
    
    $stmt = $db->prepare("SELECT id, name, email, phone, city, password, role, status FROM users WHERE $field = ? LIMIT 1");
    $stmt->execute([$identifier]);
    
    if ($user = $stmt->fetch()) {
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive'];
        }
        
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Auto-detect location if not set
            if (empty($user['city'])) {
                $location = detectUserLocation();
                $user['city'] = $location['city'];
                
                // Update user's city in database
                $updateStmt = $db->prepare("UPDATE users SET city = ? WHERE id = ?");
                $updateStmt->execute([$user['city'], $user['id']]);
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_city'] = $user['city'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'user' => $user];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid credentials'];
}

function logoutUser() {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], 
          $_SESSION['user_phone'], $_SESSION['user_city'], $_SESSION['user_role']);
    session_regenerate_id(true);
}

// Admin authentication functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function loginAdmin($username, $password) {
    $db = getDB();
    
    // First check if admin table exists and has data
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins");
        $stmt->execute();
        $result = $stmt->fetch();
        
        // If no admins exist, create default admin
        if ($result['count'] == 0) {
            createDefaultAdmin();
        }
    } catch (Exception $e) {
        // Table might not exist, try to create it
        createAdminTable();
        createDefaultAdmin();
    }
    
    $stmt = $db->prepare("SELECT id, username, email, password FROM admins WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $username]);
    
    if ($admin = $stmt->fetch()) {
        // Check if password is hashed or plain text (for backward compatibility)
        if (password_verify($password, $admin['password']) || $admin['password'] === $password) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Update password to hashed version if it was plain text
            if ($admin['password'] === $password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $admin['id']]);
            }
            
            return true;
        }
    }
    return false;
}

function createAdminTable() {
    $db = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->prepare($sql)->execute();
}

function createDefaultAdmin() {
    $db = getDB();
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT IGNORE INTO admins (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute(['admin', 'admin@spa.com', $hashedPassword]);
}

function logoutAdmin() {
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
    session_regenerate_id(true);
}

// Therapist functions
function getAllTherapists($status = 'active') {
    try {
        $db = getDB();
        if ($status === 'all') {
            $stmt = $db->prepare("SELECT * FROM therapists ORDER BY created_at DESC");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT * FROM therapists WHERE status = ? ORDER BY created_at DESC");
            $stmt->execute([$status]);
        }
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getAllTherapists: " . $e->getMessage());
        return [];
    }
}

function getTherapistById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM therapists WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in getTherapistById: " . $e->getMessage());
        return false;
    }
}

function getTherapistImages($therapistId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM therapist_images WHERE therapist_id = ? ORDER BY is_main DESC, id ASC");
        $stmt->execute([$therapistId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getTherapistImages: " . $e->getMessage());
        return [];
    }
}

function getTherapistServices($therapistId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT s.* FROM services s 
            JOIN therapist_services ts ON s.id = ts.service_id 
            WHERE ts.therapist_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$therapistId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getTherapistServices: " . $e->getMessage());
        return [];
    }
}

function getAllServices() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM services ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getAllServices: " . $e->getMessage());
        return [];
    }
}

// Booking functions
function createBooking($data) {
    $db = getDB();
    
    try {
        // Get therapist data for pricing calculation
        $therapist = getTherapistById($data['therapist_id']);
        if (!$therapist) {
            return ['success' => false, 'message' => 'Therapist not found'];
        }
        
        // Calculate pricing based on actual user selection
        $pricingType = $data['pricing_type'] ?? 'in_city';
        $basePrice = ($pricingType === 'in_city') ? 
            $therapist['in_city_price'] : 
            $therapist['out_city_price'];
        
        // Calculate night fee
        $isNightBooking = isNightTime($data['booking_time']);
        $nightFee = ($isNightBooking && $therapist['night_fee_enabled']) ? 1500 : 0;
        
        // Verify the total amount matches our calculation
        $calculatedTotal = $basePrice + $nightFee;
        if (abs($calculatedTotal - $data['total_amount']) > 0.01) {
            // Log the discrepancy but use the calculated amount for security
            error_log("Price mismatch: Expected {$calculatedTotal}, got {$data['total_amount']}");
            $data['total_amount'] = $calculatedTotal;
        }
        
        $stmt = $db->prepare("
            INSERT INTO bookings (
                therapist_id, full_name, email, phone, booking_date, booking_time, 
                message, total_amount, base_amount, night_fee, user_location,
                is_night_booking, pricing_type, status
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $result = $stmt->execute([
            $data['therapist_id'],
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['booking_date'],
            $data['booking_time'],
            $data['message'],
            $calculatedTotal,
            $basePrice,
            $nightFee,
            $data['user_location'] ?? 'Delhi',
            $isNightBooking,
            $pricingType
        ]);
        
        if ($result) {
            $bookingId = $db->lastInsertId();
            
            // Create lead entry
            createLead([
                'type' => 'booking',
                'therapist_id' => $data['therapist_id'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'message' => $data['message'] ?? '',
                'status' => 'new'
            ]);
            
            return [
                'success' => true, 
                'booking_id' => $bookingId,
                'pricing' => [
                    'base_price' => $basePrice,
                    'night_fee' => $nightFee,
                    'total_price' => $calculatedTotal,
                    'pricing_type' => $pricingType
                ]
            ];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
    
    return ['success' => false, 'message' => 'Booking failed'];
}

function getAllBookings() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT b.*, t.name as therapist_name 
            FROM bookings b 
            LEFT JOIN therapists t ON b.therapist_id = t.id 
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getAllBookings: " . $e->getMessage());
        return [];
    }
}

// Lead management functions
function createLead($data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO leads (type, therapist_id, full_name, email, phone, message, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['type'],
            $data['therapist_id'] ?? null,
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['message'],
            $data['status'] ?? 'new'
        ]);
    } catch (Exception $e) {
        error_log("Error in createLead: " . $e->getMessage());
        return false;
    }
}

function createInquiry($data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO leads (type, therapist_id, full_name, email, phone, message, status) 
            VALUES ('inquiry', ?, ?, ?, ?, ?, 'new')
        ");
        
        $result = $stmt->execute([
            $data['therapist_id'] ?? null,
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['message']
        ]);
        
        if ($result) {
            return ['success' => true, 'lead_id' => $db->lastInsertId()];
        }
    } catch (Exception $e) {
        error_log("Error in createInquiry: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
    
    return ['success' => false, 'message' => 'Inquiry submission failed'];
}

// Razorpay functions
function createRazorpayOrder($amount, $currency = 'INR') {
    if (!RAZORPAY_ENABLED) {
        return ['success' => false, 'message' => 'Payment gateway is disabled'];
    }
    
    $url = 'https://api.razorpay.com/v1/orders';
    $data = [
        'amount' => $amount * 100, // Amount in paise
        'currency' => $currency,
        'receipt' => 'spa_' . time(),
        'payment_capture' => 1
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $order = json_decode($response, true);
        return ['success' => true, 'order' => $order];
    }
    
    return ['success' => false, 'message' => 'Failed to create payment order'];
}

function verifyRazorpayPayment($paymentId, $orderId, $signature) {
    if (!RAZORPAY_ENABLED) {
        return false;
    }
    
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $signature);
}

// Email function (basic implementation)
function sendBookingConfirmation($bookingId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT b.*, t.name as therapist_name 
            FROM bookings b 
            LEFT JOIN therapists t ON b.therapist_id = t.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $subject = "Spa Booking Confirmation - Booking #" . $bookingId;
            $message = "Dear {$booking['full_name']},\n\n";
            $message .= "Your spa appointment has been confirmed!\n\n";
            $message .= "Details:\n";
            $message .= "Therapist: {$booking['therapist_name']}\n";
            $message .= "Date: {$booking['booking_date']}\n";
            $message .= "Time: {$booking['booking_time']}\n";
            $message .= "Location: {$booking['user_location']}\n";
            $message .= "Base Amount: ₹{$booking['base_amount']}\n";
            if ($booking['night_fee'] > 0) {
                $message .= "Night Fee: ₹{$booking['night_fee']}\n";
            }
            $message .= "Total Amount: ₹{$booking['total_amount']}\n\n";
            $message .= "Thank you for choosing our spa!\n";
            
            $headers = "From: noreply@spa.com\r\n";
            $headers .= "Reply-To: info@spa.com\r\n";
            
            return mail($booking['email'], $subject, $message, $headers);
        }
    } catch (Exception $e) {
        error_log("Error in sendBookingConfirmation: " . $e->getMessage());
    }
    
    return false;
}

// Utility functions
function formatPrice($price) {
    return '₹' . number_format($price, 0);
}

function timeAgo($datetime) {
    $utc = new DateTimeZone('UTC');
    $ist = new DateTimeZone('Asia/Kolkata');

    // Convert the MySQL UTC timestamp to a DateTime object in UTC
    $time = new DateTime($datetime, $utc);
    $time->setTimezone($ist); // Convert to IST

    $now = new DateTime('now', $ist); // Current time in IST
    $diff = $now->diff($time);

    $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    foreach ($units as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($units[$k]);
        }
    }

    return $units ? reset($units) . ' ago' : 'just now';
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    try {
        $db = getDB();
        
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Create tables if they don't exist
        $tables = [
            "CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NULL,
                phone VARCHAR(20) NULL,
                city VARCHAR(100),
                password VARCHAR(255) NOT NULL,
                role ENUM('user', 'admin') DEFAULT 'user',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                icon_type ENUM('bootstrap', 'upload') DEFAULT 'bootstrap',
                icon_value VARCHAR(100),
                icon_image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS therapists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                price_per_session DECIMAL(10,2) NOT NULL DEFAULT 0,
                in_city_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                out_city_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                night_fee_enabled BOOLEAN DEFAULT TRUE,
                height VARCHAR(20),
                weight VARCHAR(20),
                description TEXT,
                availability_slots TEXT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                main_image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS therapist_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                therapist_id INT NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                is_main BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS therapist_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                therapist_id INT NOT NULL,
                service_id INT NOT NULL,
                FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
                UNIQUE KEY unique_therapist_service (therapist_id, service_id)
            )",
            
            "CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                therapist_id INT NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                booking_date DATE NOT NULL,
                booking_time TIME NOT NULL,
                message TEXT,
                total_amount DECIMAL(10,2) NOT NULL,
                base_amount DECIMAL(10,2) DEFAULT 0,
                night_fee DECIMAL(10,2) DEFAULT 0,
                user_location VARCHAR(100) DEFAULT 'Delhi',
                is_night_booking BOOLEAN DEFAULT FALSE,
                pricing_type ENUM('in_city', 'out_city') DEFAULT 'in_city',
                payment_id VARCHAR(255),
                payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS leads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type ENUM('inquiry', 'booking', 'whatsapp', 'contact') NOT NULL,
                therapist_id INT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                message TEXT,
                status ENUM('new', 'follow_up', 'converted', 'closed') DEFAULT 'new',
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS contact_inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                subject VARCHAR(200),
                message TEXT NOT NULL,
                status ENUM('new', 'replied', 'closed') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $db->prepare($sql)->execute();
            } catch (Exception $e) {
                error_log("Error creating table: " . $e->getMessage());
                // Continue if table already exists
            }
        }
        
        // Insert default data
        insertDefaultData();
    } catch (Exception $e) {
        error_log("Error in initializeDatabase: " . $e->getMessage());
    }
}

function insertDefaultData() {
    try {
        $db = getDB();
        
        // Insert default admin
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM admins");
            $stmt->execute();
            if ($stmt->fetch()['count'] == 0) {
                createDefaultAdmin();
            }
        } catch (Exception $e) {
            error_log("Error checking/creating admin: " . $e->getMessage());
        }
        
        // Insert default services
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM services");
            $stmt->execute();
            if ($stmt->fetch()['count'] == 0) {
                $services = [
                    ['Swedish Massage', 'Relaxing full-body massage with gentle pressure', 'bootstrap', 'bi-heart-pulse', null],
                    ['Deep Tissue Massage', 'Therapeutic massage targeting deep muscle layers', 'bootstrap', 'bi-activity', null],
                    ['Hot Stone Therapy', 'Heated stones placed on body for deep relaxation', 'bootstrap', 'bi-fire', null],
                    ['Aromatherapy', 'Essential oils massage for mind and body wellness', 'bootstrap', 'bi-flower1', null],
                    ['Reflexology', 'Pressure point massage focusing on feet and hands', 'bootstrap', 'bi-hand-thumbs-up', null],
                    ['Thai Massage', 'Traditional stretching and pressure point therapy', 'bootstrap', 'bi-person-arms-up', null]
                ];
                
                $stmt = $db->prepare("INSERT INTO services (name, description, icon_type, icon_value, icon_image) VALUES (?, ?, ?, ?, ?)");
                foreach ($services as $service) {
                    $stmt->execute($service);
                }
            }
        } catch (Exception $e) {
            error_log("Error creating services: " . $e->getMessage());
        }
        
        // Create default users for testing
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            if ($stmt->fetch()['count'] == 0) {
                $users = [
                    ['Admin User', 'admin@spa.com', '9560656913', 'Delhi', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
                    ['Test User', 'user@spa.com', '9560656913', 'Mumbai', password_hash('user123', PASSWORD_DEFAULT), 'user']
                ];
                
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, city, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($users as $user) {
                    $stmt->execute($user);
                }
            }
        } catch (Exception $e) {
            error_log("Error creating users: " . $e->getMessage());
        }
        
        // Create sample therapists with pricing
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM therapists");
            $stmt->execute();
            if ($stmt->fetch()['count'] == 0) {
                $therapists = [
                    ['Raj Kumar', 2500, 2500, 3000, 1, '5\'8"', '70kg', 'Expert in Swedish and deep tissue massage with 8+ years experience.', 'Mon-Fri: 9 AM - 6 PM, Sat: 10 AM - 4 PM'],
                    ['Amit Singh', 3000, 3000, 3500, 1, '5\'10"', '75kg', 'Specialized in aromatherapy and hot stone treatments.', 'Mon-Sun: 10 AM - 8 PM'],
                    ['Vikash Sharma', 2800, 2800, 3300, 1, '5\'9"', '72kg', 'Traditional Thai massage and reflexology specialist.', 'Tue-Sun: 11 AM - 7 PM']
                ];
                
                $stmt = $db->prepare("INSERT INTO therapists (name, price_per_session, in_city_price, out_city_price, night_fee_enabled, height, weight, description, availability_slots) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($therapists as $therapist) {
                    $stmt->execute($therapist);
                }
            }
        } catch (Exception $e) {
            error_log("Error creating therapists: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Error in insertDefaultData: " . $e->getMessage());
    }
}
?>