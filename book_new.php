<?php
session_start();
// 1. Semak Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'db_connect.php';

$user_id = $_SESSION['user_id'];
$package_id = $_GET['package_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

$check_in_val = isset($_GET['check_in']) ? trim($_GET['check_in']) : '';
$check_out_val = isset($_GET['check_out']) ? trim($_GET['check_out']) : '';

// --- PEMBETULAN: DEFINISI VARIABLE INI ---
$is_logged_in = true; // Wajib ada sebab kita guna di Header nanti
// ----------------------------------------

if (!$package_id) {
    header("Location: package.php");
    exit;
}

// Ambil data pakej
$sql = "SELECT * FROM packages WHERE package_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package_rs = $stmt->get_result()->fetch_assoc();

if (!$package_rs) {
    header("Location: package.php");
    exit;
}

// Ambil tarikh yang telah ditempah untuk disable dalam kalendar (mengikut kuantiti availability)
$availability = intval($package_rs['availability']);
$sqlBooked = "SELECT checkin_date, checkout_date FROM bookings WHERE package_id = $package_id AND status NOT IN ('Cancelled', 'Rejected')";
$result_cal = $conn->query($sqlBooked);

$date_counts = [];
while ($row = $result_cal->fetch_assoc()) {
    $start = new DateTime($row['checkin_date']);
    $end = new DateTime($row['checkout_date']);
    
    // Gelung setiap malam tempahan (dari check-in hingga checkout - 1)
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        if (!isset($date_counts[$date_str])) {
            $date_counts[$date_str] = 0;
        }
        $date_counts[$date_str]++;
    }
}

// Cari tarikh yang telah ditempah sepenuhnya (tempahan >= kuantiti availability)
$fully_booked_dates = [];
foreach ($date_counts as $date_str => $count) {
    if ($count >= $availability) {
        $fully_booked_dates[] = $date_str;
    }
}
sort($fully_booked_dates);

// Kumpulkan tarikh berturutan ke dalam format Flatpickr range
$booked_ranges = [];
if (!empty($fully_booked_dates)) {
    $range_start = $fully_booked_dates[0];
    $range_end = $fully_booked_dates[0];
    
    for ($i = 1; $i < count($fully_booked_dates); $i++) {
        $prev_date = new DateTime($fully_booked_dates[$i - 1]);
        $curr_date = new DateTime($fully_booked_dates[$i]);
        $diff = $curr_date->diff($prev_date)->days;
        
        if ($diff === 1) {
            $range_end = $fully_booked_dates[$i];
        } else {
            $booked_ranges[] = ['from' => $range_start, 'to' => $range_end];
            $range_start = $fully_booked_dates[$i];
            $range_end = $fully_booked_dates[$i];
        }
    }
    $booked_ranges[] = ['from' => $range_start, 'to' => $range_end];
}
?>

<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <title>Reservation | EasyStay</title>
    <meta name="description" content="EasyStay - A Digital Platform for Fast and Efficient Homestay Reservation">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        .payment-option-card {
            flex: 1;
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .payment-option-card:hover {
            border-color: #C5A880;
            background: #fafafa;
        }
        .payment-option-card.active {
            border-color: #C5A880;
            background: rgba(197, 168, 128, 0.05);
        }
        .option-icon {
            font-size: 20px;
            color: #C5A880;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(197, 168, 128, 0.1);
            border-radius: 10px;
            flex-shrink: 0;
        }
        .option-details strong {
            display: block;
            font-size: 13px;
            color: #333;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        .option-details span {
            font-size: 11px;
            color: #888;
            display: block;
        }
        
        /* Custom Styling for Inline Flatpickr Calendar */
        #inline_calendar .flatpickr-calendar {
            background: #ffffff !important;
            box-shadow: none !important;
            border: none !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        #inline_calendar .flatpickr-innerContainer {
            width: 100% !important;
        }
        #inline_calendar .flatpickr-rContainer {
            width: 100% !important;
        }
        #inline_calendar .flatpickr-days {
            width: 100% !important;
        }
        #inline_calendar .dayContainer {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            justify-content: space-around !important;
        }
        #inline_calendar .flatpickr-day {
            max-width: 100% !important;
            height: 38px !important;
            line-height: 36px !important;
            border-radius: 8px !important;
            margin: 2px 0 !important;
        }
        /* Style for disabled/booked days */
        #inline_calendar .flatpickr-day.disabled,
        #inline_calendar .flatpickr-day.flatpickr-disabled,
        #inline_calendar .flatpickr-day.disabled:hover,
        #inline_calendar .flatpickr-day.flatpickr-disabled:hover {
            background: #fff5f5 !important;
            color: #e53e3e !important;
            border: 1px solid #fed7d7 !important;
            cursor: not-allowed !important;
            text-decoration: line-through !important;
            opacity: 0.85 !important;
            position: relative;
        }
        
        /* Available days (not disabled/today) */
        #inline_calendar .flatpickr-day:not(.disabled):not(.flatpickr-disabled) {
            background: #f0fdf4 !important;
            color: #16a34a !important;
            border: 1px solid #dcfce7 !important;
        }
        #inline_calendar .flatpickr-day:not(.disabled):not(.flatpickr-disabled):hover {
            background: #dcfce7 !important;
            border-color: #bbf7d0 !important;
            color: #15803d !important;
        }
        
        /* Today styling */
        #inline_calendar .flatpickr-day.today {
            border: 2px solid #C5A880 !important;
            font-weight: 700 !important;
        }
        
        /* Header styling */
        #inline_calendar .flatpickr-months {
            background: transparent !important;
        }
        #inline_calendar .flatpickr-month {
            color: #1c1c1c !important;
            fill: #1c1c1c !important;
            height: 34px !important;
        }
        #inline_calendar .flatpickr-current-month {
            font-weight: 600 !important;
            font-size: 14px !important;
            padding-top: 0 !important;
            color: #1c1c1c !important;
        }
        #inline_calendar .flatpickr-weekday {
            color: #6e6e73 !important;
            font-weight: 600 !important;
            font-size: 12px !important;
        }
        #inline_calendar .flatpickr-prev-month,
        #inline_calendar .flatpickr-next-month {
            fill: #1c1c1c !important;
            color: #1c1c1c !important;
            padding: 4px !important;
        }
        #inline_calendar .flatpickr-prev-month:hover,
        #inline_calendar .flatpickr-next-month:hover {
            fill: #C5A880 !important;
            color: #C5A880 !important;
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
        <div class="container">
            <a href="package.php" class="back-btn"><i class="fa fa-arrow-left mr-2"></i> <?= __('book_back_btn') ?></a>

            <div class="booking-container">
                <div class="package-info-card">
                    <div class="package-poster">
                        <?php
                        // Path gambar dari admin/uploads/
                        $image_name = $package_rs['image'];
                        $image_path = "admin/uploads/" . $image_name;
                        ?>
                        <img src="<?= $image_path ?>?v=<?= time() ?>" alt="Package Poster" style="width: 100%; border-radius: 10px; object-fit: cover;">
                    </div>
                    <div class="package-details-bottom">
                        <h2><?= htmlspecialchars($package_rs['package_name']) ?></h2>
                        
                        <?php 
                        $pkg_features = get_package_features($package_id);
                        ?>
                        <div class="d-flex align-items-center mb-3" style="font-weight: 700; color: #333; font-size: 14px;">
                            <i class="fa fa-user-group mr-2" style="color: #C5A880;"></i><?= htmlspecialchars($pkg_features['capacity']) ?>
                        </div>

                        <div class="price-row mb-4">
                            <span class="price-label"><?= __('pkg_price_label') ?>:</span>
                            <div class="price-tag-box">RM <?= number_format($package_rs['price'], 2) ?></div>
                        </div>

                        <div class="booking-features-list">
                            <h5><?= __('book_inclusions') ?></h5>
                            <ul class="list-unstyled">
                                <?php foreach ($pkg_features['inclusions'] as $inc): ?>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= htmlspecialchars($inc) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Inline Availability Calendar -->
                        <div class="availability-calendar-box mt-4 pt-3 border-top">
                            <h5 class="mb-3" style="font-size: 14px; font-weight: 700; color: #1c1c1c; letter-spacing: -0.2px;">
                                <i class="fa-solid fa-calendar-days mr-2" style="color: #C5A880;"></i><?= __('book_availability_cal') ?>
                            </h5>
                            <div class="inline-calendar-container" style="background: #ffffff; border-radius: 12px; padding: 10px; border: 1px solid rgba(0, 0, 0, 0.05); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);">
                                <div id="inline_calendar"></div>
                            </div>
                            <div class="calendar-legend d-flex justify-content-start mt-2 px-1" style="font-size: 11px; color: #6e6e73; gap: 15px;">
                                <div class="d-flex align-items-center">
                                    <span style="display: inline-block; width: 10px; height: 10px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 2px; margin-right: 5px;"></span>
                                    <?= __('book_avail_legend') ?>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span style="display: inline-block; width: 10px; height: 10px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 2px; position: relative; overflow: hidden; margin-right: 5px;">
                                        <span style="position: absolute; top: 50%; left: 0; right: 0; border-top: 1px dashed #e53e3e; transform: rotate(-45deg);"></span>
                                    </span>
                                    <?= __('book_booked_legend') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reservation-form-card">
                    <h3 class="font-weight-bold mb-1"><?= __('book_title') ?></h3>
                    <p class="text-muted mb-4"><?= __('book_desc') ?></p>

                    <form id="bookingForm" action="booking_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="package_id" value="<?= $package_id ?>">
                        <input type="hidden" name="process_booking" value="1">
                        <input type="hidden" id="total_price_input" name="total_price" value="0">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small"><?= __('book_checkin') ?></label>
                                <input type="text" id="checkin_date" name="checkin_date" class="form-control" placeholder="<?= __('book_select_date_placeholder') ?>" readonly required value="<?= htmlspecialchars($check_in_val) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small"><?= __('book_checkout') ?></label>
                                <input type="text" id="checkout_date" name="checkout_date" class="form-control" placeholder="<?= __('book_select_date_placeholder') ?>" readonly required value="<?= htmlspecialchars($check_out_val) ?>">
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small"><?= __('book_adults') ?></label>
                                <input type="number" name="adults" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small"><?= __('book_children') ?></label>
                                <input type="number" name="children" class="form-control" value="0" min="0">
                            </div>
                        </div>

                        <div class="total-pay-box">
                            <small><?= __('book_deposit_req') ?></small>
                            <h2 class="mb-2">RM 50.00</h2>
                            
                            <div class="booking-breakdown-box">
                                <div class="breakdown-row">
                                    <span><?= __('book_total_room') ?>:</span>
                                    <strong>RM <span id="display_room_total">0.00</span></strong>
                                </div>
                                <div class="breakdown-row mt-1">
                                    <span><?= __('book_remaining') ?>:</span>
                                    <strong>RM <span id="display_room_balance">0.00</span></strong>
                                </div>
                                <div class="breakdown-row mt-1">
                                    <span><?= __('book_security') ?>:</span>
                                    <span class="text-success font-weight-bold"><?= __('book_sec_inc') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs -->
                        <input type="hidden" name="payment_method" id="payment_method_input" value="gateway">

                        <!-- Payment Mode Toggles -->
                        <div class="payment-method-selector mb-4">
                            <label class="font-weight-bold small mb-2 d-block text-dark"><?= __('book_select_payment') ?></label>
                            <div class="d-flex flex-column flex-sm-row gap-2" style="gap: 12px;">
                                <div class="payment-option-card active" id="opt_gateway" onclick="selectPaymentMethod('gateway')">
                                    <div class="option-icon"><i class="fa-solid fa-credit-card"></i></div>
                                    <div class="option-details">
                                        <strong><?= __('book_instant_pay') ?></strong>
                                        <span><?= __('book_instant_desc') ?></span>
                                    </div>
                                </div>
                                <div class="payment-option-card" id="opt_manual" onclick="selectPaymentMethod('manual')">
                                    <div class="option-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                                    <div class="option-details">
                                        <strong><?= __('book_manual_pay') ?></strong>
                                        <span><?= __('book_manual_desc') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Gateway Info Section -->
                        <div id="section_gateway" class="bank-info-section">
                            <div class="deposit-alert-box mb-0 py-3 px-4" style="background: rgba(40, 167, 69, 0.08); border-left: 4px solid #28a745; border-radius: 8px;">
                                <div class="d-flex align-items-start">
                                    <div class="alert-icon-wrap mr-3" style="color: #28a745; font-size: 1.25rem;">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                    <div class="alert-body">
                                        <h6 class="alert-title font-weight-bold mb-1 text-success"><?= __('book_instant_title') ?></h6>
                                        <p class="alert-text mb-0 text-dark small" style="line-height: 1.5;">
                                            <?= __('book_instant_alert') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Manual Bank Info Section -->
                        <div id="section_manual" class="bank-info-section" style="display: none;">
                            <h6 class="font-weight-bold mb-3 text-dark" style="font-size: 14px;"><i class="fa fa-university mr-2 text-warning"></i> <?= __('book_bank_info') ?></h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small"><?= __('book_bank_name') ?>:</span>
                                <span class="font-weight-bold small text-dark">PUBLIC BANK BERHAD</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted small"><?= __('book_acc_no') ?>:</span>
                                <span class="font-weight-bold text-dark">8763780979</span>
                            </div>

                            <div class="deposit-alert-box mb-3 py-3 px-4" style="background: rgba(255, 193, 7, 0.08); border-left: 4px solid #ffc107; border-radius: 8px;">
                                <div class="d-flex align-items-start">
                                    <div class="alert-icon-wrap mr-3" style="color: #e0a800; font-size: 1.25rem;">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>
                                    <div class="alert-body">
                                        <h6 class="alert-title font-weight-bold mb-1" style="color: #856404;"><?= __('book_manual_title') ?></h6>
                                        <p class="alert-text mb-0 text-dark small" style="line-height: 1.5;">
                                            <?= __('book_manual_alert') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <label class="font-weight-bold small text-dark mb-2"><?= __('book_proof') ?></label>
                            <input type="file" id="payment_receipt" name="payment_receipt" class="form-control-file" accept="image/*,.pdf">
                        </div>

                        <div class="custom-control custom-checkbox mt-4 mb-2">
                            <input type="checkbox" class="custom-control-input" id="tnc_agree" name="tnc_agree" required onchange="validateForm()">
                            <label class="custom-control-label small text-dark" for="tnc_agree" style="font-weight: 600; line-height: 1.5; cursor: pointer;">
                                <?= __('book_tnc_agree') ?>
                            </label>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-confirm" disabled style="margin-top: 15px;">
                            <i class="fa fa-lock mr-2"></i> <?= __('book_confirm_btn') ?>
                        </button>
                    </form>
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

    <!-- PAYMENT GATEWAY SIMULATION MODAL -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.25); overflow: hidden;">
                <!-- Header -->
                <div class="modal-header bg-dark text-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title font-weight-bold" style="font-size: 16px; letter-spacing: -0.3px; color: white;"><i class="fa-solid fa-shield-halved mr-2 text-warning"></i> <?= __('modal_secure_checkout') ?></h5>
                    <button type="button" class="close text-white opacity-80" data-dismiss="modal" aria-label="Close" style="background:transparent; border:none; outline:none; font-size: 20px; color: white;">&times;</button>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body p-4" style="background: #fcfcfc;">
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <span class="text-muted small uppercase font-weight-bold d-block mb-1"><?= __('modal_deposit_pay') ?></span>
                        <h2 class="font-weight-bold mb-0 text-dark">RM 50.00</h2>
                    </div>

                    <!-- Payment Mode Tabs -->
                    <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active font-weight-bold small py-2" id="pills-card-tab" data-toggle="pill" href="#pills-card" role="tab" style="border-radius: 8px;"><i class="fa-solid fa-credit-card mr-2"></i> <?= __('modal_card_tab') ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link font-weight-bold small py-2" id="pills-fpx-tab" data-toggle="pill" href="#pills-fpx" role="tab" style="border-radius: 8px;"><i class="fa-solid fa-building-columns mr-2"></i> <?= __('modal_fpx_tab') ?></a>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <!-- Card Payment Tab -->
                        <div class="tab-pane fade show active" id="pills-card" role="tabpanel">
                            <div class="card-form-wrapper">
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold mb-1 text-dark"><?= __('modal_card_num') ?></label>
                                    <div class="input-group">
                                        <input type="text" id="cc_number" class="form-control" placeholder="4111 2222 3333 4444" maxlength="19" style="height: 42px; border-radius: 8px; font-size: 14px;">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-white" id="cc_icon" style="border-radius: 0 8px 8px 0; color: #888; border-left: none;"><i class="fa-solid fa-credit-card"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-7 form-group mb-3">
                                        <label class="small font-weight-bold mb-1 text-dark"><?= __('modal_card_expiry') ?></label>
                                        <input type="text" id="cc_expiry" class="form-control" placeholder="MM/YY" maxlength="5" style="height: 42px; border-radius: 8px; font-size: 14px;">
                                    </div>
                                    <div class="col-5 form-group mb-3">
                                        <label class="small font-weight-bold mb-1 text-dark"><?= __('modal_card_cvv') ?></label>
                                        <input type="password" id="cc_cvv" class="form-control" placeholder="123" maxlength="3" style="height: 42px; border-radius: 8px; font-size: 14px;">
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold mb-1 text-dark"><?= __('modal_card_holder') ?></label>
                                    <input type="text" id="cc_name" class="form-control" placeholder="<?= __('modal_card_holder_placeholder') ?>" style="height: 42px; border-radius: 8px; font-size: 14px;">
                                </div>
                            </div>
                        </div>

                        <!-- FPX Online Banking Tab -->
                        <div class="tab-pane fade" id="pills-fpx" role="tabpanel">
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold mb-1 text-dark"><?= __('modal_fpx_choose') ?></label>
                                <select id="fpx_bank" class="form-control" style="height: 42px; border-radius: 8px; font-size: 14px;">
                                    <option value=""><?= __('modal_fpx_select') ?></option>
                                    <option value="maybank">Maybank2U</option>
                                    <option value="cimb">CIMB Clicks</option>
                                    <option value="public">Public Bank</option>
                                    <option value="rhb">RHB Now</option>
                                    <option value="islam">Bank Islam</option>
                                    <option value="ambank">AmOnline</option>
                                    <option value="hlb">Hong Leong Connect</option>
                                </select>
                            </div>
                            <div class="alert alert-info py-2 px-3 mb-0" style="border-radius: 8px; font-size: 12px; line-height: 1.4;">
                                <i class="fa-solid fa-info-circle mr-1"></i> <?= __('modal_fpx_redirect_alert') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Button -->
                    <button type="button" id="payNowBtn" class="btn btn-primary btn-block py-2 mt-4 font-weight-bold" style="background: var(--primary-orange); border: none; border-radius: 10px; font-size: 14px; height: 46px;">
                        <?= __('modal_pay_securely') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESSING GATEWAY OVERLAY -->
    <div id="paymentProcessingOverlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.95); z-index: 9999; align-items: center; justify-content: center; color: white;">
        <div class="text-center">
            <div id="processingIconContainer" class="mb-3">
                <div id="processingSpinner" class="spinner-border text-warning" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
            </div>
            <h4 class="font-weight-bold" id="processingTitle" style="color: white; margin-bottom: 8px;"><?= __('modal_processing_title') ?></h4>
            <p class="text-muted small" id="processingStatus" style="font-size: 13px;"><?= __('modal_status_handshake') ?></p>
        </div>
    </div>

    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const price = <?= (float)$package_rs['price'] ?>;
        const booked = <?= json_encode($booked_ranges) ?>;
        const submitBtn = document.getElementById('submitBtn');
        const receiptInput = document.getElementById('payment_receipt');
        const totalPriceInput = document.getElementById('total_price_input');

        // Localized JS variables
        const msgCardComplete = <?= json_encode(__('modal_alert_card_complete')) ?>;
        const msgFpxSelect = <?= json_encode(__('modal_alert_fpx_select')) ?>;
        const msgProcessingAuthenticating = <?= json_encode(__('modal_status_authenticating')) ?>;
        const msgProcessingApproved = <?= json_encode(__('modal_status_approved')) ?>;

        function validateForm() {
            const inDate = document.getElementById('checkin_date').value;
            const outDate = document.getElementById('checkout_date').value;
            const paymentMethod = document.getElementById('payment_method_input').value;
            const hasFile = receiptInput.files.length > 0;
            const isTncChecked = document.getElementById('tnc_agree').checked;

            let nights = 0;
            if (inDate && outDate) {
                const d1 = new Date(inDate);
                const d2 = new Date(outDate);
                nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
            }

            if (nights > 0 && isTncChecked) {
                if (paymentMethod === 'gateway') {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = !hasFile;
                }
            } else {
                submitBtn.disabled = true;
            }
        }

        function selectPaymentMethod(method) {
            document.getElementById('payment_method_input').value = method;
            
            // Toggle active card
            document.getElementById('opt_gateway').classList.toggle('active', method === 'gateway');
            document.getElementById('opt_manual').classList.toggle('active', method === 'manual');
            
            // Toggle sections
            document.getElementById('section_gateway').style.display = (method === 'gateway') ? 'block' : 'none';
            document.getElementById('section_manual').style.display = (method === 'manual') ? 'block' : 'none';
            
            // Adjust validation requirements
            if (method === 'gateway') {
                receiptInput.removeAttribute('required');
            } else {
                receiptInput.setAttribute('required', 'required');
            }
            validateForm();
        }

        // Intercept form submit to show checkout modal
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const paymentMethod = document.getElementById('payment_method_input').value;
            if (paymentMethod === 'gateway') {
                e.preventDefault();
                $('#checkoutModal').modal('show');
            }
        });

        // Card number masking and icon detection
        document.getElementById('cc_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formatted;
            
            const ccIcon = document.getElementById('cc_icon');
            if (value.startsWith('4')) {
                ccIcon.innerHTML = '<i class="fab fa-cc-visa" style="color:#1a1f71; font-size:20px;"></i>';
            } else if (value.startsWith('5')) {
                ccIcon.innerHTML = '<i class="fab fa-cc-mastercard" style="color:#eb001b; font-size:20px;"></i>';
            } else {
                ccIcon.innerHTML = '<i class="fa-solid fa-credit-card"></i>';
            }
        });

        // Card expiry masking
        document.getElementById('cc_expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // CVV digit restriction
        document.getElementById('cc_cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Pay now button click handler
        document.getElementById('payNowBtn').addEventListener('click', function() {
            const activeTab = document.querySelector('#pills-tab .nav-link.active').id;
            
            if (activeTab === 'pills-card-tab') {
                const ccNum = document.getElementById('cc_number').value.trim();
                const ccExp = document.getElementById('cc_expiry').value.trim();
                const ccCvv = document.getElementById('cc_cvv').value.trim();
                const ccName = document.getElementById('cc_name').value.trim();
                
                if (ccNum.length < 19 || ccExp.length < 5 || ccCvv.length < 3 || ccName === '') {
                    alert(msgCardComplete);
                    return;
                }
            } else {
                const bank = document.getElementById('fpx_bank').value;
                if (!bank) {
                    alert(msgFpxSelect);
                    return;
                }
            }

            // Hide modal
            $('#checkoutModal').modal('hide');
            
            // Show processing overlay
            const overlay = document.getElementById('paymentProcessingOverlay');
            overlay.style.display = 'flex';
            
            const status = document.getElementById('processingStatus');
            
            setTimeout(() => {
                status.innerText = msgProcessingAuthenticating;
            }, 1000);

            setTimeout(() => {
                status.innerText = msgProcessingApproved;
                document.getElementById('processingIconContainer').innerHTML = '<i class="fa-solid fa-circle-check text-success fa-3x animate__animated animate__zoomIn"></i>';
            }, 2300);

            setTimeout(() => {
                document.getElementById('bookingForm').submit();
            }, 3300);
        });

        function calc() {
            const inDate = document.getElementById('checkin_date').value;
            const outDate = document.getElementById('checkout_date').value;
            if (inDate && outDate) {
                const d1 = new Date(inDate);
                const d2 = new Date(outDate);
                const nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
                if (nights > 0) {
                    const totalAmount = (nights * price).toFixed(2);
                    document.getElementById('display_room_total').innerText = totalAmount;
                    document.getElementById('display_room_balance').innerText = totalAmount;
                    document.getElementById('display_room_balance_text').innerText = totalAmount;
                    totalPriceInput.value = totalAmount;
                } else {
                    document.getElementById('display_room_total').innerText = "0.00";
                    document.getElementById('display_room_balance').innerText = "0.00";
                    document.getElementById('display_room_balance_text').innerText = "0.00";
                    totalPriceInput.value = "0.00";
                }
            }
            validateForm();
        }

        receiptInput.addEventListener('change', validateForm);

        const flatConfig = {
            minDate: "today",
            disable: booked,
            onChange: calc,
            dateFormat: "Y-m-d",
        };
        flatpickr("#checkin_date", flatConfig);
        flatpickr("#checkout_date", flatConfig);

        // Initialize Inline Availability Calendar
        flatpickr("#inline_calendar", {
            inline: true,
            minDate: "today",
            disable: booked,
            dateFormat: "Y-m-d",
            locale: {
                firstDayOfWeek: 1 // Start week on Monday
            }
        });
        
        // Trigger calc on load to set initial values if pre-filled
        calc();
    </script>
</body>

</html>