<?php
// admin/reports.php - Dashboard dengan Multiple Charts
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Define months array for Indonesian names
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

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

// Sales data query
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

// Top products query
$top_products_query = "
    SELECT 
        p.id,
        p.name as product_name,
        p.image,
        p.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN products p ON oi.product_id = p.id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    AND o.status != 'cancelled'
    GROUP BY p.id, p.name, p.image, p.price
    ORDER BY total_sold DESC
    LIMIT 15
";
$top_products_result = mysqli_query($conn, $top_products_query);

// Summary statistics
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

// Customer statistics
$customer_stats_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.phone,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    INNER JOIN orders o ON u.id = o.user_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    AND o.status != 'cancelled'
    GROUP BY u.id, u.name, u.email, u.phone
    ORDER BY total_spent DESC
    LIMIT 15
";
$customer_stats_result = mysqli_query($conn, $customer_stats_query);

// Status orders breakdown
$status_query = "
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY status
    ORDER BY count DESC
";
$status_result = mysqli_query($conn, $status_query);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[] = $row;
}

// Category sales data
$category_query = "
    SELECT 
        c.name as category_name,
        COUNT(DISTINCT p.id) as products_count,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    INNER JOIN products p ON oi.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    AND o.status != 'cancelled'
    GROUP BY c.id, c.name
    ORDER BY total_revenue DESC
";
$category_result = mysqli_query($conn, $category_query);
$category_data = [];
while ($row = mysqli_fetch_assoc($category_result)) {
    $category_data[] = $row;
}

// Comparison data based on selected period
if ($period == 'daily') {
    // Daily comparison for selected month
    $comparison_query = "
        SELECT 
            DATE_FORMAT(order_date, '%Y-%m-%d') as period,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE order_date BETWEEN '$start_date' AND '$end_date'
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(order_date, '%Y-%m-%d')
        ORDER BY period
    ";
    $comparison_title = "Trend Harian - " . (isset($months[$month]) ? $months[$month] : 'Bulan ' . $month) . " " . $year;
} elseif ($period == 'monthly') {
    // Monthly comparison for selected year
    $comparison_query = "
        SELECT 
            DATE_FORMAT(order_date, '%Y-%m') as period,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE order_date BETWEEN '$start_date' AND '$end_date'
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY period
    ";
    $comparison_title = "Trend Bulanan - " . $year;
} else {
    // Yearly comparison (last 5 years)
    $comparison_query = "
        SELECT 
            DATE_FORMAT(order_date, '%Y') as period,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE order_date BETWEEN '$start_date' AND '$end_date'
        AND status != 'cancelled'
        GROUP BY DATE_FORMAT(order_date, '%Y')
        ORDER BY period
    ";
    $comparison_title = "Trend Tahunan (" . ($year-4) . " - " . $year . ")";
}

$comparison_result = mysqli_query($conn, $comparison_query);
$comparison_data = [];
while ($row = mysqli_fetch_assoc($comparison_result)) {
    $comparison_data[] = $row;
}

// Calculate growth rate based on period
$prev_period_revenue = 0;
$current_period_revenue = 0;
$growth_rate = 0;

if (count($comparison_data) >= 2) {
    $current_period_revenue = end($comparison_data)['revenue'] ?? 0;
    $prev_period_revenue = $comparison_data[count($comparison_data)-2]['revenue'] ?? 0;
    
    if ($prev_period_revenue > 0) {
        $growth_rate = (($current_period_revenue - $prev_period_revenue) / $prev_period_revenue) * 100;
    }
}

// Stock alerts
$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM products WHERE stock <= 5 AND status = 'active'";
$low_stock_result = mysqli_query($conn, $low_stock_query);
$low_stock = mysqli_fetch_assoc($low_stock_result)['low_stock_count'];
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
        border-radius: 10px;
        padding: 1.2rem;
        text-align: center;
        transition: transform 0.2s;
        border: 1px solid #e9ecef;
        height: 100%;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.8rem;
        font-size: 1.2rem;
        color: white;
    }

    .chart-container {
        position: relative;
        height: 300px;
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .chart-container.small {
        height: 250px;
    }

    .top-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.8rem;
        margin-bottom: 0.8rem;
        border-left: 3px solid #007bff;
    }

    .metric-card {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .growth-positive {
        color: #28a745;
    }

    .growth-negative {
        color: #dc3545;
    }

    .alert-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Print Styles */
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

        body {
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 15px;
        }

        .screen-content {
            display: none !important;
        }

        .print-content {
            display: block !important;
        }

        .print-header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .company-logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .company-info {
            font-size: 11px;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 8px 0;
            text-transform: uppercase;
        }

        .report-period {
            font-size: 12px;
            margin-bottom: 15px;
        }

        .summary-box {
            border: 2px solid #000;
            background: #f5f5f5 !important;
            page-break-inside: avoid;
            margin-bottom: 15px;
            padding: 15px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
            page-break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000 !important;
            padding: 5px;
            text-align: left;
        }

        .report-table th {
            background-color: #e9ecef !important;
            font-weight: bold;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }

        .page-break {
            page-break-before: always;
        }

        .print-footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .growth-positive {
            color: #28a745 !important;
        }

        .growth-negative {
            color: #dc3545 !important;
        }
    }

    @media screen {
        .print-content {
            display: none;
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
                <!-- Screen Content -->
                <div class="screen-content">
                    <!-- Header -->
                    <div class="bg-white shadow-sm p-3 mb-4 no-print">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0">Dashboard Laporan Penjualan</h2>
                                <small class="text-muted">Analisis komprehensif performa bisnis dan tren
                                    penjualan</small>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="printReport()">
                                    <i class="fas fa-print"></i> Cetak Laporan
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
                                        <option value="daily" <?= $period == 'daily' ? 'selected' : '' ?>>Harian
                                        </option>
                                        <option value="monthly" <?= $period == 'monthly' ? 'selected' : '' ?>>Bulanan
                                        </option>
                                        <option value="yearly" <?= $period == 'yearly' ? 'selected' : '' ?>>Tahunan
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tahun</label>
                                    <select name="year" class="form-select">
                                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2" id="monthSelect"
                                    style="<?= $period == 'yearly' ? 'display:none' : '' ?>">
                                    <label class="form-label">Bulan</label>
                                    <select name="month" class="form-select">
                                        <?php foreach ($months as $num => $name): ?>
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

                    <!-- Filter Info Display -->
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Filter Aktif:</strong>
                        <?php if ($period == 'daily'): ?>
                        Laporan Harian untuk <?= isset($months[$month]) ? $months[$month] : 'Bulan ' . $month ?>
                        <?= $year ?>
                        <?php elseif ($period == 'monthly'): ?>
                        Laporan Bulanan untuk Tahun <?= $year ?>
                        <?php else: ?>
                        Laporan Tahunan untuk Periode <?= ($year-4) ?> - <?= $year ?>
                        <?php endif; ?>
                        (<?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>)
                    </div>

                    <!-- Summary Statistics - Compact Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="mb-1"><?= number_format($summary['total_orders']) ?></h5>
                                <small class="text-muted">Total Pesanan</small>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                                    <i class="fas fa-rupiah-sign"></i>
                                </div>
                                <h6 class="mb-1"><?= formatRupiah($summary['total_revenue']) ?></h6>
                                <small class="text-muted">Total Omzet</small>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <h6 class="mb-1"><?= formatRupiah($summary['avg_order_value']) ?></h6>
                                <small class="text-muted">Rata-rata Order</small>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="mb-1"><?= number_format($summary['unique_customers']) ?></h5>
                                <small class="text-muted">Pelanggan Unik</small>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                                    <i class="fas fa-chart-line"></i>
                                    <?php if ($growth_rate != 0): ?>
                                    <span class="alert-badge"><?= abs(round($growth_rate)) ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-1 <?= $growth_rate >= 0 ? 'growth-positive' : 'growth-negative' ?>">
                                    <?= $growth_rate >= 0 ? '+' : '' ?><?= number_format($growth_rate, 1) ?>%
                                </h6>
                                <small class="text-muted">
                                    <?php if ($period == 'daily'): ?>
                                    Growth Harian
                                    <?php elseif ($period == 'monthly'): ?>
                                    Growth Bulanan
                                    <?php else: ?>
                                    Growth Tahunan
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-6 mb-3">
                            <div class="stat-card position-relative">
                                <div class="stat-icon"
                                    style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?php if ($low_stock > 0): ?>
                                    <span class="alert-badge"><?= $low_stock ?></span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="mb-1"><?= $low_stock ?></h5>
                                <small class="text-muted">Stok Rendah</small>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="row mb-4">
                        <!-- Sales Trend Chart -->
                        <div class="col-lg-8">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-line text-primary"></i>
                                    Tren Penjualan <?= ucfirst($period) ?>
                                    <?php if ($period == 'daily'): ?>
                                    - <?= isset($months[$month]) ? $months[$month] : 'Bulan ' . $month ?> <?= $year ?>
                                    <?php elseif ($period == 'monthly'): ?>
                                    - <?= $year ?>
                                    <?php endif; ?>
                                </h6>
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <!-- Order Status Chart -->
                        <div class="col-lg-4">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-pie text-info"></i>
                                    Status Pesanan
                                </h6>
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 2 -->
                    <div class="row mb-4">
                        <!-- Category Performance -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-bar text-success"></i>
                                    Performa Kategori Produk
                                </h6>
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Trend Comparison -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-area text-warning"></i>
                                    <?= $comparison_title ?>
                                </h6>
                                <canvas id="comparisonChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products and Customers -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="metric-card">
                                <h6 class="mb-3">
                                    <i class="fas fa-trophy text-warning"></i>
                                    Top 10 Produk Terlaris
                                </h6>
                                <?php if (mysqli_num_rows($top_products_result) > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                <?php if ($rank <= 10): ?>
                                <div class="top-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <span class="badge bg-primary">#<?= $rank ?></span>
                                        </div>
                                        <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                                            class="rounded me-2" width="30" height="30" style="object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" style="font-size: 13px;">
                                                <?= $product['product_name'] ?></div>
                                            <small class="text-muted">
                                                <?= $product['total_sold'] ?> terjual •
                                                <?= formatRupiah($product['total_revenue']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php $rank++; ?>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-box-open fa-2x mb-2"></i>
                                    <p class="mb-0">Tidak ada data produk</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="metric-card">
                                <h6 class="mb-3">
                                    <i class="fas fa-crown text-success"></i>
                                    Top 10 Pelanggan Terbaik
                                </h6>
                                <?php 
                                mysqli_data_seek($customer_stats_result, 0);
                                if (mysqli_num_rows($customer_stats_result) > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while ($customer = mysqli_fetch_assoc($customer_stats_result)): ?>
                                <?php if ($rank <= 10): ?>
                                <div class="top-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <span class="badge bg-success">#<?= $rank ?></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" style="font-size: 13px;"><?= $customer['name'] ?></div>
                                            <small class="text-muted">
                                                <?= $customer['order_count'] ?> pesanan •
                                                <?= formatRupiah($customer['total_spent']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php $rank++; ?>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p class="mb-0">Tidak ada data pelanggan</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Content (Hidden on screen, shown when printing) -->
                <div class="print-content">
                    <!-- Print Header -->
                    <div class="print-header">
                        <img src="../logo.png" alt="Logo Sarana Smartphone" class="company-logo">
                        <div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">SARANA SMARTPHONE</div>
                        <div class="company-info">
                            Jalan Dokter Soetomo No. 78, Kota Padang, Sumatra Barat 25126<br>
                            Telp: 021-1234567 | Email: info@saranasmart.com
                        </div>
                        <div class="report-title">Laporan Penjualan</div>
                        <div class="report-period">
                            <strong>Periode:
                                <?php 
                            if ($period == 'daily') {
                                echo (isset($months[$month]) ? $months[$month] : 'Bulan ' . $month) . ' ' . $year . ' (Laporan Harian)';
                            } elseif ($period == 'monthly') {
                                echo 'Tahun ' . $year . ' (Laporan Bulanan)';
                            } else {
                                echo ($year-4) . ' - ' . $year . ' (Laporan Tahunan)';
                            }
                            ?></strong><br>
                            Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
                        </div>
                    </div>

                    <!-- Executive Summary -->
                    <div class="summary-box">
                        <h5 class="mb-3"><strong>RINGKASAN EKSEKUTIF</strong></h5>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>Total Pesanan:</strong><br>
                                <?= number_format($summary['total_orders']) ?> pesanan
                            </div>
                            <div class="col-md-3">
                                <strong>Total Omzet:</strong><br>
                                <?= formatRupiah($summary['total_revenue']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Rata-rata Order:</strong><br>
                                <?= formatRupiah($summary['avg_order_value']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Pelanggan Unik:</strong><br>
                                <?= number_format($summary['unique_customers']) ?> pelanggan
                            </div>
                        </div>
                        <?php if (abs($growth_rate) > 0): ?>
                        <div class="row">
                            <div class="col-12">
                                <strong>Growth Rate
                                    <?php if ($period == 'daily'): ?>
                                    (Perbandingan Hari)
                                    <?php elseif ($period == 'monthly'): ?>
                                    (Perbandingan Bulan)
                                    <?php else: ?>
                                    (Perbandingan Tahun)
                                    <?php endif; ?>:</strong>
                                <span class="<?= $growth_rate >= 0 ? 'growth-positive' : 'growth-negative' ?>">
                                    <?= $growth_rate >= 0 ? '+' : '' ?><?= number_format($growth_rate, 1) ?>%
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sales Trend Table -->
                    <div class="section-title">
                        1. TREN PENJUALAN
                        <?php 
                        if ($period == 'daily') echo '(HARIAN)';
                        elseif ($period == 'monthly') echo '(BULANAN)';
                        else echo '(TAHUNAN)';
                        ?>
                    </div>
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th width="15%">No</th>
                                <th width="25%">Periode</th>
                                <th width="20%">Total Pesanan</th>
                                <th width="25%">Total Omzet</th>
                                <th width="15%">Rata-rata Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sales_data)): ?>
                            <?php 
                                $no = 1;
                                $total_orders_sum = 0;
                                $total_revenue_sum = 0;
                                foreach ($sales_data as $data): 
                                    $total_orders_sum += $data['total_orders'];
                                    $total_revenue_sum += $data['total_revenue'];
                                ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <?php
                                        if ($period == 'daily') {
                                            echo date('d F Y', strtotime($data['period']));
                                        } elseif ($period == 'monthly') {
                                            $month_num = explode('-', $data['period'])[1];
                                            echo (isset($months[intval($month_num)]) ? $months[intval($month_num)] : 'Bulan ' . $month_num) . ' ' . explode('-', $data['period'])[0];
                                        } else {
                                            echo 'Tahun ' . $data['period'];
                                        }
                                        ?>
                                </td>
                                <td class="text-end"><?= number_format($data['total_orders']) ?></td>
                                <td class="text-end"><?= formatRupiah($data['total_revenue']) ?></td>
                                <td class="text-end"><?= formatRupiah($data['avg_order_value']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: #e9ecef; font-weight: bold;">
                                <td colspan="2" class="text-center">TOTAL</td>
                                <td class="text-end"><?= number_format($total_orders_sum) ?></td>
                                <td class="text-end"><?= formatRupiah($total_revenue_sum) ?></td>
                                <td class="text-end"><?= formatRupiah($total_revenue_sum / max($total_orders_sum, 1)) ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data penjualan pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Order Status Breakdown -->
                    <div class="section-title">2. ANALISIS STATUS PESANAN</div>
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th width="15%">No</th>
                                <th width="35%">Status Pesanan</th>
                                <th width="25%">Jumlah Pesanan</th>
                                <th width="25%">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($status_data)): ?>
                            <?php 
                                $no = 1;
                                $status_labels = [
                                    'pending' => 'Menunggu Konfirmasi',
                                    'confirmed' => 'Dikonfirmasi',
                                    'shipped' => 'Dikirim',
                                    'delivered' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                foreach ($status_data as $status): 
                                ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= $status_labels[$status['status']] ?? ucfirst($status['status']) ?></td>
                                <td class="text-end"><?= number_format($status['count']) ?></td>
                                <td class="text-end"><?= formatRupiah($status['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data status pesanan</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Trend Comparison Table -->
                    <?php if (!empty($comparison_data) && count($comparison_data) > 1): ?>
                    <div class="section-title">3. <?= strtoupper($comparison_title) ?></div>
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th width="15%">No</th>
                                <th width="35%">Periode</th>
                                <th width="25%">Jumlah Pesanan</th>
                                <th width="25%">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($comparison_data as $data): 
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <?php
                                    if ($period == 'daily') {
                                        echo date('d F Y', strtotime($data['period']));
                                    } elseif ($period == 'monthly') {
                                        $month_num = explode('-', $data['period'])[1];
                                        echo (isset($months[intval($month_num)]) ? $months[intval($month_num)] : 'Bulan ' . $month_num) . ' ' . explode('-', $data['period'])[0];
                                    } else {
                                        echo 'Tahun ' . $data['period'];
                                    }
                                    ?>
                                </td>
                                <td class="text-end"><?= number_format($data['orders']) ?></td>
                                <td class="text-end"><?= formatRupiah($data['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <!-- Top Products Table -->
                    <div class="section-title page-break">
                        <?= !empty($comparison_data) && count($comparison_data) > 1 ? '4' : '3' ?>. PRODUK TERLARIS
                    </div>
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th width="8%">Rank</th>
                                <th width="35%">Nama Produk</th>
                                <th width="17%">Harga Satuan</th>
                                <th width="15%">Qty Terjual</th>
                                <th width="25%">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($top_products_result, 0);
                            if (mysqli_num_rows($top_products_result) > 0): ?>
                            <?php 
                                $rank = 1;
                                $total_qty = 0;
                                $total_omzet = 0;
                                while ($product = mysqli_fetch_assoc($top_products_result)): 
                                    $total_qty += $product['total_sold'];
                                    $total_omzet += $product['total_revenue'];
                                ?>
                            <tr>
                                <td class="text-center"><?= $rank++ ?></td>
                                <td><?= $product['product_name'] ?></td>
                                <td class="text-end"><?= formatRupiah($product['price']) ?></td>
                                <td class="text-end"><?= number_format($product['total_sold']) ?></td>
                                <td class="text-end"><?= formatRupiah($product['total_revenue']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: #e9ecef; font-weight: bold;">
                                <td colspan="3" class="text-center">TOTAL</td>
                                <td class="text-end"><?= number_format($total_qty) ?></td>
                                <td class="text-end"><?= formatRupiah($total_omzet) ?></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data penjualan produk</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Top Customers Table -->
                    <div class="section-title">
                        <?= !empty($comparison_data) && count($comparison_data) > 1 ? '5' : '4' ?>. PELANGGAN TERBAIK
                    </div>
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th width="8%">Rank</th>
                                <th width="30%">Nama Pelanggan</th>
                                <th width="25%">Email</th>
                                <th width="15%">No. Telepon</th>
                                <th width="12%">Jml Order</th>
                                <th width="20%">Total Belanja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($customer_stats_result, 0);
                            if (mysqli_num_rows($customer_stats_result) > 0): ?>
                            <?php 
                                $rank = 1;
                                $total_orders_cust = 0;
                                $total_spent_all = 0;
                                while ($customer = mysqli_fetch_assoc($customer_stats_result)): 
                                    $total_orders_cust += $customer['order_count'];
                                    $total_spent_all += $customer['total_spent'];
                                ?>
                            <tr>
                                <td class="text-center"><?= $rank++ ?></td>
                                <td><?= $customer['name'] ?></td>
                                <td><?= $customer['email'] ?></td>
                                <td><?= $customer['phone'] ?: '-' ?></td>
                                <td class="text-end"><?= number_format($customer['order_count']) ?></td>
                                <td class="text-end"><?= formatRupiah($customer['total_spent']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: #e9ecef; font-weight: bold;">
                                <td colspan="4" class="text-center">TOTAL</td>
                                <td class="text-end"><?= number_format($total_orders_cust) ?></td>
                                <td class="text-end"><?= formatRupiah($total_spent_all) ?></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data pelanggan</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Report Footer -->
                    <div style="margin-top: 40px; font-size: 11px;">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Catatan:</strong></p>
                                <ul style="margin: 0; padding-left: 15px;">
                                    <li>Data berdasarkan pesanan yang tidak dibatalkan</li>
                                    <li>Periode: <?= date('d M Y', strtotime($start_date)) ?> -
                                        <?= date('d M Y', strtotime($end_date)) ?></li>
                                    <li>Filter: <?= ucfirst($period) ?>
                                        <?php if ($period == 'daily'): ?>
                                        (<?= isset($months[$month]) ? $months[$month] : 'Bulan ' . $month ?>
                                        <?= $year ?>)
                                        <?php elseif ($period == 'monthly'): ?>
                                        (Tahun <?= $year ?>)
                                        <?php else: ?>
                                        (<?= ($year-4) ?> - <?= $year ?>)
                                        <?php endif; ?>
                                    </li>
                                    <li>Laporan digenerate otomatis dari sistem</li>
                                </ul>
                            </div>
                            <div class="col-6 text-end">
                                <p>Padang, <?= date('d F Y') ?></p>
                                <br><br><br>
                                <p style="border-top: 1px solid #000; display: inline-block; padding-top: 5px;">
                                    <strong>Manager</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Print Footer -->
                    <div class="print-footer">
                        Laporan Penjualan - Sarana Smartphone | Dicetak pada <?= date('d F Y, H:i') ?> WIB | Halaman:
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sales Trend Chart
    const ctx1 = document.getElementById('salesChart').getContext('2d');
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

    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Omzet (Rp)',
                data: revenues,
                borderColor: 'rgba(102, 126, 234, 1)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Jumlah Pesanan',
                data: orders,
                borderColor: 'rgba(86, 171, 47, 1)',
                backgroundColor: 'rgba(86, 171, 47, 0.1)',
                borderWidth: 2,
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
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Order Status Pie Chart
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    const statusData = <?= json_encode($status_data) ?>;
    const statusLabels = {
        'pending': 'Menunggu',
        'confirmed': 'Dikonfirmasi',
        'shipped': 'Dikirim',
        'delivered': 'Selesai',
        'cancelled': 'Dibatalkan'
    };

    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => statusLabels[item.status] || item.status),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Category Performance Chart
    const ctx3 = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?= json_encode($category_data) ?>;

    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: categoryData.map(item => item.category_name),
            datasets: [{
                label: 'Total Omzet',
                data: categoryData.map(item => item.total_revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Comparison Chart (Dynamic based on period)
    const ctx4 = document.getElementById('comparisonChart').getContext('2d');
    const comparisonData = <?= json_encode($comparison_data) ?>;
    const currentPeriod = '<?= $period ?>';

    new Chart(ctx4, {
        type: 'line',
        data: {
            labels: comparisonData.map(item => {
                if (currentPeriod === 'daily') {
                    return new Date(item.period).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short'
                    });
                } else if (currentPeriod === 'monthly') {
                    const [year, month] = item.period.split('-');
                    return new Date(year, month - 1).toLocaleDateString('id-ID', {
                        month: 'short'
                    });
                } else {
                    return item.period;
                }
            }),
            datasets: [{
                label: 'Omzet',
                data: comparisonData.map(item => item.revenue || 0),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
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
    </script>
</body>

</html>

<?php closeConnection($conn); ?>