<?php
require_once '../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $db->prepare("
    SELECT 
        sc.cart_id,
        sc.quantity,
        p.product_id,
        p.name,
        p.price,
        p.stock_quantity,
        p.image_url,
        (p.price * sc.quantity) as subtotal
    FROM shopping_cart sc
    JOIN products p ON sc.product_id = p.product_id
    WHERE sc.user_id = :user_id AND p.is_active = 1
");

$stmt->execute([':user_id' => $userId]);
$cartItems = $stmt->fetchAll();

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: ../cart/index.php?error=empty');
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['subtotal'];
}
$shipping = 0; // Free shipping for demo
$tax = $subtotal * 0.10; // 10% tax
$total = $subtotal + $shipping + $tax;

// Get user info for pre-filling
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch();

$error = '';
$stockError = false;

// Check stock availability
foreach ($cartItems as $item) {
    if ($item['quantity'] > $item['stock_quantity']) {
        $stockError = true;
        $error = "Some items in your cart are out of stock or have insufficient quantity.";
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .checkout-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .payment-method input[type="radio"]:checked + label {
            border-color: #667eea;
            background: #f0f3ff;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 0 50px;
        }
        .step {
            text-align: center;
            position: relative;
            flex: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .step.completed .step-number {
            background: #28a745;
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
                <a class="nav-link" href="../cart/index.php">
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
            </div>
        </div>
    </nav>

    <div class="checkout-container mt-4 px-3">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div>Cart</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div>Checkout</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div>Confirmation</div>
            </div>
        </div>

        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h3 class="mb-4"><i class="fas fa-shipping-fast"></i> Shipping Information</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="place_order.php" method="POST" id="checkoutForm">
                        <!-- Contact Information -->
                        <div class="mb-4">
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                           required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="mb-4">
                            <h5 class="mb-3">Shipping Address</h5>
                            <div class="mb-3">
                                <label for="address" class="form-label">Street Address *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="address" 
                                       name="address" 
                                       placeholder="123 Main Street"
                                       required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="city" 
                                           name="city" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="state" class="form-label">State/Province *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="state" 
                                           name="state" 
                                           required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="zip" class="form-label">ZIP/Postal Code *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="zip" 
                                           name="zip" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="country" class="form-label">Country *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="country" 
                                           name="country" 
                                           value="United States"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <h5 class="mb-3">Payment Method</h5>
                            <p class="text-muted small">
                                <i class="fas fa-info-circle"></i> This is a demo. No actual payment will be processed.
                            </p>

                            <div class="payment-method">
                                <input type="radio" 
                                       class="form-check-input" 
                                       id="payment_cod" 
                                       name="payment_method" 
                                       value="Cash on Delivery"
                                       checked>
                                <label class="form-check-label ms-2" for="payment_cod">
                                    <i class="fas fa-money-bill-wave text-success"></i>
                                    <strong>Cash on Delivery</strong>
                                    <p class="mb-0 small text-muted">Pay when you receive your order</p>
                                </label>
                            </div>

                            <div class="payment-method">
                                <input type="radio" 
                                       class="form-check-input" 
                                       id="payment_card" 
                                       name="payment_method" 
                                       value="Credit/Debit Card">
                                <label class="form-check-label ms-2" for="payment_card">
                                    <i class="fas fa-credit-card text-primary"></i>
                                    <strong>Credit/Debit Card</strong>
                                    <p class="mb-0 small text-muted">Visa, MasterCard, American Express (Demo)</p>
                                </label>
                            </div>

                            <div class="payment-method">
                                <input type="radio" 
                                       class="form-check-input" 
                                       id="payment_paypal" 
                                       name="payment_method" 
                                       value="PayPal">
                                <label class="form-check-label ms-2" for="payment_paypal">
                                    <i class="fab fa-paypal text-info"></i>
                                    <strong>PayPal</strong>
                                    <p class="mb-0 small text-muted">Pay with your PayPal account (Demo)</p>
                                </label>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Any special instructions for your order?"></textarea>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="terms" 
                                   required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> *
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" 
                                    class="btn btn-primary btn-lg" 
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;"
                                    <?php echo $stockError ? 'disabled' : ''; ?>>
                                <i class="fas fa-lock"></i> Place Order - $<?php echo number_format($total, 2); ?>
                            </button>
                        </div>

                        <?php if ($stockError): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Please update your cart before proceeding.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="order-summary">
                    <h4 class="mb-4"><i class="fas fa-receipt"></i> Order Summary</h4>

                    <!-- Cart Items -->
                    <div class="mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-shrink-0" style="width: 60px; height: 60px;">
                                    <?php if (!empty($item['image_url']) && file_exists('../' . $item['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                </div>
                                <div class="fw-bold">
                                    $<?php echo number_format($item['subtotal'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span class="text-success">FREE</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (10%):</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="h5">Total:</strong>
                            <strong class="h5 text-primary">$<?php echo number_format($total, 2); ?></strong>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="alert alert-success">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Secure Checkout</strong><br>
                        <small>Your information is protected with 256-bit SSL encryption</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Agreement to Terms</h6>
                    <p>By placing an order, you agree to these terms and conditions.</p>
                    
                    <h6>2. Product Information</h6>
                    <p>We strive to provide accurate product information. However, we do not warrant that product descriptions are accurate, complete, or error-free.</p>
                    
                    <h6>3. Pricing</h6>
                    <p>All prices are in USD. We reserve the right to change prices without notice.</p>
                    
                    <h6>4. Payment (Demo)</h6>
                    <p>This is a demonstration e-commerce platform. No real payments are processed.</p>
                    
                    <h6>5. Shipping</h6>
                    <p>Estimated delivery times are provided for demonstration purposes.</p>
                    
                    <h6>6. Returns</h6>
                    <p>Return policy details would be specified here in a production environment.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>