<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string(getConnection(), $_POST['name']);
    $email = mysqli_real_escape_string(getConnection(), $_POST['email']);
    $phone = mysqli_real_escape_string(getConnection(), $_POST['phone']);
    $address = mysqli_real_escape_string(getConnection(), $_POST['address']);
    $password = MD5($_POST['password']);
    $confirm_password = MD5($_POST['confirm_password']);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($_POST['password'])) {
        $error = 'Semua field harus diisi!';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($_POST['password']) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        $conn = getConnection();
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Insert new user
            $insert_query = "INSERT INTO users (name, email, phone, address, password, role) 
                           VALUES ('$name', '$email', '$phone', '$address', '$password', 'customer')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
            }
        }
        
        closeConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Toko Sarana Smartphone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('login.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 20px 0;
    }

    .register-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .btn-register {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px;
        font-weight: 600;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .password-strength {
        height: 5px;
        border-radius: 3px;
        transition: all 0.3s;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="logo.png" alt="Sarana Smartphone" height="80" class="mb-3">
                            <h3 class="mb-0">Daftar Akun Baru</h3>
                            <p class="text-muted">Bergabunglah dengan Sarana Smartphone</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?= $success ?>
                            <br><a href="login.php" class="btn btn-success btn-sm mt-2">Login Sekarang</a>
                        </div>
                        <?php endif; ?>

                        <form method="POST" id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nama Lengkap</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Nama lengkap"
                                                value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Email aktif"
                                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="Nomor telepon/WhatsApp"
                                        value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <textarea class="form-control" id="address" name="address" rows="2"
                                        placeholder="Alamat lengkap"
                                        required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Minimal 6 karakter" required>
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePassword('password', 'toggleIcon1')">
                                                <i class="fas fa-eye" id="toggleIcon1"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength bg-light mt-1" id="passwordStrength"></div>
                                        <small class="text-muted">Kekuatan password: <span
                                                id="strengthText">Lemah</span></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password"
                                                name="confirm_password" placeholder="Ulangi password" required>
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                                <i class="fas fa-eye" id="toggleIcon2"></i>
                                            </button>
                                        </div>
                                        <small id="passwordMatch" class="text-muted"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Saya setuju dengan <a href="#" class="text-decoration-none">syarat dan ketentuan</a>
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-register">
                                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <p class="text-muted">
                                Sudah punya akun?
                                <a href="login.php" class="text-decoration-none">Login di sini</a>
                            </p>
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(fieldId, iconId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleIcon = document.getElementById(iconId);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');

        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthBar.className = 'password-strength bg-danger';
                strengthBar.style.width = '20%';
                strengthText.textContent = 'Lemah';
                strengthText.className = 'text-danger';
                break;
            case 2:
                strengthBar.className = 'password-strength bg-warning';
                strengthBar.style.width = '40%';
                strengthText.textContent = 'Sedang';
                strengthText.className = 'text-warning';
                break;
            case 3:
                strengthBar.className = 'password-strength bg-info';
                strengthBar.style.width = '60%';
                strengthText.textContent = 'Baik';
                strengthText.className = 'text-info';
                break;
            case 4:
                strengthBar.className = 'password-strength bg-success';
                strengthBar.style.width = '80%';
                strengthText.textContent = 'Kuat';
                strengthText.className = 'text-success';
                break;
            case 5:
                strengthBar.className = 'password-strength bg-success';
                strengthBar.style.width = '100%';
                strengthText.textContent = 'Sangat Kuat';
                strengthText.className = 'text-success';
                break;
        }
    });

    // Password match checker
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const matchText = document.getElementById('passwordMatch');

        if (confirmPassword) {
            if (password === confirmPassword) {
                matchText.textContent = 'Password cocok ✓';
                matchText.className = 'text-success';
            } else {
                matchText.textContent = 'Password tidak cocok ✗';
                matchText.className = 'text-danger';
            }
        } else {
            matchText.textContent = '';
        }
    }

    document.getElementById('password').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
    </script>
</body>

</html>