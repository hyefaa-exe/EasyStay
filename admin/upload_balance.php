<?php
session_start();
require 'db_connect.php'; 

// Check Admin Session
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";
$messageType = "";
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header("Location: view_payments.php");
    exit();
}

// 1. Ambil data booking untuk rujukan admin
$stmt = $conn->prepare("SELECT b.book_id, u.full_name, b.total_price, b.status, b.payment_status FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

// 2. Proses Upload bila butang ditekan
if (isset($_POST['submit_upload'])) {
    $target_dir = "../uploads/balance/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES["balance_file"]["name"], PATHINFO_EXTENSION);
    $new_filename = "BAL_" . $book_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Validasi fail
    $check = getimagesize($_FILES["balance_file"]["tmp_name"]);
    if($check !== false) {
        if (move_uploaded_file($_FILES["balance_file"]["tmp_name"], $target_file)) {
            
            /** * PENAMBAHBAIKAN:
             * Status payment ditukar kepada 'Fully Paid', manakala status tempahan
             * kekal sebagai 'Accepted' sehingga pelanggan betul-betul check-out.
             **/
            $update_stmt = $conn->prepare("UPDATE bookings SET balance_receipt = ?, payment_status = 'Fully Paid' WHERE book_id = ?");
            $update_stmt->bind_param("si", $new_filename, $book_id);
            
            if ($update_stmt->execute()) {
                $message = "Success! Balance receipt uploaded and payment is now FULLY PAID.";
                $messageType = "success";
                $booking['payment_status'] = 'Fully Paid'; // Update paparan status
            } else {
                $message = "Database update failed.";
                $messageType = "danger";
            }
        } else {
            $message = "Failed to upload file to server.";
            $messageType = "danger";
        }
    } else {
        $message = "File is not a valid image.";
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Balance | EasyStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
.upload-card {
            background: var(--white);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(197, 168, 128, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--soft-orange-bg);
            color: var(--ulu-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }

        h2 { color: var(--garden-black); margin-bottom: 10px; font-weight: 800; }
        .sub-text { color: #666; font-size: 0.9rem; margin-bottom: 30px; }

        .info-preview {
            background: #fafafa;
            padding: 15px;
            border-radius: 15px;
            text-align: left;
            margin-bottom: 25px;
            border-left: 4px solid var(--ulu-orange);
            position: relative;
        }

        .info-preview div { margin-bottom: 5px; font-size: 0.85rem; }
        .info-preview strong { color: var(--garden-black); }

        .status-tag {
            float: right;
            font-size: 0.65rem;
            padding: 4px 10px;
            border-radius: 8px;
            background: #FEF9C3;
            color: #854D0E;
            text-transform: uppercase;
            font-weight: 700;
        }
        
        .status-completed-tag {
            background: #D1FAE5;
            color: #065F46;
        }

        .file-input-wrapper {
            margin-bottom: 25px;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #ddd;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-submit {
            background: var(--ulu-orange);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
        }

        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(197, 168, 128, 0.2); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }

        .btn-back {
            display: inline-block;
            margin-top: 25px;
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-back:hover { color: var(--ulu-orange); }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

    <div class="upload-card">
        <div class="icon-box">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        
        <h2>Upload Balance Receipt</h2>
        <p class="sub-text">Upload payment proof to complete the transaction.</p>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType; ?>">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <div class="info-preview">
            <span class="status-tag <?= (strtolower($booking['payment_status']) == 'fully paid') ? 'status-completed-tag' : ''; ?>">
                <?= $booking['payment_status']; ?>
            </span>
            <div><strong>Booking ID:</strong> #<?= $booking['book_id']; ?></div>
            <div><strong>Customer:</strong> <?= htmlspecialchars($booking['full_name']); ?></div>
            <div><strong>Total Price:</strong> RM <?= number_format($booking['total_price'], 2); ?></div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="file-input-wrapper">
                <input type="file" name="balance_file" accept="image/*" required>
            </div>
            
            <button type="submit" name="submit_upload" class="btn-submit" 
                <?= (strtolower($booking['payment_status']) == 'fully paid') ? 'disabled' : ''; ?>>
                <i class="fas fa-cloud-upload-alt"></i> 
                <?= (strtolower($booking['payment_status']) == 'fully paid') ? 'Already Uploaded' : 'Confirm & Upload'; ?>
            </button>
        </form>

        <a href="view_payments.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Payment
        </a>
    </div>

</body>
</html>