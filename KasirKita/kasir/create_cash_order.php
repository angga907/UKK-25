<?php
session_start();
header('Content-Type: application/json');

try {
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
    $status = 'pending';

    // Detect optional columns
    $hasIsPaid = false; 
    $hasPaidAmount = false; 
    $hasCustomerName = false; 
    $hasIdUser = false;
    $hasStock = false;
    
    $cols = mysqli_query($koneksi, "SHOW COLUMNS FROM orders");
    if ($cols) {
        while ($c = mysqli_fetch_assoc($cols)) {
            if ($c['Field'] === 'is_paid') $hasIsPaid = true;
            if ($c['Field'] === 'paid_amount') $hasPaidAmount = true;
            if ($c['Field'] === 'customer_name') $hasCustomerName = true;
            if ($c['Field'] === 'id_user') $hasIdUser = true;
        }
    }
    
    $colsMenu = mysqli_query($koneksi, "SHOW COLUMNS FROM menu");
    if ($colsMenu) {
        while ($c = mysqli_fetch_assoc($colsMenu)) {
            if ($c['Field'] === 'stok') $hasStock = true;
        }
    }

    $fields = ['total', 'created_at', 'nomor_meja', 'status'];
    $values = [$total, "'$created_at'", $nomor_meja, "'$status'"];
    
    if ($hasIsPaid) { 
        $fields[] = 'is_paid'; 
        $values[] = 1; 
    }
    if ($hasPaidAmount) { 
        $fields[] = 'paid_amount'; 
        $values[] = $paid_amount; 
    }
    // Remove id_user - we don't want to use it
    // if ($hasIdUser) { 
    //     $fields[] = 'id_user'; 
    //     $values[] = $_SESSION['user_id'] ?? 1; 
    // }

    $insertQuery = "INSERT INTO orders (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
    $result = mysqli_query($koneksi, $insertQuery);
    
    if (!$result) {
        throw new Exception("Database error: " . mysqli_error($koneksi));
    }
    
    $order_id = mysqli_insert_id($koneksi);

    // Insert order items if table exists
    $orderItemsTable = @mysqli_query($koneksi, "SHOW TABLES LIKE 'order_items'");
    if ($orderItemsTable && mysqli_num_rows($orderItemsTable) > 0) {
        // Detect columns in order_items table
        $hasOrderId = false;
        $hasMenuId = false;
        $hasName = false;
        $hasPrice = false;
        $hasQty = false;
        
        $orderItemsCols = @mysqli_query($koneksi, "SHOW COLUMNS FROM order_items");
        if ($orderItemsCols) {
            while ($col = mysqli_fetch_assoc($orderItemsCols)) {
                if ($col['Field'] === 'order_id') $hasOrderId = true;
                if ($col['Field'] === 'menu_id') $hasMenuId = true;
                if ($col['Field'] === 'name') $hasName = true;
                if ($col['Field'] === 'price') $hasPrice = true;
                if ($col['Field'] === 'qty') $hasQty = true;
            }
        }
        
        foreach ($items as $item) {
            $name = mysqli_real_escape_string($koneksi, $item['name']);
            $price = (int)$item['price'];
            $qty = (int)$item['qty'];
            $menu_id = (int)($item['id'] ?? 0);
            
            // Build dynamic query based on available columns
            $itemFields = [];
            $itemValues = [];
            
            if ($hasOrderId) {
                $itemFields[] = 'order_id';
                $itemValues[] = $order_id;
            }
            if ($hasMenuId) {
                $itemFields[] = 'menu_id';
                $itemValues[] = $menu_id;
            }
            if ($hasName) {
                $itemFields[] = 'name';
                $itemValues[] = "'$name'";
            }
            if ($hasPrice) {
                $itemFields[] = 'price';
                $itemValues[] = $price;
            }
            if ($hasQty) {
                $itemFields[] = 'qty';
                $itemValues[] = $qty;
            }
            
            if (!empty($itemFields)) {
                $itemQuery = "INSERT INTO order_items (" . implode(',', $itemFields) . ") VALUES (" . implode(',', $itemValues) . ")";
                $itemResult = mysqli_query($koneksi, $itemQuery);
                if (!$itemResult) {
                    debugLog("Error inserting order item: " . mysqli_error($koneksi));
                }
            }
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

    echo json_encode([
        'success' => true,
        'orderId' => $order_id, 
        'total' => $total, 
        'paid' => $paid_amount, 
        'change' => max(0, $paid_amount - $total)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
