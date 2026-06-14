<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id         = $_SESSION['admin_id'];
$success_message = "";
$error_message   = "";

// Ambil data admin
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) { die("User not found."); }

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname    = trim($_POST['fullname']);
    $email       = trim($_POST['email']);
    $phone_no    = trim($_POST['phone_no']);
    $new_password = trim($_POST['new_password'] ?? '');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak sah.";
    } elseif (empty($fullname) || empty($email) || empty($phone_no)) {
        $error_message = "Sila isi semua maklumat yang diperlukan.";
    } else {
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $error_message = "Kata laluan mesti sekurang-kurangnya 8 aksara.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE admins SET fullname=?, email=?, phone_no=?, password=? WHERE admin_id=?");
                $update_stmt->bind_param("ssssi", $fullname, $email, $phone_no, $hashed_password, $user_id);
            }
        } else {
            $update_stmt = $conn->prepare("UPDATE admins SET fullname=?, email=?, phone_no=? WHERE admin_id=?");
            $update_stmt->bind_param("sssi", $fullname, $email, $phone_no, $user_id);
        }

        if (empty($error_message) && isset($update_stmt)) {
            if ($update_stmt->execute()) {
                $success_message = "Profil berjaya dikemaskini!";
                $_SESSION['full_name'] = $fullname;
                // Refresh data
                $stmt2 = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $user = $stmt2->get_result()->fetch_assoc();
            } else {
                $error_message = "Ralat semasa kemaskini profil.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | EasyStay Admin</title>
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
            <div class="topbar-title">Edit Profile</div>
            <div class="topbar-breadcrumb">Kemaskini maklumat peribadi dan kata laluan akaun</div>
        </div>
        <div class="topbar-right">
            <a href="admin_dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="admin-content" style="max-width: 680px;">

        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error_message ?></div>
        <?php endif; ?>

        <!-- Profile Banner -->
        <div class="card" style="margin-bottom:20px;">
            <div style="background: var(--slate-900); padding: 32px; text-align: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div style="width:64px; height:64px; background:var(--gold-light); border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; font-size:1.8rem; color:var(--gold);">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 style="font-size:1.3rem; font-weight:800; color:var(--white); margin-bottom:4px;"><?= htmlspecialchars($user['fullname']) ?></h2>
                <p style="color:var(--slate-400); font-size:0.82rem;">Administrator</p>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-edit"></i> Maklumat Peribadi</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nama Penuh</label>
                        <input type="text" name="fullname" class="form-control"
                               value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombor Telefon</label>
                            <input type="text" name="phone_no" class="form-control"
                                   value="<?= htmlspecialchars($user['phone_no'] ?? '') ?>" required>
                        </div>
                    </div>

                    <hr style="border-color:var(--slate-100); margin: 20px 0;">

                    <div class="section-title"><i class="fas fa-lock"></i> Kemaskini Kata Laluan</div>

                    <div class="form-group">
                        <label class="form-label">Kata Laluan Baru</label>
                        <input type="password" name="new_password" class="form-control"
                               placeholder="Biarkan kosong jika tidak mahu tukar (min. 8 aksara)">
                        <p style="font-size:0.72rem; color:var(--slate-400); margin-top:5px;">
                            <i class="fas fa-info-circle"></i> Kata laluan mesti sekurang-kurangnya 8 aksara.
                        </p>
                    </div>

                    <button type="submit" class="btn-save" style="margin-top:8px;">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
</body>
</html>