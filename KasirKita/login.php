<?php
session_start();
include "koneksi.php";

// Ambil role dari index.php
$role = isset($_GET['role']) ? $_GET['role'] : '';

if(isset($_POST['login'])){
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']);
    $role     = $_POST['role']; // dari hidden input

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password' AND role='$role'");
    $data  = mysqli_fetch_assoc($query);

    if($data){
        $_SESSION['id']   = $data['id'];
        $_SESSION['nama'] = $data['nama'];
        $_SESSION['role'] = $data['role'];

        // Redirect sesuai role
        if($data['role'] == "kasir"){
            header("Location: kasir/index.php");
        } elseif($data['role'] == "manajer"){
            header("Location: manajer/index.php");
        } elseif($data['role'] == "kitchen"){
            header("Location: kitchen/index.php");
        }
        exit();
    } else {
        $error = "Login gagal! Username/Password salah atau bukan role $role.";
    }
}

// Set role-specific styling
$roleConfig = [
    'kasir' => [
        'color' => '#4CAF50',
        'colorLight' => '#66BB6A',
        'icon' => 'fas fa-cash-register',
        'title' => 'Kasir'
    ],
    'manajer' => [
        'color' => '#2196F3',
        'colorLight' => '#42A5F5',
        'icon' => 'fas fa-chart-line',
        'title' => 'Manajer'
    ],
    'kitchen' => [
        'color' => '#FF9800',
        'colorLight' => '#FFB74D',
        'icon' => 'fas fa-utensils',
        'title' => 'Kitchen'
    ]
];

$currentRole = $roleConfig[$role] ?? $roleConfig['kasir'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login <?php echo $currentRole['title']; ?> - KasirKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .role-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            background: linear-gradient(135deg, <?php echo $currentRole['color']; ?>, <?php echo $currentRole['colorLight']; ?>);
            box-shadow: 0 10px 20px rgba(<?php echo hexdec(substr($currentRole['color'], 1, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 3, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 5, 2)); ?>, 0.3);
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .login-subtitle {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: <?php echo $currentRole['color']; ?>;
            box-shadow: 0 0 0 3px rgba(<?php echo hexdec(substr($currentRole['color'], 1, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 3, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 5, 2)); ?>, 0.1);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            transition: color 0.3s ease;
        }

        .form-control:focus + .input-icon {
            color: <?php echo $currentRole['color']; ?>;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .password-toggle:hover {
            color: <?php echo $currentRole['color']; ?>;
            background: rgba(<?php echo hexdec(substr($currentRole['color'], 1, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 3, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 5, 2)); ?>, 0.1);
        }

        .password-field {
            padding-right: 50px !important;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, <?php echo $currentRole['color']; ?>, <?php echo $currentRole['colorLight']; ?>);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(<?php echo hexdec(substr($currentRole['color'], 1, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 3, 2)); ?>, <?php echo hexdec(substr($currentRole['color'], 5, 2)); ?>, 0.3);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-back:hover {
            color: <?php echo $currentRole['color']; ?>;
            transform: translateX(-5px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 60px;
            height: 60px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 100px;
            height: 100px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 40px;
            height: 40px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-15px) rotate(180deg);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .role-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="role-icon">
                <i class="<?php echo $currentRole['icon']; ?>"></i>
            </div>
            <h1 class="login-title">Login <?php echo $currentRole['title']; ?></h1>
            <p class="login-subtitle">Masuk ke sistem KasirKita</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="role" value="<?php echo $role; ?>">
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" name="password" id="password" class="form-control password-field" placeholder="Masukkan password" required>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="password-icon"></i>
                </button>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login
            </button>
        </form>

        <div class="text-center">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Index
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
