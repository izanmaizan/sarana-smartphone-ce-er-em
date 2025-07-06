<?php
// ajax/product_search.php
require_once '../config.php';

header('Content-Type: application/json');

$conn = getConnection();

// Get search parameters
$search = $_GET['search'] ?? '';
$category_id = intval($_GET['category'] ?? 0);
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';
$limit = intval($_GET['limit'] ?? 12);
$page = intval($_GET['page'] ?? 1);

$offset = ($page - 1) * $limit;

// Build query
$products_query = "
    SELECT p.*, c.name as category_name, u.name as unit_name,
           d.percentage as discount_percentage,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN p.price - (p.price * d.percentage / 100)
               ELSE p.price 
           END as final_price,
           CASE 
               WHEN d.percentage IS NOT NULL AND d.status = 'active' 
               AND CURDATE() BETWEEN d.start_date AND d.end_date 
               THEN 1 ELSE 0 
           END as has_discount,
           (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN units u ON p.unit_id = u.id
    LEFT JOIN discounts d ON p.id = d.product_id 
    WHERE p.status = 'active' AND p.stock > 0
";

$count_query = "
    SELECT COUNT(*) as total
    FROM products p 
    WHERE p.status = 'active' AND p.stock > 0
";

$where_conditions = [];

// Search filter
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $search_condition = "(p.name LIKE '%$search_escaped%' OR p.description LIKE '%$search_escaped%')";
    $where_conditions[] = $search_condition;
}

// Category filter
if ($category_id > 0) {
    $where_conditions[] = "p.category_id = $category_id";
}

// Price filter
if ($min_price > 0) {
    $where_conditions[] = "p.price >= $min_price";
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= $max_price";
}

// Add WHERE conditions to queries
if (!empty($where_conditions)) {
    $where_clause = " AND " . implode(" AND ", $where_conditions);
    $products_query .= $where_clause;
    $count_query .= $where_clause;
}

// Sorting
switch ($sort) {
    case 'name':
        $products_query .= " ORDER BY p.name ASC";
        break;
    case 'price_low':
        $products_query .= " ORDER BY final_price ASC";
        break;
    case 'price_high':
        $products_query .= " ORDER BY final_price DESC";
        break;
    case 'rating':
        $products_query .= " ORDER BY avg_rating DESC";
        break;
    case 'popular':
        $products_query .= " ORDER BY review_count DESC";
        break;
    default: // newest
        $products_query .= " ORDER BY p.created_at DESC";
        break;
}

// Add limit and offset
$products_query .= " LIMIT $limit OFFSET $offset";

// Execute queries
$products_result = mysqli_query($conn, $products_query);
$count_result = mysqli_query($conn, $count_query);

$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $limit);

$products = [];
while ($product = mysqli_fetch_assoc($products_result)) {
    $products[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'description' => substr($product['description'], 0, 100) . '...',
        'price' => $product['price'],
        'final_price' => $product['final_price'],
        'has_discount' => $product['has_discount'],
        'discount_percentage' => $product['discount_percentage'],
        'category_name' => $product['category_name'],
        'unit_name' => $product['unit_name'],
        'stock' => $product['stock'],
        'image' => $product['image'],
        'avg_rating' => round($product['avg_rating'] ?? 0, 1),
        'review_count' => $product['review_count'] ?? 0,
        'formatted_price' => formatRupiah($product['price']),
        'formatted_final_price' => formatRupiah($product['final_price'])
    ];
}

// Get price range for filter
$price_range_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'";
$price_range_result = mysqli_query($conn, $price_range_query);
$price_range = mysqli_fetch_assoc($price_range_result);

echo json_encode([
    'success' => true,
    'products' => $products,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_products' => $total_products,
        'per_page' => $limit
    ],
    'filters' => [
        'price_range' => [
            'min' => floatval($price_range['min_price']),
            'max' => floatval($price_range['max_price'])
        ]
    ]
]);

closeConnection($conn);
?>