<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Sambungan database
require_once 'db_connect.php';

// Dapatkan booking_id dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid booking ID.");
}
$book_id = intval($_GET['id']);

// Padam booking
$stmt = $conn->prepare("DELETE FROM bookings WHERE book_id = ?");
$stmt->bind_param("i", $book_id);

if ($stmt->execute()) {
    echo "<script>alert('Booking deleted successfully.'); window.location.href='manage_bookings.php';</script>";
} else {
    echo "Error deleting booking: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
