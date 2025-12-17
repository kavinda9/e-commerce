<?php
/**
 * Logout Handler
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
startSecureSession();

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance()->getConnection();
    $sessionId = session_id();
    
    // Delete session from database
    $stmt = $db->prepare("DELETE FROM sessions WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    
    // Audit log
    logAudit($_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id']);
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit;
?>