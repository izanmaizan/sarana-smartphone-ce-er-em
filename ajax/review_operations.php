<?php
// ajax/review_operations.php
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

$action = $_POST['action'] ?? '';
$conn = getConnection();

switch ($action) {
    case 'submit_review':
        if (isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin cannot submit reviews']);
            exit;
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $order_id = intval($_POST['order_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // Validation
        if ($product_id <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }
        
        if (empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Komentar harus diisi']);
            exit;
        }
        
        // Check if user has purchased this product
        $purchase_check = "
            SELECT COUNT(*) as count 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = $user_id 
            AND oi.product_id = $product_id 
            AND o.status = 'delivered'
        ";
        $purchase_result = mysqli_query($conn, $purchase_check);
        $purchase_data = mysqli_fetch_assoc($purchase_result);
        
        if ($purchase_data['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Anda hanya bisa memberikan review untuk produk yang sudah dibeli']);
            exit;
        }
        
        // Check if user already reviewed this product
        $existing_check = "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id";
        $existing_result = mysqli_query($conn, $existing_check);
        
        if (mysqli_num_rows($existing_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan review untuk produk ini']);
            exit;
        }
        
        $comment_escaped = mysqli_real_escape_string($conn, $comment);
        
        $insert_query = "INSERT INTO reviews (user_id, product_id, order_id, rating, comment, status) 
                        VALUES ($user_id, $product_id, $order_id, $rating, '$comment_escaped', 'pending')";
        
        if (mysqli_query($conn, $insert_query)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Review berhasil dikirim dan sedang menunggu moderasi'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim review']);
        }
        break;
        
    case 'moderate_review':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        $review_id = intval($_POST['review_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($review_id <= 0 || !in_array($status, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }
        
        $update_query = "UPDATE reviews SET status = '$status' WHERE id = $review_id";
        
        if (mysqli_query($conn, $update_query)) {
            $action_text = $status == 'approved' ? 'disetujui' : 'ditolak';
            echo json_encode([
                'success' => true, 
                'message' => "Review berhasil $action_text"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memproses review']);
        }
        break;
        
    case 'vote_helpful':
        $review_id = intval($_POST['review_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        if ($review_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Review ID tidak valid']);
            exit;
        }
        
        // Check if user already voted
        $vote_check = "SELECT id FROM review_votes WHERE review_id = $review_id AND user_id = $user_id";
        $vote_result = mysqli_query($conn, $vote_check);
        
        if (mysqli_num_rows($vote_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan vote untuk review ini']);
            exit;
        }
        
        // Insert vote
        $vote_query = "INSERT INTO review_votes (review_id, user_id) VALUES ($review_id, $user_id)";
        
        if (mysqli_query($conn, $vote_query)) {
            // Get updated vote count
            $count_query = "SELECT COUNT(*) as count FROM review_votes WHERE review_id = $review_id";
            $count_result = mysqli_query($conn, $count_query);
            $count_data = mysqli_fetch_assoc($count_result);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Terima kasih atas vote Anda',
                'vote_count' => $count_data['count']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memberikan vote']);
        }
        break;
        
    case 'get_product_reviews':
        $product_id = intval($_POST['product_id'] ?? 0);
        $limit = intval($_POST['limit'] ?? 10);
        $offset = intval($_POST['offset'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
            exit;
        }
        
        $reviews_query = "
            SELECT r.*, u.name as user_name,
                   COUNT(rv.id) as helpful_votes
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN review_votes rv ON r.id = rv.review_id
            WHERE r.product_id = $product_id AND r.status = 'approved'
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $reviews_result = mysqli_query($conn, $reviews_query);
        $reviews = [];
        
        while ($review = mysqli_fetch_assoc($reviews_result)) {
            $reviews[] = [
                'id' => $review['id'],
                'user_name' => substr($review['user_name'], 0, 1) . '***', // Hide full name
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'helpful_votes' => $review['helpful_votes'],
                'created_at' => date('d M Y', strtotime($review['created_at']))
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'reviews' => $reviews
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

closeConnection($conn);
?>