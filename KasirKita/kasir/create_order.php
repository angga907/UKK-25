<?php
session_start();
header('Content-Type: application/json');

include "../koneksi.php";
$config = include __DIR__ . '/midtrans_config.php';

// Read JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
	http_response_code(400);
	echo json_encode(['error' => 'Invalid payload']);
	exit;
}

$nomor_meja = (int)($data['table'] ?? 0);
$customer_name = mysqli_real_escape_string($koneksi, $data['customer'] ?? 'Tamu');
$items = $data['items']; // [{id, name, price, qty}]

// Calculate total
$total = 0;
foreach ($items as $item) {
	$qty = (int)($item['qty'] ?? 1);
	$price = (int)($item['price'] ?? 0);
	$total += $qty * $price;
}

// Insert order to DB with status pending (to be cooked by kitchen)
$created_at = date('Y-m-d H:i:s');
$status = 'pending'; // kitchen status; payment status handled by Midtrans

// Assuming orders table at least (id, total, created_at, nomor_meja, status)
mysqli_query($koneksi, "INSERT INTO orders (total, created_at, nomor_meja, status, customer_name) VALUES ($total, '$created_at', $nomor_meja, '$status', '$customer_name')");
$order_id = mysqli_insert_id($koneksi);

// Insert order_items table if exists
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

// Prepare Midtrans Snap API request
$transaction_details = [
	'order_id' => 'ORDER-' . $order_id . '-' . time(),
	'gross_amount' => $total,
];

$item_details = [];
foreach ($items as $item) {
	$item_details[] = [
		'id' => (string)($item['id'] ?? ''),
		'price' => (int)$item['price'],
		'quantity' => (int)$item['qty'],
		'name' => substr($item['name'], 0, 50),
	];
}

$customer_details = [
	'first_name' => $customer_name,
];

$payload = [
	'transaction_details' => $transaction_details,
	'item_details' => $item_details,
	'customer_details' => $customer_details,
	'enable_payments' => ["gopay","bank_transfer","credit_card"],
	'credit_card' => ['secure' => $config['is_3ds']],
];

$serverKey = $config['server_key'];
$isProd = $config['environment'] === 'production' || $config['is_production'];
$snapUrl = $isProd ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

$ch = curl_init($snapUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Accept: application/json',
	'Authorization: Basic ' . base64_encode($serverKey . ':'),
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false || $httpCode >= 400) {
	// On failure, still return order id so cashier can retry
	echo json_encode(['orderId' => $order_id, 'error' => 'Failed to get Snap token', 'detail' => $response]);
	exit;
}
$dataSnap = json_decode($response, true);

// Return order id and snap token to front-end

echo json_encode([
	'orderId' => $order_id,
	'snapToken' => $dataSnap['token'] ?? null,
	'snapRedirectUrl' => $dataSnap['redirect_url'] ?? null,
]);
