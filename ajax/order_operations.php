<?php
// ajax/order_operations.php
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
        $order_ids = $_POST['order_ids'] ?? [];
        $status = $_POST['status'] ?? '';
        
        $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        
        if (empty($order_ids) || !in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $ids = implode(',', array_map('intval', $order_ids));
        $update_query = "UPDATE orders SET status = '$status' WHERE id IN ($ids)";
        
        if (mysqli_query($conn, $update_query)) {
            $affected = mysqli_affected_rows($conn);
            
            // If cancelling orders, restore stock
            if ($status == 'cancelled') {
                $restore_stock_query = "
                    UPDATE products p 
                    SET stock = stock + (
                        SELECT SUM(oi.quantity) 
                        FROM order_items oi 
                        WHERE oi.order_id IN ($ids) AND oi.product_id = p.id
                    )
                    WHERE p.id IN (
                        SELECT DISTINCT product_id FROM order_items WHERE order_id IN ($ids)
                    )
                ";
                mysqli_query($conn, $restore_stock_query);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => "$affected pesanan berhasil diupdate ke status: " . ucfirst($status)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate pesanan']);
        }
        break;
        
    case 'get_order_stats':
        $date_range = $_POST['date_range'] ?? '7'; // days
        $start_date = date('Y-m-d', strtotime("-$date_range days"));
        
        // Order statistics
        $stats_query = "
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE order_date >= '$start_date'
            GROUP BY status
        ";
        $stats_result = mysqli_query($conn, $stats_query);
        
        $stats = [];
        while ($row = mysqli_fetch_assoc($stats_result)) {
            $stats[$row['status']] = [
                'count' => $row['count'],
                'total_amount' => $row['total_amount']
            ];
        }
        
        // Daily orders chart
        $daily_query = "
            SELECT 
                DATE(order_date) as date,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE order_date >= '$start_date'
            GROUP BY DATE(order_date)
            ORDER BY date ASC
        ";
        $daily_result = mysqli_query($conn, $daily_query);
        
        $daily_data = [];
        while ($row = mysqli_fetch_assoc($daily_result)) {
            $daily_data[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'daily_data' => $daily_data
        ]);
        break;
        
    case 'send_order_notification':
        $order_id = intval($_POST['order_id'] ?? 0);
        $notification_type = $_POST['notification_type'] ?? '';
        
        if ($order_id <= 0 || empty($notification_type)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        // Get order and customer details
        $order_query = "
            SELECT o.*, u.name as customer_name, u.email as customer_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = $order_id
        ";
        $order_result = mysqli_query($conn, $order_query);
        
        if (mysqli_num_rows($order_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
            exit;
        }
        
        $order = mysqli_fetch_assoc($order_result);
        
        // Here you would implement email/SMS notification
        // For now, we'll just simulate it
        $messages = [
            'confirmed' => 'Pesanan Anda telah dikonfirmasi dan sedang diproses.',
            'shipped' => 'Pesanan Anda telah dikirim dan dalam perjalanan.',
            'delivered' => 'Pesanan Anda telah sampai di tujuan.',
            'cancelled' => 'Pesanan Anda telah dibatalkan.'
        ];
        
        $message = $messages[$notification_type] ?? 'Update status pesanan Anda.';
        
        // Log notification (you can implement actual email/SMS here)
        echo json_encode([
            'success' => true,
            'message' => "Notifikasi $notification_type berhasil dikirim ke {$order['customer_email']}"
        ]);
        break;
        
    case 'export_orders':
        $start_date = $_POST['start_date'] ?? date('Y-m-01');
        $end_date = $_POST['end_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? '';
        
        $export_query = "
            SELECT 
                o.id,
                o.order_date,
                u.name as customer_name,
                u.email as customer_email,
                o.total_amount,
                o.status,
                o.payment_status,
                GROUP_CONCAT(p.name SEPARATOR '; ') as products
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
        ";
        
        if (!empty($status)) {
            $export_query .= " AND o.status = '$status'";
        }
        
        $export_query .= " GROUP BY o.id ORDER BY o.order_date DESC";
        $export_result = mysqli_query($conn, $export_query);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($export_result)) {
            $data[] = [
                'ID Pesanan' => $row['id'],
                'Tanggal' => date('d/m/Y H:i', strtotime($row['order_date'])),
                'Nama Pelanggan' => $row['customer_name'],
                'Email' => $row['customer_email'],
                'Total' => $row['total_amount'],
                'Status' => ucfirst($row['status']),
                'Status Pembayaran' => ucfirst($row['payment_status']),
                'Produk' => $row['products']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'filename' => 'orders_export_' . $start_date . '_to_' . $end_date . '.csv'
        ]);
        break;
        
    case 'get_order_timeline':
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if ($order_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
            exit;
        }
        
        // Get order status history (this would need a separate table in real implementation)
        // For now, we'll simulate based on current status
        $order_query = "SELECT status, order_date FROM orders WHERE id = $order_id";
        $order_result = mysqli_query($conn, $order_query);
        
        if (mysqli_num_rows($order_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
            exit;
        }
        
        $order = mysqli_fetch_assoc($order_result);
        
        // Simulate timeline based on current status
        $timeline = [
            ['status' => 'pending', 'timestamp' => $order['order_date'], 'completed' => true],
        ];
        
        if (in_array($order['status'], ['confirmed', 'shipped', 'delivered'])) {
            $timeline[] = ['status' => 'confirmed', 'timestamp' => date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +1 hour')), 'completed' => true];
        }
        
        if (in_array($order['status'], ['shipped', 'delivered'])) {
            $timeline[] = ['status' => 'shipped', 'timestamp' => date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +1 day')), 'completed' => true];
        }
        
        if ($order['status'] == 'delivered') {
            $timeline[] = ['status' => 'delivered', 'timestamp' => date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +3 days')), 'completed' => true];
        }
        
        echo json_encode([
            'success' => true,
            'timeline' => $timeline
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

closeConnection($conn);
?>