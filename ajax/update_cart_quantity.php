<?php
// ajax/update_cart_quantity.php - NEW FILE
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
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if ($product_id === false || $product_id <= 0) {
    sendResponse(false, 'Invalid product ID');
}

if ($quantity === false || $quantity < 0) {
    sendResponse(false, 'Invalid quantity');
}

$conn = getConnection();

try {
    // Get product info
    $stmt = mysqli_prepare($conn, "SELECT name, stock FROM products WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        sendResponse(false, 'Product not found');
    }
    
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Initialize cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Update cart
    if ($quantity == 0) {
        unset($_SESSION['cart'][$product_id]);
        $message = "'{$product['name']}' dihapus dari keranjang";
    } else {
        if ($quantity > $product['stock']) {
            sendResponse(false, "Stok tidak mencukupi. Maksimal: {$product['stock']}");
        }
        
        $_SESSION['cart'][$product_id] = $quantity;
        $message = "Jumlah '{$product['name']}' diperbarui";
    }
    
    // Calculate new totals
    $cart_count = array_sum($_SESSION['cart']);
    $unique_items = count($_SESSION['cart']);
    
    sendResponse(true, $message, [
        'product_id' => $product_id,
        'new_quantity' => $quantity,
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