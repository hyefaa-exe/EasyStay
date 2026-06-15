<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
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
// DUAL LANGUAGE SYSTEM SETUP
// -----------------------------------------
if (isset($_GET['lang'])) {
    $selected_lang = $_GET['lang'] === 'ms' ? 'ms' : 'en';
    $_SESSION['lang'] = $selected_lang;
}
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

require_once __DIR__ . '/lang.php';
$translations = get_translations($lang_code);

function __($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}

function get_lang_url($lang) {
    $params = $_GET;
    $params['lang'] = $lang;
    return '?' . http_build_query($params);
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);

// -----------------------------------------
// DATABASE CONNECTION
// -----------------------------------------
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-complete past accepted stays
$conn->query("UPDATE bookings SET status = 'Completed' WHERE status = 'Accepted' AND checkout_date < CURDATE()");

// -----------------------------------------
// CENTRALIZED PACKAGE FEATURES
// -----------------------------------------
function get_package_features($package_id) {
    $features = [
        12 => [
            'capacity' => __('cap_chalet'),
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
            'capacity' => __('cap_homestay'),
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
            'capacity' => __('cap_entire'),
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
