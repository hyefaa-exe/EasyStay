<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

$status_msg = "";
$status_type = ""; // success or error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to = "easystaysupport@gmail.com";

    $from     = $_POST['email'] ?? '';
    $name     = $_POST['name'] ?? '';
    $subject  = $_POST['subject'] ?? 'No Subject';
    $number   = $_POST['number'] ?? 'N/A';
    $cmessage = $_POST['message'] ?? '';

    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $email_subject = "EasyStay Support: " . $subject;

    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Inquiry</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #ff4d00;'>New Contact Message</h2>
            <hr>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$from}</p>
            <p><strong>Phone:</strong> {$number}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong></p>
            <div style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>
                " . nl2br($cmessage) . "
            </div>
        </div>
    </body>
    </html>";

    if (@mail($to, $email_subject, $body, $headers)) {
        $status_msg = "Message sent successfully! We will get back to you soon.";
        $status_type = "success";
    } else {
        $status_msg = "Message could not be sent (SMTP error). If you are on localhost, this is normal.";
        $status_type = "danger";
    }
}
?>
<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Contact Support | EasyStay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>

<body>

    <header class="header-area">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-5 d-none d-lg-block">
                    <nav>
                        <ul id="navigation">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="package.php">Package</a></li>
                            <li><a href="about.php">About</a></li>
                            <li><a href="gallery.php">Gallery</a></li>
                            <li><a href="contact.php" class="active-link">Contact</a></li>
                            <?php if ($is_logged_in): ?>
                                <li><a href="my_profile.php">My Profile</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-2 col-lg-2 text-center">
                    <a href="index.php" class="logo-link"><img src="img/logo.png?v=2" alt="Logo" style="height: 50px;"></a>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="header-right-part d-flex justify-content-end align-items-center">
                        <ul class="social-icons-head d-flex list-unstyled m-0 mr-4">
                            <li class="mr-3"><a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank" style="color:white;"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="https://www.tiktok.com/@easystayhomestay" target="_blank" style="color:white;"><i class="fa-brands fa-tiktok"></i></a></li>
                        </ul>
                        <?php if ($is_logged_in): ?>
                            <a href="logout.php" class="auth-btn">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="auth-btn">Login / Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container text-center" style="padding: 100px 0;">
            <div class="status-card" style="max-width: 600px; margin: 0 auto;">
                <?php if ($status_type == 'success'): ?>
                    <div class="status-icon icon-success"><i class="fas fa-check-circle"></i></div>
                    <h2 class="font-weight-bold">Thank You!</h2>
                    <p><?= $status_msg ?></p>
                <?php else: ?>
                    <div class="status-icon icon-error"><i class="fas fa-exclamation-circle"></i></div>
                    <h2 class="font-weight-bold">Oops!</h2>
                    <p><?= $status_msg ?></p>
                <?php endif; ?>

                <a href="contact.php" class="btn btn-secondary mt-4">Back to Contact</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>EASYSTAY</h3>
                    <p>Lot 8012, Kampung Binjai Kertas,</p>
                    <p>21700 Kuala Berang, Terengganu.</p>
                    <div class="footer-social-icons">
                        <a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.tiktok.com/@easystayhomestay" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>CONTACT US</h3>
                    <p><i class="fas fa-phone-alt mr-2"></i> +60 19 211 9223</p>
                    <p><i class="fas fa-envelope mr-2"></i> reservation@easystay.com</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>NAVIGATION</h3>
                    <a href="index.php">Home</a>
                    <a href="package.php">Package</a>
                    <a href="about.php">About</a>
                    <a href="gallery.php">Gallery</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>NEWSLETTER</h3>
                    <p>Subscribe to get latest offers.</p>
                    <div class="newsletter-box">
                        <input type="email" placeholder="Your email">
                        <button type="button">Sign Up</button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>Copyright EasyStay © 2025. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
