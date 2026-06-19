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
            require_once 'admin_logger.php';
            logAdminAction($conn, $admin_id, 'UPDATE_PAYMENT', "Updated booking #$book_id status to '$new_status' ($new_pay_status)", $book_id, 'booking');
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

        <div class="details-grid">

            <!-- Left: Guest Info + Receipts -->
            <div>
                <!-- Guest Info Card -->
                <div class="card" style="margin-bottom: 24px; border: 1px solid rgba(197, 168, 128, 0.15); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);">
                    <div class="card-header" style="background: rgba(197, 168, 128, 0.03); padding: 18px 24px; border-bottom: 1px solid rgba(197, 168, 128, 0.1);">
                        <h3 style="font-size: 1.05rem; display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--slate-800);">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--gold-light); color: var(--gold); font-size: 0.95rem;">
                                <i class="fas fa-user-circle"></i>
                            </span>
                            Booking Profile
                        </h3>
                        <?php
                        $st = $booking['status'];
                        $badge_class = 'badge-pending';
                        if ($st === 'Accepted') $badge_class = 'badge-accepted';
                        if ($st === 'Rejected') $badge_class = 'badge-danger';
                        ?>
                        <span class="badge <?= $badge_class ?>" style="padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 0.72rem; letter-spacing: 0.5px;"><?= strtoupper($st) ?></span>
                    </div>
                    <div class="card-body" style="padding: 24px;">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-item-label">Customer Name</div>
                                <div class="info-item-value" style="text-transform: uppercase;"><?= htmlspecialchars($booking['full_name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Selected Package</div>
                                <div class="info-item-value" style="color: var(--gold-dark);"><?= htmlspecialchars($booking['package_name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Email Address</div>
                                <div class="info-item-value" style="font-weight: 600; color: var(--slate-700);"><?= htmlspecialchars($booking['email']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Contact Number</div>
                                <div class="info-item-value"><?= htmlspecialchars($booking['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Check-in Date</div>
                                <div class="info-item-value" style="display: flex; align-items: center; gap: 8px;">
                                    <i class="far fa-calendar-alt" style="color: var(--gold);"></i> <?= date('d M Y', strtotime($booking['checkin_date'])) ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-item-label">Check-out Date</div>
                                <div class="info-item-value" style="display: flex; align-items: center; gap: 8px;">
                                    <i class="far fa-calendar-alt" style="color: var(--gold);"></i> <?= date('d M Y', strtotime($booking['checkout_date'])) ?>
                                </div>
                            </div>
                            <?php if (!empty($booking['coupon_code'])): ?>
                                <div class="info-item">
                                    <div class="info-item-label">Promo Code Used</div>
                                    <div class="info-item-value" style="color: var(--success); font-weight: 700;"><?= htmlspecialchars($booking['coupon_code']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-item-label">Coupon Discount</div>
                                    <div class="info-item-value" style="color: var(--danger); font-weight: 700;">- RM <?= number_format($booking['discount_amount'], 2) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Dark/Gold Gradient Banner for Total Price -->
                        <div style="background: linear-gradient(135deg, #1A1A1A 0%, #111 100%); border-radius: 12px; padding: 22px 28px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                            <div>
                                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 800; color: rgba(197, 168, 128, 0.8); margin-bottom: 4px;">Total Amount Payable</div>
                                <div style="font-size: 1.8rem; font-weight: 800; color: var(--white); font-family: 'Plus Jakarta Sans', sans-serif;">RM <?= number_format($booking['total_price'], 2) ?></div>
                            </div>
                            <?php
                            $ps  = $booking['payment_status'] ?? 'Pending Deposit';
                            $cls = str_replace(' ', '-', $ps);
                            ?>
                            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.08); padding: 8px 16px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.05);">
                                <i class="fas fa-tag" style="color: var(--gold); font-size: 0.82rem;"></i>
                                <span style="font-size: 0.78rem; font-weight: 700; color: var(--white); text-transform: uppercase; letter-spacing: 0.5px;"><?= $ps ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submitted Documents Card -->
                <div class="card" style="border: 1px solid rgba(197, 168, 128, 0.15); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);">
                    <div class="card-header" style="background: rgba(197, 168, 128, 0.03); padding: 18px 24px; border-bottom: 1px solid rgba(197, 168, 128, 0.1);">
                        <h3 style="font-size: 1.05rem; display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--slate-800);">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--gold-light); color: var(--gold); font-size: 0.95rem;">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                            Submitted Documents
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 24px;">
                        <!-- Deposit Receipt -->
                        <div class="proof-card" style="border: 1px solid #E2E8F0; border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; background: #fff; border-left: 4px solid var(--gold);">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 8px; background: rgba(197, 168, 128, 0.08); color: var(--gold); font-size: 1.2rem;">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <div>
                                    <div class="proof-card-label" style="font-size: 0.95rem; font-weight: 700; color: var(--slate-800); margin-bottom: 4px;">Deposit Receipt</div>
                                    <div class="proof-card-status" style="font-size: 0.78rem; font-weight: 600; color: <?= $booking['receipt_path'] ? '#10B981' : '#EF4444' ?>; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas <?= $booking['receipt_path'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                        <?= $booking['receipt_path'] ? 'Document Uploaded' : 'Not Uploaded Yet' ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($booking['receipt_path']): ?>
                                <a href="../admin/uploads/receipts/<?= $booking['receipt_path'] ?>" target="_blank" class="btn" style="background: var(--slate-900); color: #fff; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            <?php else: ?>
                                <span class="btn-disabled" style="background: #F1F5F9; color: #94A3B8; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; cursor: not-allowed; border: 1px solid #E2E8F0;">
                                    No File
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Balance Receipt -->
                        <div class="proof-card" style="border: 1px solid #E2E8F0; border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; background: #fff; border-left: 4px solid var(--info);">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 8px; background: rgba(59, 130, 246, 0.08); color: var(--info); font-size: 1.2rem;">
                                    <i class="fas fa-file-invoice"></i>
                                </span>
                                <div>
                                    <div class="proof-card-label" style="font-size: 0.95rem; font-weight: 700; color: var(--slate-800); margin-bottom: 4px;">Balance Receipt</div>
                                    <div class="proof-card-status" style="font-size: 0.78rem; font-weight: 600; color: <?= $booking['balance_receipt'] ? '#10B981' : '#64748B' ?>; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas <?= $booking['balance_receipt'] ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                        <?= $booking['balance_receipt'] ? 'Document Uploaded' : 'Not Required Yet / Pending' ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($booking['balance_receipt']): ?>
                                <a href="../admin/uploads/receipts/<?= $booking['balance_receipt'] ?>" target="_blank" class="btn" style="background: var(--slate-900); color: #fff; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            <?php else: ?>
                                <span class="btn-disabled" style="background: #F1F5F9; color: #94A3B8; padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; cursor: not-allowed; border: 1px solid #E2E8F0;">
                                    No File
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Update Form (Action Center) -->
            <div class="card" style="position: sticky; top: calc(var(--topbar-h) + 20px); border: 1px solid rgba(197, 168, 128, 0.15); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);">
                <div class="card-header" style="background: rgba(197, 168, 128, 0.03); padding: 18px 24px; border-bottom: 1px solid rgba(197, 168, 128, 0.1);">
                    <h3 style="font-size: 1.05rem; display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--slate-800);">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--gold-light); color: var(--gold); font-size: 0.95rem;">
                            <i class="fas fa-sliders-h"></i>
                        </span>
                        Action Center
                    </h3>
                </div>
                <div class="card-body" style="padding: 24px;">
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label" style="font-size: 0.72rem; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 8px;">Booking Decision</label>
                            <div style="position: relative;">
                                <select name="status" style="width: 100%; padding: 12px 16px 12px 40px; border: 1px solid #E2E8F0; border-radius: 10px; font-family: inherit; font-size: 0.92rem; font-weight: 600; color: var(--slate-800); background: #fff; transition: all 0.2s; outline: none; appearance: none; cursor: pointer;">
                                    <option value="Pending"  <?= $booking['status']=='Pending' ?'selected':'' ?>>⏳ Pending Review</option>
                                    <option value="Accepted" <?= $booking['status']=='Accepted'?'selected':'' ?>>✅ Accept Booking</option>
                                    <option value="Rejected" <?= $booking['status']=='Rejected'?'selected':'' ?>>❌ Reject Booking</option>
                                </select>
                                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--gold); font-size: 0.95rem;">
                                    <i class="fas fa-gavel"></i>
                                </span>
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--slate-400); font-size: 0.85rem; pointer-events: none;">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label" style="font-size: 0.72rem; color: var(--slate-500); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 8px;">Payment Progress</label>
                            <div style="position: relative;">
                                <select name="payment_status" style="width: 100%; padding: 12px 16px 12px 40px; border: 1px solid #E2E8F0; border-radius: 10px; font-family: inherit; font-size: 0.92rem; font-weight: 600; color: var(--slate-800); background: #fff; transition: all 0.2s; outline: none; appearance: none; cursor: pointer;">
                                    <option value="Pending Deposit" <?= ($booking['payment_status']??'')==='Pending Deposit'?'selected':'' ?>>💳 Pending Deposit</option>
                                    <option value="Deposit Paid"    <?= ($booking['payment_status']??'')==='Deposit Paid'   ?'selected':'' ?>>✔ Deposit Paid</option>
                                    <option value="Pending Balance" <?= ($booking['payment_status']??'')==='Pending Balance'?'selected':'' ?>>⏳ Pending Balance</option>
                                    <option value="Fully Paid"      <?= ($booking['payment_status']??'')==='Fully Paid'     ?'selected':'' ?>>💰 Fully Paid</option>
                                </select>
                                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--gold); font-size: 0.95rem;">
                                    <i class="fas fa-credit-card"></i>
                                </span>
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--slate-400); font-size: 0.85rem; pointer-events: none;">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>
                        <div style="background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.1); border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start;">
                            <i class="fas fa-info-circle" style="color: var(--info); font-size: 0.95rem; margin-top: 2px;"></i>
                            <div style="font-size: 0.78rem; color: #1E40AF; font-weight: 500; line-height: 1.4;">
                                An automated email will be sent to the customer instantly with the updated invoice and status.
                            </div>
                        </div>
                        <button type="submit" class="btn" style="background: var(--gold); color: #fff; width: 100%; padding: 14px; border-radius: 10px; font-weight: 700; font-size: 0.92rem; display: flex; align-items: center; justify-content: center; gap: 10px; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px var(--gold-glow);">
                            <i class="fas fa-check"></i> Save Changes & Notify
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