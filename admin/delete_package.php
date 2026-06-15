<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

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
    require_once 'admin_logger.php';
    logAdminAction($conn, $_SESSION['admin_id'], 'DELETE_PACKAGE', "Deleted package ID #$package_id", $package_id, 'package');
    // Redirect ke manage_package selepas berjaya delete
    echo "<script>alert('Package deleted successfully!'); window.location.href='manage_packages.php';</script>";
} else {
    echo "Error deleting package: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
