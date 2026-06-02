<?php
session_start();
require 'db_connect.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginform.html");
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
    <title>Users Management | UluGarden</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ulu-orange: #FF7F32;
            --garden-black: #1A1A1A;
            --soft-orange-bg: #FFF5E9;
            --white: #ffffff;
            --text-main: #2D3E4E;
            --danger: #e74c3c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--soft-orange-bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* --- HEADER (SAMA SEPERTI DASHBOARD) --- */
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

        .logo-area h1 {
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .logo-ulu { color: var(--ulu-orange); }
        .logo-garden { color: var(--garden-black); }

        .brand-sub {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            display: block;
            margin-top: -3px;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .nav-btn {
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-profile {
            background: transparent;
            color: var(--ulu-orange);
            border: 1.5px solid var(--ulu-orange);
        }

        .btn-logout {
            background: var(--ulu-orange);
            color: var(--white);
            border: 1.5px solid var(--ulu-orange);
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 127, 50, 0.2);
        }

        /* --- MAIN CONTENT --- */
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }

        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--garden-black);
        }

        /* --- STATS BOX --- */
        .stats-area {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .stats-box {
            background: linear-gradient(135deg, #FF7F32, #FF9F66);
            color: white;
            padding: 30px 60px;
            border-radius: 25px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(255, 127, 50, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .stats-box h2 { font-size: 3rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }
        .stats-box p { font-size: 1rem; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }

        /* --- SEARCH AREA --- */
        .search-area {
            display: flex;
            justify-content: center;
            margin-bottom: 35px;
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-box {
            width: 100%;
            padding: 15px 25px 15px 55px;
            border-radius: 30px;
            border: 2px solid white;
            font-family: inherit;
            font-size: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            outline: none;
            transition: 0.3s;
            background: white;
        }

        .search-box:focus { border-color: var(--ulu-orange); box-shadow: 0 10px 25px rgba(255, 127, 50, 0.1); }
        .search-container i { 
            position: absolute; left: 22px; top: 50%; transform: translateY(-50%); 
            color: var(--ulu-orange); font-size: 1.1rem;
        }

        /* --- TABLE --- */
        .table-container {
            background: var(--white);
            border-radius: 25px;
            padding: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 20px; background: #fafafa; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        td { padding: 18px 20px; border-bottom: 1px solid #f9f9f9; font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }

        .user-cell { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 38px; height: 38px; border-radius: 12px;
            background: var(--soft-orange-bg); color: var(--ulu-orange);
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;
        }

        .phone-badge { background: #f1f1f1; padding: 5px 12px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; color: #555; }

        /* --- ACTIONS --- */
        .action-btns { display: flex; gap: 8px; justify-content: flex-end; }
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
        .btn-edit { background: rgba(255, 127, 50, 0.1); color: var(--ulu-orange); }
        .btn-delete { background: rgba(231, 76, 60, 0.1); color: var(--danger); }
        .btn-edit:hover { background: var(--ulu-orange); color: white; }
        .btn-delete:hover { background: var(--danger); color: white; }

        /* --- PAGINATION --- */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 10px;
        }

        .page-btn {
            padding: 10px 18px; border-radius: 12px; border: none;
            background: var(--white); color: var(--garden-black);
            cursor: pointer; font-weight: 700; font-size: 0.85rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        }
        .page-btn:hover:not(:disabled) { background: var(--ulu-orange); color: white; }
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