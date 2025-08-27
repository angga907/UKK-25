<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $user_id = (int)$_SESSION['id'];
        mysqli_query($koneksi, "UPDATE users SET nama='$nama', username='$username', email='$email' WHERE id=$user_id");
        $_SESSION['nama'] = $nama; $_SESSION['username'] = $username;
        header('Location: pengaturan.php?success=profile');
        exit;
    }
    if ($action === 'change_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if ($new_password === $confirm_password && $new_password !== '') {
            $user_id = (int)$_SESSION['id'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hashed' WHERE id=$user_id");
            header('Location: pengaturan.php?success=password');
            exit;
        } else {
            header('Location: pengaturan.php?error=password');
            exit;
        }
    }
}

$user_id = (int)$_SESSION['id'];
$user_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id=$user_id"));
function is_active($page){ return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pengaturan - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body{font-family:'Poppins',sans-serif;background:#f8f9fa}
		.dashboard-container{display:flex;min-height:100vh}
		.sidebar{width:280px;background:linear-gradient(135deg,#2196F3 0%,#1976D2 100%);color:#fff;position:fixed;height:100vh;overflow-y:auto;z-index:1000}
		.sidebar-header{padding:30px 20px;border-bottom:1px solid rgba(255,255,255,.1);text-align:center}
		.sidebar-header h3{font-weight:600;margin-bottom:5px}
		.sidebar-header p{opacity:.85;font-size:.9rem}
		.nav-menu{padding:20px 0}
		.nav-link{display:flex;align-items:center;padding:14px 24px;color:rgba(255,255,255,.85);text-decoration:none;border-left:3px solid transparent;transition:all .25s ease}
		.nav-link i{width:22px;margin-right:12px;font-size:1.05rem}
		.nav-link:hover{background:rgba(255,255,255,.12);color:#fff;border-left-color:#fff}
		.nav-link.active{background:rgba(255,255,255,.18);color:#fff;border-left-color:#fff}
		.main-content{flex:1;margin-left:280px;padding:30px}
		.card{border:none;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.08);margin-bottom:24px}
		.card-header{background:linear-gradient(135deg,#2196F3,#1976D2);color:#fff;border:none;border-radius:16px 16px 0 0}
		.btn-primary{background:linear-gradient(135deg,#2196F3,#1976D2);border:none;border-radius:10px}
		.btn-success{background:linear-gradient(135deg,#4CAF50,#66BB6A);border:none;border-radius:10px}
	</style>
</head>
<body>
	<div class="dashboard-container">
		<div class="sidebar">
			<div class="sidebar-header">
				<h3><i class="fas fa-chart-line me-2"></i>KasirKita</h3>
				<p>Manager Dashboard</p>
			</div>
			<div class="nav-menu">
				<a class="nav-link <?php echo is_active('index.php'); ?>" href="index.php"><i class="fas fa-home"></i>Dashboard</a>
				<a class="nav-link <?php echo is_active('laporan.php'); ?>" href="laporan.php"><i class="fas fa-chart-bar"></i>Laporan Penjualan</a>
				<a class="nav-link <?php echo is_active('pegawai.php'); ?>" href="pegawai.php"><i class="fas fa-users"></i>Data Pegawai</a>
				<a class="nav-link <?php echo is_active('menu.php'); ?>" href="menu.php"><i class="fas fa-utensils"></i>Data Menu</a>
				<a class="nav-link <?php echo is_active('pengaturan.php'); ?>" href="pengaturan.php"><i class="fas fa-cog"></i>Pengaturan</a>
				<a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</div>

		<div class="main-content">
			<div class="page-header mb-3">
				<h1 class="h4 mb-1"><i class="fas fa-cog me-2"></i>Pengaturan</h1>
				<p class="text-muted small mb-0">Kelola pengaturan akun dan sistem</p>
			</div>

			<?php if(isset($_GET['success']) && $_GET['success']==='profile'): ?>
			<div class="alert alert-success border-0 rounded-3">Profil berhasil disimpan.</div>
			<?php endif; ?>
			<?php if(isset($_GET['success']) && $_GET['success']==='password'): ?>
			<div class="alert alert-success border-0 rounded-3">Password berhasil diubah.</div>
			<?php endif; ?>
			<?php if(isset($_GET['error']) && $_GET['error']==='password'): ?>
			<div class="alert alert-danger border-0 rounded-3">Password baru tidak cocok.</div>
			<?php endif; ?>

			<div class="card">
				<div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i>Profil Pengguna</h5></div>
				<div class="card-body">
					<form method="POST" class="row g-3">
						<input type="hidden" name="action" value="update_profile">
						<div class="col-md-6">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Username</label>
							<input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Email</label>
							<input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
						</div>
						<div class="col-12">
							<button class="btn btn-primary" type="submit"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><h5 class="mb-0"><i class="fas fa-lock me-2"></i>Ubah Password</h5></div>
				<div class="card-body">
					<form method="POST" class="row g-3">
						<input type="hidden" name="action" value="change_password">
						<div class="col-md-6">
							<label class="form-label">Password Baru</label>
							<input type="password" name="new_password" class="form-control" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Konfirmasi Password Baru</label>
							<input type="password" name="confirm_password" class="form-control" required>
						</div>
						<div class="col-12">
							<button class="btn btn-success" type="submit"><i class="fas fa-key me-2"></i>Ubah Password</button>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5></div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<p class="mb-1"><strong>Nama Sistem</strong></p>
							<p class="text-muted">KasirKita Restaurant Management System</p>
							<p class="mb-1"><strong>Versi</strong></p>
							<p class="text-muted">v1.0.0</p>
						</div>
						<div class="col-md-6">
							<p class="mb-1"><strong>Server</strong></p>
							<p class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
							<p class="mb-1"><strong>PHP Version</strong></p>
							<p class="text-muted"><?php echo PHP_VERSION; ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
