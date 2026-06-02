<?php
// Database connection
require 'db_connect.php';

// Pastikan package_id wujud
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid package ID.");
}

$package_id = intval($_GET['id']);

// Delete package
$stmt = $conn->prepare("DELETE FROM packages WHERE package_id = ?");
$stmt->bind_param("i", $package_id);

if ($stmt->execute()) {
    // Redirect ke manage_package selepas berjaya delete
    echo "<script>alert('Package deleted successfully!'); window.location.href='manage_packages.php';</script>";
} else {
    echo "Error deleting package: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
