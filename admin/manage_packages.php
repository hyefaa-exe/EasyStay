<?php
session_start();
require 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
} else {
    $user_id = $_SESSION['admin_id'];
}

// Fetch all packages (Pagination handled by JS)
$result = $conn->query("SELECT * FROM packages ORDER BY package_id DESC");
$total_packages = $result->num_rows; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
/* --- HEADER --- */
        

        .logo-area h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        
        
        

        
        
        
        
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197, 168, 128, 0.2); }

        /* --- MAIN CONTAINER --- */
        
        
        .welcome-section h2 { font-size: 2rem; font-weight: 700; color: var(--garden-black); }

        /* --- STATS BOX --- */
        
        
        .stats-box h2 { font-size: 3.5rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }
        .stats-box p { font-size: 1rem; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }

        /* --- ACTION BAR --- */
        .action-bar { display: flex; justify-content: center; margin-bottom: 35px; }
        .add-btn {
            background: var(--garden-black); color: white; text-decoration: none;
            padding: 15px 35px; border-radius: 15px; font-weight: 700;
            display: flex; align-items: center; gap: 12px; transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .add-btn:hover { background: var(--ulu-orange); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(197, 168, 128, 0.2); }

        /* --- TABLE --- */
        
        
        
        
        

        .pkg-name { font-weight: 700; color: var(--garden-black); font-size: 1rem; }
        .pkg-price { font-weight: 800; color: var(--ulu-orange); font-size: 1.1rem; }
        .pkg-desc { color: #666; font-size: 0.85rem; max-width: 350px; }

        /* --- ACTIONS --- */
        
        
        
        
        .btn-edit:hover { background: var(--ulu-orange); color: white; transform: scale(1.1); }
        .btn-delete:hover { background: var(--danger); color: white; transform: scale(1.1); }

        /* --- PAGINATION (FOLLOWING VIEW_USERS STYLE) --- */
        
        .pagination-info { font-size: 0.85rem; color: #888; font-weight: 600; }
        .pagination-btns { display: flex; gap: 8px; }
        
        .page-btn:hover:not(:disabled) { background: var(--ulu-orange); color: white; }
        .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        @media (max-width: 768px) {  .pkg-desc { display: none; } }
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
            <a href="edit_profile.php?id=<?php echo $user_id; ?>" class="nav-btn btn-profile"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="welcome-section">
            <h2>Homestay Packages</h2>
            <p style="color: #666;">Update pricing, availability, and details for EasyStay staycations.</p>
        </div>

        <div class="stats-area">
            <div class="stats-box">
                <p>Total Registered Packages</p>
                <h2><?php echo $total_packages; ?></h2>
            </div>
        </div>

        <div class="action-bar">
            <a href="add_package.php" class="add-btn">
                <i class="fas fa-plus-circle"></i> Add New Package
            </a>
        </div>

        <div class="table-container">
            <table id="pkgTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Package Name</th>
                        <th>Description</th>
                        <th>Price (RM)</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php 
                        $no = 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td style="font-weight: 700; color: #ccc;"><?php echo $no++; ?>.</td>
                                <td class="pkg-name"><?php echo htmlspecialchars($row['package_name']); ?></td>
                                <td class="pkg-desc"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="pkg-price"><?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_package.php?id=<?php echo $row['package_id']; ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_package.php?id=<?php echo $row['package_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this package?');" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 60px; color: #999;">No packages found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination-info" id="pageInfo">Showing 1 to 5 of 0 entries</div>
            <div class="pagination-btns">
                <button class="page-btn" id="prevBtn" onclick="prevPage()"><i class="fas fa-chevron-left"></i> Previous</button>
                <button class="page-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </main>

    <script>
    let currentPage = 1;
    const rowsPerPage = 5; // Anda boleh tukar jumlah baris di sini
    const table = document.getElementById("pkgTable");
    const rows = Array.from(table.getElementsByTagName("tr")).slice(1); 

    function displayTable() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? "" : "none";
        });

        document.getElementById("pageInfo").innerText = `Showing ${rows.length > 0 ? start + 1 : 0} to ${Math.min(end, rows.length)} of ${rows.length} packages`;
        document.getElementById("prevBtn").disabled = currentPage === 1;
        document.getElementById("nextBtn").disabled = currentPage >= Math.ceil(rows.length / rowsPerPage) || rows.length === 0;
    }

    function prevPage() { if (currentPage > 1) { currentPage--; displayTable(); } }
    function nextPage() { if (currentPage < Math.ceil(rows.length / rowsPerPage)) { currentPage++; displayTable(); } }

    // Initialize table
    displayTable();
    </script>

</body>
</html>
<?php $conn->close(); ?>