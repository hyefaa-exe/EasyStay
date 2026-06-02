<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginform.html");
    exit();
}
$user_id = $_SESSION['admin_id'];

// 1. DATA JUALAN BULANAN (GRAF)
$monthly_sales_sql = "SELECT MONTH(checkin_date) as month, SUM(total_price) as total 
                      FROM bookings 
                      WHERE status = 'Accepted' AND YEAR(checkin_date) = YEAR(CURRENT_DATE)
                      GROUP BY MONTH(checkin_date)";
$monthly_result = $conn->query($monthly_sales_sql);

$months_data = array_fill(1, 12, 0);
while($row = $monthly_result->fetch_assoc()) {
    $months_data[$row['month']] = $row['total'];
}

// 2. DATA JUALAN MINGGUAN (GRAF)
$weekly_sales_sql = "SELECT WEEK(checkin_date) as week, SUM(total_price) as total 
                     FROM bookings 
                     WHERE status = 'Accepted' 
                     AND checkin_date >= DATE_SUB(NOW(), INTERVAL 8 WEEK) 
                     GROUP BY WEEK(checkin_date)
                     ORDER BY week ASC";
$weekly_result = $conn->query($weekly_sales_sql);

$weeks_label = [];
$weeks_data = [];
while($row = $weekly_result->fetch_assoc()) {
    $weeks_label[] = "Week " . $row['week'];
    $weeks_data[] = $row['total'];
}

// 3. RINGKASAN STATISTIK (DIPERBAIKI)
// Ubah: Kira 'grand_total' (Semua masa) bukannya 'monthly_total' sahaja
$stats_sql = "SELECT 
                COALESCE(SUM(total_price), 0) as grand_total, 
                COUNT(book_id) as total_bookings
              FROM bookings WHERE status = 'Accepted'";
              
$stats_result = $conn->query($stats_sql)->fetch_assoc();

$grand_revenue = $stats_result['grand_total']; // Duit masuk semua masa
$total_bookings = $stats_result['total_bookings'];

// Kira purata
$avg_revenue = 0;
if ($total_bookings > 0) {
    $avg_revenue = $grand_revenue / $total_bookings;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analysis | UluGarden</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --ulu-orange: #FF7F32;
            --garden-black: #1A1A1A;
            --soft-orange-bg: #FFF5E9;
            --white: #ffffff;
            --text-main: #2D3E4E;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--soft-orange-bg); color: var(--text-main); }

        .header {
            background: var(--white); padding: 15px 50px; display: flex;
            justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(255, 127, 50, 0.08); position: sticky; top: 0; z-index: 1000;
        }
        .logo-area h1 { font-size: 1.7rem; font-weight: 800; }
        .logo-ulu { color: var(--ulu-orange); }
        .logo-garden { color: var(--garden-black); }
        
        .nav-actions { display: flex; gap: 15px; }
        .nav-btn {
            text-decoration: none; padding: 10px 22px; border-radius: 12px;
            font-weight: 600; font-size: 0.85rem; transition: 0.3s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-profile { background: transparent; color: var(--ulu-orange); border: 1.5px solid var(--ulu-orange); }
        .btn-logout { background: var(--ulu-orange); color: white; border: 1.5px solid var(--ulu-orange); }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(255,127,50,0.1); }
        .stat-card h3 { color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 10px; font-weight: 700; }
        .stat-card p { font-size: 1.8rem; font-weight: 800; color: var(--garden-black); margin-bottom: 5px; }
        .stat-card span { color: var(--success); font-size: 0.85rem; font-weight: 600; }

        .charts-main { background: var(--white); padding: 30px; border-radius: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .chart-container { position: relative; height: 400px; width: 100%; }
        
        .tab-btn { 
            padding: 8px 20px; border-radius: 10px; border: 1.5px solid #eee; 
            cursor: pointer; font-weight: 700; background: white; transition: 0.3s;
        }
        .tab-btn.active { background: var(--ulu-orange); color: white; border-color: var(--ulu-orange); }

        @media print {
            .header, .nav-actions, .tab-btn { display: none; }
            body { background: white; }
            .stat-card { border: 1px solid #eee; box-shadow: none; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo-area">
            <h1><span class="logo-ulu">Ulu</span><span class="logo-garden">Garden</span></h1>
            <span style="font-size: 0.7rem; color: #888; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">Reports Portal</span>
        </div>
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_bookings.php" class="nav-btn btn-profile"><i class="fas fa-calendar-check"></i> Bookings</a>
            <button onclick="window.print()" class="nav-btn btn-logout"><i class="fas fa-file-pdf"></i> Print Report</button>
        </div>
    </header>

    <main class="container">
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--garden-black); letter-spacing: -1px;">Sales Analytics</h2>
            <p style="color: #777;">Performance overview.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Revenue (All Time)</h3>
                <p>RM <?= number_format($grand_revenue, 2) ?></p>
                <span style="color: #27ae60;"><i class="fas fa-wallet"></i> Total Collected</span>
            </div>
            <div class="stat-card">
                <h3>Total Accepted Bookings</h3>
                <p><?= $total_bookings ?></p>
                <span style="color: var(--ulu-orange);">Confirmed reservations</span>
            </div>
            <div class="stat-card">
                <h3>Avg. Revenue / Booking</h3>
                <p>RM <?= number_format($avg_revenue, 2) ?></p>
                <span style="color: #888;">Overall Average</span>
            </div>
        </div>

        <div class="charts-main">
            <div class="chart-header">
                <h3 style="font-weight: 800; font-size: 1.2rem;">Revenue Trend</h3>
                <div style="display: flex; gap: 8px;">
                    <button class="tab-btn active" onclick="updateChart('monthly')">Monthly (<?= date('Y') ?>)</button>
                    <button class="tab-btn" onclick="updateChart('weekly')">Weekly Trend</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        const monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthlyData = <?= json_encode(array_values($months_data)) ?>;
        
        const weeklyLabels = <?= json_encode($weeks_label) ?>;
        const weeklyData = <?= json_encode($weeks_data) ?>;

        let salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Revenue (RM)',
                    data: monthlyData,
                    borderColor: '#FF7F32',
                    backgroundColor: 'rgba(255, 127, 50, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#FF7F32',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f5f5f5' },
                        ticks: { callback: value => 'RM ' + value }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        function updateChart(type) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            if (type === 'monthly') {
                salesChart.data.labels = monthlyLabels;
                salesChart.data.datasets[0].data = monthlyData;
            } else {
                salesChart.data.labels = weeklyLabels;
                salesChart.data.datasets[0].data = weeklyData;
            }
            salesChart.update();
        }
    </script>
</body>
</html>