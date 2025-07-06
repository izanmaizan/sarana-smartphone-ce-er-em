<?php
// ajax/add_to_cart.php - ENHANCED VERSION
require_once '../config.php';

// Set headers untuk JSON dan CORS
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// CSRF Protection (Simple token check)
if (!isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Debug mode (only enable in development)
$debug_mode = defined('DEBUG_MODE') && DEBUG_MODE;

if ($debug_mode) {
    error_log("=== ADD TO CART DEBUG ===");
    error_log("Session Data: " . print_r($_SESSION, true));
    error_log("POST Data: " . print_r($_POST, true));
    error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));
}

// Enhanced error response function
function sendErrorResponse($message, $code = 'VALIDATION_ERROR', $data = []) {
    global $debug_mode;
    
    $response = [
        'success' => false,
        'message' => $message,
        'error_code' => $code,
        'timestamp' => time()
    ];
    
    if ($debug_mode && !empty($data)) {
        $response['debug_data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Enhanced success response function
function sendSuccessResponse($message, $data = []) {
    $response = [
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Metode request tidak valid', 'INVALID_METHOD');
}

// Validate user authentication
if (!isLoggedIn()) {
    sendErrorResponse('Anda harus login terlebih dahulu', 'NOT_AUTHENTICATED');
}

if (isAdmin()) {
    sendErrorResponse('Admin tidak dapat menambahkan produk ke keranjang', 'ADMIN_RESTRICTED');
}

// Validate and sanitize input
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if ($product_id === false || $product_id <= 0) {
    sendErrorResponse('ID produk tidak valid', 'INVALID_PRODUCT_ID');
}

if ($quantity === false || $quantity <= 0) {
    $quantity = 1; // Default quantity
}

if ($quantity > 100) { // Maximum quantity per add
    sendErrorResponse('Maksimal 100 item per sekali tambah', 'QUANTITY_EXCEEDED');
}

$conn = getConnection();

if (!$conn) {
    sendErrorResponse('Koneksi database gagal', 'DATABASE_ERROR');
}

try {
    // Get product details with prepared statement for security
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, stock, status FROM products WHERE id = ? AND status = 'active'");
    
    if (!$stmt) {
        throw new Exception('Gagal menyiapkan query: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($stmt);
        sendErrorResponse('Produk tidak ditemukan atau tidak tersedia', 'PRODUCT_NOT_FOUND');
    }
    
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Validate product status
    if ($product['status'] !== 'active') {
        sendErrorResponse('Produk tidak aktif', 'PRODUCT_INACTIVE');
    }
    
    // Check stock availability
    if ($product['stock'] <= 0) {
        sendErrorResponse('Produk sedang habis', 'OUT_OF_STOCK');
    }
    
    if ($quantity > $product['stock']) {
        sendErrorResponse(
            "Stok tidak mencukupi. Tersedia: {$product['stock']} unit", 
            'INSUFFICIENT_STOCK',
            ['available_stock' => $product['stock']]
        );
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Calculate total quantity including existing cart items
    $current_in_cart = $_SESSION['cart'][$product_id] ?? 0;
    $total_quantity = $current_in_cart + $quantity;
    
    // Check total quantity against stock
    if ($total_quantity > $product['stock']) {
        sendErrorResponse(
            "Total quantity melebihi stok. Di keranjang: {$current_in_cart}, Stok tersedia: {$product['stock']}", 
            'TOTAL_QUANTITY_EXCEEDED',
            [
                'current_in_cart' => $current_in_cart,
                'available_stock' => $product['stock'],
                'requested_quantity' => $quantity
            ]
        );
    }
    
    // Add/update cart
    $_SESSION['cart'][$product_id] = $total_quantity;
    
    // Calculate cart statistics
    $cart_count = array_sum($_SESSION['cart']);
    $unique_items = count($_SESSION['cart']);
    
    // Calculate total cart value (optional)
    $cart_total = 0;
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if ($pid == $product_id) {
            $cart_total += $product['price'] * $qty;
        } else {
            // You might want to cache product prices for efficiency
            $price_query = mysqli_query($conn, "SELECT price FROM products WHERE id = $pid");
            if ($price_query && $price_row = mysqli_fetch_assoc($price_query)) {
                $cart_total += $price_row['price'] * $qty;
            }
        }
    }
    
    // Success response with comprehensive data
    sendSuccessResponse(
        "'{$product['name']}' berhasil ditambahkan ke keranjang",
        [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'quantity_added' => $quantity,
            'total_in_cart' => $total_quantity,
            'cart_count' => $cart_count,
            'unique_items' => $unique_items,
            'cart_total' => $cart_total,
            'product_price' => $product['price'],
            'remaining_stock' => $product['stock'] - $total_quantity
        ]
    );
    
} catch (Exception $e) {
    if ($debug_mode) {
        error_log("Add to cart error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    sendErrorResponse(
        'Terjadi kesalahan sistem', 
        'SYSTEM_ERROR',
        $debug_mode ? ['error_details' => $e->getMessage()] : []
    );
} finally {
    if (isset($conn)) {
        closeConnection($conn);
    }
}
?>