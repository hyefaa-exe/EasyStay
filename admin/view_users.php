<?php
session_start();
require 'db_connect.php';
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE user_id = $delete_id");
    header("Location: view_users.php");
    exit();
}

$result           = $conn->query("SELECT user_id, full_name, email, phone FROM users ORDER BY user_id DESC");
$total_registered = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">User Management</div>
            <div class="topbar-breadcrumb">Directory of all registered customers in the system</div>
        </div>
        <div class="topbar-right">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search customer..." onkeyup="searchTable()">
            </div>
        </div>
    </div>

    <div class="admin-content">

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); max-width: 400px;">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Registered Users</p>
                    <h3><?= $total_registered ?></h3>
                </div>
                <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-users"></i> Customer List</h3>
            </div>
            <div class="table-responsive">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">No.</th>
                            <th style="width:80px;">ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if ($result && $result->num_rows > 0):
                            $count = 1;
                            while ($row = $result->fetch_assoc()):
                                $initial = strtoupper(substr($row['full_name'],0,1)); ?>
                        <tr>
                            <td style="color:var(--slate-300); font-weight:600;"><?= $count++ ?>.</td>
                            <td><span class="table-id">#<?= $row['user_id'] ?></span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar"><?= $initial ?></div>
                                    <div class="user-info-name"><?= htmlspecialchars($row['full_name']) ?></div>
                                </div>
                            </td>
                            <td style="color:var(--slate-500); font-size:0.82rem;"><?= htmlspecialchars($row['email']) ?></td>
                            <td><span class="phone-badge"><?= htmlspecialchars($row['phone']) ?></span></td>
                            <td>
                                <div class="action-btns" style="justify-content:flex-end;">
                                    <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="view_users.php?delete_id=<?= $row['user_id'] ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Delete user <?= htmlspecialchars($row['full_name']) ?>?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="table-empty"><i class="fas fa-users"></i> No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <span id="pageInfo" style="font-size:0.8rem; color:var(--slate-400);">Loading...</span>
                <div style="display:flex; gap:6px;">
                    <button class="page-btn" id="prevBtn" onclick="prevPage()"><i class="fas fa-chevron-left"></i> Prev</button>
                    <button class="page-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentPage = 1;
const rowsPerPage = 10;
const tableRows = Array.from(document.querySelectorAll('#tableBody tr'));

function displayTable(filteredRows) {
    const rows = filteredRows || tableRows;
    const start = (currentPage - 1) * rowsPerPage;
    const end   = start + rowsPerPage;
    tableRows.forEach(r => r.style.display = 'none');
    rows.slice(start, end).forEach(r => r.style.display = '');
    document.getElementById('pageInfo').innerText = `Showing ${rows.length > 0 ? start+1 : 0}–${Math.min(end, rows.length)} of ${rows.length} customers`;
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage >= Math.ceil(rows.length / rowsPerPage);
}

function prevPage() { if (currentPage > 1) { currentPage--; displayTable(); } }
function nextPage() { if (currentPage < Math.ceil(tableRows.length / rowsPerPage)) { currentPage++; displayTable(); } }

function searchTable() {
    const filter = document.getElementById('searchInput').value.toLowerCase();
    const filtered = tableRows.filter(r => r.innerText.toLowerCase().includes(filter));
    currentPage = 1;
    const start = 0, end = rowsPerPage;
    tableRows.forEach(r => r.style.display = 'none');
    filtered.slice(start, end).forEach(r => r.style.display = '');
    document.getElementById('pageInfo').innerText = `Found ${filtered.length} matching customers`;
    document.getElementById('prevBtn').disabled = true;
    document.getElementById('nextBtn').disabled = filtered.length <= rowsPerPage;
    if (!filter) displayTable();
}

displayTable();
</script>
</body>
</html>