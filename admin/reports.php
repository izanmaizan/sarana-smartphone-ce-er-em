<?php
// admin/reports.php - Fixed GROUP BY compatibility
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Generate date range based on period
switch ($period) {
    case 'daily':
        $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        $date_format = '%Y-%m-%d';
        break;
    case 'monthly':
        $start_date = "$year-01-01";
        $end_date = "$year-12-31";
        $date_format = '%Y-%m';
        break;
    case 'yearly':
        $start_year = $year - 4;
        $start_date = "$start_year-01-01";
        $end_date = "$year-12-31";
        $date_format = '%Y';
        break;
    default:
        $start_date = "$year-01-01";
        $end_date = "$year-12-31";
        $date_format = '%Y-%m';
        break;
}

// Sales data query - Fixed GROUP BY and ORDER BY to use same expression
$sales_query = "
    SELECT 
        DATE_FORMAT(order_date, '$date_format') as period,
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value
    FROM orders 
    WHERE order_date BETWEEN '$start_date' AND '$end_date' 
    AND status != 'cancelled'
    GROUP BY DATE_FORMAT(order_date, '$date_format')
    ORDER BY period
";
$sales_result = mysqli_query($conn, $sales_query);
$sales_data = [];
while ($row = mysqli_fetch_assoc($sales_result)) {
    $sales_data[] = $row;
}

// Top products query - Already properly structured
$top_products_query = "
    SELECT 
        p.id,
        p.name as product_name,
        p.image,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    AND o.status != 'cancelled'
    GROUP BY p.id, p.name, p.image
    ORDER BY total_sold DESC
    LIMIT 10
";
$top_products_result = mysqli_query($conn, $top_products_query);

// Summary statistics - No GROUP BY needed
$summary_query = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value,
        COUNT(DISTINCT user_id) as unique_customers
    FROM orders 
    WHERE order_date BETWEEN '$start_date' AND '$end_date'
    AND status != 'cancelled'
";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Customer statistics - Already properly structured
$customer_stats_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    INNER JOIN orders o ON u.id = o.user_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    AND o.status != 'cancelled'
    GROUP BY u.id, u.name, u.email
    ORDER BY total_spent DESC
    LIMIT 10
";
$customer_stats_result = mysqli_query($conn, $customer_stats_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Admin Sarana Smartphone</title>
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

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        transition: transform 0.3s;
        border: 1px solid #e9ecef;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 1.5rem;
        color: white;
    }

    .chart-container {
        position: relative;
        height: 400px;
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .top-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #007bff;
    }

    .print-section {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    @media print {

        .sidebar,
        .no-print {
            display: none !important;
        }

        .col-md-9,
        .col-lg-10 {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0 no-print">
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
                        <a class="nav-link" href="reviews.php">
                            <i class="fas fa-star me-2"></i> Ulasan
                        </a>
                        <a class="nav-link" href="stock.php">
                            <i class="fas fa-warehouse me-2"></i> Stok
                        </a>
                        <a class="nav-link active" href="reports.php">
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
                <div class="bg-white shadow-sm p-3 mb-4 no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Laporan Penjualan</h2>
                            <small class="text-muted">Analisis performa penjualan dan statistik bisnis</small>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary me-2" onclick="exportReport()">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <button class="btn btn-primary" onclick="printReport()">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="card mb-4 border-0 shadow-sm no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Periode</label>
                                <select name="period" class="form-select" onchange="toggleDateInputs()">
                                    <option value="daily" <?= $period == 'daily' ? 'selected' : '' ?>>Harian</option>
                                    <option value="monthly" <?= $period == 'monthly' ? 'selected' : '' ?>>Bulanan
                                    </option>
                                    <option value="yearly" <?= $period == 'yearly' ? 'selected' : '' ?>>Tahunan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tahun</label>
                                <select name="year" class="form-select">
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2" id="monthSelect"
                                style="<?= $period == 'yearly' ? 'display:none' : '' ?>">
                                <label class="form-label">Bulan</label>
                                <select name="month" class="form-select">
                                    <?php 
                                    $months = [
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                    ];
                                    foreach ($months as $num => $name): 
                                    ?>
                                    <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="fas fa-sync-alt"></i> Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="print-section">
                    <!-- Summary Statistics -->
                    <div class="row mb-5">
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h3><?= number_format($summary['total_orders']) ?></h3>
                                <p class="text-muted mb-0">Total Pesanan</p>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                                    <i class="fas fa-rupiah-sign"></i>
                                </div>
                                <h3><?= formatRupiah($summary['total_revenue']) ?></h3>
                                <p class="text-muted mb-0">Total Omzet</p>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h3><?= formatRupiah($summary['avg_order_value']) ?></h3>
                                <p class="text-muted mb-0">Rata-rata Order</p>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3><?= number_format($summary['unique_customers']) ?></h3>
                                <p class="text-muted mb-0">Pelanggan Unik</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Chart -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="mb-4">
                                    <i class="fas fa-chart-line text-primary"></i>
                                    Tren Penjualan <?= ucfirst($period) ?>
                                    <?php if ($period == 'daily'): ?>
                                    - <?= $months[$month] ?> <?= $year ?>
                                    <?php elseif ($period == 'monthly'): ?>
                                    - <?= $year ?>
                                    <?php endif; ?>
                                </h5>
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products and Customers -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="print-section">
                                <h5 class="mb-4">
                                    <i class="fas fa-trophy text-warning"></i>
                                    Produk Terlaris
                                </h5>
                                <?php if (mysqli_num_rows($top_products_result) > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                <div class="top-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="badge bg-primary">#<?= $rank ?></span>
                                        </div>
                                        <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                                            class="rounded me-3" width="40" height="40" style="object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= $product['product_name'] ?></h6>
                                            <small class="text-muted">
                                                <?= $product['total_sold'] ?> terjual •
                                                <?= formatRupiah($product['total_revenue']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php $rank++; ?>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada data penjualan produk pada periode ini</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="print-section">
                                <h5 class="mb-4">
                                    <i class="fas fa-crown text-success"></i>
                                    Pelanggan Terbaik
                                </h5>
                                <?php if (mysqli_num_rows($customer_stats_result) > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while ($customer = mysqli_fetch_assoc($customer_stats_result)): ?>
                                <div class="top-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="badge bg-success">#<?= $rank ?></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= $customer['name'] ?></h6>
                                            <small class="text-muted">
                                                <?= $customer['order_count'] ?> pesanan •
                                                <?= formatRupiah($customer['total_spent']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php $rank++; ?>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada data pelanggan pada periode ini</p>
                                </div>
                                <?php endif; ?>
                            </div>
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
        const period = '<?= $period ?>';
        if (period === 'daily') {
            return new Date(item.period).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short'
            });
        } else if (period === 'monthly') {
            const [year, month] = item.period.split('-');
            return new Date(year, month - 1).toLocaleDateString('id-ID', {
                month: 'short',
                year: 'numeric'
            });
        } else {
            return item.period;
        }
    });

    const revenues = salesData.map(item => parseFloat(item.total_revenue));
    const orders = salesData.map(item => parseInt(item.total_orders));

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
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Jumlah Pesanan',
                data: orders,
                borderColor: 'rgba(86, 171, 47, 1)',
                backgroundColor: 'rgba(86, 171, 47, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' pesanan';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Omzet: Rp ' + context.raw.toLocaleString('id-ID');
                            } else {
                                return 'Pesanan: ' + context.raw;
                            }
                        }
                    }
                }
            }
        }
    });

    function toggleDateInputs() {
        const period = document.querySelector('select[name="period"]').value;
        const monthSelect = document.getElementById('monthSelect');

        if (period === 'yearly') {
            monthSelect.style.display = 'none';
        } else {
            monthSelect.style.display = 'block';
        }
    }

    function printReport() {
        window.print();
    }

    function exportReport() {
        // Simple CSV export
        const data = <?= json_encode($sales_data) ?>;
        let csv = 'Periode,Total Pesanan,Total Omzet,Rata-rata Order\n';

        data.forEach(row => {
            csv += `${row.period},${row.total_orders},${row.total_revenue},${row.avg_order_value}\n`;
        });

        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'laporan_penjualan_<?= date('Y-m-d') ?>.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>