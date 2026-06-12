<?php
// Database connection
require 'db_connect.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['admin_id'];

// --- CONFIGURATION PAGINATION ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// --- HANDLE SEARCH ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = "";
if (!empty($search)) {
    $search_condition = " WHERE u.full_name LIKE '%$search%' OR p.package_name LIKE '%$search%' ";
}

// --- COUNT TOTAL FOR PAGINATION ---
$total_sql = "SELECT COUNT(*) AS total FROM bookings b 
              JOIN users u ON b.user_id = u.user_id 
              JOIN packages p ON b.package_id = p.package_id 
              $search_condition";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);

// --- FETCH DATA ---
$sql = "SELECT 
            b.book_id, 
            u.full_name,
            p.package_name, 
            b.checkin_date, 
            b.checkout_date, 
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN packages p ON b.package_id = p.package_id
        $search_condition
        ORDER BY b.checkin_date DESC
        LIMIT $offset, $results_per_page";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
/* --- HEADER --- */
        
        .logo-area h1 { font-size: 1.7rem; font-weight: 800; }
        
        
        

        
        
        
        

        /* --- CONTENT --- */
        
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        
        .table-card { background: var(--white); border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.03); padding: 10px; }
         /* Fixed layout untuk keselarasan */
        
        
        

        /* Penetapan lebar kolum supaya selari */
        .col-no { width: 50px; text-align: center; }
        .col-id { width: 100px; }
        .col-name { width: 220px; }
        .col-pkg { width: 150px; }
        .col-date { width: 140px; }
        .col-pay { width: 150px; text-align: center; }
        .col-status { width: 130px; text-align: center; }

        .no-col { font-weight: 700; color: #ccc; }
        .booking-id-text { font-weight: 800; color: var(--ulu-orange); font-family: monospace; }
        
        .btn-payment-link {
            display: inline-block; border: 2.5px solid var(--success); color: var(--success);
            padding: 8px 16px; border-radius: 10px; text-decoration: none;
            font-weight: 800; font-size: 0.72rem; text-transform: uppercase;
            width: 100%; text-align: center; transition: 0.2s;
        }
        .btn-payment-link:hover { background: var(--success); color: white; }

        .status-badge { display: inline-block; width: 100px; padding: 6px 0; border-radius: 20px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; text-align: center; }
        .status-Accepted { background: rgba(39, 174, 96, 0.1); color: var(--success); }
        .status-Pending { background: #FEF9C3; color: #854D0E; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
        .page-link { text-decoration: none; padding: 10px 18px; border-radius: 12px; background: var(--white); color: var(--garden-black); font-weight: 700; font-size: 0.85rem; box-shadow: 0 4px 10px rgba(0,0,0,0.04); transition: 0.3s; }
        .page-link:hover, .page-link.active { background: var(--ulu-orange); color: white; }
    </style>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php" style="text-decoration: none;">
                <h1><span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span></h1>
            </a>
            <span class="brand-sub">Management Portal</span>
        </div>
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="edit_profile.php?id=<?= $user_id; ?>" class="nav-btn btn-profile"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-header-flex">
            <div>
                <h2 style="font-size: 2.2rem; font-weight: 800; letter-spacing: -1px;">Booking Management Page</h2>
                <p style="color: #777;">Review resort reservations and track payments.</p>
                <div style="margin-top: 15px; display: inline-flex; background: white; padding: 8px 16px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; color: var(--ulu-orange); border: 1px solid rgba(255,127,50,0.1);">
                    <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Total Bookings: <?= $total_rows ?>
                </div>
            </div>
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search name or package..." value="<?= htmlspecialchars($search) ?>" 
                       style="padding: 12px 20px; border-radius: 30px; border: 1px solid #eee; outline: none; width: 300px;">
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th class="col-no">NO.</th>
                        <th class="col-id">BOOKING ID</th>
                        <th class="col-name">FULL NAME</th>
                        <th class="col-pkg">PACKAGE</th>
                        <th class="col-date">CHECK-IN</th>
                        <th class="col-date">CHECK-OUT</th>
                        <th class="col-pay">PAYMENT</th>
                        <th class="col-status">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $offset + 1;
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="col-no no-col"><?= $no++; ?>.</td>
                            <td class="col-id booking-id-text">#<?= $row['book_id']; ?></td>
                            <td class="col-name" style="font-weight:700;"><?= htmlspecialchars($row['full_name']); ?></td>
                            <td class="col-pkg"><?= htmlspecialchars($row['package_name']); ?></td>
                            <td class="col-date">
                                <i class="far fa-calendar-alt" style="color: var(--ulu-orange); margin-right: 5px;"></i>
                                <?= date('d/m/Y', strtotime($row['checkin_date'])); ?>
                            </td>
                            <td class="col-date">
                                <i class="far fa-calendar-alt" style="color: #888; margin-right: 5px;"></i>
                                <?= date('d/m/Y', strtotime($row['checkout_date'])); ?>
                            </td>
                            <td class="col-pay">
                                <a href="view_payments.php?book_id=<?= $row['book_id']; ?>" class="btn-payment-link">Payment Page</a>
                            </td>
                            <td class="col-status">
                                <span class="status-badge status-<?= $row['status']; ?>"><?= $row['status']; ?></span>
                            </td>
                        </tr>
                        <?php endwhile; 
                    else: ?>
                        <tr><td colspan="8" style="text-align:center; padding:50px; color:#ccc;">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?page=<?= min($total_pages, $page+1) ?>&search=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>