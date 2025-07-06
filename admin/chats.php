<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle reply to customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_reply'])) {
    $user_id = intval($_POST['user_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    if (!empty($message)) {
        $insert_query = "INSERT INTO chats (user_id, message, sender_type) VALUES ($user_id, '$message', 'admin')";
        
        if (mysqli_query($conn, $insert_query)) {
            // Mark customer messages as read
            $mark_read = "UPDATE chats SET status = 'read' WHERE user_id = $user_id AND sender_type = 'customer'";
            mysqli_query($conn, $mark_read);
            
            $success = 'Balasan berhasil dikirim';
        } else {
            $error = 'Gagal mengirim balasan';
        }
    }
}

// Get customers with unread messages
$unread_query = "
    SELECT u.id, u.name, u.email, 
           COUNT(c.id) as unread_count,
           MAX(c.created_at) as last_message
    FROM users u
    INNER JOIN chats c ON u.id = c.user_id
    WHERE c.status = 'unread' AND c.sender_type = 'customer'
    GROUP BY u.id, u.name, u.email
    ORDER BY last_message DESC
";
$unread_result = mysqli_query($conn, $unread_query);

// Get all customers who have chatted
$all_chats_query = "
    SELECT u.id, u.name, u.email,
           COUNT(CASE WHEN c.status = 'unread' AND c.sender_type = 'customer' THEN 1 END) as unread_count,
           MAX(c.created_at) as last_message,
           (SELECT message FROM chats WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message_text,
           (SELECT sender_type FROM chats WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_sender
    FROM users u
    INNER JOIN chats c ON u.id = c.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id, u.name, u.email
    ORDER BY last_message DESC
";
$all_chats_result = mysqli_query($conn, $all_chats_query);

// Get selected chat if any
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$chat_messages = [];
$selected_user = null;

if ($selected_user_id) {
    // Get user info
    $user_info_query = "SELECT * FROM users WHERE id = $selected_user_id AND role = 'customer'";
    $user_info_result = mysqli_query($conn, $user_info_query);
    $selected_user = mysqli_fetch_assoc($user_info_result);
    
    if ($selected_user) {
        // Get chat messages
        $messages_query = "
            SELECT c.*, u.name as user_name 
            FROM chats c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.user_id = $selected_user_id 
            ORDER BY c.created_at ASC
        ";
        $messages_result = mysqli_query($conn, $messages_query);
        
        while ($message = mysqli_fetch_assoc($messages_result)) {
            $chat_messages[] = $message;
        }
        
        // Mark customer messages as read
        $mark_read = "UPDATE chats SET status = 'read' WHERE user_id = $selected_user_id AND sender_type = 'customer'";
        mysqli_query($conn, $mark_read);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Customer Service - Admin Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(180deg, #343a40 0%, #495057 100%);
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        border-radius: 5px;
        margin: 2px 0;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    .chat-list {
        height: calc(100vh - 200px);
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 10px;
    }

    .chat-item {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .chat-item:hover {
        background-color: #f8f9fa;
    }

    .chat-item.active {
        background-color: #e7f3ff;
        border-left: 4px solid #007bff;
    }

    .chat-item.unread {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }

    .chat-area {
        height: calc(100vh - 200px);
        display: flex;
        flex-direction: column;
        border: 1px solid #e9ecef;
        border-radius: 10px;
    }

    .chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 10px 10px 0 0;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #f8f9fa;
    }

    .chat-input {
        padding: 1rem;
        border-top: 1px solid #e9ecef;
        background: white;
        border-radius: 0 0 10px 10px;
    }

    .message {
        margin-bottom: 1rem;
        display: flex;
        align-items: flex-start;
    }

    .message.admin {
        justify-content: flex-end;
    }

    .message.customer {
        justify-content: flex-start;
    }

    .message-bubble {
        max-width: 70%;
        padding: 0.75rem 1rem;
        border-radius: 20px;
        position: relative;
    }

    .message.admin .message-bubble {
        background: #007bff;
        color: white;
        border-bottom-right-radius: 5px;
    }

    .message.customer .message-bubble {
        background: white;
        color: #333;
        border: 1px solid #e9ecef;
        border-bottom-left-radius: 5px;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 0.25rem;
    }

    .unread-badge {
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-chat {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6c757d;
        text-align: center;
    }

    .quick-replies {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .quick-reply {
        background: #e9ecef;
        border: none;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .quick-reply:hover {
        background: #dee2e6;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-mobile-alt"></i> Sarana Admin
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i> Produk
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i> Kategori
                        </a>
                        <a class="nav-link" href="units.php">
                            <i class="fas fa-ruler me-2"></i> Satuan
                        </a>
                        <a class="nav-link" href="discounts.php">
                            <i class="fas fa-percent me-2"></i> Diskon
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i> Pesanan
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users me-2"></i> Pelanggan
                        </a>
                        <a class="nav-link active" href="chats.php">
                            <i class="fas fa-comments me-2"></i> Chat
                        </a>
                        <a class="nav-link" href="reviews.php">
                            <i class="fas fa-star me-2"></i> Ulasan
                        </a>
                        <a class="nav-link" href="stock.php">
                            <i class="fas fa-warehouse me-2"></i> Stok
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Laporan
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Header -->
                <div class="bg-white shadow-sm p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Chat Customer Service</h2>
                            <small class="text-muted">Kelola komunikasi dengan pelanggan</small>
                        </div>
                        <div>
                            <span class="badge bg-warning me-2">
                                <?= mysqli_num_rows($unread_result) ?> pesan belum dibaca
                            </span>
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshChats()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Chat List -->
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Daftar Chat</h5>
                            <span class="badge bg-info"><?= mysqli_num_rows($all_chats_result) ?> percakapan</span>
                        </div>

                        <div class="chat-list">
                            <?php if (mysqli_num_rows($all_chats_result) > 0): ?>
                            <?php while ($chat = mysqli_fetch_assoc($all_chats_result)): ?>
                            <div class="chat-item <?= $chat['unread_count'] > 0 ? 'unread' : '' ?> <?= $selected_user_id == $chat['id'] ? 'active' : '' ?>"
                                onclick="selectChat(<?= $chat['id'] ?>)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= $chat['name'] ?></h6>
                                        <small class="text-muted d-block"><?= $chat['email'] ?></small>
                                        <small class="text-muted">
                                            <?php if ($chat['last_sender'] == 'admin'): ?>
                                            <i class="fas fa-reply text-primary"></i> Anda:
                                            <?php endif; ?>
                                            <?= substr($chat['last_message_text'], 0, 30) ?>...
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($chat['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?= $chat['unread_count'] ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted d-block mt-1">
                                            <?= timeAgo($chat['last_message']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Belum ada percakapan</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="col-md-8">
                        <?php if ($selected_user): ?>
                        <div class="chat-area">
                            <!-- Chat Header -->
                            <div class="chat-header">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= $selected_user['name'] ?></h6>
                                        <small><?= $selected_user['email'] ?> • <?= $selected_user['phone'] ?></small>
                                    </div>
                                    <div>
                                        <button class="btn btn-light btn-sm me-2"
                                            onclick="viewCustomerInfo(<?= $selected_user['id'] ?>)">
                                            <i class="fas fa-info-circle"></i> Info
                                        </button>
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Online
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Messages -->
                            <div class="chat-messages" id="chatMessages">
                                <?php foreach ($chat_messages as $message): ?>
                                <div class="message <?= $message['sender_type'] ?>">
                                    <div class="message-bubble">
                                        <div><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                                        <div class="message-time">
                                            <?= date('H:i', strtotime($message['created_at'])) ?>
                                            <?php if ($message['sender_type'] == 'admin'): ?>
                                            <i class="fas fa-check-double"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Chat Input -->
                            <div class="chat-input">
                                <!-- Quick Replies -->
                                <div class="quick-replies">
                                    <button class="quick-reply"
                                        onclick="insertQuickReply('Halo! Ada yang bisa saya bantu?')">
                                        Salam Pembuka
                                    </button>
                                    <button class="quick-reply"
                                        onclick="insertQuickReply('Terima kasih atas pertanyaannya. Saya akan membantu Anda.')">
                                        Terima Kasih
                                    </button>
                                    <button class="quick-reply"
                                        onclick="insertQuickReply('Mohon tunggu sebentar, saya cek dulu informasinya.')">
                                        Mohon Tunggu
                                    </button>
                                    <button class="quick-reply"
                                        onclick="insertQuickReply('Apakah ada hal lain yang bisa saya bantu?')">
                                        Ada Lagi?
                                    </button>
                                </div>

                                <form method="POST" id="replyForm">
                                    <input type="hidden" name="user_id" value="<?= $selected_user['id'] ?>">
                                    <div class="input-group">
                                        <textarea class="form-control" name="message" id="messageInput"
                                            placeholder="Ketik balasan Anda..." rows="2" required></textarea>
                                        <button type="submit" name="send_reply" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Kirim
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="chat-area">
                            <div class="empty-chat">
                                <div>
                                    <i class="fas fa-comments fa-5x mb-4"></i>
                                    <h4>Pilih Percakapan</h4>
                                    <p class="text-muted">Klik pada pelanggan di sebelah kiri untuk memulai chat</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Info Modal -->
    <div class="modal fade" id="customerInfoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Informasi Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerInfoContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function selectChat(userId) {
        window.location.href = `chats.php?user_id=${userId}`;
    }

    function insertQuickReply(text) {
        document.getElementById('messageInput').value = text;
        document.getElementById('messageInput').focus();
    }

    function viewCustomerInfo(userId) {
        const modal = new bootstrap.Modal(document.getElementById('customerInfoModal'));
        modal.show();

        document.getElementById('customerInfoContent').innerHTML =
            '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';

        fetch(`../ajax/get_customer_info.php?id=${userId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('customerInfoContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('customerInfoContent').innerHTML =
                    '<div class="alert alert-danger">Gagal memuat informasi pelanggan.</div>';
            });
    }

    function refreshChats() {
        location.reload();
    }

    // Auto-scroll chat messages to bottom
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    // Handle Enter key in textarea
    document.getElementById('messageInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('replyForm').submit();
        }
    });

    // Auto-refresh every 10 seconds
    setInterval(function() {
        <?php if ($selected_user_id): ?>
        fetch(`../ajax/get_chat_messages.php?user_id=<?= $selected_user_id ?>&admin=1`)
            .then(response => response.text())
            .then(html => {
                const currentMessages = document.getElementById('chatMessages').innerHTML;
                if (currentMessages !== html && html.trim()) {
                    document.getElementById('chatMessages').innerHTML = html;
                    scrollToBottom();
                }
            });
        <?php endif; ?>
    }, 10000);

    // Scroll to bottom on page load
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>