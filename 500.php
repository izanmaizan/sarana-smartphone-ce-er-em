<?php
// 500.php - Internal Server Error
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #ffc107, #ff8f00);
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
        color: #ff8f00;
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
                    <i class="fas fa-tools fa-5x text-warning mb-4"></i>
                    <div class="error-code">500</div>
                    <h2 class="mb-4">Server Bermasalah</h2>
                    <p class="text-muted mb-4">
                        Maaf, terjadi kesalahan pada server kami.
                        Tim teknis sedang bekerja untuk memperbaiki masalah ini.
                    </p>

                    <div class="d-grid gap-2 d-md-block">
                        <button class="btn btn-warning btn-lg" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Coba Lagi
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </div>

                    <hr class="my-4">

                    <p class="small text-muted">
                        Jika masalah berlanjut, hubungi:
                        <a href="mailto:support@sarana.com">support@sarana.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>