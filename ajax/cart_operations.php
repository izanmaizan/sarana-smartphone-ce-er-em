<?php
// ajax/cart_operations.php - Enhanced version
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);

$conn = getConnection();

switch ($action) {
    case 'add':
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($product_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        // Check product availability
        $check_query = "SELECT stock, name FROM products WHERE id = $product_id AND status = 'active'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            exit;
        }
        
        $product = mysqli_fetch_assoc($check_result);
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Calculate total quantity if product already in cart
        $current_in_cart = $_SESSION['cart'][$product_id] ?? 0;
        $total_quantity = $current_in_cart + $quantity;
        
        if ($total_quantity > $product['stock']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Stok tidak mencukupi. Tersedia: ' . $product['stock'] . ' unit'
            ]);
            exit;
        }
        
        // Add to cart
        $_SESSION['cart'][$product_id] = $total_quantity;
        
        echo json_encode([
            'success' => true, 
            'message' => $product['name'] . ' berhasil ditambahkan ke keranjang',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
        break;
        
    case 'update':
        $quantity = intval($_POST['quantity'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        if ($quantity == 0) {
            // Remove item if quantity is 0
            unset($_SESSION['cart'][$product_id]);
            echo json_encode([
                'success' => true, 
                'message' => 'Item dihapus dari keranjang',
                'cart_count' => array_sum($_SESSION['cart'] ?? [])
            ]);
            exit;
        }
        
        // Check stock
        $check_query = "SELECT stock, name FROM products WHERE id = $product_id AND status = 'active'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            exit;
        }
        
        $product = mysqli_fetch_assoc($check_result);
        
        if ($quantity > $product['stock']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Stok tidak mencukupi. Maksimal: ' . $product['stock'] . ' unit'
            ]);
            exit;
        }
        
        $_SESSION['cart'][$product_id] = $quantity;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Keranjang berhasil diupdate',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
        break;
        
    case 'remove':
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        
        // Get product name for message
        $name_query = "SELECT name FROM products WHERE id = $product_id";
        $name_result = mysqli_query($conn, $name_query);
        $product_name = mysqli_num_rows($name_result) > 0 ? mysqli_fetch_assoc($name_result)['name'] : 'Item';
        
        unset($_SESSION['cart'][$product_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => $product_name . ' berhasil dihapus dari keranjang',
            'cart_count' => array_sum($_SESSION['cart'] ?? [])
        ]);
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Keranjang berhasil dikosongkan',
            'cart_count' => 0
        ]);
        break;
        
    case 'get_count':
        $count = 0;
        if (isset($_SESSION['cart'])) {
            $count = array_sum($_SESSION['cart']);
        }
        
        echo json_encode([
            'success' => true, 
            'count' => $count
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

closeConnection($conn);
?>