<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid therapist ID']);
    exit;
}

$therapistId = (int)$_GET['id'];
$userLocation = $_GET['location'] ?? ($_SESSION['user_city'] ?? 'Delhi');
$bookingTime = $_GET['time'] ?? null;

try {
    $pricingResult = calculateTherapistPrice($therapistId, $userLocation, $bookingTime);
    
    if ($pricingResult['success']) {
        echo json_encode([
            'success' => true,
            'pricing' => $pricingResult
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $pricingResult['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>