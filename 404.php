<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | EasyStay</title>
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fafaf9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: clamp(80px, 18vw, 140px);
            font-weight: 800;
            background: linear-gradient(135deg, #C5A880, #8B6940);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            letter-spacing: -4px;
        }
        .icon-wrap {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #C5A880, #8B6940);
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            margin: 20px auto;
            font-size: 2rem; color: white;
            box-shadow: 0 12px 30px rgba(197,168,128,0.3);
        }
        h1 { font-size: 1.6rem; font-weight: 800; color: #1d1d1f; margin-bottom: 12px; }
        p { color: #6b7280; font-size: 0.95rem; line-height: 1.7; margin-bottom: 30px; }
        .btn-home {
            display: inline-flex; align-items: center; gap: 8px;
            background: #1d1d1f; color: white;
            padding: 14px 28px; border-radius: 14px;
            font-weight: 700; font-size: 0.95rem;
            text-decoration: none; transition: 0.3s;
        }
        .btn-home:hover { background: #C5A880; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(197,168,128,0.3); color: white; text-decoration: none; }
        .links { margin-top: 24px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .link-sm { color: #C5A880; font-weight: 600; font-size: 0.88rem; text-decoration: none; }
        .link-sm:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <div class="icon-wrap"><i class="fas fa-compass"></i></div>
        <h1>Page Not Found</h1>
        <p>Sorry, the page you are looking for doesn't exist or has been moved. Please check the URL or navigate back to safety.</p>

        <a href="index.php" class="btn-home">
            <i class="fas fa-home"></i> Back to Home
        </a>

        <div class="links">
            <a href="package.php" class="link-sm"><i class="fas fa-bed"></i> Our Packages</a>
            <a href="gallery.php" class="link-sm"><i class="fas fa-images"></i> Gallery</a>
            <a href="contact.php" class="link-sm"><i class="fas fa-envelope"></i> Contact Us</a>
            <?php if ($is_logged_in): ?>
                <a href="my_profile.php" class="link-sm"><i class="fas fa-user"></i> My Profile</a>
            <?php else: ?>
                <a href="login.php" class="link-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
