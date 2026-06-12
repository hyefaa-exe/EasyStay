<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
require 'db_connect.php';

// Pastikan photo_id wujud
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid photo ID.");
}

$photo_id = intval($_GET['id']);

// Get the photo path to delete the file from server
$stmt = $conn->prepare("SELECT filename FROM gallery WHERE id = ?");
$stmt->bind_param("i", $photo_id);
$stmt->execute();
$result = $stmt->get_result();
$photo = $result->fetch_assoc();
if ($photo) {
    $file_path = 'uploads/' . $photo['filename'];
    if (file_exists($file_path)) {
        unlink($file_path); // Delete the file from server
    }
}

// Delete photo record
$stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
$stmt->bind_param("i", $photo_id);

if ($stmt->execute()) {
    echo "<script>alert('Photo deleted successfully!'); window.location.href='view_gallery.php';</script>";
} else {
    echo "Error deleting photo: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
