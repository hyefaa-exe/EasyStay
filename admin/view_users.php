<?php
session_start();
require 'db_connect.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
} else {
    $user_id = $_SESSION['admin_id'];
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM users WHERE user_id = $delete_id");
    header("Location: view_users.php");
    exit();
}

$result = $conn->query("SELECT user_id, full_name, email, phone FROM users");
$total_registered = $result->num_rows; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
/* --- HEADER (SAMA SEPERTI DASHBOARD) --- */
        

        .logo-area h1 {
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        
        

        

        

        

        

        

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197, 168, 128, 0.2);
        }

        /* --- MAIN CONTENT --- */
        

        

        .welcome-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--garden-black);
        }

        /* --- STATS BOX --- */
        

        

        .stats-box h2 { font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }
        .stats-box p { font-size: 1rem; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }

        /* --- SEARCH AREA --- */
        

        

        

        .search-box:focus { border-color: var(--ulu-orange); box-shadow: 0 10px 25px rgba(197, 168, 128, 0.1); }
        .search-container i { 
            position: absolute; left: 22px; top: 50%; transform: translateY(-50%); 
            color: var(--ulu-orange); font-size: 1.1rem;
        }

        /* --- TABLE --- */
        

        
        
        
        

        
        

        

        /* --- ACTIONS --- */
        
        
        
        
        .btn-edit:hover { background: var(--ulu-orange); color: white; }
        .btn-delete:hover { background: var(--danger); color: white; }

        /* --- PAGINATION --- */
        

        
        .page-btn:hover:not(:disabled) { background: var(--ulu-orange); color: white; }
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
            <a href="admin_dashboard.php" class="nav-btn btn-profile">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="edit_profile.php?id=<?php echo $user_id; ?>" class="nav-btn btn-profile">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <main class="container">
        <div class="welcome-section">
            <h2>User Management</h2>
            <p style="color: #666;">Directory of all registered customers in the system.</p>
        </div>

        <div class="stats-area">
            <div class="stats-box">
                <p>Total Registered</p>
                <h2><?php echo $total_registered; ?></h2>
            </div>
        </div>

        <div class="search-area">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search customer details..." onkeyup="searchTable()">
            </div>
        </div>

        <div class="table-container">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>User ID</th>
                        <th>Customer Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php 
                        $count = 1;
                        while ($row = $result->fetch_assoc()): 
                            $initials = strtoupper(substr($row['full_name'], 0, 1));
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #bbb;"><?php echo $count++; ?></td>
                                <td style="font-family: monospace; font-weight: 600;">#<?php echo $row['user_id']; ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="avatar"><?php echo $initials; ?></div>
                                        <div style="font-weight: 700; color: var(--garden-black);"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    </div>
                                </td>
                                <td style="color: #666; font-size: 0.85rem;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="phone-badge"><?php echo htmlspecialchars($row['phone']); ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="view_users.php?delete_id=<?php echo $row['user_id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Confirm delete user?');"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 50px; color: #999;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination-info" id="pageInfo" style="font-size: 0.85rem; color: #888; font-weight: 500;">Showing 1 to 10 of 0 entries</div>
            <div class="pagination-btns" style="display: flex; gap: 8px;">
                <button class="page-btn" id="prevBtn" onclick="prevPage()"><i class="fas fa-chevron-left"></i> Previous</button>
                <button class="page-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </main>

    <script>
    let currentPage = 1;
    const rowsPerPage = 10;
    const table = document.getElementById("usersTable");
    const rows = Array.from(table.getElementsByTagName("tr")).slice(1); 

    function displayTable() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? "" : "none";
        });

        document.getElementById("pageInfo").innerText = `Showing ${rows.length > 0 ? start + 1 : 0} to ${Math.min(end, rows.length)} of ${rows.length} customers`;
        document.getElementById("prevBtn").disabled = currentPage === 1;
        document.getElementById("nextBtn").disabled = currentPage >= Math.ceil(rows.length / rowsPerPage) || rows.length === 0;
    }

    function prevPage() { if (currentPage > 1) { currentPage--; displayTable(); } }
    function nextPage() { if (currentPage < Math.ceil(rows.length / rowsPerPage)) { currentPage++; displayTable(); } }

    function searchTable() {
        const filter = document.getElementById('searchInput').value.toLowerCase();
        let visibleRows = 0;
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = "";
                visibleRows++;
            } else {
                row.style.display = "none";
            }
        });

        if (filter === "") {
            currentPage = 1;
            displayTable();
        } else {
            document.getElementById("pageInfo").innerText = `Found ${visibleRows} matching customers`;
            document.getElementById("prevBtn").disabled = true;
            document.getElementById("nextBtn").disabled = true;
        }
    }

    displayTable();
    </script>

</body>
</html>