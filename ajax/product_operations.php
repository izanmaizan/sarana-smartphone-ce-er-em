<?php
// ajax/product_operations.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$conn = getConnection();

switch ($action) {
    case 'bulk_update_status':
        $product_ids = $_POST['product_ids'] ?? [];
        $status = $_POST['status'] ?? '';
        
        if (empty($product_ids) || !in_array($status, ['active', 'inactive'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $ids = implode(',', array_map('intval', $product_ids));
        $update_query = "UPDATE products SET status = '$status' WHERE id IN ($ids)";
        
        if (mysqli_query($conn, $update_query)) {
            $affected = mysqli_affected_rows($conn);
            echo json_encode([
                'success' => true, 
                'message' => "$affected produk berhasil diupdate"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate produk']);
        }
        break;
        
    case 'bulk_delete':
        $product_ids = $_POST['product_ids'] ?? [];
        
        if (empty($product_ids)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada produk yang dipilih']);
            exit;
        }
        
        $ids = implode(',', array_map('intval', $product_ids));
        $delete_query = "UPDATE products SET status = 'inactive' WHERE id IN ($ids)";
        
        if (mysqli_query($conn, $delete_query)) {
            $affected = mysqli_affected_rows($conn);
            echo json_encode([
                'success' => true, 
                'message' => "$affected produk berhasil dihapus"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk']);
        }
        break;
        
    case 'quick_stock_update':
        $product_id = intval($_POST['product_id'] ?? 0);
        $stock_change = intval($_POST['stock_change'] ?? 0);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? 'Quick stock update');
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
            exit;
        }
        
        // Get current stock
        $current_stock_query = "SELECT stock, name FROM products WHERE id = $product_id";
        $current_stock_result = mysqli_query($conn, $current_stock_query);
        
        if (mysqli_num_rows($current_stock_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            exit;
        }
        
        $product = mysqli_fetch_assoc($current_stock_result);
        $new_stock = max(0, $product['stock'] + $stock_change);
        
        // Update stock
        $update_query = "UPDATE products SET stock = $new_stock WHERE id = $product_id";
        
        if (mysqli_query($conn, $update_query)) {
            // Log stock change
            if ($stock_change > 0) {
                $log_query = "INSERT INTO stock_in (product_id, quantity, date, notes) 
                             VALUES ($product_id, $stock_change, CURDATE(), '$notes')";
                mysqli_query($conn, $log_query);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Stok berhasil diupdate',
                'new_stock' => $new_stock
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate stok']);
        }
        break;
        
    case 'get_product_analytics':
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
            exit;
        }
        
        // Get product sales data
        $sales_query = "
            SELECT 
                COUNT(oi.id) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue,
                AVG(r.rating) as avg_rating,
                COUNT(r.id) as review_count
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
            LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
            WHERE p.id = $product_id
        ";
        $sales_result = mysqli_query($conn, $sales_query);
        $analytics = mysqli_fetch_assoc($sales_result);
        
        // Get monthly sales trend (last 6 months)
        $trend_query = "
            SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m') as month,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * oi.price) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = $product_id 
            AND o.status = 'delivered'
            AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
            ORDER BY month ASC
        ";
        $trend_result = mysqli_query($conn, $trend_query);
        $trend_data = [];
        while ($row = mysqli_fetch_assoc($trend_result)) {
            $trend_data[] = $row;
        }
        
        $analytics['trend_data'] = $trend_data;
        $analytics['success'] = true;
        
        echo json_encode($analytics);
        break;
        
    case 'export_products':
        $category_id = intval($_POST['category_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        $export_query = "
            SELECT p.name, p.description, p.price, p.stock, p.status,
                   c.name as category_name, u.name as unit_name,
                   p.created_at
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE 1=1
        ";
        
        if ($category_id > 0) {
            $export_query .= " AND p.category_id = $category_id";
        }
        
        if (!empty($status)) {
            $export_query .= " AND p.status = '$status'";
        }
        
        $export_query .= " ORDER BY p.created_at DESC";
        $export_result = mysqli_query($conn, $export_query);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($export_result)) {
            $data[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'filename' => 'products_export_' . date('Y-m-d') . '.csv'
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

closeConnection($conn);
?>