<?php
session_start();
require 'db_connect.php';
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

$result         = $conn->query("SELECT * FROM packages ORDER BY package_id DESC");
$total_packages = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages | EasyStay Admin</title>
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
            <div class="topbar-title">Manage Packages</div>
            <div class="topbar-breadcrumb">Update pricing, availability, and details for EasyStay packages</div>
        </div>
        <div class="topbar-right">
            <a href="add_package.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Package
            </a>
        </div>
    </div>

    <div class="admin-content">

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); max-width: 400px;">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Packages</p>
                    <h3><?= $total_packages ?></h3>
                </div>
                <div class="stat-icon icon-gold"><i class="fas fa-box-open"></i></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-box-open"></i> Package List</h3>
            </div>
            <div class="table-responsive">
                <table id="pkgTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">No.</th>
                            <th>Package Name</th>
                            <th>Description</th>
                            <th>Price (RM)</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if ($result && $result->num_rows > 0):
                            $no = 1;
                            while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--slate-300); font-weight:600;"><?= $no++ ?>.</td>
                            <td style="font-weight:700; color:var(--slate-800);"><?= htmlspecialchars($row['package_name']) ?></td>
                            <td style="color:var(--slate-400); font-size:0.82rem; max-width:320px;"><?= htmlspecialchars(substr($row['description'], 0, 90)) ?>...</td>
                            <td style="font-weight:800; color:var(--gold);">RM <?= number_format($row['price'], 2) ?></td>
                            <td>
                                <div class="action-btns" style="justify-content:flex-end;">
                                    <a href="edit_package.php?id=<?= $row['package_id'] ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_package.php?id=<?= $row['package_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete this package?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="table-empty"><i class="fas fa-box-open"></i> No packages found.</td></tr>
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
const rowsPerPage = 8;
const tableRows = Array.from(document.querySelectorAll('#tableBody tr'));
function displayTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    tableRows.forEach((row, i) => row.style.display = (i >= start && i < end) ? '' : 'none');
    document.getElementById('pageInfo').innerText = `Showing ${tableRows.length > 0 ? start+1 : 0}–${Math.min(end, tableRows.length)} of ${tableRows.length} packages`;
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage >= Math.ceil(tableRows.length / rowsPerPage);
}
function prevPage() { if (currentPage > 1) { currentPage--; displayTable(); } }
function nextPage() { if (currentPage < Math.ceil(tableRows.length / rowsPerPage)) { currentPage++; displayTable(); } }
displayTable();
</script>
</body>
</html>
<?php $conn->close(); ?>