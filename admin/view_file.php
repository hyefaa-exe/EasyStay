<?php 
$book_id = $_GET['file'] ?? null;

if ($book_id === null) {
    echo "No book ID provided.";
    exit;
}

require 'db_connect.php'; // Pastikan db_connect.php ada dalam folder admin juga

$stmt = $conn->prepare("SELECT receipt_path FROM bookings WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking || empty($booking['receipt_path'])) {
    echo "Booking or receipt not found.";
    exit;
}

$hashFilePath = $booking['receipt_path'];

//--- PEMBETULAN PATH DI SINI ---
// Kita andaikan file ini dalam folder 'admin'
// Kita perlu keluar (../) untuk ke folder 'uploads/receipts' yang di root
$full_path = '../uploads/receipts/' . $hashFilePath; 

if (file_exists($full_path)) {
    $mimeType = mime_content_type($full_path);

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
    header('Content-Length: ' . filesize($full_path));
    
    // Bersihkan buffer untuk elak data sampah (corrupted image)
    ob_clean();
    flush();
    
    readfile($full_path);
    exit;
} else {
    // Debug: Paparkan path yang dicari jika gagal
    echo "File not found at: " . realpath($full_path); 
    exit;
}
?>