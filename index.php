<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);

// Ambil 3 ulasan premium terbaharu untuk seksyen testimoni
$testi_sql = "SELECT r.*, u.full_name, p.package_name 
              FROM reviews r 
              JOIN users u ON r.user_id = u.user_id 
              JOIN packages p ON r.package_id = p.package_id 
              WHERE r.rating >= 4 
              ORDER BY r.created_at DESC 
              LIMIT 3";
$testi_result = $conn->query($testi_sql);
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];

    if (empty($check_in) || empty($check_out) || $check_in >= $check_out) {
        $message = "<div class='alert alert-danger' id='alertMsg'>Invalid date selection.</div>";
    } else {
        header("Location: package.php?check_in=" . urlencode($check_in) . "&check_out=" . urlencode($check_out));
        exit();
    }
}
?>

<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>EasyStay</title>
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
                            <li><a href="index.php" class="active-link">Home</a></li>
                            <li><a href="package.php">Package</a></li>
                            <li><a href="about.php">About</a></li>
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
        <div class="welcome-section">
            <div class="container text-center">
                <div class="mb-4">
                    <img src="img/logo.png?v=2" alt="EasyStay Logo" style="height: 100px; width: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15));">
                </div>
                <h1 class="welcome-title">Welcome to EasyStay</h1>
                <p class="welcome-subtitle">A Digital Platform for Fast and Efficient Homestay Reservation</p>
                <p class="welcome-description">Escape to our serene paradise nestled in Terengganu.</p>

                <div class="row justify-content-center mt-4">
                    <div class="col-lg-10">
                        <form action="" method="post" class="check-form glass-effect">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label>CHECK-IN</label>
                                    <input type="date" name="check_in_date" class="form-control" required value="<?php echo isset($_POST['check_in_date']) ? $_POST['check_in_date'] : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label>CHECK-OUT</label>
                                    <input type="date" name="check_out_date" class="form-control" required value="<?php echo isset($_POST['check_out_date']) ? $_POST['check_out_date'] : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn-check">Check Availability</button>
                                </div>
                            </div>
                        </form>
                        <?php if ($message) echo $message; ?>
                    </div>
                </div>
            </div>
        </div>


        <!-- ===== VISUAL SHOWCASE SECTION ===== -->
        <section class="visual-showcase">
            <div class="showcase-header">
                <span class="showcase-tag">Experience EasyStay</span>
                <h2 class="showcase-title">Where Every Moment<br><em>Becomes a Memory</em></h2>
            </div>

            <div class="showcase-grid">
                <!-- Panel 1 — Large Left -->
                <a href="gallery.php" class="showcase-panel panel-large">
                    <div class="showcase-img" style="background-image: url('img/chalet day.jpg');"></div>
                    <div class="showcase-overlay">
                        <div class="showcase-content">
                            <div class="showcase-icon"><i class="fas fa-home"></i></div>
                            <h3>Peaceful Nature Setting</h3>
                            <p>Surrounded by lush tropical greenery</p>
                            <span class="showcase-cta">Explore <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>

                <!-- Right column — 3 stacked -->
                <div class="showcase-right">
                    <a href="gallery.php" class="showcase-panel panel-sm">
                        <div class="showcase-img" style="background-image: url('img/chalet night.jpg');"></div>
                        <div class="showcase-overlay">
                            <div class="showcase-content">
                                <div class="showcase-icon"><i class="fas fa-moon"></i></div>
                                <h3>Luxury by Night</h3>
                                <p>Magical evenings under the stars</p>
                                <span class="showcase-cta">Explore <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                    <a href="gallery.php" class="showcase-panel panel-sm">
                        <div class="showcase-img" style="background-image: url('img/poolday.jpg');"></div>
                        <div class="showcase-overlay">
                            <div class="showcase-content">
                                <div class="showcase-icon"><i class="fas fa-swimming-pool"></i></div>
                                <h3>Private Pool & Recreation</h3>
                                <p>Your own slice of paradise</p>
                                <span class="showcase-cta">Explore <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                    <a href="gallery.php" class="showcase-panel panel-sm">
                        <div class="showcase-img" style="background-image: url('img/poolnight.jpg');"></div>
                        <div class="showcase-overlay">
                            <div class="showcase-content">
                                <div class="showcase-icon"><i class="fas fa-star"></i></div>
                                <h3>Night Paradise</h3>
                                <p>Glowing pool under starry skies</p>
                                <span class="showcase-cta">Explore <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="showcase-footer">
                <a href="gallery.php" class="showcase-gallery-btn">
                    <i class="fas fa-images mr-2"></i> View Full Gallery
                </a>
            </div>
        </section>

        <style>
        /* ===== VISUAL SHOWCASE ===== */
        .visual-showcase {
            padding: 80px 0 60px;
            background: #0a0a0a;
        }
        .showcase-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }
        .showcase-tag {
            display: inline-block;
            background: rgba(197,168,128,0.15);
            color: #C5A880;
            border: 1px solid rgba(197,168,128,0.3);
            padding: 6px 20px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .showcase-title {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin: 0;
        }
        .showcase-title em {
            font-style: normal;
            background: linear-gradient(135deg, #C5A880, #E8D5B7, #A48256);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .showcase-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 6px;
            padding: 0 6px;
            height: 620px;
        }
        .showcase-right {
            display: grid;
            grid-template-rows: 1fr 1fr 1fr;
            gap: 6px;
        }
        .showcase-panel {
            position: relative;
            overflow: hidden;
            display: block;
            text-decoration: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .showcase-img {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform;
        }
        .showcase-panel:hover .showcase-img {
            transform: scale(1.08);
        }
        .showcase-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,0.82) 0%,
                rgba(0,0,0,0.3) 40%,
                rgba(0,0,0,0.05) 70%,
                transparent 100%
            );
            display: flex;
            align-items: flex-end;
            padding: 30px;
            transition: background 0.4s ease;
        }
        .showcase-panel:hover .showcase-overlay {
            background: linear-gradient(
                to top,
                rgba(0,0,0,0.88) 0%,
                rgba(197,168,128,0.15) 50%,
                rgba(0,0,0,0.1) 100%
            );
        }
        .showcase-content {
            transform: translateY(12px);
            transition: transform 0.4s ease;
        }
        .showcase-panel:hover .showcase-content {
            transform: translateY(0);
        }
        .showcase-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(197,168,128,0.25);
            border: 1px solid rgba(197,168,128,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #C5A880;
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.4s ease 0.05s;
        }
        .showcase-panel:hover .showcase-icon {
            opacity: 1;
            transform: scale(1);
        }
        .showcase-content h3 {
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .panel-large .showcase-content h3 {
            font-size: 1.5rem;
        }
        .showcase-content p {
            color: rgba(255,255,255,0.7);
            font-size: 0.82rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .panel-large .showcase-content p {
            font-size: 0.95rem;
        }
        .showcase-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #C5A880;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(197,168,128,0.4);
            padding-bottom: 2px;
            opacity: 0;
            transform: translateX(-8px);
            transition: all 0.35s ease 0.1s;
        }
        .showcase-panel:hover .showcase-cta {
            opacity: 1;
            transform: translateX(0);
        }
        .showcase-footer {
            text-align: center;
            margin-top: 35px;
        }
        .showcase-gallery-btn {
            display: inline-flex;
            align-items: center;
            padding: 14px 36px;
            border: 1.5px solid rgba(197,168,128,0.5);
            color: #C5A880;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .showcase-gallery-btn:hover {
            background: #C5A880;
            color: #fff;
            border-color: #C5A880;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197,168,128,0.3);
            text-decoration: none;
        }
        @media (max-width: 900px) {
            .showcase-grid {
                grid-template-columns: 1fr 1fr;
                height: auto;
            }
            .panel-large { height: 300px; }
            .showcase-right { height: auto; }
            .panel-sm { height: 200px; }
            .showcase-right { grid-template-rows: auto; }
        }
        @media (max-width: 600px) {
            .showcase-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
            .showcase-right { grid-template-columns: 1fr; }
            .panel-large, .panel-sm { height: 260px; }
            .showcase-title { font-size: 1.8rem; }
        }
        </style>



        <!-- Testimonials Section -->
        <?php if ($testi_result && $testi_result->num_rows > 0): ?>
        <section class="testimonials-section">
            <div class="container">
                <div class="section-title mb-5">
                    <h2>What Our Guests Say</h2>
                    <p>Real experiences shared by our visitors.</p>
                </div>
                <div class="row">
                    <?php while ($testi = $testi_result->fetch_assoc()): 
                        $avatar_letter = strtoupper(substr(trim($testi['full_name']), 0, 1));
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="testimonial-card">
                                <div>
                                    <div class="testimonial-stars">
                                        <?php for ($i = 0; $i < $testi['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = $testi['rating']; $i < 5; $i++): ?>
                                            <i class="far fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="testimonial-text">"<?= htmlspecialchars($testi['comment']) ?>"</p>
                                </div>
                                <div class="testimonial-author">
                                    <div class="author-avatar"><?= $avatar_letter ?></div>
                                    <div class="author-info">
                                        <h5><?= htmlspecialchars($testi['full_name']) ?></h5>
                                        <small>Stayed in <?= htmlspecialchars($testi['package_name']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

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
                <p>Copyright EasyStay &copy; 2025. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        // Auto-hide alert after 5s
        setTimeout(() => {
            const msg = document.getElementById('alertMsg');
            if (msg) {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            }
        }, 5000);
    </script>
</body>

</html>