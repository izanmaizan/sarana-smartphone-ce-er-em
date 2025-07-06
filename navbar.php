<?php
// navbar.php - Enhanced Navbar Component with Global JavaScript
// Usage: include 'navbar.php'; or include 'components/navbar.php';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Fixed Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand fw-bold text-primary" href="<?= BASE_URL ?>index.php">
            <img src="logo.png" alt="Sarana Smartphone" height="40" class="me-2">
            Sarana Smartphone
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Empty space for left side (remove main navigation) -->
            <ul class="navbar-nav me-auto">
                <!-- Keep empty or add other general links if needed -->
            </ul>

            <!-- Right Side Navigation -->
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>

                <?php if (isAdmin()): ?>
                <!-- Admin Navigation -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>admin/products.php">
                        <i class="fas fa-box"></i> Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>admin/orders.php">
                        <i class="fas fa-shopping-cart"></i> Pesanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>admin/customers.php">
                        <i class="fas fa-users"></i> Pelanggan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>admin/chats.php">
                        <i class="fas fa-comments"></i> Chat
                    </a>
                </li>

                <?php else: ?>
                <!-- Customer Navigation -->

                <!-- Beranda -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                </li>

                <!-- Pesanan -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'orders' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>orders.php">
                        <i class="fas fa-shopping-bag"></i> Pesanan
                    </a>
                </li>

                <!-- Cart with Badge -->
                <li class="nav-item">
                    <a class="nav-link position-relative <?= $current_page == 'cart' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>cart.php">
                        <i class="fas fa-shopping-cart"></i> Keranjang
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill"
                            id="cart-count" style="font-size: 0.7rem; display: none;">0</span>
                    </a>
                </li>

                <!-- Chat Button -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>chat.php" title="Chat Customer Service">
                        <i class="fas fa-comments"></i> Chat
                    </a>
                </li>

                <?php endif; ?>

                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li class="dropdown-header">
                            <div class="text-center">
                                <i class="fas fa-user-circle fa-2x text-primary mb-1"></i>
                                <div class="fw-bold"><?= htmlspecialchars($_SESSION['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($_SESSION['email']) ?></small>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <?php if (isAdmin()): ?>
                        <!-- Admin Menu Items -->
                        <li>
                            <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], 'admin/dashboard') !== false ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/products.php">
                                <i class="fas fa-box me-2"></i> Kelola Produk
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/orders.php">
                                <i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/customers.php">
                                <i class="fas fa-users me-2"></i> Data Pelanggan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/chats.php">
                                <i class="fas fa-comments me-2"></i> Chat Support
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/reports.php">
                                <i class="fas fa-chart-bar me-2"></i> Laporan
                            </a>
                        </li>

                        <?php else: ?>
                        <!-- Customer Menu Items -->
                        <li>
                            <a class="dropdown-item <?= $current_page == 'profile' ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>profile.php">
                                <i class="fas fa-user me-2"></i> Profil Saya
                            </a>
                        </li>
                        <?php endif; ?>

                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"
                                onclick="return confirm('Yakin ingin logout?')">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>

                <?php else: ?>
                <!-- Guest Navigation -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'login' ? 'active' : '' ?>" href="<?= BASE_URL ?>login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'register' ? 'active' : '' ?>"
                        href="<?= BASE_URL ?>register.php">
                        <i class="fas fa-user-plus"></i> Daftar
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Global JavaScript Variables and Enhanced Functions -->
<script>
// Set global variables available to all pages
window.IS_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
window.IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
window.BASE_URL = '<?= BASE_URL ?>';
window.CURRENT_PAGE = '<?= $current_page ?>';
<?php if (isLoggedIn()): ?>
window.USER_ID = <?= $_SESSION['user_id'] ?>;
window.USER_NAME = '<?= addslashes($_SESSION['name']) ?>';
window.USER_EMAIL = '<?= addslashes($_SESSION['email']) ?>';
window.USER_ROLE = '<?= $_SESSION['role'] ?>';
<?php else: ?>
window.USER_ID = null;
window.USER_NAME = null;
window.USER_EMAIL = null;
window.USER_ROLE = null;
<?php endif; ?>

// Enhanced navbar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load cart count for customers
    <?php if (isLoggedIn() && !isAdmin()): ?>
    loadCartCount();
    // Refresh cart count every 30 seconds
    setInterval(loadCartCount, 30000);
    <?php endif; ?>

    // Auto-collapse navbar on mobile when clicking a link
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            });
        });
    }

    // Enhanced scroll effect for navbar
    const navbar = document.querySelector('.navbar');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        lastScrollTop = scrollTop;
    }, {
        passive: true
    });

    // Initialize navbar state
    if (window.pageYOffset > 50) {
        navbar.classList.add('scrolled');
    }

    console.log('🚀 Sarana Smartphone CRM System - Navbar initialized');
    console.log('📊 System Status:', {
        isLoggedIn: IS_LOGGED_IN,
        isAdmin: IS_ADMIN,
        currentPage: CURRENT_PAGE,
        userId: USER_ID,
        userRole: USER_ROLE
    });
});

// Enhanced function to load cart count
function loadCartCount() {
    if (!IS_LOGGED_IN || IS_ADMIN) {
        return;
    }

    fetch(`${BASE_URL}ajax/get_cart_count.php`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                updateCartBadges(data.data.count || 0);

                // Store cart data globally for other scripts to use
                window.cartData = data.data;

                // Dispatch custom event for other components
                const event = new CustomEvent('cartUpdated', {
                    detail: data.data
                });
                document.dispatchEvent(event);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
}

// Enhanced function to update cart badges
function updateCartBadges(count) {
    const badges = ['cart-count', 'cart-count-dropdown'];

    badges.forEach(badgeId => {
        const badge = document.getElementById(badgeId);
        if (badge) {
            badge.textContent = count;

            // Show/hide badge based on count
            if (count === 0) {
                badge.style.display = 'none';
            } else {
                badge.style.display = 'flex';
            }
        }
    });

    // Update global cart count
    window.cartCount = count;
}

// Enhanced function to open chat (for floating button in index.php)
function openChatModal() {
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

    // Try to open popup for customers, fallback to new window if blocked
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

// Global function to update cart count from other pages
window.updateCartCount = function(newCount) {
    updateCartBadges(newCount);
};

// Global function to show notification
window.showNavbarNotification = function(message, type = 'success') {
    // Integration with toast system if available
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
    }
};

// Global function to refresh user session data
window.refreshUserSession = function() {
    if (!IS_LOGGED_IN) return;

    fetch(`${BASE_URL}ajax/get_user_session.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update global variables if needed
                if (data.data.name !== USER_NAME) {
                    USER_NAME = data.data.name;
                    // Update name in navbar
                    const nameElements = document.querySelectorAll('.navbar .fw-bold');
                    nameElements.forEach(el => {
                        if (el.textContent === USER_NAME) {
                            el.textContent = data.data.name;
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing user session:', error);
        });
};

// Enhanced error handling for AJAX requests
window.handleAjaxError = function(error, context = 'Request') {
    console.error(`${context} failed:`, error);

    if (error.message && error.message.includes('NetworkError')) {
        showNavbarNotification('Koneksi internet bermasalah. Silakan coba lagi.', 'error');
    } else if (error.status === 401) {
        showNavbarNotification('Sesi Anda telah berakhir. Silakan login kembali.', 'error');
        setTimeout(() => {
            window.location.href = `${BASE_URL}login.php`;
        }, 2000);
    } else {
        showNavbarNotification('Terjadi kesalahan sistem. Silakan coba lagi.', 'error');
    }
};
</script>

<!-- Enhanced CSS for Fixed Navbar -->
<style>
/* Fixed Navbar Styling */
.navbar.fixed-top {
    position: fixed !important;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    background-color: white !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

/* Navbar scroll effect */
.navbar.scrolled {
    background-color: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
}

/* Brand styling */
.navbar-brand {
    font-weight: 700;
    font-size: 1.4rem;
    color: #667eea !important;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.navbar-brand img {
    height: 40px;
    width: auto;
    object-fit: contain;
}

.navbar-brand:hover {
    color: #5a6fd8 !important;
}

/* Navigation links */
.navbar-nav .nav-link {
    font-weight: 500;
    border-radius: 8px;
    margin: 0 2px;
    padding: 8px 12px;
    color: #495057;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(102, 126, 234, 0.1);
    color: #667eea !important;
}

.navbar-nav .nav-link.active {
    background-color: rgba(102, 126, 234, 0.15);
    color: #667eea !important;
    font-weight: 600;
}

/* User avatar */
.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Dropdown menu - Simple version */
.dropdown-menu {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.5rem 0;
    min-width: 280px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dropdown-header {
    padding: 1rem;
    background: #f8f9fa;
    color: #495057;
    border-radius: 8px 8px 0 0;
    margin: -0.5rem 0 0 0;
    border-bottom: 1px solid #dee2e6;
}

.dropdown-item {
    padding: 0.7rem 1.5rem;
    color: #495057;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #667eea;
}

.dropdown-item.active {
    background-color: rgba(102, 126, 234, 0.1);
    color: #667eea;
    font-weight: 600;
}

/* Cart badge */
#cart-count,
#cart-count-dropdown {
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

/* Mobile responsive */
@media (max-width: 991.98px) {
    .navbar-nav {
        text-align: center;
        padding: 1rem 0;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0 0 15px 15px;
        margin-top: 10px;
    }

    .navbar-nav .nav-link {
        margin: 5px 0;
        border-radius: 8px;
    }

    .dropdown-menu {
        position: static !important;
        transform: none !important;
        width: 100%;
        border: 1px solid #dee2e6;
        box-shadow: none;
        background-color: #f8f9fa;
        margin-top: 10px;
    }

    .dropdown-header {
        border-radius: 8px;
    }

    .navbar-toggler {
        border: none;
        padding: 4px 8px;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }
}

/* Navbar toggler icon custom */
.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}
</style>