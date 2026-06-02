<?php
session_start();
require 'db_connect.php';

// 1. Semak sesi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Terima 'id' (dari profile) ATAU 'book_id' (jika ada link lama)
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; color: #333; line-height: 1.6; padding: 20px; background-color: #f4f4f4; }
        .receipt-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; background: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #FF7F32; padding-bottom: 20px; margin-bottom: 20px; }
        
        .logo-container { display: flex; align-items: center; gap: 12px; }
        .logo-container img { height: 60px; width: auto; }
        .brand-info h2 { margin: 0; letter-spacing: -0.5px; line-height: 1; margin-bottom: 2px; font-weight: 800; font-size: 24px; }
        .text-ulu { color: #FF7F32; }
        .text-garden { color: #1A1A1A; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-col h4 { margin: 0 0 10px 0; color: #888; text-transform: uppercase; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #eee; }
        .table td { padding: 12px; border-bottom: 1px solid #eee; }
        .total-section { text-align: right; }
        .total-section h2 { color: #FF7F32; margin-top: 5px; }
        .status-stamp { display: inline-block; border: 3px solid #2e7d32; color: #2e7d32; padding: 5px 15px; font-weight: bold; border-radius: 5px; transform: rotate(-5deg); text-transform: uppercase; margin-top: 10px; }
        
        /* Navigation Buttons */
        .no-print { 
            max-width: 250px; 
            margin: 20px auto 40px; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }
        .btn-print { 
            background: #FF7F32; color: white; border: none; padding: 8px 16px; 
            border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; 
            display: flex; align-items: center; justify-content: center; font-size: 13px; 
        }
        .btn-back { 
            background: white; color: #666; border: 1px solid #ccc; padding: 8px 16px; 
            border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; 
            display: flex; align-items: center; justify-content: center; transition: 0.3s; font-size: 13px; 
        }
        .btn-back:hover { background: #f8f9fa; border-color: #999; color: #333; }
        
        @media print {
            .no-print { display: none; }
            .receipt-box { border: none; box-shadow: none; margin: 0; padding: 0; }
            body { padding: 0; background-color: white; }
        }
    </style>
</head>
<body>

    <div class="receipt-box">
        <div class="header">
            <div class="logo-container">
                <img src="img/logo.png" alt="Logo">
                <div class="brand-info">
                    <h2><span class="text-ulu">ULU</span><span class="text-garden">GARDEN</span></h2>
                    <small>Lot 8012, Kampung Binjai Kertas,<br>21700 Kuala Berang, Terengganu.</small>
                </div>
            </div>
            <div style="text-align: right;">
                <h1 style="margin:0; font-size: 24px;">OFFICIAL RECEIPT</h1>
                <p style="margin:0;">Invoice No: #UG-<?php echo str_pad($data['book_id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p style="margin:0;">Date: <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>

        <div class="info-row">
            <div class="info-col">
                <h4>Customer Details:</h4>
                <strong><?php echo htmlspecialchars($data['full_name']); ?></strong><br>
                <?php echo htmlspecialchars($data['email']); ?><br>
                <?php echo htmlspecialchars($data['phone']); ?>
            </div>
            <div class="info-col" style="text-align: right;">
                <h4>Booking Status:</h4>
                <div class="status-stamp"><?php echo $data['status']; ?></div>
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
                        <strong><?php echo htmlspecialchars($data['package_name']); ?></strong><br>
                        <small>Pax: <?php echo $data['adults']; ?> Adults, <?php echo $data['children']; ?> Children</small>
                    </td>
                    <td>
                        <?php echo $valid_dates ? date('d M Y', strtotime($data['checkin_date'])) : '<span style="color:red; font-style:italic;">Invalid Date</span>'; ?>
                    </td>
                    <td>
                        <?php echo $valid_dates ? date('d M Y', strtotime($data['checkout_date'])) : '<span style="color:red; font-style:italic;">Invalid Date</span>'; ?>
                    </td>
                    <td style="text-align: center;"><?php echo $nights; ?></td>
                    <td style="text-align: right;">RM <?php echo number_format($data['base_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <p style="margin:0;">Subtotal: RM <?php echo number_format($total_price, 2); ?></p>
            <p style="margin:0;">Security Deposit: RM 50.00</p>
            <hr style="width: 200px; margin-right: 0;">
            <p style="margin:0; font-weight: bold;">GRAND TOTAL</p>
            <h2>RM <?php echo number_format($total_price, 2); ?></h2>
        </div>

        <div style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 10px;">
            <p><small>* This is a computer-generated receipt. No signature is required.</small></p>
            <p><small>* Please present this receipt during check-in.</small></p>
        </div>
    </div>

    <div class="no-print">
        <a href="my_profile.php?tab=booking" class="btn-back">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Profile
        </a>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> Print / Download PDF
        </button>
    </div>

</body>
</html>