<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
require_once 'db_connect.php';
require_once 'email.php';

$id  = intval($_GET['id']);
$msg = "";
$msg_type = "success";

// PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status         = $_POST['status'];
    $new_payment_status = $_POST['payment_status'];

    $stmt = $conn->prepare("UPDATE bookings SET status=?, payment_status=? WHERE book_id=?");
    $stmt->bind_param("ssi", $new_status, $new_payment_status, $id);

    if ($stmt->execute()) {
        $user_sql = "SELECT u.email, u.full_name, b.checkin_date, b.checkout_date, b.total_price, p.package_name 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.user_id 
                     JOIN packages p ON b.package_id = p.package_id
                     WHERE b.book_id = ?";
        $stmt_user = $conn->prepare($user_sql);
        $stmt_user->bind_param("i", $id);
        $stmt_user->execute();
        $user_res = $stmt_user->get_result()->fetch_assoc();

        if ($user_res) {
            $checkin  = date('d M Y', strtotime($user_res['checkin_date']));
            $checkout = date('d M Y', strtotime($user_res['checkout_date']));
            $balance  = ($new_payment_status == 'Fully Paid') ? 0.00 : $user_res['total_price'];
            $emailDetails = [
                'booking_id'     => $id,
                'package_name'   => $user_res['package_name'],
                'checkin'        => $checkin,
                'checkout'       => $checkout,
                'status'         => $new_status,
                'payment_status' => $new_payment_status,
                'balance'        => $balance
            ];
            sendBookingStatusEmail($user_res['email'], $user_res['full_name'], $emailDetails, $id);
        }

        header("Location: manage_bookings.php?updated=1");
        exit();
    } else {
        $msg = "Error: " . $conn->error;
        $msg_type = "danger";
    }
}

// FETCH DATA
$sql     = "SELECT b.*, u.full_name, u.email, p.package_name FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN packages p ON b.package_id = p.package_id WHERE b.book_id = ?";
$stmt    = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) { echo "Booking not found."; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking #<?= $id ?> | EasyStay Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Edit Booking</div>
            <div class="topbar-breadcrumb">Update status for Booking #<?= $id ?> — <?= htmlspecialchars($booking['full_name']) ?></div>
        </div>
        <div class="topbar-right">
            <a href="manage_bookings.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
    </div>

    <div class="admin-content" style="max-width: 720px;">

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <i class="fas fa-exclamation-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Booking Summary</h3>
                <span class="badge status-<?= $booking['status'] ?>"><?= $booking['status'] ?></span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">Customer</div>
                        <div class="info-item-value"><?= htmlspecialchars($booking['full_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Package</div>
                        <div class="info-item-value" style="color:var(--gold);"><?= htmlspecialchars($booking['package_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Check-in</div>
                        <div class="info-item-value"><?= date('d M Y', strtotime($booking['checkin_date'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Check-out</div>
                        <div class="info-item-value"><?= date('d M Y', strtotime($booking['checkout_date'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Total Price</div>
                        <div class="info-item-value" style="color:var(--slate-900); font-size:1.1rem;">RM <?= number_format($booking['total_price'], 2) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Email</div>
                        <div class="info-item-value" style="font-size:0.82rem;"><?= htmlspecialchars($booking['email']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Update Status</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Booking Status</label>
                        <select name="status">
                            <option value="Pending"   <?= $booking['status']=='Pending'   ?'selected':'' ?>>⏳ Pending</option>
                            <option value="Accepted"  <?= $booking['status']=='Accepted'  ?'selected':'' ?>>✅ Accepted (Room Locked)</option>
                            <option value="Rejected"  <?= $booking['status']=='Rejected'  ?'selected':'' ?>>❌ Rejected</option>
                            <option value="Cancelled" <?= $booking['status']=='Cancelled' ?'selected':'' ?>>🚫 Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status">
                            <option value="Pending Deposit" <?= $booking['payment_status']=='Pending Deposit'?'selected':'' ?>>💳 Pending Deposit</option>
                            <option value="Deposit Paid"    <?= $booking['payment_status']=='Deposit Paid'   ?'selected':'' ?>>✔ Deposit Paid</option>
                            <option value="Pending Balance" <?= $booking['payment_status']=='Pending Balance'?'selected':'' ?>>⏳ Pending Balance</option>
                            <option value="Fully Paid"      <?= $booking['payment_status']=='Fully Paid'     ?'selected':'' ?>>💰 Fully Paid</option>
                        </select>
                    </div>
                    <div style="background:var(--info-bg); border:1px solid rgba(59,130,246,0.15); border-radius:8px; padding:10px 14px; margin-bottom:16px;">
                        <span style="font-size:0.72rem; color:var(--info); font-weight:700;">
                            <i class="fas fa-envelope"></i> Email notification will be sent automatically to <?= htmlspecialchars($booking['email']) ?>
                        </span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-save"></i> Update Booking Status
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
</body>
</html>