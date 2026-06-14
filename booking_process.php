<?php
session_start();
require 'db_connect.php';

// 1. Semak Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Semak Method & Process Booking flag
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['process_booking'])) {
    header("Location: package.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$package_id = intval($_POST['package_id']);

// 3. Ambil harga pakej dari DATABASE (bukan dari POST — keselamatan!)
$pkg_stmt = $conn->prepare("SELECT price FROM packages WHERE package_id = ?");
$pkg_stmt->bind_param("i", $package_id);
$pkg_stmt->execute();
$pkg_data = $pkg_stmt->get_result()->fetch_assoc();

if (!$pkg_data) {
    header("Location: package.php?msg=PackageNotFound");
    exit();
}

$price_per_night = (float)$pkg_data['price'];

// 4. Sanitize & Validate Tarikh
$raw_checkin  = trim($_POST['checkin_date']  ?? '');
$raw_checkout = trim($_POST['checkout_date'] ?? '');

$checkin  = date('Y-m-d', strtotime(str_replace('/', '-', $raw_checkin)));
$checkout = date('Y-m-d', strtotime(str_replace('/', '-', $raw_checkout)));

// Pastikan tarikh sah
if (!$checkin || !$checkout || $checkin === '1970-01-01' || $checkout === '1970-01-01') {
    header("Location: book_new.php?package_id=$package_id&msg=InvalidDate");
    exit();
}

$d1 = new DateTime($checkin);
$d2 = new DateTime($checkout);
$nights = $d2->diff($d1)->days;

if ($nights <= 0) {
    header("Location: book_new.php?package_id=$package_id&msg=InvalidDate");
    exit();
}

// 5. Kira harga SEBENAR di server (SELAMAT)
$total_price = $price_per_night * $nights;

// 6. Validate adults & children
$adults   = max(1, intval($_POST['adults']   ?? 1));
$children = max(0, intval($_POST['children'] ?? 0));

// 7. Semak Kekosongan (Double Check)
$check_sql = "SELECT book_id FROM bookings 
              WHERE package_id = ? 
              AND status NOT IN ('Cancelled', 'Rejected') 
              AND (checkin_date < ? AND checkout_date > ?)";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("iss", $package_id, $checkout, $checkin);
$stmt_check->execute();

if ($stmt_check->get_result()->num_rows > 0) {
    header("Location: book_new.php?package_id=$package_id&msg=DateUnavailable");
    exit();
}

$payment_method = trim($_POST['payment_method'] ?? 'manual');

if ($payment_method === 'gateway') {
    // Instant simulation payment
    $new_name = 'instant_gateway.png';
    $original_filename = 'Instant Gateway Payment';
    $status = 'Accepted';
    $payment_status = 'Deposit Paid';
} else {
    // 8. Proses Upload Resit Deposit
    if (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] !== 0) {
        header("Location: book_new.php?package_id=$package_id&msg=ReceiptRequired");
        exit();
    }

    $allowed    = ['jpg', 'jpeg', 'png', 'pdf'];
    $filename   = $_FILES['payment_receipt']['name'];
    $file_ext   = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $filesize   = $_FILES['payment_receipt']['size'];
    $max_size   = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_ext, $allowed)) {
        header("Location: book_new.php?package_id=$package_id&msg=InvalidFileType");
        exit();
    }

    if ($filesize > $max_size) {
        header("Location: book_new.php?package_id=$package_id&msg=FileTooLarge");
        exit();
    }

    $new_name        = time() . "_" . uniqid() . "." . $file_ext;
    $destination     = "admin/uploads/receipts/" . $new_name;
    $original_filename = htmlspecialchars(strip_tags($filename));

    if (!is_dir('admin/uploads/receipts/')) {
        mkdir('admin/uploads/receipts/', 0755, true);
    }

    if (!move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $destination)) {
        header("Location: book_new.php?package_id=$package_id&msg=UploadFailed");
        exit();
    }
    
    $status         = 'Pending';
    $payment_status = 'Pending Deposit';
}

// 9. Ambil data user untuk rekod
$user_sql = "SELECT full_name, email FROM users WHERE user_id = ?";
$stmt_u   = $conn->prepare($user_sql);
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$u_data = $stmt_u->get_result()->fetch_assoc();

$full_name      = $u_data['full_name'];
$customer_email = $u_data['email'];

// 10. Simpan ke Database
$sql_insert = "INSERT INTO bookings 
               (user_id, full_name, customer_email, package_id, checkin_date, checkout_date, adults, children, total_price, receipt_path, original_filename, status, payment_status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql_insert);
$stmt->bind_param(
    "isssssiidssss",
    $user_id, $full_name, $customer_email, $package_id,
    $checkin, $checkout, $adults, $children,
    $total_price, $new_name, $original_filename,
    $status, $payment_status
);

if ($stmt->execute()) {
    header("Location: my_profile.php?msg=BookingSuccess");
    exit();
} else {
    // Padam fail yang terlanjur diupload jika DB gagal
    if (isset($destination)) {
        @unlink($destination);
    }
    header("Location: book_new.php?package_id=$package_id&msg=BookingFailed");
    exit();
}
?>