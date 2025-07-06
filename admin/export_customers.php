<?php
// admin/export_customers.php
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Get all customers with statistics
$customers_query = "
    SELECT u.name, u.email, u.phone, u.address, u.created_at,
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount END), 0) as total_spent,
           COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as completed_orders,
           MAX(o.order_date) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id, u.name, u.email, u.phone, u.address, u.created_at
    ORDER BY u.created_at DESC
";
$customers_result = mysqli_query($conn, $customers_query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="customers_export_' . date('Y-m-d') . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV header
fputcsv($output, [
    'Nama',
    'Email', 
    'Telepon',
    'Alamat',
    'Tanggal Daftar',
    'Total Pesanan',
    'Pesanan Selesai',
    'Total Belanja',
    'Terakhir Order'
]);

// Add data rows
while ($customer = mysqli_fetch_assoc($customers_result)) {
    fputcsv($output, [
        $customer['name'],
        $customer['email'],
        $customer['phone'],
        $customer['address'],
        date('d/m/Y', strtotime($customer['created_at'])),
        $customer['total_orders'],
        $customer['completed_orders'],
        $customer['total_spent'],
        $customer['last_order_date'] ? date('d/m/Y', strtotime($customer['last_order_date'])) : '-'
    ]);
}

fclose($output);
closeConnection($conn);
?>