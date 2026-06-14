<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$error_msg = '';

if (isset($_POST['register'])) {
    $full_name       = strip_tags(trim($_POST['full_name']));
    $username        = strip_tags(trim($_POST['username']));
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $password        = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // === VALIDASI ===
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error_msg = __('register_err_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = __('register_err_email');
    } elseif (!preg_match('/^(01[0-9])\d{7,8}$/', $phone)) {
        $error_msg = __('register_err_phone');
    } elseif (strlen($password) < 8) {
        $error_msg = __('register_err_pass_length');
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_msg = __('register_err_pass_upper');
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error_msg = __('register_err_pass_num');
    } elseif ($password !== $confirm_password) {
        $error_msg = __('register_err_pass_match');
    } else {
        // Semak username/email duplikat
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_msg = __('register_err_duplicate');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("sssss", $full_name, $username, $email, $phone, $hashed_password);
                if ($stmt->execute()) {
                    $_SESSION['user_id']   = $conn->insert_id;
                    $_SESSION['username']  = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role']      = 'customer';
                    header("Location: index.php");
                    exit();
                } else {
                    $error_msg = __('register_err_failed');
                }
                $stmt->close();
            } else {
                $error_msg = __('register_err_db');
            }
        }
        $check_stmt->close();
    }
}
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= __('register_title') ?> | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        body.login-body {
            background-color: #f5f5f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px 0;
            /* Tambah padding sikit sebab form register panjang */
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            /* Lebarkan sikit dari login sebab input banyak */
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
            <a href="index.php"><img src="img/logo.png?v=2" alt="EasyStay Logo"></a>
        </div>
        <h2 class="login-title"><?= __('register_title') ?></h2>
        <p class="login-subtitle"><?= __('register_subtitle') ?></p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label"><?= __('register_fullname') ?></label>
                <input type="text" name="full_name" class="form-control-ios" placeholder="<?= __('register_fullname_placeholder') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('register_username') ?></label>
                <input type="text" name="username" class="form-control-ios" placeholder="<?= __('register_username_placeholder') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('register_email') ?></label>
                <input type="email" name="email" class="form-control-ios" placeholder="<?= __('register_email_placeholder') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('register_phone') ?></label>
                <input type="text" name="phone" class="form-control-ios" placeholder="<?= __('register_phone_placeholder') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('register_password') ?></label>
                <input type="password" name="password" id="password" class="form-control-ios" placeholder="<?= __('register_password_placeholder') ?>" required>
                <div id="pwd-strength" style="font-size:11px; margin-top:5px; color:#888;"></div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('register_confirm_password') ?></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control-ios" placeholder="<?= __('register_confirm_password_placeholder') ?>" required>
                <div id="pwd-match" style="font-size:11px; margin-top:5px;"></div>
            </div>

            <button type="submit" name="register" class="btn-login-ios"><?= __('register_btn') ?></button>
        </form>

        <div class="register-link">
            <?= __('register_already_account') ?> <a href="login.php"><?= __('register_sign_in') ?></a>
        </div>

        <a href="index.php" class="back-home"><i class="fas fa-arrow-left mr-1"></i> <?= __('login_back') ?></a>
    </div>

    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
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

        // Password Strength Checker
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const el  = document.getElementById('pwd-strength');
            let score = 0;
            let tips  = [];

            if (val.length >= 8)          score++; else tips.push(labelMinChar);
            if (/[A-Z]/.test(val))        score++; else tips.push(labelUppercase);
            if (/[0-9]/.test(val))        score++; else tips.push(labelNumber);
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const labels = ['', '⚠️ ' + labelWeak, '⚠️ ' + labelMedium, '✅ ' + labelGood, '✅ ' + labelStrong];
            const colors = ['', '#e74c3c', '#f39c12', '#27ae60', '#1e8449'];
            el.style.color = colors[score] || '#888';
            el.innerHTML = score > 0 ? labels[score] + (tips.length ? ' — ' + labelNeed + ': ' + tips.join(', ') : '') : '';

            checkMatch();
        });

        // Confirm Password Matcher
        function checkMatch() {
            const pwd  = document.getElementById('password').value;
            const conf = document.getElementById('confirm_password').value;
            const el   = document.getElementById('pwd-match');
            if (!conf) { el.innerHTML = ''; return; }
            if (pwd === conf) {
                el.style.color = '#27ae60';
                el.innerHTML   = '✅ ' + labelMatch;
            } else {
                el.style.color = '#e74c3c';
                el.innerHTML   = '❌ ' + labelNotMatch;
            }
        }
        document.getElementById('confirm_password').addEventListener('input', checkMatch);
    </script>
</body>

</html>
