<?php
require_once 'config.php';

$conn = getConnection();
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    redirect('index.php');
}

// Handle review submission - LANGSUNG APPROVED
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && isLoggedIn() && !isAdmin()) {
    $rating = intval($_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user already reviewed this product
    $check_review = "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = mysqli_query($conn, $check_review);
    
    if (mysqli_num_rows($check_result) == 0) {
        // LANGSUNG SET STATUS 'approved' TANPA PERLU PERSETUJUAN ADMIN
        $insert_review = "INSERT INTO reviews (user_id, product_id, rating, comment, status) 
                         VALUES ($user_id, $product_id, $rating, '$comment', 'approved')";
        
        if (mysqli_query($conn, $insert_review)) {
            $review_success = "Ulasan Anda berhasil dikirim dan telah dipublikasikan!";
        } else {
            $review_error = "Gagal mengirim ulasan. Silakan coba lagi.";
        }
    } else {
        $review_error = "Anda sudah memberikan ulasan untuk produk ini.";
    }
}

// Get product details with discount
$product_query = "
    SELECT p.*, c.name as category_name, u.name as unit_name,
           d.percentage as discount_percentage,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN p.price - (p.price * d.percentage / 100)
               ELSE p.price 
           END as final_price,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN 1 ELSE 0 
           END as has_discount,
           d.end_date as discount_end_date
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN discounts d ON p.id = d.product_id 
    WHERE p.id = $product_id AND p.status = 'active'
";

$product_result = mysqli_query($conn, $product_query);

if (mysqli_num_rows($product_result) == 0) {
    redirect('index.php');
}

$product = mysqli_fetch_assoc($product_result);

// Get reviews and ratings WITH ADMIN REPLIES
$reviews_query = "
    SELECT r.*, u.name as user_name, u.email,
           admin.name as admin_name
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    LEFT JOIN users admin ON r.replied_by = admin.id
    WHERE r.product_id = $product_id AND r.status = 'approved'
    ORDER BY r.created_at DESC
";
$reviews_result = mysqli_query($conn, $reviews_query);

// Get rating statistics
$rating_stats_query = "
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1
    FROM reviews 
    WHERE product_id = $product_id AND status = 'approved'
";
$rating_stats_result = mysqli_query($conn, $rating_stats_query);
$rating_stats = mysqli_fetch_assoc($rating_stats_result);

// Get related products
$related_query = "
    SELECT p.*, 
           d.percentage as discount_percentage,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN p.price - (p.price * d.percentage / 100)
               ELSE p.price 
           END as final_price,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN 1 ELSE 0 
           END as has_discount
    FROM products p 
    LEFT JOIN discounts d ON p.id = d.product_id 
    WHERE p.category_id = {$product['category_id']} 
    AND p.id != $product_id 
    AND p.status = 'active' 
    AND p.stock > 0
    ORDER BY RAND() 
    LIMIT 4
";
$related_result = mysqli_query($conn, $related_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product['name'] ?> - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f8f9fa;
        padding-top: 80px;
    }

    .product-image {
        max-height: 400px;
        object-fit: cover;
        border-radius: 10px;
    }

    .discount-timer {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        padding: 10px;
        border-radius: 10px;
        text-align: center;
    }

    .rating-stars {
        color: #ffc107;
    }

    .rating-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .rating-fill {
        height: 100%;
        background: #ffc107;
        transition: width 0.3s;
    }

    .review-card {
        border-left: 4px solid #007bff;
        background: #f8f9fa;
    }

    .admin-reply {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        margin-top: 10px;
        padding: 10px;
        border-radius: 5px;
    }

    .btn-add-cart {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        font-weight: bold;
        padding: 12px 30px;
        border-radius: 25px;
        transition: transform 0.3s;
    }

    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }

    .btn-buy-now {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        border: none;
        color: white;
        font-weight: bold;
        padding: 12px 30px;
        border-radius: 25px;
    }

    .btn-buy-now:hover {
        color: white;
    }

    .product-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
    }

    .product-tabs .nav-link.active {
        color: #007bff;
        border-bottom: 2px solid #007bff;
    }

    .quantity-selector {
        border: 2px solid #e9ecef;
        border-radius: 25px;
        overflow: hidden;
    }

    .stock-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 1;
    }

    .breadcrumb-section {
        background: white;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 0;
        margin-top: -80px;
        padding-top: 100px;
    }
    </style>
</head>

<body>
    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Breadcrumb Section -->
    <section class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item"><a
                            href="index.php?category=<?= $product['category_id'] ?>"><?= $product['category_name'] ?></a>
                    </li>
                    <li class="breadcrumb-item active"><?= $product['name'] ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <div class="container py-4">
        <!-- Product Detail -->
        <div class="row mb-5">
            <div class="col-lg-6">
                <div class="position-relative">
                    <?php if ($product['has_discount']): ?>
                    <span class="badge bg-danger stock-badge fs-6">
                        -<?= $product['discount_percentage'] ?>% OFF
                    </span>
                    <?php endif; ?>

                    <?php if ($product['stock'] <= 5): ?>
                    <span class="badge bg-warning stock-badge" style="top: 50px;">
                        Stok Terbatas
                    </span>
                    <?php endif; ?>

                    <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                        class="img-fluid product-image w-100" alt="<?= $product['name'] ?>">
                </div>
            </div>

            <div class="col-lg-6">
                <h1 class="h3 mb-3"><?= $product['name'] ?></h1>

                <!-- Rating -->
                <?php if ($rating_stats['total_reviews'] > 0): ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="rating-stars me-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="<?= $i <= round($rating_stats['avg_rating']) ? 'fas' : 'far' ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted">
                        <?= number_format($rating_stats['avg_rating'], 1) ?>/5
                        (<?= $rating_stats['total_reviews'] ?> ulasan)
                    </span>
                </div>
                <?php endif; ?>

                <!-- Price -->
                <div class="mb-3">
                    <?php if ($product['has_discount']): ?>
                    <span class="text-decoration-line-through text-muted fs-5 me-2">
                        <?= formatRupiah($product['price']) ?>
                    </span>
                    <span class="text-danger fs-3 fw-bold">
                        <?= formatRupiah($product['final_price']) ?>
                    </span>
                    <span class="badge bg-danger ms-2">
                        Hemat <?= formatRupiah($product['price'] - $product['final_price']) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-primary fs-3 fw-bold">
                        <?= formatRupiah($product['final_price']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Discount Timer -->
                <?php if ($product['has_discount'] && $product['discount_end_date']): ?>
                <div class="discount-timer mb-3">
                    <h6 class="mb-1">⏰ Diskon berakhir dalam:</h6>
                    <div id="countdown" class="fw-bold"></div>
                </div>
                <?php endif; ?>

                <!-- Stock Info -->
                <div class="mb-3">
                    <span class="text-muted">Stok: </span>
                    <span
                        class="<?= $product['stock'] > 10 ? 'text-success' : ($product['stock'] > 0 ? 'text-warning' : 'text-danger') ?>">
                        <?= $product['stock'] ?> <?= $product['unit_name'] ?>
                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                        (Tersisa sedikit!)
                        <?php elseif ($product['stock'] == 0): ?>
                        (Habis)
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Category -->
                <div class="mb-3">
                    <span class="text-muted">Kategori: </span>
                    <a href="index.php?category=<?= $product['category_id'] ?>" class="text-decoration-none">
                        <?= $product['category_name'] ?>
                    </a>
                </div>

                <!-- Purchase Section -->
                <?php if ($product['stock'] > 0 && isLoggedIn() && !isAdmin()): ?>
                <div class="border p-3 rounded mb-3">
                    <h6>Jumlah Pembelian:</h6>
                    <div class="d-flex align-items-center mb-3">
                        <div class="quantity-selector d-flex">
                            <button class="btn btn-outline-secondary" onclick="changeQuantity(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="quantity" class="form-control text-center border-0" value="1"
                                min="1" max="<?= $product['stock'] ?>" style="width: 80px;">
                            <button class="btn btn-outline-secondary" onclick="changeQuantity(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <span class="ms-3 text-muted">Max: <?= $product['stock'] ?></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-add-cart btn-lg" onclick="addToCart()">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                        <button class="btn btn-buy-now btn-lg" onclick="buyNow()">
                            <i class="fas fa-bolt"></i> Beli Sekarang
                        </button>
                    </div>
                </div>
                <?php elseif ($product['stock'] == 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Produk sedang habis. Silakan hubungi kami untuk informasi restock.
                </div>
                <?php elseif (!isLoggedIn()): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <a href="login.php" class="text-decoration-none">Login</a>
                    untuk membeli produk ini.
                </div>
                <?php endif; ?>

                <!-- Contact -->
                <div class="border p-3 rounded">
                    <h6><i class="fas fa-headset text-info"></i> Butuh Bantuan?</h6>
                    <p class="small text-muted mb-2">
                        Tim customer service kami siap membantu dengan pertanyaan produk.
                    </p>
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                    <button class="btn btn-info btn-sm" onclick="openChat()">
                        <i class="fas fa-comments"></i> Chat Sekarang
                    </button>
                    <?php endif; ?>
                    <a href="tel:+6212345678901" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-phone"></i> Telepon
                    </a>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs product-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">
                            Deskripsi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">
                            Ulasan (<?= $rating_stats['total_reviews'] ?: 0 ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" id="description">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5>Deskripsi Produk</h5>
                                <div style="white-space: pre-line;">
                                    <?= htmlspecialchars($product['description']) ?>
                                </div>

                                <hr>

                                <h6>Spesifikasi:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td width="150">Kategori</td>
                                        <td><?= $product['category_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <td>Satuan</td>
                                        <td><?= $product['unit_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <td>Stok Tersedia</td>
                                        <td><?= $product['stock'] ?> unit</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews">
                        <div class="row">
                            <!-- Rating Summary -->
                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            <?= $rating_stats['total_reviews'] > 0 ? number_format($rating_stats['avg_rating'], 1) : '0.0' ?>
                                        </h3>
                                        <div class="rating-stars mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i
                                                class="<?= $i <= round($rating_stats['avg_rating']) ? 'fas' : 'far' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="text-muted"><?= $rating_stats['total_reviews'] ?> ulasan</p>

                                        <?php if ($rating_stats['total_reviews'] > 0): ?>
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="me-2"><?= $i ?> ⭐</span>
                                            <div class="rating-bar flex-grow-1 me-2">
                                                <div class="rating-fill"
                                                    style="width: <?= $rating_stats['total_reviews'] > 0 ? ($rating_stats['star_' . $i] / $rating_stats['total_reviews'] * 100) : 0 ?>%">
                                                </div>
                                            </div>
                                            <small><?= $rating_stats['star_' . $i] ?></small>
                                        </div>
                                        <?php endfor; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Reviews List -->
                            <div class="col-lg-8">
                                <!-- Write Review Form -->
                                <?php if (isLoggedIn() && !isAdmin()): ?>
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <h6><i class="fas fa-star text-warning"></i> Tulis Ulasan</h6>

                                        <?php if (isset($review_success)): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> <?= $review_success ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (isset($review_error)): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle"></i> <?= $review_error ?>
                                        </div>
                                        <?php endif; ?>

                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <div id="star-rating" class="star-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="far fa-star" data-rating="<?= $i ?>"
                                                        onclick="setRating(<?= $i ?>)"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <input type="hidden" name="rating" id="rating-input" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Komentar</label>
                                                <textarea class="form-control" name="comment" rows="3"
                                                    placeholder="Bagikan pengalaman Anda dengan produk ini..."
                                                    required></textarea>
                                            </div>
                                            <button type="submit" name="submit_review" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> Kirim Ulasan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Reviews List -->
                                <div class="reviews-list">
                                    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                    <div class="review-card p-3 mb-3 rounded">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong><?= htmlspecialchars($review['user_name']) ?></strong>
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i
                                                        class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted"><?= timeAgo($review['created_at']) ?></small>
                                        </div>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>

                                        <!-- Admin Reply -->
                                        <?php if ($review['admin_reply']): ?>
                                        <div class="admin-reply">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-reply text-primary me-2"></i>
                                                <strong class="text-primary">
                                                    Balasan dari <?= $review['admin_name'] ?: 'Admin' ?>
                                                </strong>
                                                <small class="text-muted ms-auto">
                                                    <?= timeAgo($review['admin_reply_date']) ?>
                                                </small>
                                            </div>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-comment-alt fa-3x mb-3"></i>
                                        <p>Belum ada ulasan untuk produk ini. Jadilah yang pertama!</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (mysqli_num_rows($related_result) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Produk Terkait</h4>
                <div class="row">
                    <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="position-relative">
                                <?php if ($related['has_discount']): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                    -<?= $related['discount_percentage'] ?>%
                                </span>
                                <?php endif; ?>
                                <img src="<?= BASE_URL . UPLOAD_PATH . ($related['image'] ?: 'no-image.jpg') ?>"
                                    class="card-img-top" style="height: 200px; object-fit: cover;"
                                    alt="<?= $related['name'] ?>">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?= substr($related['name'], 0, 50) ?>...</h6>
                                <div class="mt-auto">
                                    <?php if ($related['has_discount']): ?>
                                    <small class="text-decoration-line-through text-muted">
                                        <?= formatRupiah($related['price']) ?>
                                    </small><br>
                                    <strong class="text-danger"><?= formatRupiah($related['final_price']) ?></strong>
                                    <?php else: ?>
                                    <strong><?= formatRupiah($related['final_price']) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <a href="product_detail.php?id=<?= $related['id'] ?>"
                                    class="btn btn-outline-primary btn-sm mt-2">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Set global variables
    const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
    const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
    const CURRENT_PRODUCT_ID = <?= $product_id ?>;
    const BASE_URL = '<?= BASE_URL ?>';

    // Toast notification function
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');

        const toast = document.createElement('div');
        toast.className =
            `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    // Quantity controls
    function changeQuantity(change) {
        const quantityInput = document.getElementById('quantity');
        if (!quantityInput) return;

        const currentValue = parseInt(quantityInput.value) || 1;
        const newValue = currentValue + change;
        const maxValue = parseInt(quantityInput.getAttribute('max')) || 999;
        const minValue = parseInt(quantityInput.getAttribute('min')) || 1;

        if (newValue >= minValue && newValue <= maxValue) {
            quantityInput.value = newValue;

            // Add visual feedback
            quantityInput.style.backgroundColor = '#e8f5e8';
            setTimeout(() => {
                quantityInput.style.backgroundColor = '';
            }, 200);
        } else {
            // Show feedback for invalid quantity
            if (newValue > maxValue) {
                showToast(`Maksimal ${maxValue} unit`, 'error');
            } else if (newValue < minValue) {
                showToast(`Minimal ${minValue} unit`, 'error');
            }
        }
    }

    // Add to cart function
    function addToCart() {
        if (!IS_LOGGED_IN) {
            showToast('Silakan login terlebih dahulu', 'error');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }

        if (IS_ADMIN) {
            showToast('Admin tidak dapat menambahkan produk ke keranjang', 'error');
            return;
        }

        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;

        // Show loading state
        const button = document.querySelector('.btn-add-cart');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            button.disabled = true;

            fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${CURRENT_PRODUCT_ID}&quantity=${quantity}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');

                        // Update button text temporarily
                        button.innerHTML = '<i class="fas fa-check"></i> Berhasil Ditambahkan!';
                        button.className = 'btn btn-success btn-lg';

                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.className = 'btn btn-add-cart btn-lg';
                            button.disabled = false;
                        }, 2000);
                    } else {
                        showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }
    }

    // Buy now function
    function buyNow() {
        if (!IS_LOGGED_IN) {
            showToast('Silakan login terlebih dahulu', 'error');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }

        if (IS_ADMIN) {
            showToast('Admin tidak dapat melakukan pembelian', 'error');
            return;
        }

        const button = document.querySelector('.btn-buy-now');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            button.disabled = true;
        }

        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;

        fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${CURRENT_PRODUCT_ID}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Mengarahkan ke keranjang...', 'success');
                    setTimeout(() => {
                        window.location.href = 'cart.php';
                    }, 1000);
                } else {
                    showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
                    if (button) {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                if (button) {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
    }

    // Star rating function
    function setRating(rating) {
        document.getElementById('rating-input').value = rating;

        // Update visual stars
        document.querySelectorAll('#star-rating i').forEach((star, index) => {
            if (index < rating) {
                star.className = 'fas fa-star text-warning';
            } else {
                star.className = 'far fa-star';
            }
        });
    }

    // Validate quantity input
    function validateQuantity() {
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                const value = parseInt(this.value);
                const max = parseInt(this.getAttribute('max'));
                const min = parseInt(this.getAttribute('min')) || 1;

                if (value > max) {
                    this.value = max;
                    showToast(`Maksimal ${max} unit`, 'error');
                } else if (value < min) {
                    this.value = min;
                    showToast(`Minimal ${min} unit`, 'error');
                }
            });
        }
    }

    // Countdown timer for discount
    <?php if ($product['has_discount'] && $product['discount_end_date']): ?>

    function updateCountdown() {
        const endDate = new Date('<?= $product['discount_end_date'] ?> 23:59:59').getTime();
        const now = new Date().getTime();
        const distance = endDate - now;

        if (distance > 0) {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('countdown').innerHTML =
                `${days}h ${hours}j ${minutes}m ${seconds}d`;
        } else {
            document.getElementById('countdown').innerHTML = 'Diskon telah berakhir';
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();
    <?php endif; ?>

    // Initialize everything
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize quantity validation
        validateQuantity();

        // Auto-scroll to reviews if success message shown
        <?php if (isset($review_success)): ?>
        setTimeout(() => {
            document.querySelector('button[data-bs-target="#reviews"]').click();
        }, 1000);
        <?php endif; ?>

        // Debug info
        console.log('Login Status:', IS_LOGGED_IN);
        console.log('Admin Status:', IS_ADMIN);
        console.log('Product ID:', CURRENT_PRODUCT_ID);
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>