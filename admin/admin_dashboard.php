<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
} else {
    $user_id  = $_SESSION['admin_id'];
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
}

require_once 'db_connect.php';

// A. Total Revenue (Accepted)
$sqlSales    = "SELECT SUM(total_price) as total FROM bookings WHERE status = 'Accepted'";
$totalSales  = $conn->query($sqlSales)->fetch_assoc()['total'] ?? 0;

// B. Total Bookings
$sqlBookings  = "SELECT COUNT(*) as total FROM bookings WHERE status != 'Cancelled'";
$totalBookings = $conn->query($sqlBookings)->fetch_assoc()['total'] ?? 0;

// C. Pending
$sqlPending   = "SELECT COUNT(*) as total FROM bookings WHERE status = 'Pending'";
$totalPending = $conn->query($sqlPending)->fetch_assoc()['total'] ?? 0;

// Monthly Revenue Chart
$salesData = array_fill(0, 12, 0);
$sqlGraph  = "SELECT MONTH(checkin_date) as m, SUM(total_price) as total 
              FROM bookings 
              WHERE status='Accepted' AND YEAR(checkin_date) = YEAR(CURDATE()) 
              GROUP BY MONTH(checkin_date)";
$resGraph  = $conn->query($sqlGraph);
while ($row = $resGraph->fetch_assoc()) {
    $salesData[$row['m'] - 1] = (float)$row['total'];
}

// Calendar events
$calendarEvents = [];
$sqlCal = "SELECT b.book_id, b.checkin_date, b.checkout_date, b.status, p.package_name, u.full_name 
           FROM bookings b 
           JOIN packages p ON b.package_id = p.package_id 
           JOIN users u ON b.user_id = u.user_id
           WHERE b.status != 'Cancelled'";
$resCal = $conn->query($sqlCal);
while ($row = $resCal->fetch_assoc()) {
    $color = '#C5A880';
    if ($row['status'] == 'Pending')  $color = '#F59E0B';
    if ($row['status'] == 'Rejected') $color = '#EF4444';
    $calendarEvents[] = [
        'title' => $row['package_name'] . ' - ' . $row['full_name'],
        'start' => $row['checkin_date'],
        'end'   => date('Y-m-d', strtotime($row['checkout_date'] . ' +1 day')),
        'color' => $color,
        'url'   => 'edit_booking.php?id=' . $row['book_id']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-breadcrumb">Welcome back, <?= htmlspecialchars($admin_name) ?></div>
        </div>
        <div class="topbar-right">
            <a href="edit_profile.php" class="btn btn-outline btn-sm">
                <i class="fas fa-user-cog"></i> Profile
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="admin-content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Revenue</p>
                    <h3>RM <?= number_format($totalSales, 0) ?></h3>
                    <span class="stat-sub"><i class="fas fa-arrow-up"></i> Accepted bookings</span>
                </div>
                <div class="stat-icon icon-gold"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Bookings</p>
                    <h3><?= $totalBookings ?></h3>
                    <span class="stat-sub" style="color:var(--info);"><i class="fas fa-calendar"></i> All time</span>
                </div>
                <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <p>Pending Actions</p>
                    <h3><?= $totalPending ?></h3>
                    <span class="stat-sub" style="color:var(--warning);"><i class="fas fa-clock"></i> Need review</span>
                </div>
                <div class="stat-icon icon-orange"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <div class="dashboard-card">
                <div class="dashboard-card-title">
                    <i class="fas fa-chart-bar"></i> Monthly Revenue
                </div>
                <canvas id="salesChart" height="220"></canvas>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-card-title">
                    <i class="fas fa-calendar-alt"></i> Booking Calendar
                </div>
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-title"><i class="fas fa-th-large"></i> Quick Actions</div>
        <div class="menu-grid">
            <a href="manage_packages.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-box-open"></i></div>
                <h3>Manage Packages</h3>
                <p>Add new room packages, update pricing, or change descriptions.</p>
                <span class="action-link">Go to Packages <i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="view_payments.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-receipt"></i></div>
                <h3>Payment Verification</h3>
                <p>Verify payment receipts uploaded by customers.</p>
                <span class="action-link">Check Payments <i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="manage_bookings.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-list-alt"></i></div>
                <h3>Booking Records</h3>
                <p>View complete booking history and customer details.</p>
                <span class="action-link">View Records <i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="view_gallery.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-images"></i></div>
                <h3>Gallery Settings</h3>
                <p>Update photos to showcase the best of EasyStay.</p>
                <span class="action-link">Update Gallery <i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="reports_analysis.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Detailed Reports</h3>
                <p>In-depth analysis of business performance and trends.</p>
                <span class="action-link">Open Reports <i class="fas fa-arrow-right"></i></span>
            </a>
            <a href="view_users.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-users"></i></div>
                <h3>User Management</h3>
                <p>Manage registered customers and view their profiles.</p>
                <span class="action-link">View Users <i class="fas fa-arrow-right"></i></span>
            </a>
        </div>

    </div><!-- end admin-content -->
</div><!-- end admin-wrapper -->

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Revenue (RM)',
                data: <?= json_encode($salesData) ?>,
                backgroundColor: 'rgba(197,168,128,0.8)',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            events: <?= json_encode($calendarEvents) ?>,
            headerToolbar: {
                left: 'prev,next',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            height: 340,
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });
        calendar.render();
    });
</script>
</body>
</html>