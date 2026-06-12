<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}
require_once 'db_connect.php';
$admin_id = $_SESSION['admin_id'];

// ==========================================
// MOD 1: JIKA ADA ID -> PAPAR DETAIL (INDIVIDU)
// ==========================================
if (isset($_GET['book_id'])) {
    
    $book_id = $_GET['book_id'];
    $msg = "";

    // Proses Update
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $new_status = $_POST['status'];
        $new_pay_status = $_POST['payment_status'];
        
        $stmt = $conn->prepare("UPDATE bookings SET status=?, payment_status=? WHERE book_id=?");
        $stmt->bind_param("ssi", $new_status, $new_pay_status, $book_id);
        
        if ($stmt->execute()) {
            $msg = "Status berjaya dikemaskini!";
        }
    }

    // Ambil Data
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
        <title>Payment Verification #<?= $book_id ?> | EasyStay</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
/* --- HEADER (SAMA SEPERTI MANAGE_BOOKINGS) --- */
            
            .logo-area h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
            .logo-area a { text-decoration: none; }
            
            
            
            
            
            
            

            /* --- DETAIL CONTENT --- */
            
            .content-card { background: var(--white); padding: 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
            
            
            .info-box { background: #F9FAFB; padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #eee; }
            .proof-card { border: 1px solid #eee; border-radius: 15px; padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
            .proof-card.deposit { border-left: 5px solid var(--ulu-orange); }
            .proof-card.balance { border-left: 5px solid #27ae60; }
            .btn-view { background: var(--garden-black); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
            .update-box { background: #FFF9C4; padding: 25px; border-radius: 15px; border: 1px solid #FBC02D; margin-top: 30px; }
            .update-box label { font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: #854D0E; display: block; margin-bottom: 5px; }
            select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; margin-bottom: 20px; font-family: inherit; }
            .btn-save { background: var(--ulu-orange); color: white; border: none; padding: 15px; border-radius: 12px; width: 100%; cursor: pointer; font-weight: 800; font-size: 1rem; transition: 0.3s; }
            .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197, 168, 128, 0.3); }
    </style>
        <link rel="stylesheet" href="css/admin_style.css">
</head>
    <body>

    <header class="header">
        <div class="logo-area">
            <a href="admin_dashboard.php">
                <h1><span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span></h1>
            </a>
            <span class="brand-sub">Management Portal</span>
        </div>
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_bookings.php" class="nav-btn btn-profile"><i class="fas fa-calendar-alt"></i> Bookings</a>
            <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="main-container">
        <div class="content-card">
            <a href="view_payments.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Payment List</a>
            
            <h2 style="font-size: 1.8rem; margin-bottom: 10px;">Verify Payment #<?= $booking['book_id'] ?></h2>
            <?php if($msg): ?>
                <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600; border-left: 5px solid #059669;">
                    <i class="fas fa-check-circle"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <p style="font-size: 0.8rem; color: #888; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Guest Information</p>
                <strong style="font-size: 1.2rem; color: var(--garden-black);"><?= htmlspecialchars($booking['full_name']) ?></strong><br>
                <span style="color: var(--ulu-orange); font-weight: 600;"><?= htmlspecialchars($booking['package_name']) ?></span><br>
                <small style="font-weight: 700;">Total Amount: RM <?= number_format($booking['total_price'],2) ?></small>
            </div>

            <div class="proof-card deposit">
                <div>
                    <strong style="display:block; margin-bottom:4px;">Deposit Receipt</strong>
                    <span class="status-badge" style="font-size: 0.75rem; color: <?= $booking['receipt_path'] ? '#27ae60' : '#e74c3c' ?>;">
                        <i class="fas <?= $booking['receipt_path'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        <?= $booking['receipt_path'] ? 'Receipt Uploaded' : 'Not Uploaded Yet' ?>
                    </span>
                </div>
                <?php if($booking['receipt_path']): ?>
                    <a href="../admin/uploads/receipts/<?= $booking['receipt_path'] ?>" target="_blank" class="btn-view">View Receipt</a>
                <?php endif; ?>
            </div>

            <div class="proof-card balance">
                <div>
                    <strong style="display:block; margin-bottom:4px;">Balance Receipt</strong>
                    <span class="status-badge" style="font-size: 0.75rem; color: <?= $booking['balance_receipt'] ? '#27ae60' : '#e74c3c' ?>;">
                        <i class="fas <?= $booking['balance_receipt'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        <?= $booking['balance_receipt'] ? 'Receipt Uploaded' : 'Not Uploaded Yet' ?>
                    </span>
                </div>
                <?php if($booking['balance_receipt']): ?>
                    <a href="../admin/uploads/receipts/<?= $booking['balance_receipt'] ?>" target="_blank" class="btn-view">View Receipt</a>
                <?php endif; ?>
            </div>

            <form method="POST" class="update-box">
                <label>Update Booking Status</label>
                <select name="status">
                    <option value="Pending" <?= $booking['status']=='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Accepted" <?= $booking['status']=='Accepted'?'selected':'' ?>>Accepted</option>
                    <option value="Rejected" <?= $booking['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                </select>

                <label>Update Payment Status</label>
                <select name="payment_status">
                    <option value="Pending Deposit" <?= $booking['payment_status']=='Pending Deposit'?'selected':'' ?>>Pending Deposit</option>
                    <option value="Deposit Paid" <?= $booking['payment_status']=='Deposit Paid'?'selected':'' ?>>Deposit Paid</option>
                    <option value="Pending Balance" <?= $booking['payment_status']=='Pending Balance'?'selected':'' ?>>Pending Balance</option>
                    <option value="Fully Paid" <?= $booking['payment_status']=='Fully Paid'?'selected':'' ?>>Fully Paid</option>
                </select>
                <button type="submit" class="btn-save">Update Records</button>
            </form>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// ==========================================
// MOD 2: TIADA ID -> PAPAR SENARAI SEMUA (LIST)
// ==========================================
$sql = "SELECT b.*, u.full_name, p.package_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        JOIN packages p ON b.package_id = p.package_id 
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Payments | EasyStay Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
/* --- HEADER --- */
        
        .logo-area h1 { font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; }
        .logo-area a { text-decoration: none; }
        
        
        
        
        
        
        
        

        /* --- TABLE LAYOUT --- */
        
        .table-card { background: var(--white); border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.03); padding: 10px; }
        
        
        
        
        
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .bg-Pending-Deposit { background: #ffebee; color: #c62828; }
        .bg-Deposit-Paid { background: #e3f2fd; color: #1565c0; }
        .bg-Pending-Balance { background: #fff3e0; color: #ef6c00; }
        .bg-Fully-Paid { background: #e8f5e9; color: #2e7d32; }

        .btn-check { background: var(--ulu-orange); color: white; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; transition: 0.3s; }
        .btn-check:hover { background: var(--garden-black); transform: translateY(-2px); }

        .page-title { margin-bottom: 30px; }
        .page-title h2 { font-size: 2rem; font-weight: 800; color: var(--garden-black); }
    </style>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<header class="header">
    <div class="logo-area">
        <a href="admin_dashboard.php">
            <h1><span class="logo-ulu">Easy</span><span class="logo-garden">Stay</span></h1>
        </a>
        <span class="brand-sub">Management Portal</span>
    </div>
    <div class="nav-actions">
        <a href="admin_dashboard.php" class="nav-btn btn-profile"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="manage_bookings.php" class="nav-btn btn-profile"><i class="fas fa-calendar-alt"></i> Bookings</a>
        <a href="../logout.php" class="nav-btn btn-logout" onclick="return confirm('Confirm Logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</header>

<div class="container">
    <div class="page-title">
        <h2><i class="fas fa-wallet" style="color:var(--ulu-orange); margin-right:10px;"></i> Payment Verifications</h2>
        <p style="color: #666;">Verify guest receipts and manage payment lifecycle.</p>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Customer / Package</th>
                    <th>Total Price</th>
                    <th>Payment Status</th>
                    <th>Receipt Proofs</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: var(--ulu-orange);">#<?= $row['book_id'] ?></td>
                    <td>
                        <div style="font-weight: 700; color: var(--garden-black);"><?= htmlspecialchars($row['full_name']) ?></div>
                        <small style="color:#888"><?= htmlspecialchars($row['package_name']) ?></small>
                    </td>
                    <td style="font-weight: 600;">RM <?= number_format($row['total_price'], 2) ?></td>
                    <td>
                        <?php 
                            $ps = $row['payment_status'] ?? 'Pending Deposit'; 
                            $cls = str_replace(' ', '-', $ps);
                        ?>
                        <span class="badge bg-<?= $cls ?>"><?= $ps ?></span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <?php if($row['receipt_path']): ?>
                                <span title="Deposit Received" style="color: #27ae60; font-size: 1.1rem;"><i class="fas fa-file-invoice-dollar"></i></span>
                            <?php else: ?>
                                <span title="No Deposit" style="color: #ccc; font-size: 1.1rem;"><i class="fas fa-file-invoice-dollar"></i></span>
                            <?php endif; ?>

                            <?php if($row['balance_receipt']): ?>
                                <span title="Balance Received" style="color: #2ecc71; font-size: 1.1rem;"><i class="fas fa-check-double"></i></span>
                            <?php else: ?>
                                <span title="No Balance" style="color: #ccc; font-size: 1.1rem;"><i class="fas fa-check-double"></i></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <a href="view_payments.php?book_id=<?= $row['book_id'] ?>" class="btn-check">
                            Verify <i class="fas fa-chevron-right" style="margin-left: 5px;"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>