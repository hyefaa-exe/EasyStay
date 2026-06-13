<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

// Pagination
$results_per_page = 10;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = "";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $search_condition = " WHERE u.full_name LIKE '%$search_safe%' OR p.package_name LIKE '%$search_safe%' ";
}

// Total count
$total_rows  = $conn->query("SELECT COUNT(*) AS total FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN packages p ON b.package_id = p.package_id $search_condition")->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);

// Fetch data
$sql = "SELECT b.book_id, b.user_id, u.full_name, p.package_name, b.checkin_date, b.checkout_date, b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN packages p ON b.package_id = p.package_id
        $search_condition
        ORDER BY FIELD(b.status,'Pending','Accepted','Rejected') ASC, b.checkin_date DESC
        LIMIT $offset, $results_per_page";
$result = $conn->query($sql);

header("Cache-Control: no-cache, must-revalidate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .page-link { text-decoration:none; padding:7px 13px; border-radius:7px; background:var(--white); color:var(--slate-700); font-weight:700; font-size:0.8rem; border:1px solid var(--slate-200); transition:0.2s; }
        .page-link:hover, .page-link.active { background:var(--gold); color:white; border-color:var(--gold); }
        .page-link.disabled { opacity:0.4; pointer-events:none; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Booking Management</div>
            <div class="topbar-breadcrumb">Review reservations and track payment statuses</div>
        </div>
        <div class="topbar-right">
            <form action="" method="GET" style="display:flex; gap:8px; align-items:center;">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="search-box" placeholder="Search guest or package..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>
    </div>

    <div class="admin-content">

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Booking status updated successfully!</div>
        <?php endif; ?>

        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-calendar-check" style="color:var(--gold)"></i> All Bookings
                    <span style="font-size:0.75rem; font-weight:500; color:var(--slate-400); margin-left:8px;"><?= $total_rows ?> records</span>
                </h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No.</th>
                            <th style="width:90px;">ID</th>
                            <th>Guest</th>
                            <th>Package</th>
                            <th>Check-in / Out</th>
                            <th style="text-align:center;">Payment</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            $no = $offset + 1;
                            while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--slate-300); font-weight:600;"><?= $no++ ?>.</td>
                                <td><span class="table-id">#<?= $row['book_id'] ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="avatar"><?= strtoupper(substr($row['full_name'],0,1)) ?></div>
                                        <div>
                                            <div class="user-info-name"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="user-info-sub">ID: <?= $row['user_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight:600; color:var(--slate-800);"><?= htmlspecialchars($row['package_name']) ?></td>
                                <td>
                                    <div style="font-size:0.82rem; font-weight:600;"><i class="far fa-calendar-check text-gold" style="width:14px;"></i> <?= date('d M Y', strtotime($row['checkin_date'])) ?></div>
                                    <div style="font-size:0.78rem; color:var(--slate-400);"><i class="far fa-calendar-times" style="width:14px;"></i> <?= date('d M Y', strtotime($row['checkout_date'])) ?></div>
                                </td>
                                <td style="text-align:center;">
                                    <a href="view_payments.php?book_id=<?= $row['book_id'] ?>" class="btn btn-sm btn-outline" style="font-size:0.72rem;">
                                        <i class="fas fa-receipt"></i> Verify
                                    </a>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge status-<?= $row['status'] ?>"><?= $row['status'] ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="action-btns" style="justify-content:flex-end;">
                                        <a href="edit_booking.php?id=<?= $row['book_id'] ?>" class="btn-action btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_booking.php?id=<?= $row['book_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Confirm delete booking #<?= $row['book_id'] ?>?')" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8" class="table-empty">
                                    <i class="fas fa-calendar-times"></i>
                                    <?= !empty($search) ? "No results for \"".htmlspecialchars($search)."\"" : "No bookings found." ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <span><?= $total_rows ?> total records &bull; Page <?= $page ?> of <?= $total_pages ?></span>
                <div style="display:flex; gap:6px; align-items:center;">
                    <a href="?page=<?= max(1, $page-1) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= min($total_pages, $page+1) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>