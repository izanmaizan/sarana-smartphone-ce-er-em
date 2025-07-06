<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Get dashboard statistics
$stats = [];

// Total products
$products_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$products_result = mysqli_query($conn, $products_query);
$stats['total_products'] = mysqli_fetch_assoc($products_result)['total'];

// Total orders today
$orders_today_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = CURDATE()";
$orders_today_result = mysqli_query($conn, $orders_today_query);
$stats['orders_today'] = mysqli_fetch_assoc($orders_today_result)['total'];

// Total customers
$customers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$customers_result = mysqli_query($conn, $customers_query);
$stats['total_customers'] = mysqli_fetch_assoc($customers_result)['total'];

// Revenue this month
$revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                 WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) 
                 AND status != 'cancelled'";
$revenue_result = mysqli_query($conn, $revenue_query);
$stats['monthly_revenue'] = mysqli_fetch_assoc($revenue_result)['total'];

// Pending orders
$pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$pending_orders_result = mysqli_query($conn, $pending_orders_query);
$stats['pending_orders'] = mysqli_fetch_assoc($pending_orders_result)['total'];

// Unread chats
$unread_chats_query = "SELECT COUNT(DISTINCT user_id) as total FROM chats WHERE status = 'unread' AND sender_type = 'customer'";
$unread_chats_result = mysqli_query($conn, $unread_chats_query);
$stats['unread_chats'] = mysqli_fetch_assoc($unread_chats_result)['total'];

// Low stock products
$low_stock_query = "SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND status = 'active'";
$low_stock_result = mysqli_query($conn, $low_stock_query);
$stats['low_stock'] = mysqli_fetch_assoc($low_stock_result)['total'];

// Pending reviews
$pending_reviews_query = "SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'";
$pending_reviews_result = mysqli_query($conn, $pending_reviews_query);
$stats['pending_reviews'] = mysqli_fetch_assoc($pending_reviews_result)['total'];

// Recent orders
$recent_orders_query = "
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 10
";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);

// Top selling products
$top_products_query = "
    SELECT p.name, SUM(oi.quantity) as total_sold, p.image
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id, p.name, p.image
    ORDER BY total_sold DESC
    LIMIT 5
";
$top_products_result = mysqli_query($conn, $top_products_query);

// Sales chart data (last 7 days)
$sales_chart_query = "
    SELECT DATE(order_date) as date, COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    AND status != 'cancelled'
    GROUP BY DATE(order_date)
    ORDER BY date ASC
";
$sales_chart_result = mysqli_query($conn, $sales_chart_query);
$sales_data = [];
while ($row = mysqli_fetch_assoc($sales_chart_result)) {
    $sales_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sarana Smartphone CRM</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

    .card-stats {
        transition: transform 0.3s;
    }

    .card-stats:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }

    .brand-title {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: bold;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box me-2"></i> Produk
                            <?php if ($stats['low_stock'] > 0): ?>
                            <span class="notification-badge"><?= $stats['low_stock'] ?></span>
                            <?php endif; ?>
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
                            <?php if ($stats['pending_orders'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_orders'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users me-2"></i> Pelanggan
                        </a>
                        <a class="nav-link" href="chats.php">
                            <i class="fas fa-comments me-2"></i> Chat
                            <?php if ($stats['unread_chats'] > 0): ?>
                            <span class="notification-badge"><?= $stats['unread_chats'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="reviews.php">
                            <i class="fas fa-star me-2"></i> Ulasan
                            <?php if ($stats['pending_reviews'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_reviews'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="stock.php">
                            <i class="fas fa-warehouse me-2"></i> Stok
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Laporan
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="../index.php">
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
                            <h2 class="brand-title mb-0">Dashboard Admin</h2>
                            <small class="text-muted">Selamat datang, <?= $_SESSION['name'] ?>!</small>
                        </div>
                        <div>
                            <span class="text-muted">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d F Y, H:i') ?> WIB
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card card-stats shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-gradient-primary me-3">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Total Produk</p>
                                        <h4 class="mb-0"><?= number_format($stats['total_products']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card card-stats shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-gradient-success me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Pesanan Hari Ini</p>
                                        <h4 class="mb-0"><?= number_format($stats['orders_today']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card card-stats shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-gradient-info me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Total Pelanggan</p>
                                        <h4 class="mb-0"><?= number_format($stats['total_customers']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card card-stats shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-gradient-warning me-3">
                                        <i class="fas fa-rupiah-sign"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0">Omzet Bulan Ini</p>
                                        <h4 class="mb-0"><?= formatRupiah($stats['monthly_revenue']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Cards -->
                <div class="row mb-4">
                    <?php if ($stats['pending_orders'] > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                                <h5><?= $stats['pending_orders'] ?></h5>
                                <small>Pesanan Pending</small>
                                <a href="orders.php?status=pending"
                                    class="btn btn-warning btn-sm d-block mt-2">Lihat</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($stats['unread_chats'] > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-comments text-info fa-2x mb-2"></i>
                                <h5><?= $stats['unread_chats'] ?></h5>
                                <small>Chat Belum Dibaca</small>
                                <a href="chats.php" class="btn btn-info btn-sm d-block mt-2">Balas</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($stats['low_stock'] > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                                <h5><?= $stats['low_stock'] ?></h5>
                                <small>Stok Menipis</small>
                                <a href="products.php?filter=low_stock"
                                    class="btn btn-danger btn-sm d-block mt-2">Cek</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($stats['pending_reviews'] > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body text-center">
                                <i class="fas fa-star text-secondary fa-2x mb-2"></i>
                                <h5><?= $stats['pending_reviews'] ?></h5>
                                <small>Ulasan Pending</small>
                                <a href="reviews.php?status=pending"
                                    class="btn btn-secondary btn-sm d-block mt-2">Review</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Charts and Tables -->
                <div class="row">
                    <!-- Sales Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line text-primary me-2"></i>
                                    Penjualan 7 Hari Terakhir
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-trophy text-warning me-2"></i>
                                    Produk Terlaris
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (mysqli_num_rows($top_products_result) > 0): ?>
                                    <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                    <div class="list-group-item d-flex align-items-center">
                                        <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                                            class="rounded me-3" width="40" height="40" style="object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?= substr($product['name'], 0, 25) ?>...</h6>
                                            <small class="text-muted"><?= $product['total_sold'] ?> terjual</small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <div class="list-group-item text-center text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                        <p class="mb-0">Belum ada data penjualan</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-info me-2"></i>
                            Pesanan Terbaru
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($recent_orders_result) > 0): ?>
                                    <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                    <tr>
                                        <td><strong>#<?= $order['id'] ?></strong></td>
                                        <td><?= $order['customer_name'] ?></td>
                                        <td><?= formatRupiah($order['total_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                    $order['status'] == 'pending' ? 'warning' : 
                                                    ($order['status'] == 'confirmed' ? 'info' : 
                                                    ($order['status'] == 'shipped' ? 'primary' : 
                                                    ($order['status'] == 'delivered' ? 'success' : 'danger'))) 
                                                ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <a href="orders.php?view=<?= $order['id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                            <p class="mb-0">Belum ada pesanan</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?= json_encode($sales_data) ?>;

    const labels = salesData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short'
        });
    });

    const revenues = salesData.map(item => parseFloat(item.revenue));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Omzet (Rp)',
                data: revenues,
                borderColor: 'rgba(102, 126, 234, 1)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Auto refresh notifications every 30 seconds
    setInterval(function() {
        fetch('ajax/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                // Update notification badges
                updateBadge('pending_orders', data.pending_orders);
                updateBadge('unread_chats', data.unread_chats);
                updateBadge('low_stock', data.low_stock);
                updateBadge('pending_reviews', data.pending_reviews);
            })
            .catch(error => {
                console.log('Failed to fetch notifications:', error);
            });
    }, 30000);

    function updateBadge(type, count) {
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            const link = badge.closest('a');
            if (link && link.href.includes(type.replace('_', ''))) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>