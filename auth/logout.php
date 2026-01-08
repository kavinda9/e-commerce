<?php
require_once '../config/database.php';

startSecureSession();

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    logAudit($_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id'], 'User logged out');
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
?>