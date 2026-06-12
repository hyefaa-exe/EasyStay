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
$dbname = "easystay";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -----------------------------------------
// CENTRALIZED PACKAGE FEATURES
// -----------------------------------------
function get_package_features($package_id) {
    $features = [
        12 => [
            'capacity' => 'MAX 3 Pax (Add-on extra bed RM20)',
            'inclusions' => [
                'Aircond',
                'Private Bathroom (Bilik Air)',
                'Water Heater',
                'Sejadah',
                'Coffee & Tea Station',
                'TV with YouTube access',
                'Free High-Speed WiFi',
                'Fresh Towels (Tuala)',
                'Mini Fridge (Peti Ais Mini)',
                'Soap & Shampoo (Sabun & Syampu)',
                'Jug Kettle',
                'Access to BBQ Pit'
            ]
        ],
        13 => [
            'capacity' => 'MAX 15 Pax (Add-on extra bed RM20)',
            'inclusions' => [
                '3 Bedrooms + 3 Bathrooms + 2 Extra Beds',
                'Fully Airconditioned (Bilik & Ruang Tamu)',
                'Sejadah',
                'TV with YouTube access',
                'Free High-Speed WiFi',
                'Fresh Towels (Tuala)',
                'Full-size Refrigerator (Peti Ais)',
                'Soap & Shampoo (Sabun & Syampu)',
                'Kitchen & Cooking Utensils (Peralatan Memasak)',
                'Iron & Washing Machine (Seterika & Mesin Basuh)'
            ]
        ],
        14 => [
            'capacity' => 'MAX 30 Pax (Entire Property)',
            'inclusions' => [
                '3-Bedroom Homestay',
                '3 Cozy Chalet Units',
                '1 Additional Extra Room',
                'Private Pool (Kolam Mandi Persendirian)',
                'Perfect for Family Gatherings (No other guests)',
                'Total of 7 bedrooms available'
            ]
        ]
    ];
    return isset($features[$package_id]) ? $features[$package_id] : ['capacity' => '', 'inclusions' => []];
}
?>
