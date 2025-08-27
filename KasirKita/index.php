<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasirKita - Sistem Restoran Modern</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            color: #666;
            font-weight: 300;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .role-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .role-card:hover::before {
            transform: scaleX(1);
        }

        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--card-color);
        }

        .role-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-light));
            transition: all 0.3s ease;
        }

        .role-card:hover .role-icon {
            transform: scale(1.1);
        }

        .role-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .role-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .kasir-card {
            --card-color: #4CAF50;
            --card-color-light: #66BB6A;
        }

        .manajer-card {
            --card-color: #2196F3;
            --card-color-light: #42A5F5;
        }

        .kitchen-card {
            --card-color: #FF9800;
            --card-color-light: #FFB74D;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .role-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-utensils me-3"></i>KasirKita</h1>
            <p>Sistem Manajemen Restoran Terpadu</p>
        </div>

        <div class="role-grid">
            <a href="login.php?role=kasir" class="role-card kasir-card">
                <div class="role-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="role-title">Kasir</div>
                <div class="role-description">
                    Kelola transaksi, pembayaran, dan layanan pelanggan dengan mudah dan cepat
                </div>
            </a>

            <a href="login.php?role=manajer" class="role-card manajer-card">
                <div class="role-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="role-title">Manajer</div>
                <div class="role-description">
                    Pantau laporan, analisis bisnis, dan kelola operasional restoran
                </div>
            </a>

            <a href="login.php?role=kitchen" class="role-card kitchen-card">
                <div class="role-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="role-title">Kitchen</div>
                <div class="role-description">
                    Kelola pesanan, persiapan makanan, dan koordinasi dengan tim dapur
                </div>
            </a>
        </div>

        <div class="footer">
            <p>&copy; 2024 KasirKita. Sistem Restoran Modern</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
