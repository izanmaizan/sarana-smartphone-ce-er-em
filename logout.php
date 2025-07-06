<?php
// logout.php
require_once 'config.php';

// Clear all session data
session_unset();
session_destroy();

// Start new session for message
session_start();
$_SESSION['logout_message'] = 'Anda telah berhasil logout.';

// Redirect to login page
redirect('login.php');
?>

---

<?php
// admin/products.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $category_id = intval($_POST['category_id']);
                $unit_id = intval($_POST['unit_id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    $image = uploadImage($_FILES['image'], 'products');
                    if (!$image) {
                        $error = 'Gagal mengupload gambar';
                        break;
                    }
                }
                
                $insert_query = "INSERT INTO products (category_id, unit_id, name, description, price, stock, image) 
                               VALUES ($category_id, $unit_id, '$name', '$description', $price, $stock, '$image')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = 'Produk berhasil ditambahkan';
                } else {
                    $error = 'Gagal menambahkan produk';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $category_id = intval($_POST['category_id']);
                $unit_id = intval($_POST['unit_id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                
                $update_query = "UPDATE products SET 
                               category_id = $category_id, 
                               unit_id = $unit_id,
                               name = '$name', 
                               description = '$description', 
                               price = $price, 
                               stock = $stock";
                
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    $image = uploadImage($_FILES['image'], 'products');
                    if ($image) {
                        $update_query .= ", image = '$image'";
                    }
                }
                
                $update_query .= " WHERE id = $id";
                
                if (mysqli_query($conn, $update_query)) {
                    $success = 'Produk berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate produk';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $delete_query = "UPDATE products SET status = 'inactive' WHERE id = $id";
                
                if (mysqli_query($conn, $delete_query)) {
                    $success = 'Produk berhasil dihapus';
                } else {
                    $error = 'Gagal menghapus produk';
                }
                break;
        }
    }
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Get categories and units for dropdowns
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

$units_query = "SELECT * FROM units ORDER BY name";
$units_result = mysqli_query($conn, $units_query);

// Build products query
$products_query = "
    SELECT p.*, c.name as category_name, u.name as unit_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN units u ON p.unit_id = u.id
    WHERE p.status = 'active'
";

if (!empty($category_filter)) {
    $products_query .= " AND p.category_id = " . intval($category_filter);
}

if (!empty($search)) {
    $products_query .= " AND (p.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                        OR p.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

if ($filter == 'low_stock') {
    $products_query .= " AND p.stock <= 5";
}

$products_query .= " ORDER BY p.created_at DESC";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin Sarana Smartphone</title>
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

    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }

    .stock-badge {
        font-size: 0.75rem;
    }

    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
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
                        <a class="nav-link active" href="products.php">
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
                            <h2 class="mb-0">Kelola Produk</h2>
                            <small class="text-muted">Tambah, edit, dan kelola produk</small>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i> Tambah Produk
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
                                <select name="category" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                    <option value="<?= $category['id'] ?>"
                                        <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                        <?= $category['name'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filter" class="form-select">
                                    <option value="">Semua Produk</option>
                                    <option value="low_stock" <?= $filter == 'low_stock' ? 'selected' : '' ?>>
                                        Stok Menipis
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Cari produk..."
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Nama Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($products_result) > 0): ?>
                                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                                                class="product-image" alt="<?= $product['name'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= $product['name'] ?></strong>
                                            <br><small
                                                class="text-muted"><?= substr($product['description'], 0, 50) ?>...</small>
                                        </td>
                                        <td><?= $product['category_name'] ?></td>
                                        <td><?= formatRupiah($product['price']) ?></td>
                                        <td>
                                            <span
                                                class="badge stock-badge <?= $product['stock'] <= 5 ? 'bg-danger' : ($product['stock'] <= 10 ? 'bg-warning' : 'bg-success') ?>">
                                                <?= $product['stock'] ?> <?= $product['unit_name'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Aktif</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-outline-primary btn-action"
                                                    onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-info btn-action"
                                                    onclick="addStock(<?= $product['id'] ?>, '<?= $product['name'] ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-action"
                                                    onclick="deleteProduct(<?= $product['id'] ?>, '<?= $product['name'] ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-box fa-3x mb-3"></i>
                                            <br>Tidak ada produk yang ditemukan.
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($category = mysqli_fetch_assoc($categories_result)): 
                                        ?>
                                        <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-select" name="unit_id" required>
                                        <option value="">Pilih Satuan</option>
                                        <?php while ($unit = mysqli_fetch_assoc($units_result)): ?>
                                        <option value="<?= $unit['id'] ?>"><?= $unit['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga</label>
                                    <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stok Awal</label>
                                    <input type="number" class="form-control" name="stock" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id" id="edit_category_id" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($category = mysqli_fetch_assoc($categories_result)): 
                                        ?>
                                        <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-select" name="unit_id" id="edit_unit_id" required>
                                        <option value="">Pilih Satuan</option>
                                        <?php 
                                        mysqli_data_seek($units_result, 0);
                                        while ($unit = mysqli_fetch_assoc($units_result)): 
                                        ?>
                                        <option value="<?= $unit['id'] ?>"><?= $unit['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Harga</label>
                                    <input type="number" class="form-control" name="price" id="edit_price" min="0"
                                        step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stok</label>
                                    <input type="number" class="form-control" name="stock" id="edit_stock" min="0"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"
                                required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gambar Produk (Opsional - biarkan kosong jika tidak ingin
                                mengubah)</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function editProduct(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_category_id').value = product.category_id;
        document.getElementById('edit_unit_id').value = product.unit_id;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_description').value = product.description;

        const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
        modal.show();
    }

    function deleteProduct(id, name) {
        if (confirm(`Yakin ingin menghapus produk "${name}"?`)) {
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

    function addStock(id, name) {
        const quantity = prompt(`Tambah stok untuk "${name}":\nMasukkan jumlah:`);
        if (quantity && !isNaN(quantity) && quantity > 0) {
            window.location.href = `stock.php?action=add&product_id=${id}&quantity=${quantity}`;
        }
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>