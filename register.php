<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$error_msg = '';

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];

    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_msg = "Username or Email already registered!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (full_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssss", $full_name, $username, $email, $phone, $hashed_password);
            if ($stmt->execute()) {
                $_SESSION['user_id']  = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Registration failed. Please try again.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database error.";
        }
    }
    $check_stmt->close();
}
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register | Ulu Garden Homestay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

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
            border-color: #ff7b00;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255, 123, 0, 0.1);
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
            background: #ff7b00;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 123, 0, 0.2);
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
            color: #ff7b00;
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
            <a href="index.php"><img src="img/logo.png" alt="Ulu Garden Logo"></a>
        </div>
        <h2 class="login-title">Create Account</h2>
        <p class="login-subtitle">Join us for a better experience</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control-ios" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control-ios" placeholder="Choose a username" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control-ios" placeholder="name@example.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control-ios" placeholder="e.g. 0123456789" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control-ios" placeholder="Create a strong password" required>
            </div>

            <button type="submit" name="register" class="btn-login-ios">Register Now</button>
        </form>

        <div class="register-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>

        <a href="index.php" class="back-home"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
    </div>

    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>