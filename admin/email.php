<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan path autoload betul. 
require_once dirname(__DIR__) . '/vendor/autoload.php';

function sendBookingStatusEmail($toEmail, $toName, $emailContent, $bookingId = null) {
    $mail = new PHPMailer(true);

    try {
        // --- TETAPAN SERVER SMTP (GMAIL) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'easystay.mpi@gmail.com'; 
        $mail->Password   = 'zzfp ayfc buxf fsgk'; // App Password Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- PENGHANTAR & PENERIMA ---
        $mail->setFrom('easystay.mpi@gmail.com', 'EasyStay Admin');
        $mail->addAddress($toEmail, $toName);

        // --- PROSES KANDUNGAN & TEMPLATE ---
        $mail->isHTML(true);
        
        if (is_array($emailContent)) {
            $packageName   = $emailContent['package_name'] ?? '';
            $checkin       = $emailContent['checkin'] ?? '';
            $checkout      = $emailContent['checkout'] ?? '';
            $status        = $emailContent['status'] ?? '';
            $paymentStatus = $emailContent['payment_status'] ?? '';
            $balance       = $emailContent['balance'] ?? 0.00;
            $bookingId     = $emailContent['booking_id'] ?? $bookingId;

            // Status style
            $statusStyle = "background-color: #FEF9C3; color: #854D0E; border: 1px solid #FEF08A;"; // Pending
            if ($status == 'Accepted' || $status == 'Completed') {
                $statusStyle = "background-color: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0;";
            } elseif ($status == 'Rejected' || $status == 'Cancelled') {
                $statusStyle = "background-color: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5;";
            }

            // Payment Status style
            $paymentStatusStyle = "background-color: #FEF9C3; color: #854D0E; border: 1px solid #FEF08A;"; // Pending Deposit
            if ($paymentStatus == 'Fully Paid') {
                $paymentStatusStyle = "background-color: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0;";
            } elseif ($paymentStatus == 'Deposit Paid') {
                $paymentStatusStyle = "background-color: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE;";
            }

            $balanceFormatted = number_format($balance, 2);
            $mail->Subject = "Update on Booking #$bookingId | EasyStay";

            // Tentukan URL Dashboard secara dinamik
            $isLocalhost = false;
            if (isset($_SERVER['HTTP_HOST'])) {
                $host = $_SERVER['HTTP_HOST'];
                if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                    $isLocalhost = true;
                }
            } else {
                $isLocalhost = true; 
            }

            $ctaSection = '';
            if ($isLocalhost) {
                // Di localhost, kita elakkan meletakkan tag <a href="http://localhost..."> kerana penapis spam menyekatnya.
                // Sebaliknya, letak butang rekaan visual yang membimbing pengguna secara teks.
                $ctaSection = "
                <div style=\"text-align: center; margin-top: 10px;\">
                    <p style=\"font-size: 14px; color: #718096; margin-bottom: 15px;\">To view complete details or upload your receipt, please log in to your EasyStay Dashboard.</p>
                    <div style=\"background-color: #C5A880; color: #ffffff; padding: 14px 35px; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-block; text-align: center; opacity: 0.9;\">
                        Access Dashboard via EasyStay Website
                    </div>
                </div>";
            } else {
                // Di server pengeluaran (production), letak butang pautan aktif dengan pautan dinamik
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $dashboardUrl = $protocol . $_SERVER['HTTP_HOST'] . '/easystay/login.php';
                
                $ctaSection = "
                <div style=\"text-align: center; margin-top: 10px;\">
                    <p style=\"font-size: 14px; color: #718096; margin-bottom: 22px;\">To view complete details or upload your receipt, please visit your dashboard.</p>
                    <a href=\"$dashboardUrl\" style=\"background-color: #C5A880; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-block; box-shadow: 0 4px 12px rgba(197, 168, 128, 0.35); text-align: center;\">
                        Go to Dashboard
                    </a>
                </div>";
            }

            $finalBody = "
            <div style=\"background-color: #f4f6f8; padding: 40px 20px; font-family: 'Plus Jakarta Sans', 'Inter', 'Helvetica Neue', Arial, sans-serif; min-height: 100%;\">
                <div style=\"max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eaeaea;\">
                    
                    <!-- Header Banner -->
                    <div style=\"background: linear-gradient(135deg, #C5A880 0%, #A37F52 100%); padding: 35px 20px; text-align: center; color: #ffffff;\">
                        <div style=\"font-size: 11px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; margin-bottom: 8px; opacity: 0.9;\">EasyStay Homestay</div>
                        <h1 style=\"margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;\">Booking Notification</h1>
                    </div>

                    <!-- Main Content -->
                    <div style=\"padding: 40px 35px; color: #2D3748;\">
                        <p style=\"margin-top: 0; font-size: 16px; line-height: 1.6; color: #4A5568;\">
                            Dear <strong style=\"color: #1A202C;\">$toName</strong>,
                        </p>
                        <p style=\"font-size: 15px; line-height: 1.6; color: #718096; margin-bottom: 30px;\">
                            Your reservation details for Booking <strong style=\"color: #1A202C;\">#$bookingId</strong> have been updated by our administrator. Below are the latest updates for your booking:
                        </p>

                        <!-- Details Table -->
                        <div style=\"background-color: #F7FAFC; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #EDF2F7;\">
                            <h3 style=\"margin-top: 0; margin-bottom: 20px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #A37F52; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px;\">
                                Reservation Details
                            </h3>

                            <table style=\"width: 100%; border-collapse: collapse; font-size: 14px;\">
                                <tr style=\"border-bottom: 1px solid #E2E8F0;\">
                                    <td style=\"padding: 12px 0; color: #718096; font-weight: 600; width: 45%;\">Homestay Package</td>
                                    <td style=\"padding: 12px 0; color: #1A202C; font-weight: 700; text-align: right;\">$packageName</td>
                                </tr>
                                <tr style=\"border-bottom: 1px solid #E2E8F0;\">
                                    <td style=\"padding: 12px 0; color: #718096; font-weight: 600;\">Check-in Date</td>
                                    <td style=\"padding: 12px 0; color: #1A202C; font-weight: 700; text-align: right;\">$checkin</td>
                                </tr>
                                <tr style=\"border-bottom: 1px solid #E2E8F0;\">
                                    <td style=\"padding: 12px 0; color: #718096; font-weight: 600;\">Check-out Date</td>
                                    <td style=\"padding: 12px 0; color: #1A202C; font-weight: 700; text-align: right;\">$checkout</td>
                                </tr>
                                <tr style=\"border-bottom: 1px solid #E2E8F0;\">
                                    <td style=\"padding: 12px 0; color: #718096; font-weight: 600;\">Booking Status</td>
                                    <td style=\"padding: 12px 0; text-align: right;\">
                                        <span style=\"display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; $statusStyle\">
                                            $status
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 12px 0; color: #718096; font-weight: 600;\">Payment Status</td>
                                    <td style=\"padding: 12px 0; text-align: right;\">
                                        <span style=\"display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; $paymentStatusStyle\">
                                            $paymentStatus
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Amount Box -->
                        <div style=\"background: linear-gradient(135deg, #FFFDF5 0%, #FFF9E6 100%); border: 1px dashed #F3D9A2; border-radius: 12px; padding: 22px; text-align: center; margin-bottom: 35px;\">
                            <div style=\"font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #A37F52; font-weight: 800; margin-bottom: 5px;\">Total Amount to Pay</div>
                            <div style=\"font-size: 30px; font-weight: 800; color: #A37F52;\">RM $balanceFormatted</div>
                            <div style=\"font-size: 12px; color: #718096; margin-top: 5px;\">Please settle this balance in accordance with the reservation policy.</div>
                        </div>

                        <!-- CTA Button -->
                        $ctaSection
                    </div>

                    <!-- Footer -->
                    <div style=\"background-color: #F7FAFC; padding: 30px 25px; text-align: center; border-top: 1px solid #EDF2F7; font-size: 12px; color: #A0AEC0; line-height: 1.5;\">
                        <p style=\"margin: 0 0 8px; font-weight: 600;\">&copy; " . date('Y') . " EasyStay. All rights reserved.</p>
                        <p style=\"margin: 0; font-size: 11px;\">Need assistance? Contact us at <a href=\"mailto:easystaysupport@gmail.com\" style=\"color: #C5A880; text-decoration: none; font-weight: 600;\">easystaysupport@gmail.com</a></p>
                    </div>
                </div>
            </div>";
            
            $altBody = "Dear $toName,\n\nYour reservation details for Booking #$bookingId have been updated.\n\n" .
                       "Homestay Package: $packageName\n" .
                       "Check-in: $checkin\n" .
                       "Check-out: $checkout\n" .
                       "Status: $status\n" .
                       "Payment: $paymentStatus\n" .
                       "Total Amount to Pay: RM $balanceFormatted\n\n" .
                       "Please login to your dashboard to view full details.";
        } else {
            $mail->Subject = "Update on Booking #$bookingId";
            $finalBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background-color: #ffffff;'>
                <div style='background-color: #C5A880; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin:0; font-size: 24px;'>EasyStay Notification</h2>
                </div>
                <div style='padding: 30px; color: #333333; line-height: 1.6;'>
                    $emailContent
                </div>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #eeeeee;'>
                    <p style='margin:0;'>&copy; " . date('Y') . " EasyStay. All rights reserved.</p>
                    <p style='margin:10px 0 0;'>
                        <a href='http://localhost/easystay/login.php' style='background-color: #C5A880; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                            Login to Dashboard
                        </a>
                    </p>
                </div>
            </div>";
            $altBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailContent));
        }

        $mail->Body = $finalBody;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>