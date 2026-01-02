<?php
/**
 * Remove from Cart
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if ($cartId <= 0) {
        header('Location: index.php?error=invalid');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Verify cart item belongs to user
        $stmt = $db->prepare("SELECT * FROM shopping_cart WHERE cart_id = :cart_id AND user_id = :user_id");
        $stmt->execute([
            ':cart_id' => $cartId,
            ':user_id' => $userId
        ]);
        
        $cartItem = $stmt->fetch();
        
        if (!$cartItem) {
            header('Location: index.php?error=notfound');
            exit;
        }
        
        // Delete cart item
        $stmt = $db->prepare("DELETE FROM shopping_cart WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cartId]);
        
        logAudit($userId, 'CART_ITEM_REMOVED', 'cart', $cartId, "Removed cart item");
        
        header('Location: index.php?removed=1');
        exit;
        
    } catch (Exception $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        header('Location: index.php?error=system');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>