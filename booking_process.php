<?php
session_start();
require 'db_connect.php';

// 1. Semak Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Proses Borang Tempahan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_booking'])) {
    
    $user_id = $_SESSION['user_id'];
    $package_id = $_POST['package_id'];
    
    // --- PEMBETULAN TARIKH (CRITICAL) ---
    // Tukar format input kepada YYYY-MM-DD untuk database MySQL
    $raw_checkin = $_POST['checkin_date'];
    $raw_checkout = $_POST['checkout_date'];
    
    // Gunakan str_replace untuk tukar '/' kepada '-' sebagai langkah berjaga-jaga
    $checkin = date('Y-m-d', strtotime(str_replace('/', '-', $raw_checkin)));
    $checkout = date('Y-m-d', strtotime(str_replace('/', '-', $raw_checkout)));
    // ------------------------------------

    $adults = $_POST['adults'];
    $children = $_POST['children'];
    $total_price = $_POST['total_price'];

    // 3. Semak Kekosongan (Double Check)
    // Pastikan tarikh belum diambil oleh orang lain (Status NOT IN 'Cancelled', 'Rejected')
    $check_sql = "SELECT book_id FROM bookings 
                  WHERE package_id = ? 
                  AND status NOT IN ('Cancelled', 'Rejected') 
                  AND (checkin_date < ? AND checkout_date > ?)";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("iss", $package_id, $checkout, $checkin);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        die("Maaf, tarikh ini baru sahaja ditempah oleh orang lain. Sila pilih tarikh lain.");
    }

    // 4. Proses Upload Resit Deposit
    $receipt_path = "";
    $original_filename = "";

    if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['payment_receipt']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            // Rename fail unik: time_uniqid.ext
            $new_name = time() . "_" . uniqid() . "." . $file_ext;
            $destination = "admin/uploads/receipts/" . $new_name;

            // Pastikan folder wujud
            if (!is_dir('admin/uploads/receipts/')) {
                mkdir('admin/uploads/receipts/', 0777, true);
            }

            if (move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $destination)) {
                $receipt_path = $new_name;
                $original_filename = $filename;
            } else {
                die("Gagal memuat naik resit. Sila cuba lagi.");
            }
        } else {
            die("Format fail tidak sah. Sila guna JPG, PNG atau PDF.");
        }
    } else {
        die("Resit pembayaran wajib dimuat naik.");
    }

    // 5. Simpan ke Database
    // Ambil data user (nama/email) untuk rekod
    $user_sql = "SELECT full_name, email FROM users WHERE user_id = ?";
    $stmt_u = $conn->prepare($user_sql);
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $u_data = $stmt_u->get_result()->fetch_assoc();

    $full_name = $u_data['full_name'];
    $customer_email = $u_data['email'];
    
    // Default Status
    $status = 'Pending';
    $payment_status = 'Pending Deposit';

    $sql_insert = "INSERT INTO bookings 
                   (user_id, full_name, customer_email, package_id, checkin_date, checkout_date, adults, children, total_price, receipt_path, original_filename, status, payment_status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);

    // --- PEMBETULAN BIND_PARAM DI SINI ---
    // ASAL: "isssisiidssss" (Salah huruf ke-5 'i')
    // BARU: "isssssiidssss" (Huruf ke-5 ditukar jadi 's' untuk string tarikh)
    $stmt->bind_param("isssssiidssss", $user_id, $full_name, $customer_email, $package_id, $checkin, $checkout, $adults, $children, $total_price, $receipt_path, $original_filename, $status, $payment_status);

    if ($stmt->execute()) {
        // Berjaya
        header("Location: my_profile.php?msg=BookingSuccess");
        exit();
    } else {
        echo "Ralat Database: " . $stmt->error;
    }

} else {
    // Jika akses terus tanpa post
    header("Location: package.php");
    exit();
}
?>