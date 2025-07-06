<?php
// utils/system_info.php - System information
require_once '../config.php';

// Only allow admin access
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Access denied. Admin only.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Information - Sarana Smartphone CRM</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h2><i class="fas fa-info-circle text-primary"></i> System Information</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-server"></i> Server Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Server Software</td>
                                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td>PHP Version</td>
                                <td><?= PHP_VERSION ?></td>
                            </tr>
                            <tr>
                                <td>Server Name</td>
                                <td><?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td>Document Root</td>
                                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td>Server Time</td>
                                <td><?= date('Y-m-d H:i:s') ?></td>
                            </tr>
                            <tr>
                                <td>Timezone</td>
                                <td><?= date_default_timezone_get() ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-database"></i> Database Information</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $conn = getConnection();
                            $version_query = mysqli_query($conn, "SELECT VERSION() as version");
                            $version = mysqli_fetch_assoc($version_query)['version'];
                            
                            // Get database size
                            $size_query = mysqli_query($conn, "
                                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' 
                                FROM information_schema.tables 
                                WHERE table_schema = '" . DB_NAME . "'
                            ");
                            $db_size = mysqli_fetch_assoc($size_query)['db_size'];
                            
                            echo "<table class='table table-sm'>";
                            echo "<tr><td>MySQL Version</td><td>$version</td></tr>";
                            echo "<tr><td>Database Name</td><td>" . DB_NAME . "</td></tr>";
                            echo "<tr><td>Database Size</td><td>{$db_size} MB</td></tr>";
                            echo "<tr><td>Connection Status</td><td><span class='text-success'>✅ Connected</span></td></tr>";
                            echo "</table>";
                            
                            closeConnection($conn);
                        } catch (Exception $e) {
                            echo "<p class='text-danger'>❌ Database connection failed</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> PHP Configuration</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Memory Limit</td>
                                <td><?= ini_get('memory_limit') ?></td>
                            </tr>
                            <tr>
                                <td>Max Execution Time</td>
                                <td><?= ini_get('max_execution_time') ?>s</td>
                            </tr>
                            <tr>
                                <td>Upload Max Filesize</td>
                                <td><?= ini_get('upload_max_filesize') ?></td>
                            </tr>
                            <tr>
                                <td>Post Max Size</td>
                                <td><?= ini_get('post_max_size') ?></td>
                            </tr>
                            <tr>
                                <td>Session Save Path</td>
                                <td><?= ini_get('session.save_path') ?: 'Default' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-puzzle-piece"></i> PHP Extensions</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $required_extensions = ['mysqli', 'gd', 'curl', 'json', 'mbstring', 'openssl'];
                        echo "<table class='table table-sm'>";
                        foreach ($required_extensions as $ext) {
                            $status = extension_loaded($ext) ? 
                                "<span class='text-success'>✅ Loaded</span>" : 
                                "<span class='text-danger'>❌ Missing</span>";
                            echo "<tr><td>$ext</td><td>$status</td></tr>";
                        }
                        echo "</table>";
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-folder"></i> Directory Permissions</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $directories = [
                            'uploads/' => 'Upload directory',
                            'uploads/products/' => 'Product images',
                            'config.php' => 'Configuration file',
                            '.htaccess' => 'Apache configuration'
                        ];
                        
                        echo "<table class='table table-sm'>";
                        echo "<tr><th>Path</th><th>Description</th><th>Writable</th><th>Permissions</th></tr>";
                        foreach ($directories as $path => $desc) {
                            if (file_exists($path)) {
                                $writable = is_writable($path) ? 
                                    "<span class='text-success'>✅ Yes</span>" : 
                                    "<span class='text-danger'>❌ No</span>";
                                $perms = substr(sprintf('%o', fileperms($path)), -4);
                                echo "<tr><td>$path</td><td>$desc</td><td>$writable</td><td>$perms</td></tr>";
                            } else {
                                echo "<tr><td>$path</td><td>$desc</td><td><span class='text-warning'>⚠️ Not Found</span></td><td>-</td></tr>";
                            }
                        }
                        echo "</table>";
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="../admin/dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
</body>

</html>