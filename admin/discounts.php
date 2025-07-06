<?php
// admin/discounts.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle discount actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product_id = intval($_POST['product_id']);
                $percentage = floatval($_POST['percentage']);
                $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
                $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
                
                // Check if product already has active discount
                $check_query = "SELECT id FROM discounts WHERE product_id = $product_id AND status = 'active'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error = 'Produk sudah memiliki diskon aktif';
                } else {
                    $insert_query = "INSERT INTO discounts (product_id, percentage, start_date, end_date, status) 
                                   VALUES ($product_id, $percentage, '$start_date', '$end_date', 'active')";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $success = 'Diskon berhasil ditambahkan';
                    } else {
                        $error = 'Gagal menambahkan diskon';
                    }
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $percentage = floatval($_POST['percentage']);
                $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
                $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                
                $update_query = "UPDATE discounts SET 
                               percentage = $percentage, 
                               start_date = '$start_date', 
                               end_date = '$end_date',
                               status = '$status'
                               WHERE id = $id";
                
                if (mysqli_query($conn, $update_query)) {
                    $success = 'Diskon berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate diskon';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $delete_query = "DELETE FROM discounts WHERE id = $id";
                
                if (mysqli_query($conn, $delete_query)) {
                    $success = 'Diskon berhasil dihapus';
                } else {
                    $error = 'Gagal menghapus diskon';
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$product_filter = isset($_GET['product']) ? $_GET['product'] : '';

// Get products for dropdown
$products_query = "SELECT id, name FROM products WHERE status = 'active' ORDER BY name";
$products_result = mysqli_query($conn, $products_query);

// Build discounts query
$discounts_query = "
    SELECT d.*, p.name as product_name, p.price as product_price
    FROM discounts d
    LEFT JOIN products p ON d.product_id = p.id
    WHERE 1=1
";

if (!empty($status_filter)) {
    $discounts_query .= " AND d.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if (!empty($product_filter)) {
    $discounts_query .= " AND d.product_id = " . intval($product_filter);
}

$discounts_query .= " ORDER BY d.created_at DESC";
$discounts_result = mysqli_query($conn, $discounts_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Diskon - Admin Sarana Smartphone</title>
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

    .discount-card {
        transition: transform 0.3s;
        border-left: 4px solid #e9ecef;
    }

    .discount-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .discount-card.active {
        border-left-color: #28a745;
    }

    .discount-card.inactive {
        border-left-color: #dc3545;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (same as categories.php) -->
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
                        <a class="nav-link active" href="discounts.php">
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
                            <h2 class="mb-0">Kelola Diskon</h2>
                            <small class="text-muted">Tambah dan kelola diskon produk</small>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDiscountModal">
                            <i class="fas fa-plus"></i> Tambah Diskon
                        </button>
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

                <!-- Filters -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif
                                    </option>
                                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Tidak
                                        Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <select name="product" class="form-select">
                                    <option value="">Semua Produk</option>
                                    <?php 
                                    mysqli_data_seek($products_result, 0);
                                    while ($product = mysqli_fetch_assoc($products_result)): 
                                    ?>
                                    <option value="<?= $product['id'] ?>"
                                        <?= $product_filter == $product['id'] ? 'selected' : '' ?>>
                                        <?= $product['name'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Discounts List -->
                <div class="row">
                    <?php if (mysqli_num_rows($discounts_result) > 0): ?>
                    <?php while ($discount = mysqli_fetch_assoc($discounts_result)): ?>
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card discount-card <?= $discount['status'] ?> border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="text-primary mb-1"><?= $discount['product_name'] ?></h6>
                                        <span class="badge bg-danger fs-6"><?= $discount['percentage'] ?>% OFF</span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    onclick="editDiscount(<?= htmlspecialchars(json_encode($discount)) ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#"
                                                    onclick="deleteDiscount(<?= $discount['id'] ?>, '<?= $discount['product_name'] ?>')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Harga Normal:</span>
                                        <span
                                            class="text-decoration-line-through"><?= formatRupiah($discount['product_price']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Harga Diskon:</span>
                                        <strong class="text-success">
                                            <?= formatRupiah($discount['product_price'] - ($discount['product_price'] * $discount['percentage'] / 100)) ?>
                                        </strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Hemat:</span>
                                        <span class="text-danger">
                                            <?= formatRupiah($discount['product_price'] * $discount['percentage'] / 100) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d M Y', strtotime($discount['start_date'])) ?> -
                                        <?= date('d M Y', strtotime($discount['end_date'])) ?>
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span
                                        class="badge bg-<?= $discount['status'] == 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($discount['status']) ?>
                                    </span>

                                    <?php
                                            $now = date('Y-m-d');
                                            $is_expired = $now > $discount['end_date'];
                                            $is_future = $now < $discount['start_date'];
                                            ?>

                                    <?php if ($is_expired): ?>
                                    <span class="badge bg-warning">Expired</span>
                                    <?php elseif ($is_future): ?>
                                    <span class="badge bg-info">Belum Dimulai</span>
                                    <?php elseif ($discount['status'] == 'active'): ?>
                                    <span class="badge bg-success">Berjalan</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-percent fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">Tidak Ada Diskon</h4>
                                <p class="text-muted">Belum ada diskon yang dibuat.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Discount Modal -->
    <div class="modal fade" id="addDiscountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Diskon Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label class="form-label">Produk</label>
                            <select class="form-select" name="product_id" required>
                                <option value="">Pilih Produk</option>
                                <?php 
                                mysqli_data_seek($products_result, 0);
                                while ($product = mysqli_fetch_assoc($products_result)): 
                                ?>
                                <option value="<?= $product['id'] ?>"><?= $product['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Persentase Diskon (%)</label>
                            <input type="number" class="form-control" name="percentage" min="1" max="99" step="0.1"
                                required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Berakhir</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Discount Modal -->
    <div class="modal fade" id="editDiscountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Diskon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="mb-3">
                            <label class="form-label">Produk</label>
                            <input type="text" class="form-control" id="edit_product_name" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Persentase Diskon (%)</label>
                            <input type="number" class="form-control" name="percentage" id="edit_percentage" min="1"
                                max="99" step="0.1" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" name="start_date" id="edit_start_date"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Berakhir</label>
                                    <input type="date" class="form-control" name="end_date" id="edit_end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function editDiscount(discount) {
        document.getElementById('edit_id').value = discount.id;
        document.getElementById('edit_product_name').value = discount.product_name;
        document.getElementById('edit_percentage').value = discount.percentage;
        document.getElementById('edit_start_date').value = discount.start_date;
        document.getElementById('edit_end_date').value = discount.end_date;
        document.getElementById('edit_status').value = discount.status;

        const modal = new bootstrap.Modal(document.getElementById('editDiscountModal'));
        modal.show();
    }

    function deleteDiscount(id, productName) {
        if (confirm(`Yakin ingin menghapus diskon untuk "${productName}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;

            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="start_date"]').setAttribute('min', today);
        document.querySelector('input[name="end_date"]').setAttribute('min', today);
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>