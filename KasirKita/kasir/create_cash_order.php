<?php
session_start();
header('Content-Type: application/json');

include "../koneksi.php";

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
	http_response_code(400);
	echo json_encode(['error' => 'Invalid payload']);
	exit;
}

$nomor_meja = (int)($data['table'] ?? 0);
$customer_name = mysqli_real_escape_string($koneksi, $data['customer'] ?? 'Tamu');
$paid_amount = (int)($data['paid'] ?? 0);
$items = $data['items'];

$total = 0;
foreach ($items as $item) {
	$total += (int)$item['price'] * (int)$item['qty'];
}

$created_at = date('Y-m-d H:i:s');
$status = 'pending'; // pesanan baru -> siap diolah oleh kitchen

// Detect optional columns
$hasIsPaid = false; $hasPaidAmount = false; $hasCustomerName = false; $hasStock = false;
$cols = mysqli_query($koneksi, "SHOW COLUMNS FROM orders");
while ($c = mysqli_fetch_assoc($cols)) {
	if ($c['Field'] === 'is_paid') $hasIsPaid = true;
	if ($c['Field'] === 'paid_amount') $hasPaidAmount = true;
	if ($c['Field'] === 'customer_name') $hasCustomerName = true;
}
$colsMenu = mysqli_query($koneksi, "SHOW COLUMNS FROM menu");
while ($c = mysqli_fetch_assoc($colsMenu)) {
	if ($c['Field'] === 'stok') $hasStock = true;
}

$fields = ['total','created_at','nomor_meja','status'];
$values = [$total, "'$created_at'", $nomor_meja, "'$status'"];
if ($hasIsPaid) { $fields[] = 'is_paid'; $values[] = 1; }
if ($hasPaidAmount) { $fields[] = 'paid_amount'; $values[] = $paid_amount; }
if ($hasCustomerName) { $fields[] = 'customer_name'; $values[] = "'$customer_name'"; }

mysqli_query($koneksi, "INSERT INTO orders (".implode(',',$fields).") VALUES (".implode(',',$values).")");
$order_id = mysqli_insert_id($koneksi);

$orderItemsTable = @mysqli_query($koneksi, "SHOW TABLES LIKE 'order_items'");
if ($orderItemsTable && mysqli_num_rows($orderItemsTable) > 0) {
	foreach ($items as $item) {
		$name = mysqli_real_escape_string($koneksi, $item['name']);
		$price = (int)$item['price'];
		$qty = (int)$item['qty'];
		$menu_id = (int)($item['id'] ?? 0);
		mysqli_query($koneksi, "INSERT INTO order_items (order_id, menu_id, name, price, qty) VALUES ($order_id, $menu_id, '$name', $price, $qty)");
	}
}

// Decrease stock if available
if ($hasStock) {
	foreach ($items as $item) {
		$menu_id = (int)($item['id'] ?? 0);
		$qty = (int)$item['qty'];
		if ($menu_id > 0 && $qty > 0) {
			mysqli_query($koneksi, "UPDATE menu SET stok = GREATEST(0, stok - $qty) WHERE id=$menu_id");
		}
	}
}

echo json_encode(['orderId' => $order_id, 'total' => $total]);
