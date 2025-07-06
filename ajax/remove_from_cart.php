<?php
// ajax/remove_from_cart.php - NEW FILE
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

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if ($product_id === false || $product_id <= 0) {
    sendResponse(false, 'Invalid product ID');
}

if (!isset($_SESSION['cart'][$product_id])) {
    sendResponse(false, 'Product not in cart');
}

$conn = getConnection();

try {
    // Get product name
    $stmt = mysqli_prepare($conn, "SELECT name FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $product_name = 'Product';
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $product_name = $row['name'];
    }
    
    mysqli_stmt_close($stmt);
    
    // Remove from cart
    unset($_SESSION['cart'][$product_id]);
    
    // Calculate new totals
    $cart_count = array_sum($_SESSION['cart']);
    $unique_items = count($_SESSION['cart']);
    
    sendResponse(true, "'{$product_name}' dihapus dari keranjang", [
        'product_id' => $product_id,
        'cart_count' => $cart_count,
        'unique_items' => $unique_items
    ]);
    
} catch (Exception $e) {
    sendResponse(false, 'System error occurred');
} finally {
    if (isset($conn)) {
        closeConnection($conn);
    }
}
?>