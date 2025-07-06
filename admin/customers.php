<?php
// admin/customers.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build customers query
$customers_query = "
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount END), 0) as total_spent,
           COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as completed_orders,
           MAX(o.order_date) as last_order_date,
           COUNT(DISTINCT r.id) as total_reviews
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN reviews r ON u.id = r.user_id
    WHERE u.role = 'customer'
";

if (!empty($search)) {
    $customers_query .= " AND (u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                         OR u.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                         OR u.phone LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

$customers_query .= " GROUP BY u.id, u.name, u.email, u.phone, u.address, u.created_at";

// Add sorting
switch ($sort) {
    case 'name':
        $customers_query .= " ORDER BY u.name ASC";
        break;
    case 'spent':
        $customers_query .= " ORDER BY total_spent DESC";
        break;
    case 'orders':
        $customers_query .= " ORDER BY total_orders DESC";
        break;
    case 'oldest':
        $customers_query .= " ORDER BY u.created_at ASC";
        break;
    default: // newest
        $customers_query .= " ORDER BY u.created_at DESC";
        break;
}

$customers_result = mysqli_query($conn, $customers_query);

// Get summary statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
    FROM users 
    WHERE role = 'customer'
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Admin Sarana Smartphone</title>
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

    .customer-card {
        transition: transform 0.3s;
        border-left: 4px solid #e9ecef;
    }

    .customer-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .customer-card.vip {
        border-left-color: #ffc107;
    }

    .customer-card.loyal {
        border-left-color: #28a745;
    }

    .customer-card.new {
        border-left-color: #007bff;
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
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i> Pesanan
                        </a>
                        <a class="nav-link active" href="customers.php">
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
                            <h2 class="mb-0">Kelola Pelanggan</h2>
                            <small class="text-muted">Pantau dan kelola data pelanggan</small>
                        </div>
                        <div>
                            <button class="btn btn-outline-primary" onclick="exportCustomers()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4><?= number_format($stats['total_customers']) ?></h4>
                            <p class="text-muted mb-0">Total Pelanggan</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #28a745;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4><?= number_format($stats['new_today']) ?></h4>
                            <p class="text-muted mb-0">Baru Hari Ini</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #007bff;">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <h4><?= number_format($stats['new_this_week']) ?></h4>
                            <p class="text-muted mb-0">Minggu Ini</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #ffc107;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h4><?= number_format($stats['new_this_month']) ?></h4>
                            <p class="text-muted mb-0">Bulan Ini</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari nama, email, atau telepon..."
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="sort" class="form-select">
                                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Terbaru</option>
                                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Terlama</option>
                                    <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Nama A-Z</option>
                                    <option value="spent" <?= $sort == 'spent' ? 'selected' : '' ?>>Total Belanja
                                    </option>
                                    <option value="orders" <?= $sort == 'orders' ? 'selected' : '' ?>>Total Pesanan
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customers List -->
                <div class="row">
                    <?php if (mysqli_num_rows($customers_result) > 0): ?>
                    <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                    <?php
                            // Determine customer type
                            $customer_type = 'regular';
                            if ($customer['total_spent'] >= 10000000) {
                                $customer_type = 'vip';
                            } elseif ($customer['total_orders'] >= 5) {
                                $customer_type = 'loyal';
                            } elseif (strtotime($customer['created_at']) > strtotime('-7 days')) {
                                $customer_type = 'new';
                            }
                            ?>
                    <div class="col-12 mb-3">
                        <div class="card customer-card <?= $customer_type ?> border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-lg-3 col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user fa-lg"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?= $customer['name'] ?></h6>
                                                <small class="text-muted"><?= $customer['email'] ?></small>
                                                <?php if ($customer_type != 'regular'): ?>
                                                <br><span class="badge bg-<?= 
                                                                $customer_type == 'vip' ? 'warning' : 
                                                                ($customer_type == 'loyal' ? 'success' : 'primary') 
                                                            ?>">
                                                    <?= strtoupper($customer_type) ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-md-3">
                                        <small class="text-muted">Total Pesanan</small>
                                        <div class="fw-bold"><?= $customer['total_orders'] ?></div>
                                    </div>

                                    <div class="col-lg-2 col-md-3">
                                        <small class="text-muted">Total Belanja</small>
                                        <div class="fw-bold text-success"><?= formatRupiah($customer['total_spent']) ?>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-md-2">
                                        <small class="text-muted">Bergabung</small>
                                        <div><?= date('d M Y', strtotime($customer['created_at'])) ?></div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 text-end">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-outline-info btn-sm"
                                                onclick="viewCustomerDetail(<?= $customer['id'] ?>)">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                            <a href="tel:<?= $customer['phone'] ?>"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                            <a href="mailto:<?= $customer['email'] ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <a href="chats.php?user_id=<?= $customer['id'] ?>"
                                                class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?= $customer['phone'] ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-star"></i> <?= $customer['total_reviews'] ?> ulasan
                                            <?php if ($customer['last_order_date']): ?>
                                            • Terakhir order: <?= timeAgo($customer['last_order_date']) ?>
                                            <?php endif; ?>
                                        </small>
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
                                <i class="fas fa-users fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">Tidak Ada Pelanggan</h4>
                                <p class="text-muted">
                                    <?php if (!empty($search)): ?>
                                    Tidak ada pelanggan yang sesuai dengan pencarian.
                                    <?php else: ?>
                                    Belum ada pelanggan yang terdaftar.
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

    <!-- Customer Detail Modal -->
    <div class="modal fade" id="customerDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewCustomerDetail(customerId) {
        const modal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
        modal.show();

        document.getElementById('customerDetailContent').innerHTML =
            '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat...</div>';

        fetch(`../ajax/get_customer_info.php?id=${customerId}&detailed=1`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('customerDetailContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('customerDetailContent').innerHTML =
                    '<div class="alert alert-danger">Gagal memuat detail pelanggan.</div>';
            });
    }

    function exportCustomers() {
        window.location.href = 'export_customers.php';
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>