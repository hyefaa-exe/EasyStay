<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
require_once 'db_connect.php';
require_once 'email.php'; // Pastikan fail email.php wujud

$id = $_GET['id'];
$msg = "";

// PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];
    $new_payment_status = $_POST['payment_status']; 
    
    // 1. Update Database
    $stmt = $conn->prepare("UPDATE bookings SET status=?, payment_status=? WHERE book_id=?");
    $stmt->bind_param("ssi", $new_status, $new_payment_status, $id);
    
    if ($stmt->execute()) {
        
        // 2. Ambil Data Detail User & Booking untuk Emel
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
            // Format Tarikh
            $checkin = date('d M Y', strtotime($user_res['checkin_date']));
            $checkout = date('d M Y', strtotime($user_res['checkout_date']));
            
            // --- LOGIK BARU (TANPA TOLAK DEPOSIT) ---
            // Jika status 'Fully Paid', baki ialah 0.
            // Jika tidak, baki ialah TOTAL PRICE penuh (tanpa tolak deposit).
            $balance = ($new_payment_status == 'Fully Paid') ? 0.00 : $user_res['total_price'];
            
            // Bina Mesej Emel yang Detail
            $emailBody = "
            <h3>Booking Status Update #$id</h3>
            <p>Dear <strong>{$user_res['full_name']}</strong>,</p>
            <p>Your booking details have been updated by the admin:</p>
            <hr>
            <p>
                <strong>Package:</strong> {$user_res['package_name']}<br>
                <strong>Check-in:</strong> $checkin<br>
                <strong>Check-out:</strong> $checkout<br>
                <strong>Room Status:</strong> $new_status<br>
                <strong>Payment Status:</strong> $new_payment_status
            </p>
            <p style='font-size:16px; color:#d35400;'>
                <strong>Total Amount to Pay: RM " . number_format($balance, 2) . "</strong>
            </p>
            <hr>
            <p>Please login to your dashboard to view full details or upload payment proof.</p>
            ";

            // Hantar Emel
            sendBookingStatusEmail($user_res['email'], $user_res['full_name'], $emailBody, $id);
        }

        header("Location: manage_bookings.php?msg=updated");
        exit();
    } else {
        $msg = "Ralat: " . $conn->error;
    }
}

// AMBIL DATA UNTUK PAPARAN FORM
$sql = "SELECT b.*, u.full_name, u.email, p.package_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        JOIN packages p ON b.package_id = p.package_id 
        WHERE b.book_id = $id";
$booking = $conn->query($sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Booking | EasyStay Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo"><h1>Easy<span>Stay</span></h1></div>
        <nav class="admin-nav"><a href="manage_bookings.php">Back</a></nav>
    </header>

    <div class="container" style="max-width: 600px;">
        <div class="page-header">
            <div class="page-title"><i class="fas fa-edit"></i> Update Booking #<?= $id ?></div>
        </div>

        <?php if($msg): ?><div style="color:red; margin-bottom:10px;"><?= $msg ?></div><?php endif; ?>

        <div class="card">
            <form method="POST">
                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p><strong>Customer:</strong> <?= htmlspecialchars($booking['full_name']) ?></p>
                    <p><strong>Package:</strong> <?= htmlspecialchars($booking['package_name']) ?></p>
                    <p><strong>Dates:</strong> <?= date('d M', strtotime($booking['checkin_date'])) ?> - <?= date('d M Y', strtotime($booking['checkout_date'])) ?></p>
                    <p><strong>Total Price:</strong> RM <?= number_format($booking['total_price'], 2) ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Booking Status (Room Availability)</label>
                    <select name="status" class="form-control">
                        <option value="Pending" <?= $booking['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Accepted" <?= $booking['status'] == 'Accepted' ? 'selected' : '' ?>>Accepted (Room Locked)</option>
                        <option value="Rejected" <?= $booking['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Cancelled" <?= $booking['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Status (Money)</label>
                    <select name="payment_status" class="form-control" style="border: 2px solid #C5A880;">
                        <option value="Pending Deposit" <?= $booking['payment_status'] == 'Pending Deposit' ? 'selected' : '' ?>>Pending Deposit</option>
                        <option value="Deposit Paid" <?= $booking['payment_status'] == 'Deposit Paid' ? 'selected' : '' ?>>Deposit Paid (Confirm)</option>
                        <option value="Pending Balance" <?= $booking['payment_status'] == 'Pending Balance' ? 'selected' : '' ?>>Pending Balance (User Uploaded)</option>
                        <option value="Fully Paid" <?= $booking['payment_status'] == 'Fully Paid' ? 'selected' : '' ?>>Fully Paid (Done)</option>
                    </select>
                </div>

                <button type="submit" class="btn-ulu" style="width: 100%; justify-content: center;">
                    Update All Status <i class="fas fa-save" style="margin-left:8px;"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>