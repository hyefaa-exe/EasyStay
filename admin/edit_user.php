<?php
// Database connection
require 'db_connect.php';

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate User ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);

if ($user_id <= 0) {
    die("Invalid User ID.");
}

$success_message = "";
$error_message = "";

// Fetch user details
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone_no = trim($_POST['phone_no']);

    if (empty($fullname) || empty($email) || empty($phone_no)) {
        $error_message = "All fields are required.";
    } else {
        $update = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $update->bind_param("sssi", $fullname, $email, $phone_no, $user_id);

        if ($update->execute()) {
            require_once 'admin_logger.php';
            logAdminAction($conn, $_SESSION['admin_id'], 'UPDATE_USER', "Updated information for user '$fullname'", $user_id, 'user');
            $success_message = "User information updated successfully!";
            // Update local data for immediate display
            $user['full_name'] = $fullname;
            $user['email'] = $email;
            $user['phone'] = $phone_no;
            header("refresh:2;url=view_users.php");
        } else {
            $error_message = "Error updating user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | EasyStay Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="../img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            z-index: 10;
        }
        .input-wrapper .form-control {
            padding-left: 42px !important;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .info-item {
            background: var(--slate-50);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--slate-200);
        }
        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--slate-400);
            font-weight: 700;
            margin-bottom: 4px;
        }
        .info-value {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--slate-800);
        }
        .highlight {
            color: var(--gold);
        }
        .form-section {
            background: var(--slate-50);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--slate-200);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Edit User Settings</div>
            <div class="topbar-breadcrumb">Manage information for Customer #<?php echo $user['user_id']; ?></div>
        </div>
        <div class="topbar-right">
            <a href="view_users.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="admin-content" style="max-width: 680px;">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- User Settings Banner Card -->
        <div class="card" style="margin-bottom:20px;">
            <div style="background: var(--slate-900); padding: 32px; text-align: center; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div style="width:64px; height:64px; background:var(--gold-light); border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; font-size:1.8rem; color:var(--gold);">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h2 style="font-size:1.3rem; font-weight:800; color:var(--white); margin-bottom:4px;"><?= htmlspecialchars($user['full_name']) ?></h2>
                <p style="color:var(--slate-400); font-size:0.82rem;">Registered Customer</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Edit User Information</h3>
            </div>

            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <p class="info-label">Current Full Name</p>
                        <p class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Current Email</p>
                        <p class="info-value highlight"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="form-section">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="fullname">Full Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fullname" id="fullname" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="phone_no" id="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="update_user" class="btn-save" id="submitBtn" style="margin-top: 8px;">
                            <i class="fas fa-save"></i> UPDATE USER DATA
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Loading effect on submit
    document.getElementById('editForm').onsubmit = function() {
        const btn = document.getElementById('submitBtn');
        btn.style.opacity = '0.7';
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> UPDATING...';
    };
</script>
</body>
</html>