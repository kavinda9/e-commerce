<?php
require_once '../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Get user's orders with items
$stmt = $db->prepare("
    SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.payment_method,
        o.order_date,
        COUNT(oi.order_item_id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = :user_id
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");

$stmt->execute([':user_id' => $userId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .order-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 15px;
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
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box"></i> My Orders</h2>
            <span class="badge bg-primary"><?php echo count($orders); ?> total orders</span>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-5x text-muted mb-3"></i>
                <h3>No orders yet</h3>
                <p class="text-muted">Start shopping to see your orders here!</p>
                <a href="../products/index.php" class="btn btn-primary">
                    <i class="fas fa-box"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h5 class="mb-0">Order #<?php echo $order['order_id']; ?></h5>
                                <small class="text-muted">
                                    <?php echo date('M d, Y - h:i A', strtotime($order['order_date'])); ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Amount</strong><br>
                                <span class="text-primary h5">$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="col-md-3">
                                <strong>Status</strong><br>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'paid' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?> status-badge">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <button class="btn btn-sm btn-outline-primary" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#order-<?php echo $order['order_id']; ?>">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details (Collapsible) -->
                    <div class="collapse" id="order-<?php echo $order['order_id']; ?>">
                        <?php
                        // Get order items
                        $stmt = $db->prepare("
                            SELECT 
                                oi.*,
                                p.name as product_name,
                                p.description
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.product_id
                            WHERE oi.order_id = :order_id
                        ");
                        $stmt->execute([':order_id' => $order['order_id']]);
                        $items = $stmt->fetchAll();
                        ?>

                        <div class="mt-3">
                            <h6 class="mb-3"><i class="fas fa-list"></i> Order Items</h6>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                </td>
                                                <td>$<?php echo number_format($item['price_at_purchase'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?>x</td>
                                                <td>
                                                    <strong>$<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light">
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong class="text-primary">$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($order['payment_method']): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-credit-card"></i> 
                                        Payment Method: <?php echo htmlspecialchars($order['payment_method']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Order Summary -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo count($orders); ?></h3>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <?php
                            $totalSpent = array_sum(array_column($orders, 'total_amount'));
                            ?>
                            <h3 class="text-success">$<?php echo number_format($totalSpent, 2); ?></h3>
                            <p class="text-muted mb-0">Total Spent</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <?php
                            $deliveredOrders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
                            ?>
                            <h3 class="text-info"><?php echo $deliveredOrders; ?></h3>
                            <p class="text-muted mb-0">Delivered Orders</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>