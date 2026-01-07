<?php
/**
 * Admin Orders Management - Enhanced
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Handle order status update
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitizeInput($_POST['status']);
    
    $stmt = $db->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id");
    $stmt->execute([':status' => $newStatus, ':order_id' => $orderId]);
    
    logAudit($_SESSION['user_id'], 'ORDER_STATUS_UPDATED', 'order', $orderId, "Status changed to: $newStatus");
    
    header('Location: orders.php?updated=1');
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = sanitizeInput($_GET['search'] ?? '');

// Build query
$query = "
    SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.payment_method,
        o.shipping_address,
        o.order_date,
        o.updated_at,
        u.first_name,
        u.last_name,
        u.email,
        COUNT(oi.order_item_id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($searchQuery)) {
    $query .= " AND (o.order_id = :search OR u.first_name LIKE :search_like OR u.last_name LIKE :search_like OR u.email LIKE :search_like)";
    $params[':search'] = $searchQuery;
    $params[':search_like'] = "%$searchQuery%";
}

$query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'paid' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'shipped' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn(),
    'delivered' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
    'cancelled' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
    'revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        .orders-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-crown"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="../dashboard.php"><i class="fas fa-store"></i> Store</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Orders Management</h2>

        <!-- Success Message -->
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Order updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                    <p class="mb-0 small">Total Orders</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-warning"><?php echo $stats['pending']; ?></h3>
                    <p class="mb-0 small">Pending</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-info"><?php echo $stats['paid']; ?></h3>
                    <p class="mb-0 small">Paid</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-primary"><?php echo $stats['shipped']; ?></h3>
                    <p class="mb-0 small">Shipped</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-success"><?php echo $stats['delivered']; ?></h3>
                    <p class="mb-0 small">Delivered</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3 class="text-danger"><?php echo $stats['cancelled']; ?></h3>
                    <p class="mb-0 small">Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card">
                    <h2 class="text-success mb-0">$<?php echo number_format($stats['revenue'], 2); ?></h2>
                    <p class="mb-0 text-muted">Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-search"></i> Search</label>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Order ID, Customer name, or email..."
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-filter"></i> Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <?php if (!empty($searchQuery) || $statusFilter !== 'all'): ?>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="orders.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="orders-card">
            <h5 class="mb-3"><i class="fas fa-list"></i> All Orders (<?php echo count($orders); ?>)</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $order['item_count']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($order['payment_method']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'paid' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = $statusColors[$order['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?> status-badge">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($order['order_date'])); ?></small>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#orderModal<?php echo $order['order_id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Order Details Modal -->
                                <div class="modal fade" id="orderModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Order #<?php echo $order['order_id']; ?> Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Customer Info -->
                                                <h6 class="border-bottom pb-2 mb-3">Customer Information</h6>
                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <strong>Name:</strong><br>
                                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Email:</strong><br>
                                                        <?php echo htmlspecialchars($order['email']); ?>
                                                    </div>
                                                </div>

                                                <!-- Order Items -->
                                                <h6 class="border-bottom pb-2 mb-3">Order Items</h6>
                                                <?php
                                                $itemsStmt = $db->prepare("
                                                    SELECT oi.*, p.name, p.image_url
                                                    FROM order_items oi
                                                    JOIN products p ON oi.product_id = p.product_id
                                                    WHERE oi.order_id = :order_id
                                                ");
                                                $itemsStmt->execute([':order_id' => $order['order_id']]);
                                                $items = $itemsStmt->fetchAll();
                                                ?>
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Price</th>
                                                            <th>Qty</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                                <td>$<?php echo number_format($item['price_at_purchase'], 2); ?></td>
                                                                <td><?php echo $item['quantity']; ?></td>
                                                                <td>$<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr class="table-light">
                                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <!-- Shipping Address -->
                                                <h6 class="border-bottom pb-2 mb-3 mt-4">Shipping Address</h6>
                                                <address><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></address>

                                                <!-- Order Details -->
                                                <h6 class="border-bottom pb-2 mb-3 mt-4">Order Information</h6>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <strong>Payment Method:</strong><br>
                                                        <?php echo htmlspecialchars($order['payment_method']); ?>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Order Date:</strong><br>
                                                        <?php echo date('M d, Y - h:i A', strtotime($order['order_date'])); ?>
                                                    </div>
                                                </div>

                                                <!-- Update Status -->
                                                <h6 class="border-bottom pb-2 mb-3 mt-4">Update Status</h6>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <div class="row">
                                                        <div class="col-8">
                                                            <select class="form-select" name="status">
                                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-4">
                                                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                                                <i class="fas fa-save"></i> Update
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
