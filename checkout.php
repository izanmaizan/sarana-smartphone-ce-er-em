<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Get user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get cart items
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
$cart_items = [];
$total_amount = 0;

while ($product = mysqli_fetch_assoc($cart_result)) {
    $product['quantity'] = $_SESSION['cart'][$product['id']];
    $product['subtotal'] = $product['final_price'] * $product['quantity'];
    $total_amount += $product['subtotal'];
    $cart_items[] = $product;
}

$shipping_cost = 0; // Free shipping
$final_total = $total_amount + $shipping_cost;

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    $delivery_phone = mysqli_real_escape_string($conn, $_POST['delivery_phone']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create order
        $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_status, order_date) 
                       VALUES ($user_id, $final_total, 'pending', 'pending', NOW())";
        
        if (!mysqli_query($conn, $order_query)) {
            throw new Exception("Gagal membuat pesanan");
        }
        
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items
        foreach ($cart_items as $item) {
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                          VALUES ($order_id, {$item['id']}, {$item['quantity']}, {$item['final_price']})";
            
            if (!mysqli_query($conn, $item_query)) {
                throw new Exception("Gagal menyimpan item pesanan");
            }
            
            // Update product stock
            $update_stock = "UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['id']}";
            if (!mysqli_query($conn, $update_stock)) {
                throw new Exception("Gagal mengupdate stok produk");
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to success page
        redirect("order_success.php?order_id=$order_id");
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .checkout-steps {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
    }

    .step {
        display: flex;
        align-items: center;
        padding: 10px 0;
    }

    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-weight: bold;
    }

    .step.active .step-number {
        background: #28a745;
    }

    .order-summary {
        background: #f8f9fa;
        border-radius: 10px;
    }

    .payment-method {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .payment-method:hover {
        border-color: #007bff;
        background: #f8f9fa;
    }

    .payment-method.selected {
        border-color: #007bff;
        background: #e7f3ff;
    }

    .btn-place-order {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        border: none;
        color: white;
        font-weight: bold;
        padding: 15px;
        border-radius: 10px;
        font-size: 1.1rem;
    }

    .btn-place-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }
    </style>
</head>

<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-mobile-alt"></i> Sarana Smartphone
            </a>
            <span class="navbar-text">
                <i class="fas fa-lock text-success"></i> Checkout Aman
            </span>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Checkout Steps -->
        <div class="checkout-steps p-4 mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="step completed">
                        <div class="step-number">✓</div>
                        <span>Keranjang</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step active">
                        <div class="step-number">2</div>
                        <span>Checkout</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step">
                        <div class="step-number">3</div>
                        <span>Pembayaran</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step">
                        <div class="step-number">4</div>
                        <span>Selesai</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <div class="row">
                <!-- Delivery Information -->
                <div class="col-lg-8">
                    <!-- Customer Information -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary"></i>
                                Informasi Pelanggan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" value="<?= $user_data['name'] ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= $user_data['email'] ?>"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt text-success"></i>
                                Alamat Pengiriman
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" name="delivery_address" rows="3"
                                    placeholder="Masukkan alamat lengkap untuk pengiriman"
                                    required><?= $user_data['address'] ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" name="delivery_phone"
                                    value="<?= $user_data['phone'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan Pengiriman (Opsional)</label>
                                <textarea class="form-control" name="notes" rows="2"
                                    placeholder="Contoh: Rumah cat hijau, sebelah warung Pak Budi"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card text-info"></i>
                                Metode Pembayaran
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="payment-method" onclick="selectPayment('transfer')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="transfer" id="transfer" required>
                                    <label for="transfer" class="ms-3 mb-0">
                                        <i class="fas fa-university text-primary fa-2x"></i>
                                        <div class="ms-3 d-inline-block">
                                            <strong>Transfer Bank</strong>
                                            <br><small class="text-muted">BCA, BNI, BRI, Mandiri</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="payment-method" onclick="selectPayment('ewallet')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="ewallet" id="ewallet" required>
                                    <label for="ewallet" class="ms-3 mb-0">
                                        <i class="fas fa-mobile text-success fa-2x"></i>
                                        <div class="ms-3 d-inline-block">
                                            <strong>E-Wallet</strong>
                                            <br><small class="text-muted">GoPay, OVO, DANA, ShopeePay</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="payment-method" onclick="selectPayment('cod')">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="cod" id="cod" required>
                                    <label for="cod" class="ms-3 mb-0">
                                        <i class="fas fa-money-bill-wave text-warning fa-2x"></i>
                                        <div class="ms-3 d-inline-block">
                                            <strong>Bayar di Tempat (COD)</strong>
                                            <br><small class="text-muted">Bayar tunai saat barang diterima</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt text-primary"></i>
                                Ringkasan Pesanan
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Order Items -->
                            <div class="order-summary p-3 mb-3">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?= substr($item['name'], 0, 30) ?>...</h6>
                                        <small class="text-muted">
                                            <?= formatRupiah($item['final_price']) ?> x <?= $item['quantity'] ?>
                                            <?php if ($item['has_discount']): ?>
                                            <span
                                                class="badge bg-danger ms-1">-<?= $item['discount_percentage'] ?>%</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <strong><?= formatRupiah($item['subtotal']) ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pricing Details -->
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal (<?= count($cart_items) ?> item)</span>
                                    <span><?= formatRupiah($total_amount) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Ongkos Kirim</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Biaya Admin</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total Pembayaran</strong>
                                    <strong class="text-primary fs-5"><?= formatRupiah($final_total) ?></strong>
                                </div>
                            </div>

                            <!-- Place Order Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" name="place_order" class="btn btn-place-order">
                                    <i class="fas fa-check-circle"></i>
                                    Buat Pesanan
                                </button>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Kembali ke Keranjang
                                </a>
                            </div>

                            <!-- Security Info -->
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt text-success"></i>
                                    Transaksi aman dengan enkripsi SSL
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body">
                            <h6><i class="fas fa-truck text-primary"></i> Informasi Pengiriman</h6>
                            <ul class="small text-muted mb-0">
                                <li>Pengiriman gratis untuk semua area</li>
                                <li>Estimasi tiba: 1-3 hari kerja</li>
                                <li>Barang dikemas dengan aman</li>
                                <li>Bisa tracking status pengiriman</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function selectPayment(method) {
        // Remove all selected classes
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });

        // Add selected class to clicked method
        event.currentTarget.classList.add('selected');

        // Check the radio button
        document.getElementById(method).checked = true;
    }

    // Form validation
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        const deliveryAddress = document.querySelector('input[name="delivery_address"]');
        const deliveryPhone = document.querySelector('input[name="delivery_phone"]');

        if (!paymentMethod) {
            e.preventDefault();
            alert('Silakan pilih metode pembayaran');
            return;
        }

        if (!deliveryAddress.value.trim()) {
            e.preventDefault();
            alert('Alamat pengiriman harus diisi');
            deliveryAddress.focus();
            return;
        }

        if (!deliveryPhone.value.trim()) {
            e.preventDefault();
            alert('Nomor telepon harus diisi');
            deliveryPhone.focus();
            return;
        }

        // Show loading
        const submitBtn = document.querySelector('button[name="place_order"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        submitBtn.disabled = true;
    });

    // Auto-select first payment method
    document.addEventListener('DOMContentLoaded', function() {
        const firstPayment = document.querySelector('.payment-method');
        if (firstPayment) {
            firstPayment.click();
        }
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>