<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $review_id = intval($_POST['review_id']);
        $action = $_POST['action'];
        
        if (in_array($action, ['approved', 'rejected'])) {
            $update_query = "UPDATE reviews SET status = '$action' WHERE id = $review_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success = "Review berhasil " . ($action == 'approved' ? 'disetujui' : 'ditolak');
            } else {
                $error = "Gagal mengupdate status review";
            }
        } elseif ($action == 'reply') {
            $admin_reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
            $admin_id = $_SESSION['user_id'];
            
            if (!empty($admin_reply)) {
                $reply_query = "UPDATE reviews SET 
                               admin_reply = '$admin_reply', 
                               admin_reply_date = NOW(), 
                               replied_by = $admin_id 
                               WHERE id = $review_id";
                
                if (mysqli_query($conn, $reply_query)) {
                    $success = "Balasan berhasil dikirim";
                } else {
                    $error = "Gagal mengirim balasan";
                }
            } else {
                $error = "Balasan tidak boleh kosong";
            }
        } elseif ($action == 'delete_reply') {
            $delete_reply_query = "UPDATE reviews SET 
                                  admin_reply = NULL, 
                                  admin_reply_date = NULL, 
                                  replied_by = NULL 
                                  WHERE id = $review_id";
            
            if (mysqli_query($conn, $delete_reply_query)) {
                $success = "Balasan berhasil dihapus";
            } else {
                $error = "Gagal menghapus balasan";
            }
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$product_filter = isset($_GET['product']) ? $_GET['product'] : '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Get products for filter dropdown
$products_query = "SELECT id, name FROM products WHERE status = 'active' ORDER BY name";
$products_result = mysqli_query($conn, $products_query);

// Build reviews query
$reviews_query = "
    SELECT r.*, u.name as user_name, u.email as user_email, p.name as product_name,
           admin.name as admin_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users admin ON r.replied_by = admin.id
    WHERE 1=1
";

if (!empty($status_filter)) {
    $reviews_query .= " AND r.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if (!empty($product_filter)) {
    $reviews_query .= " AND r.product_id = " . intval($product_filter);
}

if ($rating_filter > 0) {
    $reviews_query .= " AND r.rating = " . $rating_filter;
}

$reviews_query .= " ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
        COUNT(CASE WHEN admin_reply IS NOT NULL THEN 1 END) as replied_reviews,
        AVG(CASE WHEN status = 'approved' THEN rating END) as avg_rating
    FROM reviews
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Review & Ulasan - Admin Sarana Smartphone</title>
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

    .review-card {
        transition: transform 0.3s;
        border-left: 4px solid #e9ecef;
    }

    .review-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .review-card.pending {
        border-left-color: #ffc107;
    }

    .review-card.approved {
        border-left-color: #28a745;
    }

    .review-card.rejected {
        border-left-color: #dc3545;
    }

    .review-card.has-reply {
        border-left-color: #007bff;
    }

    .rating-stars {
        color: #ffc107;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        transition: transform 0.3s;
        border: 1px solid #e9ecef;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.2rem;
        color: white;
    }

    .admin-reply-section {
        background: #e3f2fd;
        border: 1px solid #2196f3;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
    }

    .reply-form {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
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
                        <a class="nav-link" href="chats.php">
                            <i class="fas fa-comments me-2"></i> Chat
                        </a>
                        <a class="nav-link active" href="reviews.php">
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
                            <h2 class="mb-0">Kelola Review & Ulasan</h2>
                            <small class="text-muted">Kelola ulasan produk dan berikan balasan kepada pelanggan</small>
                        </div>
                        <div>
                            <span class="badge bg-info me-2">
                                <?= $stats['total_reviews'] ?> total ulasan
                            </span>
                            <span class="badge bg-success">
                                <?= $stats['replied_reviews'] ?> sudah dibalas
                            </span>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-star"></i>
                            </div>
                            <h4><?= $stats['total_reviews'] ?></h4>
                            <p class="text-muted mb-0 small">Total Review</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #28a745;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4><?= $stats['approved_reviews'] ?></h4>
                            <p class="text-muted mb-0 small">Disetujui</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #ffc107;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4><?= $stats['pending_reviews'] ?></h4>
                            <p class="text-muted mb-0 small">Pending</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #dc3545;">
                                <i class="fas fa-times"></i>
                            </div>
                            <h4><?= $stats['rejected_reviews'] ?></h4>
                            <p class="text-muted mb-0 small">Ditolak</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #007bff;">
                                <i class="fas fa-reply"></i>
                            </div>
                            <h4><?= $stats['replied_reviews'] ?></h4>
                            <p class="text-muted mb-0 small">Dibalas</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4><?= number_format((float)$stats['avg_rating'], 1) ?></h4>
                            <p class="text-muted mb-0 small">Rata-rata</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>
                                        Disetujui</option>
                                    <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>
                                        Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="product" class="form-select">
                                    <option value="">Semua Produk</option>
                                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                    <option value="<?= $product['id'] ?>"
                                        <?= $product_filter == $product['id'] ? 'selected' : '' ?>>
                                        <?= $product['name'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="rating" class="form-select">
                                    <option value="">Semua Rating</option>
                                    <option value="5" <?= $rating_filter == 5 ? 'selected' : '' ?>>5 Bintang</option>
                                    <option value="4" <?= $rating_filter == 4 ? 'selected' : '' ?>>4 Bintang</option>
                                    <option value="3" <?= $rating_filter == 3 ? 'selected' : '' ?>>3 Bintang</option>
                                    <option value="2" <?= $rating_filter == 2 ? 'selected' : '' ?>>2 Bintang</option>
                                    <option value="1" <?= $rating_filter == 1 ? 'selected' : '' ?>>1 Bintang</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="row">
                    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                    <div class="col-12 mb-3">
                        <div
                            class="card review-card <?= $review['status'] ?> <?= $review['admin_reply'] ? 'has-reply' : '' ?> border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= $review['user_name'] ?></h6>
                                                <small class="text-muted"><?= $review['user_email'] ?></small>
                                                <div class="rating-stars mt-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i
                                                        class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                    <span class="ms-2 text-muted">(<?= $review['rating'] ?>/5)</span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?= 
                                                            $review['status'] == 'pending' ? 'warning' : 
                                                            ($review['status'] == 'approved' ? 'success' : 'danger') 
                                                        ?>">
                                                    <?= ucfirst($review['status']) ?>
                                                </span>
                                                <?php if ($review['admin_reply']): ?>
                                                <span class="badge bg-info ms-1">Ada Balasan</span>
                                                <?php endif; ?>
                                                <div class="small text-muted mt-1">
                                                    <?= date('d M Y, H:i', strtotime($review['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="text-primary mb-2">
                                            <i class="fas fa-box"></i> <?= $review['product_name'] ?>
                                        </h6>

                                        <div class="review-comment mb-3">
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                        </div>

                                        <!-- Admin Reply Section -->
                                        <?php if ($review['admin_reply']): ?>
                                        <div class="admin-reply-section">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="text-primary">
                                                    <i class="fas fa-reply"></i>
                                                    Balasan dari <?= $review['admin_name'] ?: 'Admin' ?>
                                                </strong>
                                                <small class="text-muted">
                                                    <?= timeAgo($review['admin_reply_date']) ?>
                                                </small>
                                            </div>
                                            <p class="mb-2"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                                            <div class="text-end">
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteReply(<?= $review['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus Balasan
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary"
                                                    onclick="toggleReplyForm(<?= $review['id'] ?>)">
                                                    <i class="fas fa-edit"></i> Edit Balasan
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Reply Form -->
                                        <div class="reply-form" id="reply-form-<?= $review['id'] ?>"
                                            style="display: <?= $review['admin_reply'] ? 'none' : 'block' ?>;">
                                            <form method="POST">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <input type="hidden" name="action" value="reply">
                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        <i class="fas fa-reply text-primary"></i>
                                                        <?= $review['admin_reply'] ? 'Edit Balasan' : 'Balas Ulasan' ?>
                                                    </label>
                                                    <textarea class="form-control" name="admin_reply" rows="3"
                                                        placeholder="Tulis balasan Anda untuk pelanggan..."
                                                        required><?= $review['admin_reply'] ?></textarea>
                                                </div>
                                                <div class="text-end">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2"
                                                        onclick="toggleReplyForm(<?= $review['id'] ?>)">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-paper-plane"></i>
                                                        <?= $review['admin_reply'] ? 'Update Balasan' : 'Kirim Balasan' ?>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 text-end">
                                        <div class="btn-group-vertical w-100" role="group">
                                            <?php if ($review['status'] == 'pending'): ?>
                                            <form method="POST" class="d-inline mb-2">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="btn btn-success btn-sm w-100"
                                                    onclick="return confirm('Setujui review ini?')">
                                                    <i class="fas fa-check"></i> Setujui
                                                </button>
                                            </form>

                                            <form method="POST" class="d-inline mb-2">
                                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                <input type="hidden" name="action" value="rejected">
                                                <button type="submit" class="btn btn-danger btn-sm w-100"
                                                    onclick="return confirm('Tolak review ini?')">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <?php if (!$review['admin_reply']): ?>
                                            <button class="btn btn-outline-primary btn-sm mb-2"
                                                onclick="toggleReplyForm(<?= $review['id'] ?>)">
                                                <i class="fas fa-reply"></i> Balas
                                            </button>
                                            <?php endif; ?>

                                            <a href="../product_detail.php?id=<?= $review['product_id'] ?>#reviews"
                                                class="btn btn-outline-info btn-sm mb-2" target="_blank">
                                                <i class="fas fa-eye"></i> Lihat Produk
                                            </a>

                                            <?php if ($review['status'] != 'pending'): ?>
                                            <div class="btn-group mb-2">
                                                <?php if ($review['status'] == 'approved'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <input type="hidden" name="action" value="rejected">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('Tolak review yang sudah disetujui?')">
                                                        <i class="fas fa-ban"></i> Tolak
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                                        onclick="return confirm('Setujui review yang sebelumnya ditolak?')">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-star fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">Tidak Ada Review</h4>
                                <p class="text-muted">
                                    <?php if (!empty($status_filter) || !empty($product_filter) || $rating_filter > 0): ?>
                                    Tidak ada review yang sesuai dengan filter yang dipilih.
                                    <?php else: ?>
                                    Belum ada review produk yang masuk.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <?php if ($status_filter == 'pending' && mysqli_num_rows($reviews_result) > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6><i class="fas fa-bolt text-warning"></i> Aksi Cepat</h6>
                                <p class="text-muted small mb-3">
                                    Setujui atau tolak semua review pending sekaligus
                                </p>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="bulkAction('approved')">
                                        <i class="fas fa-check"></i> Setujui Semua
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="bulkAction('rejected')">
                                        <i class="fas fa-times"></i> Tolak Semua
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function bulkAction(action) {
        const actionText = action === 'approved' ? 'menyetujui' : 'menolak';

        if (confirm(`Yakin ingin ${actionText} semua review pending?`)) {
            // Create form for bulk action
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'bulk_review_action.php';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status_filter';
            statusInput.value = 'pending';

            form.appendChild(actionInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function toggleReplyForm(reviewId) {
        const replyForm = document.getElementById(`reply-form-${reviewId}`);
        if (replyForm.style.display === 'none') {
            replyForm.style.display = 'block';
        } else {
            replyForm.style.display = 'none';
        }
    }

    function deleteReply(reviewId) {
        if (confirm('Yakin ingin menghapus balasan ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const reviewIdInput = document.createElement('input');
            reviewIdInput.type = 'hidden';
            reviewIdInput.name = 'review_id';
            reviewIdInput.value = reviewId;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_reply';

            form.appendChild(reviewIdInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Auto-refresh pending count every 30 seconds
    setInterval(function() {
        fetch('../ajax/get_pending_reviews_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.badge.bg-warning');
                if (badge && data.count !== undefined) {
                    badge.textContent = `${data.count} menunggu moderasi`;
                    // Highlight if count increased
                    if (data.count > parseInt(badge.textContent)) {
                        badge.style.animation = 'pulse 1s';
                        setTimeout(() => {
                            badge.style.animation = '';
                        }, 1000);
                    }
                }
            });
    }, 30000);
    </script>
</body>

</html>

<?php closeConnection($conn); ?>