<?php
// Load config.php if exists
if (file_exists(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST',     getenv('MYSQLHOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER',     getenv('MYSQLUSER') ?: 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME',     getenv('MYSQLDATABASE') ?: 'easystay');
}
if (!defined('DB_PORT')) {
    define('DB_PORT',     getenv('MYSQLPORT') ?: '3306');
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
