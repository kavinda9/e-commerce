<?php
/**
 * Shopping Cart
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Get cart items with product details
$stmt = $db->prepare("
    SELECT 
        sc.cart_id,
        sc.quantity,
        sc.added_at,
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image_url,
        (p.price * sc.quantity) as subtotal
    FROM shopping_cart sc
    JOIN products p ON sc.product_id = p.product_id
    WHERE sc.user_id = :user_id AND p.is_active = 1
    ORDER BY sc.added_at DESC
");

$stmt->execute([':user_id' => $userId]);
$cartItems = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['subtotal'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .cart-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        .cart-summary {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-shopping-cart"></i> Secure E-Commerce
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-shopping-bag"></i> Shopping Cart</h2>
        <hr>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Product added to cart successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> Cart updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['removed'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-trash"></i> Item removed from cart!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <?php if (empty($cartItems)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted">Start adding products to your cart!</p>
                        <a href="../products/index.php" class="btn btn-primary">
                            <i class="fas fa-box"></i> Browse Products
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="product-image">
                                        <i class="fas fa-box fa-3x text-muted"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 60)) . '...'; ?>
                                    </p>
                                    <span class="badge bg-success mt-2">
                                        <?php echo $item['stock_quantity']; ?> in stock
                                    </span>
                                </div>
                                
                                <div class="col-md-2">
                                    <p class="mb-0"><strong>$<?php echo number_format($item['price'], 2); ?></strong></p>
                                    <small class="text-muted">per item</small>
                                </div>
                                
                                <div class="col-md-2">
                                    <form action="update.php" method="POST" class="d-inline">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <input 
                                            type="number" 
                                            name="quantity" 
                                            class="form-control quantity-input" 
                                            value="<?php echo $item['quantity']; ?>" 
                                            min="1" 
                                            max="<?php echo $item['stock_quantity']; ?>"
                                            onchange="this.form.submit()"
                                        >
                                    </form>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-primary">
                                            $<?php echo number_format($item['subtotal'], 2); ?>
                                        </strong>
                                        <form action="remove.php" method="POST" class="d-inline">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Remove this item from cart?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($cartItems)): ?>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="mb-4"><i class="fas fa-receipt"></i> Order Summary</h4>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items (<?php echo count($cartItems); ?>):</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span class="text-success">FREE</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total:</strong>
                        <strong class="text-primary h4">$<?php echo number_format($total, 2); ?></strong>
                    </div>
                    
                    <button class="btn btn-primary w-100 mb-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </button>
                    
                    <a href="../products/index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt"></i> 
                            <strong>Secure Checkout</strong><br>
                            Your payment information is protected with industry-standard encryption.
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>