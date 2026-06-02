<?php
// Database connection
require 'db_connect.php';

// Pastikan session dimulakan untuk mendapatkan ID admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: loginform.html");
    exit();
} else {
    $user_id = $_SESSION['admin_id'];
}

// --- CONFIGURATION PAGINATION ---
$results_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// --- HANDLE SEARCH ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = "";
if (!empty($search)) {
    // Cari berdasarkan nama penuh atau nama pakej
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

// --- FETCH DATA WITH SEARCH, LIMIT & OFFSET ---
// Kita gunakan FIELD(b.status, 'Pending', 'Accepted', 'Rejected') 
// supaya 'Pending' mendapat nilai 1 (paling atas), 'Accepted' nilai 2, dan seterusnya.
$sql = "SELECT 
            b.book_id, 
            b.user_id, 
            u.full_name,
            p.package_name, 
            b.checkin_date, 
            b.checkout_date, 
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN packages p ON b.package_id = p.package_id
        $search_condition
        ORDER BY FIELD(b.status, 'Pending', 'Accepted', 'Rejected') ASC, b.checkin_date DESC
        LIMIT $offset, $results_per_page";

$result = $conn->query($sql);

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | UluGarden</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ulu-orange: #FF7F32;
            --garden-black: #1A1A1A;
            --soft-orange-bg: #FFF5E9;
            --white: #ffffff;
            --text-main: #2D3E4E;
            --text-gray: #8E8E8E;
            --danger: #e74c3c;
            --success: #27ae60;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-orange-bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* --- HEADER --- */
        .header {
            background: var(--white);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(255, 127, 50, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-area h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        .logo-ulu { color: var(--ulu-orange); }
        .logo-garden { color: var(--garden-black); }
        .brand-sub { font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; display: block; margin-top: -3px; }

        .nav-actions { display: flex; gap: 15px; align-items: center; }
        .nav-btn {
            text-decoration: none; padding: 10px 22px; border-radius: 12px;
            font-weight: 600; font-size: 0.85rem; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-profile { background: transparent; color: var(--ulu-orange); border: 1.5px solid var(--ulu-orange); }
        .btn-logout { background: var(--ulu-orange); color: var(--white); border: 1.5px solid var(--ulu-orange); }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 127, 50, 0.2); }

        /* --- MAIN CONTENT & TABLE STYLES --- */
        .container { max-width: 1250px; margin: 0 auto; padding: 40px 20px; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }

        .search-form { position: relative; }
        .search-input {
            padding: 15px 25px 15px 50px; border-radius: 30px; border: 2px solid white;
            width: 350px; font-size: 0.9rem; box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            outline: none; transition: 0.3s; background: white;
        }
        .search-input:focus { border-color: var(--ulu-orange); box-shadow: 0 10px 25px rgba(255, 127, 50, 0.1); }
        .search-icon-inside { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--ulu-orange); }

        .table-card { background: var(--white); border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.03); padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 20px 15px; background: #fafafa; color: #888; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; text-align: left; }
        td { padding: 20px 15px; border-bottom: 1px solid #f9f9f9; font-size: 0.88rem; vertical-align: middle; }

        /* --- PAYMENT BUTTON STYLE --- */
        .btn-payment-link {
            display: inline-block; border: 2px solid var(--success); color: var(--success);
            padding: 6px 12px; border-radius: 8px; text-decoration: none;
            font-weight: 800; font-size: 0.7rem; text-transform: uppercase;
            transition: 0.2s; text-align: center;
        }
        .btn-payment-link:hover { background: var(--success); color: white; }

        .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; display: inline-block; min-width: 90px; text-align: center; }
        .status-Accepted { background: #D1FAE5; color: #065F46; }
        .status-Rejected { background: #FEE2E2; color: #991B1B; }
        .status-Pending { background: #FEF9C3; color: #854D0E; }

        .btn-icon {
            text-decoration: none; padding: 8px; border-radius: 10px;
            color: var(--garden-black); background: #F3F4F6; transition: 0.2s;
            margin-left: 5px; display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-icon:hover { background: var(--garden-black); color: white; }
        .btn-delete-action { background: rgba(231, 76, 60, 0.1); color: var(--danger); }
        .btn-delete-action:hover { background: var(--danger); color: white; }

        /* --- PAGINATION --- */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
        .page-link {
            text-decoration: none; padding: 10px 18px; border-radius: 12px;
            background: var(--white); color: var(--garden-black);
            font-weight: 700; font-size: 0.85rem; box-shadow: 0 4px 10px rgba(0,0,0,0.04); transition: 0.3s;
        }
        .page-link:hover, .page-link.active { background: var(--ulu-orange); color: white; }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }

        @media (max-width: 992px) {
            .header { padding: 15px 25px; }
            .page-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
            .search-input { width: 100%; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php" style="text-decoration: none;">
                <h1><span class="logo-ulu">Ulu</span><span class="logo-garden">Garden</span></h1>
            </a>
            <span class="brand-sub">Management Portal</span>
        </div>
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="edit_profile.php?id=<?php echo $user_id; ?>" class="nav-btn btn-profile"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-header-flex">
            <div>
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--garden-black);">Booking Management</h2>
                <p style="color: #666; font-size: 0.95rem;">Review reservations and track payment statuses.</p>
            </div>

            <form action="" method="GET" class="search-form">
                <i class="fas fa-search search-icon-inside"></i>
                <input type="text" name="search" class="search-input" placeholder="Search guest or package..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div style="background: #D1FAE5; color: #065F46; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700;">
                <i class="fas fa-check-circle"></i> Booking status updated successfully!
            </div>
        <?php endif; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th style="width: 110px;">Booking ID</th>
                        <th>Guest Name</th>
                        <th>Package</th>
                        <th style="width: 180px;">Check-in / Out</th>
                        <th style="width: 150px; text-align: center;">Payment</th>
                        <th style="width: 120px; text-align: center;">Status</th>
                        <th style="width: 110px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php 
                        $no = $offset + 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #bbb;"><?= $no++; ?>.</td>
                                <td style="font-family: monospace; font-weight: 700; color: var(--ulu-orange);">#<?= $row['book_id']; ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--garden-black);"><?= htmlspecialchars($row['full_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #888;">User ID: <?= $row['user_id']; ?></div>
                                </td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['package_name']); ?></td>
                                <td>
                                    <div style="font-size: 0.85rem; font-weight: 600;"><i class="far fa-calendar-check" style="color: var(--ulu-orange); width: 15px;"></i> In: <?= date('d M Y', strtotime($row['checkin_date'])); ?></div>
                                    <div style="font-size: 0.85rem; color: #888;"><i class="far fa-calendar-times" style="width: 15px;"></i> Out: <?= date('d M Y', strtotime($row['checkout_date'])); ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <a href="view_payments.php?book_id=<?= $row['book_id']; ?>" class="btn-payment-link">
                                        <i class="fas fa-receipt"></i> Payment Page
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <span class="status-badge status-<?= $row['status']; ?>">
                                        <?= $row['status']; ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="edit_booking.php?id=<?= $row['book_id']; ?>" class="btn-icon" title="Edit Status">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_booking.php?id=<?= $row['book_id']; ?>" class="btn-icon btn-delete-action" onclick="return confirm('Confirm delete booking?')" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: #999;">
                                <?= !empty($search) ? "No results found for '".htmlspecialchars($search)."'" : "No bookings found." ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <a href="?page=<?= min($total_pages, $page+1) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>