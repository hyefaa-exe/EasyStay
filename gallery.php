<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data dari database
$result = $conn->query("SELECT * FROM `gallery` ORDER BY id DESC");
?>
<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Gallery | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
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
                            <li><a href="about.php">About</a></li>
                            <li><a href="gallery.php" class="active-link">Gallery</a></li>
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
        <section class="gallery-section py-5">
            <div class="container-fluid px-4 px-lg-5">
                <div class="text-center mb-5 mt-4">
                    <h2 class="gallery-main-title">Our Gallery</h2>
                    <p class="gallery-subtitle">Discover the beauty and tranquility of EasyStay.</p>
                </div>

                <div class="gallery-grid">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="gallery-card">
                                <a href="admin/uploads/<?php echo htmlspecialchars($row['filename']); ?>" class="img-pop-up gallery-link">
                                    <div class="gallery-img-wrap">
                                        <img src="admin/uploads/<?php echo htmlspecialchars($row['filename']); ?>" alt="EasyStay Gallery" class="gallery-img" loading="lazy">
                                        <div class="gallery-overlay">
                                            <div class="gallery-overlay-icon">
                                                <i class="fas fa-expand-alt"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="gallery-empty">
                            <i class="fas fa-images fa-3x mb-3" style="color:#C5A880; opacity:0.4;"></i>
                            <p>No images found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <style>
            .gallery-main-title {
                font-size: 2.8rem;
                font-weight: 800;
                color: #1a1a1a;
                letter-spacing: -0.5px;
            }
            .gallery-subtitle {
                color: #C5A880;
                font-size: 1.1rem;
                font-weight: 500;
                margin-top: -5px;
            }
            .gallery-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
            }
            @media (max-width: 1200px) {
                .gallery-grid { grid-template-columns: repeat(3, 1fr); }
            }
            @media (max-width: 768px) {
                .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
                .gallery-main-title { font-size: 2rem; }
            }
            @media (max-width: 480px) {
                .gallery-grid { grid-template-columns: 1fr; }
            }
            .gallery-card {
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                background: #f5f5f5;
            }
            .gallery-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 35px rgba(197,168,128,0.25);
            }
            .gallery-link {
                display: block;
                text-decoration: none;
            }
            .gallery-img-wrap {
                position: relative;
                width: 100%;
                padding-top: 56.25%;  /* 16:9 aspect ratio – semua gambar sama tinggi */
                overflow: hidden;
            }
            .gallery-img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
                transition: transform 0.4s ease;
                display: block;
            }
            .gallery-card:hover .gallery-img {
                transform: scale(1.06);
            }
            .gallery-overlay {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(135deg, rgba(197,168,128,0.0) 0%, rgba(26,26,26,0.45) 100%);
                opacity: 0;
                transition: opacity 0.35s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .gallery-card:hover .gallery-overlay {
                opacity: 1;
            }
            .gallery-overlay-icon {
                width: 52px;
                height: 52px;
                border-radius: 50%;
                background: rgba(255,255,255,0.92);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #C5A880;
                font-size: 18px;
                transform: scale(0.7);
                transition: transform 0.3s ease;
                box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            }
            .gallery-card:hover .gallery-overlay-icon {
                transform: scale(1);
            }
            .gallery-empty {
                grid-column: 1 / -1;
                text-align: center;
                padding: 60px 20px;
                color: #888;
            }
        </style>
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
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.img-pop-up').magnificPopup({
                type: 'image',
                gallery: {
                    enabled: true,
                    navigateByImgClick: true
                }
            });
        });
    </script>
</body>

</html>