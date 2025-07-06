<?php
// admin/system_tools.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'backup_database':
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = '../backups/' . $backup_file;
                
                // Create backups directory if not exists
                if (!file_exists('../backups/')) {
                    mkdir('../backups/', 0777, true);
                }
                
                // MySQL dump command
                $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_path;
                
                if (exec($command) !== false) {
                    $success = "Database berhasil di-backup ke file: $backup_file";
                } else {
                    $error = "Gagal melakukan backup database";
                }
                break;
                
            case 'clear_logs':
                $log_type = $_POST['log_type'] ?? 'all';
                
                if ($log_type == 'all' || $log_type == 'activity') {
                    // Clear activity logs (if you have activity_logs table)
                    $clear_activity = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    mysqli_query($conn, $clear_activity);
                }
                
                if ($log_type == 'all' || $log_type == 'old_orders') {
                    // Delete old cancelled orders (older than 6 months)
                    $clear_orders = "DELETE FROM orders WHERE status = 'cancelled' AND order_date < DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                    mysqli_query($conn, $clear_orders);
                }
                
                $success = "Log berhasil dibersihkan";
                break;
                
            case 'optimize_database':
                // Get all tables
                $tables_query = "SHOW TABLES";
                $tables_result = mysqli_query($conn, $tables_query);
                
                $optimized = 0;
                while ($table = mysqli_fetch_array($tables_result)) {
                    $table_name = $table[0];
                    $optimize_query = "OPTIMIZE TABLE `$table_name`";
                    if (mysqli_query($conn, $optimize_query)) {
                        $optimized++;
                    }
                }
                
                $success = "$optimized tabel berhasil dioptimasi";
                break;
        }
    }
}

// Get system information
$system_info = [];

// Database size
$db_size_query = "
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size (MB)'
    FROM information_schema.tables 
    WHERE table_schema = '" . DB_NAME . "'
";
$db_size_result = mysqli_query($conn, $db_size_query);
$system_info['db_size'] = mysqli_fetch_assoc($db_size_result)['DB Size (MB)'];

// Table statistics
$table_stats_query = "
    SELECT 
        table_name,
        table_rows,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
    FROM information_schema.TABLES 
    WHERE table_schema = '" . DB_NAME . "'
    ORDER BY (data_length + index_length) DESC
";
$table_stats_result = mysqli_query($conn, $table_stats_query);

// Recent activity (if activity logs table exists)
$recent_activity = [];
$activity_query = "
    SELECT 
        'order' as type, 
        CONCAT('Pesanan #', id, ' - ', status) as description,
        order_date as created_at
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 
        'user' as type,
        CONCAT('User baru: ', name) as description,
        created_at
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
    LIMIT 20
";
$activity_result = mysqli_query($conn, $activity_query);
while ($activity = mysqli_fetch_assoc($activity_result)) {
    $recent_activity[] = $activity;
}

// Check for available backups
$backup_files = [];
if (file_exists('../backups/')) {
    $files = scandir('../backups/');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize('../backups/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime('../backups/' . $file))
            ];
        }
    }
    // Sort by date, newest first
    usort($backup_files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Tools - Admin Sarana Smartphone</title>
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

    .tool-card {
        transition: transform 0.3s;
        border: 1px solid #e9ecef;
    }

    .tool-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .system-metric {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .danger-zone {
        border: 2px solid #dc3545;
        border-radius: 10px;
        background: rgba(220, 53, 69, 0.1);
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
                        <a class="nav-link" href="reviews.php">
                            <i class="fas fa-star me-2"></i> Ulasan
                        </a>
                        <a class="nav-link" href="stock.php">
                            <i class="fas fa-warehouse me-2"></i> Stok
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Laporan
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Pengaturan
                        </a>
                        <a class="nav-link active" href="system_tools.php">
                            <i class="fas fa-tools me-2"></i> System Tools
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
                            <h2 class="mb-0">System Tools</h2>
                            <small class="text-muted">Kelola sistem, backup, dan maintenance</small>
                        </div>
                        <div>
                            <span class="badge bg-info">
                                <i class="fas fa-database"></i> DB: <?= $system_info['db_size'] ?> MB
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

                <!-- System Metrics -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="system-metric">
                            <h4 class="text-primary"><?= $system_info['db_size'] ?> MB</h4>
                            <small class="text-muted">Ukuran Database</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="system-metric">
                            <h4 class="text-success"><?= count($backup_files) ?></h4>
                            <small class="text-muted">File Backup</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="system-metric">
                            <h4 class="text-info"><?= phpversion() ?></h4>
                            <small class="text-muted">PHP Version</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="system-metric">
                            <h4 class="text-warning"><?= count($recent_activity) ?></h4>
                            <small class="text-muted">Recent Activities</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Backup Tools -->
                    <div class="col-lg-6">
                        <div class="card tool-card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-download text-primary"></i>
                                    Database Backup
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Buat backup database untuk keamanan data.</p>

                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="action" value="backup_database">
                                    <button type="submit" class="btn btn-primary"
                                        onclick="return confirm('Yakin ingin membuat backup database?')">
                                        <i class="fas fa-download"></i> Buat Backup
                                    </button>
                                </form>

                                <h6>File Backup Tersedia:</h6>
                                <?php if (!empty($backup_files)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>File</th>
                                                <th>Ukuran</th>
                                                <th>Tanggal</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($backup_files, 0, 5) as $backup): ?>
                                            <tr>
                                                <td><?= $backup['name'] ?></td>
                                                <td><?= number_format($backup['size'] / 1024, 1) ?> KB</td>
                                                <td><?= date('d/m/Y H:i', strtotime($backup['date'])) ?></td>
                                                <td>
                                                    <a href="../backups/<?= $backup['name'] ?>"
                                                        class="btn btn-sm btn-outline-primary" download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Belum ada file backup.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Tools -->
                    <div class="col-lg-6">
                        <div class="card tool-card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-tools text-warning"></i>
                                    Database Maintenance
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Optimasi dan pembersihan database.</p>

                                <div class="d-grid gap-2">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="optimize_database">
                                        <button type="submit" class="btn btn-warning w-100"
                                            onclick="return confirm('Yakin ingin mengoptimasi database?')">
                                            <i class="fas fa-rocket"></i> Optimasi Database
                                        </button>
                                    </form>

                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="clear_logs">
                                        <input type="hidden" name="log_type" value="all">
                                        <button type="submit" class="btn btn-info w-100"
                                            onclick="return confirm('Yakin ingin membersihkan log lama?')">
                                            <i class="fas fa-broom"></i> Bersihkan Log Lama
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Tables -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-table text-info"></i>
                                    Statistik Tabel Database
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Tabel</th>
                                                <th>Jumlah Baris</th>
                                                <th>Ukuran (MB)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($table = mysqli_fetch_assoc($table_stats_result)): ?>
                                            <tr>
                                                <td><code><?= $table['table_name'] ?></code></td>
                                                <td><?= number_format($table['table_rows']) ?></td>
                                                <td><?= $table['Size (MB)'] ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-success"></i>
                                    Aktivitas Terbaru
                                </h5>
                            </div>
                            <div class="card-body">
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php foreach ($recent_activity as $activity): ?>
                                    <div class="d-flex align-items-start mb-2">
                                        <i
                                            class="fas fa-<?= $activity['type'] == 'order' ? 'shopping-cart' : 'user' ?> 
                                           text-<?= $activity['type'] == 'order' ? 'primary' : 'success' ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <small class="d-block"><?= $activity['description'] ?></small>
                                            <small class="text-muted"><?= timeAgo($activity['created_at']) ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="danger-zone p-4 mt-4">
                    <h5 class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        Danger Zone
                    </h5>
                    <p class="text-muted">
                        <strong>Peringatan:</strong> Operasi di bawah ini dapat menghapus data secara permanen.
                        Pastikan Anda telah membuat backup sebelum melanjutkan.
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="clear_logs">
                                <input type="hidden" name="log_type" value="old_orders">
                                <button type="submit" class="btn btn-outline-danger"
                                    onclick="return confirm('PERINGATAN: Ini akan menghapus semua pesanan yang dibatalkan lebih dari 6 bulan. Yakin melanjutkan?')">
                                    <i class="fas fa-trash"></i> Hapus Pesanan Lama
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php closeConnection($conn); ?>