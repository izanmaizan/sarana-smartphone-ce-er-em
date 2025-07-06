<?php
// ajax/cancel_order.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Check if order belongs to user and can be cancelled
$check_query = "SELECT status FROM orders WHERE id = $order_id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
    exit;
}

$order = mysqli_fetch_assoc($check_result);

if ($order['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan']);
    exit;
}

// Cancel order
$cancel_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";

if (mysqli_query($conn, $cancel_query)) {
    // Restore product stock
    $restore_stock_query = "
        UPDATE products p 
        SET stock = stock + (
            SELECT oi.quantity 
            FROM order_items oi 
            WHERE oi.order_id = $order_id AND oi.product_id = p.id
        )
        WHERE p.id IN (
            SELECT product_id FROM order_items WHERE order_id = $order_id
        )
    ";
    mysqli_query($conn, $restore_stock_query);
    
    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pesanan']);
}

closeConnection($conn);
?>