<?php
// utils/backup_database.php - Simple database backup
require_once '../config.php';

// Only allow admin access
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Admin only.');
}

$filename = 'backup_sarana_crm_' . date('Y-m-d_H-i-s') . '.sql';
$backup_path = '../backups/';

// Create backup directory if not exists
if (!file_exists($backup_path)) {
    mkdir($backup_path, 0755, true);
}

// Database credentials
$host = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Command to create backup
$command = "mysqldump --host=$host --user=$username --password=$password $database > {$backup_path}{$filename}";

// Execute backup
$output = null;
$return_var = null;
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "<h2>✅ Database Backup Successful</h2>";
    echo "<p>Backup file: <strong>{$filename}</strong></p>";
    echo "<p>Size: " . formatBytes(filesize($backup_path . $filename)) . "</p>";
    echo "<p>Location: {$backup_path}</p>";
    
    echo "<br><a href='download_backup.php?file={$filename}' class='btn btn-primary'>Download Backup</a>";
} else {
    echo "<h2>❌ Backup Failed</h2>";
    echo "<p>Please check your MySQL configuration and permissions.</p>";
    echo "<p>Alternative: Use phpMyAdmin to export database manually.</p>";
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "<br><br><a href='../admin/dashboard.php'>← Back to Admin Panel</a>";
?>