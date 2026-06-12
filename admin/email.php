<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan path autoload betul. 
require_once dirname(__DIR__) . '/vendor/autoload.php';

function sendBookingStatusEmail($toEmail, $toName, $emailContent, $bookingId) {
    $mail = new PHPMailer(true);

    try {
        // --- TETAPAN SERVER SMTP (GMAIL) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'easystay@gmail.com'; 
        $mail->Password   = 'zjcp zpxj swio gtbl'; // Gunakan App Password anda
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- PENGHANTAR & PENERIMA ---
        $mail->setFrom('easystay@gmail.com', 'EasyStay Admin');
        $mail->addAddress($toEmail, $toName);

        // --- KANDUNGAN EMEL ---
        $mail->isHTML(true);
        $mail->Subject = "Update on Booking #$bookingId";

        // Template HTML
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

        $mail->Body = $finalBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailContent));

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>