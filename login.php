<?php
session_start();
require 'db_connect.php';

// Jika user dah login, redirect ke page yang sepatutnya
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/admin_dashboard.php");
    exit();
}

$error_msg = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ------------------------------------------
    // 1. CARI DALAM JADUAL USERS (CUSTOMER)
    // ------------------------------------------
    $stmt = $conn->prepare("SELECT user_id, password, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // LOGIK PEMBAIKAN: Check Hash ATAU Plain Text
        if (password_verify($password, $user['password'])) {
            // A. Login berjaya (User Moden / Hash)
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'customer';
            header("Location: index.php");
            exit();
        } elseif ($password === $user['password']) {
            // B. Login berjaya (User Lama / Plain Text)
            // Tindakan: Auto-update ke hash supaya selamat lain kali
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $newHash, $user['user_id']);
            $updateStmt->execute();
            $updateStmt->close();

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'customer';
            header("Location: index.php");
            exit();
        }
    }
    $stmt->close();

    // ------------------------------------------
    // 2. JIKA BUKAN USER, CARI DALAM ADMINS
    // ------------------------------------------
    $stmt_admin = $conn->prepare("SELECT admin_id, password, fullname FROM admins WHERE username = ?");
    $stmt_admin->bind_param("s", $username);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($result_admin->num_rows === 1) {
        $admin = $result_admin->fetch_assoc();

        // Admin login logic (Hash atau Plain text)
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['full_name'] = $admin['fullname'];
            $_SESSION['role'] = 'admin';
            header("Location: admin/admin_dashboard.php");
            exit();
        }
    }
    $stmt_admin->close();

    // Jika sampai sini, login gagal
    $error_msg = __('login_err_incorrect');
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= __('nav_login') ?> | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        body.login-body {
            background-color: #f5f5f7;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .login-logo img {
            height: 60px;
            margin-bottom: 20px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 10px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #86868b;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .form-control-ios {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d2d2d7;
            border-radius: 12px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background: #fbfbfd;
        }

        .form-control-ios:focus {
            border-color: #C5A880;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(197, 168, 128, 0.1);
        }

        .btn-login-ios {
            width: 100%;
            padding: 14px;
            background: #1d1d1f;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login-ios:hover {
            background: #C5A880;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(197, 168, 128, 0.2);
        }

        .alert-custom {
            background: #fff2f2;
            color: #ff3b30;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #ffcccc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .register-link {
            margin-top: 25px;
            font-size: 14px;
            color: #86868b;
        }

        .register-link a {
            color: #C5A880;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            margin-top: 20px;
            display: block;
            font-size: 13px;
            color: #86868b;
            text-decoration: none;
        }

        .back-home:hover {
            color: #1d1d1f;
        }
    </style>
</head>

<body class="login-body">

    <div class="login-card">
        <div class="login-logo">
            <img src="img/logo.png?v=2" alt="EasyStay Logo">
        </div>
        <h2 class="login-title"><?= __('login_title') ?></h2>
        <p class="login-subtitle"><?= __('login_subtitle') ?></p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label"><?= __('login_username') ?></label>
                <input type="text" name="username" class="form-control-ios" placeholder="<?= __('login_username_placeholder') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('login_password') ?></label>
                <input type="password" name="password" class="form-control-ios" placeholder="<?= __('login_password_placeholder') ?>" required>
            </div>

        <div style="text-align:right; margin-top:8px; margin-bottom:4px;">
            <a href="forgot_password.php" style="font-size:12px; color:#C5A880; text-decoration:none; font-weight:600;"><?= __('login_forgot') ?></a>
        </div>

        <button type="submit" name="login" class="btn-login-ios"><?= __('login_btn') ?></button>
    </form>

    <div class="register-link">
        <?= __('login_no_account') ?> <a href="register.php"><?= __('login_create') ?></a>
    </div>


        <a href="index.php" class="back-home"><i class="fas fa-arrow-left mr-1"></i> <?= __('login_back') ?></a>
    </div>

</body>

</html>
