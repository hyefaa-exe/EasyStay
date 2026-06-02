<?php
require_once 'db_connect.php'; // Session & CSRF start di sini

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. SEMAK CSRF TOKEN (KESELAMATAN)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ralat Keselamatan: CSRF Token tidak sah. Sila refresh halaman.");
    }

    $user_id = $_SESSION['user_id'];
    $booking_id = $_POST['booking_id'];
    $package_id = $_POST['package_id'];
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);

    // Pastikan rating antara 1-5
    if ($rating < 1 || $rating > 5) {
        header("Location: my_profile.php?msg=InvalidRating");
        exit();
    }

    // 2. SIMPAN REVIEW KE DATABASE
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, booking_id, package_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $user_id, $booking_id, $package_id, $rating, $comment);
    
    if ($stmt->execute()) {
        header("Location: my_profile.php?msg=ReviewSuccess");
    } else {
        header("Location: my_profile.php?msg=ReviewError");
    }
    $stmt->close();
} else {
    header("Location: index.php");
}
?>