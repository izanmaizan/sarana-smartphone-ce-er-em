<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($status, $valid_statuses)) {
        $update_query = "UPDATE orders SET status = '$status' WHERE id = $order_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = 'Status pesanan berhasil diupdate';
        } else {
            $error = 'Gagal mengupdate status pesanan';
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build orders query
$orders_query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           COUNT(oi.id) as total_items
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

if (!empty($status_filter)) {
    $orders_query .= " AND o.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if (!empty($date_filter)) {
    $orders_query .= " AND DATE(o.order_date) = '" . mysqli_real_escape_string($conn, $date_filter) . "'";
}

if (!empty($search)) {
    $orders_query .= " AND (u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                      OR u.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                      OR o.id LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

$orders_query .= " GROUP BY o.id ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_orders,
        COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as total_revenue
    FROM orders
    WHERE DATE(order_date) = CURDATE()
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Sarana Smartphone</title>
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

    .status-select {
        min-width: 120px;
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
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i> Pesanan
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users me-2"></i> Pelanggan
                        </a>
                        <a class="nav-link" href="chats.php">
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
                            <h2 class="mb-0">Kelola Pesanan</h2>
                            <small class="text-muted">Pantau dan kelola semua pesanan pelanggan</small>
                        </div>
                        <div>
                            <span class="text-muted">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d F Y') ?>
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
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h4><?= $stats['total_orders'] ?></h4>
                            <p class="text-muted mb-0 small">Total Hari Ini</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #ffc107;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4><?= $stats['pending_orders'] ?></h4>
                            <p class="text-muted mb-0 small">Pending</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #17a2b8;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4><?= $stats['confirmed_orders'] ?></h4>
                            <p class="text-muted mb-0 small">Konfirmasi</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #007bff;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h4><?= $stats['shipped_orders'] ?></h4>
                            <p class="text-muted mb-0 small">Dikirim</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #28a745;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4><?= $stats['delivered_orders'] ?></h4>
                            <p class="text-muted mb-0 small">Selesai</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                                <i class="fas fa-rupiah-sign"></i>
                            </div>
                            <h6><?= formatRupiah($stats['total_revenue']) ?></h6>
                            <p class="text-muted mb-0 small">Omzet</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>
                                        Dikonfirmasi</option>
                                    <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Dikirim
                                    </option>
                                    <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>
                                        Selesai</option>
                                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>
                                        Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date" class="form-control" value="<?= $date_filter ?>">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari pesanan, nama, email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="row">
                    <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                    <div class="col-12 mb-3">
                        <div class="card order-card <?= $order['status'] ?> border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-lg-2 col-md-3">
                                        <h6 class="mb-1">Order #<?= $order['id'] ?></h6>
                                        <small class="text-muted">
                                            <?= date('d M Y H:i', strtotime($order['order_date'])) ?>
                                        </small>
                                    </div>

                                    <div class="col-lg-3 col-md-4">
                                        <h6 class="mb-1"><?= $order['customer_name'] ?></h6>
                                        <small class="text-muted">
                                            <?= $order['customer_email'] ?>
                                            <?php if ($order['customer_phone']): ?>
                                            <br><?= $order['customer_phone'] ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>

                                    <div class="col-lg-2 col-md-2">
                                        <h6 class="mb-0"><?= formatRupiah($order['total_amount']) ?></h6>
                                        <small class="text-muted"><?= $order['total_items'] ?> item</small>
                                    </div>

                                    <div class="col-lg-2 col-md-3">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" class="form-select form-select-sm status-select"
                                                onchange="this.form.submit()">
                                                <option value="pending"
                                                    <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending
                                                </option>
                                                <option value="confirmed"
                                                    <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>
                                                    Dikonfirmasi</option>
                                                <option value="shipped"
                                                    <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Dikirim
                                                </option>
                                                <option value="delivered"
                                                    <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Selesai
                                                </option>
                                                <option value="cancelled"
                                                    <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Dibatalkan
                                                </option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>

                                    <div class="col-lg-3 col-md-12 text-end">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-outline-primary btn-sm"
                                                onclick="viewOrderDetail(<?= $order['id'] ?>)">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                            <a href="tel:<?= $order['customer_phone'] ?>"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                            <button class="btn btn-outline-info btn-sm"
                                                onclick="printOrder(<?= $order['id'] ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
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
                                <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">Tidak Ada Pesanan</h4>
                                <p class="text-muted">
                                    <?php if (!empty($status_filter) || !empty($date_filter) || !empty($search)): ?>
                                    Tidak ada pesanan yang sesuai dengan filter yang dipilih.
                                    <?php else: ?>
                                    Belum ada pesanan masuk hari ini.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
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
    function viewOrderDetail(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        modal.show();

        document.getElementById('orderDetailContent').innerHTML =
            '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat...</div>';

        fetch(`../ajax/get_order_detail.php?id=${orderId}&admin=1`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetailContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('orderDetailContent').innerHTML =
                    '<div class="alert alert-danger">Gagal memuat detail pesanan.</div>';
            });
    }

    function printOrder(orderId) {
        window.open(`print_order.php?id=${orderId}`, '_blank');
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        const currentUrl = new URL(window.location);
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                // Update statistics only
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newStats = doc.querySelectorAll('.stat-card h4, .stat-card h6');
                const currentStats = document.querySelectorAll('.stat-card h4, .stat-card h6');

                newStats.forEach((stat, index) => {
                    if (currentStats[index] && stat.textContent !== currentStats[index]
                        .textContent) {
                        currentStats[index].textContent = stat.textContent;
                        currentStats[index].style.color = '#28a745';
                        setTimeout(() => {
                            currentStats[index].style.color = '';
                        }, 1000);
                    }
                });
            });
    }, 30000);

    // Confirmation for status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function(e) {
            const newStatus = this.value;
            const orderId = this.closest('form').querySelector('input[name="order_id"]').value;

            if (!confirm(`Ubah status pesanan #${orderId} menjadi "${newStatus}"?`)) {
                e.preventDefault();
                this.value = this.defaultValue;
                return false;
            }
        });
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>