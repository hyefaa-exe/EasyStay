<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$msg      = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg      = "Format email tidak sah.";
        $msg_type = "error";
    } else {
        // Semak sama ada email wujud dalam database
        $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Jana token reset yang selamat
            $token     = bin2hex(random_bytes(32));
            $expires   = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $user_id   = $user['user_id'];

            // Simpan token ke database (hapus token lama dulu)
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();

            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user_id, $token, $expires);

            if ($ins->execute()) {
                // Hantar email reset
                try {
                    require_once 'admin/email.php';
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/EasyStay/reset_password.php?token=$token";
                    $emailBody  = "
                        <div style='font-family:Arial,sans-serif; max-width:600px; margin:0 auto;'>
                            <div style='background:linear-gradient(135deg,#1d1d1f,#333); padding:30px; text-align:center; border-radius:12px 12px 0 0;'>
                                <h1 style='color:#C5A880; margin:0; font-size:24px;'>EasyStay</h1>
                                <p style='color:#ccc; margin:8px 0 0; font-size:13px;'>Password Reset Request</p>
                            </div>
                            <div style='background:#fff; padding:30px; border:1px solid #e5e7eb; border-top:none; border-radius:0 0 12px 12px;'>
                                <h2 style='color:#1d1d1f; font-size:20px;'>Hi, " . htmlspecialchars($user['full_name']) . " 👋</h2>
                                <p style='color:#6b7280; line-height:1.7;'>We received a request to reset your EasyStay account password. Click the button below to reset it.</p>
                                <div style='text-align:center; margin:30px 0;'>
                                    <a href='$reset_link' style='background:#1d1d1f; color:white; padding:14px 32px; border-radius:10px; text-decoration:none; font-weight:700; display:inline-block;'>Reset My Password</a>
                                </div>
                                <p style='color:#6b7280; font-size:13px;'>This link will expire in <strong>1 hour</strong>. If you did not request a password reset, please ignore this email.</p>
                                <hr style='border:none; border-top:1px solid #e5e7eb; margin:20px 0;'>
                                <p style='color:#9ca3af; font-size:12px; text-align:center;'>EasyStay Homestay &bull; Kuala Berang, Terengganu</p>
                            </div>
                        </div>
                    ";
                    sendBookingStatusEmail($email, $user['full_name'], $emailBody);
                    $msg      = "✅ Email reset kata laluan telah dihantar ke <strong>$email</strong>. Sila semak inbox anda (dan folder Spam).";
                    $msg_type = "success";
                } catch (Exception $e) {
                    $msg      = "Gagal menghantar email. Sila hubungi admin.";
                    $msg_type = "error";
                }
            }
        } else {
            // Sengaja tunjuk mesej yang sama (keselamatan — jangan dedah email mana wujud)
            $msg      = "✅ Jika email ini berdaftar, kami akan menghantar pautan reset. Sila semak inbox anda.";
            $msg_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f5f7; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .card { background: #fff; width: 100%; max-width: 420px; padding: 40px 32px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); text-align: center; }
        .logo img { height: 55px; margin-bottom: 20px; }
        h2 { font-size: 1.5rem; font-weight: 800; color: #1d1d1f; margin-bottom: 8px; }
        p.sub { font-size: 0.88rem; color: #6b7280; margin-bottom: 28px; line-height: 1.6; }
        .form-group { margin-bottom: 18px; text-align: left; }
        label { display: block; font-size: 0.72rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 7px; }
        input[type=email] { width: 100%; padding: 13px 15px; border: 1.5px solid #e5e7eb; border-radius: 12px; font-size: 15px; font-family: inherit; outline: none; transition: 0.3s; background: #fafaf9; }
        input[type=email]:focus { border-color: #C5A880; box-shadow: 0 0 0 4px rgba(197,168,128,0.1); background: #fff; }
        .btn { width: 100%; padding: 14px; background: #1d1d1f; color: #fff; border: none; border-radius: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; font-family: inherit; }
        .btn:hover { background: #C5A880; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(197,168,128,0.2); }
        .alert { padding: 14px 16px; border-radius: 12px; font-size: 0.88rem; margin-bottom: 20px; text-align: left; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .links { margin-top: 24px; font-size: 0.85rem; color: #6b7280; }
        .links a { color: #C5A880; font-weight: 600; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png?v=2" alt="EasyStay Logo"></a>
        </div>
        <h2>Forgot Password?</h2>
        <p class="sub">No worries! Enter your email address and we'll send you a link to reset your password.</p>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($msg_type !== 'success'): ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" required>
            </div>
            <button type="submit" name="request_reset" class="btn">
                <i class="fas fa-paper-plane" style="margin-right:8px;"></i> Send Reset Link
            </button>
        </form>
        <?php endif; ?>

        <div class="links" style="margin-top: 20px;">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
