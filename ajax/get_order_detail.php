<?php
// ajax/get_order_detail.php
require_once '../config.php';

if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

$order_id = intval($_GET['id']);
$is_admin = isset($_GET['admin']) && isAdmin();

$conn = getConnection();

// Security check - users can only view their own orders unless admin
if (!$is_admin) {
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT id FROM orders WHERE id = $order_id AND user_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        echo '<div class="alert alert-danger">Order not found</div>';
        exit;
    }
}

// Get order details
$order_query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email, 
           u.phone as customer_phone, u.address as customer_address
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id
";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    echo '<div class="alert alert-danger">Order not found</div>';
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "
    SELECT oi.*, p.name as product_name, p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
";
$items_result = mysqli_query($conn, $items_query);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Informasi Pesanan</h6>
        <table class="table table-sm">
            <tr>
                <td width="120">ID Pesanan</td>
                <td><strong>#<?= $order['id'] ?></strong></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td><?= date('d F Y, H:i', strtotime($order['order_date'])) ?> WIB</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>
                    <span class="badge bg-<?= 
                        $order['status'] == 'pending' ? 'warning' : 
                        ($order['status'] == 'confirmed' ? 'info' : 
                        ($order['status'] == 'shipped' ? 'primary' : 
                        ($order['status'] == 'delivered' ? 'success' : 'danger'))) 
                    ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>Pembayaran</td>
                <td>
                    <span class="badge bg-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>Total</td>
                <td><strong><?= formatRupiah($order['total_amount']) ?></strong></td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h6>Informasi Pelanggan</h6>
        <table class="table table-sm">
            <tr>
                <td width="120">Nama</td>
                <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
                <td><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td><?= !empty($order['customer_address']) ? nl2br(htmlspecialchars($order['customer_address'])) : 'Belum diisi' ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<hr>

<h6>Item Pesanan</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($items_result) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="<?= BASE_URL . UPLOAD_PATH . ($item['image'] ?: 'no-image.jpg') ?>"
                            class="rounded me-3" width="40" height="40" style="object-fit: cover;"
                            alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                        <span><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></span>
                    </div>
                </td>
                <td><?= formatRupiah($item['price']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= formatRupiah($item['price'] * $item['quantity']) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="4" class="text-center text-muted">Tidak ada item dalam pesanan ini</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total</th>
                <th><?= formatRupiah($order['total_amount']) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php 
// Show order actions for customers
if (!$is_admin && $order['status'] == 'pending'): 
?>
<hr>
<div class="text-end">
    <button class="btn btn-outline-danger" onclick="cancelOrderFromDetail(<?= $order['id'] ?>)">
        <i class="fas fa-times"></i> Batalkan Pesanan
    </button>
</div>

<script>
function cancelOrderFromDetail(orderId) {
    if (confirm('Yakin ingin membatalkan pesanan ini?')) {
        fetch('ajax/cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pesanan berhasil dibatalkan');
                    // Close modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('orderDetailModal'));
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('Gagal membatalkan pesanan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat membatalkan pesanan');
            });
    }
}
</script>
<?php endif; ?>

<?php closeConnection($conn); ?>