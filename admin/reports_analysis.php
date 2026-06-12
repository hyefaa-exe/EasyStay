<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
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

// 3. RINGKASAN STATISTIK
$stats_sql = "SELECT 
                COALESCE(SUM(total_price), 0) as grand_total, 
                COUNT(book_id) as total_bookings
              FROM bookings WHERE status = 'Accepted'";
              
$stats_result = $conn->query($stats_sql)->fetch_assoc();

$grand_revenue = $stats_result['grand_total']; 
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
    <title>Reports & Analysis | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
    
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 25px; border-radius: 20px; box-shadow: var(--card-shadow); border: 1px solid rgba(197, 168, 128, 0.08); }
        .stat-card h3 { color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 10px; font-weight: 700; }
        .stat-card p { font-size: 1.8rem; font-weight: 800; color: var(--easy-charcoal); margin-bottom: 5px; }
        .stat-card span { color: #27ae60; font-size: 0.85rem; font-weight: 600; }

        .charts-main { background: var(--white); padding: 30px; border-radius: 25px; box-shadow: var(--card-shadow); border: 1px solid rgba(0,0,0,0.02); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .chart-container { position: relative; height: 400px; width: 100%; }
        
        .tab-btn { 
            padding: 8px 20px; border-radius: 10px; border: 1.5px solid #eee; 
            cursor: pointer; font-weight: 700; background: white; transition: 0.3s;
        }
        .tab-btn.active { background: var(--easy-gold); color: white; border-color: var(--easy-gold); }

        @media print {
            .header, .nav-actions, .tab-btn { display: none; }
            .stat-card { border: 1px solid #eee; box-shadow: none; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php" style="text-decoration: none;">
                <h1><span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span></h1>
            </a>
            <span class="brand-sub">Reports Portal</span>
        </div>
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_bookings.php" class="nav-btn btn-profile"><i class="fas fa-calendar-check"></i> Bookings</a>
            <button onclick="window.print()" class="nav-btn btn-logout"><i class="fas fa-file-pdf"></i> Print Report</button>
        </div>
    </header>

    <main class="container">
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--easy-charcoal); letter-spacing: -1px;">Sales Analytics</h2>
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
                <span style="color: var(--easy-gold);">Confirmed reservations</span>
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
                    <button class="tab-btn active" onclick="updateChart(event, 'monthly')">Monthly (<?= date('Y') ?>)</button>
                    <button class="tab-btn" onclick="updateChart(event, 'weekly')">Weekly Trend</button>
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
                    borderColor: '#C5A880',
                    backgroundColor: 'rgba(197, 168, 128, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#C5A880',
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

        function updateChart(e, type) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');

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