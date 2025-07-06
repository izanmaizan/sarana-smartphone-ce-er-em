<?php
// ajax/clear_cart.php - NEW FILE
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

function sendResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

if (!isLoggedIn() || isAdmin()) {
    sendResponse(false, 'Unauthorized access');
}

try {
    // Get cart count before clearing
    $items_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    
    // Clear cart
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    
    sendResponse(true, "Keranjang berhasil dikosongkan", [
        'items_removed' => $items_count,
        'cart_count' => 0,
        'unique_items' => 0
    ]);
    
} catch (Exception $e) {
    sendResponse(false, 'System error occurred');
}
?>