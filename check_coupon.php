<?php
header('Content-Type: application/json');

require_once 'db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to use coupons.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$coupon_code = isset($_POST['coupon_code']) ? strtoupper(trim($_POST['coupon_code'])) : '';
$total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0.00;

if (empty($coupon_code)) {
    echo json_encode([
        'success' => false,
        'message' => __('book_coupon_invalid')
    ]);
    exit();
}

// Query coupon
$stmt = $conn->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? LIMIT 1");
$stmt->bind_param("s", $coupon_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => __('book_coupon_invalid')
    ]);
    exit();
}

$coupon = $res->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');

// 1. Check status
if ($coupon['status'] !== 'ACTIVE') {
    echo json_encode([
        'success' => false,
        'message' => __('book_coupon_invalid')
    ]);
    exit();
}

// 2. Check expiry
if ($coupon['expiry_date'] < $today) {
    echo json_encode([
        'success' => false,
        'message' => __('book_coupon_invalid')
    ]);
    exit();
}

// 3. Check usage limit
if ($coupon['max_uses'] > 0 && $coupon['uses_count'] >= $coupon['max_uses']) {
    echo json_encode([
        'success' => false,
        'message' => __('book_coupon_limit_exceeded')
    ]);
    exit();
}

// 4. Check minimum spend
if ($total_price < floatval($coupon['min_spend'])) {
    $min_spend_formatted = number_format($coupon['min_spend'], 2);
    echo json_encode([
        'success' => false,
        'message' => sprintf(__('book_coupon_min_spend'), $min_spend_formatted)
    ]);
    exit();
}

// Calculate discount
$discount_amount = 0.00;
$discount_value = floatval($coupon['discount_value']);

if ($coupon['discount_type'] === 'percentage') {
    $discount_amount = $total_price * ($discount_value / 100.00);
} else {
    $discount_amount = $discount_value;
}

// Ensure discount doesn't exceed total price
if ($discount_amount > $total_price) {
    $discount_amount = $total_price;
}

$new_total = $total_price - $discount_amount;

echo json_encode([
    'success' => true,
    'message' => __('book_coupon_applied'),
    'coupon_code' => $coupon['code'],
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $discount_value,
    'discount_amount' => round($discount_amount, 2),
    'new_total' => round($new_total, 2)
]);
exit();
?>
