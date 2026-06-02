<?php
require 'db_connect.php';

// Arahan SQL untuk tambah column booking_id jika belum ada
$sql = "ALTER TABLE reviews ADD COLUMN booking_id INT(11) NOT NULL AFTER package_id";

if ($conn->query($sql) === TRUE) {
    echo "<h1>Berjaya! ✅</h1>";
    echo "<p>Lajur 'booking_id' telah ditambah ke dalam jadual 'reviews'.</p>";
    echo "<p>Sila padam fail ini dan refresh <a href='my_profile.php'>My Profile</a>.</p>";
} else {
    echo "<h1>Ralat atau Sudah Wujud ⚠️</h1>";
    echo "<p>Database berkata: " . $conn->error . "</p>";
    echo "<p>Cuba refresh <a href='my_profile.php'>My Profile</a> sekarang.</p>";
}
?>