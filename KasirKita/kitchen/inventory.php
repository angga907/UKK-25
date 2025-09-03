<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kitchen') {
    header("Location: ../login.php?role=kitchen");
    exit;
}

include "../koneksi.php";

// Handle POST with redirect-after-POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $adjustment = (int)($_POST['adjustment'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE ingredients SET stok = GREATEST(0, stok + $adjustment) WHERE id=$id");
        }
    }

    if ($action === 'update_min_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $min_stok = (int)($_POST['min_stok'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE ingredients SET min_stok = $min_stok WHERE id=$id");
        }
    }

    header('Location: inventory.php?success=1');
    exit;
}

// Fetch ingredients with low stock alert
$ingredients = mysqli_query($koneksi, "SELECT * FROM ingredients ORDER BY 
    CASE 
        WHEN stok <= min_stok THEN 1 
        WHEN stok <= min_stok * 1.5 THEN 2 
        ELSE 3 
    END, nama");

// Get statistics
$stats = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total_ingredients,
    SUM(CASE WHEN stok <= min_stok THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stok <= min_stok * 1.5 THEN 1 ELSE 0 END) as warning_stock,
    SUM(stok * harga) as total_value
    FROM ingredients");

$stat = mysqli_fetch_assoc($stats);

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Inventory - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body { font-family: 'Poppins', sans-serif; background:#f8f9fa; }
		.dashboard-container { display:flex; min-height:100vh; }
		.sidebar { width:280px; background:linear-gradient(135deg,#388e3c 0%,#2e7d32 100%); color:#fff; position:fixed; height:100vh; overflow-y:auto; z-index:1000; }
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
		.btn-primary { background:linear-gradient(135deg,#388e3c,#2e7d32); border:none; border-radius:10px; }
		.table th { border-top:none; font-weight:600; color:#333; }
		.stock-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:500; }
		.stock-high { background:#d4edda; color:#155724; }
		.stock-medium { background:#fff3cd; color:#856404; }
		.stock-low { background:#f8d7da; color:#721c24; }
		.stats-card { background:linear-gradient(135deg,#388e3c,#2e7d32); color:#fff; }
		.alert-card { background:#fff; border-left:4px solid #dc3545; }
		.progress { height:8px; }
	</style>
</head>
<body>
	<div class="dashboard-container">
		<div class="sidebar">
			<div class="sidebar-header">
				<h3><i class="fas fa-fire me-2"></i>KasirKita</h3>
				<p>Kitchen Dashboard</p>
			</div>
			<div class="nav-menu">
				<a class="nav-link <?php echo is_active('index.php'); ?>" href="index.php"><i class="fas fa-home"></i>Dashboard</a>
				<a class="nav-link <?php echo is_active('crud_module.php'); ?>" href="crud_module.php"><i class="fas fa-database"></i>CRUD Module</a>
				<a class="nav-link <?php echo is_active('inventory.php'); ?>" href="inventory.php"><i class="fas fa-boxes"></i>Inventory</a>
				<a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</div>

		<div class="main-content">
			<div class="page-header">
				<div>
					<h1 class="h4 mb-1"><i class="fas fa-boxes me-2"></i>Inventory</h1>
					<p class="text-muted small mb-0">Kelola stok bahan baku</p>
				</div>
			</div>

			<?php if (isset($_GET['success'])): ?>
			<div class="alert alert-success rounded-3 border-0">Perubahan tersimpan.</div>
			<?php endif; ?>

			<!-- Statistics Cards -->
			<div class="row mb-4">
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-boxes fa-2x mb-2"></i>
							<h4><?php echo $stat['total_ingredients']; ?></h4>
							<p class="mb-0">Total Bahan</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
							<h4><?php echo $stat['low_stock']; ?></h4>
							<p class="mb-0">Stok Rendah</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-exclamation-circle fa-2x mb-2"></i>
							<h4><?php echo $stat['warning_stock']; ?></h4>
							<p class="mb-0">Perhatian</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-dollar-sign fa-2x mb-2"></i>
							<h4>Rp <?php echo number_format($stat['total_value'],0,',','.'); ?></h4>
							<p class="mb-0">Nilai Total</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Low Stock Alert -->
			<?php if ($stat['low_stock'] > 0): ?>
			<div class="alert alert-danger alert-card mb-4">
				<div class="d-flex align-items-center">
					<i class="fas fa-exclamation-triangle fa-2x me-3"></i>
					<div>
						<h5 class="alert-heading mb-1">Peringatan Stok Rendah!</h5>
						<p class="mb-0">Ada <?php echo $stat['low_stock']; ?> bahan baku yang stoknya sudah di bawah minimum. Segera lakukan restock!</p>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Inventory Table -->
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Bahan Baku</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover align-middle">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Bahan</th>
									<th>Stok Saat Ini</th>
									<th>Min. Stok</th>
									<th>Progress</th>
									<th>Satuan</th>
									<th>Harga</th>
									<th style="width:140px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php while($ingredient = mysqli_fetch_assoc($ingredients)): 
									$stock_percentage = $ingredient['min_stok'] > 0 ? ($ingredient['stok'] / ($ingredient['min_stok'] * 2)) * 100 : 100;
									$stock_percentage = min(100, max(0, $stock_percentage));
									$stock_class = $ingredient['stok'] <= $ingredient['min_stok'] ? 'low' : ($ingredient['stok'] <= $ingredient['min_stok'] * 1.5 ? 'medium' : 'high');
								?>
								<tr>
									<td><strong>#<?php echo $ingredient['id']; ?></strong></td>
									<td><strong><?php echo htmlspecialchars($ingredient['nama']); ?></strong></td>
									<td>
										<span class="stock-badge stock-<?php echo $stock_class; ?>">
											<?php echo $ingredient['stok']; ?>
										</span>
									</td>
									<td><small class="text-muted"><?php echo $ingredient['min_stok']; ?></small></td>
									<td>
										<div class="progress" style="width:100px;">
											<div class="progress-bar bg-<?php echo $stock_class === 'low' ? 'danger' : ($stock_class === 'medium' ? 'warning' : 'success'); ?>" 
												 style="width: <?php echo $stock_percentage; ?>%"></div>
										</div>
									</td>
									<td><small class="text-muted"><?php echo $ingredient['satuan']; ?></small></td>
									<td><strong>Rp <?php echo number_format($ingredient['harga'],0,',','.'); ?></strong></td>
									<td>
										<div class="btn-group btn-group-sm">
											<form method="POST" class="d-inline" onsubmit="return confirm('Tambah stok +1?')">
												<input type="hidden" name="action" value="adjust_stock">
												<input type="hidden" name="id" value="<?php echo $ingredient['id']; ?>">
												<input type="hidden" name="adjustment" value="1">
												<button class="btn btn-outline-success btn-sm" title="Tambah Stok"><i class="fas fa-plus"></i></button>
											</form>
											<form method="POST" class="d-inline" onsubmit="return confirm('Kurangi stok -1?')">
												<input type="hidden" name="action" value="adjust_stock">
												<input type="hidden" name="id" value="<?php echo $ingredient['id']; ?>">
												<input type="hidden" name="adjustment" value="-1">
												<button class="btn btn-outline-danger btn-sm" title="Kurangi Stok"><i class="fas fa-minus"></i></button>
											</form>
											<button class="btn btn-outline-primary btn-sm" onclick="openMinStockModal(<?php echo $ingredient['id']; ?>, <?php echo $ingredient['min_stok']; ?>)" title="Ubah Min. Stok">
												<i class="fas fa-cog"></i>
											</button>
										</div>
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

	<!-- Min Stock Modal -->
	<div class="modal fade" id="minStockModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Minimum Stok</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="update_min_stock">
						<input type="hidden" name="id" id="min-stock-id">
						<div class="mb-3">
							<label class="form-label">Minimum Stok</label>
							<input type="number" name="min_stok" id="min-stock-value" class="form-control" min="0" required>
							<small class="text-muted">Stok minimum untuk peringatan</small>
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
		function openMinStockModal(id, currentMinStock) {
			document.getElementById('min-stock-id').value = id;
			document.getElementById('min-stock-value').value = currentMinStock;
			var modal = new bootstrap.Modal(document.getElementById('minStockModal'));
			modal.show();
		}
	</script>
</body>
</html>
