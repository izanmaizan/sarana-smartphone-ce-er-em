<?php
// utils/database_test.php - Test database connection
require_once '../config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $conn = getConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test basic queries
    $tables = [
        'users' => 'SELECT COUNT(*) as count FROM users',
        'products' => 'SELECT COUNT(*) as count FROM products',
        'orders' => 'SELECT COUNT(*) as count FROM orders',
        'categories' => 'SELECT COUNT(*) as count FROM categories'
    ];
    
    echo "<h3>Table Status:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Records</th><th>Status</th></tr>";
    
    foreach ($tables as $table => $query) {
        try {
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $count = $row['count'];
            echo "<tr><td>$table</td><td>$count</td><td style='color: green;'>✅ OK</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td>-</td><td style='color: red;'>❌ Error: " . $e->getMessage() . "</td></tr>";
        }
    }
    echo "</table>";
    
    closeConnection($conn);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<br><a href='../index.php'>← Back to Website</a>";
?>