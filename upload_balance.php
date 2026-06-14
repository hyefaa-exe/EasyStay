<?php
session_start();
require 'db_connect.php';

// 1. Semak login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Semak method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_profile.php");
    exit();
}

// 3. CSRF Token Check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: my_profile.php?tab=booking&msg=SecurityError");
    exit();
}

$booking_id = intval($_POST['booking_id']);
$user_id    = $_SESSION['user_id'];

// 4. Pastikan booking ini milik user yang login & status Accepted
$check = $conn->prepare(
    "SELECT book_id FROM bookings 
     WHERE book_id = ? AND user_id = ? AND status = 'Accepted'"
);
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    header("Location: my_profile.php?tab=booking&msg=AccessDenied");
    exit();
}

// 5. Proses Upload
if (!isset($_FILES['balance_receipt']) || $_FILES['balance_receipt']['error'] !== 0) {
    header("Location: my_profile.php?tab=booking&msg=UploadError");
    exit();
}

$allowed     = ['jpg', 'jpeg', 'png', 'pdf'];
$max_size    = 5 * 1024 * 1024; // 5MB
$filename    = $_FILES['balance_receipt']['name'];
$ext         = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$filesize    = $_FILES['balance_receipt']['size'];

// 6. Semak jenis fail
if (!in_array($ext, $allowed)) {
    header("Location: my_profile.php?tab=booking&msg=InvalidFileType");
    exit();
}

// 7. Semak saiz fail (max 5MB)
if ($filesize > $max_size) {
    header("Location: my_profile.php?tab=booking&msg=FileTooLarge");
    exit();
}

// 8. Simpan fail
$new_name    = time() . '_bal_' . uniqid() . '.' . $ext;
$destination = 'admin/uploads/receipts/' . $new_name;

if (!is_dir('admin/uploads/receipts/')) {
    mkdir('admin/uploads/receipts/', 0755, true);
}

if (!move_uploaded_file($_FILES['balance_receipt']['tmp_name'], $destination)) {
    header("Location: my_profile.php?tab=booking&msg=UploadError");
    exit();
}

// 9. Update database
$stmt = $conn->prepare(
    "UPDATE bookings 
     SET balance_receipt = ?, payment_status = 'Pending Balance' 
     WHERE book_id = ? AND user_id = ?"
);
$stmt->bind_param("sii", $new_name, $booking_id, $user_id);

if ($stmt->execute()) {
    header("Location: my_profile.php?tab=booking&msg=BalanceUploadSuccess");
} else {
    // Padam fail yang terlanjur diupload jika DB gagal
    @unlink($destination);
    header("Location: my_profile.php?tab=booking&msg=Error");
}
exit();
?>