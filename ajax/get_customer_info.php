<?php
// ajax/get_customer_info.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$detailed = isset($_GET['detailed']) ? true : false;

if ($customer_id == 0) {
    echo '<div class="alert alert-danger">ID pelanggan tidak valid.</div>';
    exit;
}

// Get customer basic info
$customer_query = "
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount END), 0) as total_spent,
           COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as completed_orders,
           MAX(o.order_date) as last_order_date,
           COUNT(DISTINCT r.id) as total_reviews,
           AVG(CASE WHEN r.status = 'approved' THEN r.rating END) as avg_rating
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN reviews r ON u.id = r.user_id
    WHERE u.id = $customer_id AND u.role = 'customer'
    GROUP BY u.id
";

$customer_result = mysqli_query($conn, $customer_query);

if (mysqli_num_rows($customer_result) == 0) {
    echo '<div class="alert alert-danger">Pelanggan tidak ditemukan.</div>';
    exit;
}

$customer = mysqli_fetch_assoc($customer_result);

// Determine customer type
$customer_type = 'Regular';
$badge_class = 'bg-secondary';

if ($customer['total_spent'] >= 10000000) {
    $customer_type = 'VIP';
    $badge_class = 'bg-warning';
} elseif ($customer['total_orders'] >= 5) {
    $customer_type = 'Loyal';
    $badge_class = 'bg-success';
} elseif (strtotime($customer['created_at']) > strtotime('-7 days')) {
    $customer_type = 'New';
    $badge_class = 'bg-primary';
}
?>

<!-- Customer Info Display -->
<div class="row">
    <div class="col-md-4 text-center">
        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
            style="width: 80px; height: 80px; font-size: 2rem;">
            <i class="fas fa-user"></i>
        </div>
        <h5><?= $customer['name'] ?></h5>
        <span class="badge <?= $badge_class ?> mb-2"><?= $customer_type ?></span>
        <p class="text-muted small">
            Bergabung <?= date('d M Y', strtotime($customer['created_at'])) ?>
        </p>
    </div>

    <div class="col-md-8">
        <div class="row">
            <div class="col-6">
                <h6><i class="fas fa-envelope text-primary"></i> Email</h6>
                <p><?= $customer['email'] ?></p>

                <h6><i class="fas fa-phone text-success"></i> Telepon</h6>
                <p><?= $customer['phone'] ?: '-' ?></p>
            </div>
            <div class="col-6">
                <h6><i class="fas fa-map-marker-alt text-danger"></i> Alamat</h6>
                <p><?= $customer['address'] ?: 'Belum diisi' ?></p>

                <h6><i class="fas fa-birthday-cake text-warning"></i> Tanggal Lahir</h6>
                <p><?= $customer['birth_date'] ? date('d M Y', strtotime($customer['birth_date'])) : '-' ?></p>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- Statistics -->
<div class="row text-center">
    <div class="col-3">
        <div class="border rounded p-3">
            <h4 class="text-primary"><?= $customer['total_orders'] ?></h4>
            <small class="text-muted">Total Pesanan</small>
        </div>
    </div>
    <div class="col-3">
        <div class="border rounded p-3">
            <h4 class="text-success"><?= formatRupiah($customer['total_spent']) ?></h4>
            <small class="text-muted">Total Belanja</small>
        </div>
    </div>
    <div class="col-3">
        <div class="border rounded p-3">
            <h4 class="text-info"><?= $customer['completed_orders'] ?></h4>
            <small class="text-muted">Selesai</small>
        </div>
    </div>
    <div class="col-3">
        <div class="border rounded p-3">
            <h4 class="text-warning"><?= $customer['total_reviews'] ?></h4>
            <small class="text-muted">Review</small>
        </div>
    </div>
</div>

<?php if ($detailed): ?>
<hr>

<!-- Recent Orders -->
<h6><i class="fas fa-shopping-cart text-primary"></i> Pesanan Terbaru</h6>
<?php
$recent_orders_query = "
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $customer_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5
";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);

if (mysqli_num_rows($recent_orders_result) > 0):
?>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Tanggal</th>
                <th>Item</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>
            <tr>
                <td>#<?= $order['id'] ?></td>
                <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                <td><?= $order['item_count'] ?> item</td>
                <td><?= formatRupiah($order['total_amount']) ?></td>
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
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<p class="text-muted">Belum ada pesanan.</p>
<?php endif; ?>

<hr>

<!-- Recent Reviews -->
<h6><i class="fas fa-star text-warning"></i> Review Terbaru</h6>
<?php
$recent_reviews_query = "
    SELECT r.*, p.name as product_name
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    WHERE r.user_id = $customer_id
    ORDER BY r.created_at DESC
    LIMIT 3
";
$recent_reviews_result = mysqli_query($conn, $recent_reviews_query);

if (mysqli_num_rows($recent_reviews_result) > 0):
?>
<?php while ($review = mysqli_fetch_assoc($recent_reviews_result)): ?>
<div class="border rounded p-3 mb-2">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="mb-1"><?= $review['product_name'] ?></h6>
            <div class="text-warning mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                <?php endfor; ?>
                <span class="text-muted ms-1">(<?= $review['rating'] ?>/5)</span>
            </div>
            <p class="mb-0 small"><?= substr($review['comment'], 0, 100) ?>...</p>
        </div>
        <small class="text-muted"><?= date('d/m/Y', strtotime($review['created_at'])) ?></small>
    </div>
</div>
<?php endwhile; ?>
<?php else: ?>
<p class="text-muted">Belum ada review.</p>
<?php endif; ?>

<?php endif; ?>

<!-- Action Buttons -->
<div class="text-end mt-3">
    <a href="orders.php?search=<?= urlencode($customer['email']) ?>" class="btn btn-outline-primary btn-sm"
        target="_blank">
        <i class="fas fa-shopping-cart"></i> Lihat Pesanan
    </a>
    <a href="chats.php?user_id=<?= $customer['id'] ?>" class="btn btn-outline-success btn-sm" target="_blank">
        <i class="fas fa-comments"></i> Chat
    </a>
    <a href="mailto:<?= $customer['email'] ?>" class="btn btn-outline-info btn-sm">
        <i class="fas fa-envelope"></i> Email
    </a>
    <?php if ($customer['phone']): ?>
    <a href="tel:<?= $customer['phone'] ?>" class="btn btn-outline-warning btn-sm">
        <i class="fas fa-phone"></i> Telepon
    </a>
    <?php endif; ?>
</div>

<?php closeConnection($conn); ?>