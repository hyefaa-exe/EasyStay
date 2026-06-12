<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];
$success_message = "";
$error_message = "";

// Ambil data admin
$sql = "SELECT * FROM admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);
    $new_password = trim($_POST['new_password']);

    if (empty($fullname) || empty($email) || empty($phone_no)) {
        $error_message = "Please fill in all required fields.";
    } else {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admins SET fullname = ?, email = ?, phone_no = ?, password = ? WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $fullname, $email, $phone_no, $hashed_password, $user_id);
        } else {
            $update_sql = "UPDATE admins SET fullname = ?, email = ?, phone_no = ? WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $fullname, $email, $phone_no, $user_id);
        }

        if ($update_stmt->execute()) {
            $success_message = "Profile updated successfully!";
            header("refresh:2;url=edit_profile.php");
        } else {
            $error_message = "Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
/* --- HEADER --- */
        
        .logo-area h1 { font-size: 1.7rem; font-weight: 800; }
        
        
        

        
        
        .btn-outline { background: transparent; color: var(--ulu-orange); border: 1.5px solid var(--ulu-orange); }
        .btn-outline:hover { background: var(--ulu-orange); color: white; }
        

        /* --- CONTENT --- */
        
        
        .profile-card {
            background: var(--white); border-radius: 25px; overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }

        .card-banner {
            background: var(--garden-black); padding: 40px; text-align: center; color: white;
        }
        .avatar-box {
            width: 70px; height: 70px; background: var(--ulu-orange); 
            border-radius: 20px; display: flex; align-items: center; 
            justify-content: center; margin: 0 auto 15px; font-size: 1.8rem;
        }

        .card-
        
        .section-label {
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase;
            color: var(--ulu-orange); letter-spacing: 1px; border-bottom: 1px solid #eee;
            padding-bottom: 10px; margin-bottom: 25px; display: block;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .full-wid

        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.85rem; color: #555; }
        .form-control {
            width: 100%; padding: 14px 18px; border-radius: 12px;
            border: 2px solid #f0f0f0; font-family: inherit; transition: 0.3s;
        }
        .form-control:focus { outline: none; border-color: var(--ulu-orange); background: #fffcf9; }

        .btn-save {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--ulu-orange); color: white; font-weight: 800;
            font-size: 1rem; cursor: pointer; transition: 0.3s;
            box-shadow: 0 8px 20px rgba(197, 168, 128, 0.2);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(197, 168, 128, 0.3); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 0.9rem; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
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
            <a href="admin_dashboard.php" class="nav-btn btn-outline"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="../logout.php" class="nav-btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="card-banner">
                <div class="avatar-box"><i class="fas fa-user-shield"></i></div>
                <h2 style="font-weight: 800; font-size: 1.8rem; letter-spacing: -1px;">Edit Profile</h2>
                <p style="opacity: 0.6; font-size: 0.85rem;">Update your personal details and account security</p>
            </div>

            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success_message ?></div>
                <?php endif; ?>

                <form method="POST">
                    <span class="section-label">General Information</span>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_no" class="form-control" value="<?= htmlspecialchars($user['phone_no']) ?>" required>
                        </div>
                    </div>

                    <span class="section-label">Password Update</span>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                        <p style="font-size: 0.75rem; color: #999; margin-top: 8px;">Keep blank if you don't wish to change your password.</p>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>