<?php
// ajax/get_pending_reviews_count.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['count' => 0]);
    exit;
}

$conn = getConnection();

$count_query = "SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'";
$count_result = mysqli_query($conn, $count_query);
$count = mysqli_fetch_assoc($count_result)['count'];

echo json_encode(['count' => intval($count)]);

closeConnection($conn);
?>