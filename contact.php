<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Contact Us | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
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
        <section class="welcome-section">
            <div class="container text-center">
                <h1 class="welcome-title">Find Us</h1>
                <p class="welcome-subtitle">EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation</p>
                <div class="map-wrapper">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3971.123456789!2d103.0326159!3d5.1337392!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31b7c5afaca70901%3A0x6dcf7258498a54d9!2sEasyStay!5e0!3m2!1sen!2smy!4v1710000000000!5m2!1sen!2smy"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </section>

        <section class="contact-section-padding" style="padding: 60px 0;">
            <div class="container">
                <div class="row">
                    <div class="col-12 mb-5">
                        <h2 style="font-weight: 800; font-size: 2.5rem;">Connect With Us</h2>
                    </div>

                    <div class="col-lg-8">
                        <form action="contact_support.php" method="post" class="contact-form-wrapper">
                            <div class="row">
                                <div class="col-12">
                                    <textarea name="message" class="form-textarea-custom" rows="6" placeholder="Enter Message" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <input name="name" class="form-input-custom" type="text" placeholder="Enter your name" required>
                                </div>
                                <div class="col-md-6">
                                    <input name="email" class="form-input-custom" type="email" placeholder="Email" required>
                                </div>
                                <div class="col-md-12">
                                    <input name="number" class="form-input-custom" type="text" placeholder="Phone Number" required>
                                </div>
                                <div class="col-12">
                                    <input name="subject" class="form-input-custom" type="text" placeholder="Enter Subject" required>
                                </div>
                            </div>
                            <button type="submit" class="auth-btn" style="border:none; width: 200px; height: 55px; cursor: pointer;">
                                Send Message
                            </button>
                        </form>
                    </div>

                    <div class="col-lg-4">
                        <div class="info-list-wrapper pl-lg-4">
                            <div class="info-item d-flex mb-4">
                                <i class="fa-solid fa-location-dot mr-3 mt-1"></i>
                                <div>
                                    <h3>Kampung Binjai Kertas</h3>
                                    <p>Kuala Berang, Terengganu, Malaysia</p>
                                </div>
                            </div>
                            <div class="info-item d-flex mb-4">
                                <i class="fa-solid fa-phone mr-3 mt-1"></i>
                                <div>
                                    <h3>+6019 916 8870</h3>
                                    <p>Mon to Fri 9am to 6pm</p>
                                </div>
                            </div>
                            <div class="info-item d-flex mb-4">
                                <i class="fa-solid fa-envelope mr-3 mt-1"></i>
                                <div>
                                    <h3>easystaysupport@gmail.com</h3>
                                    <p>Send us your query anytime!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
