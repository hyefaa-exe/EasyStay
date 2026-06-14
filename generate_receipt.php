<?php
session_start();
require 'db_connect.php';

// 1. Semak sesi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$book_id = $_GET['id'] ?? $_GET['book_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$book_id) {
    die("Booking ID tidak dijumpai.");
}

// 2. Ambil data tempahan lengkap dengan maklumat pakej dan user
$sql = "SELECT b.*, p.package_name, p.price as base_price, u.full_name, u.email, u.phone 
        FROM bookings b 
        JOIN packages p ON b.package_id = p.package_id 
        JOIN users u ON b.user_id = u.user_id 
        WHERE b.book_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $book_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Rekod tempahan tidak sah atau anda tiada akses.");
}

// ==========================================
// 3. LOGIK PEMBAIKAN (FIX)
// ==========================================

// Semak adakah tarikh wujud dan bukan '0000-00-00'
$valid_dates = ($data['checkin_date'] && $data['checkin_date'] != '0000-00-00' && 
                $data['checkout_date'] && $data['checkout_date'] != '0000-00-00');

$nights = 0;

if ($valid_dates) {
    try {
        $d1 = new DateTime($data['checkin_date']);
        $d2 = new DateTime($data['checkout_date']);
        
        // Pastikan check-out lebih besar dari check-in
        if ($d2 > $d1) {
            $nights = $d2->diff($d1)->format("%a");
        } else {
            $nights = 1; // Default minimum 1 malam jika tarikh salah
        }
    } catch (Exception $e) {
        $nights = 0;
    }
}

// Kira Total Price (Guna harga database jika ada, jika 0 baru kira manual)
if ($data['total_price'] > 0) {
    $total_price = $data['total_price'];
} else {
    $total_price = $nights * $data['base_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_#UG-<?php echo str_pad($data['book_id'], 5, '0', STR_PAD_LEFT); ?></title>
    <!-- Premium Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-gold: #C5A880;
            --hover-gold: #A48256;
            --dark-charcoal: #121212;
            --text-main: #2D2D2D;
            --text-muted: #7A7A7A;
            --bg-soft: #FAF9F6;
            --border-color: rgba(197, 168, 128, 0.15);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px;
            background: radial-gradient(circle at top right, #fbfaf8 0%, #f4f2ee 100%);
            -webkit-font-smoothing: antialiased;
        }

        .receipt-box {
            max-width: 850px;
            margin: auto;
            padding: 50px;
            border-radius: 24px;
            background: white;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            border-top: 6px solid var(--primary-gold);
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-container img {
            height: 55px;
            width: auto;
        }
        .brand-info h2 {
            margin: 0;
            letter-spacing: -0.5px;
            line-height: 1;
            margin-bottom: 5px;
            font-weight: 800;
            font-size: 24px;
        }
        .text-ulu {
            color: var(--primary-gold);
        }
        .text-garden {
            color: var(--dark-charcoal);
        }
        .brand-info small {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.5;
            display: block;
        }

        .doc-info {
            text-align: right;
        }
        .doc-info h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--dark-charcoal);
            margin-bottom: 8px;
        }
        .doc-info p {
            margin: 2px 0;
            font-size: 13px;
            color: var(--text-main);
        }
        .doc-info p.invoice-date {
            color: var(--text-muted);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }
        .info-col {
            flex: 1;
        }

        .section-label {
            font-size: 10px;
            font-weight: 800;
            color: var(--primary-gold);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
        }

        .customer-card {
            background: #FAF9F6;
            padding: 18px 24px;
            border-radius: 16px;
            border: 1px dashed rgba(197, 168, 128, 0.25);
        }
        .customer-name {
            margin: 0 0 6px 0;
            font-size: 15px;
            font-weight: 700;
            color: var(--dark-charcoal);
        }
        .customer-meta {
            margin: 2px 0;
            font-size: 13px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .customer-meta i {
            color: var(--primary-gold);
            font-size: 12px;
            width: 14px;
        }

        .status-stamp {
            display: inline-block;
            padding: 8px 18px;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 2px;
            border-radius: 8px;
            transform: rotate(-3deg);
            text-transform: uppercase;
            margin-top: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            border: 2px dashed currentColor;
        }
        .stamp-success {
            color: #2E7D32;
            background: rgba(46, 125, 50, 0.04);
        }
        .stamp-pending {
            color: #D48C00;
            background: rgba(212, 140, 0, 0.04);
        }
        .stamp-danger {
            color: #C62828;
            background: rgba(198, 40, 40, 0.04);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .table th {
            background: #FAF9F6;
            text-align: left;
            padding: 14px 18px;
            border-bottom: 2px solid #eaeaea;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .table td {
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            vertical-align: middle;
        }
        .table td strong {
            font-size: 15px;
            color: var(--dark-charcoal);
            font-weight: 700;
        }
        .table td small {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
            margin-top: 3px;
        }

        .total-box-container {
            width: 320px;
            margin-left: auto;
            margin-bottom: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: var(--text-main);
        }
        .text-muted-small {
            font-size: 12px;
            color: var(--text-muted);
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .deposit-info {
            display: block;
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 2px;
            text-align: right;
            font-style: italic;
        }
        .grand-total-box {
            background: #FAF9F6;
            border-radius: 12px;
            padding: 14px 20px;
            border: 1px solid var(--border-color);
            margin-top: 5px;
        }
        .total-row-grand {
            align-items: center;
            padding: 0;
        }
        .total-row-grand span:first-child {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--text-muted);
        }
        .grand-total-amount {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-gold);
        }

        .footer-section {
            margin-top: 60px;
            border-top: 1px solid #eaeaea;
            padding-top: 20px;
        }
        .footer-section p {
            margin: 6px 0;
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .footer-section p i {
            color: var(--primary-gold);
            font-size: 10px;
        }

        /* Navigation Buttons */
        .no-print {
            max-width: 400px;
            margin: 30px auto 50px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-print {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-gold) 0%, var(--hover-gold) 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
            transition: all 0.3s ease;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.4);
        }
        .btn-back {
            flex: 1;
            background: white;
            color: #555;
            border: 1px solid #ddd;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #fbfaf8;
            color: var(--dark-charcoal);
            border-color: var(--primary-gold);
            transform: translateY(-2px);
        }

        @media print {
            .no-print { display: none !important; }
            .receipt-box {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 0;
                border-top: none;
            }
            body {
                padding: 0;
                background: white;
            }
        }
    </style>
</head>
<body>

    <?php
    // Semak status untuk stamp style
    $status_clean = strtolower($data['status']);
    $stamp_class = 'stamp-pending';
    if ($status_clean == 'accepted' || $status_clean == 'completed' || $status_clean == 'verified') {
        $stamp_class = 'stamp-success';
    } elseif ($status_clean == 'cancelled' || $status_clean == 'rejected') {
        $stamp_class = 'stamp-danger';
    }
    ?>

    <div class="receipt-box">
        <div class="header">
            <div class="logo-container">
                <img src="img/logo.png?v=2" alt="Logo">
                <div class="brand-info">
                    <h2><span class="text-ulu">EASY</span><span class="text-garden">STAY</span></h2>
                    <small>Lot 8012, Kampung Binjai Kertas,<br>21700 Kuala Berang, Terengganu.</small>
                </div>
            </div>
            <div class="doc-info">
                <h1>OFFICIAL RECEIPT</h1>
                <p style="font-weight: 700;">Invoice No: #UG-<?php echo str_pad($data['book_id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p class="invoice-date">Date: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="info-col">
                <span class="section-label">Billed To</span>
                <div class="customer-card">
                    <h3 class="customer-name"><?php echo htmlspecialchars($data['full_name']); ?></h3>
                    <p class="customer-meta"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($data['email']); ?></p>
                    <p class="customer-meta"><i class="fa-regular fa-phone"></i> <?php echo htmlspecialchars($data['phone']); ?></p>
                </div>
            </div>
            <div class="info-col" style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; justify-content: flex-start;">
                <span class="section-label">Booking Status</span>
                <div class="status-stamp <?php echo $stamp_class; ?>"><?php echo htmlspecialchars($data['status']); ?></div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th style="text-align: center;">Nights</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($data['package_name']); ?></strong>
                        <small>Pax: <?php echo $data['adults']; ?> Adults, <?php echo $data['children']; ?> Children</small>
                    </td>
                    <td>
                        <?php echo $valid_dates ? date('d M Y', strtotime($data['checkin_date'])) : '<span style="color:red; font-style:italic;">Invalid Date</span>'; ?>
                    </td>
                    <td>
                        <?php echo $valid_dates ? date('d M Y', strtotime($data['checkout_date'])) : '<span style="color:red; font-style:italic;">Invalid Date</span>'; ?>
                    </td>
                    <td style="text-align: center; font-weight: 600;"><?php echo $nights; ?></td>
                    <td style="text-align: right; font-weight: 600; color: var(--dark-charcoal);">RM <?php echo number_format($data['base_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-box-container">
            <div class="total-row">
                <span style="font-weight: 500;">Subtotal</span>
                <span style="font-weight: 600; color: var(--dark-charcoal);">RM <?php echo number_format($total_price, 2); ?></span>
            </div>
            <div class="total-row text-muted-small">
                <span>Security Deposit</span>
                <span>
                    RM 50.00
                    <span class="deposit-info">Refundable at check-out</span>
                </span>
            </div>
            <div class="grand-total-box">
                <div class="total-row total-row-grand">
                    <span>GRAND TOTAL PAID</span>
                    <span class="grand-total-amount">RM <?php echo number_format($total_price, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer-section">
            <p><i class="fa-solid fa-circle-info"></i> * This is a computer-generated receipt. No signature is required.</p>
            <p><i class="fa-solid fa-circle-info"></i> * Please present this receipt during check-in.</p>
        </div>
    </div>

    <div class="no-print">
        <a href="my_profile.php?tab=booking" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Profile
        </a>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print / Download PDF
        </button>
    </div>

</body>
</html>