<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
$user_id = $_SESSION['admin_id'];

$result       = $conn->query("SELECT * FROM gallery ORDER BY id DESC");
$total_photos = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .gallery-img { width:100px; height:68px; object-fit:cover; border-radius:8px; border:2px solid var(--slate-200); transition:0.2s; }
        .gallery-img:hover { transform:scale(1.05); box-shadow:var(--shadow-md); }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Gallery Management</div>
            <div class="topbar-breadcrumb">Review and organize your homestay's visual collection</div>
        </div>
        <div class="topbar-right">
            <a href="add_photo.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Photo
            </a>
        </div>
    </div>

    <div class="admin-content">

        <!-- Stat -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); max-width: 400px;">
            <div class="stat-card">
                <div class="stat-info">
                    <p>Total Photos</p>
                    <h3><?= $total_photos ?></h3>
                </div>
                <div class="stat-icon icon-gold"><i class="fas fa-images"></i></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-images"></i> Photo Library</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px;">No.</th>
                            <th style="width:130px;">Preview</th>
                            <th>File Name</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_photos > 0):
                            $no = 1;
                            while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--slate-300); font-weight:600;"><?= sprintf("%02d", $no++) ?>.</td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($row['filename']) ?>" class="gallery-img" alt="Photo">
                            </td>
                            <td>
                                <span style="font-family:monospace; font-size:0.82rem; color:var(--slate-500);">
                                    <?= htmlspecialchars($row['filename']) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <a href="delete_photo.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this photo permanently?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="4" class="table-empty">
                                <i class="fas fa-camera-retro"></i>
                                No photos in gallery yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>