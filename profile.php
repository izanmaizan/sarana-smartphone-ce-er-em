<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        
        $update_query = "UPDATE users SET 
                        name = '$name', 
                        phone = '$phone', 
                        address = '$address' 
                        WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['name'] = $name; // Update session
            $success = 'Profil berhasil diperbarui';
        } else {
            $error = 'Gagal memperbarui profil';
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = MD5($_POST['current_password']);
        $new_password = MD5($_POST['new_password']);
        $confirm_password = MD5($_POST['confirm_password']);
        
        // Verify current password
        $check_query = "SELECT id FROM users WHERE id = $user_id AND password = '$current_password'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            $error = 'Password lama tidak benar';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'Konfirmasi password baru tidak cocok';
        } elseif (strlen($_POST['new_password']) < 6) {
            $error = 'Password baru minimal 6 karakter';
        } else {
            $update_password_query = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
            
            if (mysqli_query($conn, $update_password_query)) {
                $success = 'Password berhasil diubah';
            } else {
                $error = 'Gagal mengubah password';
            }
        }
    }
}

// Get user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get user statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as completed_orders,
        COUNT(DISTINCT r.id) as total_reviews
    FROM orders o 
    LEFT JOIN reviews r ON r.user_id = o.user_id
    WHERE o.user_id = $user_id
";
$stats_result = mysqli_query($conn, $stats_query);
$user_stats = mysqli_fetch_assoc($stats_result);

// Get recent orders
$recent_orders_query = "
    SELECT o.*, COUNT(oi.id) as total_items
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $user_id 
    GROUP BY o.id
    ORDER BY o.order_date DESC 
    LIMIT 5
";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f8f9fa;
        padding-top: 76px;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-right: 1.5rem;
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
        transform: translateY(-5px);
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

    .nav-pills .nav-link {
        border-radius: 25px;
        padding: 0.75rem 1.5rem;
        margin-right: 0.5rem;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .form-floating {
        margin-bottom: 1rem;
    }

    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
    }

    .btn-gradient:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .order-mini {
        border-left: 4px solid #e9ecef;
        padding: 1rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 0 8px 8px 0;
    }

    .order-mini.delivered {
        border-left-color: #28a745;
    }

    .order-mini.pending {
        border-left-color: #ffc107;
    }

    .order-mini.shipped {
        border-left-color: #007bff;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }
    }
    </style>
</head>

<body>
    <!-- Include navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <!-- Profile Header -->
        <div class="profile-header mb-4">
            <div class="d-flex align-items-center">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h2 class="mb-1"><?= $user_data['name'] ?></h2>
                    <p class="mb-1"><?= $user_data['email'] ?></p>
                    <small>Member sejak <?= date('F Y', strtotime($user_data['created_at'])) ?></small>
                </div>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h4><?= $user_stats['total_orders'] ?></h4>
                    <p class="text-muted mb-0">Total Pesanan</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4><?= $user_stats['completed_orders'] ?></h4>
                    <p class="text-muted mb-0">Pesanan Selesai</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-rupiah-sign"></i>
                    </div>
                    <h4><?= formatRupiah($user_stats['total_spent']) ?></h4>
                    <p class="text-muted mb-0">Total Belanja</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4><?= $user_stats['total_reviews'] ?></h4>
                    <p class="text-muted mb-0">Ulasan Diberikan</p>
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
            <!-- Profile Tabs -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="pill"
                                    data-bs-target="#profile" type="button">
                                    <i class="fas fa-user me-2"></i>Profil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="password-tab" data-bs-toggle="pill"
                                    data-bs-target="#password" type="button">
                                    <i class="fas fa-lock me-2"></i>Password
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Profile Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <form method="POST">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?= htmlspecialchars($user_data['name']) ?>" required>
                                        <label for="name">Nama Lengkap</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email"
                                            value="<?= htmlspecialchars($user_data['email']) ?>" readonly>
                                        <label for="email">Email (tidak dapat diubah)</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" required>
                                        <label for="phone">Nomor Telepon</label>
                                    </div>

                                    <div class="form-floating">
                                        <textarea class="form-control" id="address" name="address" style="height: 100px"
                                            required><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                                        <label for="address">Alamat Lengkap</label>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" name="update_profile" class="btn btn-gradient">
                                            <i class="fas fa-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Password Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <form method="POST" id="changePasswordForm">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="current_password"
                                            name="current_password" required>
                                        <label for="current_password">Password Lama</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="new_password"
                                            name="new_password" minlength="6" required>
                                        <label for="new_password">Password Baru</label>
                                    </div>

                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="confirm_password"
                                            name="confirm_password" minlength="6" required>
                                        <label for="confirm_password">Konfirmasi Password Baru</label>
                                    </div>

                                    <div id="passwordMatchMessage" class="text-danger small mb-3"></div>

                                    <div class="text-end">
                                        <button type="submit" name="change_password" class="btn btn-gradient">
                                            <i class="fas fa-key"></i> Ubah Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-clock text-primary"></i>
                            Pesanan Terbaru
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($recent_orders_result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                        <div class="order-mini <?= $order['status'] ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>#<?= $order['id'] ?></strong>
                                    <br><small class="text-muted">
                                        <?= $order['total_items'] ?> item •
                                        <?= formatRupiah($order['total_amount']) ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?= 
                                            $order['status'] == 'pending' ? 'warning' : 
                                            ($order['status'] == 'confirmed' ? 'info' : 
                                            ($order['status'] == 'shipped' ? 'primary' : 
                                            ($order['status'] == 'delivered' ? 'success' : 'danger'))) 
                                        ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?= date('d M Y', strtotime($order['order_date'])) ?>
                            </small>
                        </div>
                        <?php endwhile; ?>

                        <div class="text-center mt-3">
                            <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list"></i> Lihat Semua Pesanan
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shopping-bag fa-2x mb-2"></i>
                            <p class="mb-0">Belum ada pesanan</p>
                            <a href="index.php" class="btn btn-primary btn-sm mt-2">
                                Mulai Belanja
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <!-- <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt text-warning"></i>
                            Aksi Cepat
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-shopping-bag"></i> Lihat Pesanan
                            </a>
                            <a href="cart.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-shopping-cart"></i> Keranjang
                            </a>
                            <button class="btn btn-outline-info btn-sm" onclick="openChat()">
                                <i class="fas fa-comments"></i> Chat CS
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-store"></i> Belanja Lagi
                            </a>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Password confirmation check
    function checkPasswordMatch() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const messageElement = document.getElementById('passwordMatchMessage');

        if (confirmPassword) {
            if (newPassword === confirmPassword) {
                messageElement.textContent = '';
                messageElement.className = 'text-success small mb-3';
                messageElement.innerHTML = '<i class="fas fa-check"></i> Password cocok';
            } else {
                messageElement.textContent = '';
                messageElement.className = 'text-danger small mb-3';
                messageElement.innerHTML = '<i class="fas fa-times"></i> Password tidak cocok';
            }
        } else {
            messageElement.textContent = '';
        }
    }

    document.getElementById('new_password').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

    // Form validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Konfirmasi password tidak cocok!');
            return false;
        }

        if (newPassword.length < 6) {
            e.preventDefault();
            alert('Password baru minimal 6 karakter!');
            return false;
        }
    });

    function openChat() {
        if (typeof openChatModal === 'function') {
            openChatModal();
        } else {
            window.location.href = '<?= BASE_URL ?>chat.php';
        }
    }

    // Auto-save profile changes
    let saveTimeout;
    document.querySelectorAll('#profile input, #profile textarea').forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                // Show auto-save indicator
                console.log('Auto-saving...');
            }, 3000);
        });
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>