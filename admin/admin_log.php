<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
require_once 'db_connect.php';

// Fetch logs dengan join ke admins table
$sql = "SELECT al.*, a.fullname 
        FROM admin_logs al 
        JOIN admins a ON al.admin_id = a.admin_id 
        ORDER BY al.created_at DESC 
        LIMIT 200";
$result = $conn->query($sql);

// Map action ke warna badge
$actionColors = [
    'UPDATE_BOOKING'  => 'var(--info)',
    'DELETE_USER'     => 'var(--danger)',
    'UPDATE_PAYMENT'  => 'var(--success)',
    'DELETE_PACKAGE'  => 'var(--danger)',
    'ADD_PACKAGE'     => 'var(--gold)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Activity Log</div>
            <div class="topbar-breadcrumb">Rekod tindakan admin dalam sistem (200 terkini)</div>
        </div>
    </div>

    <div class="admin-content">
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-history"></i> Admin Activity Log</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th style="width:160px;">Date & Time</th>
                            <th>Admin</th>
                            <th style="width:160px;">Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                                $color = $actionColors[$row['action']] ?? 'var(--slate-400)';
                        ?>
                        <tr>
                            <td style="color:var(--slate-300); font-weight:600;"><?= $no++ ?></td>
                            <td style="font-size:0.78rem; color:var(--slate-500);">
                                <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                                <span style="color:var(--slate-400);"><?= date('H:i:s', strtotime($row['created_at'])) ?></span>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar"><?= strtoupper(substr($row['fullname'],0,1)) ?></div>
                                    <div class="user-info-name"><?= htmlspecialchars($row['fullname']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background:<?= $color ?>20; color:<?= $color ?>; border:1px solid <?= $color ?>40;">
                                    <?= htmlspecialchars($row['action']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem; color:var(--slate-600);">
                                <?= htmlspecialchars($row['description']) ?>
                                <?php if ($row['target_id'] && $row['target_type'] === 'booking'): ?>
                                    <a href="edit_booking.php?id=<?= $row['target_id'] ?>" style="color:var(--gold); font-size:0.72rem; margin-left:6px;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="table-empty">
                                <i class="fas fa-history"></i> Tiada log aktiviti lagi.
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
