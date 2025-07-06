<?php
// utils/sample_data.php - Insert sample data
require_once '../config.php';

$conn = getConnection();

echo "<h2>Sample Data Insertion</h2>";

// Sample categories
$categories = [
    ['Smartphone', 'Handphone pintar terbaru'],
    ['Tablet', 'Tablet untuk kebutuhan multimedia'],
    ['Aksesoris', 'Aksesoris smartphone dan tablet'],
    ['Sparepart', 'Suku cadang dan komponen'],
    ['Audio', 'Headphone dan speaker']
];

echo "<h3>Inserting Categories...</h3>";
foreach ($categories as $cat) {
    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '{$cat[0]}'");
    if (mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO categories (name, description) VALUES ('{$cat[0]}', '{$cat[1]}')";
        if (mysqli_query($conn, $query)) {
            echo "✅ Added category: {$cat[0]}<br>";
        }
    } else {
        echo "⚠️ Category already exists: {$cat[0]}<br>";
    }
}

// Sample units
$units = ['Unit', 'Pcs', 'Set', 'Pair', 'Pack'];

echo "<h3>Inserting Units...</h3>";
foreach ($units as $unit) {
    $check = mysqli_query($conn, "SELECT id FROM units WHERE name = '$unit'");
    if (mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO units (name) VALUES ('$unit')";
        if (mysqli_query($conn, $query)) {
            echo "✅ Added unit: $unit<br>";
        }
    } else {
        echo "⚠️ Unit already exists: $unit<br>";
    }
}

// Sample products
$products = [
    [1, 1, 'iPhone 15 Pro Max', 'iPhone terbaru dengan chip A17 Pro dan kamera 48MP', 18000000, 15],
    [1, 1, 'Samsung Galaxy S24 Ultra', 'Flagship Samsung dengan S Pen dan AI features', 16000000, 20],
    [1, 1, 'Xiaomi 14 Pro', 'Smartphone flagship dengan Leica camera', 12000000, 25],
    [1, 1, 'OPPO Find X7 Pro', 'Premium smartphone dengan fast charging 100W', 14000000, 18],
    [3, 2, 'AirPods Pro 2nd Gen', 'Wireless earphones dengan Active Noise Cancellation', 3500000, 30],
    [3, 2, 'Samsung Galaxy Buds2 Pro', 'Premium earbuds dengan 360 Audio', 2800000, 25],
    [3, 1, 'Case iPhone 15 Pro', 'Premium leather case untuk iPhone 15 Pro', 450000, 50],
    [3, 1, 'Screen Protector Universal', 'Tempered glass premium universal', 150000, 100],
    [4, 2, 'Charger Fast Charging 65W', 'Charger cepat universal dengan kabel USB-C', 350000, 40],
    [4, 1, 'Power Bank 20000mAh', 'Power bank kapasitas besar dengan fast charging', 250000, 35]
];

echo "<h3>Inserting Products...</h3>";
foreach ($products as $prod) {
    $check = mysqli_query($conn, "SELECT id FROM products WHERE name = '{$prod[2]}'");
    if (mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO products (category_id, unit_id, name, description, price, stock) 
                 VALUES ({$prod[0]}, {$prod[1]}, '{$prod[2]}', '{$prod[3]}', {$prod[4]}, {$prod[5]})";
        if (mysqli_query($conn, $query)) {
            echo "✅ Added product: {$prod[2]}<br>";
        }
    } else {
        echo "⚠️ Product already exists: {$prod[2]}<br>";
    }
}

// Sample discounts
echo "<h3>Inserting Sample Discounts...</h3>";
$discount_query = "INSERT INTO discounts (product_id, percentage, start_date, end_date, status) 
                  SELECT id, 15.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active' 
                  FROM products WHERE name LIKE '%iPhone%' OR name LIKE '%Samsung%' 
                  AND id NOT IN (SELECT product_id FROM discounts)";
if (mysqli_query($conn, $discount_query)) {
    echo "✅ Added sample discounts<br>";
}

// Sample customer
echo "<h3>Creating Sample Customer...</h3>";
$customer_check = mysqli_query($conn, "SELECT id FROM users WHERE email = 'john.doe@example.com'");
if (mysqli_num_rows($customer_check) == 0) {
    $customer_query = "INSERT INTO users (name, email, password, phone, address, role) 
                      VALUES ('John Doe', 'john.doe@example.com', MD5('password123'), '081234567890', 
                      'Jl. Contoh No. 123, Jakarta', 'customer')";
    if (mysqli_query($conn, $customer_query)) {
        echo "✅ Added sample customer: john.doe@example.com / password123<br>";
    }
}

echo "<hr>";
echo "<h3>Sample Data Installation Complete!</h3>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@sarana.com / admin123</li>";
echo "<li><strong>Demo Customer:</strong> customer@demo.com / customer123</li>";
echo "<li><strong>Sample Customer:</strong> john.doe@example.com / password123</li>";
echo "</ul>";

echo "<br><a href='../index.php'>← Go to Website</a> | ";
echo "<a href='../admin/dashboard.php'>Go to Admin Panel →</a>";

closeConnection($conn);
?>