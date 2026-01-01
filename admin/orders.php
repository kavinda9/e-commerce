<?php

require_once '../config/database.php';
requireLogin();


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();



$stmt = $db->query("
    SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at,
        u.first_name,
        u.last_name,
        u.email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.created_at DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2>🧾 Orders Management</h2>
    <p class="text-muted">View all customer orders</p>

    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['first_name'].' '.$order['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $order['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No orders found
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
