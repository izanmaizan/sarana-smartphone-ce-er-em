<?php
// ajax/get_cart_count.php - ENHANCED VERSION
require_once '../config.php';

// Set headers untuk JSON dan caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Debug mode
$debug_mode = defined('DEBUG_MODE') && DEBUG_MODE;

if ($debug_mode) {
    error_log("=== GET CART COUNT DEBUG ===");
    error_log("Session ID: " . session_id());
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Cart Data: " . print_r($_SESSION['cart'] ?? [], true));
}

// Enhanced response function
function sendCartResponse($data) {
    global $debug_mode;
    
    $response = [
        'success' => true,
        'timestamp' => time(),
        'data' => $data
    ];
    
    if ($debug_mode) {
        $response['debug_info'] = [
            'session_id' => session_id(),
            'php_session_id' => session_id(),
            'server_time' => date('Y-m-d H:i:s')
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Check authentication status
    $is_logged_in = isLoggedIn();
    $is_admin = $is_logged_in ? isAdmin() : false;
    
    // Base response data
    $response_data = [
        'count' => 0,
        'unique_items' => 0,
        'logged_in' => $is_logged_in,
        'is_admin' => $is_admin,
        'user_id' => $is_logged_in ? $_SESSION['user_id'] : null,
        'user_name' => $is_logged_in ? $_SESSION['name'] : null
    ];
    
    // If not logged in or is admin, return empty cart
    if (!$is_logged_in || $is_admin) {
        sendCartResponse($response_data);
    }
    
    // Calculate cart data for customers
    $cart_data = $_SESSION['cart'] ?? [];
    
    if (!empty($cart_data) && is_array($cart_data)) {
        // Calculate totals
        $total_count = array_sum($cart_data);
        $unique_items = count($cart_data);
        
        // Get cart details with product info (for advanced features)
        $cart_details = [];
        $cart_total = 0;
        
        if (!empty($cart_data)) {
            $conn = getConnection();
            
            if ($conn) {
                $product_ids = array_keys($cart_data);
                $ids_str = implode(',', array_map('intval', $product_ids));
                
                $query = "SELECT id, name, price, stock, image FROM products WHERE id IN ($ids_str) AND status = 'active'";
                $result = mysqli_query($conn, $query);
                
                if ($result) {
                    while ($product = mysqli_fetch_assoc($result)) {
                        $product_id = $product['id'];
                        $quantity = $cart_data[$product_id];
                        $subtotal = $product['price'] * $quantity;
                        
                        $cart_details[] = [
                            'product_id' => $product_id,
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'stock' => $product['stock'],
                            'image' => $product['image']
                        ];
                        
                        $cart_total += $subtotal;
                    }
                }
                
                closeConnection($conn);
            }
        }
        
        // Update response data
        $response_data['count'] = $total_count;
        $response_data['unique_items'] = $unique_items;
        $response_data['cart_total'] = $cart_total;
        $response_data['formatted_total'] = formatRupiah($cart_total);
        
        // Include cart details only if requested
        if (isset($_GET['include_details']) && $_GET['include_details'] === 'true') {
            $response_data['cart_details'] = $cart_details;
        }
        
        // Add cart summary for navbar
        $response_data['cart_summary'] = [
            'items_text' => $unique_items . ' item' . ($unique_items > 1 ? 's' : ''),
            'total_text' => formatRupiah($cart_total),
            'is_empty' => false
        ];
    } else {
        $response_data['cart_summary'] = [
            'items_text' => 'Kosong',
            'total_text' => 'Rp 0',
            'is_empty' => true
        ];
    }
    
    sendCartResponse($response_data);
    
} catch (Exception $e) {
    if ($debug_mode) {
        error_log("Get cart count error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    // Send minimal error response
    echo json_encode([
        'success' => false,
        'error' => 'System error',
        'data' => [
            'count' => 0,
            'logged_in' => false,
            'is_admin' => false
        ],
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
}
?>