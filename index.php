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

        <div class="photo-sections">
            <div class="photo-block" style="background-image: url('img/chalet day.jpg');">
                <div class="overlay">
                    <div class="content">
                        <h3>Peaceful Nature Setting</h3>
                        <p>Surrounded by lush greenery</p>
                    </div>
                </div>
            </div>
            <div class="photo-block" style="background-image: url('img/chalet night.jpg');">
                <div class="overlay">
                    <div class="content">
                        <h3>Luxury Accommodations</h3>
                        <p>Comfortable chalets and homestay</p>
                    </div>
                </div>
            </div>
            <div class="photo-block" style="background-image: url('img/poolday.jpg');">
                <div class="overlay">
                    <div class="content">
                        <h3>Pool & Recreation</h3>
                        <p>Enjoy our private pool</p>
                    </div>
                </div>
            </div>
            <div class="photo-block" style="background-image: url('img/poolnight.jpg');">
                <div class="overlay">
                    <div class="content">
                        <h3>Night Paradise</h3>
                        <p>Beautiful nights under the stars</p>
                    </div>
                </div>
            </div>
        </div>

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