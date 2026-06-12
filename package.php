<?php
session_start();
require 'db_connect.php';

// Menentukan status login
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

$check_in = isset($_GET['check_in']) ? trim($_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';

// Ambil senarai pakej aktif dari database
$result = $conn->query("SELECT package_id, package_name, description, price, availability, image FROM packages WHERE status = 'ACTIVE'");
?>
<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Our Exclusive Packages | EasyStay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="css/style.css?v=3">

    <style>
        /* Tetapan Font & Warna Tema */
        :root {
            --primary-orange: #C5A880;
            --dark-black: #1a1a1a;
            --soft-grey: #f9f9f9;
        }

        body {
            background-color: var(--soft-grey);
        }

        /* Tajuk Seksyen Minimalist */
        .section-title h2 {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark-black);
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .section-title p {
            font-size: 16px;
            color: #666;
            font-weight: 400;
        }

        /* Kad Pakej Minimalist */
        .package-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            margin-bottom: 30px;
            position: relative;
            height: 100%;
            /* Pastikan kad sama tinggi */
            display: flex;
            flex-direction: column;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(197, 168, 128, 0.15);
        }

        /* --- PERUBAHAN CSS DI SINI UNTUK GAMBAR FULL --- */
        /* Gambar Pakej */
        .package-img-box {
            position: relative;
            /* height: 250px;  <-- DIBUANG supaya tinggi ikut gambar */
            overflow: hidden;
            background-color: #eee;
            /* Placeholder color jika loading lambat */
        }

        .package-img-box img {
            width: 100%;
            height: auto;
            /* <-- DITUKAR supaya tinggi menyesuaikan diri */
            /* object-fit: cover; <-- DIBUANG supaya gambar tak kena crop */
            display: block;
            /* Menghilangkan ruang kosong di bawah gambar */
            transition: transform 0.6s ease;
        }

        /* ----------------------------------------------- */

        .package-card:hover .package-img-box img {
            transform: scale(1.05);
            /* Dikurangkan sedikit zoom supaya lebih smooth */
        }

        /* Konten Pakej */
        .package-content {
            padding: 25px;
            text-align: left;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .package-content h4 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-black);
            margin-bottom: 8px;
        }

        /* Deskripsi Pakej */
        .package-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
            /* Tolak harga ke bawah */
        }

        /* Harga */
        .price-box {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-orange);
            margin-bottom: 20px;
            background: transparent;
            padding: 0;
        }

        .price-box small {
            font-size: 14px;
            color: #aaa;
            font-weight: 400;
        }

        /* Butang Book Now Minimalist */
        .btn-book {
            display: block;
            width: 100%;
            background: var(--dark-black);
            color: #fff !important;
            text-align: center;
            padding: 14px 0;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: 0.3s;
            border: 1px solid transparent;
            text-decoration: none;
        }

        .btn-book:hover {
            background: var(--primary-orange);
            box-shadow: 0 5px 15px rgba(197, 168, 128, 0.4);
            transform: translateY(-2px);
        }

        /* Senarai Kemudahan Pakej */
        .package-features-list {
            margin-top: 15px;
            margin-bottom: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 15px;
            flex-grow: 1; /* Biar tolak harga ke bawah secara sekata */
        }
        .package-features-list li {
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }
        .package-features-list li i {
            color: #C5A880 !important; /* Premium Gold */
            margin-right: 8px;
            margin-top: 3px;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <header class="header-area">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-5 d-none d-lg-block">
                    <nav>
                        <ul id="navigation">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="package.php" class="active-link">Package</a></li>
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
        <div class="package-area section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="section-title text-center mb-50">
                            <h2>Our Exclusive Packages</h2>
                            <p>Curated stays for your perfect getaway.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $is_booked = false;
                            if (!empty($check_in) && !empty($check_out)) {
                                $stmt_check = $conn->prepare("SELECT book_id FROM bookings WHERE package_id = ? AND status NOT IN ('Cancelled', 'Rejected') AND (checkin_date < ? AND checkout_date > ?)");
                                $stmt_check->bind_param("iss", $row['package_id'], $check_out, $check_in);
                                $stmt_check->execute();
                                if ($stmt_check->get_result()->num_rows > 0) {
                                    $is_booked = true;
                                }
                                $stmt_check->close();
                            }
                            
                            // Ambil purata rating dan bilangan ulasan
                            $package_id = $row['package_id'];
                            $rev_sql = "SELECT AVG(rating) as avg_rating, COUNT(review_id) as count_reviews FROM reviews WHERE package_id = ?";
                            $stmt_rev = $conn->prepare($rev_sql);
                            $stmt_rev->bind_param("i", $package_id);
                            $stmt_rev->execute();
                            $rev_data = $stmt_rev->get_result()->fetch_assoc();
                            $avg_rating = $rev_data['avg_rating'] ? round($rev_data['avg_rating'], 1) : 0;
                            $count_reviews = $rev_data['count_reviews'];
                            $stmt_rev->close();
                        ?>
                            <div class="col-xl-4 col-lg-4 col-md-6 mb-4">
                                <div class="package-card">
                                    <div class="package-img-box">
                                        <img src="admin/uploads/<?= htmlspecialchars($row['image']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($row['package_name']) ?>">
                                    </div>
                                    <div class="package-content">

                                        <h4>
                                            <?= htmlspecialchars($row['package_name']) ?>
                                            <?php if ($is_booked): ?>
                                                <span class="badge badge-danger ml-2" style="font-size: 11px; border-radius: 8px; vertical-align: middle; background-color: #dc3545; color: white; padding: 4px 8px;">Fully Booked</span>
                                            <?php endif; ?>
                                        </h4>
                                        
                                        <!-- Paparan Ulasan Bintang Pelanggan -->
                                        <div class="rating-info mb-3" style="font-size: 14px; font-weight: 600; color: var(--gold-premium);">
                                            <?php if ($count_reviews > 0): ?>
                                                <i class="fas fa-star text-warning"></i> <?= $avg_rating ?>/5.0 (<?= $count_reviews ?> reviews)
                                                <a href="#" class="ml-2 text-muted font-weight-normal" data-toggle="modal" data-target="#reviewsModal<?= $package_id ?>" style="font-size: 13px; text-decoration: underline;">Read Reviews</a>
                                            <?php else: ?>
                                                <i class="far fa-star text-muted"></i> No reviews yet
                                            <?php endif; ?>
                                        </div>

                                        <?php 
                                        $pkg_features = get_package_features($package_id);
                                        ?>
                                        <div class="package-desc mb-0" style="flex-grow: 0; margin-bottom: 0;">
                                            <div class="d-flex align-items-center" style="font-weight: 700; color: #333;">
                                                <i class="fa fa-user-group mr-2" style="color: #C5A880;"></i><?= htmlspecialchars($pkg_features['capacity']) ?>
                                            </div>
                                        </div>
                                        
                                        <ul class="package-features-list list-unstyled pl-0">
                                            <?php foreach ($pkg_features['inclusions'] as $inc): ?>
                                                <li>
                                                    <i class="fas fa-check-circle"></i>
                                                    <span><?= htmlspecialchars($inc) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>

                                        <div class="price-box">
                                            <small>RM</small> <?= number_format($row['price'], 0) ?> <small>/night</small>
                                        </div>

                                        <?php if ($is_booked): ?>
                                            <button class="btn btn-secondary w-100" style="padding: 14px 0; border-radius: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; cursor: not-allowed;" disabled>
                                                Unavailable
                                            </button>
                                        <?php else: ?>
                                            <a href="book_new.php?package_id=<?= $row['package_id'] ?><?= (!empty($check_in) && !empty($check_out)) ? '&check_in=' . urlencode($check_in) . '&check_out=' . urlencode($check_out) : '' ?>" class="btn-book">
                                                Book This Room
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Reviews Premium -->
                            <div class="modal fade" id="reviewsModal<?= $package_id ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15);">
                                        <div class="modal-header" style="background: var(--ios-black); color: white; border: none; padding: 20px 25px;">
                                            <h5 class="modal-title font-weight-bold" style="letter-spacing: -0.5px;">Customer Reviews</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8; outline: none; border: none; background: transparent;">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body" style="padding: 25px; max-height: 450px; overflow-y: auto;">
                                            <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid #eee;">
                                                <h2 class="font-weight-bold mb-1" style="color: var(--text-primary); font-size: 2.2rem;"><?= $avg_rating ?><span style="font-size: 1.2rem; color: #888;">/5.0</span></h2>
                                                <div class="stars mb-1" style="color: #FFC107; font-size: 18px;">
                                                    <?php 
                                                    $floor_rating = floor($avg_rating);
                                                    for ($i = 0; $i < $floor_rating; $i++) echo '<i class="fas fa-star"></i>';
                                                    if ($avg_rating - $floor_rating >= 0.5) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                        $floor_rating++;
                                                    }
                                                    for ($i = $floor_rating; $i < 5; $i++) echo '<i class="far fa-star"></i>';
                                                    ?>
                                                </div>
                                                <p class="text-muted small mb-0">Based on <?= $count_reviews ?> customer ratings</p>
                                            </div>
                                            
                                            <?php
                                            $comments_sql = "SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.package_id = ? ORDER BY r.created_at DESC";
                                            $stmt_comments = $conn->prepare($comments_sql);
                                            $stmt_comments->bind_param("i", $package_id);
                                            $stmt_comments->execute();
                                            $comments_result = $stmt_comments->get_result();
                                            
                                            if ($comments_result->num_rows > 0):
                                                while ($comment = $comments_result->fetch_assoc()):
                                                    $c_avatar = strtoupper(substr(trim($comment['full_name']), 0, 1));
                                            ?>
                                                    <div class="review-item">
                                                        <div class="review-header">
                                                            <div class="review-user">
                                                                <div class="review-avatar" style="background-color: var(--gold-premium); color: white;"><?= $c_avatar ?></div>
                                                                <div>
                                                                    <h6 class="font-weight-bold mb-0" style="font-size: 14px;"><?= htmlspecialchars($comment['full_name']) ?></h6>
                                                                    <div class="review-rating">
                                                                        <?php for ($i = 0; $i < $comment['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                                                        <?php for ($i = $comment['rating']; $i < 5; $i++) echo '<i class="far fa-star"></i>'; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <span class="review-date"><?= date('d M Y', strtotime($comment['created_at'])) ?></span>
                                                        </div>
                                                        <p class="review-comment" style="font-style: italic;">"<?= htmlspecialchars($comment['comment']) ?>"</p>
                                                    </div>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="far fa-comments fa-2x mb-2" style="opacity: 0.3;"></i>
                                                    <p class="small mb-0">No written reviews yet.</p>
                                                </div>
                                            <?php 
                                            endif; 
                                            $stmt_comments->close();
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <p class="alert alert-warning">No packages available at the moment.</p>
                        </div>
                    <?php endif; ?>
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
                <p>Copyright EasyStay &copy; 2025. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>