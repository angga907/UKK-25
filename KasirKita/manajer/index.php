<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manajer') {
    header("Location: ../login.php?role=manajer");
    exit;
}

include "../koneksi.php";

// Get statistics data
$today = date('Y-m-d');
$month = date('Y-m');

// Check if orders table exists
$check_orders_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'orders'");
$orders_table_exists = mysqli_num_rows($check_orders_table) > 0;

if ($orders_table_exists) {
    // Total sales today
    $query_today = mysqli_query($koneksi, "SELECT SUM(total) as total_today FROM orders WHERE DATE(created_at) = '$today'");
    $data_today = mysqli_fetch_assoc($query_today);
    $total_today = $data_today['total_today'] ?? 0;

    // Total sales this month
    $query_month = mysqli_query($koneksi, "SELECT SUM(total) as total_month FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $data_month = mysqli_fetch_assoc($query_month);
    $total_month = $data_month['total_month'] ?? 0;

    // Total orders today
    $query_orders_today = mysqli_query($koneksi, "SELECT COUNT(*) as orders_today FROM orders WHERE DATE(created_at) = '$today'");
    $data_orders_today = mysqli_fetch_assoc($query_orders_today);
    $orders_today = $data_orders_today['orders_today'] ?? 0;

    // Recent orders
    $query_recent = mysqli_query($koneksi, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
} else {
    // Default values if orders table doesn't exist
    $total_today = 0;
    $total_month = 0;
    $orders_today = 0;
    $query_recent = false;
}

// Total employees
$query_employees = mysqli_query($koneksi, "SELECT COUNT(*) as total_employees FROM users WHERE role IN ('kasir', 'kitchen')");
$data_employees = mysqli_fetch_assoc($query_employees);
$total_employees = $data_employees['total_employees'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manajer - KasirKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h3 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #fff;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: #fff;
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: #f8f9fa;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding: 20px 0;
        }

        .welcome-section h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .welcome-section p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .current-time {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 12px 16px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            min-width: 200px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196F3, #1976D2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .user-details h6 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .user-details small {
            font-size: 0.8rem;
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        .user-status small {
            font-size: 0.75rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online {
            background: #4CAF50;
        }

        .profile-dropdown .dropdown-toggle {
            color: #666;
            text-decoration: none;
            padding: 4px;
            border: none;
            background: none;
            font-size: 0.8rem;
        }

        .profile-dropdown .dropdown-toggle:hover {
            color: #2196F3;
        }

        .profile-dropdown .dropdown-toggle:focus {
            box-shadow: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #2196F3;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.sales {
            border-left-color: #4CAF50;
        }

        .stat-card.orders {
            border-left-color: #FF9800;
        }

        .stat-card.employees {
            border-left-color: #9C27B0;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.sales {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
        }

        .stat-icon.orders {
            background: linear-gradient(135deg, #FF9800, #FFB74D);
        }

        .stat-icon.employees {
            background: linear-gradient(135deg, #9C27B0, #BA68C8);
        }

        .stat-icon.monthly {
            background: linear-gradient(135deg, #2196F3, #42A5F5);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .chart-controls {
            display: flex;
            gap: 10px;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .orders-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px 10px;
            margin: 0 -10px;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info h6 {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .order-time {
            color: #666;
            font-size: 0.9rem;
            margin: 0 0 5px 0;
        }

        .order-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-dimasak {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-selesai {
            background: #d4edda;
            color: #155724;
        }

        .status-dibayar {
            background: #cce5ff;
            color: #004085;
        }

        .order-details {
            text-align: right;
        }

        .order-amount {
            font-weight: 600;
            color: #4CAF50;
            margin-bottom: 3px;
        }

        .order-table {
            font-size: 0.8rem;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state p {
            margin: 10px 0 5px 0;
            font-weight: 500;
        }

        .empty-state small {
            color: #999;
        }

        .recent-orders {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .order-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info h6 {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .order-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .order-amount {
            font-weight: 600;
            color: #4CAF50;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .action-icon.reports {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
        }

        .action-icon.employees {
            background: linear-gradient(135deg, #2196F3, #42A5F5);
        }

        .action-icon.menu {
            background: linear-gradient(135deg, #FF9800, #FFB74D);
        }

        .action-icon.settings {
            background: linear-gradient(135deg, #9C27B0, #BA68C8);
        }

        .action-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .action-desc {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            padding: 20px;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-chart-line me-2"></i>KasirKita</h3>
                <p>Manager Dashboard</p>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                <a href="index.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        Dashboard
                </a>
                </div>
                <div class="nav-item">
                <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        Laporan Penjualan
                </a>
                </div>
                <div class="nav-item">
                <a href="pegawai.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Data Pegawai
                </a>
                </div>
                <div class="nav-item">
                <a href="menu.php" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        Data Menu
                    </a>
                </div>
                <div class="nav-item">
                    <a href="pengaturan.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </div>
                <div class="nav-item">
                <a href="../logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                </a>
                </div>
            </div>
    </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h1>Selamat Datang, <?php echo $_SESSION['nama']; ?>! 👋</h1>
                    <p>Dashboard Manager - Kelola dan pantau operasional restoran Anda</p>
                    <div class="current-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <h6 class="mb-0"><?php echo $_SESSION['nama']; ?></h6>
                            <small class="text-muted">Manager</small>
                            <div class="user-status">
                                <span class="status-dot online"></span>
                                <small>Online</small>
                        </div>
                    </div>
                </div>
                    <div class="profile-dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                        </div>
                    </div>
                </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card sales">
                    <div class="stat-header">
                        <div class="stat-icon sales">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">Rp <?php echo number_format($total_today, 0, ',', '.'); ?></div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                </div>

                <div class="stat-card orders">
                    <div class="stat-header">
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $orders_today; ?></div>
                    <div class="stat-label">Pesanan Hari Ini</div>
                </div>

                <div class="stat-card employees">
                    <div class="stat-header">
                        <div class="stat-icon employees">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-label">Total Pegawai</div>
                </div>

                <div class="stat-card monthly">
                    <div class="stat-header">
                        <div class="stat-icon monthly">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">Rp <?php echo number_format($total_month, 0, ',', '.'); ?></div>
                    <div class="stat-label">Pendapatan Bulan Ini</div>
                </div>
            </div>

            <!-- Charts and Recent Orders -->
            <div class="content-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5 class="chart-title">Grafik Penjualan Mingguan</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-primary" onclick="updateChart('week')">Minggu Ini</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateChart('month')">Bulan Ini</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <div class="recent-orders">
                    <div class="chart-header">
                        <h5 class="chart-title">Pesanan Terbaru</h5>
                        <a href="laporan.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="orders-list">
                        <?php if($query_recent && mysqli_num_rows($query_recent) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($query_recent)): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <h6>Order #<?php echo $order['id']; ?></h6>
                                        <p class="order-time"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'Menunggu',
                                                'dimasak' => 'Dimasak',
                                                'selesai' => 'Selesai',
                                                'dibayar' => 'Dibayar'
                                            ];
                                            echo $status_labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </div>
                                    <div class="order-details">
                                        <div class="order-amount">
                                            Rp <?php echo number_format($order['total'], 0, ',', '.'); ?>
                                        </div>
                                        <div class="order-table">
                                            Meja <?php echo $order['nomor_meja']; ?>
                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-cart"></i>
                                <p>Belum ada pesanan</p>
                                <small>Pesanan akan muncul di sini</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="laporan.php" class="action-card">
                    <div class="action-icon reports">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-title">Laporan Penjualan</div>
                    <div class="action-desc">Analisis dan laporan detail penjualan</div>
                </a>

                <a href="pegawai.php" class="action-card">
                    <div class="action-icon employees">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="action-title">Kelola Pegawai</div>
                    <div class="action-desc">Tambah, edit, dan kelola data pegawai</div>
                </a>

                <a href="menu.php" class="action-card">
                    <div class="action-icon menu">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="action-title">Kelola Menu</div>
                    <div class="action-desc">Tambah, edit, dan kelola menu restoran</div>
                </a>

                <a href="pengaturan.php" class="action-card">
                    <div class="action-icon settings">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-title">Pengaturan</div>
                    <div class="action-desc">Konfigurasi sistem dan preferensi</div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Current time display
        function updateCurrentTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('current-time').textContent = now.toLocaleDateString('id-ID', options);
        }
        
        // Update time every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Chart data
        let salesChart;
        const weekData = {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [1200000, 1500000, 1800000, 1400000, 2000000, 2500000, 2200000],
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        };

        const monthData = {
            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [8500000, 9200000, 7800000, 9500000],
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        };

        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(ctx, {
                type: 'line',
                data: weekData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update chart function
        function updateChart(period) {
            if (salesChart) {
                salesChart.destroy();
            }
            
            const ctx = document.getElementById('salesChart').getContext('2d');
            const data = period === 'month' ? monthData : weekData;
            
            salesChart = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize chart on page load
        initChart();
    </script>
</body>
</html>