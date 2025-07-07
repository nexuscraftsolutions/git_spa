<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!RAZORPAY_ENABLED) {
    echo json_encode(['success' => false, 'message' => 'Payment gateway is disabled']);
    exit;
}

// Validate payment details
$paymentId = sanitizeInput($_POST['payment_id'] ?? '');
$orderId = sanitizeInput($_POST['order_id'] ?? '');
$signature = sanitizeInput($_POST['signature'] ?? '');

if (empty($paymentId) || empty($orderId) || empty($signature)) {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    exit;
}

// Verify payment signature
if (!verifyRazorpayPayment($paymentId, $orderId, $signature)) {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    exit;
}

// Validate booking details
$required_fields = ['therapist_id', 'full_name', 'email', 'phone', 'booking_date', 'booking_time', 'total_amount'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitize and validate inputs
$therapist_id = (int)$_POST['therapist_id'];
$full_name = sanitizeInput($_POST['full_name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$booking_date = sanitizeInput($_POST['booking_date']);
$booking_time = sanitizeInput($_POST['booking_time']);
$message = sanitizeInput($_POST['message'] ?? '');
$total_amount = (float)$_POST['total_amount'];
$pricing_type = sanitizeInput($_POST['pricing_type'] ?? 'in_city');
$user_location = sanitizeInput($_POST['user_location'] ?? 'Based on pricing selection');

// Validate email
if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate phone
if (!validatePhone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Validate date
$today = date('Y-m-d');
if ($booking_date < $today) {
    echo json_encode(['success' => false, 'message' => 'Booking date cannot be in the past']);
    exit;
}

// Verify therapist exists
$therapist = getTherapistById($therapist_id);
if (!$therapist) {
    echo json_encode(['success' => false, 'message' => 'Selected therapist not found']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get therapist data for accurate pricing
    $therapist = getTherapistById($therapist_id);
    if (!$therapist) {
        throw new Exception('Therapist not found');
    }
    
    // Calculate and verify pricing
    $basePrice = ($pricing_type === 'in_city') ? 
        $therapist['in_city_price'] : 
        $therapist['out_city_price'];
    
    $isNightBooking = isNightTime($booking_time);
    $nightFee = ($isNightBooking && $therapist['night_fee_enabled']) ? 1500 : 0;
    $calculatedTotal = $basePrice + $nightFee;
    
    // Use calculated amount for security
    $total_amount = $calculatedTotal;
    
    // Create booking with payment details and accurate pricing
    $stmt = $db->prepare("
        INSERT INTO bookings (
            therapist_id, full_name, email, phone, booking_date, booking_time, 
            message, total_amount, base_amount, night_fee, user_location,
            is_night_booking, pricing_type, payment_id, payment_status, status
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', 'confirmed')
    ");
    
    $result = $stmt->execute([
        $therapist_id,
        $full_name,
        $email,
        $phone,
        $booking_date,
        $booking_time,
        $message,
        $total_amount,
        $basePrice,
        $nightFee,
        $user_location,
        $isNightBooking,
        $pricing_type,
        $paymentId
    ]);
    
    if ($result) {
        $bookingId = $db->lastInsertId();
        
        // Create lead entry
        createLead([
            'type' => 'booking',
            'therapist_id' => $therapist_id,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'status' => 'converted'
        ]);
        
        $db->commit();
        
        // Send confirmation email
        sendBookingConfirmation($bookingId);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment successful and booking confirmed!',
            'booking_id' => $bookingId
        ]);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Booking creation failed']);
    }
    
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your booking']);
}
?>