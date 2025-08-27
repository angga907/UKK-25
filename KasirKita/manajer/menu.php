<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

// Detect optional 'status' column on menu table
$colCheck = @mysqli_query($koneksi, "SHOW COLUMNS FROM menu LIKE 'status'");
$hasStatus = $colCheck && mysqli_num_rows($colCheck) > 0;

// Handle POST with redirect-after-POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_menu'] ?? '');
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'aktif');
        if ($nama && $kategori && $harga > 0) {
            if ($hasStatus) {
                mysqli_query($koneksi, "INSERT INTO menu (nama_menu, kategori, harga, deskripsi, status) VALUES ('$nama', '$kategori', $harga, '$deskripsi', '$status')");
            } else {
                mysqli_query($koneksi, "INSERT INTO menu (nama_menu, kategori, harga, deskripsi) VALUES ('$nama', '$kategori', $harga, '$deskripsi')");
            }
        }
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama_menu'] ?? '');
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'aktif');
        if ($id > 0) {
            if ($hasStatus) {
                mysqli_query($koneksi, "UPDATE menu SET nama_menu='$nama', kategori='$kategori', harga=$harga, deskripsi='$deskripsi', status='$status' WHERE id=$id");
            } else {
                mysqli_query($koneksi, "UPDATE menu SET nama_menu='$nama', kategori='$kategori', harga=$harga, deskripsi='$deskripsi' WHERE id=$id");
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM menu WHERE id=$id");
        }
    }

    header('Location: menu.php?success=1');
    exit;
}

// Fetch menu list depending on column availability
if ($hasStatus) {
    $menus = mysqli_query($koneksi, "SELECT id, nama_menu, kategori, harga, deskripsi, status FROM menu ORDER BY kategori, nama_menu");
} else {
    $menus = mysqli_query($koneksi, "SELECT id, nama_menu, kategori, harga, deskripsi FROM menu ORDER BY kategori, nama_menu");
}

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Data Menu - KasirKita</title>
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
		.kategori-badge, .status-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:500; }
		.kategori-makanan { background:#d4edda; color:#155724; }
		.kategori-minuman { background:#cce5ff; color:#004085; }
		.kategori-dessert { background:#fff3cd; color:#856404; }
		.status-aktif { background:#d4edda; color:#155724; }
		.status-nonaktif { background:#f8d7da; color:#721c24; }
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
					<h1 class="h4 mb-1"><i class="fas fa-utensils me-2"></i>Data Menu</h1>
					<p class="text-muted small mb-0">Kelola menu restoran</p>
				</div>
				<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal"><i class="fas fa-plus me-2"></i>Tambah Menu</button>
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
									<th>Nama Menu</th>
									<th>Kategori</th>
									<th>Harga</th>
									<?php if ($hasStatus): ?><th>Status</th><?php endif; ?>
									<th style="width:140px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php while($menu = mysqli_fetch_assoc($menus)): ?>
								<tr>
									<td>#<?php echo $menu['id']; ?></td>
									<td>
										<strong><?php echo htmlspecialchars($menu['nama_menu']); ?></strong>
										<?php if (!empty($menu['deskripsi'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($menu['deskripsi']); ?></small><?php endif; ?>
									</td>
									<td><span class="kategori-badge kategori-<?php echo $menu['kategori']; ?>"><?php echo ucfirst($menu['kategori']); ?></span></td>
									<td><strong>Rp <?php echo number_format($menu['harga'],0,',','.'); ?></strong></td>
									<?php if ($hasStatus): ?><td><span class="status-badge status-<?php echo $menu['status']; ?>"><?php echo ucfirst($menu['status']); ?></span></td><?php endif; ?>
									<td>
										<button class="btn btn-sm btn-outline-primary me-2" onclick="openEdit(<?php echo $menu['id']; ?>,'<?php echo htmlspecialchars($menu['nama_menu'], ENT_QUOTES); ?>','<?php echo $menu['kategori']; ?>',<?php echo (int)$menu['harga']; ?>,'<?php echo htmlspecialchars($menu['deskripsi'] ?? '', ENT_QUOTES); ?>'<?php echo $hasStatus ? ",'" . $menu['status'] . "'" : ",''"; ?>)"><i class="fas fa-edit"></i></button>
										<form method="POST" class="d-inline" onsubmit="return confirm('Hapus menu ini?')">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
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
	<div class="modal fade" id="addMenuModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Menu</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add">
						<div class="mb-3"><label class="form-label">Nama Menu</label><input type="text" name="nama_menu" class="form-control" required></div>
						<div class="mb-3"><label class="form-label">Kategori</label>
							<select name="kategori" class="form-control" required>
								<option value="">Pilih Kategori</option>
								<option value="makanan">Makanan</option>
								<option value="minuman">Minuman</option>
								<option value="dessert">Dessert</option>
							</select>
						</div>
						<div class="mb-3"><label class="form-label">Harga</label><input type="number" name="harga" class="form-control" required></div>
						<div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
						<?php if ($hasStatus): ?>
						<div class="mb-2"><label class="form-label">Status</label>
							<select name="status" class="form-control" required>
								<option value="aktif">Aktif</option>
								<option value="nonaktif">Nonaktif</option>
							</select>
						</div>
						<?php endif; ?>
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
	<div class="modal fade" id="editMenuModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Menu</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit">
						<input type="hidden" name="id" id="edit-id">
						<div class="mb-3"><label class="form-label">Nama Menu</label><input type="text" name="nama_menu" id="edit-nama" class="form-control" required></div>
						<div class="mb-3"><label class="form-label">Kategori</label>
							<select name="kategori" id="edit-kategori" class="form-control" required>
								<option value="makanan">Makanan</option>
								<option value="minuman">Minuman</option>
								<option value="dessert">Dessert</option>
							</select>
						</div>
						<div class="mb-3"><label class="form-label">Harga</label><input type="number" name="harga" id="edit-harga" class="form-control" required></div>
						<div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="edit-deskripsi" class="form-control" rows="3"></textarea></div>
						<?php if ($hasStatus): ?>
						<div class="mb-2"><label class="form-label">Status</label>
							<select name="status" id="edit-status" class="form-control" required>
								<option value="aktif">Aktif</option>
								<option value="nonaktif">Nonaktif</option>
							</select>
						</div>
						<?php endif; ?>
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
		function openEdit(id, nama, kategori, harga, deskripsi, status) {
			document.getElementById('edit-id').value = id;
			document.getElementById('edit-nama').value = nama;
			document.getElementById('edit-kategori').value = kategori;
			document.getElementById('edit-harga').value = harga;
			document.getElementById('edit-deskripsi').value = deskripsi;
			if (document.getElementById('edit-status')) {
				document.getElementById('edit-status').value = status || 'aktif';
			}
			var modal = new bootstrap.Modal(document.getElementById('editMenuModal'));
			modal.show();
		}
	</script>
</body>
</html>
