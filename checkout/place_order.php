<?php
require_once '../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Collect and sanitize form data
$firstName = sanitizeInput($_POST['first_name'] ?? '');
$lastName = sanitizeInput($_POST['last_name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$city = sanitizeInput($_POST['city'] ?? '');
$state = sanitizeInput($_POST['state'] ?? '');
$zip = sanitizeInput($_POST['zip'] ?? '');
$country = sanitizeInput($_POST['country'] ?? '');
$paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'Cash on Delivery');
$notes = sanitizeInput($_POST['notes'] ?? '');

// Validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
    empty($address) || empty($city) || empty($state) || empty($zip) || empty($country)) {
    header('Location: index.php?error=incomplete');
    exit;
}

// Build shipping address
$shippingAddress = "$address, $city, $state $zip, $country\nPhone: $phone";
if (!empty($notes)) {
    $shippingAddress .= "\nNotes: $notes";
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get cart items with stock check
    $stmt = $db->prepare("
        SELECT 
            sc.cart_id,
            sc.quantity,
            p.product_id,
            p.name,
            p.price,
            p.stock_quantity
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.product_id
        WHERE sc.user_id = :user_id AND p.is_active = 1
        FOR UPDATE
    ");
    
    $stmt->execute([':user_id' => $userId]);
    $cartItems = $stmt->fetchAll();
    
    // Check if cart is empty
    if (empty($cartItems)) {
        $db->rollBack();
        header('Location: ../cart/index.php?error=empty');
        exit;
    }
    
    // Validate stock and calculate total
    $totalAmount = 0;
    $stockErrors = [];
    
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $stockErrors[] = $item['name'];
        }
        $totalAmount += $item['price'] * $item['quantity'];
    }
    
    if (!empty($stockErrors)) {
        $db->rollBack();
        $errorMsg = urlencode('Insufficient stock for: ' . implode(', ', $stockErrors));
        header('Location: index.php?error=stock&msg=' . $errorMsg);
        exit;
    }
    
    // Add tax (10%)
    $tax = $totalAmount * 0.10;
    $totalAmount += $tax;
    
    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_address)
        VALUES (:user_id, :total_amount, :status, :payment_method, :shipping_address)
    ");
    
    $orderStatus = ($paymentMethod === 'Cash on Delivery') ? 'pending' : 'paid';
    
    $stmt->execute([
        ':user_id' => $userId,
        ':total_amount' => $totalAmount,
        ':status' => $orderStatus,
        ':payment_method' => $paymentMethod,
        ':shipping_address' => $shippingAddress
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Create order items and update stock
    foreach ($cartItems as $item) {
        // Insert order item
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
            VALUES (:order_id, :product_id, :quantity, :price)
        ");
        
        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
        
        // Update product stock
        $stmt = $db->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - :quantity 
            WHERE product_id = :product_id
        ");
        
        $stmt->execute([
            ':quantity' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }
    
    // Create payment transaction record
    $stmt = $db->prepare("
        INSERT INTO payment_transactions (order_id, amount, status, payment_gateway_ref)
        VALUES (:order_id, :amount, :status, :gateway_ref)
    ");
    
    $transactionStatus = ($paymentMethod === 'Cash on Delivery') ? 'pending' : 'completed';
    $gatewayRef = 'DEMO-' . strtoupper(uniqid());
    
    $stmt->execute([
        ':order_id' => $orderId,
        ':amount' => $totalAmount,
        ':status' => $transactionStatus,
        ':gateway_ref' => $gatewayRef
    ]);
    
    // Clear shopping cart
    $stmt = $db->prepare("DELETE FROM shopping_cart WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    
    // Log the order
    logAudit($userId, 'ORDER_PLACED', 'order', $orderId, "Order placed. Total: $$totalAmount, Payment: $paymentMethod");
    
    // Commit transaction
    $db->commit();
    
    // Redirect to confirmation page
    header('Location: order_confirmation.php?order_id=' . $orderId);
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    error_log("Order placement error: " . $e->getMessage());
    header('Location: index.php?error=system');
    exit;
}
?>