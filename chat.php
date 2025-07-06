<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Detect if opened as modal or fullscreen
$is_modal = isset($_GET['mode']) && $_GET['mode'] === 'modal';

// Handle new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $sender_type = isAdmin() ? 'admin' : 'customer';
    
    if (!empty($message)) {
        $insert_query = "INSERT INTO chats (user_id, message, sender_type) VALUES ($user_id, '$message', '$sender_type')";
        mysqli_query($conn, $insert_query);
    }
}

// Get chat messages
$chat_query = "
    SELECT c.*, u.name as user_name 
    FROM chats c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.user_id = $user_id 
    ORDER BY c.created_at ASC
";
$chat_result = mysqli_query($conn, $chat_query);

// Mark messages as read
$update_read = "UPDATE chats SET status = 'read' WHERE user_id = $user_id AND sender_type = 'admin'";
mysqli_query($conn, $update_read);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Customer Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        <?php if ($is_modal): ?> margin: 0;
        padding: 0;
        <?php else: ?> padding-top: 76px;
        /* For fixed navbar */
        <?php endif;
        ?>
    }

    .chat-container {
        <?php if ($is_modal): ?> height: 100vh;
        <?php else: ?> height: calc(100vh - 76px);
        <?php endif;
        ?>display: flex;
        flex-direction: column;
        <?php if ( !$is_modal): ?> max-width: 800px;
        margin: 0 auto;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        <?php endif;
        ?>
    }

    .chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-bottom: 1px solid #ddd;
        <?php if ( !$is_modal): ?> border-radius: 15px 15px 0 0;
        <?php endif;
        ?>
    }

    <?php if ( !$is_modal): ?>.back-button {
        position: absolute;
        top: 1rem;
        left: 1rem;
        color: white;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .back-button:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateX(-3px);
    }

    <?php endif;

    ?>.chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #fff;
    }

    .message {
        margin-bottom: 1rem;
        display: flex;
    }

    .message.customer {
        justify-content: flex-end;
    }

    .message.admin {
        justify-content: flex-start;
    }

    .message-bubble {
        max-width: 70%;
        padding: 0.75rem 1rem;
        border-radius: 20px;
        position: relative;
    }

    .message.customer .message-bubble {
        background: #007bff;
        color: white;
        border-bottom-right-radius: 5px;
    }

    .message.admin .message-bubble {
        background: #e9ecef;
        color: #333;
        border-bottom-left-radius: 5px;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 0.25rem;
    }

    .message.customer .message-time {
        text-align: right;
    }

    .message.admin .message-time {
        text-align: left;
    }

    .chat-input {
        background: white;
        border-top: 1px solid #ddd;
        padding: 1rem;
        <?php if ( !$is_modal): ?> border-radius: 0 0 15px 15px;
        <?php endif;
        ?>
    }

    .typing-indicator {
        display: none;
        padding: 0.5rem 1rem;
        font-style: italic;
        color: #666;
    }

    .typing-dots {
        display: inline-block;
    }

    .typing-dots span {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #999;
        margin: 0 1px;
        animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dots span:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-dots span:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes typing {

        0%,
        80%,
        100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    .online-indicator {
        width: 10px;
        height: 10px;
        background: #28a745;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }

    <?php if ( !$is_modal): ?>

    /* Fullscreen specific styles */
    .navbar-included {
        margin-top: 2rem;
        margin-bottom: 2rem;
    }

    .chat-header {
        position: relative;
    }

    .minimize-button {
        position: absolute;
        top: 1rem;
        right: 1rem;
        color: white;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        border: none;
    }

    .minimize-button:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    <?php endif;
    ?>

    /* Mobile responsive */
    @media (max-width: 768px) {
        <?php if ( !$is_modal): ?>body {
            padding-top: 70px;
        }

        .chat-container {
            height: calc(100vh - 70px);
            border-radius: 0;
            margin: 0;
        }

        .chat-header {
            border-radius: 0;
        }

        .chat-input {
            border-radius: 0;
        }

        <?php endif;

        ?>.message-bubble {
            max-width: 85%;
        }
    }
    </style>
</head>

<body>
    <?php if (!$is_modal): ?>
    <!-- Include navbar for fullscreen mode -->
    <?php include 'navbar.php'; ?>
    <div class="container navbar-included">
        <?php endif; ?>

        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <?php if (!$is_modal): ?>
                <!-- Back button for fullscreen mode -->
                <a href="<?= BASE_URL ?>index.php" class="back-button" title="Kembali ke Beranda">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-headset me-2"></i>
                        <div class="text-center">
                            <h6 class="mb-0">Customer Service</h6>
                            <small>
                                <span class="online-indicator"></span>
                                Online - Siap membantu Anda
                            </small>
                        </div>
                    </div>
                </div>

                <?php if ($is_modal): ?>
                <!-- Close button for modal mode -->
                <button class="btn btn-light btn-sm position-absolute" style="top: 1rem; right: 1rem;"
                    onclick="window.close()">
                    <i class="fas fa-times"></i>
                </button>
                <?php else: ?>
                <!-- Minimize button for fullscreen mode -->
                <button class="minimize-button" onclick="openChatModal()" title="Buka sebagai popup kecil">
                    <i class="fas fa-compress-alt"></i>
                </button>
                <?php endif; ?>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (mysqli_num_rows($chat_result) == 0): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Belum ada percakapan. Mulai chat dengan Customer Service!</p>
                </div>
                <?php else: ?>
                <?php while ($chat = mysqli_fetch_assoc($chat_result)): ?>
                <div class="message <?= $chat['sender_type'] ?>">
                    <div class="message-bubble">
                        <div class="message-text"><?= nl2br(htmlspecialchars($chat['message'])) ?></div>
                        <div class="message-time">
                            <?php if ($chat['sender_type'] == 'admin'): ?>
                            <i class="fas fa-user-tie"></i> CS •
                            <?php endif; ?>
                            <?= date('H:i', strtotime($chat['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>

                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="message admin">
                        <div class="message-bubble">
                            Customer Service sedang mengetik
                            <span class="typing-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="chat-input">
                <form method="POST" id="chatForm">
                    <div class="input-group">
                        <input type="text" class="form-control" name="message" id="messageInput"
                            placeholder="Ketik pesan Anda..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> Respon dalam 1-5 menit •
                        <i class="fas fa-shield-alt"></i> Aman & Terpercaya
                    </small>
                </div>
            </div>
        </div>

        <?php if (!$is_modal): ?>
    </div> <!-- Close container -->
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Set global variables
    const IS_MODAL = <?= $is_modal ? 'true' : 'false' ?>;
    const BASE_URL = '<?= BASE_URL ?>';
    const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;

    // Auto scroll to bottom
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Auto refresh messages
    function refreshMessages() {
        fetch('ajax/get_chat_messages.php')
            .then(response => response.text())
            .then(html => {
                const currentMessages = document.getElementById('chatMessages').innerHTML;
                if (currentMessages !== html) {
                    document.getElementById('chatMessages').innerHTML = html;
                    scrollToBottom();
                }
            })
            .catch(error => console.log('Error refreshing messages:', error));
    }

    // Show typing indicator randomly (simulate admin typing)
    function showTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (Math.random() > 0.7) { // 30% chance
            indicator.style.display = 'block';
            scrollToBottom();

            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000 + Math.random() * 3000); // 2-5 seconds
        }
    }

    // Handle form submission
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();

        if (message) {
            // Send message via AJAX
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(() => {
                    messageInput.value = '';
                    refreshMessages();

                    // Simulate admin response delay
                    if (!IS_ADMIN) {
                        setTimeout(showTypingIndicator, 1000);
                    }
                });
        }
    });

    // Open chat as modal from fullscreen mode
    function openChatModal() {
        if (IS_MODAL) return; // Already in modal mode

        try {
            const chatWindow = window.open(
                `${BASE_URL}chat.php?mode=modal`,
                'chat',
                'width=400,height=600,scrollbars=yes,resizable=yes,location=no,menubar=no,toolbar=no'
            );

            if (chatWindow) {
                // Close current fullscreen window after small delay
                setTimeout(() => {
                    window.location.href = `${BASE_URL}index.php`;
                }, 500);
            }
        } catch (e) {
            console.error('Unable to open chat modal:', e);
        }
    }

    // Auto-scroll on page load
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();

        // Auto refresh every 5 seconds
        setInterval(refreshMessages, 5000);

        // Focus on input
        document.getElementById('messageInput').focus();

        // Add window resize handler for modal mode
        if (IS_MODAL) {
            // Prevent accidental navigation in modal
            window.addEventListener('beforeunload', function(e) {
                // Only show warning if there are recent messages
                const messages = document.querySelectorAll('.message');
                if (messages.length > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        }

        console.log('💬 Chat initialized in ' + (IS_MODAL ? 'modal' : 'fullscreen') + ' mode');
    });

    // Handle Enter key
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }
    });

    // Add some auto-responses for demo (only for customer)
    <?php if (!isAdmin()): ?>
    const autoResponses = [
        "Terima kasih telah menghubungi Sarana Smartphone! Bagaimana saya bisa membantu Anda hari ini?",
        "Saya akan membantu Anda dengan pertanyaan tentang produk kami.",
        "Apakah ada produk smartphone tertentu yang Anda cari?",
        "Untuk informasi lebih lanjut, saya bisa membantu Anda memilih produk yang sesuai kebutuhan."
    ];

    function sendAutoResponse() {
        if (Math.random() > 0.8) { // 20% chance
            const response = autoResponses[Math.floor(Math.random() * autoResponses.length)];

            setTimeout(() => {
                fetch('ajax/send_admin_response.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'user_id=<?= $user_id ?>&message=' + encodeURIComponent(response)
                    })
                    .then(() => refreshMessages());
            }, 3000 + Math.random() * 5000); // 3-8 seconds delay
        }
    }

    // Trigger auto response occasionally
    setTimeout(sendAutoResponse, 10000); // After 10 seconds
    <?php endif; ?>

    // Modal specific functionality
    if (IS_MODAL) {
        // Adjust sizing for small popup
        document.addEventListener('DOMContentLoaded', function() {
            // Make message bubbles slightly smaller in modal
            const style = document.createElement('style');
            style.textContent = `
                .message-bubble {
                    max-width: 80%;
                    font-size: 0.9rem;
                    padding: 0.6rem 0.8rem;
                }
                .message-time {
                    font-size: 0.7rem;
                }
            `;
            document.head.appendChild(style);
        });
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>