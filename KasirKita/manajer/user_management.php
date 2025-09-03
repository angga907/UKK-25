<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

// Handle POST with redirect-after-POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        
        if ($username && $nama && $role) {
            mysqli_query($koneksi, "INSERT INTO users (username, password, nama, role, email, telepon, alamat) VALUES ('$username', '$password', '$nama', '$role', '$email', '$telepon', '$alamat')");
        }
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        
        if ($id > 0) {
            $updateQuery = "UPDATE users SET username='$username', nama='$nama', role='$role', email='$email', telepon='$telepon', alamat='$alamat'";
            
            // Update password only if provided
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $updateQuery .= ", password='$password'";
            }
            
            $updateQuery .= " WHERE id=$id";
            mysqli_query($koneksi, $updateQuery);
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id != $_SESSION['user_id']) { // Prevent self-deletion
            mysqli_query($koneksi, "DELETE FROM users WHERE id=$id");
        }
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id != $_SESSION['user_id']) { // Prevent self-deactivation
            mysqli_query($koneksi, "UPDATE users SET status = CASE WHEN status = 'aktif' THEN 'nonaktif' ELSE 'aktif' END WHERE id=$id");
        }
    }

    header('Location: user_management.php?success=1');
    exit;
}

// Fetch users list
$users = mysqli_query($koneksi, "SELECT id, username, nama, role, email, telepon, alamat, status, created_at FROM users ORDER BY role, nama");

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manajemen User - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body { font-family: 'Poppins', sans-serif; background:#f8f9fa; }
		.dashboard-container { display:flex; min-height:100vh; }
		.sidebar { width:280px; background:linear-gradient(135deg,#2196F3 0%,#1976D2 100%); color:#fff; position:fixed; height:100vh; overflow-y:auto; z-index:1000; }
		.sidebar-header { padding:30px 20px; border-bottom:1px solid rgba(255,255,255,.1); text-align:center; }
		.sidebar-header h3 { font-weight:600; margin-bottom:5px; }
		.sidebar-header p { opacity:.85; font-size:.9rem; }
		.nav-menu { padding:20px 0; }
		.nav-link { display:flex; align-items:center; padding:14px 24px; color:rgba(255,255,255,.85); text-decoration:none; border-left:3px solid transparent; transition:all .25s ease; }
		.nav-link i { width:22px; margin-right:12px; font-size:1.05rem; }
		.nav-link:hover { background:rgba(255,255,255,.12); color:#fff; border-left-color:#fff; }
		.nav-link.active { background:rgba(255,255,255,.18); color:#fff; border-left-color:#fff; }
		.main-content { flex:1; margin-left:280px; padding:30px; }
		.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
		.card { border:none; border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
		.btn-primary { background:linear-gradient(135deg,#2196F3,#1976D2); border:none; border-radius:10px; }
		.table th { border-top:none; font-weight:600; color:#333; }
		.role-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:500; }
		.role-manajer { background:#e3f2fd; color:#1976d2; }
		.role-kasir { background:#f3e5f5; color:#7b1fa2; }
		.role-kitchen { background:#e8f5e8; color:#388e3c; }
		.status-aktif { background:#d4edda; color:#155724; }
		.status-nonaktif { background:#f8d7da; color:#721c24; }
		.user-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#2196F3,#1976D2); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:600; }
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
				<a class="nav-link <?php echo is_active('user_management.php'); ?>" href="user_management.php"><i class="fas fa-user-cog"></i>Manajemen User</a>
				<a class="nav-link <?php echo is_active('order_management.php'); ?>" href="order_management.php"><i class="fas fa-shopping-cart"></i>Manajemen Order</a>
				<a class="nav-link <?php echo is_active('pengaturan.php'); ?>" href="pengaturan.php"><i class="fas fa-cog"></i>Pengaturan</a>
				<a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</div>

		<div class="main-content">
			<div class="page-header">
				<div>
					<h1 class="h4 mb-1"><i class="fas fa-user-cog me-2"></i>Manajemen User</h1>
					<p class="text-muted small mb-0">Kelola semua pengguna sistem</p>
				</div>
				<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus me-2"></i>Tambah User</button>
			</div>

			<?php if (isset($_GET['success'])): ?>
			<div class="alert alert-success rounded-3 border-0">Perubahan tersimpan.</div>
			<?php endif; ?>

			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover align-middle">
							<thead>
								<tr>
									<th style="width:60px;">Avatar</th>
									<th>Nama</th>
									<th>Username</th>
									<th>Role</th>
									<th>Kontak</th>
									<th>Status</th>
									<th>Bergabung</th>
									<th style="width:140px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php while($user = mysqli_fetch_assoc($users)): ?>
								<tr>
									<td>
										<div class="user-avatar">
											<?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
										</div>
									</td>
									<td>
										<strong><?php echo htmlspecialchars($user['nama']); ?></strong>
										<?php if (!empty($user['alamat'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($user['alamat']); ?></small><?php endif; ?>
									</td>
									<td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
									<td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
									<td>
										<?php if (!empty($user['email'])): ?><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?><br><?php endif; ?>
										<?php if (!empty($user['telepon'])): ?><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['telepon']); ?><?php endif; ?>
									</td>
									<td><span class="badge status-<?php echo $user['status'] ?? 'aktif'; ?>"><?php echo ucfirst($user['status'] ?? 'aktif'); ?></span></td>
									<td><small class="text-muted"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small></td>
									<td>
										<button class="btn btn-sm btn-outline-primary me-1" onclick="openEdit(<?php echo $user['id']; ?>,'<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($user['nama'], ENT_QUOTES); ?>','<?php echo $user['role']; ?>','<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($user['telepon'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($user['alamat'] ?? '', ENT_QUOTES); ?>')"><i class="fas fa-edit"></i></button>
										
										<?php if ($user['id'] != $_SESSION['user_id']): ?>
										<form method="POST" class="d-inline" onsubmit="return confirm('<?php echo ($user['status'] ?? 'aktif') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?> user ini?')">
											<input type="hidden" name="action" value="toggle_status">
											<input type="hidden" name="id" value="<?php echo $user['id']; ?>">
											<button class="btn btn-sm btn-outline-<?php echo ($user['status'] ?? 'aktif') === 'aktif' ? 'warning' : 'success'; ?> me-1" title="<?php echo ($user['status'] ?? 'aktif') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
												<i class="fas fa-<?php echo ($user['status'] ?? 'aktif') === 'aktif' ? 'ban' : 'check'; ?>"></i>
											</button>
										</form>
										
										<form method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="id" value="<?php echo $user['id']; ?>">
											<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
										</form>
										<?php endif; ?>
									</td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add Modal -->
	<div class="modal fade" id="addUserModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add">
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" name="username" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Role</label>
							<select name="role" class="form-control" required>
								<option value="">Pilih Role</option>
								<option value="manajer">Manajer</option>
								<option value="kasir">Kasir</option>
								<option value="kitchen">Kitchen</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" name="email" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Telepon</label>
							<input type="text" name="telepon" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Alamat</label>
							<textarea name="alamat" class="form-control" rows="3"></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit Modal -->
	<div class="modal fade" id="editUserModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit">
						<input type="hidden" name="id" id="edit-id">
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" name="username" id="edit-username" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small></label>
							<input type="password" name="password" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" id="edit-nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Role</label>
							<select name="role" id="edit-role" class="form-control" required>
								<option value="manajer">Manajer</option>
								<option value="kasir">Kasir</option>
								<option value="kitchen">Kitchen</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" name="email" id="edit-email" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Telepon</label>
							<input type="text" name="telepon" id="edit-telepon" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Alamat</label>
							<textarea name="alamat" id="edit-alamat" class="form-control" rows="3"></textarea>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		function openEdit(id, username, nama, role, email, telepon, alamat) {
			document.getElementById('edit-id').value = id;
			document.getElementById('edit-username').value = username;
			document.getElementById('edit-nama').value = nama;
			document.getElementById('edit-role').value = role;
			document.getElementById('edit-email').value = email;
			document.getElementById('edit-telepon').value = telepon;
			document.getElementById('edit-alamat').value = alamat;
			var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
			modal.show();
		}
	</script>
</body>
</html>
