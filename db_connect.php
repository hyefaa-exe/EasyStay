<?php
// Mulakan session jika belum bermula
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------
// CSRF PROTECTION SETUP
// -----------------------------------------
if (empty($_SESSION['csrf_token'])) {
    // Jana token random 32-byte hex
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -----------------------------------------
// DATABASE CONNECTION
// -----------------------------------------
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ulugarden";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
