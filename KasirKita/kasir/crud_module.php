<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php?role=kasir");
    exit;
}

include "../koneksi.php";

// Handle POST with redirect-after-POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_customer') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        
        if ($nama) {
            mysqli_query($koneksi, "INSERT INTO customers (nama, telepon, alamat, email) VALUES ('$nama', '$telepon', '$alamat', '$email')");
        }
    }

    if ($action === 'edit_customer') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE customers SET nama='$nama', telepon='$telepon', alamat='$alamat', email='$email' WHERE id=$id");
        }
    }

    if ($action === 'delete_customer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM customers WHERE id=$id");
        }
    }

    if ($action === 'add_table') {
        $nomor = (int)($_POST['nomor'] ?? 0);
        $kapasitas = (int)($_POST['kapasitas'] ?? 0);
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'tersedia');
        
        if ($nomor > 0) {
            mysqli_query($koneksi, "INSERT INTO tables (nomor, kapasitas, status) VALUES ($nomor, $kapasitas, '$status')");
        }
    }

    if ($action === 'edit_table') {
        $id = (int)($_POST['id'] ?? 0);
        $nomor = (int)($_POST['nomor'] ?? 0);
        $kapasitas = (int)($_POST['kapasitas'] ?? 0);
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'tersedia');
        
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE tables SET nomor=$nomor, kapasitas=$kapasitas, status='$status' WHERE id=$id");
        }
    }

    if ($action === 'delete_table') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM tables WHERE id=$id");
        }
    }

    header('Location: crud_module.php?success=1');
    exit;
}

// Fetch data
$customers = mysqli_query($koneksi, "SELECT * FROM customers ORDER BY nama");
$tables = mysqli_query($koneksi, "SELECT * FROM tables ORDER BY nomor");

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>CRUD Module - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body { font-family: 'Poppins', sans-serif; background:#f8f9fa; }
		.dashboard-container { display:flex; min-height:100vh; }
		.sidebar { width:280px; background:linear-gradient(135deg,#7b1fa2 0%,#6a1b9a 100%); color:#fff; position:fixed; height:100vh; overflow-y:auto; z-index:1000; }
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
		.btn-primary { background:linear-gradient(135deg,#7b1fa2,#6a1b9a); border:none; border-radius:10px; }
		.table th { border-top:none; font-weight:600; color:#333; }
		.status-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:500; }
		.status-tersedia { background:#d4edda; color:#155724; }
		.status-terisi { background:#f8d7da; color:#721c24; }
		.status-reserved { background:#fff3cd; color:#856404; }
		.nav-tabs .nav-link { border:none; color:#6c757d; }
		.nav-tabs .nav-link.active { color:#7b1fa2; border-bottom:2px solid #7b1fa2; }
	</style>
</head>
<body>
	<div class="dashboard-container">
		<div class="sidebar">
			<div class="sidebar-header">
				<h3><i class="fas fa-cash-register me-2"></i>KasirKita</h3>
				<p>Kasir Dashboard</p>
			</div>
			<div class="nav-menu">
				<a class="nav-link <?php echo is_active('index.php'); ?>" href="index.php"><i class="fas fa-home"></i>Dashboard</a>
				<a class="nav-link <?php echo is_active('crud_module.php'); ?>" href="crud_module.php"><i class="fas fa-database"></i>CRUD Module</a>
				<a class="nav-link <?php echo is_active('order_history.php'); ?>" href="order_history.php"><i class="fas fa-history"></i>Riwayat Order</a>
				<a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</div>

		<div class="main-content">
			<div class="page-header">
				<div>
					<h1 class="h4 mb-1"><i class="fas fa-database me-2"></i>CRUD Module</h1>
					<p class="text-muted small mb-0">Kelola data pelanggan dan meja</p>
				</div>
			</div>

			<?php if (isset($_GET['success'])): ?>
			<div class="alert alert-success rounded-3 border-0">Perubahan tersimpan.</div>
			<?php endif; ?>

			<!-- Tabs -->
			<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button" role="tab">
						<i class="fas fa-users me-2"></i>Data Pelanggan
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="tables-tab" data-bs-toggle="tab" data-bs-target="#tables" type="button" role="tab">
						<i class="fas fa-chair me-2"></i>Data Meja
					</button>
				</li>
			</ul>

			<div class="tab-content" id="myTabContent">
				<!-- Customers Tab -->
				<div class="tab-pane fade show active" id="customers" role="tabpanel">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="mb-0"><i class="fas fa-users me-2"></i>Data Pelanggan</h5>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
								<i class="fas fa-plus me-1"></i>Tambah Pelanggan
							</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead>
										<tr>
											<th>ID</th>
											<th>Nama</th>
											<th>Kontak</th>
											<th>Alamat</th>
											<th style="width:120px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($customer = mysqli_fetch_assoc($customers)): ?>
										<tr>
											<td><strong>#<?php echo $customer['id']; ?></strong></td>
											<td>
												<strong><?php echo htmlspecialchars($customer['nama']); ?></strong>
												<?php if (!empty($customer['email'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small><?php endif; ?>
											</td>
											<td>
												<?php if (!empty($customer['telepon'])): ?><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['telepon']); ?><br><?php endif; ?>
											</td>
											<td><small class="text-muted"><?php echo htmlspecialchars($customer['alamat'] ?? '-'); ?></small></td>
											<td>
												<button class="btn btn-sm btn-outline-primary me-1" onclick="openEditCustomer(<?php echo $customer['id']; ?>,'<?php echo htmlspecialchars($customer['nama'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($customer['telepon'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($customer['alamat'] ?? '', ENT_QUOTES); ?>','<?php echo htmlspecialchars($customer['email'] ?? '', ENT_QUOTES); ?>')">
													<i class="fas fa-edit"></i>
												</button>
												<form method="POST" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?')">
													<input type="hidden" name="action" value="delete_customer">
													<input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
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

				<!-- Tables Tab -->
				<div class="tab-pane fade" id="tables" role="tabpanel">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="mb-0"><i class="fas fa-chair me-2"></i>Data Meja</h5>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTableModal">
								<i class="fas fa-plus me-1"></i>Tambah Meja
							</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead>
										<tr>
											<th>ID</th>
											<th>Nomor Meja</th>
											<th>Kapasitas</th>
											<th>Status</th>
											<th style="width:120px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($table = mysqli_fetch_assoc($tables)): ?>
										<tr>
											<td><strong>#<?php echo $table['id']; ?></strong></td>
											<td><strong>Meja <?php echo $table['nomor']; ?></strong></td>
											<td><i class="fas fa-users me-1"></i><?php echo $table['kapasitas']; ?> orang</td>
											<td><span class="status-badge status-<?php echo $table['status']; ?>"><?php echo ucfirst($table['status']); ?></span></td>
											<td>
												<button class="btn btn-sm btn-outline-primary me-1" onclick="openEditTable(<?php echo $table['id']; ?>,<?php echo $table['nomor']; ?>,<?php echo $table['kapasitas']; ?>,'<?php echo $table['status']; ?>')">
													<i class="fas fa-edit"></i>
												</button>
												<form method="POST" class="d-inline" onsubmit="return confirm('Hapus meja ini?')">
													<input type="hidden" name="action" value="delete_table">
													<input type="hidden" name="id" value="<?php echo $table['id']; ?>">
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
		</div>
	</div>

	<!-- Add Customer Modal -->
	<div class="modal fade" id="addCustomerModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Pelanggan</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_customer">
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Telepon</label>
							<input type="text" name="telepon" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" name="email" class="form-control">
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

	<!-- Edit Customer Modal -->
	<div class="modal fade" id="editCustomerModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Pelanggan</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_customer">
						<input type="hidden" name="id" id="edit-customer-id">
						<div class="mb-3">
							<label class="form-label">Nama Lengkap</label>
							<input type="text" name="nama" id="edit-customer-nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Telepon</label>
							<input type="text" name="telepon" id="edit-customer-telepon" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Email</label>
							<input type="email" name="email" id="edit-customer-email" class="form-control">
						</div>
						<div class="mb-3">
							<label class="form-label">Alamat</label>
							<textarea name="alamat" id="edit-customer-alamat" class="form-control" rows="3"></textarea>
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

	<!-- Add Table Modal -->
	<div class="modal fade" id="addTableModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Meja</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_table">
						<div class="mb-3">
							<label class="form-label">Nomor Meja</label>
							<input type="number" name="nomor" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Kapasitas</label>
							<input type="number" name="kapasitas" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Status</label>
							<select name="status" class="form-control" required>
								<option value="tersedia">Tersedia</option>
								<option value="terisi">Terisi</option>
								<option value="reserved">Reserved</option>
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

	<!-- Edit Table Modal -->
	<div class="modal fade" id="editTableModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Meja</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_table">
						<input type="hidden" name="id" id="edit-table-id">
						<div class="mb-3">
							<label class="form-label">Nomor Meja</label>
							<input type="number" name="nomor" id="edit-table-nomor" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Kapasitas</label>
							<input type="number" name="kapasitas" id="edit-table-kapasitas" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Status</label>
							<select name="status" id="edit-table-status" class="form-control" required>
								<option value="tersedia">Tersedia</option>
								<option value="terisi">Terisi</option>
								<option value="reserved">Reserved</option>
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
		function openEditCustomer(id, nama, telepon, alamat, email) {
			document.getElementById('edit-customer-id').value = id;
			document.getElementById('edit-customer-nama').value = nama;
			document.getElementById('edit-customer-telepon').value = telepon;
			document.getElementById('edit-customer-alamat').value = alamat;
			document.getElementById('edit-customer-email').value = email;
			var modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
			modal.show();
		}

		function openEditTable(id, nomor, kapasitas, status) {
			document.getElementById('edit-table-id').value = id;
			document.getElementById('edit-table-nomor').value = nomor;
			document.getElementById('edit-table-kapasitas').value = kapasitas;
			document.getElementById('edit-table-status').value = status;
			var modal = new bootstrap.Modal(document.getElementById('editTableModal'));
			modal.show();
		}
	</script>
</body>
</html>
