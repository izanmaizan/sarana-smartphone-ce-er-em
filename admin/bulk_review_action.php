<?php
// admin/bulk_review_action.php
require_once '../config.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bulk_action = $_POST['bulk_action'];
    $status_filter = $_POST['status_filter'];
    
    if (in_array($bulk_action, ['approved', 'rejected']) && $status_filter == 'pending') {
        $conn = getConnection();
        
        $update_query = "UPDATE reviews SET status = '$bulk_action' WHERE status = 'pending'";
        
        if (mysqli_query($conn, $update_query)) {
            $affected_rows = mysqli_affected_rows($conn);
            $success = "Berhasil " . ($bulk_action == 'approved' ? 'menyetujui' : 'menolak') . " $affected_rows review";
        } else {
            $error = "Gagal melakukan bulk action";
        }
        
        closeConnection($conn);
        
        // Redirect back to reviews page
        $message = isset($success) ? 'success=' . urlencode($success) : 'error=' . urlencode($error);
        header("Location: reviews.php?$message");
        exit;
    }
}

// Redirect if invalid request
header("Location: reviews.php");
exit;
?>