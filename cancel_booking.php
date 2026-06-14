<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: my_profile.php");
    exit();
}

// 1. Semak CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: my_profile.php?tab=booking&msg=SecurityError");
    exit();
}

$booking_id = intval($_POST['booking_id']);
$user_id    = $_SESSION['user_id'];

// 2. Pastikan booking milik user ini & status valid untuk cancel
$check = $conn->prepare(
    "SELECT b.status, b.checkin_date, b.checkout_date, b.total_price, 
            p.package_name, u.full_name, u.email
     FROM bookings b 
     JOIN packages p ON b.package_id = p.package_id
     JOIN users u ON b.user_id = u.user_id
     WHERE b.book_id = ? AND b.user_id = ?"
);
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
$res = $check->get_result()->fetch_assoc();

if (!$res || !in_array($res['status'], ['Pending', 'Accepted'])) {
    header("Location: my_profile.php?tab=booking&msg=CannotCancel");
    exit();
}

// 3. Proses Cancel
$update = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE book_id = ?");
$update->bind_param("i", $booking_id);

if ($update->execute()) {
    // 4. Hantar email notifikasi ke admin
    try {
        require_once 'admin/email.php';
        
        // Ambil email admin
        $admin_res = $conn->query("SELECT email FROM admins LIMIT 1")->fetch_assoc();
        $admin_email = $admin_res['email'] ?? 'easystay.mpi@gmail.com';

        $checkin  = date('d M Y', strtotime($res['checkin_date']));
        $checkout = date('d M Y', strtotime($res['checkout_date']));
        
        $days_to_checkin = (strtotime($res['checkin_date']) - time()) / (60 * 60 * 24);
        $refund_status = ($days_to_checkin >= 7) ? "<span style='color:green;font-weight:bold;'>ELIGIBLE FOR REFUND (Cancelled >7 days before check-in)</span>" : "<span style='color:red;font-weight:bold;'>FORFEITED (Cancelled <7 days before check-in)</span>";

        $emailBody = "
            <h2 style='color:#e74c3c;'>⚠️ Booking Cancelled by Customer</h2>
            <table style='width:100%; border-collapse:collapse; font-family:Arial,sans-serif;'>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold; width:35%'>Booking ID</td><td style='padding:8px;'>#$booking_id</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Customer</td><td style='padding:8px;'>" . htmlspecialchars($res['full_name']) . "</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Email</td><td style='padding:8px;'>" . htmlspecialchars($res['email']) . "</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Package</td><td style='padding:8px;'>" . htmlspecialchars($res['package_name']) . "</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Check-in</td><td style='padding:8px;'>$checkin</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Check-out</td><td style='padding:8px;'>$checkout</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Total Price</td><td style='padding:8px;'>RM " . number_format($res['total_price'], 2) . "</td></tr>
                <tr><td style='padding:8px; background:#f9f9f9; font-weight:bold;'>Refund Status</td><td style='padding:8px;'>$refund_status</td></tr>
            </table>
            <p style='margin-top:16px; color:#666;'>The customer has cancelled this booking from their profile. Please review and update your records accordingly.</p>
            <a href='http://localhost/EasyStay/admin/manage_bookings.php' style='display:inline-block; margin-top:16px; padding:10px 20px; background:#1d1d1f; color:white; text-decoration:none; border-radius:8px;'>View Booking Records</a>
        ";

        sendBookingStatusEmail($admin_email, 'EasyStay Admin', $emailBody);
    } catch (Exception $e) {
        // Email gagal tidak hentikan proses cancel
        error_log("Cancel notification email failed: " . $e->getMessage());
    }

    header("Location: my_profile.php?tab=booking&msg=CancelSuccess");
    exit();
}

header("Location: my_profile.php?tab=booking&msg=Error");
exit();
?>