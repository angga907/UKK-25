<?php
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pembayaran Berhasil</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container py-5">
		<div class="card shadow-sm p-4 mx-auto" style="max-width:600px;">
			<h3 class="text-success">Pembayaran Berhasil</h3>
			<p>Order ID: <strong>#<?php echo $orderId; ?></strong></p>
			<p>Status akhir akan dipastikan melalui notifikasi server (webhook). Silakan lanjut menyiapkan pesanan.</p>
			<a class="btn btn-primary" href="index.php">Kembali ke Kasir</a>
		</div>
	</div>
</body>
</html>
