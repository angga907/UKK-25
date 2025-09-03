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

    if ($action === 'adjust_menu_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $adjustment = (int)($_POST['adjustment'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE menu SET stok = GREATEST(0, stok + $adjustment) WHERE id=$id");
        }
    }

    if ($action === 'adjust_ingredient_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $adjustment = (int)($_POST['adjustment'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE ingredients SET stok = GREATEST(0, stok + $adjustment) WHERE id=$id");
        }
    }

    if ($action === 'update_min_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $min_stok = (int)($_POST['min_stok'] ?? 0);
        $type = $_POST['type'] ?? '';
        if ($id > 0 && $type) {
            if ($type === 'menu') {
                mysqli_query($koneksi, "UPDATE menu SET min_stok = $min_stok WHERE id=$id");
            } else {
                mysqli_query($koneksi, "UPDATE ingredients SET min_stok = $min_stok WHERE id=$id");
            }
        }
    }

    header('Location: stock_management.php?success=1');
    exit;
}

// Check if tables exist
$menuExists = mysqli_query($koneksi, "SHOW TABLES LIKE 'menu'");
$ingredientsExists = mysqli_query($koneksi, "SHOW TABLES LIKE 'ingredients'");

$hasMenu = mysqli_num_rows($menuExists) > 0;
$hasIngredients = mysqli_num_rows($ingredientsExists) > 0;

// Get menu stock data
$menuStock = [];
if ($hasMenu) {
    $colCheckStok = @mysqli_query($koneksi, "SHOW COLUMNS FROM menu LIKE 'stok'");
    $hasMenuStock = $colCheckStok && mysqli_num_rows($colCheckStok) > 0;
    
    if ($hasMenuStock) {
        $colCheckMinStok = @mysqli_query($koneksi, "SHOW COLUMNS FROM menu LIKE 'min_stok'");
        $hasMenuMinStock = $colCheckMinStok && mysqli_num_rows($colCheckMinStok) > 0;
        
        $selectFields = "id, nama_menu, stok";
        if ($hasMenuMinStock) {
            $selectFields .= ", min_stok";
        }
        
        $menuStock = mysqli_query($koneksi, "SELECT $selectFields FROM menu ORDER BY nama_menu");
    }
}

// Get ingredients stock data
$ingredientsStock = [];
if ($hasIngredients) {
    $ingredientsStock = mysqli_query($koneksi, "SELECT id, nama, stok, min_stok, satuan FROM ingredients ORDER BY nama");
}

// Get statistics
$stats = [
    'total_menu' => 0,
    'low_stock_menu' => 0,
    'total_ingredients' => 0,
    'low_stock_ingredients' => 0,
    'total_menu_value' => 0,
    'total_ingredients_value' => 0
];

if ($hasMenu && $hasMenuStock) {
    $menuStats = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stok <= COALESCE(min_stok, 0) THEN 1 ELSE 0 END) as low_stock
        FROM menu");
    $menuStat = mysqli_fetch_assoc($menuStats);
    $stats['total_menu'] = $menuStat['total'];
    $stats['low_stock_menu'] = $menuStat['low_stock'];
}

if ($hasIngredients) {
    $ingredientStats = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stok <= min_stok THEN 1 ELSE 0 END) as low_stock,
        SUM(stok * harga) as total_value
        FROM ingredients");
    $ingredientStat = mysqli_fetch_assoc($ingredientStats);
    $stats['total_ingredients'] = $ingredientStat['total'];
    $stats['low_stock_ingredients'] = $ingredientStat['low_stock'];
    $stats['total_ingredients_value'] = $ingredientStat['total_value'];
}

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Stock Management - KasirKita</title>
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
		.stock-badge { padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:500; }
		.stock-high { background:#d4edda; color:#155724; }
		.stock-medium { background:#fff3cd; color:#856404; }
		.stock-low { background:#f8d7da; color:#721c24; }
		.stats-card { background:linear-gradient(135deg,#2196F3,#1976D2); color:#fff; }
		.alert-card { background:#fff; border-left:4px solid #dc3545; }
		.progress { height:8px; }
		.nav-tabs .nav-link { border:none; color:#6c757d; }
		.nav-tabs .nav-link.active { color:#2196F3; border-bottom:2px solid #2196F3; }
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
				<a class="nav-link <?php echo is_active('stock_management.php'); ?>" href="stock_management.php"><i class="fas fa-boxes"></i>Stock Management</a>
				<a class="nav-link <?php echo is_active('pengaturan.php'); ?>" href="pengaturan.php"><i class="fas fa-cog"></i>Pengaturan</a>
				<a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			</div>
		</div>

		<div class="main-content">
			<div class="page-header">
				<div>
					<h1 class="h4 mb-1"><i class="fas fa-boxes me-2"></i>Stock Management</h1>
					<p class="text-muted small mb-0">Kelola stok menu dan bahan baku</p>
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
							<i class="fas fa-utensils fa-2x mb-2"></i>
							<h4><?php echo $stats['total_menu']; ?></h4>
							<p class="mb-0">Total Menu</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
							<h4><?php echo $stats['low_stock_menu']; ?></h4>
							<p class="mb-0">Menu Stok Rendah</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-carrot fa-2x mb-2"></i>
							<h4><?php echo $stats['total_ingredients']; ?></h4>
							<p class="mb-0">Total Bahan</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-exclamation-circle fa-2x mb-2"></i>
							<h4><?php echo $stats['low_stock_ingredients']; ?></h4>
							<p class="mb-0">Bahan Stok Rendah</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Low Stock Alert -->
			<?php if (($stats['low_stock_menu'] + $stats['low_stock_ingredients']) > 0): ?>
			<div class="alert alert-danger alert-card mb-4">
				<div class="d-flex align-items-center">
					<i class="fas fa-exclamation-triangle fa-2x me-3"></i>
					<div>
						<h5 class="alert-heading mb-1">Peringatan Stok Rendah!</h5>
						<p class="mb-0">Ada <?php echo $stats['low_stock_menu'] + $stats['low_stock_ingredients']; ?> item yang stoknya sudah di bawah minimum. Segera lakukan restock!</p>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Tabs -->
			<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
				<?php if ($hasMenu && $hasMenuStock): ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" type="button" role="tab">
						<i class="fas fa-utensils me-2"></i>Stok Menu
					</button>
				</li>
				<?php endif; ?>
				<?php if ($hasIngredients): ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link <?php echo !$hasMenu || !$hasMenuStock ? 'active' : ''; ?>" id="ingredients-tab" data-bs-toggle="tab" data-bs-target="#ingredients" type="button" role="tab">
						<i class="fas fa-carrot me-2"></i>Stok Bahan Baku
					</button>
				</li>
				<?php endif; ?>
			</ul>

			<div class="tab-content" id="myTabContent">
				<!-- Menu Stock Tab -->
				<?php if ($hasMenu && $hasMenuStock): ?>
				<div class="tab-pane fade show active" id="menu" role="tabpanel">
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0"><i class="fas fa-utensils me-2"></i>Stok Menu</h5>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead>
										<tr>
											<th>ID</th>
											<th>Nama Menu</th>
											<th>Stok Saat Ini</th>
											<?php if ($hasMenuMinStock): ?>
											<th>Min. Stok</th>
											<th>Progress</th>
											<?php endif; ?>
											<th style="width:140px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($menu = mysqli_fetch_assoc($menuStock)): 
											$min_stok = $menu['min_stok'] ?? 0;
											$stock_percentage = $min_stok > 0 ? ($menu['stok'] / ($min_stok * 2)) * 100 : 100;
											$stock_percentage = min(100, max(0, $stock_percentage));
											$stock_class = $min_stok > 0 && $menu['stok'] <= $min_stok ? 'low' : ($min_stok > 0 && $menu['stok'] <= $min_stok * 1.5 ? 'medium' : 'high');
										?>
										<tr>
											<td><strong>#<?php echo $menu['id']; ?></strong></td>
											<td><strong><?php echo htmlspecialchars($menu['nama_menu']); ?></strong></td>
											<td>
												<span class="stock-badge stock-<?php echo $stock_class; ?>">
													<?php echo $menu['stok']; ?>
												</span>
											</td>
											<?php if ($hasMenuMinStock): ?>
											<td><small class="text-muted"><?php echo $min_stok; ?></small></td>
											<td>
												<div class="progress" style="width:100px;">
													<div class="progress-bar bg-<?php echo $stock_class === 'low' ? 'danger' : ($stock_class === 'medium' ? 'warning' : 'success'); ?>" 
														 style="width: <?php echo $stock_percentage; ?>%"></div>
												</div>
											</td>
											<?php endif; ?>
											<td>
												<div class="btn-group btn-group-sm">
													<form method="POST" class="d-inline" onsubmit="return confirm('Tambah stok +1?')">
														<input type="hidden" name="action" value="adjust_menu_stock">
														<input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
														<input type="hidden" name="adjustment" value="1">
														<button class="btn btn-outline-success btn-sm" title="Tambah Stok"><i class="fas fa-plus"></i></button>
													</form>
													<form method="POST" class="d-inline" onsubmit="return confirm('Kurangi stok -1?')">
														<input type="hidden" name="action" value="adjust_menu_stock">
														<input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
														<input type="hidden" name="adjustment" value="-1">
														<button class="btn btn-outline-danger btn-sm" title="Kurangi Stok"><i class="fas fa-minus"></i></button>
													</form>
													<?php if ($hasMenuMinStock): ?>
													<button class="btn btn-outline-primary btn-sm" onclick="openMinStockModal(<?php echo $menu['id']; ?>, <?php echo $min_stok; ?>, 'menu')" title="Ubah Min. Stok">
														<i class="fas fa-cog"></i>
													</button>
													<?php endif; ?>
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
				<?php endif; ?>

				<!-- Ingredients Stock Tab -->
				<?php if ($hasIngredients): ?>
				<div class="tab-pane fade <?php echo !$hasMenu || !$hasMenuStock ? 'show active' : ''; ?>" id="ingredients" role="tabpanel">
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0"><i class="fas fa-carrot me-2"></i>Stok Bahan Baku</h5>
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
											<th style="width:140px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($ingredient = mysqli_fetch_assoc($ingredientsStock)): 
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
											<td>
												<div class="btn-group btn-group-sm">
													<form method="POST" class="d-inline" onsubmit="return confirm('Tambah stok +1?')">
														<input type="hidden" name="action" value="adjust_ingredient_stock">
														<input type="hidden" name="id" value="<?php echo $ingredient['id']; ?>">
														<input type="hidden" name="adjustment" value="1">
														<button class="btn btn-outline-success btn-sm" title="Tambah Stok"><i class="fas fa-plus"></i></button>
													</form>
													<form method="POST" class="d-inline" onsubmit="return confirm('Kurangi stok -1?')">
														<input type="hidden" name="action" value="adjust_ingredient_stock">
														<input type="hidden" name="id" value="<?php echo $ingredient['id']; ?>">
														<input type="hidden" name="adjustment" value="-1">
														<button class="btn btn-outline-danger btn-sm" title="Kurangi Stok"><i class="fas fa-minus"></i></button>
													</form>
													<button class="btn btn-outline-primary btn-sm" onclick="openMinStockModal(<?php echo $ingredient['id']; ?>, <?php echo $ingredient['min_stok']; ?>, 'ingredient')" title="Ubah Min. Stok">
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
				<?php endif; ?>
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
						<input type="hidden" name="type" id="min-stock-type">
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
		function openMinStockModal(id, currentMinStock, type) {
			document.getElementById('min-stock-id').value = id;
			document.getElementById('min-stock-value').value = currentMinStock;
			document.getElementById('min-stock-type').value = type;
			var modal = new bootstrap.Modal(document.getElementById('minStockModal'));
			modal.show();
		}
	</script>
</body>
</html>
