<?php
require_once 'config.php';

$conn = getConnection();

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build products query with filters and discounts
$products_query = "
    SELECT p.*, c.name as category_name, u.name as unit_name,
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
           END as has_discount,
           (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN discounts d ON p.id = d.product_id 
    WHERE p.status = 'active' AND p.stock > 0
";

if (!empty($category_filter)) {
    $products_query .= " AND p.category_id = " . intval($category_filter);
}

if (!empty($search)) {
    $products_query .= " AND (p.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                        OR p.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

$products_query .= " ORDER BY p.created_at DESC";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Sarana Smartphone - CRM System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    /* Body dengan padding untuk fixed navbar */
    body {
        padding-top: 76px;
        /* Sesuai tinggi navbar fixed */
        background-color: #f8f9fa;
    }

    /* Hero section dengan background image */
    .hero-section {
        background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('hero.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: white;
        padding: 80px 0 60px 0;
        margin-top: 0;
        min-height: 500px;
        display: flex;
        align-items: center;
    }

    /* Product cards */
    .product-card {
        transition: all 0.3s ease;
        height: 100%;
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .product-card .card-img-top {
        transition: transform 0.3s ease;
    }

    .product-card:hover .card-img-top {
        transform: scale(1.05);
    }

    /* Discount badge */
    .discount-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 6px 10px;
    }

    /* Price styling */
    .original-price {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9em;
    }

    /* Star ratings */
    .star-rating {
        color: #ffc107;
    }

    /* Floating action button untuk chat */
    .floating-action-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        z-index: 1000;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .floating-action-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.6);
        color: white;
    }

    .floating-action-btn:focus {
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
        color: white;
    }

    /* Hide floating button on mobile */
    @media (max-width: 991.98px) {
        .floating-action-btn {
            display: none !important;
        }
    }

    /* Filter section */
    .filter-section {
        background: white;
        padding: 2rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    /* Product section */
    .products-section {
        padding: 3rem 0;
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
        border-radius: 8px;
        font-weight: 500;
    }

    .btn-outline-primary:hover {
        background-color: #667eea;
        border-color: #667eea;
        transform: translateY(-1px);
    }

    /* Footer */
    footer {
        background: #2d3748 !important;
        margin-top: 4rem;
    }

    /* Loading states */
    .btn-loading {
        position: relative;
        color: transparent;
    }

    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        body {
            padding-top: 70px;
        }

        .hero-section {
            padding: 60px 0 40px 0;
            min-height: 400px;
        }

        .hero-section h1 {
            font-size: 2rem;
        }
    }

    /* Toast container positioning */
    .toast-container {
        top: 90px !important;
        /* Di bawah navbar fixed */
    }
    </style>
</head>

<body>
    <!-- Include Fixed Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4 fw-bold">Selamat Datang di Sarana Smartphone</h1>
            <p class="lead mb-4">Toko smartphone terpercaya dengan pelayanan terbaik dan sistem CRM yang canggih</p>
            <a href="#products" class="btn btn-light btn-lg px-4 py-2">
                <i class="fas fa-mobile-alt me-2"></i>Lihat Produk
            </a>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php 
                        mysqli_data_seek($categories_result, 0);
                        while ($category = mysqli_fetch_assoc($categories_result)): 
                        ?>
                        <option value="<?= $category['id'] ?>"
                            <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari produk smartphone..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Produk Terbaru</h2>
                <p class="text-muted">Temukan smartphone terbaik dengan harga terjangkau</p>
            </div>

            <div class="row">
                <?php if (mysqli_num_rows($products_result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100 shadow-sm position-relative">
                        <?php if ($product['has_discount']): ?>
                        <span class="badge bg-danger discount-badge">
                            -<?= $product['discount_percentage'] ?>%
                        </span>
                        <?php endif; ?>

                        <img src="<?= BASE_URL . UPLOAD_PATH . ($product['image'] ?: 'no-image.jpg') ?>"
                            class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>"
                            style="height: 200px; object-fit: cover;">

                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="card-text text-muted small mb-3">
                                <?= htmlspecialchars(substr($product['description'], 0, 60)) ?>...
                            </p>

                            <!-- Rating -->
                            <div class="mb-2">
                                <?php if ($product['avg_rating']): ?>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= round($product['avg_rating']) ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                    <small class="text-muted ms-1">(<?= $product['review_count'] ?>)</small>
                                </div>
                                <?php else: ?>
                                <small class="text-muted">Belum ada ulasan</small>
                                <?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="price-section mb-3">
                                <?php if ($product['has_discount']): ?>
                                <div class="original-price"><?= formatRupiah($product['price']) ?></div>
                                <strong class="text-danger fs-6"><?= formatRupiah($product['final_price']) ?></strong>
                                <?php else: ?>
                                <strong class="fs-6"><?= formatRupiah($product['final_price']) ?></strong>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="mt-auto">
                                <div class="d-grid gap-2">
                                    <a href="product_detail.php?id=<?= $product['id'] ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </a>
                                    <?php if (isLoggedIn() && !isAdmin()): ?>
                                    <button onclick="addToCart(<?= $product['id'] ?>)"
                                        class="btn btn-primary btn-sm add-to-cart-btn"
                                        data-product-id="<?= $product['id'] ?>">
                                        <i class="fas fa-cart-plus me-1"></i>Keranjang
                                    </button>
                                    <?php elseif (!isLoggedIn()): ?>
                                    <a href="login.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login untuk Beli
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-search fa-4x text-muted"></i>
                        </div>
                        <h4 class="text-muted">Tidak ada produk yang ditemukan</h4>
                        <p class="text-muted">Coba gunakan kata kunci pencarian yang berbeda</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-refresh me-1"></i>Reset Pencarian
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Floating Chat Button (Only for customers on desktop) -->
    <?php if (isLoggedIn() && !isAdmin()): ?>
    <button class="floating-action-btn d-none d-lg-flex" onclick="openChatModal()" title="Chat Customer Service">
        <i class="fas fa-comments"></i>
    </button>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Toko Sarana Smartphone</h5>
                    <p class="mb-3">Toko smartphone terpercaya dengan sistem CRM untuk pelayanan terbaik dan pengalaman
                        berbelanja yang memuaskan.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-whatsapp fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Kontak Kami</h5>
                    <div class="contact-info">
                        <p class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:+6212345678901" class="text-light text-decoration-none">+62 123 456 7890</a>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:info@sarana-smartphone.com"
                                class="text-light text-decoration-none">info@sarana-smartphone.com</a>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Jl. Teknologi No. 123, Jakarta Selatan
                        </p>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 Toko Sarana Smartphone CRM System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
    class SaranaCart {
        constructor() {
            this.isLoggedIn = IS_LOGGED_IN;
            this.isAdmin = IS_ADMIN;
            this.baseUrl = BASE_URL || '';
            this.loadingButtons = new Set();
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.loadCartCount();
            this.startPeriodicUpdates();
            this.setupToastContainer();
        }

        setupEventListeners() {
            // Handle all add to cart buttons
            document.addEventListener('click', (e) => {
                if (e.target.closest('.add-to-cart-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.add-to-cart-btn');
                    const productId = button.dataset.productId;
                    if (productId) {
                        this.addToCart(parseInt(productId));
                    }
                }
            });

            // Handle quantity change buttons (for cart page)
            document.addEventListener('click', (e) => {
                if (e.target.closest('.quantity-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.quantity-btn');
                    const productId = parseInt(button.dataset.productId);
                    const change = parseInt(button.dataset.change);
                    this.changeQuantity(productId, change);
                }
            });

            // Handle remove from cart buttons
            document.addEventListener('click', (e) => {
                if (e.target.closest('.remove-from-cart-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.remove-from-cart-btn');
                    const productId = parseInt(button.dataset.productId);
                    this.removeFromCart(productId);
                }
            });

            // Handle clear cart button
            document.addEventListener('click', (e) => {
                if (e.target.closest('.clear-cart-btn')) {
                    e.preventDefault();
                    this.clearCart();
                }
            });
        }

        setupToastContainer() {
            if (!document.getElementById('toast-container')) {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                container.style.top = '90px'; // Below fixed navbar
                document.body.appendChild(container);
            }
        }

        showToast(message, type = 'success', duration = 4000) {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;

            const toast = document.createElement('div');
            toast.className =
                `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            const toastId = 'toast-' + Date.now();
            toast.id = toastId;

            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

            toastContainer.appendChild(toast);

            // Initialize Bootstrap toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: duration
            });

            bsToast.show();

            // Remove element after hide
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });

            return bsToast;
        }

        async makeRequest(url, data = {}, method = 'POST') {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                };

                if (method === 'POST') {
                    const formData = new URLSearchParams();
                    Object.keys(data).forEach(key => {
                        formData.append(key, data[key]);
                    });
                    options.body = formData;
                }

                const response = await fetch(url, options);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                return result;

            } catch (error) {
                console.error('Request failed:', error);
                throw error;
            }
        }

        setButtonLoading(button, loading = true) {
            if (!button) return;

            const productId = button.dataset.productId;

            if (loading) {
                if (this.loadingButtons.has(productId)) return; // Already loading

                this.loadingButtons.add(productId);
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
                button.disabled = true;
                button.classList.add('btn-loading');
            } else {
                this.loadingButtons.delete(productId);
                button.innerHTML = button.dataset.originalText || button.innerHTML;
                button.disabled = false;
                button.classList.remove('btn-loading');
            }
        }

        async addToCart(productId, quantity = 1) {
            if (!this.validateUser()) return;

            const button = document.querySelector(`[data-product-id="${productId}"]`);
            this.setButtonLoading(button, true);

            try {
                const result = await this.makeRequest(`${this.baseUrl}ajax/add_to_cart.php`, {
                    product_id: productId,
                    quantity: quantity
                });

                if (result.success) {
                    this.showToast(result.message, 'success');

                    // Update cart count
                    if (result.data && typeof result.data.cart_count !== 'undefined') {
                        this.updateCartBadges(result.data.cart_count);
                    }

                    // Show success state temporarily
                    if (button) {
                        button.innerHTML = '<i class="fas fa-check me-1"></i>Berhasil!';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');

                        setTimeout(() => {
                            button.innerHTML = button.dataset.originalText;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                            this.setButtonLoading(button, false);
                        }, 2000);
                    }

                    // Trigger custom event
                    this.dispatchCartEvent('itemAdded', {
                        productId: productId,
                        quantity: quantity,
                        cartData: result.data
                    });

                } else {
                    this.showToast(result.message || 'Gagal menambahkan ke keranjang', 'error');
                    this.setButtonLoading(button, false);
                }

            } catch (error) {
                console.error('Add to cart error:', error);
                this.showToast('Terjadi kesalahan jaringan. Silakan coba lagi.', 'error');
                this.setButtonLoading(button, false);
            }
        }

        async updateCartQuantity(productId, newQuantity) {
            if (!this.validateUser()) return;

            try {
                const result = await this.makeRequest(`${this.baseUrl}ajax/update_cart_quantity.php`, {
                    product_id: productId,
                    quantity: newQuantity
                });

                if (result.success) {
                    this.showToast(result.message, 'success');

                    if (result.data) {
                        this.updateCartBadges(result.data.cart_count);
                    }

                    this.dispatchCartEvent('quantityUpdated', {
                        productId: productId,
                        newQuantity: newQuantity,
                        cartData: result.data
                    });

                    return result;
                } else {
                    this.showToast(result.message || 'Gagal mengupdate quantity', 'error');
                    return false;
                }

            } catch (error) {
                console.error('Update quantity error:', error);
                this.showToast('Terjadi kesalahan jaringan', 'error');
                return false;
            }
        }

        async changeQuantity(productId, change) {
            const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
            if (!quantityInput) return;

            const currentQuantity = parseInt(quantityInput.value) || 0;
            const newQuantity = Math.max(0, currentQuantity + change);

            quantityInput.value = newQuantity;

            return this.updateCartQuantity(productId, newQuantity);
        }

        async removeFromCart(productId) {
            if (!this.validateUser()) return;

            if (!confirm('Yakin ingin menghapus item ini dari keranjang?')) {
                return;
            }

            try {
                const result = await this.makeRequest(`${this.baseUrl}ajax/remove_from_cart.php`, {
                    product_id: productId
                });

                if (result.success) {
                    this.showToast(result.message, 'success');

                    if (result.data) {
                        this.updateCartBadges(result.data.cart_count);
                    }

                    // Remove item from DOM if on cart page
                    const cartItem = document.querySelector(`[data-cart-item="${productId}"]`);
                    if (cartItem) {
                        cartItem.style.transition = 'all 0.3s ease';
                        cartItem.style.opacity = '0';
                        cartItem.style.transform = 'translateX(-100%)';

                        setTimeout(() => {
                            cartItem.remove();
                            this.checkEmptyCart();
                        }, 300);
                    }

                    this.dispatchCartEvent('itemRemoved', {
                        productId: productId,
                        cartData: result.data
                    });

                } else {
                    this.showToast(result.message || 'Gagal menghapus item', 'error');
                }

            } catch (error) {
                console.error('Remove from cart error:', error);
                this.showToast('Terjadi kesalahan jaringan', 'error');
            }
        }

        async clearCart() {
            if (!this.validateUser()) return;

            if (!confirm('Yakin ingin mengosongkan seluruh keranjang?')) {
                return;
            }

            try {
                const result = await this.makeRequest(`${this.baseUrl}ajax/clear_cart.php`);

                if (result.success) {
                    this.showToast(result.message, 'success');
                    this.updateCartBadges(0);

                    // Reload cart page if on cart page
                    if (window.location.pathname.includes('cart.php')) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }

                    this.dispatchCartEvent('cartCleared', {
                        itemsRemoved: result.data?.items_removed || 0
                    });

                } else {
                    this.showToast(result.message || 'Gagal mengosongkan keranjang', 'error');
                }

            } catch (error) {
                console.error('Clear cart error:', error);
                this.showToast('Terjadi kesalahan jaringan', 'error');
            }
        }

        async loadCartCount() {
            if (!this.isLoggedIn || this.isAdmin) {
                this.updateCartBadges(0);
                return;
            }

            try {
                const result = await this.makeRequest(`${this.baseUrl}ajax/get_cart_count.php`, {}, 'GET');

                if (result.success && result.data) {
                    this.updateCartBadges(result.data.count || 0);

                    // Store additional cart data
                    if (result.data.cart_summary) {
                        this.updateCartSummary(result.data.cart_summary);
                    }
                }

            } catch (error) {
                console.error('Load cart count error:', error);
                // Silently fail for cart count
            }
        }

        updateCartBadges(count) {
            const badges = ['cart-count', 'cart-count-dropdown'];

            badges.forEach(badgeId => {
                const badge = document.getElementById(badgeId);
                if (badge) {
                    badge.textContent = count;

                    if (count === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'flex';

                        // Add bounce animation
                        badge.classList.add('cart-updated');
                        setTimeout(() => {
                            badge.classList.remove('cart-updated');
                        }, 600);

                        // Add pulse for high count
                        if (count > 5) {
                            badge.classList.add('badge-pulse');
                        } else {
                            badge.classList.remove('badge-pulse');
                        }
                    }
                }
            });

            // Update global cart count
            window.cartCount = count;
        }

        updateCartSummary(summary) {
            // Update cart summary in navbar dropdown or cart page
            const summaryElements = document.querySelectorAll('.cart-summary');
            summaryElements.forEach(element => {
                const itemsText = element.querySelector('.cart-items-text');
                const totalText = element.querySelector('.cart-total-text');

                if (itemsText) itemsText.textContent = summary.items_text;
                if (totalText) totalText.textContent = summary.total_text;
            });
        }

        checkEmptyCart() {
            // Check if cart is empty on cart page
            const cartItems = document.querySelectorAll('[data-cart-item]');
            if (cartItems.length === 0) {
                // Show empty cart message
                const cartContainer = document.querySelector('.cart-items-container');
                if (cartContainer) {
                    cartContainer.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                        <h4>Keranjang Kosong</h4>
                        <p class="text-muted">Belum ada produk di keranjang Anda</p>
                        <a href="${this.baseUrl}index.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-1"></i>Mulai Belanja
                        </a>
                    </div>
                `;
                }
            }
        }

        validateUser() {
            if (!this.isLoggedIn) {
                this.showToast('Silakan login terlebih dahulu', 'error');
                setTimeout(() => {
                    window.location.href = `${this.baseUrl}login.php`;
                }, 1500);
                return false;
            }

            if (this.isAdmin) {
                this.showToast('Admin tidak dapat menggunakan keranjang', 'error');
                return false;
            }

            return true;
        }

        dispatchCartEvent(eventName, detail) {
            const event = new CustomEvent(`cart:${eventName}`, {
                detail: detail,
                bubbles: true
            });
            document.dispatchEvent(event);
        }

        startPeriodicUpdates() {
            // Update cart count every 30 seconds
            setInterval(() => {
                this.loadCartCount();
            }, 30000);
        }

        // Public methods for external use
        getCartCount() {
            return window.cartCount || 0;
        }

        refreshCart() {
            return this.loadCartCount();
        }
    }

    // Chat functionality
    class SaranaChat {
        constructor() {
            this.init();
        }

        init() {
            // Setup chat button listeners
            document.addEventListener('click', (e) => {
                if (e.target.closest('[onclick*="openChatModal"]')) {
                    e.preventDefault();
                    this.openChatModal();
                }
            });
        }

        openChatModal() {
            if (!IS_LOGGED_IN) {
                if (confirm('Anda perlu login untuk menggunakan fitur chat. Login sekarang?')) {
                    window.location.href = `${BASE_URL}login.php`;
                }
                return;
            }

            if (IS_ADMIN) {
                window.location.href = `${BASE_URL}admin/chats.php`;
                return;
            }

            // Try to open popup for customers
            try {
                const chatWindow = window.open(
                    `${BASE_URL}chat.php?mode=modal`,
                    'chat',
                    'width=400,height=600,scrollbars=yes,resizable=yes,location=no,menubar=no,toolbar=no'
                );

                if (!chatWindow || chatWindow.closed || typeof chatWindow.closed === 'undefined') {
                    // Popup blocked, redirect to chat page
                    window.location.href = `${BASE_URL}chat.php`;
                }
            } catch (e) {
                window.location.href = `${BASE_URL}chat.php`;
            }
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize cart system
        window.saranaCart = new SaranaCart();
        window.saranaChat = new SaranaChat();

        // Global functions for backward compatibility
        window.addToCart = (productId, quantity) => window.saranaCart.addToCart(productId, quantity);
        window.updateCartCount = (count) => window.saranaCart.updateCartBadges(count);
        window.openChatModal = () => window.saranaChat.openChatModal();

        // Setup smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Performance monitoring
        if (window.performance) {
            window.addEventListener('load', function() {
                const loadTime = performance.now();
                console.log(`⚡ Page loaded in ${Math.round(loadTime)}ms`);
            });
        }

        console.log('🚀 Sarana Smartphone CRM System initialized');
        console.log('Login Status:', IS_LOGGED_IN);
        console.log('Admin Status:', IS_ADMIN);
    });
    </script>
</body>

</html>

<?php closeConnection($conn); ?>