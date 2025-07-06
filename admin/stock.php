<?php
// admin/stock.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle stock actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_stock':
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                $notes = mysqli_real_escape_string($conn, $_POST['notes']);
                $date = mysqli_real_escape_string($conn, $_POST['date']);
                
                if ($quantity > 0) {
                    // Insert stock record
                    $insert_query = "INSERT INTO stock_in (product_id, quantity, date, notes) 
                                   VALUES ($product_id, $quantity, '$date', '$notes')";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        // Update product stock
                        $update_stock = "UPDATE products SET stock = stock + $quantity WHERE id = $product_id";
                        mysqli_query($conn, $update_stock);
                        
                        $success = 'Stok berhasil ditambahkan';
                    } else {
                        $error = 'Gagal menambahkan stok';
                    }
                }
                break;
        }
    }
}

// Get filter parameters
$product_filter = isset($_GET['product']) ? $_GET['product'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Get products for dropdown
$products_query = "SELECT id, name FROM products WHERE status = 'active' ORDER BY name";
$products_result = mysqli_query($conn, $products_query);

// Build stock query
$stock_query = "
    SELECT s.*, p.name as product_name, p.stock as current_stock
    FROM stock_in s
    LEFT JOIN products p ON s.product_id = p.id
    WHERE 1=1
";

if (!empty($product_filter)) {
    $stock_query .= " AND s.product_id = " . intval($product_filter);
}

if (!empty($date_filter)) {
    $stock_query .= " AND DATE(s.date) = '" . mysqli_real_escape_string($conn, $date_filter) . "'";
}

$stock_query .= " ORDER BY s.created_at DESC";
$stock_result = mysqli_query($conn, $stock_query);

// Get products with low stock
$low_stock_query = "
    SELECT id, name, stock, 
           CASE 
               WHEN stock = 0 THEN 'Habis'
               WHEN stock <= 3 THEN 'Kritis'
               WHEN stock <= 10 THEN 'Rendah'
               ELSE 'Normal'
           END as stock_status
    FROM products 
    WHERE status = 'active' AND stock <= 10
    ORDER BY stock ASC
";
$low_stock_result = mysqli_query($conn, $low_stock_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - Admin Sarana Smartphone</title>
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

    .stock-alert {
        border-left: 4px solid;
        border-radius: 0 8px 8px 0;
    }

    .stock-alert.critical {
        border-left-color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
    }

    .stock-alert.low {
        border-left-color: #ffc107;
        background: rgba(255, 193, 7, 0.1);
    }

    .stock-alert.empty {
        border-left-color: #6c757d;
        background: rgba(108, 117, 125, 0.1);
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
                        <a class="nav-link active" href="stock.php">
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
                            <h2 class="mb-0">Kelola Stok</h2>
                            <small class="text-muted">Pantau dan kelola stok produk</small>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                            <i class="fas fa-plus"></i> Tambah Stok
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

                <div class="row">
                    <!-- Stock Alerts -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    Peringatan Stok
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                                <?php while ($product = mysqli_fetch_assoc($low_stock_result)): ?>
                                <div
                                    class="stock-alert <?= strtolower($product['stock_status']) == 'habis' ? 'empty' : (strtolower($product['stock_status']) == 'kritis' ? 'critical' : 'low') ?> p-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= $product['name'] ?></h6>
                                            <span class="badge bg-<?= 
                                                        strtolower($product['stock_status']) == 'habis' ? 'secondary' : 
                                                        (strtolower($product['stock_status']) == 'kritis' ? 'danger' : 'warning') 
                                                    ?>">
                                                <?= $product['stock_status'] ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?= $product['stock'] ?> unit</div>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="quickAddStock(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>')">
                                                <i class="fas fa-plus"></i> Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center p-4 text-success">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <p class="mb-0">Semua stok dalam kondisi baik!</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Stock History -->
                    <div class="col-lg-8">
                        <!-- Filters -->
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-5">
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
                                    <div class="col-md-3">
                                        <input type="date" name="date" class="form-control" value="<?= $date_filter ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="stock.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-refresh"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Stock History Table -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-info"></i>
                                    Riwayat Penambahan Stok
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Produk</th>
                                                <th>Jumlah</th>
                                                <th>Stok Saat Ini</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($stock_result) > 0): ?>
                                            <?php while ($stock = mysqli_fetch_assoc($stock_result)): ?>
                                            <tr>
                                                <td>
                                                    <?= date('d M Y', strtotime($stock['date'])) ?>
                                                    <br><small class="text-muted">
                                                        <?= date('H:i', strtotime($stock['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td><?= $stock['product_name'] ?></td>
                                                <td>
                                                    <span class="badge bg-success">+<?= $stock['quantity'] ?></span>
                                                </td>
                                                <td>
                                                    <strong><?= $stock['current_stock'] ?></strong> unit
                                                </td>
                                                <td>
                                                    <?= $stock['notes'] ?: '-' ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                                    <br>Belum ada riwayat penambahan stok.
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
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Stok Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_stock">

                        <div class="mb-3">
                            <label class="form-label">Produk</label>
                            <select class="form-select" name="product_id" id="product_select" required>
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
                            <label class="form-label">Jumlah Stok</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="2"
                                placeholder="Contoh: Restock dari supplier, Return barang, dll"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function quickAddStock(productId, productName) {
        const quantity = prompt(`Tambah stok untuk "${productName}"\nMasukkan jumlah:`);

        if (quantity && !isNaN(quantity) && quantity > 0) {
            // Create form
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'add_stock';

            const productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            productInput.value = productId;

            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = quantity;

            const dateInput = document.createElement('input');
            dateInput.type = 'hidden';
            dateInput.name = 'date';
            dateInput.value = '<?= date('Y-m-d') ?>';

            const notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'notes';
            notesInput.value = 'Quick add stock';

            form.appendChild(actionInput);
            form.appendChild(productInput);
            form.appendChild(quantityInput);
            form.appendChild(dateInput);
            form.appendChild(notesInput);

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Auto-fill product in modal if coming from URL parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('product_id');

        if (productId) {
            document.getElementById('product_select').value = productId;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('addStockModal'));
            modal.show();
        }
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>