<?php
// ajax/send_admin_response.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = intval($_POST['user_id']);
$message = mysqli_real_escape_string(getConnection(), $_POST['message']);

if ($user_id <= 0 || empty($message)) {
    echo json_encode(['success' => false]);
    exit;
}

$conn = getConnection();

$insert_query = "INSERT INTO chats (user_id, message, sender_type) VALUES ($user_id, '$message', 'admin')";

if (mysqli_query($conn, $insert_query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

closeConnection($conn);
?>