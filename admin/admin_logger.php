<?php
/**
 * EasyStay Admin Activity Logger
 * Rekod setiap tindakan admin ke dalam table admin_logs
 */

/**
 * Log tindakan admin
 * 
 * @param mysqli $conn       Database connection
 * @param int    $admin_id   ID admin yang melakukan tindakan
 * @param string $action     Jenis tindakan (e.g., 'UPDATE_BOOKING', 'DELETE_USER')
 * @param string $description Penerangan ringkas tindakan
 * @param int    $target_id  ID rekod yang terlibat (optional)
 * @param string $target_type Jenis rekod (e.g., 'booking', 'user', 'package')
 */
function logAdminAction($conn, $admin_id, $action, $description, $target_id = null, $target_type = null) {
    $stmt = $conn->prepare(
        "INSERT INTO admin_logs (admin_id, action, description, target_id, target_type) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issis", $admin_id, $action, $description, $target_id, $target_type);
    $stmt->execute();
}
?>
