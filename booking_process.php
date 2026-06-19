<?php
session_start();
require 'db_connect.php';

// 1. Semak Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Semak Method & Process Booking flag & CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['process_booking'])) {
    header("Location: package.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: package.php?msg=SecurityError");
    exit();
}

$user_id    = $_SESSION['user_id'];
$package_id = intval($_POST['package_id']);

// 3. Ambil harga pakej dari DATABASE (bukan dari POST — keselamatan!)
$pkg_stmt = $conn->prepare("SELECT price, availability FROM packages WHERE package_id = ?");
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

// 5a. Proses Kupon (jika ada)
$coupon_code = isset($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : '';
$discount_amount = 0.00;

if (!empty($coupon_code)) {
    $c_stmt = $conn->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? LIMIT 1");
    $c_stmt->bind_param("s", $coupon_code);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    
    if ($c_res->num_rows === 1) {
        $coupon = $c_res->fetch_assoc();
        $today = date('Y-m-d');
        
        // Semak status, tarikh luput, had penggunaan, & min spend
        if ($coupon['status'] === 'ACTIVE' 
            && $coupon['expiry_date'] >= $today 
            && ($coupon['max_uses'] == 0 || $coupon['uses_count'] < $coupon['max_uses'])
            && $total_price >= floatval($coupon['min_spend'])) {
            
            $val = floatval($coupon['discount_value']);
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = $total_price * ($val / 100.00);
            } else {
                $discount_amount = $val;
            }
            
            if ($discount_amount > $total_price) {
                $discount_amount = $total_price;
            }
            
            // Simpan coupon_code sebenar
            $coupon_code = $coupon['code'];
        } else {
            $coupon_code = null;
        }
    } else {
        $coupon_code = null;
    }
    $c_stmt->close();
} else {
    $coupon_code = null;
}

$final_total_price = $total_price - $discount_amount;

// 6. Validate adults & children
$adults   = max(1, intval($_POST['adults']   ?? 1));
$children = max(0, intval($_POST['children'] ?? 0));

// 7. Semak Kekosongan (Double Check dengan Kuantiti Availability)
$availability = intval($pkg_data['availability']);

$check_sql = "SELECT checkin_date, checkout_date FROM bookings 
              WHERE package_id = ? 
              AND status NOT IN ('Cancelled', 'Rejected') 
              AND (checkin_date < ? AND checkout_date > ?)";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("iss", $package_id, $checkout, $checkin);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

// Semak bertindih malam demi malam
$requested_start = new DateTime($checkin);
$requested_end   = new DateTime($checkout);
$interval = new DateInterval('P1D');
$period   = new DatePeriod($requested_start, $interval, $requested_end);

$date_counts = [];
while ($row = $res_check->fetch_assoc()) {
    $b_start = new DateTime($row['checkin_date']);
    $b_end   = new DateTime($row['checkout_date']);
    $b_period = new DatePeriod($b_start, $interval, $b_end);
    foreach ($b_period as $d) {
        $d_str = $d->format('Y-m-d');
        if (!isset($date_counts[$d_str])) {
            $date_counts[$d_str] = 0;
        }
        $date_counts[$d_str]++;
    }
}

$overbooked = false;
foreach ($period as $d) {
    $d_str = $d->format('Y-m-d');
    $current_bookings = $date_counts[$d_str] ?? 0;
    if ($current_bookings >= $availability) {
        $overbooked = true;
        break;
    }
}

if ($overbooked) {
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
               (user_id, full_name, customer_email, package_id, coupon_code, checkin_date, checkout_date, adults, children, total_price, discount_amount, receipt_path, original_filename, status, payment_status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql_insert);
$stmt->bind_param(
    "ississsiiddssss",
    $user_id, $full_name, $customer_email, $package_id, $coupon_code,
    $checkin, $checkout, $adults, $children,
    $final_total_price, $discount_amount, $new_name, $original_filename,
    $status, $payment_status
);

if ($stmt->execute()) {
    // Increment uses_count jika kupon berjaya digunakan
    if ($coupon_code !== null) {
        $up_stmt = $conn->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE code = ?");
        $up_stmt->bind_param("s", $coupon_code);
        $up_stmt->execute();
        $up_stmt->close();
    }
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