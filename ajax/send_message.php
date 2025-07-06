<?php
// ajax/send_message.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'];
$sender_type = isAdmin() ? 'admin' : 'customer';

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

$conn = getConnection();

$message_escaped = mysqli_real_escape_string($conn, $message);

$insert_query = "INSERT INTO chats (user_id, message, sender_type, status) 
                VALUES ($user_id, '$message_escaped', '$sender_type', 'unread')";

if (mysqli_query($conn, $insert_query)) {
    // If customer sends message, mark admin messages as read
    if ($sender_type == 'customer') {
        $mark_read = "UPDATE chats SET status = 'read' WHERE user_id = $user_id AND sender_type = 'admin'";
        mysqli_query($conn, $mark_read);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully',
        'timestamp' => date('H:i')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

closeConnection($conn);
?>