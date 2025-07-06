<?php
// ajax/update_order_status.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = intval($_POST['order_id']);
$status = mysqli_real_escape_string(getConnection(), $_POST['status']);

$valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$conn = getConnection();

$update_query = "UPDATE orders SET status = '$status' WHERE id = $order_id";

if (mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
}

closeConnection($conn);
?>