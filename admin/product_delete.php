<?php
require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: products.php?error=invalid');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Get product info before deleting
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php?error=notfound');
        exit;
    }
    
    // Check if product has orders (optional - decide if you want to prevent deletion)
    $stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :id");
    $stmt->execute([':id' => $productId]);
    $orderCount = $stmt->fetchColumn();
    
    if ($orderCount > 0) {
        // Product has orders - use soft delete (deactivate) instead
        $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE product_id = :id");
        $stmt->execute([':id' => $productId]);
        
        logAudit($_SESSION['user_id'], 'PRODUCT_DEACTIVATED', 'product', $productId, "Product deactivated (has {$orderCount} orders): {$product['name']}");
        
        header('Location: products.php?updated=1&msg=deactivated');
        exit;
    }
    
    // Delete product image if exists
    if (!empty($product['image_url'])) {
        $uploadDir = realpath('../assets/images/products/');
        $imagePath = '../' . $product['image_url'];
        $realImagePath = realpath($imagePath);
        
        // Validate path is within allowed directory
        if ($realImagePath && strpos($realImagePath, $uploadDir) === 0 && file_exists($realImagePath)) {
            unlink($realImagePath);
        }
    }
    
    // Delete product from cart items first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM shopping_cart WHERE product_id = :id");
    $stmt->execute([':id' => $productId]);
    
    // Delete product
    $stmt = $db->prepare("DELETE FROM products WHERE product_id = :id");
    $stmt->execute([':id' => $productId]);
    
    logAudit($_SESSION['user_id'], 'PRODUCT_DELETED', 'product', $productId, "Deleted product: {$product['name']}");
    
    header('Location: products.php?deleted=1');
    exit;
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    header('Location: products.php?error=delete_failed');
    exit;
}
?>