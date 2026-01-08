<?php
require_once '../config/database.php';
requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Get order details
$stmt = $db->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
");

$stmt->execute([
    ':order_id' => $orderId,
    ':user_id' => $userId
]);

$order = $stmt->fetch();

if (!$order) {
    header('Location: ../dashboard.php?error=notfound');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = :order_id
");

$stmt->execute([':order_id' => $orderId]);
$orderItems = $stmt->fetchAll();

// Calculate estimated delivery date (7-10 business days)
$estimatedDelivery = date('M d, Y', strtotime('+10 days', strtotime($order['created_at'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .confirmation-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        .order-summary-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -17px;
            top: 20px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e0e0e0;
        }
        .timeline-item:last-child::after {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .navbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-shopping-cart"></i> Secure E-Commerce
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="../user/orders.php">
                    <i class="fas fa-box"></i> My Orders
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="confirmation-card">
            <!-- Success Icon -->
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>

            <!-- Success Message -->
            <div class="text-center mb-4">
                <h2 class="text-success mb-2">Order Placed Successfully!</h2>
                <p class="text-muted">Thank you for your order. We've sent a confirmation email to <strong><?php echo htmlspecialchars($order['email']); ?></strong></p>
            </div>

            <!-- Order Number -->
            <div class="alert alert-info text-center">
                <h4 class="mb-0">
                    Order Number: <strong>#<?php echo $order['order_id']; ?></strong>
                </h4>
            </div>

            <!-- Order Details -->
            <div class="order-summary-box">
                <h5 class="mb-3"><i class="fas fa-receipt"></i> Order Details</h5>
                
                <div class="row mb-2">
                    <div class="col-6"><strong>Order Date:</strong></div>
                    <div class="col-6 text-end"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6"><strong>Payment Method:</strong></div>
                    <div class="col-6 text-end"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6"><strong>Order Status:</strong></div>
                    <div class="col-6 text-end">
                        <span class="badge bg-<?php echo $order['status'] === 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-6"><strong>Total Amount:</strong></div>
                    <div class="col-6 text-end">
                        <h5 class="mb-0 text-primary">$<?php echo number_format($order['total_amount'], 2); ?></h5>
                    </div>
                </div>
            </div>

            <!-- Items Ordered -->
            <div class="mt-4">
                <h5 class="mb-3"><i class="fas fa-box"></i> Items Ordered</h5>
                <?php foreach ($orderItems as $item): ?>
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
                            <small class="text-muted">Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price_at_purchase'], 2); ?></small>
                        </div>
                        <div class="fw-bold">
                            $<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Shipping Address -->
            <div class="order-summary-box mt-4">
                <h5 class="mb-3"><i class="fas fa-shipping-fast"></i> Shipping Address</h5>
                <address class="mb-0">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </address>
            </div>

            <!-- Order Timeline -->
            <div class="mt-4">
                <h5 class="mb-3"><i class="fas fa-clock"></i> Order Timeline</h5>
                <div class="timeline">
                    <div class="timeline-item">
                        <strong>Order Placed</strong><br>
                        <small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></small>
                    </div>
                    <?php if ($order['status'] === 'paid'): ?>
                    <div class="timeline-item">
                        <strong>Payment Confirmed</strong><br>
                        <small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></small>
                    </div>
                    <?php endif; ?>
                    <div class="timeline-item">
                        <strong>Processing</strong><br>
                        <small class="text-muted">We're preparing your order</small>
                    </div>
                    <div class="timeline-item">
                        <strong>Estimated Delivery</strong><br>
                        <small class="text-muted"><?php echo $estimatedDelivery; ?></small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-center no-print">
                <a href="../user/orders.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Orders
                </a>
                <a href="../products/index.php" class="btn btn-outline-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>

            <!-- Support Info -->
            <div class="alert alert-light mt-4 text-center">
                <small>
                    <i class="fas fa-headset"></i> 
                    Need help? Contact our support team or check your <a href="../user/orders.php">order history</a>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>