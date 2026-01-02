<?php
/**
 * Add to Cart
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    if ($productId <= 0 || $quantity <= 0) {
        header('Location: ../products/index.php?error=invalid');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if product exists and is active
        $stmt = $db->prepare("SELECT * FROM products WHERE product_id = :product_id AND is_active = 1");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header('Location: ../products/index.php?error=notfound');
            exit;
        }
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            header('Location: ../products/details.php?id=' . $productId . '&error=stock');
            exit;
        }
        
        // Check if product already in cart
        $stmt = $db->prepare("SELECT * FROM shopping_cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
        
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            if ($newQuantity > $product['stock_quantity']) {
                header('Location: ../products/details.php?id=' . $productId . '&error=stock');
                exit;
            }
            
            $stmt = $db->prepare("UPDATE shopping_cart SET quantity = :quantity WHERE cart_id = :cart_id");
            $stmt->execute([
                ':quantity' => $newQuantity,
                ':cart_id' => $existingItem['cart_id']
            ]);
            
            logAudit($userId, 'CART_UPDATED', 'cart', $existingItem['cart_id'], "Updated quantity to $newQuantity");
        } else {
            // Add new item
            $stmt = $db->prepare("INSERT INTO shopping_cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $stmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId,
                ':quantity' => $quantity
            ]);
            
            logAudit($userId, 'CART_ITEM_ADDED', 'cart', $db->lastInsertId(), "Added product: {$product['name']}");
        }
        
        header('Location: index.php?added=1');
        exit;
        
    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
        header('Location: ../products/index.php?error=system');
        exit;
    }
} else {
    header('Location: ../products/index.php');
    exit;
}
?>