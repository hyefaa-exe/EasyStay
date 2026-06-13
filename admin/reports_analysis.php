<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

// Monthly Sales
$months_data = array_fill(1, 12, 0);
$monthly_result = $conn->query("SELECT MONTH(checkin_date) as month, SUM(total_price) as total FROM bookings WHERE status='Accepted' AND YEAR(checkin_date)=YEAR(CURRENT_DATE) GROUP BY MONTH(checkin_date)");
while ($row = $monthly_result->fetch_assoc()) { $months_data[$row['month']] = (float)$row['total']; }

// Weekly Sales
$weeks_label = []; $weeks_data = [];
$weekly_result = $conn->query("SELECT WEEK(checkin_date) as week, SUM(total_price) as total FROM bookings WHERE status='Accepted' AND checkin_date >= DATE_SUB(NOW(), INTERVAL 8 WEEK) GROUP BY WEEK(checkin_date) ORDER BY week ASC");
while ($row = $weekly_result->fetch_assoc()) { $weeks_label[] = "Week ".$row['week']; $weeks_data[] = (float)$row['total']; }

// Summary Stats
$stats = $conn->query("SELECT COALESCE(SUM(total_price),0) as grand_total, COUNT(book_id) as total_bookings FROM bookings WHERE status='Accepted'")->fetch_assoc();
$grand_revenue  = $stats['grand_total'];
$total_bookings = $stats['total_bookings'];
$avg_revenue    = $total_bookings > 0 ? $grand_revenue / $total_bookings : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analysis | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .tab-btn { padding:7px 16px; border-radius:8px; border:1px solid var(--slate-200); cursor:pointer; font-weight:700; font-size:0.8rem; background:var(--white); transition:0.2s; font-family:inherit; color:var(--slate-600); }
        .tab-btn.active { background:var(--gold); color:var(--white); border-color:var(--gold); }
        @media print { .sidebar, .topbar, .tab-btn { display:none!important; } .admin-wrapper { margin-left:0; } .admin-content { padding:20px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Sales Analytics</div>
            <div class="topbar-breadcrumb">Business performance overview — <?= date('Y') ?></div>
        </div>
        <div class="topbar-right">
            <button onclick="window.print()" class="btn btn-outline btn-sm">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="admin-content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Revenue (All Time)</p>
                    <h3>RM <?= number_format($grand_revenue, 0) ?></h3>
                    <span class="stat-sub"><i class="fas fa-wallet"></i> Total Collected</span>
                </div>
                <div class="stat-icon icon-gold"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Accepted Bookings</p>
                    <h3><?= $total_bookings ?></h3>
                    <span class="stat-sub" style="color:var(--info);"><i class="fas fa-calendar-check"></i> Confirmed</span>
                </div>
                <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Avg. Revenue / Booking</p>
                    <h3>RM <?= number_format($avg_revenue, 0) ?></h3>
                    <span class="stat-sub" style="color:var(--slate-400);"><i class="fas fa-chart-bar"></i> Overall Average</span>
                </div>
                <div class="stat-icon icon-green"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Revenue Trend</h3>
                <div style="display:flex; gap:6px;">
                    <button class="tab-btn active" onclick="updateChart(event, 'monthly')">Monthly (<?= date('Y') ?>)</button>
                    <button class="tab-btn" onclick="updateChart(event, 'weekly')">Weekly Trend</button>
                </div>
            </div>
            <div class="card-body">
                <div style="position:relative; height:380px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const monthlyLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const monthlyData   = <?= json_encode(array_values($months_data)) ?>;
    const weeklyLabels  = <?= json_encode($weeks_label) ?>;
    const weeklyData    = <?= json_encode($weeks_data) ?>;

    let salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Revenue (RM)',
                data: monthlyData,
                borderColor: '#C5A880',
                backgroundColor: 'rgba(197,168,128,0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
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
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'RM '+v } },
                x: { grid: { display: false } }
            }
        }
    });

    function updateChart(e, type) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        salesChart.data.labels  = type === 'monthly' ? monthlyLabels : weeklyLabels;
        salesChart.data.datasets[0].data = type === 'monthly' ? monthlyData : weeklyData;
        salesChart.update();
    }
</script>
</body>
</html>