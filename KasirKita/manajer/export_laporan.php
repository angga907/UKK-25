<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = ["DATE(created_at) BETWEEN '$start_date' AND '$end_date'"];
if ($status_filter) {
    $where_conditions[] = "status = '$status_filter'";
}
$where_clause = implode(' AND ', $where_conditions);

// Get orders data
$query_orders = mysqli_query($koneksi, "
    SELECT * FROM orders 
    WHERE $where_clause
    ORDER BY created_at DESC
");

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_penjualan_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Calculate totals
$query_totals = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total) as total_revenue,
        AVG(total) as avg_order_value
    FROM orders 
    WHERE $where_clause
");
$totals = mysqli_fetch_assoc($query_totals);
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - KasirKita</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 15px;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="7" class="header">
                <h2>LAPORAN PENJUALAN KASIRKITA</h2>
                <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
            </td>
        </tr>
        
        <tr>
            <td colspan="7" class="summary">
                <strong>Ringkasan:</strong><br>
                Total Pesanan: <?php echo number_format($totals['total_orders'] ?? 0); ?><br>
                Total Pendapatan: Rp <?php echo number_format($totals['total_revenue'] ?? 0, 0, ',', '.'); ?><br>
                Rata-rata Pesanan: Rp <?php echo number_format($totals['avg_order_value'] ?? 0, 0, ',', '.'); ?>
            </td>
        </tr>
        
        <tr>
            <th>No</th>
            <th>ID Order</th>
            <th>Tanggal</th>
            <th>Meja</th>
            <th>Status</th>
            <th>Total</th>
            <th>Keterangan</th>
        </tr>
        
        <?php 
        $no = 1;
        while($order = mysqli_fetch_assoc($query_orders)): 
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $order['id']; ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
            <td>Meja <?php echo $order['nomor_meja']; ?></td>
            <td>
                <?php 
                $status_labels = [
                    'pending' => 'Menunggu',
                    'dimasak' => 'Dimasak',
                    'selesai' => 'Selesai',
                    'dibayar' => 'Dibayar'
                ];
                echo $status_labels[$order['status']] ?? $order['status'];
                ?>
            </td>
            <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
            <td><?php echo $order['keterangan'] ?? '-'; ?></td>
        </tr>
        <?php endwhile; ?>
        
        <tr>
            <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL:</td>
            <td style="font-weight: bold;">Rp <?php echo number_format($totals['total_revenue'] ?? 0, 0, ',', '.'); ?></td>
            <td></td>
        </tr>
    </table>
    
    <br><br>
    
    <table>
        <tr>
            <td colspan="2" class="header">
                <h3>STATISTIK HARIAN</h3>
            </td>
        </tr>
        
        <tr>
            <th>Tanggal</th>
            <th>Total Pendapatan</th>
        </tr>
        
        <?php
        // Get daily statistics
        $query_daily = mysqli_query($koneksi, "
            SELECT 
                DATE(created_at) as tanggal,
                SUM(total) as total_revenue
            FROM orders 
            WHERE $where_clause
            GROUP BY DATE(created_at)
            ORDER BY tanggal DESC
        ");
        
        while($daily = mysqli_fetch_assoc($query_daily)):
        ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($daily['tanggal'])); ?></td>
            <td>Rp <?php echo number_format($daily['total_revenue'], 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
