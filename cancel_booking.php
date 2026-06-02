<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // Check CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error.");
    }

    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id'];

    // Pastikan booking milik user ini & status valid untuk cancel
    $check = $conn->prepare("SELECT status FROM bookings WHERE book_id = ? AND user_id = ?");
    $check->bind_param("ii", $booking_id, $user_id);
    $check->execute();
    $res = $check->get_result();
    $row = $res->fetch_assoc();

    if ($row && ($row['status'] == 'Pending' || $row['status'] == 'Accepted')) {
        // Proses Cancel
        $update = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE book_id = ?");
        $update->bind_param("i", $booking_id);
        
        if ($update->execute()) {
            header("Location: my_profile.php?tab=booking&msg=CancelSuccess");
            exit();
        }
    }
}

header("Location: my_profile.php?tab=booking&msg=Error");
?>