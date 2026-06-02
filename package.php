<?php
session_start();
require 'db_connect.php';

// Menentukan status login
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil senarai pakej aktif dari database
$result = $conn->query("SELECT package_id, package_name, description, price, availability, image FROM packages WHERE status = 'ACTIVE'");
?>
<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Our Exclusive Packages | Ulu Garden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Tetapan Font & Warna Tema */
        :root {
            --primary-orange: #ff7b00;
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
            box-shadow: 0 20px 40px rgba(255, 123, 0, 0.15);
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
            box-shadow: 0 5px 15px rgba(255, 123, 0, 0.4);
            transform: translateY(-2px);
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
                    <a href="index.php" class="logo-link"><img src="img/logo.png" alt="Logo" style="height: 50px;"></a>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="header-right-part d-flex justify-content-end align-items-center">
                        <ul class="social-icons-head d-flex list-unstyled m-0 mr-4">
                            <li class="mr-3"><a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank" style="color:white;"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="https://www.tiktok.com/@ulugardenhomestay" target="_blank" style="color:white;"><i class="fa-brands fa-tiktok"></i></a></li>
                        </ul>
                        <?php if ($is_logged_in): ?>
                            <a href="logout.php" class="auth-btn" style="background: #ff7b00; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none;">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="auth-btn" style="background: #ff7b00; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none;">Login / Register</a>
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
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="col-xl-4 col-lg-4 col-md-6 mb-4">
                                <div class="package-card">
                                    <div class="package-img-box">
                                        <img src="admin/uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['package_name']) ?>">
                                    </div>
                                    <div class="package-content">

                                        <h4><?= htmlspecialchars($row['package_name']) ?></h4>

                                        <div class="package-desc">
                                            <i class="fa fa-user-group mr-1"></i><?= htmlspecialchars($row['description']) ?>
                                        </div>

                                        <div class="price-box">
                                            <small>RM</small> <?= number_format($row['price'], 0) ?> <small>/night</small>
                                        </div>

                                        <a href="book_new.php?package_id=<?= $row['package_id'] ?>" class="btn-book">
                                            Book This Room
                                        </a>
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
                    <h3>ULU GARDEN</h3>
                    <p>Lot 8012, Kampung Binjai Kertas,</p>
                    <p>21700 Kuala Berang, Terengganu.</p>
                    <div class="footer-social-icons">
                        <a href="https://www.facebook.com/profile.php?id=100092359781203" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.tiktok.com/@ulugardenhomestay" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h3>CONTACT US</h3>
                    <p><i class="fas fa-phone-alt mr-2"></i> +60 19 211 9223</p>
                    <p><i class="fas fa-envelope mr-2"></i> reservation@ulugarden.com</p>
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
                <p>Copyright Ulu Garden &copy; 2025. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>