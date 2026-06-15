<?php
require_once 'db_connect.php'; // Auto start session & CSRF

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

$is_logged_in = isset($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

// --- HANDLING MESSAGES ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'ReviewSuccess') $success_msg = __('profile_msg_review_success');
    if ($_GET['msg'] == 'CancelSuccess') $success_msg = __('profile_msg_cancel_success');
    if ($_GET['msg'] == 'BalanceUploadSuccess') $success_msg = __('profile_msg_balance_success');
    if ($_GET['msg'] == 'Error') $error_msg = __('profile_msg_error');
}

// --- LOGIC 1: UPDATE PROFILE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = __('profile_err_csrf');
    } else {
        $full_name = strip_tags(trim($_POST['full_name']));
        $email     = trim($_POST['email']);
        $phone     = trim($_POST['phone']);
        $password  = $_POST['password'];

        // === VALIDASI INPUT ===
        if (empty($full_name)) {
            $error_msg = __('profile_err_name');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = __('profile_err_email');
        } elseif (!preg_match('/^(01[0-9])\d{7,8}$/', $phone)) {
            $error_msg = __('profile_err_phone');
        } elseif (!empty($password) && strlen($password) < 8) {
            $error_msg = __('profile_err_password');
        } elseif (!empty($password) && !preg_match('/[A-Z]/', $password)) {
            $error_msg = __('register_err_pass_upper');
        } elseif (!empty($password) && !preg_match('/[0-9]/', $password)) {
            $error_msg = __('register_err_pass_num');
        } else {

        $profile_pic  = null;
        $upload_error = false;

        // Handle profile picture upload if selected
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['profile_pic']['name'];
            $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filesize = $_FILES['profile_pic']['size'];

            if (!in_array($ext, $allowed)) {
                $error_msg    = __('profile_err_file_type');
                $upload_error = true;
            } elseif ($filesize > 2 * 1024 * 1024) {
                $error_msg    = __('profile_err_file_size');
                $upload_error = true;
            } else {
                $target_dir = 'uploads/profile/';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $new_name    = time() . '_profile_' . uniqid() . '.' . $ext;
                $destination = $target_dir . $new_name;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    $profile_pic = $new_name;

                    // Delete the old profile picture if exists
                    $stmt_old = $conn->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
                    $stmt_old->bind_param("i", $user_id);
                    $stmt_old->execute();
                    $res_old = $stmt_old->get_result()->fetch_assoc();
                    if ($res_old && !empty($res_old['profile_pic'])) {
                        $old_pic_path = $target_dir . $res_old['profile_pic'];
                        if (file_exists($old_pic_path)) {
                            @unlink($old_pic_path);
                        }
                    }
                } else {
                    $error_msg    = __('profile_err_file_upload');
                    $upload_error = true;
                }
            }
        }

        if (!$upload_error) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                if ($profile_pic !== null) {
                    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, password=?, profile_pic=? WHERE user_id=?");
                    $stmt->bind_param("sssssi", $full_name, $email, $phone, $hashed_password, $profile_pic, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, password=? WHERE user_id=?");
                    $stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed_password, $user_id);
                }
            } else {
                if ($profile_pic !== null) {
                    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, profile_pic=? WHERE user_id=?");
                    $stmt->bind_param("ssssi", $full_name, $email, $phone, $profile_pic, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
                    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
                }
            }


            if ($stmt->execute()) {
                $success_msg = __('profile_msg_profile_success');
                $_SESSION['full_name'] = $full_name;
            } else {
                $error_msg = __('profile_msg_profile_error');
            }
        } // end if (!$upload_error)
        } // end validation else
    } // end CSRF else
} // end POST check


// --- LOGIC 2: SUBMIT REVIEW ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = __('profile_err_csrf');
    } else {
        $booking_id = intval($_POST['booking_id']);
        $package_id = intval($_POST['package_id']);
        $rating     = intval($_POST['rating']);
        $comment    = strip_tags(trim($_POST['comment']));

        // 1. Verify ownership and Checked Out status
        $ownership = $conn->prepare("SELECT book_id FROM bookings WHERE book_id = ? AND user_id = ? AND status = 'Completed'");
        $ownership->bind_param("ii", $booking_id, $user_id);
        $ownership->execute();
        if ($ownership->get_result()->num_rows === 0) {
            $error_msg = "Anda hanya boleh meninggalkan ulasan untuk tempahan yang telah selesai (Checked Out).";
        } else {
            // 2. Check duplicate
            $dup = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? AND user_id = ?");
            $dup->bind_param("ii", $booking_id, $user_id);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $error_msg = "Anda telah pun menghantar ulasan untuk tempahan ini.";
            } else {
                $stmt_rev = $conn->prepare("INSERT INTO reviews (user_id, booking_id, package_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt_rev->bind_param("iiiis", $user_id, $booking_id, $package_id, $rating, $comment);

                if ($stmt_rev->execute()) {
                    header("Location: my_profile.php?msg=ReviewSuccess&tab=booking");
                    exit();
                } else {
                    $error_msg = __('profile_msg_review_error');
                }
            }
        }
    }
}

// --- FETCH DATA ---
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// AMBIL DATA BOOKING (Updated Query)
$bookings_sql = "SELECT b.*, p.package_name, p.price as base_price, r.review_id, r.rating as user_rating 
                 FROM bookings b 
                 JOIN packages p ON b.package_id = p.package_id 
                 LEFT JOIN reviews r ON b.book_id = r.booking_id
                 WHERE b.user_id = ? 
                 ORDER BY b.created_at DESC";
$stmt_b = $conn->prepare($bookings_sql);
$stmt_b->bind_param("i", $user_id);
$stmt_b->execute();
$bookings = $stmt_b->get_result();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= __('nav_profile') ?> | EasyStay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        body {
            background-color: #FAF9F6;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #2D2D2D;
        }

        /* Sidebar Styling */
        .profile-sidebar {
            background: white;
            border-radius: 20px;
            padding: 35px 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(197, 168, 128, 0.1);
        }

        .avatar-box {
            width: 100px;
            height: 100px;
            background: #FAF9F6;
            color: #C5A880;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
            border: 2px solid #C5A880;
            box-shadow: 0 8px 20px rgba(197, 168, 128, 0.15);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .profile-sidebar:hover .avatar-box {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(197, 168, 128, 0.25);
        }

        .user-name {
            font-weight: 800;
            color: #121212;
            margin-bottom: 4px;
            font-size: 18px;
            line-height: 1.3;
        }

        .user-email {
            color: #7A7A7A;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .nav-pills-custom .nav-link {
            color: #555;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: left;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .nav-pills-custom .nav-link i {
            width: 28px;
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .nav-pills-custom .nav-link:hover {
            background-color: #FAF9F6;
            color: #C5A880;
            border-color: rgba(197, 168, 128, 0.15);
        }
        .nav-pills-custom .nav-link:hover i {
            transform: translateX(2px);
        }

        .nav-pills-custom .nav-link.active {
            background-color: #C5A880;
            color: white;
            box-shadow: 0 8px 20px rgba(197, 168, 128, 0.2);
            border-color: #C5A880;
        }

        .nav-pills-custom a.text-danger {
            margin-top: 25px !important;
            border: 1px solid rgba(198, 40, 40, 0.15) !important;
            background: rgba(198, 40, 40, 0.02) !important;
            color: #C62828 !important;
            font-weight: 700;
            justify-content: center;
            border-radius: 12px;
        }
        .nav-pills-custom a.text-danger:hover {
            background: #C62828 !important;
            color: white !important;
            border-color: #C62828 !important;
            box-shadow: 0 6px 15px rgba(198, 40, 40, 0.15);
        }

        /* Content Panel */
        .content-panel {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(197, 168, 128, 0.08);
            min-height: 550px;
        }

        .panel-title {
            font-weight: 800;
            color: #121212;
            margin-bottom: 25px;
            font-size: 24px;
            letter-spacing: -0.5px;
        }

        /* Stat Cards */
        .stat-card {
            padding: 28px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        }

        .stat-card.orange {
            background: rgba(197, 168, 128, 0.08);
            color: #A48256;
            border-color: rgba(197, 168, 128, 0.15);
        }

        .stat-card.blue {
            background: #121212;
            color: #C5A880;
            border-color: #121212;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Booking Cards */
        .booking-card {
            border: 1px solid #EEEEEE;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-card:hover {
            border-color: #C5A880;
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(197, 168, 128, 0.12);
        }

        .booking-info h5 {
            font-weight: 800;
            margin-bottom: 8px;
            color: #121212;
            font-size: 16px;
        }

        .booking-dates {
            display: inline-flex;
            align-items: center;
            background: #FAF9F6;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            font-weight: 600;
            border: 1px solid rgba(197, 168, 128, 0.1);
            gap: 6px;
        }

        .booking-price {
            font-weight: 800;
            color: #C5A880;
            font-size: 16px;
            margin-top: 10px;
        }

        /* Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .badge-success-soft {
            background: rgba(46, 125, 50, 0.08);
            color: #2E7D32;
            border: 1px solid rgba(46, 125, 50, 0.15);
        }

        /* Tracker Stepper styling */
        .booking-tracker {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
            padding: 10px 0;
            width: 100%;
        }
        .tracker-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }
        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e2e8f0;
            z-index: 2;
        }
        .tracker-step.completed .step-circle {
            background: #2E7D32;
            color: #fff;
            box-shadow: 0 0 0 2px #2E7D32;
        }
        .tracker-step.active .step-circle {
            background: #C5A880;
            color: #fff;
            box-shadow: 0 0 0 2px #C5A880;
            transform: scale(1.1);
        }
        .step-label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .tracker-step.completed .step-label {
            color: #2E7D32;
        }
        .tracker-step.active .step-label {
            color: #C5A880;
        }
        .tracker-line {
            height: 4px;
            background: #e2e8f0;
            flex-grow: 1;
            margin: 0 -10px;
            position: relative;
            top: -12px;
            z-index: 1;
        }
        .tracker-line.completed {
            background: #2E7D32;
        }
        @media (max-width: 576px) {
            .booking-tracker {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .tracker-step {
                flex-direction: row;
                gap: 15px;
                flex: none;
                width: 100%;
            }
            .tracker-line {
                width: 4px;
                height: 20px;
                margin: -10px 0 -10px 16px;
                top: 0;
            }
            .step-label {
                margin-top: 0;
                text-align: left;
            }
        }

        .badge-danger-soft {
            background: rgba(198, 40, 40, 0.08);
            color: #C62828;
            border: 1px solid rgba(198, 40, 40, 0.15);
        }

        .badge-warning-soft {
            background: rgba(249, 168, 37, 0.08);
            color: #F9A825;
            border: 1px solid rgba(249, 168, 37, 0.15);
        }

        .badge-info-soft {
            background: rgba(0, 104, 168, 0.08);
            color: #0068A8;
            border: 1px solid rgba(0, 104, 168, 0.15);
        }

        .badge-muted {
            background: rgba(136, 136, 136, 0.08);
            color: #666;
            border: 1px solid rgba(136, 136, 136, 0.15);
            text-decoration: none !important;
        }

        /* Booking Cards Buttons Override */
        .booking-card .btn-info {
            background-color: transparent !important;
            border: 1.5px solid #C5A880 !important;
            color: #C5A880 !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }
        .booking-card .btn-info:hover {
            background-color: #C5A880 !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(197, 168, 128, 0.2) !important;
        }

        .booking-card .btn-success {
            background-color: #2E7D32 !important;
            border: none !important;
            color: white !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.2) !important;
            transition: all 0.2s ease !important;
        }
        .booking-card .btn-success:hover {
            background-color: #1b5e20 !important;
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3) !important;
            transform: translateY(-1px) !important;
        }

        .booking-card .btn-outline-danger {
            border: 1.5px solid rgba(198, 40, 40, 0.3) !important;
            color: #C62828 !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            background: transparent !important;
            transition: all 0.2s ease !important;
        }
        .booking-card .btn-outline-danger:hover {
            background-color: #C62828 !important;
            color: white !important;
            border-color: #C62828 !important;
            box-shadow: 0 4px 10px rgba(198, 40, 40, 0.2) !important;
        }

        .booking-card .btn-outline-dark {
            border: 1.5px solid #121212 !important;
            color: #121212 !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            padding: 8px 18px !important;
            border-radius: 30px !important;
            background: transparent !important;
            transition: all 0.2s ease !important;
        }
        .booking-card .btn-outline-dark:hover {
            background-color: #121212 !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(18, 18, 18, 0.2) !important;
        }

        /* Star Rating */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }

        .star-rating label {
            font-size: 32px;
            color: #E0E0E0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input:checked~label {
            color: #FFC107;
        }

        .star-rating input {
            display: none;
        }

        /* Settings Form Styling */
        .form-label-custom {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper-custom {
            position: relative;
            width: 100%;
        }

        .input-icon-custom {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #C5A880;
            font-size: 16px;
            transition: 0.3s;
            pointer-events: none;
        }

        .form-control-custom {
            height: 52px;
            border-radius: 12px;
            border: 1.5px solid #EAEAEA;
            padding: 0 20px 0 52px;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s ease;
            background: #FCFCFC;
            color: #1a1a1a;
            font-weight: 500;
            outline: none;
        }

        .form-control-custom:focus {
            border-color: #C5A880;
            background: #fff;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.08);
        }

        .form-control-custom:focus ~ .input-icon-custom {
            color: #A48256;
        }

        .btn-save-custom {
            background: #C5A880;
            color: white;
            border: none;
            height: 52px;
            padding: 0 40px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-save-custom:hover {
            background: #A48256;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.3);
            text-decoration: none !important;
            color: white !important;
        }

        .btn-save-custom:active {
            transform: translateY(0);
        }

        /* Modal Overhaul */
        .modal-content {
            border-radius: 20px !important;
            border: 1px solid rgba(197, 168, 128, 0.15) !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid #f0f0f0 !important;
            padding: 22px 28px !important;
            align-items: center;
            background-color: white !important;
        }

        .modal-header.bg-danger {
            background-color: white !important;
            color: #121212 !important;
        }
        .modal-header.bg-danger .modal-title {
            color: #C62828 !important;
        }
        .modal-header.bg-danger .close {
            color: #121212 !important;
            text-shadow: none !important;
            opacity: 0.5 !important;
        }

        .modal-header.bg-success {
            background-color: white !important;
            color: #121212 !important;
        }
        .modal-header.bg-success .modal-title {
            color: #2E7D32 !important;
        }
        .modal-header.bg-success .close {
            color: #121212 !important;
            text-shadow: none !important;
            opacity: 0.5 !important;
        }

        .modal-title {
            font-weight: 800 !important;
            font-size: 18px !important;
            color: #121212 !important;
        }

        .modal-body {
            padding: 28px !important;
        }

        .modal-footer {
            border-top: 1px solid #f0f0f0 !important;
            padding: 20px 28px !important;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0 !important;
            border-bottom: 1px solid #f0f0f0 !important;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none !important;
        }

        .info-label {
            color: #7A7A7A !important;
            font-weight: 500;
        }

        .info-val {
            font-weight: 700 !important;
            color: #121212 !important;
        }

        .info-val.text-warning {
            color: #C5A880 !important;
            font-size: 16px;
        }

        .modal .btn-block {
            border-radius: 10px !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 12px 20px !important;
            transition: all 0.2s !important;
        }

        .btn-outline-primary {
            border: 1.5px solid #C5A880 !important;
            color: #C5A880 !important;
            background: transparent !important;
        }
        .btn-outline-primary:hover {
            background: #C5A880 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(197, 168, 128, 0.2) !important;
        }

        .btn-outline-success {
            border: 1.5px solid #2E7D32 !important;
            color: #2E7D32 !important;
            background: transparent !important;
        }
        .btn-outline-success:hover {
            background: #2E7D32 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2) !important;
        }

        .modal-footer .btn-secondary {
            background-color: #FAF9F6 !important;
            color: #555 !important;
            border: 1.5px solid #ddd !important;
            font-weight: 700 !important;
            padding: 10px 24px !important;
            font-size: 13px !important;
            border-radius: 30px !important;
            transition: all 0.2s !important;
        }
        .modal-footer .btn-secondary:hover {
            background-color: #f0f0f0 !important;
            color: #121212 !important;
        }

        .modal-footer .btn-danger {
            background-color: #C62828 !important;
            border: none !important;
            color: white !important;
            font-weight: 700 !important;
            padding: 10px 24px !important;
            font-size: 13px !important;
            border-radius: 30px !important;
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.2) !important;
            transition: all 0.2s !important;
        }
        .modal-footer .btn-danger:hover {
            background-color: #b71c1c !important;
            box-shadow: 0 6px 15px rgba(198, 40, 40, 0.3) !important;
            transform: translateY(-1px);
        }

        .modal-footer .btn-success {
            background-color: #2E7D32 !important;
            border: none !important;
            color: white !important;
            font-weight: 700 !important;
            padding: 12px 24px !important;
            font-size: 13px !important;
            border-radius: 30px !important;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2) !important;
            transition: all 0.2s !important;
        }
        .modal-footer .btn-success:hover {
            background-color: #1b5e20 !important;
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3) !important;
            transform: translateY(-1px);
        }

        .form-group.p-3.border.rounded.bg-light {
            background-color: #FAF9F6 !important;
            border: 1.5px dashed rgba(197, 168, 128, 0.25) !important;
            border-radius: 12px !important;
            padding: 24px !important;
            transition: all 0.3s;
        }
        .form-group.p-3.border.rounded.bg-light:hover {
            border-color: #C5A880 !important;
        }
        .form-group.p-3.border.rounded.bg-light label {
            color: #C5A880 !important;
            font-size: 14px;
        }
        .form-group.p-3.border.rounded.bg-light input[type="file"] {
            border: none !important;
            background: transparent !important;
            padding: 10px 0;
            width: 100%;
            cursor: pointer;
        }

        .modal {
            z-index: 1055 !important;
        }

        /* Fix Z-Index */
        .modal-backdrop {
            z-index: 1050 !important;
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
        <div class="container" style="padding-top: 40px; padding-bottom: 40px;">

            <?php if ($success_msg): ?><div class="alert alert-success rounded-pill px-4 shadow-sm mb-4 border-0"><?= $success_msg ?></div><?php endif; ?>
            <?php if ($error_msg): ?><div class="alert alert-danger rounded-pill px-4 shadow-sm mb-4 border-0"><?= $error_msg ?></div><?php endif; ?>

            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="profile-sidebar mb-4">
                        <div class="avatar-box" style="overflow: hidden; padding: 0;">
                            <?php if (!empty($user['profile_pic']) && file_exists("uploads/profile/" . $user['profile_pic'])): ?>
                                <img src="uploads/profile/<?= htmlspecialchars($user['profile_pic']) ?>?v=<?= time() ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="user-name"><?= htmlspecialchars($user['full_name']) ?></h5>
                        <p class="user-email">@<?= htmlspecialchars($user['username']) ?></p>

                        <div class="nav flex-column nav-pills nav-pills-custom" id="v-pills-tab" role="tablist">
                            <a class="nav-link active" data-toggle="pill" href="#dashboard" role="tab"><i class="fas fa-th-large"></i> <?= __('profile_dashboard') ?></a>
                            <a class="nav-link" data-toggle="pill" href="#booking" role="tab"><i class="fas fa-calendar-check"></i> <?= __('profile_my_bookings') ?></a>
                            <a class="nav-link" data-toggle="pill" href="#settings" role="tab"><i class="fas fa-cog"></i> <?= __('profile_settings') ?></a>
                            <a href="logout.php" class="nav-link text-danger mt-3" style="justify-content: center;"><i class="fas fa-sign-out-alt"></i> <?= __('profile_logout') ?></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="tab-content" id="v-pills-tabContent">

                        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                            <div class="content-panel">
                                <h3 class="panel-title"><?= __('profile_welcome') ?>, <?= explode(' ', trim($user['full_name']))[0] ?>!</h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-card orange">
                                            <div>
                                                <div class="stat-value"><?= $bookings->num_rows ?></div>
                                                <div class="stat-label"><?= __('profile_total_bookings') ?></div>
                                            </div>
                                            <i class="fas fa-bookmark fa-2x" style="opacity: 0.2;"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-card blue">
                                            <div>
                                                <div class="stat-value"><?= date('d') ?></div>
                                                <div class="stat-label"><?= date('M Y') ?></div>
                                            </div>
                                            <i class="fas fa-calendar-day fa-2x" style="opacity: 0.2;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="booking" role="tabpanel">
                            <div class="content-panel">
                                <h4 class="panel-title"><?= __('profile_history') ?></h4>
                                <?php if ($bookings->num_rows > 0): ?>
                                    <?php while ($row = $bookings->fetch_assoc()):
                                        $status_clean = strtolower($row['status']);
                                        $is_cancelled = ($status_clean == 'cancelled');

                                        // Status Badge Logic
                                        $badgeClass = 'badge-warning-soft';
                                        if ($status_clean == 'accepted') $badgeClass = 'badge-info-soft';
                                        if ($status_clean == 'completed') $badgeClass = 'badge-success-soft';
                                        if ($status_clean == 'rejected') $badgeClass = 'badge-danger-soft';
                                        if ($is_cancelled) $badgeClass = 'badge-muted';
                                    ?>
                                        <div class="booking-card" style="<?= $is_cancelled ? 'opacity:0.6;' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-start w-100" style="gap: 20px; flex-wrap: wrap;">
                                                <div class="booking-info">
                                                    <h5><?= htmlspecialchars($row['package_name']) ?></h5>
                                                    <div class="booking-dates"><i class="far fa-calendar-alt mr-2"></i> <?= date('d M', strtotime($row['checkin_date'])) ?> - <?= date('d M Y', strtotime($row['checkout_date'])) ?></div>
                                                    <div class="booking-price"><?= __('profile_total') ?>: RM <?= number_format($row['total_price'], 2) ?></div>
                                                </div>
                                                <div class="text-right d-flex flex-column align-items-end" style="gap: 8px;">
                                                    <span class="status-badge <?= $badgeClass ?>"><?= __('status_' . strtolower($row['status'])) ?></span>

                                                    <div class="d-flex" style="gap: 5px;">
                                                        <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3" data-toggle="modal" data-target="#detailModal<?= $row['book_id'] ?>">
                                                            <i class="fas fa-info-circle"></i> <?= __('profile_details') ?>
                                                        </button>

                                                        <?php if (!$is_cancelled): ?>
                                                            <?php if ($status_clean == 'accepted' && empty($row['balance_receipt'])): ?>
                                                                <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3" data-toggle="modal" data-target="#balanceModal<?= $row['book_id'] ?>">
                                                                    <i class="fas fa-dollar-sign"></i> <?= __('profile_pay_balance') ?>
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($status_clean == 'pending' || $status_clean == 'accepted'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-toggle="modal" data-target="#cancelModal<?= $row['book_id'] ?>">
                                                                    <?= __('profile_cancel') ?>
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($status_clean == 'accepted' || $status_clean == 'completed'): ?>
                                                                <a href="generate_receipt.php?id=<?= $row['book_id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3"><?= __('profile_receipt') ?></a>
                                                            <?php endif; ?>

                                                            <?php if ($status_clean == 'completed' && !$row['review_id']): ?>
                                                                <button class="btn btn-sm btn-warning text-white rounded-pill px-3" data-toggle="modal" data-target="#rateModal<?= $row['book_id'] ?>"><?= __('profile_rate_us') ?></button>
                                                            <?php elseif ($row['review_id']): ?>
                                                                <span class="badge badge-light text-warning mt-1"><i class="fas fa-star"></i> <?= $row['user_rating'] ?>/5</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!$is_cancelled && $status_clean !== 'rejected'): 
                                                // Calculate progress step
                                                $step = 1; // Reserved
                                                if ($status_clean == 'accepted' || $status_clean == 'completed' || $row['payment_status'] == 'Deposit Paid' || $row['payment_status'] == 'Fully Paid') {
                                                    $step = 2; // Deposit Paid
                                                }
                                                
                                                $today = date('Y-m-d');
                                                if ($status_clean == 'completed' || ($status_clean == 'accepted' && $today >= $row['checkin_date'])) {
                                                    $step = 3; // Checked In
                                                }
                                                if ($status_clean == 'completed') {
                                                    $step = 4; // Refunded & Done
                                                }
                                            ?>
                                                <div class="booking-stepper-wrap w-100 mt-4 pt-3 border-top">
                                                    <div class="booking-tracker">
                                                         <div class="tracker-step <?= $step >= 1 ? 'completed' : '' ?> <?= $step == 1 ? 'active' : '' ?>">
                                                             <div class="step-circle"><i class="fa fa-calendar-plus"></i></div>
                                                             <div class="step-label"><?= __('step_reserved') ?></div>
                                                         </div>
                                                         <div class="tracker-line <?= $step >= 2 ? 'completed' : '' ?>"></div>
                                                         
                                                         <div class="tracker-step <?= $step >= 2 ? 'completed' : '' ?> <?= $step == 2 ? 'active' : '' ?>">
                                                             <div class="step-circle"><i class="fa fa-wallet"></i></div>
                                                             <div class="step-label"><?= __('step_deposit') ?></div>
                                                         </div>
                                                         <div class="tracker-line <?= $step >= 3 ? 'completed' : '' ?>"></div>
                                                         
                                                         <div class="tracker-step <?= $step >= 3 ? 'completed' : '' ?> <?= $step == 3 ? 'active' : '' ?>">
                                                             <div class="step-circle"><i class="fa fa-key"></i></div>
                                                             <div class="step-label"><?= __('step_checkin') ?></div>
                                                         </div>
                                                         <div class="tracker-line <?= $step >= 4 ? 'completed' : '' ?>"></div>
                                                         
                                                         <div class="tracker-step <?= $step >= 4 ? 'completed' : '' ?> <?= $step == 4 ? 'active' : '' ?>">
                                                             <div class="step-circle"><i class="fa fa-handshake"></i></div>
                                                             <div class="step-label"><?= __('step_done') ?></div>
                                                         </div>
                                                     </div>
                                                 </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <h5 class="text-muted"><?= __('profile_no_bookings') ?></h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <div class="content-panel">
                                <h4 class="panel-title mb-1"><?= __('settings_title') ?></h4>
                                <p class="text-muted small mb-4" style="font-size: 14px;"><?= __('settings_desc') ?></p>
                                
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label-custom"><?= __('settings_name') ?></label>
                                            <div class="input-wrapper-custom">
                                                <i class="fas fa-user input-icon-custom"></i>
                                                <input type="text" name="full_name" class="form-control-custom" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label-custom"><?= __('settings_email') ?></label>
                                            <div class="input-wrapper-custom">
                                                <i class="fas fa-envelope input-icon-custom"></i>
                                                <input type="email" name="email" class="form-control-custom" value="<?= htmlspecialchars($user['email']) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label-custom"><?= __('settings_phone') ?></label>
                                            <div class="input-wrapper-custom">
                                                <i class="fas fa-phone input-icon-custom"></i>
                                                <input type="text" name="phone" class="form-control-custom" value="<?= htmlspecialchars($user['phone']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label-custom"><?= __('settings_password') ?></label>
                                            <div class="input-wrapper-custom">
                                                <i class="fas fa-lock input-icon-custom"></i>
                                                <input type="password" name="password" id="profile_password" class="form-control-custom" placeholder="<?= __('settings_pass_placeholder') ?>">
                                            </div>
                                            <div id="pwd-strength-profile" style="font-size:11px; margin-top:5px; color:#888;"></div>
                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center mb-4">
                                            <div class="current-avatar-preview" style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; margin: 0 auto 10px; border: 2px solid #C5A880; box-shadow: 0 4px 10px rgba(197,168,128,0.15); background: #FAF9F6; display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($user['profile_pic']) && file_exists("uploads/profile/" . $user['profile_pic'])): ?>
                                                    <img src="uploads/profile/<?= htmlspecialchars($user['profile_pic']) ?>?v=<?= time() ?>" alt="Current Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fas fa-user" style="font-size: 30px; color: #C5A880;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span style="font-size: 10px; font-weight: 700; color: #C5A880; text-transform: uppercase; letter-spacing: 0.5px;"><?= __('settings_current_photo') ?></span>
                                        </div>
                                        <div class="col-md-9 mb-4">
                                            <label class="form-label-custom"><?= __('settings_avatar') ?></label>
                                            <div class="input-wrapper-custom">
                                                <i class="fas fa-image input-icon-custom"></i>
                                                <input type="file" name="profile_pic" class="form-control-custom" accept="image/*" style="padding-top: 13px; padding-left: 52px;">
                                            </div>
                                            <small class="text-muted mt-1 d-block" style="font-size: 11px;"><?= __('settings_avatar_desc') ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right mt-3">
                                        <button type="submit" name="update_profile" class="btn-save-custom">
                                            <i class="fas fa-save mr-2"></i> <?= __('settings_save') ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php
    $bookings->data_seek(0);
    while ($row = $bookings->fetch_assoc()):
        $status_clean = strtolower($row['status']);
    ?>

        <div class="modal fade" id="detailModal<?= $row['book_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold"><?= __('profile_details_title') ?> #<?= $row['book_id'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="info-row"><span class="info-label"><?= __('nav_package') ?></span><span class="info-val"><?= htmlspecialchars($row['package_name']) ?></span></div>
                        <div class="info-row"><span class="info-label"><?= __('home_checkin') ?></span><span class="info-val"><?= date('d M Y', strtotime($row['checkin_date'])) ?></span></div>
                        <div class="info-row"><span class="info-label"><?= __('home_checkout') ?></span><span class="info-val"><?= date('d M Y', strtotime($row['checkout_date'])) ?></span></div>
                        <div class="info-row"><span class="info-label"><?= __('pkg_guests') ?></span><span class="info-val"><?= $row['adults'] ?> <?= __('profile_details_adults') ?>, <?= $row['children'] ?> <?= __('profile_details_children') ?></span></div>
                        <div class="info-row"><span class="info-label"><?= __('pkg_price_label') ?></span><span class="info-val text-warning">RM <?= number_format($row['total_price'], 2) ?></span></div>
                        <div class="info-row"><span class="info-label">Status</span><span class="info-val"><?= __('status_' . strtolower($row['status'])) ?></span></div>

                        <div class="mt-3 bg-light p-3 rounded">
                            <p class="mb-1 small font-weight-bold text-muted"><?= __('profile_details_dep_receipt') ?></p>
                            <?php if ($row['receipt_path']): ?>
                                <a href="admin/uploads/receipts/<?= $row['receipt_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-block"><?= __('profile_details_view_file') ?></a>
                            <?php else: ?>
                                <span class="text-muted small"><?= __('profile_details_not_uploaded') ?></span>
                            <?php endif; ?>

                            <?php if ($row['balance_receipt']): ?>
                                <hr>
                                <p class="mb-1 small font-weight-bold text-muted"><?= __('profile_details_bal_receipt') ?></p>
                                <a href="admin/uploads/receipts/<?= $row['balance_receipt'] ?>" target="_blank" class="btn btn-sm btn-outline-success btn-block"><?= __('profile_details_view_file') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancelModal<?= $row['book_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <?php
                        $days_to_checkin = (strtotime($row['checkin_date']) - time()) / (60 * 60 * 24);
                        $is_refundable = ($days_to_checkin >= 7);
                    ?>
                    <div class="modal-header <?= $is_refundable ? 'bg-info' : 'bg-danger' ?> text-white">
                        <h5 class="modal-title font-weight-bold">
                            <?= $is_refundable ? __('profile_cancel_refund_title') : __('profile_cancel_forfeit_title') ?>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="cancel_booking.php" method="POST">
                        <div class="modal-body text-center">
                            <input type="hidden" name="booking_id" value="<?= $row['book_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <p class="mb-3"><?= __('profile_cancel_confirm') ?></p>

                            <?php if ($status_clean == 'accepted'): ?>
                                <?php if ($is_refundable): ?>
                                    <div class="alert alert-info text-left small border-0 shadow-sm">
                                        <i class="fas fa-info-circle mr-2"></i> <strong><?= __('profile_cancel_refund_title') ?></strong><br>
                                        <?= __('profile_cancel_refund_text') ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger text-left small border-0 shadow-sm">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> <strong><?= __('profile_cancel_forfeit_title') ?></strong><br>
                                        <?= __('profile_cancel_forfeit_text') ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer justify-content-center border-0">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal"><?= __('profile_cancel_keep') ?></button>
                            <button type="submit" class="btn <?= $is_refundable ? 'btn-info' : 'btn-danger' ?> rounded-pill px-4 text-white"><?= __('profile_cancel_yes') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="balanceModal<?= $row['book_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold"><?= __('profile_balance_title') ?></h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="upload_balance.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?= $row['book_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <p class="small text-muted mb-3"><?= __('profile_balance_desc') ?></p>

                            <div class="form-group p-3 border rounded bg-light text-center">
                                <label class="font-weight-bold mb-2 d-block text-success"><i class="fas fa-cloud-upload-alt fa-2x"></i><br><?= __('profile_balance_select') ?></label>
                                <input type="file" name="balance_receipt" class="form-control-file" required accept=".jpg,.jpeg,.png,.pdf">
                                <small class="d-block mt-2 text-muted"><?= __('profile_balance_format') ?></small>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-success rounded-pill px-4 btn-block font-weight-bold"><?= __('profile_balance_btn') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($status_clean == 'completed' && !$row['review_id']): ?>
            <div class="modal fade" id="rateModal<?= $row['book_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header d-block text-center border-0 pb-0">
                            <h5 class="modal-title font-weight-bold"><?= __('profile_rate_title') ?></h5>
                            <p class="text-muted small"><?= htmlspecialchars($row['package_name']) ?></p>
                        </div>
                        <form method="POST">
                            <div class="modal-body text-center">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $row['book_id'] ?>">
                                <input type="hidden" name="package_id" value="<?= $row['package_id'] ?>">
                                <div class="star-rating">
                                    <input type="radio" id="s5-<?= $row['book_id'] ?>" name="rating" value="5" /><label for="s5-<?= $row['book_id'] ?>">★</label>
                                    <input type="radio" id="s4-<?= $row['book_id'] ?>" name="rating" value="4" /><label for="s4-<?= $row['book_id'] ?>">★</label>
                                    <input type="radio" id="s3-<?= $row['book_id'] ?>" name="rating" value="3" /><label for="s3-<?= $row['book_id'] ?>">★</label>
                                    <input type="radio" id="s2-<?= $row['book_id'] ?>" name="rating" value="2" /><label for="s2-<?= $row['book_id'] ?>">★</label>
                                    <input type="radio" id="s1-<?= $row['book_id'] ?>" name="rating" value="1" required /><label for="s1-<?= $row['book_id'] ?>">★</label>
                                </div>
                                <textarea name="comment" class="form-control" rows="3" placeholder="<?= __('profile_rate_placeholder') ?>" required style="border-radius:10px;"></textarea>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pt-0">
                                <button type="submit" name="submit_review" class="btn btn-warning text-white rounded-pill px-5 font-weight-bold"><?= __('profile_rate_submit') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endwhile; ?>

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
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            $('.nav-pills a[href="#' + tabParam + '"]').tab('show');
        }

        // Password Strength Checker for Profile Update
        const labelWeak = <?= json_encode(__('js_strength_weak')) ?>;
        const labelMedium = <?= json_encode(__('js_strength_medium')) ?>;
        const labelGood = <?= json_encode(__('js_strength_good')) ?>;
        const labelStrong = <?= json_encode(__('js_strength_strong')) ?>;
        const labelNeed = <?= json_encode(__('js_strength_need')) ?>;
        const labelMinChar = <?= json_encode(__('js_strength_min_char')) ?>;
        const labelUppercase = <?= json_encode(__('js_strength_uppercase')) ?>;
        const labelNumber = <?= json_encode(__('js_strength_number')) ?>;

        document.getElementById('profile_password').addEventListener('input', function() {
            const val = this.value;
            const el  = document.getElementById('pwd-strength-profile');
            if (val === '') { el.innerHTML = ''; return; }
            let score = 0;
            let tips  = [];

            if (val.length >= 8)          score++; else tips.push(labelMinChar);
            if (/[A-Z]/.test(val))        score++; else tips.push(labelUppercase);
            if (/[0-9]/.test(val))        score++; else tips.push(labelNumber);
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const labels = ['', '⚠️ ' + labelWeak, '⚠️ ' + labelMedium, '✅ ' + labelGood, '✅ ' + labelStrong];
            const colors = ['', '#e74c3c', '#f39c12', '#27ae60', '#1e8449'];
            el.style.color = colors[score] || '#888';
            el.innerHTML = score > 0 ? labels[score] + (tips.length ? ' — ' + labelNeed + ': ' + tips.join(', ') : '') : '';
        });
    </script>
</body>

</html>
