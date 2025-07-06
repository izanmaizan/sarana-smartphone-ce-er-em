<?php
// ajax/get_notifications.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode([]);
    exit;
}

$conn = getConnection();

// Get notification counts
$stats = [];

// Pending orders
$pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$pending_orders_result = mysqli_query($conn, $pending_orders_query);
$stats['pending_orders'] = mysqli_fetch_assoc($pending_orders_result)['total'];

// Unread chats
$unread_chats_query = "SELECT COUNT(DISTINCT user_id) as total FROM chats WHERE status = 'unread' AND sender_type = 'customer'";
$unread_chats_result = mysqli_query($conn, $unread_chats_query);
$stats['unread_chats'] = mysqli_fetch_assoc($unread_chats_result)['total'];

// Low stock products
$low_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND status = 'active'";
$low_stock_result = mysqli_query($conn, $low_stock_query);
$stats['low_stock'] = mysqli_fetch_assoc($low_stock_result)['total'];

// Pending reviews
$pending_reviews_query = "SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'";
$pending_reviews_result = mysqli_query($conn, $pending_reviews_query);
$stats['pending_reviews'] = mysqli_fetch_assoc($pending_reviews_result)['total'];

echo json_encode($stats);

closeConnection($conn);
?>