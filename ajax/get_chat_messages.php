<?php
// ajax/get_chat_messages.php
require_once '../config.php';

if (!isLoggedIn()) {
    exit;
}

$conn = getConnection();
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];
$is_admin = isset($_GET['admin']) && isAdmin();

// Security check for admin
if (isset($_GET['admin']) && !isAdmin()) {
    exit;
}

$chat_query = "
    SELECT c.*, u.name as user_name 
    FROM chats c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.user_id = $user_id 
    ORDER BY c.created_at ASC
";
$chat_result = mysqli_query($conn, $chat_query);

if (mysqli_num_rows($chat_result) == 0) {
    echo '<div class="text-center text-muted py-4">
            <i class="fas fa-comments fa-3x mb-3"></i>
            <p>Belum ada percakapan. Mulai chat dengan Customer Service!</p>
          </div>';
} else {
    while ($chat = mysqli_fetch_assoc($chat_result)) {
        echo '<div class="message ' . $chat['sender_type'] . '">
                <div class="message-bubble">
                    <div class="message-text">' . nl2br(htmlspecialchars($chat['message'])) . '</div>
                    <div class="message-time">';
        
        if ($chat['sender_type'] == 'admin') {
            echo '<i class="fas fa-user-tie"></i> CS • ';
        }
        
        echo date('H:i', strtotime($chat['created_at']));
        
        if ($chat['sender_type'] == 'admin') {
            echo ' <i class="fas fa-check-double"></i>';
        }
        
        echo '</div>
                </div>
              </div>';
    }
}

closeConnection($conn);
?>