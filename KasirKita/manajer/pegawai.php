<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

// Handle form submissions with redirect-after-POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
        $passwordRaw = $_POST['password'] ?? '';
        $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? '');
        if ($nama && $username && $passwordRaw && $role) {
            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$password', '$role')");
        }
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
        $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? '');
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE users SET nama='$nama', username='$username', role='$role' WHERE id=$id");
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                mysqli_query($koneksi, "UPDATE users SET password='$password' WHERE id=$id");
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM users WHERE id=$id");
        }
    }

    header('Location: pegawai.php?success=1');
    exit;
}

// Get employees (exclude manajer)
$query_employees = mysqli_query($koneksi, "SELECT id, nama, username, role FROM users WHERE role IN ('kasir','kitchen') ORDER BY nama");

function is_active($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Data Pegawai - KasirKita</title>
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
		.role-kasir { background:#d4edda; color:#155724; }
		.role-kitchen { background:#fff3cd; color:#856404; }
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
			<div class="page-header">
				<div>
					<h1 class="h4 mb-1"><i class="fas fa-users me-2"></i>Data Pegawai</h1>
					<p class="text-muted small mb-0">Kelola data pegawai restoran</p>
				</div>
				<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal"><i class="fas fa-plus me-2"></i>Tambah Pegawai</button>
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
									<th style="width:80px;">ID</th>
									<th>Nama</th>
									<th>Username</th>
									<th>Role</th>
									<th style="width:140px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php while($employee = mysqli_fetch_assoc($query_employees)): ?>
								<tr>
									<td>#<?php echo $employee['id']; ?></td>
									<td><?php echo htmlspecialchars($employee['nama']); ?></td>
									<td><?php echo htmlspecialchars($employee['username']); ?></td>
									<td>
										<span class="role-badge role-<?php echo $employee['role']; ?>"><?php echo ucfirst($employee['role']); ?></span>
									</td>
									<td>
										<button class="btn btn-sm btn-outline-primary me-2" onclick="openEdit(<?php echo $employee['id']; ?>,'<?php echo htmlspecialchars($employee['nama'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($employee['username'], ENT_QUOTES); ?>','<?php echo $employee['role']; ?>')"><i class="fas fa-edit"></i></button>
										<form method="POST" class="d-inline" onsubmit="return confirm('Hapus pegawai ini?')">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
											<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
										</form>
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
	<div class="modal fade" id="addEmployeeModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Pegawai</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add">
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" name="username" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password</label>
							<input type="password" name="password" class="form-control" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Role</label>
							<select name="role" class="form-control" required>
								<option value="">Pilih Role</option>
								<option value="kasir">Kasir</option>
								<option value="kitchen">Kitchen</option>
							</select>
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
	<div class="modal fade" id="editEmployeeModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Pegawai</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit">
						<input type="hidden" name="id" id="edit-id">
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" id="edit-nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Username</label>
							<input type="text" name="username" id="edit-username" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Password Baru (opsional)</label>
							<input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
						</div>
						<div class="mb-2">
							<label class="form-label">Role</label>
							<select name="role" id="edit-role" class="form-control" required>
								<option value="kasir">Kasir</option>
								<option value="kitchen">Kitchen</option>
							</select>
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
		function openEdit(id, nama, username, role) {
			document.getElementById('edit-id').value = id;
			document.getElementById('edit-nama').value = nama;
			document.getElementById('edit-username').value = username;
			document.getElementById('edit-role').value = role;
			var modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
			modal.show();
		}
	</script>
</body>
</html>
