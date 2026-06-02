<?php
session_start();
// Semak login admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
} else {
    $user_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
}

require_once 'db_connect.php'; // Pastikan path db_connect betul

// ==========================================
// 1. DATA PENGIRAAN UNTUK DASHBOARD
// ==========================================

// A. Jumlah Jualan (Status: Accepted)
$sqlSales = "SELECT SUM(total_price) as total FROM bookings WHERE status = 'Accepted'";
$resSales = $conn->query($sqlSales);
$totalSales = $resSales->fetch_assoc()['total'] ?? 0;

// B. Jumlah Tempahan (Semua kecuali Cancelled)
$sqlBookings = "SELECT COUNT(*) as total FROM bookings WHERE status != 'Cancelled'";
$resBookings = $conn->query($sqlBookings);
$totalBookings = $resBookings->fetch_assoc()['total'] ?? 0;

// C. Tempahan Pending (Perlu tindakan)
$sqlPending = "SELECT COUNT(*) as total FROM bookings WHERE status = 'Pending'";
$resPending = $conn->query($sqlPending);
$totalPending = $resPending->fetch_assoc()['total'] ?? 0;

// ==========================================
// 2. DATA UNTUK GRAF (CHART.JS)
// ==========================================

// Jualan Bulanan (Tahun Semasa)
$salesData = array_fill(0, 12, 0); // Array kosong Jan-Dec
$sqlGraph = "SELECT MONTH(checkin_date) as m, SUM(total_price) as total 
             FROM bookings 
             WHERE status='Accepted' AND YEAR(checkin_date) = YEAR(CURDATE()) 
             GROUP BY MONTH(checkin_date)";
$resGraph = $conn->query($sqlGraph);
while($row = $resGraph->fetch_assoc()) {
    $salesData[$row['m'] - 1] = $row['total']; // Masukkan data ikut bulan (Index 0 = Jan)
}

// ==========================================
// 3. DATA UNTUK KALENDAR (FULLCALENDAR)
// ==========================================
$calendarEvents = [];
$sqlCal = "SELECT b.book_id, b.checkin_date, b.checkout_date, b.status, p.package_name, u.full_name 
           FROM bookings b 
           JOIN packages p ON b.package_id = p.package_id 
           JOIN users u ON b.user_id = u.user_id
           WHERE b.status != 'Cancelled'";
$resCal = $conn->query($sqlCal);

while($row = $resCal->fetch_assoc()) {
    // Warna ikut status
    $color = '#FF7F32'; // Default Orange (Accepted)
    if($row['status'] == 'Pending') $color = '#f1c40f'; // Kuning
    if($row['status'] == 'Rejected') $color = '#e74c3c'; // Merah

    $calendarEvents[] = [
        'title' => $row['package_name'] . ' - ' . $row['full_name'],
        'start' => $row['checkin_date'],
        'end'   => date('Y-m-d', strtotime($row['checkout_date'] . ' +1 day')), // FullCalendar perlukan +1 hari untuk end date
        'color' => $color,
        'url'   => 'edit_booking.php?id=' . $row['book_id'] // Klik untuk edit
    ];
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UluGarden</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <style>
        :root {
            --ulu-orange: #FF7F32;
            --ulu-orange-dark: #e66a20;
            --garden-black: #1A1A1A;
            --soft-bg: #F8F9FA;
            --white: #ffffff;
            --text-grey: #64748b;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-bg);
            color: var(--garden-black);
            padding-bottom: 50px;
        }

        /* --- HEADER --- */
        header {
            background-color: var(--white);
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--garden-black);
            letter-spacing: -0.5px;
        }
        .logo span { color: var(--ulu-orange); }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .admin-info { text-align: right; }
        .admin-info h4 { font-size: 0.9rem; font-weight: 700; }
        .admin-info p { font-size: 0.8rem; color: var(--text-grey); }
        
        .logout-btn {
            background: #FFF0E6;
            color: var(--ulu-orange);
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .logout-btn:hover { background: var(--ulu-orange); color: white; }

        /* --- MAIN CONTAINER --- */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--ulu-orange); }

        /* --- STAT CARDS (NEW) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }

        .stat-info h3 { font-size: 2rem; font-weight: 800; color: var(--garden-black); }
        .stat-info p { color: var(--text-grey); font-size: 0.9rem; font-weight: 600; }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .icon-orange { background: #FFF0E6; color: var(--ulu-orange); }
        .icon-blue { background: #E6F3FF; color: #007BFF; }
        .icon-red { background: #FFE6E6; color: #FF3B30; }

        /* --- ANALYTICS & CALENDAR GRID --- */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 2fr; /* Chart 1/3, Calendar 2/3 */
            gap: 20px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }

        /* FullCalendar Customization */
        #calendar { width: 100%; max-height: 600px; }
        .fc-event { cursor: pointer; border: none; font-size: 0.85rem; }
        .fc-toolbar-title { font-size: 1.2rem !important; font-weight: 700; }
        .fc-button-primary { background-color: var(--garden-black) !important; border: none !important; }
        .fc-button-primary:hover { background-color: var(--ulu-orange) !important; }

        /* --- ORIGINAL MENU GRID --- */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 1px solid transparent;
            display: block;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: var(--ulu-orange);
            box-shadow: 0 10px 30px rgba(255, 127, 50, 0.15);
        }

        .menu-icon {
            width: 60px;
            height: 60px;
            background: var(--garden-black);
            color: var(--white);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .menu-card:hover .menu-icon {
            background: var(--ulu-orange);
        }

        .menu-card h3 { font-size: 1.2rem; margin-bottom: 10px; }
        .menu-card p { color: var(--text-grey); font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px; }
        
        .action-link {
            font-weight: 700;
            color: var(--ulu-orange);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .analytics-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <h1>Ulu<span>Garden</span> Admin</h1>
        </div>
        <div class="admin-profile">
            <div class="admin-info">
                <h4>Hello, <?= htmlspecialchars($admin_name) ?></h4>
                <p>Administrator</p>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <div class="container">
        
        <div class="section-title"><i class="fas fa-chart-pie"></i> Overview</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>RM <?= number_format($totalSales, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-icon icon-orange"><i class="fas fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $totalBookings ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?= $totalPending ?></h3>
                    <p>Pending Actions</p>
                </div>
                <div class="stat-icon icon-red"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="dashboard-card">
                <div class="section-title" style="font-size: 1.2rem; margin-bottom: 15px;">Monthly Revenue</div>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="dashboard-card">
                <div class="section-title" style="font-size: 1.2rem; margin-bottom: 15px;">Booking Calendar</div>
                <div id="calendar"></div>
            </div>
        </div>

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
                <p>Verify payment details uploaded by customers via WhatsApp/System.</p>
                <span class="action-link">Check Payments <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="manage_bookings.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-list-alt"></i></div>
                <h3>Booking Records</h3>
                <p>View complete booking history, check-ins, and customer details.</p>
                <span class="action-link">View Records <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="view_gallery.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-images"></i></div>
                <h3>Gallery Settings</h3>
                <p>Update photos of the resort to showcase the best of UluGarden.</p>
                <span class="action-link">Update Gallery <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="reports_analysis.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Detailed Reports</h3>
                <p>In-depth analysis of business performance and yearly trends.</p>
                <span class="action-link">Open Reports <i class="fas fa-arrow-right"></i></span>
            </a>

            <a href="view_users.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-users"></i></div>
                <h3>User Management</h3>
                <p>Manage registered customers and view their profiles.</p>
                <span class="action-link">View Users <i class="fas fa-arrow-right"></i></span>
            </a>
        </div>

    </div>

    <script>
        // --- 1. SETUP CHART.JS ---
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue (RM)',
                    data: <?= json_encode($salesData) ?>,
                    backgroundColor: '#FF7F32',
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // --- 2. SETUP FULLCALENDAR (PEMBETULAN) ---
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?= json_encode($calendarEvents) ?>,
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                
                // --- PEMBETULAN DI SINI ---
                height: 400, // Tetapkan tinggi tetap (pixel)
                // JANGAN letak contentHeight: 'auto' (Ini punca ia memanjang)
                // --------------------------
                
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