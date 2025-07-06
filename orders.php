<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build orders query
$orders_query = "
    SELECT o.*, 
           COUNT(oi.id) as total_items,
           GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = $user_id
";

if (!empty($status_filter)) {
    $orders_query .= " AND o.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

$orders_query .= " GROUP BY o.id ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f8f9fa;
        padding-top: 80px;
    }

    .order-card {
        transition: transform 0.3s;
        border-left: 4px solid #e9ecef;
    }

    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .order-card.pending {
        border-left-color: #ffc107;
    }

    .order-card.confirmed {
        border-left-color: #17a2b8;
    }

    .order-card.shipped {
        border-left-color: #007bff;
    }

    .order-card.delivered {
        border-left-color: #28a745;
    }

    .order-card.cancelled {
        border-left-color: #dc3545;
    }

    .status-badge {
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
    }

    .order-timeline {
        position: relative;
    }

    .timeline-step {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .timeline-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 0.8rem;
    }

    .timeline-icon.completed {
        background: #28a745;
        color: white;
    }

    .timeline-icon.current {
        background: #ffc107;
        color: white;
    }

    .timeline-icon.pending {
        background: #e9ecef;
        color: #6c757d;
    }

    .empty-orders {
        text-align: center;
        color: #6c757d;
        padding: 3rem 0;
    }

    .breadcrumb-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-top: -80px;
        padding-top: 100px;
    }

    .breadcrumb-section .breadcrumb {
        background: none;
        margin-bottom: 0;
    }

    .breadcrumb-section .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }

    .breadcrumb-section .breadcrumb-item.active {
        color: white;
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
                    <li class="breadcrumb-item active">Pesanan Saya</li>
                </ol>
            </nav>
            <h1 class="h2 mb-0">
                <i class="fas fa-shopping-bag me-2"></i>
                Pesanan Saya
            </h1>
        </div>
    </section>

    <div class="container py-4">
        <!-- Filter Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?= empty($status_filter) ? 'active' : '' ?>" href="orders.php">
                            <i class="fas fa-list"></i> Semua
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'pending' ? 'active' : '' ?>"
                            href="orders.php?status=pending">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'confirmed' ? 'active' : '' ?>"
                            href="orders.php?status=confirmed">
                            <i class="fas fa-check"></i> Dikonfirmasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'shipped' ? 'active' : '' ?>"
                            href="orders.php?status=shipped">
                            <i class="fas fa-truck"></i> Dikirim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'delivered' ? 'active' : '' ?>"
                            href="orders.php?status=delivered">
                            <i class="fas fa-check-circle"></i> Selesai
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'cancelled' ? 'active' : '' ?>"
                            href="orders.php?status=cancelled">
                            <i class="fas fa-times-circle"></i> Dibatalkan
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (mysqli_num_rows($orders_result) > 0): ?>
        <div class="row">
            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
            <div class="col-12 mb-4">
                <div class="card order-card <?= $order['status'] ?> border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-2 col-md-3">
                                <h6 class="mb-1">Order #<?= $order['id'] ?></h6>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($order['order_date'])) ?>
                                </small>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <p class="mb-1">
                                    <strong><?= $order['total_items'] ?> Item</strong>
                                </p>
                                <small class="text-muted">
                                    <?= strlen($order['product_names']) > 50 ? 
                                                substr($order['product_names'], 0, 50) . '...' : 
                                                $order['product_names'] ?>
                                </small>
                            </div>

                            <div class="col-lg-2 col-md-3">
                                <h6 class="mb-0"><?= formatRupiah($order['total_amount']) ?></h6>
                                <small class="text-muted">Total</small>
                            </div>

                            <div class="col-lg-2 col-md-2">
                                <span class="badge status-badge bg-<?= 
                                            $order['status'] == 'pending' ? 'warning' : 
                                            ($order['status'] == 'confirmed' ? 'info' : 
                                            ($order['status'] == 'shipped' ? 'primary' : 
                                            ($order['status'] == 'delivered' ? 'success' : 'danger'))) 
                                        ?>">
                                    <?php
                                            $status_text = [
                                                'pending' => 'Pending',
                                                'confirmed' => 'Dikonfirmasi',
                                                'shipped' => 'Dikirim',
                                                'delivered' => 'Selesai',
                                                'cancelled' => 'Dibatalkan'
                                            ];
                                            echo $status_text[$order['status']];
                                            ?>
                                </span>
                            </div>

                            <div class="col-lg-2 col-md-12 text-end">
                                <button class="btn btn-outline-primary btn-sm"
                                    onclick="viewOrderDetail(<?= $order['id'] ?>)">
                                    <i class="fas fa-eye"></i> Detail
                                </button>

                                <?php if ($order['status'] == 'pending'): ?>
                                <button class="btn btn-outline-danger btn-sm mt-1"
                                    onclick="cancelOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-times"></i> Batal
                                </button>
                                <?php endif; ?>

                                <?php if ($order['status'] == 'delivered'): ?>
                                <a href="product_detail.php?id=<?= $order['id'] ?>#reviews"
                                    class="btn btn-outline-warning btn-sm mt-1">
                                    <i class="fas fa-star"></i> Ulasan
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="order-timeline">
                                    <div class="d-flex justify-content-between">
                                        <div class="timeline-step">
                                            <div class="timeline-icon completed">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <small class="fw-bold">Pesanan Dibuat</small>
                                                <br><small
                                                    class="text-muted"><?= date('d M Y H:i', strtotime($order['order_date'])) ?></small>
                                            </div>
                                        </div>

                                        <div class="timeline-step">
                                            <div
                                                class="timeline-icon <?= in_array($order['status'], ['confirmed', 'shipped', 'delivered']) ? 'completed' : ($order['status'] == 'pending' ? 'current' : 'pending') ?>">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <small class="fw-bold">Dikonfirmasi</small>
                                            </div>
                                        </div>

                                        <div class="timeline-step">
                                            <div
                                                class="timeline-icon <?= in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : ($order['status'] == 'confirmed' ? 'current' : 'pending') ?>">
                                                <i class="fas fa-truck"></i>
                                            </div>
                                            <div>
                                                <small class="fw-bold">Dikirim</small>
                                            </div>
                                        </div>

                                        <div class="timeline-step">
                                            <div
                                                class="timeline-icon <?= $order['status'] == 'delivered' ? 'completed' : ($order['status'] == 'shipped' ? 'current' : 'pending') ?>">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div>
                                                <small class="fw-bold">Diterima</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag fa-5x mb-4"></i>
                    <h4>Belum Ada Pesanan</h4>
                    <p class="mb-4">
                        <?php if (!empty($status_filter)): ?>
                        Tidak ada pesanan dengan status "<?= ucfirst($status_filter) ?>".
                        <?php else: ?>
                        Anda belum pernah melakukan pemesanan. Mulai berbelanja sekarang!
                        <?php endif; ?>
                    </p>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Mulai Belanja
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle text-info fa-3x mb-3"></i>
                        <h5>Butuh Bantuan?</h5>
                        <p class="text-muted">
                            Tim customer service kami siap membantu dengan pertanyaan pesanan Anda.
                        </p>
                        <button class="btn btn-info" onclick="openChat()">
                            <i class="fas fa-comments"></i> Chat Customer Service
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-truck text-success fa-3x mb-3"></i>
                        <h5>Informasi Pengiriman</h5>
                        <ul class="text-muted text-start">
                            <li>Pengiriman gratis untuk semua area</li>
                            <li>Estimasi pengiriman: 1-3 hari kerja</li>
                            <li>Tracking tersedia setelah barang dikirim</li>
                            <li>Kemasan aman dan terpercaya</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Set global variables
    const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
    const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
    const BASE_URL = '<?= BASE_URL ?>';

    function viewOrderDetail(orderId) {
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        modal.show();

        // Load order detail
        document.getElementById('orderDetailContent').innerHTML =
            '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat...</div>';

        fetch(`ajax/get_order_detail.php?id=${orderId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetailContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('orderDetailContent').innerHTML =
                    '<div class="alert alert-danger">Gagal memuat detail pesanan.</div>';
            });
    }

    function cancelOrder(orderId) {
        if (confirm('Yakin ingin membatalkan pesanan ini?')) {
            fetch('ajax/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Pesanan berhasil dibatalkan');
                        location.reload();
                    } else {
                        alert('Gagal membatalkan pesanan: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat membatalkan pesanan');
                });
        }
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>