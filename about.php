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
    <title>About Us | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
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
                            <li><a href="about.php" class="active-link">About</a></li>
                            <li><a href="gallery.php">Gallery</a></li>
                            <li><a href="contact.php">Contact</a></li>
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
            <div class="container">
                <div class="about-info-content text-center">
                    <h1 class="welcome-title">About EasyStay</h1>
                    <p class="welcome-subtitle">Your Nature Retreat in Kuala Berang</p>
                    <p class="welcome-description mx-auto" style="max-width: 800px;">
                        Escape the busy city life and discover peace at EasyStay. Surrounded by lush greenery and fresh air, it’s the ideal place for families, friends, and groups to relax and enjoy time together. With cozy chalets, a fully equipped homestay, a private pool, BBQ area, and complete facilities, every moment here is made for comfort and joy.
                    </p>
                </div>
            </div>
        </section>

        <div class="about_area" style="padding-bottom: 60px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-5 col-lg-5">
                        <div class="about_info">
                            <div class="section_title">
                                <h3 style="font-weight: 800; font-size: 32px; margin-bottom: 20px;">A Luxurious Chalet <br> with Nature</h3>
                            </div>
                            <p>At EasyStay, we offer an exclusive package featuring three cozy chalets, each facing the pool and surrounded by nature, designed for up to three guests with a luxurious interior, a comfortable bed, private bathroom, and modern facilities.</p>
                        </div>
                    </div>
                    <div class="col-xl-7 col-lg-7">
                        <div class="about_thumb d-flex">
                            <div class="img_1 mr-2">
                                <img src="img/chalet day.jpg" alt="Chalet Day" class="img-fluid" style="border-radius: 15px;">
                            </div>
                            <div class="img_2">
                                <img src="img/chalet night.jpg" alt="Chalet Night" class="img-fluid" style="border-radius: 15px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="about_area" style="padding-bottom: 100px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-7 col-lg-7 order-2 order-lg-1">
                        <div class="about_thumb2 d-flex">
                            <div class="img_1 mr-2">
                                <img src="img/homestay night.jpg" alt="Homestay Night" class="img-fluid" style="border-radius: 15px;">
                            </div>
                            <div class="img_2">
                                <img src="img/homestay day.jpg" alt="Homestay Day" class="img-fluid" style="border-radius: 15px;">
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5 col-lg-5 order-1 order-lg-2">
                        <div class="about_info">
                            <div class="section_title">
                                <h3 style="font-weight: 800; font-size: 32px; margin-bottom: 20px;">A Perfect Homestay Retreat Surrounded by Nature</h3>
                            </div>
                            <p>EasyStay also offers a spacious homestay included in the package, accommodating up to 30 people with breathtaking nature views, a private pool, and nearby chalets. The homestay features a fully equipped kitchen, BBQ area, three bedrooms, three bathrooms, and complete modern facilities.</p>
                        </div>
                    </div>
                </div>
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