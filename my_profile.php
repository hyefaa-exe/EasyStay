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

// --- HANDLING MESSAGES ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'ReviewSuccess') $success_msg = "Thank you! Your review has been submitted.";
    if ($_GET['msg'] == 'CancelSuccess') $success_msg = "Booking has been cancelled successfully.";
    if ($_GET['msg'] == 'BalanceUploadSuccess') $success_msg = "Balance receipt uploaded successfully! Admin will verify shortly.";
    if ($_GET['msg'] == 'Error') $error_msg = "An error occurred. Please try again.";
}

// --- LOGIC 1: UPDATE PROFILE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security Error (CSRF). Please refresh and try again.";
    } else {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, password=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        }

        if ($stmt->execute()) {
            $success_msg = "Profile updated successfully!";
            $_SESSION['full_name'] = $full_name;
        } else {
            $error_msg = "Failed to update profile.";
        }
    }
}

// --- LOGIC 2: SUBMIT REVIEW ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security Error (CSRF).";
    } else {
        $booking_id = $_POST['booking_id'];
        $package_id = $_POST['package_id'];
        $rating = $_POST['rating'];
        $comment = trim($_POST['comment']);

        $stmt_rev = $conn->prepare("INSERT INTO reviews (user_id, booking_id, package_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt_rev->bind_param("iiiis", $user_id, $booking_id, $package_id, $rating, $comment);

        if ($stmt_rev->execute()) {
            header("Location: my_profile.php?msg=ReviewSuccess&tab=booking");
            exit();
        } else {
            $error_msg = "Failed to submit review.";
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
    <title>My Profile | Ulu Garden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background-color: #F5F7FA;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .profile-sidebar {
            background: white;
            border-radius: 16px;
            padding: 30px 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            text-align: center;
        }

        .avatar-box {
            width: 90px;
            height: 90px;
            background: #FFF0E6;
            color: #FF7F32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 15px;
            border: 3px solid #ffffff;
            box-shadow: 0 3px 10px rgba(255, 127, 50, 0.2);
        }

        .user-name {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 2px;
        }

        .user-email {
            color: #888;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .nav-pills-custom .nav-link {
            color: #555;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: 0.2s;
            text-align: left;
            display: flex;
            align-items: center;
        }

        .nav-pills-custom .nav-link i {
            width: 30px;
            font-size: 18px;
        }

        .nav-pills-custom .nav-link:hover {
            background-color: #F8F9FA;
            color: #FF7F32;
        }

        .nav-pills-custom .nav-link.active {
            background-color: #FF7F32;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 127, 50, 0.3);
        }

        .content-panel {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            min-height: 550px;
        }

        .panel-title {
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .stat-card {
            padding: 25px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card.orange {
            background: #FFF5EB;
            color: #C05600;
        }

        .stat-card.blue {
            background: #EBF8FF;
            color: #0068A8;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .booking-card {
            border: 1px solid #EEEEEE;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-card:hover {
            border-color: #FF7F32;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .booking-info h5 {
            font-weight: 700;
            margin-bottom: 5px;
            color: #1a1a1a;
        }

        .booking-dates {
            display: inline-flex;
            align-items: center;
            background: #F8F9FA;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            font-weight: 500;
        }

        .booking-price {
            font-weight: 700;
            color: #FF7F32;
            font-size: 15px;
            margin-top: 8px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success-soft {
            background: #E6F8EB;
            color: #0F9D58;
        }

        .badge-danger-soft {
            background: #FEEBEB;
            color: #D93025;
        }

        .badge-warning-soft {
            background: #FFF8E1;
            color: #F9A825;
        }

        .badge-info-soft {
            background: #EBF8FF;
            color: #0068A8;
        }

        .badge-muted {
            background: #f3f3f3;
            color: #888;
            text-decoration: line-through;
        }

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

        .form-control-custom {
            height: 50px;
            border-radius: 10px;
            border: 1px solid #E0E0E0;
            padding: 0 20px;
            font-size: 15px;
            width: 100%;
            transition: 0.3s;
        }

        .btn-save {
            background: #FF7F32;
            color: white;
            border: none;
            height: 50px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        /* Modal Info */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #888;
            font-size: 14px;
        }

        .info-val {
            font-weight: 600;
            color: #333;
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
        <div class="container-fluid h-100">
            <div class="row align-items-center h-100">
                <div class="col-xl-5 col-lg-5 d-none d-lg-block">
                    <nav>
                        <ul id="navigation">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="package.php">Package</a></li>
                            <li><a href="about.php">About</a></li>
                            <li><a href="gallery.php">Gallery</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="my_profile.php" class="active-link">My Profile</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-2 col-lg-2 text-center">
                    <a href="index.php" class="logo-link"><img src="img/logo.png" alt="Logo"></a>
                </div>
                <div class="col-xl-5 col-lg-5 text-right">
                    <a href="logout.php" class="auth-btn" style="background: #ff7b00; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; text-decoration: none;">Logout</a>
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
                        <div class="avatar-box"><i class="fas fa-user"></i></div>
                        <h5 class="user-name"><?= htmlspecialchars($user['full_name']) ?></h5>
                        <p class="user-email">@<?= htmlspecialchars($user['username']) ?></p>

                        <div class="nav flex-column nav-pills nav-pills-custom" id="v-pills-tab" role="tablist">
                            <a class="nav-link active" data-toggle="pill" href="#dashboard" role="tab"><i class="fas fa-th-large"></i> Dashboard</a>
                            <a class="nav-link" data-toggle="pill" href="#booking" role="tab"><i class="fas fa-calendar-check"></i> My Bookings</a>
                            <a class="nav-link" data-toggle="pill" href="#settings" role="tab"><i class="fas fa-cog"></i> Settings</a>
                            <a href="logout.php" class="nav-link text-danger mt-3" style="justify-content: center;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="tab-content" id="v-pills-tabContent">

                        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                            <div class="content-panel">
                                <h3 class="panel-title">Welcome, <?= explode(' ', trim($user['full_name']))[0] ?>!</h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="stat-card orange">
                                            <div>
                                                <div class="stat-value"><?= $bookings->num_rows ?></div>
                                                <div class="stat-label">Total Bookings</div>
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
                                <h4 class="panel-title">Booking History</h4>
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
                                            <div class="booking-info">
                                                <h5><?= htmlspecialchars($row['package_name']) ?></h5>
                                                <div class="booking-dates"><i class="far fa-calendar-alt mr-2"></i> <?= date('d M', strtotime($row['checkin_date'])) ?> - <?= date('d M Y', strtotime($row['checkout_date'])) ?></div>
                                                <div class="booking-price">Total: RM <?= number_format($row['total_price'], 2) ?></div>
                                            </div>
                                            <div class="text-right d-flex flex-column align-items-end" style="gap: 8px;">
                                                <span class="status-badge <?= $badgeClass ?>"><?= $row['status'] ?></span>

                                                <div class="d-flex" style="gap: 5px;">
                                                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3" data-toggle="modal" data-target="#detailModal<?= $row['book_id'] ?>">
                                                        <i class="fas fa-info-circle"></i> Details
                                                    </button>

                                                    <?php if (!$is_cancelled): ?>
                                                        <?php if ($status_clean == 'accepted' && empty($row['balance_receipt'])): ?>
                                                            <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3" data-toggle="modal" data-target="#balanceModal<?= $row['book_id'] ?>">
                                                                <i class="fas fa-dollar-sign"></i> Pay Balance
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($status_clean == 'pending' || $status_clean == 'accepted'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-toggle="modal" data-target="#cancelModal<?= $row['book_id'] ?>">
                                                                Cancel
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($status_clean == 'accepted' || $status_clean == 'completed'): ?>
                                                            <a href="generate_receipt.php?id=<?= $row['book_id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3">Receipt</a>
                                                        <?php endif; ?>

                                                        <?php if ($status_clean == 'completed' && !$row['review_id']): ?>
                                                            <button class="btn btn-sm btn-warning text-white rounded-pill px-3" data-toggle="modal" data-target="#rateModal<?= $row['book_id'] ?>">Rate Us</button>
                                                        <?php elseif ($row['review_id']): ?>
                                                            <span class="badge badge-light text-warning mt-1"><i class="fas fa-star"></i> <?= $row['user_rating'] ?>/5</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <h5 class="text-muted">No bookings found.</h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <div class="content-panel">
                                <h4 class="panel-title">Account Settings</h4>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="mb-3"><label class="small font-weight-bold">Full Name</label><input type="text" name="full_name" class="form-control-custom" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                                    <div class="mb-3"><label class="small font-weight-bold">Email</label><input type="email" name="email" class="form-control-custom" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                                    <div class="mb-3"><label class="small font-weight-bold">Phone</label><input type="text" name="phone" class="form-control-custom" value="<?= htmlspecialchars($user['phone']) ?>" required></div>
                                    <div class="mb-4"><label class="small font-weight-bold">New Password (Optional)</label><input type="password" name="password" class="form-control-custom" placeholder="Leave blank to keep current"></div>
                                    <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
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
                        <h5 class="modal-title font-weight-bold">Booking Details #<?= $row['book_id'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="info-row"><span class="info-label">Package</span><span class="info-val"><?= htmlspecialchars($row['package_name']) ?></span></div>
                        <div class="info-row"><span class="info-label">Check-In</span><span class="info-val"><?= date('d M Y', strtotime($row['checkin_date'])) ?></span></div>
                        <div class="info-row"><span class="info-label">Check-Out</span><span class="info-val"><?= date('d M Y', strtotime($row['checkout_date'])) ?></span></div>
                        <div class="info-row"><span class="info-label">Guests</span><span class="info-val"><?= $row['adults'] ?> Adults, <?= $row['children'] ?> Children</span></div>
                        <div class="info-row"><span class="info-label">Total Price</span><span class="info-val text-warning">RM <?= number_format($row['total_price'], 2) ?></span></div>
                        <div class="info-row"><span class="info-label">Status</span><span class="info-val"><?= $row['status'] ?></span></div>

                        <div class="mt-3 bg-light p-3 rounded">
                            <p class="mb-1 small font-weight-bold text-muted">Deposit Receipt:</p>
                            <?php if ($row['receipt_path']): ?>
                                <a href="admin/uploads/receipts/<?= $row['receipt_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-block">View File</a>
                            <?php else: ?>
                                <span class="text-muted small">Not uploaded</span>
                            <?php endif; ?>

                            <?php if ($row['balance_receipt']): ?>
                                <hr>
                                <p class="mb-1 small font-weight-bold text-muted">Balance Receipt:</p>
                                <a href="admin/uploads/receipts/<?= $row['balance_receipt'] ?>" target="_blank" class="btn btn-sm btn-outline-success btn-block">View File</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancelModal<?= $row['book_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title font-weight-bold">Cancel Booking?</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="cancel_booking.php" method="POST">
                        <div class="modal-body text-center">
                            <input type="hidden" name="booking_id" value="<?= $row['book_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <p class="mb-3">Are you sure you want to cancel this booking?</p>

                            <?php if ($status_clean == 'accepted'): ?>
                                <div class="alert alert-warning text-left small border-0 shadow-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> <strong>IMPORTANT WARNING:</strong><br>
                                    Since your booking has been <u>Accepted</u> by admin, cancelling now means your <strong>deposit will be forfeited (burned)</strong> and is non-refundable.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer justify-content-center border-0">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">Keep Booking</button>
                            <button type="submit" class="btn btn-danger rounded-pill px-4">Yes, Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="balanceModal<?= $row['book_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold">Upload Balance Payment</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="upload_balance.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?= $row['book_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <p class="small text-muted mb-3">Please upload the payment receipt for the remaining balance.</p>

                            <div class="form-group p-3 border rounded bg-light text-center">
                                <label class="font-weight-bold mb-2 d-block text-success"><i class="fas fa-cloud-upload-alt fa-2x"></i><br>Select Receipt File</label>
                                <input type="file" name="balance_receipt" class="form-control-file" required accept=".jpg,.jpeg,.png,.pdf">
                                <small class="d-block mt-2 text-muted">Format: JPG, PNG, PDF</small>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" class="btn btn-success rounded-pill px-4 btn-block font-weight-bold">Upload Receipt</button>
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
                            <h5 class="modal-title font-weight-bold">Rate Your Stay</h5>
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
                                <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..." required style="border-radius:10px;"></textarea>
                            </div>
                            <div class="modal-footer justify-content-center border-0 pt-0">
                                <button type="submit" name="submit_review" class="btn btn-warning text-white rounded-pill px-5 font-weight-bold">Submit Review</button>
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
                <p>Copyright Ulu Garden © 2025. All rights reserved.</p>
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
    </script>
</body>

</html>