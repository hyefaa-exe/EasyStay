<?php
// Database connection
require 'db_connect.php';

// Ambil data gambar
$sql = "SELECT * FROM gallery ORDER BY id DESC";
$result = $conn->query($sql);

session_start();
// Pastikan admin_id ada, jika tidak set ke 1 (untuk testing)
$user_id = $_SESSION['admin_id'] ?? 1; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
/* --- HEADER (Identikal dengan view_payments) --- */
        

        .logo-area h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        
        
        

        
        
        
        
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197, 168, 128, 0.2); }

        /* --- MAIN CONTENT --- */
        

        .page-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
        }

        .btn-add-photo {
            background: var(--ulu-orange);
            color: white;
            padding: 14px 28px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(197, 168, 128, 0.2);
            transition: 0.3s;
        }
        .btn-add-photo:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(197, 168, 128, 0.3); }

        .stats-badge {
            margin-top: 15px;
            display: inline-flex;
            background: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--ulu-orange);
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            border: 1px solid rgba(197, 168, 128, 0.1);
        }

        /* --- TABLE CARD --- */
        .table-card {
            background: var(--white);
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            padding: 10px;
        }

        
        
        

        .gallery-img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 2px solid white;
            transition: 0.3s;
        }

        
        .btn-delete:hover { background: var(--danger); color: white; }

        .no-data-box { text-align: center; padding: 60px; color: #bbb; }

        @media (max-width: 992px) {
            
            .page-header-flex { flex-direction: column; align-items: flex-start; gap: 20px; }
        }
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
            <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <main class="container">
        <div class="page-header-flex">
            <div>
                <h2 style="font-size: 2rem; font-weight: 700; color: var(--garden-black);">Gallery Management</h2>
                <p style="color: #666; font-size: 0.95rem;">Review and organize your homestay's visual collection.</p>
                <div class="stats-badge">
                    <i class="fas fa-images" style="margin-right: 8px;"></i> Total Photos: <?php echo $result->num_rows; ?>
                </div>
            </div>
            <a href="add_photo.php" class="btn-add-photo">
                <i class="fas fa-plus-circle"></i> Add New Photo
            </a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">No.</th>
                        <th>Preview Photo</th>
                        <th>File Name</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 700; color: #ccc;"><?= sprintf("%02d", $no++); ?>.</td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($row['filename']); ?>" class="gallery-img">
                            </td>
                            <td>
                                <span style="font-family: monospace; font-weight: 600; color: #666;">
                                    <?= htmlspecialchars($row['filename']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="delete_photo.php?id=<?= $row['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Confirm delete this photo?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; 
                    else: ?>
                        <tr>
                            <td colspan="4" class="no-data-box">
                                <i class="fas fa-camera-retro" style="font-size: 3rem; opacity: 0.2; margin-bottom: 10px; display: block;"></i>
                                No photos in gallery.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
<?php $conn->close(); ?>