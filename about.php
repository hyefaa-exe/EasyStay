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
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>

<body>

        <header class="header-area">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-5 d-none d-lg-block">
                    <nav>
                        <ul id="navigation">
                            <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active-link' : '' ?>"><?= __('nav_home') ?></a></li>
                            <li><a href="package.php" class="<?= $current_page == 'package.php' ? 'active-link' : '' ?>"><?= __('nav_package') ?></a></li>
                            <li><a href="about.php" class="<?= $current_page == 'about.php' ? 'active-link' : '' ?>"><?= __('nav_about') ?></a></li>
                            <li><a href="gallery.php" class="<?= $current_page == 'gallery.php' ? 'active-link' : '' ?>"><?= __('nav_gallery') ?></a></li>
                            <li><a href="contact.php" class="<?= $current_page == 'contact.php' ? 'active-link' : '' ?>"><?= __('nav_contact') ?></a></li>
                            <?php if ($is_logged_in): ?>
                                <li><a href="my_profile.php" class="<?= $current_page == 'my_profile.php' ? 'active-link' : '' ?>"><?= __('nav_profile') ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-2 col-lg-2 text-center">
                    <a href="index.php" class="logo-link"><img src="img/logo.png?v=2" alt="Logo" style="height: 50px;"></a>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="header-right-part d-flex justify-content-end align-items-center">
                        <div class="lang-selector mr-4 d-flex align-items-center" style="gap: 8px;">
                            <a href="<?= get_lang_url('en') ?>" style="color: <?= $lang_code == 'en' ? '#C5A880' : 'rgba(255,255,255,0.6)' ?>; font-weight: 700; font-size: 13px; text-decoration: none; border-bottom: <?= $lang_code == 'en' ? '2px solid #C5A880' : 'none' ?>; padding-bottom: 2px;">EN</a>
                            <span style="color: rgba(255,255,255,0.3); font-size: 13px;">|</span>
                            <a href="<?= get_lang_url('ms') ?>" style="color: <?= $lang_code == 'ms' ? '#C5A880' : 'rgba(255,255,255,0.6)' ?>; font-weight: 700; font-size: 13px; text-decoration: none; border-bottom: <?= $lang_code == 'ms' ? '2px solid #C5A880' : 'none' ?>; padding-bottom: 2px;">BM</a>
                        </div>
                        <ul class="social-icons-head d-flex list-unstyled m-0 mr-4">
                            <li class="mr-3"><a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank" style="color:white;"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="https://www.tiktok.com/@easystayhomestay" target="_blank" style="color:white;"><i class="fa-brands fa-tiktok"></i></a></li>
                        </ul>
                        <?php if ($is_logged_in): ?>
                            <a href="logout.php" class="auth-btn"><?= __('nav_logout') ?></a>
                        <?php else: ?>
                            <a href="login.php" class="auth-btn"><?= __('nav_login') ?></a>
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
                    <h1 class="welcome-title"><?= __('about_title') ?></h1>
                    <p class="welcome-subtitle"><?= __('about_subtitle') ?></p>
                    <p class="welcome-description mx-auto" style="max-width: 800px;">
                        <?= __('about_desc') ?>
                    </p>
                </div>
            </div>
        </section>

        <div class="about_area" style="padding-top: 80px; padding-bottom: 60px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-5 col-lg-5">
                        <div class="about_info">
                            <div class="section_title">
                                <h3 style="font-weight: 800; font-size: 32px; margin-bottom: 20px;"><?= str_replace('Elegant ', 'Elegant <br> ', __('about_chalet_title')) ?></h3>
                            </div>
                            <p class="mb-4" style="font-size: 15px; color: #555; line-height: 1.6;"><?= __('about_chalet_desc') ?></p>
                            
                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_chalet_f1_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_chalet_f1_desc') ?></p>
                                </div>
                            </div>

                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-water"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_chalet_f2_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_chalet_f2_desc') ?></p>
                                </div>
                            </div>

                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-wifi"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_chalet_f3_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_chalet_f3_desc') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-7 col-lg-7">
                        <div class="about_thumb d-flex">
                            <div class="img_1 mr-2">
                                <img src="img/chalet day.jpg?v=<?= time() ?>" alt="Chalet Day" class="img-fluid" style="border-radius: 15px;">
                            </div>
                            <div class="img_2">
                                <img src="img/chalet night.jpg?v=<?= time() ?>" alt="Chalet Night" class="img-fluid" style="border-radius: 15px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="about_area" style="padding-top: 80px; padding-bottom: 100px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-xl-7 col-lg-7 order-2 order-lg-1">
                        <div class="about_thumb2 d-flex">
                            <div class="img_1 mr-2">
                                <img src="img/homestay night.jpg?v=<?= time() ?>" alt="Homestay Night" class="img-fluid" style="border-radius: 15px;">
                            </div>
                            <div class="img_2">
                                <img src="img/homestay day.jpg?v=<?= time() ?>" alt="Homestay Day" class="img-fluid" style="border-radius: 15px;">
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5 col-lg-5 order-1 order-lg-2">
                        <div class="about_info">
                            <div class="section_title">
                                <h3 style="font-weight: 800; font-size: 32px; margin-bottom: 20px;"><?= __('about_home_title') ?></h3>
                            </div>
                            <p class="mb-4" style="font-size: 15px; color: #555; line-height: 1.6;"><?= __('about_home_desc') ?></p>

                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-house-user"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_home_f1_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_home_f1_desc') ?></p>
                                </div>
                            </div>

                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-utensils"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_home_f2_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_home_f2_desc') ?></p>
                                </div>
                            </div>

                            <div class="about-feature-item d-flex align-items-start mb-4" style="gap: 16px;">
                                <div class="feature-icon" style="font-size: 18px; color: #C5A880; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: rgba(197, 168, 128, 0.08); border-radius: 10px; flex-shrink: 0;">
                                    <i class="fa-solid fa-circle-nodes"></i>
                                </div>
                                <div class="feature-details">
                                    <h4 style="font-size: 15px; font-weight: 700; color: #1c1c1c; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;"><?= __('about_home_f3_title') ?></h4>
                                    <p style="font-size: 13px; color: #6e6e73; line-height: 1.5; margin: 0;"><?= __('about_home_f3_desc') ?></p>
                                </div>
                            </div>
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
                    <p><?= __('footer_desc') ?></p>
                    <div class="footer-social-icons">
                        <a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.tiktok.com/@easystayhomestay" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3><?= __('footer_contact') ?></h3>
                    <p><i class="fas fa-phone-alt mr-2"></i> +60 19 211 9223</p>
                    <p><i class="fas fa-envelope mr-2"></i> reservation@easystay.com</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3><?= __('footer_nav') ?></h3>
                    <a href="index.php"><?= __('nav_home') ?></a>
                    <a href="package.php"><?= __('nav_package') ?></a>
                    <a href="about.php"><?= __('nav_about') ?></a>
                    <a href="gallery.php"><?= __('nav_gallery') ?></a>
                    <a href="contact.php"><?= __('nav_contact') ?></a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h3><?= __('footer_newsletter') ?></h3>
                    <p><?= __('footer_subscribe') ?></p>
                    <div class="newsletter-box">
                        <input type="email" placeholder="<?= __('footer_newsletter_placeholder') ?>">
                        <button type="button"><?= __('footer_signup') ?></button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p><?= __('footer_copyright') ?></p>
            </div>
        </div>
    </footer>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
