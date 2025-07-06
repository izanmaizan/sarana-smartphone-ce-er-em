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
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);
                $unit_id = intval($_POST['unit_id']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                
                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image']['name'];
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, $allowed)) {
                        $new_filename = time() . '_' . uniqid() . '.' . $extension;
                        $upload_path = '../uploads/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image = $new_filename;
                        }
                    }
                }
                
                $insert_query = "INSERT INTO products (name, description, price, stock, category_id, unit_id, image, status) 
                               VALUES ('$name', '$description', $price, $stock, $category_id, $unit_id, '$image', '$status')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = 'Produk berhasil ditambahkan';
                } else {
                    $error = 'Gagal menambahkan produk';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);
                $unit_id = intval($_POST['unit_id']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                
                // Handle image upload
                $image_update = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image']['name'];
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, $allowed)) {
                        $new_filename = time() . '_' . uniqid() . '.' . $extension;
                        $upload_path = '../uploads/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            // Delete old image
                            $old_image_query = "SELECT image FROM products WHERE id = $id";
                            $old_image_result = mysqli_query($conn, $old_image_query);
                            $old_image = mysqli_fetch_assoc($old_image_result);
                            if ($old_image['image'] && file_exists('../uploads/' . $old_image['image'])) {
                                unlink('../uploads/' . $old_image['image']);
                            }
                            
                            $image_update = ", image = '$new_filename'";
                        }
                    }
                }
                
                $update_query = "UPDATE products SET 
                               name = '$name', 
                               description = '$description', 
                               price = $price, 
                               stock = $stock, 
                               category_id = $category_id, 
                               unit_id = $unit_id, 
                               status = '$status' 
                               $image_update 
                               WHERE id = $id";
                
                if (mysqli_query($conn, $update_query)) {
                    $success = 'Produk berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate produk';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Get image for deletion
                $image_query = "SELECT image FROM products WHERE id = $id";
                $image_result = mysqli_query($conn, $image_query);
                $image_data = mysqli_fetch_assoc($image_result);
                
                $delete_query = "DELETE FROM products WHERE id = $id";
                
                if (mysqli_query($conn, $delete_query)) {
                    // Delete image file
                    if ($image_data['image'] && file_exists('../uploads/' . $image_data['image'])) {
                        unlink('../uploads/' . $image_data['image']);
                    }
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
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Get categories and units for dropdowns
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

$units_query = "SELECT id, name FROM units ORDER BY name";
$units_result = mysqli_query($conn, $units_query);

// Build products query
$products_query = "
    SELECT p.*, c.name as category_name, u.name as unit_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN units u ON p.unit_id = u.id 
    WHERE 1=1
";

if (!empty($category_filter)) {
    $products_query .= " AND p.category_id = " . intval($category_filter);
}

if (!empty($status_filter)) {
    $products_query .= " AND p.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

if (!empty($search)) {
    $products_query .= " AND (p.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                        OR p.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

if ($stock_filter == 'low') {
    $products_query .= " AND p.stock <= 5";
} elseif ($stock_filter == 'out') {
    $products_query .= " AND p.stock = 0";
}

$products_query .= " ORDER BY p.created_at DESC";
$products_result = mysqli_query($conn, $products_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_products,
        COUNT(CASE WHEN stock <= 5 THEN 1 END) as low_stock_products,
        COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock,
        COALESCE(SUM(stock), 0) as total_stock
    FROM products
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
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

    .product-card {
        transition: transform 0.3s;
        border-left: 4px solid #e9ecef;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .product-card.active {
        border-left-color: #28a745;
    }

    .product-card.inactive {
        border-left-color: #dc3545;
    }

    .product-card.low-stock {
        border-left-color: #ffc107;
    }

    .product-card.out-of-stock {
        border-left-color: #6c757d;
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

    .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
    }

    .stock-badge {
        position: absolute;
        top: 10px;
        right: 10px;
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
                            <small class="text-muted">Tambah, edit, dan kelola produk smartphone</small>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-box"></i>
                            </div>
                            <h4><?= $stats['total_products'] ?></h4>
                            <p class="text-muted mb-0 small">Total Produk</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #28a745;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4><?= $stats['active_products'] ?></h4>
                            <p class="text-muted mb-0 small">Aktif</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #dc3545;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h4><?= $stats['inactive_products'] ?></h4>
                            <p class="text-muted mb-0 small">Tidak Aktif</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #ffc107;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4><?= $stats['low_stock_products'] ?></h4>
                            <p class="text-muted mb-0 small">Stok Rendah</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: #6c757d;">
                                <i class="fas fa-ban"></i>
                            </div>
                            <h4><?= $stats['out_of_stock'] ?></h4>
                            <p class="text-muted mb-0 small">Habis</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon"
                                style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <h4><?= $stats['total_stock'] ?></h4>
                            <p class="text-muted mb-0 small">Total Stok</p>
                        </div>
                    </div>
                </div>

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
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif
                                    </option>
                                    <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Tidak
                                        Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="stock" class="form-select">
                                    <option value="">Semua Stok</option>
                                    <option value="low" <?= $stock_filter == 'low' ? 'selected' : '' ?>>Stok Rendah
                                    </option>
                                    <option value="out" <?= $stock_filter == 'out' ? 'selected' : '' ?>>Habis</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control" placeholder="Cari produk..."
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php if (mysqli_num_rows($products_result) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                    <?php
                        $card_class = $product['status'];
                        if ($product['stock'] == 0) {
                            $card_class = 'out-of-stock';
                        } elseif ($product['stock'] <= 5) {
                            $card_class = 'low-stock';
                        }
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card <?= $card_class ?> border-0 shadow-sm h-100">
                            <div class="card-body position-relative">
                                <!-- Stock Badge -->
                                <div class="stock-badge">
                                    <span class="badge bg-<?= 
                                        $product['stock'] == 0 ? 'secondary' : 
                                        ($product['stock'] <= 5 ? 'warning' : 'success') 
                                    ?>">
                                        <?= $product['stock'] ?> unit
                                    </span>
                                </div>

                                <div class="d-flex mb-3">
                                    <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                                        class="product-image me-3" alt="<?= $product['name'] ?>">
                                    <div class="flex-grow-1">
                                        <h6 class="text-primary mb-1"><?= $product['name'] ?></h6>
                                        <small class="text-muted"><?= $product['category_name'] ?></small>
                                        <div class="mt-1">
                                            <span
                                                class="badge bg-<?= $product['status'] == 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($product['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-muted small mb-3"><?= substr($product['description'], 0, 100) ?>...</p>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-success"><?= formatRupiah($product['price']) ?></strong>
                                        <small class="text-muted">per <?= $product['unit_name'] ?></small>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?= date('d M Y', strtotime($product['created_at'])) ?>
                                    </small>
                                    <div class="btn-group">
                                        <button class="btn btn-outline-primary btn-sm"
                                            onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="stock.php?product_id=<?= $product['id'] ?>"
                                            class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm"
                                            onclick="deleteProduct(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                                <i class="fas fa-box fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">Tidak Ada Produk</h4>
                                <p class="text-muted">
                                    <?php if (!empty($search) || !empty($category_filter) || !empty($status_filter)): ?>
                                    Tidak ada produk yang sesuai dengan filter yang dipilih.
                                    <?php else: ?>
                                    Belum ada produk yang ditambahkan.
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
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Harga</label>
                                            <input type="number" class="form-control" name="price" min="0" step="0.01"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Stok</label>
                                            <input type="number" class="form-control" name="stock" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
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
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Satuan</label>
                                            <select class="form-select" name="unit_id" required>
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
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar Produk</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Max: 2MB</small>
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

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="description" id="edit_description"
                                        rows="3"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Harga</label>
                                            <input type="number" class="form-control" name="price" id="edit_price"
                                                min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Stok</label>
                                            <input type="number" class="form-control" name="stock" id="edit_stock"
                                                min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select class="form-select" name="category_id" id="edit_category_id"
                                                required>
                                                <?php 
                                                mysqli_data_seek($categories_result, 0);
                                                while ($category = mysqli_fetch_assoc($categories_result)): 
                                                ?>
                                                <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Satuan</label>
                                            <select class="form-select" name="unit_id" id="edit_unit_id" required>
                                                <?php 
                                                mysqli_data_seek($units_result, 0);
                                                while ($unit = mysqli_fetch_assoc($units_result)): 
                                                ?>
                                                <option value="<?= $unit['id'] ?>"><?= $unit['name'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
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

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar Produk</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                                    <div id="current_image" class="mt-2"></div>
                                </div>
                            </div>
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
    function editProduct(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_description').value = product.description;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_category_id').value = product.category_id;
        document.getElementById('edit_unit_id').value = product.unit_id;
        document.getElementById('edit_status').value = product.status;

        // Show current image
        const currentImageDiv = document.getElementById('current_image');
        if (product.image) {
            currentImageDiv.innerHTML =
                `<img src="../uploads/${product.image}" class="img-thumbnail" style="max-width: 100px;">`;
        } else {
            currentImageDiv.innerHTML = '<small class="text-muted">Tidak ada gambar</small>';
        }

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
    </script>
</body>

</html>

<?php closeConnection($conn); ?>