<?php
// admin/settings.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                
                $update_query = "UPDATE users SET name = '$name', email = '$email', phone = '$phone' WHERE id = " . $_SESSION['user_id'];
                
                if (mysqli_query($conn, $update_query)) {
                    $_SESSION['name'] = $name;
                    $success = 'Profil berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate profil';
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $error = 'Konfirmasi password tidak cocok';
                } else {
                    // Verify current password
                    $check_query = "SELECT password FROM users WHERE id = " . $_SESSION['user_id'];
                    $check_result = mysqli_query($conn, $check_query);
                    $user = mysqli_fetch_assoc($check_result);
                    
                    if (md5($current_password) === $user['password']) {
                        $new_password_hash = md5($new_password);
                        $update_query = "UPDATE users SET password = '$new_password_hash' WHERE id = " . $_SESSION['user_id'];
                        
                        if (mysqli_query($conn, $update_query)) {
                            $success = 'Password berhasil diubah';
                        } else {
                            $error = 'Gagal mengubah password';
                        }
                    } else {
                        $error = 'Password saat ini salah';
                    }
                }
                break;
                
            case 'update_site_settings':
                $site_name = mysqli_real_escape_string($conn, $_POST['site_name']);
                $site_description = mysqli_real_escape_string($conn, $_POST['site_description']);
                $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email']);
                $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone']);
                $address = mysqli_real_escape_string($conn, $_POST['address']);
                
                // Update or insert site settings
                $settings = [
                    'site_name' => $site_name,
                    'site_description' => $site_description,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone,
                    'address' => $address
                ];
                
                foreach ($settings as $key => $value) {
                    $update_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value') 
                                   ON DUPLICATE KEY UPDATE setting_value = '$value'";
                    mysqli_query($conn, $update_query);
                }
                
                $success = 'Pengaturan website berhasil diupdate';
                break;
        }
    }
}

// Get current user info
$user_query = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get site settings
$settings_query = "SELECT setting_key, setting_value FROM settings";
$settings_result = mysqli_query($conn, $settings_query);
$site_settings = [];
while ($setting = mysqli_fetch_assoc($settings_result)) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get system statistics
$system_stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
        (SELECT COUNT(*) FROM products WHERE status = 'active') as total_products,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM reviews) as total_reviews
";
$system_stats_result = mysqli_query($conn, $system_stats_query);
$system_stats = mysqli_fetch_assoc($system_stats_result);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Sarana Smartphone</title>
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

    .settings-card {
        transition: transform 0.3s;
        border: 1px solid #e9ecef;
    }

    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .settings-nav {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
    }

    .settings-nav .nav-link {
        color: #495057;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }

    .settings-nav .nav-link:hover,
    .settings-nav .nav-link.active {
        background: #007bff;
        color: white;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .stat-item i {
        font-size: 2rem;
        color: #007bff;
        margin-bottom: 0.5rem;
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
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog me-2"></i> Pengaturan
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
                            <h2 class="mb-0">Pengaturan</h2>
                            <small class="text-muted">Kelola pengaturan sistem dan profil admin</small>
                        </div>
                        <div>
                            <span class="text-muted">
                                <i class="fas fa-user"></i>
                                <?= $_SESSION['name'] ?>
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

                <div class="row">
                    <!-- Settings Navigation -->
                    <div class="col-lg-3">
                        <div class="settings-nav">
                            <nav class="nav flex-column">
                                <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                                    <i class="fas fa-user me-2"></i> Profil Admin
                                </a>
                                <a class="nav-link" href="#password" data-bs-toggle="tab">
                                    <i class="fas fa-lock me-2"></i> Ubah Password
                                </a>
                                <a class="nav-link" href="#site" data-bs-toggle="tab">
                                    <i class="fas fa-globe me-2"></i> Pengaturan Website
                                </a>
                                <a class="nav-link" href="#system" data-bs-toggle="tab">
                                    <i class="fas fa-server me-2"></i> Info Sistem
                                </a>
                            </nav>
                        </div>
                    </div>

                    <!-- Settings Content -->
                    <div class="col-lg-9">
                        <div class="tab-content">
                            <!-- Profile Settings -->
                            <div class="tab-pane fade show active" id="profile">
                                <div class="card settings-card border-0 shadow-sm">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user text-primary me-2"></i>
                                            Profil Admin
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_profile">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Lengkap</label>
                                                        <input type="text" class="form-control" name="name"
                                                            value="<?= $user['name'] ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control" name="email"
                                                            value="<?= $user['email'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nomor Telepon</label>
                                                        <input type="text" class="form-control" name="phone"
                                                            value="<?= $user['phone'] ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Role</label>
                                                        <input type="text" class="form-control"
                                                            value="<?= ucfirst($user['role']) ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Settings -->
                            <div class="tab-pane fade" id="password">
                                <div class="card settings-card border-0 shadow-sm">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-lock text-warning me-2"></i>
                                            Ubah Password
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="change_password">

                                            <div class="mb-3">
                                                <label class="form-label">Password Saat Ini</label>
                                                <input type="password" class="form-control" name="current_password"
                                                    required>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Password Baru</label>
                                                        <input type="password" class="form-control" name="new_password"
                                                            minlength="6" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Konfirmasi Password Baru</label>
                                                        <input type="password" class="form-control"
                                                            name="confirm_password" minlength="6" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                Password minimal 6 karakter dan sebaiknya mengandung kombinasi huruf,
                                                angka, dan simbol.
                                            </div>

                                            <div class="text-end">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-key"></i> Ubah Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Site Settings -->
                            <div class="tab-pane fade" id="site">
                                <div class="card settings-card border-0 shadow-sm">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-globe text-success me-2"></i>
                                            Pengaturan Website
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_site_settings">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Website</label>
                                                        <input type="text" class="form-control" name="site_name"
                                                            value="<?= $site_settings['site_name'] ?? 'Sarana Smartphone' ?>"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Email Kontak</label>
                                                        <input type="email" class="form-control" name="contact_email"
                                                            value="<?= $site_settings['contact_email'] ?? '' ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi Website</label>
                                                <textarea class="form-control" name="site_description"
                                                    rows="3"><?= $site_settings['site_description'] ?? '' ?></textarea>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nomor Telepon</label>
                                                        <input type="text" class="form-control" name="contact_phone"
                                                            value="<?= $site_settings['contact_phone'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Alamat</label>
                                                        <input type="text" class="form-control" name="address"
                                                            value="<?= $site_settings['address'] ?? '' ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-save"></i> Simpan Pengaturan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- System Info -->
                            <div class="tab-pane fade" id="system">
                                <div class="card settings-card border-0 shadow-sm">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-server text-info me-2"></i>
                                            Informasi Sistem
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="stat-item">
                                                    <i class="fas fa-users"></i>
                                                    <h4><?= $system_stats['total_customers'] ?></h4>
                                                    <p class="text-muted mb-0">Total Pelanggan</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-item">
                                                    <i class="fas fa-box"></i>
                                                    <h4><?= $system_stats['total_products'] ?></h4>
                                                    <p class="text-muted mb-0">Total Produk</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-item">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    <h4><?= $system_stats['total_orders'] ?></h4>
                                                    <p class="text-muted mb-0">Total Pesanan</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-item">
                                                    <i class="fas fa-star"></i>
                                                    <h4><?= $system_stats['total_reviews'] ?></h4>
                                                    <p class="text-muted mb-0">Total Review</p>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-info-circle text-info"></i> Informasi Server</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td>PHP Version</td>
                                                        <td><?= phpversion() ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Server Software</td>
                                                        <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>MySQL Version</td>
                                                        <td>
                                                            <?php
                                                            $mysql_version = mysqli_get_server_info($conn);
                                                            echo $mysql_version;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>System Time</td>
                                                        <td><?= date('Y-m-d H:i:s') ?> WIB</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-database text-success"></i> Database Info</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td>Database Name</td>
                                                        <td><?= DB_NAME ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Database Host</td>
                                                        <td><?= DB_HOST ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Last Login</td>
                                                        <td><?= $_SESSION['last_login'] ?? 'N/A' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Session ID</td>
                                                        <td><?= substr(session_id(), 0, 8) ?>...</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Peringatan:</strong> Pastikan untuk melakukan backup database secara
                                            berkala dan menjaga keamanan sistem.
                                        </div>
                                    </div>
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
    // Password confirmation validation
    document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
        const newPassword = document.querySelector('input[name="new_password"]').value;
        const confirmPassword = this.value;

        if (confirmPassword && newPassword !== confirmPassword) {
            this.setCustomValidity('Password tidak cocok');
        } else {
            this.setCustomValidity('');
        }
    });

    // Tab persistence
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        const tabTrigger = document.querySelector(`a[href="${activeTab}"]`);
        const tab = new bootstrap.Tab(tabTrigger);
        tab.show();
    }

    // Save active tab
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem('activeSettingsTab', e.target.getAttribute('href'));
        });
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>