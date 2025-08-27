<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'kitchen'){
	// header("Location: ../login.php?role=kitchen");
	// exit();
}
include "../koneksi.php";

// Handle status updates (POST)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['to'])){
	$id = (int)($_POST['id'] ?? 0);
	$to = $_POST['to'] ?? '';
	$allowed = ['dimasak','selesai'];
	if($id>0 && in_array($to,$allowed)){
		mysqli_query($koneksi, "UPDATE orders SET status='$to' WHERE id=$id");
	}
	header('Location: index.php');
	exit;
}

// Detect optional columns on orders
$hasCustomer = false;
$cols = @mysqli_query($koneksi, "SHOW COLUMNS FROM orders LIKE 'customer_name'");
if ($cols && mysqli_num_rows($cols) > 0) { $hasCustomer = true; }

// Filter status
$filter = $_GET['status'] ?? 'active'; // active = pending + dimasak
$where = "status IN ('pending','dimasak')";
if ($filter === 'pending') $where = "status='pending'";
if ($filter === 'dimasak') $where = "status='dimasak'";
if ($filter === 'selesai') $where = "status='selesai'";

$select = "id, nomor_meja, total, status, created_at" . ($hasCustomer ? ", customer_name" : "");
$orders = mysqli_query($koneksi, "SELECT $select FROM orders WHERE $where ORDER BY created_at ASC");

// Detect order_items table
$hasItems = false;
$itCheck = @mysqli_query($koneksi, "SHOW TABLES LIKE 'order_items'");
if ($itCheck && mysqli_num_rows($itCheck) > 0) { $hasItems = true; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kitchen - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container py-4">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h3 class="mb-0"><i class="fas fa-utensils me-2"></i>Kitchen</h3>
			<div class="d-flex gap-2">
				<button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">Refresh</button>
				<a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
			</div>
		</div>

		<ul class="nav nav-pills mb-3">
			<li class="nav-item"><a class="nav-link <?php echo $filter==='active'?'active':''; ?>" href="?status=active">Aktif</a></li>
			<li class="nav-item"><a class="nav-link <?php echo $filter==='pending'?'active':''; ?>" href="?status=pending">Pending</a></li>
			<li class="nav-item"><a class="nav-link <?php echo $filter==='dimasak'?'active':''; ?>" href="?status=dimasak">Dimasak</a></li>
			<li class="nav-item"><a class="nav-link <?php echo $filter==='selesai'?'active':''; ?>" href="?status=selesai">Selesai</a></li>
		</ul>

		<div class="card shadow-sm">
			<div class="card-header">Daftar Pesanan</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table align-middle">
						<thead>
							<tr>
								<th>ID</th>
								<th>Meja</th>
								<th>Pelanggan</th>
								<th>Total</th>
								<th>Status</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if(mysqli_num_rows($orders)>0): while($o=mysqli_fetch_assoc($orders)): ?>
							<tr>
								<td>#<?php echo $o['id']; ?></td>
								<td>Meja <?php echo (int)$o['nomor_meja']; ?></td>
								<td><?php echo $hasCustomer ? htmlspecialchars($o['customer_name'] ?? '') : ''; ?></td>
								<td><strong>Rp <?php echo number_format($o['total'],0,',','.'); ?></strong></td>
								<td><span class="badge bg-<?php echo $o['status']==='pending' ? 'warning' : ($o['status']==='dimasak'?'info':'success'); ?> text-dark"><?php echo ucfirst($o['status']); ?></span></td>
								<td>
									<?php if($o['status']==='pending'): ?>
									<form method="POST" class="d-inline">
										<input type="hidden" name="id" value="<?php echo $o['id']; ?>">
										<input type="hidden" name="to" value="dimasak">
										<button class="btn btn-sm btn-outline-primary">Mulai Masak</button>
									</form>
									<?php elseif($o['status']==='dimasak'): ?>
									<form method="POST" class="d-inline">
										<input type="hidden" name="id" value="<?php echo $o['id']; ?>">
										<input type="hidden" name="to" value="selesai">
										<button class="btn btn-sm btn-success">Selesai</button>
									</form>
									<?php endif; ?>
									<?php if($hasItems): ?>
									<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#items-<?php echo $o['id']; ?>">Detail</button>
									<?php endif; ?>
								</td>
							</tr>
							<?php if($hasItems): ?>
							<tr class="collapse" id="items-<?php echo $o['id']; ?>">
								<td colspan="6">
									<?php 
									$its = mysqli_query($koneksi, "SELECT name, qty, price FROM order_items WHERE order_id=".$o['id']);
									if(mysqli_num_rows($its)>0): ?>
									<table class="table table-sm mb-0">
										<thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr></thead>
										<tbody>
										<?php $sub=0; while($i=mysqli_fetch_assoc($its)): $s=$i['qty']*$i['price']; $sub+=$s; ?>
										<tr><td><?php echo htmlspecialchars($i['name']); ?></td><td class="text-center"><?php echo (int)$i['qty']; ?></td><td class="text-end">Rp <?php echo number_format($s,0,',','.'); ?></td></tr>
										<?php endwhile; ?>
										<tr><td colspan="2" class="text-end"><strong>Total Item</strong></td><td class="text-end"><strong>Rp <?php echo number_format($sub,0,',','.'); ?></strong></td></tr>
										</tbody>
									</table>
									<?php else: ?>
									<small class="text-muted">Tidak ada detail item.</small>
									<?php endif; ?>
								</td>
							</tr>
							<?php endif; ?>
							<?php endwhile; else: ?>
							<tr><td colspan="6" class="text-center text-muted">Belum ada pesanan.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		setInterval(()=>{ location.reload(); }, 10000);
	</script>
</body>
</html>
