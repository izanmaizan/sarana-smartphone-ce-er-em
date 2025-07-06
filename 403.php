<?php
// 403.php - Access Forbidden
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        min-height: 100vh;
        display: flex;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .error-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        padding: 3rem;
        text-align: center;
    }

    .error-code {
        font-size: 8rem;
        font-weight: bold;
        color: #dc3545;
        line-height: 1;
        margin-bottom: 1rem;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="error-container">
                    <i class="fas fa-shield-alt fa-5x text-danger mb-4"></i>
                    <div class="error-code">403</div>
                    <h2 class="mb-4">Akses Ditolak</h2>
                    <p class="text-muted mb-4">
                        Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                        Silakan login dengan akun yang sesuai.
                    </p>

                    <div class="d-grid gap-2 d-md-block">
                        <a href="login.php" class="btn btn-danger btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>