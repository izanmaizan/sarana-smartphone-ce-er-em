<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    redirect('orders.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get order details
$order_query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id AND o.user_id = $user_id
";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    redirect('orders.php');
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding-top: 76px;
    }

    .success-page {
        padding: 1.5rem 0;
    }

    .success-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        max-width: 100%;
    }

    .success-header {
        background: #e9ecef;
        color: #495057;
        text-align: center;
        padding: 2rem 1.5rem 1.5rem;
    }

    .success-icon {
        width: 60px;
        height: 60px;
        background: #28a745;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem;
        color: white;
    }

    .main-content {
        padding: 1.5rem;
    }

    .order-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-item {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .info-icon {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }

    .info-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }

    .items-section {
        margin-bottom: 1.5rem;
    }

    .items-container {
        max-height: 200px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }

    .item-row {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background: white;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        border: 1px solid #e9ecef;
    }

    .item-row:last-child {
        margin-bottom: 0;
    }

    .item-image {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
        margin-right: 0.75rem;
    }

    .item-details {
        flex-grow: 1;
    }

    .item-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .item-price {
        color: #6c757d;
        font-size: 0.8rem;
    }

    .actions-section {
        border-top: 1px solid #dee2e6;
        padding-top: 1.5rem;
    }

    .btn-simple {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        transition: all 0.2s ease;
        margin: 0.25rem;
    }

    .btn-simple:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
        color: #495057;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-primary-simple {
        background: #495057;
        color: white;
        border-color: #495057;
    }

    .btn-primary-simple:hover {
        background: #343a40;
        border-color: #343a40;
        color: white;
    }

    .next-steps {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
    }

    .step {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background: white;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }

    .step-number {
        width: 24px;
        height: 24px;
        background: #6c757d;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 0.75rem;
        font-size: 0.75rem;
    }

    .step.completed .step-number {
        background: #28a745;
    }

    .step-text {
        font-size: 0.85rem;
    }

    .step-title {
        font-weight: 600;
        margin-bottom: 0.15rem;
    }

    .step-desc {
        color: #6c757d;
        font-size: 0.75rem;
    }

    .contact-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        font-size: 0.85rem;
        margin-top: 1rem;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }

        .success-page {
            padding: 1rem 0;
        }

        .success-header {
            padding: 1.5rem 1rem 1rem;
        }

        .main-content {
            padding: 1rem;
        }

        .info-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .steps-grid {
            grid-template-columns: 1fr;
        }

        .success-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .btn-simple {
            display: block;
            width: 100%;
            margin: 0.25rem 0;
            text-align: center;
        }

        .items-container {
            max-height: 150px;
        }
    }

    @media (max-width: 576px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .info-item {
            padding: 0.75rem;
        }
    }
    </style>
</head>

<body>
    <!-- Include navbar -->
    <?php include 'navbar.php'; ?>

    <div class="success-page">
        <div class="container-fluid px-3">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="success-content">
                        <!-- Success Header -->
                        <div class="success-header">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h1 class="h4 mb-2">Pesanan Berhasil Dibuat!</h1>
                            <p class="mb-0 text-muted">Terima kasih telah berbelanja di Sarana Smartphone</p>
                        </div>

                        <!-- Main Content -->
                        <div class="main-content">
                            <!-- Order Information -->
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div class="info-label">ID Pesanan</div>
                                    <div class="info-value">#<?= $order['id'] ?></div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="info-label">Total Pembayaran</div>
                                    <div class="info-value text-success"><?= formatRupiah($order['total_amount']) ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="info-label">Tanggal Pesanan</div>
                                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        <span class="badge bg-warning">Menunggu Konfirmasi</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Steps -->
                            <div class="next-steps">
                                <h6 class="mb-3">
                                    <i class="fas fa-list-check me-2"></i>
                                    Langkah Selanjutnya:
                                </h6>

                                <div class="steps-grid">
                                    <div class="step completed">
                                        <div class="step-number">1</div>
                                        <div class="step-text">
                                            <div class="step-title">Pesanan Diterima</div>
                                            <div class="step-desc">Berhasil masuk sistem</div>
                                        </div>
                                    </div>

                                    <div class="step">
                                        <div class="step-number">2</div>
                                        <div class="step-text">
                                            <div class="step-title">Konfirmasi Admin</div>
                                            <div class="step-desc">Dalam 1-2 jam</div>
                                        </div>
                                    </div>

                                    <div class="step">
                                        <div class="step-number">3</div>
                                        <div class="step-text">
                                            <div class="step-title">Hubungi Anda</div>
                                            <div class="step-desc">Proses pembayaran</div>
                                        </div>
                                    </div>

                                    <div class="step">
                                        <div class="step-number">4</div>
                                        <div class="step-text">
                                            <div class="step-title">Pengiriman</div>
                                            <div class="step-desc">Setelah pembayaran</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="items-section">
                                <h6 class="mb-3">
                                    <i class="fas fa-box me-2"></i>
                                    Item Pesanan (<?= mysqli_num_rows($items_result) ?> produk):
                                </h6>

                                <div class="items-container">
                                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                    <div class="item-row">
                                        <img src="<?= BASE_URL . UPLOAD_PATH . ($item['image'] ?: 'no-image.jpg') ?>"
                                            class="item-image" alt="<?= $item['product_name'] ?>">
                                        <div class="item-details">
                                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <div class="item-price">
                                                <?= $item['quantity'] ?> × <?= formatRupiah($item['price']) ?> =
                                                <strong><?= formatRupiah($item['quantity'] * $item['price']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="actions-section">
                                <div class="row">
                                    <div class="col-md-4">
                                        <a href="orders.php" class="btn btn-primary-simple btn-simple w-100">
                                            <i class="fas fa-list-alt me-2"></i>
                                            Lihat Semua Pesanan
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="index.php" class="btn btn-simple w-100">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Lanjut Berbelanja
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-simple w-100" onclick="openChat()">
                                            <i class="fas fa-comments me-2"></i>
                                            Chat Customer Service
                                        </button>
                                    </div>
                                </div>

                                <!-- Contact Info -->
                                <div class="contact-info">
                                    <i class="fas fa-phone me-1"></i>
                                    <strong>+62 123 456 7890</strong> •
                                    <i class="fas fa-envelope me-1"></i>
                                    <strong>support@sarana-smartphone.com</strong>
                                    <br>
                                    <small>Tim Customer Service kami siap membantu Anda 24/7</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function openChat() {
        if (typeof openChatModal === 'function') {
            openChatModal();
        } else {
            window.location.href = '<?= BASE_URL ?>chat.php';
        }
    }

    // Auto redirect prompt after 45 seconds
    setTimeout(() => {
        if (confirm('Ingin melihat detail semua pesanan Anda?')) {
            window.location.href = '<?= BASE_URL ?>orders.php';
        }
    }, 45000);

    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Order success page loaded - Order #<?= $order['id'] ?>');
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>