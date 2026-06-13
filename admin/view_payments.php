<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }
require_once 'db_connect.php';
$admin_id = $_SESSION['admin_id'];

// ==========================================
// MOD 1: JIKA ADA ID -> PAPAR DETAIL
// ==========================================
if (isset($_GET['book_id'])) {
    $book_id = intval($_GET['book_id']);
    $msg = "";
    $msg_type = "success";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_status     = $_POST['status'];
        $new_pay_status = $_POST['payment_status'];
        $stmt = $conn->prepare("UPDATE bookings SET status=?, payment_status=? WHERE book_id=?");
        $stmt->bind_param("ssi", $new_status, $new_pay_status, $book_id);
        if ($stmt->execute()) {
            $msg = "Status berjaya dikemaskini!";
            $user_sql = "SELECT b.*, u.full_name, u.email, p.package_name 
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.user_id 
                         JOIN packages p ON b.package_id = p.package_id
                         WHERE b.book_id = ?";
            $stmt_user = $conn->prepare($user_sql);
            $stmt_user->bind_param("i", $book_id);
            $stmt_user->execute();
            $user_res = $stmt_user->get_result()->fetch_assoc();
            if ($user_res) {
                require_once 'email.php';
                $checkin  = date('d M Y', strtotime($user_res['checkin_date']));
                $checkout = date('d M Y', strtotime($user_res['checkout_date']));
                $balance  = ($new_pay_status == 'Fully Paid') ? 0.00 : $user_res['total_price'];
                $emailDetails = [
                    'booking_id'     => $book_id,
                    'package_name'   => $user_res['package_name'],
                    'checkin'        => $checkin,
                    'checkout'       => $checkout,
                    'status'         => $new_status,
                    'payment_status' => $new_pay_status,
                    'balance'        => $balance
                ];
                sendBookingStatusEmail($user_res['email'], $user_res['full_name'], $emailDetails, $book_id);
            }
        } else {
            $msg = "Ralat semasa kemaskini.";
            $msg_type = "danger";
        }
    }

    // Fetch booking
    $sql = "SELECT b.*, u.full_name, u.email, u.phone, p.package_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.user_id 
            JOIN packages p ON b.package_id = p.package_id 
            WHERE b.book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    if (!$booking) { echo "Rekod tidak dijumpai."; exit(); }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payment #<?= $book_id ?> | EasyStay Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Payment Verification</div>
            <div class="topbar-breadcrumb">Booking #<?= $book_id ?> — <?= htmlspecialchars($booking['full_name']) ?></div>
        </div>
        <div class="topbar-right">
            <a href="view_payments.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="admin-content">

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 380px; gap: 20px; align-items: start;">

            <!-- Left: Guest Info + Receipts -->
            <div>
                <!-- Guest Info Card -->
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Guest Information</h3>
                        <span class="badge status-<?= $booking['status'] ?>"><?= $booking['status'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-item-label">Full Name</div>
                                <div class="info-item-value"><?= htmlspecialchars($booking['full_name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Package</div>
                                <div class="info-item-value" style="color:var(--gold);"><?= htmlspecialchars($booking['package_name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Email</div>
                                <div class="info-item-value" style="font-size:0.82rem;"><?= htmlspecialchars($booking['email']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Phone</div>
                                <div class="info-item-value"><?= htmlspecialchars($booking['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Check-in</div>
                                <div class="info-item-value"><?= date('d M Y', strtotime($booking['checkin_date'])) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Check-out</div>
                                <div class="info-item-value"><?= date('d M Y', strtotime($booking['checkout_date'])) ?></div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; background:var(--gold-light); border-radius:8px; border:1px solid rgba(197,168,128,0.2);">
                            <div>
                                <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.8px; font-weight:700; color:var(--gold-dark); margin-bottom:2px;">Total Amount</div>
                                <div style="font-size:1.4rem; font-weight:800; color:var(--slate-900);">RM <?= number_format($booking['total_price'], 2) ?></div>
                            </div>
                            <?php
                            $ps  = $booking['payment_status'] ?? 'Pending Deposit';
                            $cls = str_replace(' ', '-', $ps);
                            ?>
                            <span class="badge bg-<?= $cls ?>"><?= $ps ?></span>
                        </div>
                    </div>
                </div>

                <!-- Receipt Proofs Card -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Payment Receipts</h3>
                    </div>
                    <div class="card-body">
                        <!-- Deposit Receipt -->
                        <div class="proof-card deposit">
                            <div>
                                <div class="proof-card-label">Deposit Receipt</div>
                                <div class="proof-card-status" style="color: <?= $booking['receipt_path'] ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <i class="fas <?= $booking['receipt_path'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= $booking['receipt_path'] ? 'Uploaded' : 'Not Uploaded Yet' ?>
                                </div>
                            </div>
                            <?php if ($booking['receipt_path']): ?>
                                <a href="../admin/uploads/receipts/<?= $booking['receipt_path'] ?>" target="_blank" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Balance Receipt -->
                        <div class="proof-card balance">
                            <div>
                                <div class="proof-card-label">Balance Receipt</div>
                                <div class="proof-card-status" style="color: <?= $booking['balance_receipt'] ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <i class="fas <?= $booking['balance_receipt'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= $booking['balance_receipt'] ? 'Uploaded' : 'Not Uploaded Yet' ?>
                                </div>
                            </div>
                            <?php if ($booking['balance_receipt']): ?>
                                <a href="../admin/uploads/receipts/<?= $booking['balance_receipt'] ?>" target="_blank" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Update Form -->
            <div class="card" style="position:sticky; top:calc(var(--topbar-h) + 16px);">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Update Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Booking Status</label>
                            <select name="status">
                                <option value="Pending"  <?= $booking['status']=='Pending' ?'selected':'' ?>>⏳ Pending</option>
                                <option value="Accepted" <?= $booking['status']=='Accepted'?'selected':'' ?>>✅ Accepted</option>
                                <option value="Rejected" <?= $booking['status']=='Rejected'?'selected':'' ?>>❌ Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status">
                                <option value="Pending Deposit" <?= ($booking['payment_status']??'')==='Pending Deposit'?'selected':'' ?>>💳 Pending Deposit</option>
                                <option value="Deposit Paid"    <?= ($booking['payment_status']??'')==='Deposit Paid'   ?'selected':'' ?>>✔ Deposit Paid</option>
                                <option value="Pending Balance" <?= ($booking['payment_status']??'')==='Pending Balance'?'selected':'' ?>>⏳ Pending Balance</option>
                                <option value="Fully Paid"      <?= ($booking['payment_status']??'')==='Fully Paid'     ?'selected':'' ?>>💰 Fully Paid</option>
                            </select>
                        </div>
                        <div style="background:var(--info-bg); border:1px solid rgba(59,130,246,0.15); border-radius:8px; padding:10px 12px; margin-bottom:16px;">
                            <div style="font-size:0.72rem; color:var(--info); font-weight:700; display:flex; align-items:center; gap:5px;">
                                <i class="fas fa-envelope"></i> Email notification will be sent automatically.
                            </div>
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Update Records
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
    <?php
    exit();
}

// ==========================================
// MOD 2: TIADA ID -> SENARAI SEMUA
// ==========================================
$sql    = "SELECT b.*, u.full_name, p.package_name FROM bookings b JOIN users u ON b.user_id = u.user_id JOIN packages p ON b.package_id = p.package_id ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification | EasyStay Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Payment Verification</div>
            <div class="topbar-breadcrumb">Verify guest receipts and manage payment lifecycle</div>
        </div>
    </div>

    <div class="admin-content">
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-wallet"></i> All Payments</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:90px;">Booking ID</th>
                            <th>Customer / Package</th>
                            <th>Total Price</th>
                            <th>Payment Status</th>
                            <th style="text-align:center;">Receipts</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $ps  = $row['payment_status'] ?? 'Pending Deposit';
                            $cls = str_replace(' ', '-', $ps);
                        ?>
                        <tr>
                            <td><span class="table-id">#<?= $row['book_id'] ?></span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar"><?= strtoupper(substr($row['full_name'],0,1)) ?></div>
                                    <div>
                                        <div class="user-info-name"><?= htmlspecialchars($row['full_name']) ?></div>
                                        <div class="user-info-sub"><?= htmlspecialchars($row['package_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:700; color:var(--slate-900);">RM <?= number_format($row['total_price'], 2) ?></td>
                            <td><span class="badge bg-<?= $cls ?>"><?= $ps ?></span></td>
                            <td style="text-align:center;">
                                <div style="display:flex; gap:10px; justify-content:center;">
                                    <span title="Deposit Receipt" style="color: <?= $row['receipt_path'] ? 'var(--success)' : 'var(--slate-200)' ?>; font-size:1.1rem;">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </span>
                                    <span title="Balance Receipt" style="color: <?= $row['balance_receipt'] ? 'var(--success)' : 'var(--slate-200)' ?>; font-size:1.1rem;">
                                        <i class="fas fa-check-double"></i>
                                    </span>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <a href="view_payments.php?book_id=<?= $row['book_id'] ?>" class="btn-check">
                                    Verify <i class="fas fa-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>