<?php
// Database connection
require 'db_connect.php';

// Pastikan user_id wujud
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = intval($_GET['id']);

// Delete package
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Redirect ke manage_package selepas berjaya delete
    echo "<script>alert('User deleted successfully!'); window.location.href='view_users.php';</script>";
} else {
    echo "Error deleting user: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
