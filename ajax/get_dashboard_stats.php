<?php
// ajax/get_dashboard_stats.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = getConnection();

// Get real-time statistics
$stats = [];

// Today's statistics
$today = date('Y-m-d');

// Orders today
$orders_today_query = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue 
                      FROM orders WHERE DATE(order_date) = '$today' AND status != 'cancelled'";
$orders_today_result = mysqli_query($conn, $orders_today_query);
$orders_today = mysqli_fetch_assoc($orders_today_result);

$stats['orders_today'] = $orders_today['count'];
$stats['revenue_today'] = $orders_today['revenue'];

// New customers today
$customers_today_query = "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND DATE(created_at) = '$today'";
$customers_today_result = mysqli_query($conn, $customers_today_query);
$stats['customers_today'] = mysqli_fetch_assoc($customers_today_result)['count'];

// Pending orders
$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$pending_orders_result = mysqli_query($conn, $pending_orders_query);
$stats['pending_orders'] = mysqli_fetch_assoc($pending_orders_result)['count'];

// Low stock products
$low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock <= 5 AND status = 'active'";
$low_stock_result = mysqli_query($conn, $low_stock_query);
$stats['low_stock'] = mysqli_fetch_assoc($low_stock_result)['count'];

// Unread chats
$unread_chats_query = "SELECT COUNT(DISTINCT user_id) as count FROM chats WHERE status = 'unread' AND sender_type = 'customer'";
$unread_chats_result = mysqli_query($conn, $unread_chats_query);
$stats['unread_chats'] = mysqli_fetch_assoc($unread_chats_result)['count'];

// Pending reviews
$pending_reviews_query = "SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'";
$pending_reviews_result = mysqli_query($conn, $pending_reviews_query);
$stats['pending_reviews'] = mysqli_fetch_assoc($pending_reviews_result)['count'];

// Recent sales data (last 7 days)
$sales_chart_query = "
    SELECT DATE(order_date) as date, 
           COUNT(*) as orders_count,
           COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    AND status != 'cancelled'
    GROUP BY DATE(order_date)
    ORDER BY date ASC
";
$sales_chart_result = mysqli_query($conn, $sales_chart_query);
$sales_data = [];
while ($row = mysqli_fetch_assoc($sales_chart_result)) {
    $sales_data[] = $row;
}
$stats['sales_chart'] = $sales_data;

// Monthly comparison
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

$monthly_query = "
    SELECT 
        SUM(CASE WHEN DATE_FORMAT(order_date, '%Y-%m') = '$this_month' THEN total_amount ELSE 0 END) as this_month,
        SUM(CASE WHEN DATE_FORMAT(order_date, '%Y-%m') = '$last_month' THEN total_amount ELSE 0 END) as last_month
    FROM orders WHERE status != 'cancelled'
";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = mysqli_fetch_assoc($monthly_result);

$stats['monthly_growth'] = $monthly_data['last_month'] > 0 ? 
    (($monthly_data['this_month'] - $monthly_data['last_month']) / $monthly_data['last_month']) * 100 : 0;

echo json_encode($stats);

closeConnection($conn);
?>