<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../login.php?role=kasir");
    exit;
}

include "../koneksi.php";

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
if ($status_filter) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $status_filter) . "'";
}
if ($date_from) {
    $where_conditions[] = "DATE(created_at) >= '" . mysqli_real_escape_string($koneksi, $date_from) . "'";
}
if ($date_to) {
    $where_conditions[] = "DATE(created_at) <= '" . mysqli_real_escape_string($koneksi, $date_to) . "'";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch orders
$orders = mysqli_query($koneksi, "SELECT * FROM orders $where_clause ORDER BY created_at DESC");

// Get statistics
$stats = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'dimasak' THEN 1 ELSE 0 END) as cooking_orders,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END) as total_revenue
    FROM orders");

$stat = mysqli_fetch_assoc($stats);

function is_active($page) { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Riwayat Order - KasirKita</title>
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
		.status-pending { background:#fff3cd; color:#856404; }
		.status-dimasak { background:#cce5ff; color:#004085; }
		.status-selesai { background:#d4edda; color:#155724; }
		.stats-card { background:linear-gradient(135deg,#7b1fa2,#6a1b9a); color:#fff; }
		.filter-card { background:#fff; border:1px solid #e9ecef; }
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
					<h1 class="h4 mb-1"><i class="fas fa-history me-2"></i>Riwayat Order</h1>
					<p class="text-muted small mb-0">Lihat semua pesanan yang telah dibuat</p>
				</div>
			</div>

			<!-- Statistics Cards -->
			<div class="row mb-4">
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-shopping-cart fa-2x mb-2"></i>
							<h4><?php echo $stat['total_orders']; ?></h4>
							<p class="mb-0">Total Order</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-clock fa-2x mb-2"></i>
							<h4><?php echo $stat['pending_orders']; ?></h4>
							<p class="mb-0">Pending</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-fire fa-2x mb-2"></i>
							<h4><?php echo $stat['cooking_orders']; ?></h4>
							<p class="mb-0">Sedang Dimasak</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card stats-card">
						<div class="card-body text-center">
							<i class="fas fa-check-circle fa-2x mb-2"></i>
							<h4><?php echo $stat['completed_orders']; ?></h4>
							<p class="mb-0">Selesai</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Filter Card -->
			<div class="card filter-card mb-4">
				<div class="card-body">
					<form method="GET" class="row g-3">
						<div class="col-md-3">
							<label class="form-label">Status</label>
							<select name="status" class="form-control">
								<option value="">Semua Status</option>
								<option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
								<option value="dimasak" <?php echo $status_filter === 'dimasak' ? 'selected' : ''; ?>>Sedang Dimasak</option>
								<option value="selesai" <?php echo $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label">Dari Tanggal</label>
							<input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
						</div>
						<div class="col-md-3">
							<label class="form-label">Sampai Tanggal</label>
							<input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
						</div>
						<div class="col-md-3">
							<label class="form-label">&nbsp;</label>
							<div class="d-flex gap-2">
								<button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
								<a href="order_history.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Reset</a>
							</div>
						</div>
					</form>
				</div>
			</div>

			<!-- Orders Table -->
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover align-middle">
							<thead>
								<tr>
									<th style="width:80px;">ID</th>
									<th>Detail Order</th>
									<th>Meja</th>
									<th>Total</th>
									<th>Status</th>
									<th>Tanggal</th>
									<th style="width:100px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php while($order = mysqli_fetch_assoc($orders)): ?>
								<tr>
									<td><strong>#<?php echo $order['id']; ?></strong></td>
									<td>
										<div>
											<strong>Order #<?php echo $order['id']; ?></strong>
											<?php if (!empty($order['customer_name'])): ?>
											<br><small class="text-muted"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($order['customer_name']); ?></small>
											<?php endif; ?>
											<br><small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
										</div>
									</td>
									<td>
										<span class="badge bg-info">Meja <?php echo $order['nomor_meja']; ?></span>
									</td>
									<td><strong>Rp <?php echo number_format($order['total_harga'],0,',','.'); ?></strong></td>
									<td>
										<span class="status-badge status-<?php echo $order['status']; ?>">
											<?php 
											$status_text = [
												'pending' => 'Pending',
												'dimasak' => 'Sedang Dimasak',
												'selesai' => 'Selesai'
											];
											echo $status_text[$order['status']] ?? ucfirst($order['status']);
											?>
										</span>
									</td>
									<td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small></td>
									<td>
										<a href="../kasir/print_struk.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
											<i class="fas fa-print"></i>
										</a>
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

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
