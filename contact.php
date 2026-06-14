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
            <div class="container text-center">
                <h1 class="welcome-title"><?= __('contact_title') ?></h1>
                <p class="welcome-subtitle"><?= __('home_subtitle') ?></p>
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
                        <h2 style="font-weight: 800; font-size: 2.5rem;"><?= __('contact_connect') ?></h2>
                    </div>

                    <div class="col-lg-8">
                        <form action="contact_support.php" method="post" class="contact-form-wrapper">
                            <div class="row">
                                <div class="col-12">
                                    <textarea name="message" class="form-textarea-custom" rows="6" placeholder="<?= __('contact_msg_placeholder') ?>" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <input name="name" class="form-input-custom" type="text" placeholder="<?= __('contact_name_placeholder') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <input name="email" class="form-input-custom" type="email" placeholder="<?= __('contact_email_placeholder') ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <input name="number" class="form-input-custom" type="text" placeholder="<?= __('contact_phone_placeholder') ?>" required>
                                </div>
                                <div class="col-12">
                                    <input name="subject" class="form-input-custom" type="text" placeholder="<?= __('contact_subject_placeholder') ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="auth-btn" style="border:none; width: 200px; height: 55px; cursor: pointer;">
                                <?= __('contact_send') ?>
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
                                    <p><?= __('contact_mon_fri') ?></p>
                                </div>
                            </div>
                            <div class="info-item d-flex mb-4">
                                <i class="fa-solid fa-envelope mr-3 mt-1"></i>
                                <div>
                                    <h3>easystaysupport@gmail.com</h3>
                                    <p><?= __('contact_query') ?></p>
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
