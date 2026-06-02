<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['balance_receipt']) && $_FILES['balance_receipt']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['balance_receipt']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = time() . '_bal_' . uniqid() . '.' . $ext;
            $destination = 'admin/uploads/receipts/' . $new_name;

            if (move_uploaded_file($_FILES['balance_receipt']['tmp_name'], $destination)) {
                
                // UPDATE: Simpan nama file DAN tukar payment_status kepada 'Pending Balance'
                $stmt = $conn->prepare("UPDATE bookings SET balance_receipt = ?, payment_status = 'Pending Balance' WHERE book_id = ? AND user_id = ?");
                $stmt->bind_param("sii", $new_name, $booking_id, $user_id);
                
                if ($stmt->execute()) {
                    header("Location: my_profile.php?tab=booking&msg=BalanceUploadSuccess");
                    exit();
                }
            }
        }
    }
}
header("Location: my_profile.php?tab=booking&msg=Error");
?>