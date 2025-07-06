<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($quantity > 0) {
                    // Check if item exists in cart
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id] = $quantity;
                    }
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                break;
                
            case 'remove_item':
                $product_id = intval($_POST['product_id']);
                unset($_SESSION['cart'][$product_id]);
                break;
                
            case 'clear_cart':
                unset($_SESSION['cart']);
                break;
        }
    }
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_items = [];
$total_amount = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = implode(',', array_keys($_SESSION['cart']));
    
    $cart_query = "
        SELECT p.*, c.name as category_name,
               d.percentage as discount_percentage,
               CASE 
                   WHEN d.percentage IS NOT NULL AND d.status = 'active' 
                   AND CURDATE() BETWEEN d.start_date AND d.end_date 
                   THEN p.price - (p.price * d.percentage / 100)
                   ELSE p.price 
               END as final_price,
               CASE 
                   WHEN d.percentage IS NOT NULL AND d.status = 'active' 
                   AND CURDATE() BETWEEN d.start_date AND d.end_date 
                   THEN 1 ELSE 0 
               END as has_discount
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN discounts d ON p.id = d.product_id 
        WHERE p.id IN ($product_ids) AND p.status = 'active'
    ";
    
    $cart_result = mysqli_query($conn, $cart_query);
    
    while ($product = mysqli_fetch_assoc($cart_result)) {
        $product['quantity'] = $_SESSION['cart'][$product['id']];
        $product['subtotal'] = $product['final_price'] * $product['quantity'];
        $total_amount += $product['subtotal'];
        $cart_items[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        padding-top: 80px;
        background-color: #f8f9fa;
    }

    .cart-item {
        transition: all 0.3s;
    }

    .cart-item:hover {
        background-color: #f8f9fa;
    }

    .quantity-input {
        width: 80px;
    }

    .cart-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        position: sticky;
        top: 100px;
    }

    .btn-checkout {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        border: none;
        color: white;
        font-weight: bold;
        padding: 12px 30px;
        border-radius: 25px;
        transition: transform 0.3s;
    }

    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }

    .empty-cart {
        text-align: center;
        color: #6c757d;
    }

    .discount-badge {
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
    }

    .original-price {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9em;
    }

    .breadcrumb-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-top: -80px;
        padding-top: 120px;
    }

    .breadcrumb-section .breadcrumb {
        background: none;
        margin-bottom: 0;
    }

    .breadcrumb-section .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }

    .breadcrumb-section .breadcrumb-item.active {
        color: white;
    }
    </style>
</head>

<body>
    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Breadcrumb Section -->
    <section class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Keranjang Belanja</li>
                </ol>
            </nav>
            <h1 class="h2 mb-0">
                <i class="fas fa-shopping-cart me-2"></i>
                Keranjang Belanja
                <?php if (!empty($cart_items)): ?>
                <span class="badge bg-light text-dark"><?= count($cart_items) ?> item</span>
                <?php endif; ?>
            </h1>
        </div>
    </section>

    <div class="container py-5">
        <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart fa-5x mb-4 text-muted"></i>
                            <h4>Keranjang Anda Kosong</h4>
                            <p class="mb-4">Belum ada produk yang ditambahkan ke keranjang. Yuk mulai berbelanja!</p>
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag"></i> Mulai Belanja
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Cart Items -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Item dalam Keranjang</h5>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Yakin ingin mengosongkan keranjang?')">
                                    <i class="fas fa-trash"></i> Kosongkan Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?= BASE_URL . UPLOAD_PATH . ($item['image'] ?: 'no-image.jpg') ?>"
                                        class="img-fluid rounded" alt="<?= $item['name'] ?>"
                                        style="height: 80px; object-fit: cover;">
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-1"><?= $item['name'] ?></h6>
                                    <small class="text-muted"><?= $item['category_name'] ?></small>
                                    <?php if ($item['has_discount']): ?>
                                    <br><span class="discount-badge">-<?= $item['discount_percentage'] ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <div class="price-info">
                                        <?php if ($item['has_discount']): ?>
                                        <div class="original-price"><?= formatRupiah($item['price']) ?></div>
                                        <strong class="text-success"><?= formatRupiah($item['final_price']) ?></strong>
                                        <?php else: ?>
                                        <strong><?= formatRupiah($item['final_price']) ?></strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="changeQuantity(<?= $item['id'] ?>, -1)">-</button>
                                            <input type="number" class="form-control text-center quantity-input"
                                                name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                                max="<?= $item['stock'] ?>"
                                                onchange="updateQuantity(<?= $item['id'] ?>, this.value)">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="changeQuantity(<?= $item['id'] ?>, 1)">+</button>
                                        </div>
                                    </form>
                                    <small class="text-muted">Stok: <?= $item['stock'] ?></small>
                                </div>
                                <div class="col-md-1 text-center">
                                    <strong><?= formatRupiah($item['subtotal']) ?></strong>
                                </div>
                                <div class="col-md-1 text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Hapus item ini dari keranjang?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Shopping Tips -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6><i class="fas fa-lightbulb text-warning"></i> Tips Belanja</h6>
                        <ul class="small text-muted mb-0">
                            <li>Pastikan stok tersedia sebelum checkout</li>
                            <li>Manfaatkan diskon yang sedang berlangsung</li>
                            <li>Hubungi customer service jika ada pertanyaan</li>
                            <li>Simpan produk favorit untuk pembelian berikutnya</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body cart-summary">
                            <h5 class="mb-4">
                                <i class="fas fa-calculator"></i>
                                Ringkasan Pesanan
                            </h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?= count($cart_items) ?> item)</span>
                                <span><?= formatRupiah($total_amount) ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Ongkos Kirim</span>
                                <span class="text-success">GRATIS</span>
                            </div>

                            <hr class="border-light">

                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total Pembayaran</strong>
                                <strong class="fs-5"><?= formatRupiah($total_amount) ?></strong>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="checkout.php" class="btn btn-checkout btn-lg">
                                    <i class="fas fa-credit-card"></i>
                                    Checkout Sekarang
                                </a>
                                <a href="index.php" class="btn btn-outline-light">
                                    <i class="fas fa-plus"></i>
                                    Tambah Produk Lain
                                </a>
                            </div>

                            <div class="mt-4 text-center">
                                <small>
                                    <i class="fas fa-shield-alt"></i>
                                    Pembayaran aman & terpercaya
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promo Code -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6><i class="fas fa-tag text-success"></i> Kode Promo</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Masukkan kode promo">
                            <button class="btn btn-outline-success" type="button">
                                Gunakan
                            </button>
                        </div>
                        <small class="text-muted">*Fitur coming soon</small>
                    </div>
                </div>

                <!-- Customer Service -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body text-center">
                        <h6><i class="fas fa-headset text-info"></i> Butuh Bantuan?</h6>
                        <p class="small text-muted mb-3">
                            Tim customer service kami siap membantu Anda
                        </p>
                        <button class="btn btn-info btn-sm" onclick="openChat()">
                            <i class="fas fa-comments"></i> Chat Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Toko Sarana Smartphone CRM System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Set global variables
    const IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
    const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
    const BASE_URL = '<?= BASE_URL ?>';

    function changeQuantity(productId, change) {
        const input = document.querySelector(
            `input[name="quantity"][data-product="${productId}"], 
                                                form input[name="product_id"][value="${productId}"] + input[name="quantity"], 
                                                form:has(input[name="product_id"][value="${productId}"]) input[name="quantity"]`
        );
        if (input) {
            const currentValue = parseInt(input.value);
            const newValue = Math.max(1, currentValue + change);
            const maxValue = parseInt(input.getAttribute('max')) || 999;

            if (newValue <= maxValue) {
                input.value = newValue;
                updateQuantity(productId, newValue);
            }
        }
    }

    function updateQuantity(productId, quantity) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_quantity';

        const productInput = document.createElement('input');
        productInput.type = 'hidden';
        productInput.name = 'product_id';
        productInput.value = productId;

        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.name = 'quantity';
        quantityInput.value = quantity;

        form.appendChild(actionInput);
        form.appendChild(productInput);
        form.appendChild(quantityInput);

        document.body.appendChild(form);
        form.submit();
    }

    // Auto-save quantity changes after 2 seconds of inactivity
    let quantityTimer;
    document.querySelectorAll('input[name="quantity"]').forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(quantityTimer);
            const productId = this.closest('form').querySelector('input[name="product_id"]').value;
            const quantity = this.value;

            quantityTimer = setTimeout(() => {
                if (quantity > 0) {
                    updateQuantity(productId, quantity);
                }
            }, 2000);
        });
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>