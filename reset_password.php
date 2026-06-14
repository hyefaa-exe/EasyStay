<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$token    = trim($_GET['token'] ?? '');
$msg      = '';
$msg_type = '';
$valid    = false;

if (empty($token)) {
    header("Location: forgot_password.php");
    exit();
}

// Sahkan token — cari dalam DB dan pastikan belum luput
$stmt = $conn->prepare(
    "SELECT pr.user_id, pr.expires_at, u.full_name, u.email 
     FROM password_resets pr 
     JOIN users u ON pr.user_id = u.user_id
     WHERE pr.token = ? AND pr.expires_at > NOW()"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();

if (!$reset) {
    $msg      = __('reset_err_token');
    $msg_type = "error";
} else {
    $valid = true;
}

// Proses reset kata laluan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $valid) {
    $new_password    = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 8) {
        $msg      = __('reset_err_length');
        $msg_type = "error";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $msg      = __('reset_err_uppercase');
        $msg_type = "error";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $msg      = __('reset_err_number');
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $msg      = __('reset_err_match');
        $msg_type = "error";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $user_id = $reset['user_id'];

        // Update kata laluan
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $upd->bind_param("si", $hashed, $user_id);

        if ($upd->execute()) {
            // Padam token (satu kali guna)
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();

            $msg      = __('reset_success');
            $msg_type = "success";
            $valid    = false; // Sorokkan form
        } else {
            $msg      = __('reset_err_failed');
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('reset_title') ?> | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f5f7; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .card { background: #fff; width: 100%; max-width: 420px; padding: 40px 32px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); text-align: center; }
        .logo img { height: 55px; margin-bottom: 20px; }
        h2 { font-size: 1.5rem; font-weight: 800; color: #1d1d1f; margin-bottom: 8px; }
        p.sub { font-size: 0.88rem; color: #6b7280; margin-bottom: 28px; }
        .form-group { margin-bottom: 18px; text-align: left; }
        label { display: block; font-size: 0.72rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 7px; }
        input[type=password] { width: 100%; padding: 13px 15px; border: 1.5px solid #e5e7eb; border-radius: 12px; font-size: 15px; font-family: inherit; outline: none; transition: 0.3s; background: #fafaf9; }
        input[type=password]:focus { border-color: #C5A880; box-shadow: 0 0 0 4px rgba(197,168,128,0.1); }
        .btn { width: 100%; padding: 14px; background: #1d1d1f; color: #fff; border: none; border-radius: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; font-family: inherit; }
        .btn:hover { background: #C5A880; transform: translateY(-2px); }
        .alert { padding: 14px 16px; border-radius: 12px; font-size: 0.88rem; margin-bottom: 20px; text-align: left; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .links { margin-top: 20px; font-size: 0.85rem; color: #6b7280; }
        .links a { color: #C5A880; font-weight: 600; text-decoration: none; }
        .strength { font-size: 11px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png?v=2" alt="EasyStay Logo"></a>
        </div>
        <h2><?= __('reset_heading') ?></h2>
        <?php if ($valid): ?>
            <p class="sub"><?= sprintf(__('reset_sub_hi'), htmlspecialchars($reset['full_name'])) ?></p>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($valid): ?>
        <form method="POST">
            <div class="form-group">
                <label><?= __('reset_new_password') ?></label>
                <input type="password" name="new_password" id="pwd" placeholder="<?= __('reset_password_placeholder') ?>" required>
                <div id="pwd-strength" class="strength"></div>
            </div>
            <div class="form-group">
                <label><?= __('reset_confirm_password') ?></label>
                <input type="password" name="confirm_password" id="cpwd" placeholder="<?= __('reset_confirm_password_placeholder') ?>" required>
                <div id="pwd-match" class="strength"></div>
            </div>
            <button type="submit" name="reset_password" class="btn">
                <i class="fas fa-key" style="margin-right:8px;"></i> <?= __('reset_btn') ?>
            </button>
        </form>
        <?php elseif ($msg_type === 'success'): ?>
            <a href="login.php" class="btn" style="display:block; text-decoration:none; margin-top:8px;">
                <i class="fas fa-sign-in-alt" style="margin-right:8px;"></i> <?= __('reset_go_login') ?>
            </a>
        <?php else: ?>
            <a href="forgot_password.php" class="btn" style="display:block; text-decoration:none; margin-top:8px;">
                <?= __('reset_request_new') ?>
            </a>
        <?php endif; ?>

        <div class="links"><a href="login.php"><i class="fas fa-arrow-left"></i> <?= __('reset_back_login') ?></a></div>
    </div>

    <script>
        // Localized JS variables
        const labelWeak = <?= json_encode(__('js_strength_weak')) ?>;
        const labelMedium = <?= json_encode(__('js_strength_medium')) ?>;
        const labelGood = <?= json_encode(__('js_strength_good')) ?>;
        const labelStrong = <?= json_encode(__('js_strength_strong')) ?>;
        const labelNeed = <?= json_encode(__('js_strength_need')) ?>;
        const labelMinChar = <?= json_encode(__('js_strength_min_char')) ?>;
        const labelUppercase = <?= json_encode(__('js_strength_uppercase')) ?>;
        const labelNumber = <?= json_encode(__('js_strength_number')) ?>;
        const labelMatch = <?= json_encode(__('js_pass_match')) ?>;
        const labelNotMatch = <?= json_encode(__('js_pass_not_match')) ?>;

        const pwdEl = document.getElementById('pwd');
        const cpwdEl = document.getElementById('cpwd');
        if (pwdEl) {
            pwdEl.addEventListener('input', function() {
                const v = this.value, el = document.getElementById('pwd-strength');
                let s = 0, tips = [];
                if (v.length >= 8) s++; else tips.push(labelMinChar);
                if (/[A-Z]/.test(v)) s++; else tips.push(labelUppercase);
                if (/[0-9]/.test(v)) s++; else tips.push(labelNumber);
                const lbl = ['', '⚠️ ' + labelWeak, '⚠️ ' + labelMedium, '✅ ' + labelGood, '✅ ' + labelStrong];
                const col = ['', '#e74c3c', '#f39c12', '#27ae60', '#1e8449'];
                el.style.color = col[s]; el.innerHTML = s > 0 ? lbl[s] + (tips.length ? ' — ' + labelNeed + ': ' + tips.join(', ') : '') : '';
            });
            cpwdEl.addEventListener('input', function() {
                const el = document.getElementById('pwd-match');
                if (!this.value) { el.innerHTML = ''; return; }
                el.style.color = pwdEl.value === this.value ? '#27ae60' : '#e74c3c';
                el.innerHTML = pwdEl.value === this.value ? '✅ ' + labelMatch : '❌ ' + labelNotMatch;
            });
        }
    </script>
</body>
</html>
