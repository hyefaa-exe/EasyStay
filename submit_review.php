<?php
require_once 'db_connect.php'; // Session & CSRF start di sini

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 1. SEMAK CSRF TOKEN
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: my_profile.php?msg=SecurityError");
    exit();
}

$user_id    = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id']);
$package_id = intval($_POST['package_id']);
$rating     = intval($_POST['rating']);
$comment    = strip_tags(trim($_POST['comment']));

// 2. Pastikan rating antara 1-5
if ($rating < 1 || $rating > 5) {
    header("Location: my_profile.php?msg=InvalidRating");
    exit();
}

// 3. Pastikan booking ini milik user yang login
$ownership = $conn->prepare("SELECT book_id FROM bookings WHERE book_id = ? AND user_id = ? AND status = 'Accepted'");
$ownership->bind_param("ii", $booking_id, $user_id);
$ownership->execute();
if ($ownership->get_result()->num_rows === 0) {
    header("Location: my_profile.php?msg=AccessDenied");
    exit();
}

// 4. Semak DUPLICATE — pastikan user belum pernah review booking ini
$dup = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? AND user_id = ?");
$dup->bind_param("ii", $booking_id, $user_id);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    header("Location: my_profile.php?msg=ReviewAlreadySubmitted");
    exit();
}

// 5. Simpan review ke database
$stmt = $conn->prepare("INSERT INTO reviews (user_id, booking_id, package_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiis", $user_id, $booking_id, $package_id, $rating, $comment);

if ($stmt->execute()) {
    header("Location: my_profile.php?msg=ReviewSuccess");
} else {
    header("Location: my_profile.php?msg=ReviewError");
}
$stmt->close();
exit();
?>