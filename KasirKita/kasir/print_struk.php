<?php
include "../koneksi.php";
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) { die('Order tidak ditemukan'); }

$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM orders WHERE id=$orderId"));
if (!$order) { die('Order tidak ditemukan'); }

$hasItems = false;
$items = [];
$check = @mysqli_query($koneksi, "SHOW TABLES LIKE 'order_items'");
if ($check && mysqli_num_rows($check) > 0) {
	$hasItems = true;
	$items = mysqli_query($koneksi, "SELECT * FROM order_items WHERE order_id=$orderId");
}

function rupiah($n){ return 'Rp ' . number_format((int)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Struk #<?php echo $orderId; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		@media print {
			.no-print { display: none; }
			body { margin: 0; }
		}
		.receipt { max-width: 420px; margin: 0 auto; }
		hr { margin: .5rem 0; }
	</style>
</head>
<body>
	<div class="receipt p-3">
		<div class="text-center">
			<h5 class="mb-0">KasirKita</h5>
			<small class="text-muted">Struk Pembayaran</small>
		</div>
		<hr>
		<div class="d-flex justify-content-between">
			<div>
				<div>No. Order: #<?php echo $orderId; ?></div>
				<div>Tanggal: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
			</div>
			<div class="text-end">
				<div>Meja: <?php echo (int)$order['nomor_meja']; ?></div>
				<div>Nama: <?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></div>
			</div>
		</div>
		<hr>
		<?php if ($hasItems && mysqli_num_rows($items)>0): ?>
		<table class="table table-sm">
			<thead>
				<tr>
					<th>Item</th>
					<th class="text-center">Qty</th>
					<th class="text-end">Subtotal</th>
				</tr>
			</thead>
			<tbody>
				<?php $total=0; while($it = mysqli_fetch_assoc($items)): $sub = $it['price']*$it['qty']; $total+=$sub; ?>
				<tr>
					<td><?php echo htmlspecialchars($it['name']); ?><br><small class="text-muted"><?php echo rupiah($it['price']); ?></small></td>
					<td class="text-center"><?php echo (int)$it['qty']; ?></td>
					<td class="text-end"><?php echo rupiah($sub); ?></td>
				</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<div class="d-flex justify-content-between">
			<strong>Total</strong>
			<strong><?php echo rupiah($order['total']); ?></strong>
		</div>
		<hr>
		<div class="text-center">
			<small>Terima kasih telah berkunjung!</small>
		</div>
		<div class="no-print mt-3 d-grid gap-2">
			<button class="btn btn-primary" onclick="window.print()">Cetak</button>
			<a class="btn btn-outline-secondary" href="index.php">Kembali ke Kasir</a>
		</div>
	</div>
</body>
</html>
