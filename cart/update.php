<?php
/**
 * Update Cart Quantity
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    if ($cartId <= 0 || $quantity <= 0) {
        header('Location: index.php?error=invalid');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get cart item with product info
        $stmt = $db->prepare("
            SELECT sc.*, p.stock_quantity, p.name
            FROM shopping_cart sc
            JOIN products p ON sc.product_id = p.product_id
            WHERE sc.cart_id = :cart_id AND sc.user_id = :user_id
        ");
        
        $stmt->execute([
            ':cart_id' => $cartId,
            ':user_id' => $userId
        ]);
        
        $cartItem = $stmt->fetch();
        
        if (!$cartItem) {
            header('Location: index.php?error=notfound');
            exit;
        }
        
        // Check stock availability
        if ($quantity > $cartItem['stock_quantity']) {
            header('Location: index.php?error=stock');
            exit;
        }
        
        // Update quantity
        $stmt = $db->prepare("UPDATE shopping_cart SET quantity = :quantity WHERE cart_id = :cart_id");
        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cartId
        ]);
        
        logAudit($userId, 'CART_QUANTITY_UPDATED', 'cart', $cartId, "Updated quantity to $quantity for {$cartItem['name']}");
        
        header('Location: index.php?updated=1');
        exit;
        
    } catch (Exception $e) {
        error_log("Update cart error: " . $e->getMessage());
        header('Location: index.php?error=system');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>