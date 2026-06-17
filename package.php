<?php
session_start();
require 'db_connect.php';

// Menentukan status login
$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

$check_in = isset($_GET['check_in']) ? trim($_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 0;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : '';
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : '';

// ----------------------------------------------------
// BUILD DYNAMIC SQL QUERY
// ----------------------------------------------------
$where_clauses = ["status = 'ACTIVE'"];
$bind_types = "";
$bind_params = [];

// Guest Capacity Filter mapping
if ($guests > 0) {
    if ($guests <= 3) {
        $where_clauses[] = "package_id = 12";
    } elseif ($guests <= 15) {
        $where_clauses[] = "package_id = 13";
    } else {
        $where_clauses[] = "package_id = 14";
    }
}

// Min Price Filter
if ($min_price !== '') {
    $where_clauses[] = "price >= ?";
    $bind_types .= "d";
    $bind_params[] = $min_price;
}

// Max Price Filter
if ($max_price !== '') {
    $where_clauses[] = "price <= ?";
    $bind_types .= "d";
    $bind_params[] = $max_price;
}

// Amenities / Inclusions Filter
if (isset($_GET['amenities']) && is_array($_GET['amenities'])) {
    $matching_package_ids = [];
    $all_package_ids = [12, 13, 14];
    foreach ($all_package_ids as $pid) {
        $features = get_package_features($pid);
        $inc_lower = array_map('strtolower', $features['inclusions']);
        
        $match = true;
        foreach ($_GET['amenities'] as $amenity) {
            if ($amenity == 'aircond' && !in_array('aircond', $inc_lower) && !in_array('fully airconditioned (bilik & ruang tamu)', $inc_lower)) {
                $match = false;
            }
            if ($amenity == 'wifi' && !in_array('free high-speed wifi', $inc_lower)) {
                $match = false;
            }
            if ($amenity == 'pool' && !in_array('private pool (kolam mandi persendirian)', $inc_lower)) {
                $match = false;
            }
            if ($amenity == 'kitchen' && !in_array('kitchen & cooking utensils (peralatan memasak)', $inc_lower)) {
                $match = false;
            }
            if ($amenity == 'washing' && !in_array('iron & washing machine (seterika & mesin basuh)', $inc_lower)) {
                $match = false;
            }
            if ($amenity == 'bbq' && !in_array('access to bbq pit', $inc_lower)) {
                $match = false;
            }
        }
        if ($match) {
            $matching_package_ids[] = $pid;
        }
    }
    
    if (!empty($matching_package_ids)) {
        $where_clauses[] = "package_id IN (" . implode(",", $matching_package_ids) . ")";
    } else {
        $where_clauses[] = "1 = 0"; // Force zero results
    }
}

// Assemble Query
$sql = "SELECT package_id, package_name, description, price, availability, image FROM packages";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sorting logic
if ($sort_by == 'price_asc') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort_by == 'price_desc') {
    $sql .= " ORDER BY price DESC";
} else {
    $sql .= " ORDER BY FIELD(package_id, 14, 12, 13)";
}

$stmt = $conn->prepare($sql);
if ($bind_types !== "") {
    $stmt->bind_param($bind_types, ...$bind_params);
}
$stmt->execute();
$result = $stmt->get_result();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        /* Tetapan Font & Warna Tema */
        :root {
            --primary-orange: #C5A880;
            --dark-black: #1a1a1a;
            --soft-grey: #f9f9f9;
        }

        /* Search Bar & Filter styling */
        .search-bar-wrap {
            background: #fff;
            padding: 18px 24px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .search-form-bar {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr auto;
            gap: 16px;
            align-items: flex-end;
        }
        @media (max-width: 991px) {
            .search-form-bar {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        .search-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .search-field label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--primary-orange);
            margin: 0;
        }
        .form-control-bar {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            outline: none;
            background: #fdfdfd;
            height: 44px;
            transition: all 0.3s ease;
        }
        .form-control-bar:focus {
            border-color: var(--primary-orange);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15);
        }
        .btn-search-bar {
            background: var(--dark-black);
            color: #fff;
            border: none;
            padding: 0 28px;
            height: 44px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-search-bar:hover {
            background: var(--primary-orange);
            box-shadow: 0 5px 15px rgba(197, 168, 128, 0.35);
        }

        /* Filter Sidebar styling */
        .filter-sidebar {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(197, 168, 128, 0.08);
            position: sticky;
            top: 100px;
        }
        .filter-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--dark-black);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            letter-spacing: -0.3px;
        }
        .filter-group {
            margin-bottom: 22px;
        }
        .filter-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #888;
            margin-bottom: 12px;
            display: block;
        }
        .gap-2 { gap: 8px; }

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
        <div class="package-area section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="section-title text-center mb-40">
                            <h2><?= __('pkg_title') ?></h2>
                            <p><?= __('pkg_subtitle') ?></p>
                        </div>
                    </div>
                </div>

                <!-- 1. Search Bar -->
                <div class="search-bar-wrap mb-5">
                    <form method="GET" action="package.php" class="search-form-bar">
                        <div class="search-field">
                            <label><i class="fa-solid fa-calendar-days"></i> <?= __('home_checkin') ?></label>
                            <input type="text" id="check_in" name="check_in" class="form-control-bar" placeholder="Choose check-in date" value="<?= htmlspecialchars($check_in) ?>" readonly>
                        </div>
                        <div class="search-field">
                            <label><i class="fa-solid fa-calendar-days"></i> <?= __('home_checkout') ?></label>
                            <input type="text" id="check_out" name="check_out" class="form-control-bar" placeholder="Choose check-out date" value="<?= htmlspecialchars($check_out) ?>" readonly>
                        </div>
                        <div class="search-field">
                            <label><i class="fa-solid fa-users"></i> <?= __('pkg_guests') ?></label>
                            <select name="guests" class="form-control-bar">
                                <option value=""><?= __('pkg_all_capacities') ?></option>
                                <option value="3" <?= $guests == 3 ? 'selected' : '' ?>><?= __('pkg_cap_chalet') ?></option>
                                <option value="15" <?= $guests == 15 ? 'selected' : '' ?>><?= __('pkg_cap_homestay') ?></option>
                                <option value="30" <?= $guests == 30 ? 'selected' : '' ?>><?= __('pkg_cap_entire') ?></option>
                            </select>
                        </div>
                        <button type="submit" class="btn-search-bar"><i class="fa fa-search"></i> <?= __('pkg_search_btn') ?></button>
                    </form>
                </div>

                <div class="row">
                    <!-- 2. Filter Sidebar -->
                    <div class="col-xl-3 col-lg-4 col-md-12 mb-4">
                        <div class="filter-sidebar">
                            <h5 class="filter-title"><i class="fa fa-sliders mr-2"></i> <?= __('pkg_filter_title') ?></h5>
                            <form method="GET" action="package.php" id="filterForm">
                                <input type="hidden" name="check_in" value="<?= htmlspecialchars($check_in) ?>">
                                <input type="hidden" name="check_out" value="<?= htmlspecialchars($check_out) ?>">
                                <input type="hidden" name="guests" value="<?= $guests > 0 ? $guests : '' ?>">
                                <input type="hidden" name="sort_by" id="filter_sort_by" value="<?= htmlspecialchars($sort_by) ?>">

                                <!-- Price Range -->
                                <div class="filter-group">
                                    <label class="filter-label"><?= __('pkg_price_range') ?></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="<?= __('pkg_min_price') ?>" value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>" style="border-radius: 8px;">
                                        <span class="mx-2 text-muted">-</span>
                                        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="<?= __('pkg_max_price') ?>" value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>" style="border-radius: 8px;">
                                    </div>
                                </div>

                                <!-- Amenities -->
                                <div class="filter-group">
                                    <label class="filter-label"><?= __('pkg_amenities') ?></label>
                                    <?php
                                    $amenity_options = [
                                        'aircond' => __('amenity_aircond'),
                                        'wifi' => __('amenity_wifi'),
                                        'pool' => __('amenity_pool'),
                                        'kitchen' => __('amenity_kitchen'),
                                        'washing' => __('amenity_washing'),
                                        'bbq' => __('amenity_bbq')
                                    ];
                                    foreach ($amenity_options as $key => $lbl):
                                        $checked = isset($_GET['amenities']) && in_array($key, $_GET['amenities']) ? 'checked' : '';
                                    ?>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input" id="amenity_<?= $key ?>" name="amenities[]" value="<?= $key ?>" <?= $checked ?> onchange="document.getElementById('filterForm').submit();">
                                            <label class="custom-control-label small text-dark font-weight-bold" for="amenity_<?= $key ?>"><?= $lbl ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm btn-block mt-3" style="background: var(--primary-orange); border-color: var(--primary-orange); border-radius: 8px; font-weight: 700; height: 38px;"><?= __('pkg_apply_filters') ?></button>
                                <a href="package.php" class="btn btn-link btn-sm btn-block text-muted text-center small mt-2"><?= __('pkg_clear_all') ?></a>
                            </form>
                        </div>
                    </div>

                    <!-- 3. Packages Grid Column -->
                    <div class="col-xl-9 col-lg-8 col-md-12">
                        <!-- Sorting Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <div class="text-muted small font-weight-bold">
                                <?= __('pkg_showing') ?> <?= $result->num_rows ?> <?= __('pkg_packages') ?>
                            </div>
                            <div class="d-flex align-items-center">
                                <label class="mr-2 mb-0 small font-weight-bold text-dark text-nowrap"><?= __('pkg_sort_title') ?>:</label>
                                <select class="form-control form-control-sm" style="width: auto; border-radius: 8px; font-weight: 600;" onchange="changeSort(this.value)">
                                    <option value="" <?= $sort_by == '' ? 'selected' : '' ?>><?= __('pkg_sort_default') ?></option>
                                    <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>><?= __('pkg_sort_low') ?></option>
                                    <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>><?= __('pkg_sort_high') ?></option>
                                </select>
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
                            <div class="col-xl-6 col-lg-6 col-md-6 mb-4">
                                <div class="package-card">
                                     <div class="package-img-box">
                                         <?php
                                         $pkg_image = '';
                                         if ($row['package_id'] == 12) {
                                             $pkg_image = 'img/chalet night.jpg';
                                         } elseif ($row['package_id'] == 13) {
                                             $pkg_image = 'img/homestay day.jpg';
                                         } elseif ($row['package_id'] == 14) {
                                             $pkg_image = 'img/pakej privacy.jpg';
                                         } else {
                                             $pkg_image = 'admin/uploads/' . htmlspecialchars($row['image']);
                                         }
                                         ?>
                                         <img src="<?= $pkg_image ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($row['package_name']) ?>">
                                     </div>
                                    <div class="package-content">

                                        <h4>
                                            <?= htmlspecialchars($row['package_name']) ?>
                                            <?php if ($is_booked): ?>
                                                <span class="badge badge-danger ml-2" style="font-size: 11px; border-radius: 8px; vertical-align: middle; background-color: #dc3545; color: white; padding: 4px 8px;"><?= __('pkg_fully_booked') ?></span>
                                            <?php endif; ?>
                                        </h4>
                                        
                                        <!-- Paparan Ulasan Bintang Pelanggan -->
                                        <div class="rating-info mb-3" style="font-size: 14px; font-weight: 600; color: var(--gold-premium);">
                                            <?php if ($count_reviews > 0): ?>
                                                <i class="fas fa-star text-warning"></i> <?= $avg_rating ?>/5.0 (<?= $count_reviews ?> <?= __('pkg_reviews') ?>)
                                                <a href="#" class="ml-2 text-muted font-weight-normal" data-toggle="modal" data-target="#reviewsModal<?= $package_id ?>" style="font-size: 13px; text-decoration: underline;"><?= __('pkg_read_reviews') ?></a>
                                            <?php else: ?>
                                                <i class="far fa-star text-muted"></i> <?= __('pkg_no_reviews') ?>
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
                                            <small>RM</small> <?= number_format($row['price'], 0) ?> <small>/<?= __('pkg_night') ?></small>
                                        </div>

                                        <?php if ($is_booked): ?>
                                            <button class="btn btn-secondary w-100" style="padding: 14px 0; border-radius: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; cursor: not-allowed;" disabled>
                                                <?= __('pkg_unavailable') ?>
                                            </button>
                                        <?php else: ?>
                                            <a href="book_new.php?package_id=<?= $row['package_id'] ?><?= (!empty($check_in) && !empty($check_out)) ? '&check_in=' . urlencode($check_in) . '&check_out=' . urlencode($check_out) : '' ?>" class="btn-book">
                                                <?= __('pkg_book_this_room') ?>
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
                                            <h5 class="modal-title font-weight-bold" style="letter-spacing: -0.5px;"><?= __('modal_reviews_title') ?></h5>
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
                                                <p class="text-muted small mb-0"><?= __('modal_based_on') ?> <?= $count_reviews ?> <?= __('modal_ratings') ?></p>
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
                                                    <p class="small mb-0"><?= __('modal_no_reviews') ?></p>
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
                            <p class="alert alert-warning"><?= __('pkg_no_results') ?></p>
                        </div>
                    <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const inPicker = flatpickr("#check_in", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                outPicker.set('minDate', dateStr);
            }
        });
        const outPicker = flatpickr("#check_out", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });

        function changeSort(val) {
            document.getElementById('filter_sort_by').value = val;
            document.getElementById('filterForm').submit();
        }
    </script>
</body>

</html>
