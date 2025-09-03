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

    if ($action === 'add_ingredient') {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan'] ?? '');
        $stok = (int)($_POST['stok'] ?? 0);
        $min_stok = (int)($_POST['min_stok'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);
        
        if ($nama) {
            mysqli_query($koneksi, "INSERT INTO ingredients (nama, satuan, stok, min_stok, harga) VALUES ('$nama', '$satuan', $stok, $min_stok, $harga)");
        }
    }

    if ($action === 'edit_ingredient') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
        $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan'] ?? '');
        $stok = (int)($_POST['stok'] ?? 0);
        $min_stok = (int)($_POST['min_stok'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);
        
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE ingredients SET nama='$nama', satuan='$satuan', stok=$stok, min_stok=$min_stok, harga=$harga WHERE id=$id");
        }
    }

    if ($action === 'delete_ingredient') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM ingredients WHERE id=$id");
        }
    }

    if ($action === 'adjust_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $adjustment = (int)($_POST['adjustment'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "UPDATE ingredients SET stok = GREATEST(0, stok + $adjustment) WHERE id=$id");
        }
    }

    if ($action === 'add_recipe') {
        $menu_id = (int)($_POST['menu_id'] ?? 0);
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        
        if ($menu_id > 0 && $ingredient_id > 0 && $quantity > 0) {
            mysqli_query($koneksi, "INSERT INTO recipes (menu_id, ingredient_id, quantity) VALUES ($menu_id, $ingredient_id, $quantity)");
        }
    }

    if ($action === 'delete_recipe') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mysqli_query($koneksi, "DELETE FROM recipes WHERE id=$id");
        }
    }

    header('Location: crud_module.php?success=1');
    exit;
}

// Fetch data
$ingredients = mysqli_query($koneksi, "SELECT * FROM ingredients ORDER BY nama");
$menus = mysqli_query($koneksi, "SELECT * FROM menu ORDER BY nama_menu");
$recipes = mysqli_query($koneksi, "SELECT r.*, m.nama_menu, i.nama as ingredient_name, i.satuan 
    FROM recipes r 
    JOIN menu m ON r.menu_id = m.id 
    JOIN ingredients i ON r.ingredient_id = i.id 
    ORDER BY m.nama_menu, i.nama");

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
		.nav-tabs .nav-link { border:none; color:#6c757d; }
		.nav-tabs .nav-link.active { color:#388e3c; border-bottom:2px solid #388e3c; }
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
					<h1 class="h4 mb-1"><i class="fas fa-database me-2"></i>CRUD Module</h1>
					<p class="text-muted small mb-0">Kelola bahan baku dan resep</p>
				</div>
			</div>

			<?php if (isset($_GET['success'])): ?>
			<div class="alert alert-success rounded-3 border-0">Perubahan tersimpan.</div>
			<?php endif; ?>

			<!-- Tabs -->
			<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="ingredients-tab" data-bs-toggle="tab" data-bs-target="#ingredients" type="button" role="tab">
						<i class="fas fa-carrot me-2"></i>Bahan Baku
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="recipes-tab" data-bs-toggle="tab" data-bs-target="#recipes" type="button" role="tab">
						<i class="fas fa-book me-2"></i>Resep
					</button>
				</li>
			</ul>

			<div class="tab-content" id="myTabContent">
				<!-- Ingredients Tab -->
				<div class="tab-pane fade show active" id="ingredients" role="tabpanel">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="mb-0"><i class="fas fa-carrot me-2"></i>Bahan Baku</h5>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
								<i class="fas fa-plus me-1"></i>Tambah Bahan
							</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead>
										<tr>
											<th>ID</th>
											<th>Nama Bahan</th>
											<th>Stok</th>
											<th>Min. Stok</th>
											<th>Satuan</th>
											<th>Harga</th>
											<th style="width:140px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($ingredient = mysqli_fetch_assoc($ingredients)): ?>
										<tr>
											<td><strong>#<?php echo $ingredient['id']; ?></strong></td>
											<td><strong><?php echo htmlspecialchars($ingredient['nama']); ?></strong></td>
											<td>
												<div class="d-flex align-items-center">
													<span class="stock-badge stock-<?php echo $ingredient['stok'] > $ingredient['min_stok'] ? 'high' : ($ingredient['stok'] > 0 ? 'medium' : 'low'); ?> me-2">
														<?php echo $ingredient['stok']; ?>
													</span>
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
													</div>
												</div>
											</td>
											<td><small class="text-muted"><?php echo $ingredient['min_stok']; ?></small></td>
											<td><small class="text-muted"><?php echo $ingredient['satuan']; ?></small></td>
											<td><strong>Rp <?php echo number_format($ingredient['harga'],0,',','.'); ?></strong></td>
											<td>
												<button class="btn btn-sm btn-outline-primary me-1" onclick="openEditIngredient(<?php echo $ingredient['id']; ?>,'<?php echo htmlspecialchars($ingredient['nama'], ENT_QUOTES); ?>','<?php echo $ingredient['satuan']; ?>',<?php echo $ingredient['stok']; ?>,<?php echo $ingredient['min_stok']; ?>,<?php echo $ingredient['harga']; ?>)">
													<i class="fas fa-edit"></i>
												</button>
												<form method="POST" class="d-inline" onsubmit="return confirm('Hapus bahan ini?')">
													<input type="hidden" name="action" value="delete_ingredient">
													<input type="hidden" name="id" value="<?php echo $ingredient['id']; ?>">
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

				<!-- Recipes Tab -->
				<div class="tab-pane fade" id="recipes" role="tabpanel">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center">
							<h5 class="mb-0"><i class="fas fa-book me-2"></i>Resep</h5>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRecipeModal">
								<i class="fas fa-plus me-1"></i>Tambah Resep
							</button>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead>
										<tr>
											<th>ID</th>
											<th>Menu</th>
											<th>Bahan Baku</th>
											<th>Jumlah</th>
											<th style="width:80px;">Aksi</th>
										</tr>
									</thead>
									<tbody>
										<?php while($recipe = mysqli_fetch_assoc($recipes)): ?>
										<tr>
											<td><strong>#<?php echo $recipe['id']; ?></strong></td>
											<td><strong><?php echo htmlspecialchars($recipe['nama_menu']); ?></strong></td>
											<td><?php echo htmlspecialchars($recipe['ingredient_name']); ?></td>
											<td><strong><?php echo $recipe['quantity']; ?> <?php echo $recipe['satuan']; ?></strong></td>
											<td>
												<form method="POST" class="d-inline" onsubmit="return confirm('Hapus resep ini?')">
													<input type="hidden" name="action" value="delete_recipe">
													<input type="hidden" name="id" value="<?php echo $recipe['id']; ?>">
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

	<!-- Add Ingredient Modal -->
	<div class="modal fade" id="addIngredientModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Bahan Baku</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_ingredient">
						<div class="mb-3">
							<label class="form-label">Nama Bahan</label>
							<input type="text" name="nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Satuan</label>
							<select name="satuan" class="form-control" required>
								<option value="">Pilih Satuan</option>
								<option value="kg">Kilogram (kg)</option>
								<option value="gram">Gram (g)</option>
								<option value="liter">Liter (L)</option>
								<option value="ml">Mililiter (ml)</option>
								<option value="pcs">Pieces (pcs)</option>
								<option value="bungkus">Bungkus</option>
							</select>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Stok Awal</label>
									<input type="number" name="stok" class="form-control" value="0" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Min. Stok</label>
									<input type="number" name="min_stok" class="form-control" value="0" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Harga per Satuan</label>
									<input type="number" name="harga" class="form-control" value="0" min="0">
								</div>
							</div>
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

	<!-- Edit Ingredient Modal -->
	<div class="modal fade" id="editIngredientModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Ubah Bahan Baku</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="edit_ingredient">
						<input type="hidden" name="id" id="edit-ingredient-id">
						<div class="mb-3">
							<label class="form-label">Nama Bahan</label>
							<input type="text" name="nama" id="edit-ingredient-nama" class="form-control" required>
						</div>
						<div class="mb-3">
							<label class="form-label">Satuan</label>
							<select name="satuan" id="edit-ingredient-satuan" class="form-control" required>
								<option value="kg">Kilogram (kg)</option>
								<option value="gram">Gram (g)</option>
								<option value="liter">Liter (L)</option>
								<option value="ml">Mililiter (ml)</option>
								<option value="pcs">Pieces (pcs)</option>
								<option value="bungkus">Bungkus</option>
							</select>
						</div>
						<div class="row">
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Stok</label>
									<input type="number" name="stok" id="edit-ingredient-stok" class="form-control" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Min. Stok</label>
									<input type="number" name="min_stok" id="edit-ingredient-min-stok" class="form-control" min="0">
								</div>
							</div>
							<div class="col-md-4">
								<div class="mb-3">
									<label class="form-label">Harga per Satuan</label>
									<input type="number" name="harga" id="edit-ingredient-harga" class="form-control" min="0">
								</div>
							</div>
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

	<!-- Add Recipe Modal -->
	<div class="modal fade" id="addRecipeModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Tambah Resep</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="action" value="add_recipe">
						<div class="mb-3">
							<label class="form-label">Menu</label>
							<select name="menu_id" class="form-control" required>
								<option value="">Pilih Menu</option>
								<?php 
								mysqli_data_seek($menus, 0);
								while($menu = mysqli_fetch_assoc($menus)): 
								?>
								<option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['nama_menu']); ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Bahan Baku</label>
							<select name="ingredient_id" class="form-control" required>
								<option value="">Pilih Bahan Baku</option>
								<?php 
								mysqli_data_seek($ingredients, 0);
								while($ingredient = mysqli_fetch_assoc($ingredients)): 
								?>
								<option value="<?php echo $ingredient['id']; ?>"><?php echo htmlspecialchars($ingredient['nama']); ?> (<?php echo $ingredient['satuan']; ?>)</option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Jumlah</label>
							<input type="number" name="quantity" class="form-control" step="0.1" required>
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
		function openEditIngredient(id, nama, satuan, stok, minStok, harga) {
			document.getElementById('edit-ingredient-id').value = id;
			document.getElementById('edit-ingredient-nama').value = nama;
			document.getElementById('edit-ingredient-satuan').value = satuan;
			document.getElementById('edit-ingredient-stok').value = stok;
			document.getElementById('edit-ingredient-min-stok').value = minStok;
			document.getElementById('edit-ingredient-harga').value = harga;
			var modal = new bootstrap.Modal(document.getElementById('editIngredientModal'));
			modal.show();
		}
	</script>
</body>
</html>
