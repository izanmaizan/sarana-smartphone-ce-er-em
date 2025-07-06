<?php
// admin/print_order.php - Enhanced version dengan logo
require_once '../config.php';
requireLogin();
requireAdmin();

$conn = getConnection();

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id == 0) {
    header("Location: orders.php");
    exit;
}

// Get order details
$order_query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email, 
           u.phone as customer_phone, u.address as customer_address
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = $order_id
";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "
    SELECT oi.*, p.name as product_name, p.image as product_image,
           c.name as category_name, u.name as unit_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    WHERE oi.order_id = $order_id
    ORDER BY oi.id
";
$items_result = mysqli_query($conn, $items_query);

// Site settings with actual company info
$site_settings = [
    'site_name' => 'SARANA SMARTPHONE',
    'site_location' => 'PADANG',
    'address' => 'Jl. Dr. Sutomo No. 78, Kubu Marapalam, Padang Timur, Kota Padang, Provinsi Sumatera Barat',
    'contact_phone' => '0751-1234567',
    'contact_email' => 'info@saranasmart.com'
];

// Try to get settings from database if table exists
$settings_query = "SHOW TABLES LIKE 'settings'";
$table_check = mysqli_query($conn, $settings_query);

if (mysqli_num_rows($table_check) > 0) {
    $settings_query = "SELECT setting_key, setting_value FROM settings";
    $settings_result = mysqli_query($conn, $settings_query);
    
    if ($settings_result) {
        while ($setting = mysqli_fetch_assoc($settings_result)) {
            $site_settings[$setting['setting_key']] = $setting['setting_value'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur #<?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?> - <?= $site_settings['site_name'] ?></title>
    <style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
            margin: 0;
            padding: 15px;
        }

        .invoice {
            box-shadow: none !important;
            border: 2px solid #dc2626 !important;
        }

        @page {
            margin: 1cm;
            size: A4;
        }
    }

    @media screen {
        body {
            background: #f3f4f6;
            font-family: Arial, sans-serif;
        }

        .invoice {
            max-width: 800px;
            margin: 20px auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
    }

    .invoice {
        background: white;
        border: 2px solid #dc2626;
    }

    .header {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        padding: 15px;
        text-align: center;
        position: relative;
    }

    .logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 10px;
    }

    .logo {
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        position: relative;
    }

    .logo-s {
        font-size: 32px;
        font-weight: bold;
        color: #dc2626;
        font-family: Arial, sans-serif;
    }

    .company-name {
        font-size: 24px;
        font-weight: bold;
        margin: 0;
        letter-spacing: 2px;
    }

    .company-location {
        font-size: 16px;
        margin: 5px 0;
        letter-spacing: 1px;
    }

    .company-address {
        font-size: 11px;
        margin: 10px 0;
        opacity: 0.9;
    }

    .divider {
        height: 3px;
        background: #dc2626;
        margin: 0;
    }

    .invoice-details {
        padding: 20px;
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 15px;
    }

    .invoice-number {
        font-size: 14px;
        font-weight: bold;
    }

    .customer-info {
        background: #f9fafb;
        padding: 15px;
        border-left: 4px solid #dc2626;
        margin-bottom: 20px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .items-table th {
        background: #f3f4f6;
        padding: 10px 8px;
        text-align: left;
        border: 1px solid #d1d5db;
        font-weight: bold;
        font-size: 11px;
    }

    .items-table td {
        padding: 8px;
        border: 1px solid #d1d5db;
        font-size: 11px;
    }

    .items-table tr:nth-child(even) {
        background: #f9fafb;
    }

    .total-section {
        float: right;
        width: 300px;
        margin-top: 20px;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .total-final {
        font-weight: bold;
        font-size: 14px;
        background: #fef2f2;
        padding: 10px;
        border: 1px solid #dc2626;
        color: #dc2626;
    }

    .signature-section {
        clear: both;
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }

    .signature-box {
        text-align: center;
        width: 200px;
    }

    .signature-line {
        border-bottom: 1px solid #333;
        margin: 50px 0 10px 0;
    }

    .footer-note {
        background: #f3f4f6;
        padding: 15px;
        margin-top: 30px;
        text-align: center;
        font-size: 10px;
        color: #6b7280;
        border-top: 2px solid #dc2626;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-confirmed {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-shipped {
        background: #e0e7ff;
        color: #3730a3;
    }

    .status-delivered {
        background: #dcfce7;
        color: #166534;
    }

    .status-cancelled {
        background: #fecaca;
        color: #991b1b;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .font-bold {
        font-weight: bold;
    }
    </style>
</head>

<body>
    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()"
            style="background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            🖨️ Cetak Faktur
        </button>
        <a href="orders.php"
            style="background: #6b7280; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            ← Kembali
        </a>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <div class="logo-s">S</div>
                </div>
                <div>
                    <h1 class="company-name"><?= $site_settings['site_name'] ?></h1>
                    <div class="company-location"><?= $site_settings['site_location'] ?? 'PADANG' ?></div>
                </div>
            </div>
            <div class="company-address"><?= $site_settings['address'] ?></div>
        </div>

        <div class="divider"></div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <!-- Invoice Header -->
            <div class="invoice-header">
                <div>
                    <div class="invoice-number">No Faktur: <?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?></div>
                    <div>Nama: <strong><?= $order['customer_name'] ?></strong></div>
                </div>
                <div class="text-right">
                    <div>Tanggal: <?= date('j F Y', strtotime($order['order_date'])) ?></div>
                    <div>Nomor HP: <?= $order['customer_phone'] ?: '-' ?></div>
                    <div style="margin-top: 10px;">
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="customer-info">
                <strong>Informasi Pelanggan:</strong><br>
                Email: <?= $order['customer_email'] ?: '-' ?><br>
                Alamat: <?= $order['customer_address'] ?: 'Sesuai profil pelanggan' ?>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%">No</th>
                        <th style="width: 40%">Uraian Pesanan</th>
                        <th style="width: 10%" class="text-center">Jumlah</th>
                        <th style="width: 20%" class="text-right">Harga (Rp)</th>
                        <th style="width: 25%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $total = 0;
                    $total_qty = 0;
                    while ($item = mysqli_fetch_assoc($items_result)): 
                        $subtotal = $item['quantity'] * $item['price'];
                        $total += $subtotal;
                        $total_qty += $item['quantity'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td><?= $item['product_name'] ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-right"><?= number_format($item['price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($subtotal, 0, ',', '.') ?></td>
                    </tr>
                    <?php 
                    $no++;
                    endwhile; 
                    ?>
                </tbody>
            </table>

            <!-- Total Section -->
            <div class="total-section">
                <div class="total-row">
                    <span>Diskon:</span>
                    <span style="color: #dc2626;">-</span>
                </div>
                <div class="total-row">
                    <span>Uang:</span>
                    <span style="color: #dc2626;">-</span>
                </div>
                <div class="total-row">
                    <span>Kembalian:</span>
                    <span style="color: #dc2626;">-</span>
                </div>
                <div class="total-final">
                    <div style="display: flex; justify-content: space-between;">
                        <span>TOTAL:</span>
                        <span><?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <div style="border: 1px solid #333; padding: 5px; margin-bottom: 10px;">
                        NB : Barang Yang Sudah Dibeli<br>
                        Tidak Dapat Dikembalikan Lagi
                    </div>
                </div>
                <div class="signature-box">
                    <div>Terima kasih,</div>
                    <div>Semoga Tetap Jadi Langganan</div>
                    <div class="signature-line"></div>
                    <div>( <?= $order['customer_name'] ?> )</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-note">
                <strong>Terima kasih atas kepercayaan Anda berbelanja di
                    <?= $site_settings['site_name'] ?>!</strong><br>
                Faktur ini dicetak secara otomatis pada <?= date('d F Y H:i:s') ?> WIB.<br>
                Untuk pertanyaan, hubungi: <?= $site_settings['contact_phone'] ?>
            </div>
        </div>
    </div>

    <script>
    // Auto print on load if requested
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === 'true') {
        window.print();
    }
    </script>
</body>

</html>

<?php closeConnection($conn); ?>