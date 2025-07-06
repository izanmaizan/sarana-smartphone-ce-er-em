<?php
// 404.php - Page Not Found
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
        margin-bottom: 1rem;
    }

    .floating-icons {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }

    .floating-icon {
        position: absolute;
        color: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }
    </style>
</head>

<body>
    <div class="floating-icons">
        <i class="fas fa-mobile-alt floating-icon"
            style="top: 10%; left: 10%; font-size: 3rem; animation-delay: 0s;"></i>
        <i class="fas fa-shopping-cart floating-icon"
            style="top: 20%; right: 20%; font-size: 2rem; animation-delay: 1s;"></i>
        <i class="fas fa-search floating-icon"
            style="bottom: 30%; left: 20%; font-size: 2.5rem; animation-delay: 2s;"></i>
        <i class="fas fa-heart floating-icon"
            style="bottom: 20%; right: 10%; font-size: 2rem; animation-delay: 3s;"></i>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="error-container">
                    <div class="error-code">404</div>
                    <h2 class="mb-4">Oops! Halaman Tidak Ditemukan</h2>
                    <p class="text-muted mb-4">
                        Maaf, halaman yang Anda cari tidak dapat ditemukan.
                        Mungkin halaman telah dipindahkan atau URL salah.
                    </p>

                    <div class="d-grid gap-2 d-md-block">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Kembali ke Beranda
                        </a>
                        <button class="btn btn-outline-secondary btn-lg" onclick="history.back()">
                            <i class="fas fa-arrow-left"></i> Halaman Sebelumnya
                        </button>
                    </div>

                    <hr class="my-4">

                    <div class="row text-center">
                        <div class="col-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-store fa-2x text-primary mb-2"></i>
                                <div class="small">Toko</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-user fa-2x text-success mb-2"></i>
                                <div class="small">Login</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="mailto:support@sarana.com" class="text-decoration-none">
                                <i class="fas fa-headset fa-2x text-info mb-2"></i>
                                <div class="small">Bantuan</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>