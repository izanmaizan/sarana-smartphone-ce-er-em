<?php
// admin/units.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Handle unit actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                
                $insert_query = "INSERT INTO units (name) VALUES ('$name')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = 'Satuan berhasil ditambahkan';
                } else {
                    $error = 'Gagal menambahkan satuan';
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                
                $update_query = "UPDATE units SET name = '$name' WHERE id = $id";
                
                if (mysqli_query($conn, $update_query)) {
                    $success = 'Satuan berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate satuan';
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Check if unit is used
                $check_query = "SELECT COUNT(*) as count FROM products WHERE unit_id = $id";
                $check_result = mysqli_query($conn, $check_query);
                $check = mysqli_fetch_assoc($check_result);
                
                if ($check['count'] > 0) {
                    $error = 'Satuan tidak dapat dihapus karena masih digunakan oleh produk';
                } else {
                    $delete_query = "DELETE FROM units WHERE id = $id";
                    
                    if (mysqli_query($conn, $delete_query)) {
                        $success = 'Satuan berhasil dihapus';
                    } else {
                        $error = 'Gagal menghapus satuan';
                    }
                }
                break;
        }
    }
}

// Get all units
$units_query = "
    SELECT u.*, 
           COUNT(p.id) as product_count
    FROM units u
    LEFT JOIN products p ON u.id = p.unit_id AND p.status = 'active'
    GROUP BY u.id, u.name, u.created_at
    ORDER BY u.name
";
$units_result = mysqli_query($conn, $units_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Satuan - Admin Sarana Smartphone</title>
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

    .unit-card {
        transition: transform 0.3s;
        border-left: 4px solid #007bff;
    }

    .unit-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
                        <a class="nav-link active" href="units.php">
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
                            <h2 class="mb-0">Kelola Satuan</h2>
                            <small class="text-muted">Tambah, edit, dan kelola satuan produk</small>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                            <i class="fas fa-plus"></i> Tambah Satuan
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

                <!-- Units Grid -->
                <div class="row">
                    <?php while ($unit = mysqli_fetch_assoc($units_result)): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card unit-card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-ruler fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title"><?= $unit['name'] ?></h5>
                                <p class="text-muted">
                                    <span class="badge bg-primary"><?= $unit['product_count'] ?> produk</span>
                                </p>
                                <small class="text-muted">
                                    Dibuat: <?= date('d M Y', strtotime($unit['created_at'])) ?>
                                </small>

                                <div class="mt-3">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-outline-primary btn-sm"
                                            onclick="editUnit(<?= htmlspecialchars(json_encode($unit)) ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm"
                                            onclick="deleteUnit(<?= $unit['id'] ?>, '<?= $unit['name'] ?>')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Unit Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Satuan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label class="form-label">Nama Satuan</label>
                            <input type="text" class="form-control" name="name"
                                placeholder="Contoh: Unit, Pcs, Set, Buah" required>
                            <small class="text-muted">Masukkan satuan yang akan digunakan untuk produk</small>
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

    <!-- Edit Unit Modal -->
    <div class="modal fade" id="editUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Satuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="mb-3">
                            <label class="form-label">Nama Satuan</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
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
    function editUnit(unit) {
        document.getElementById('edit_id').value = unit.id;
        document.getElementById('edit_name').value = unit.name;

        const modal = new bootstrap.Modal(document.getElementById('editUnitModal'));
        modal.show();
    }

    function deleteUnit(id, name) {
        if (confirm(
                `Yakin ingin menghapus satuan "${name}"?\n\nPerhatian: Satuan yang masih digunakan produk tidak bisa dihapus.`
            )) {
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