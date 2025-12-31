<?php
require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();


$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>👑 Admin Dashboard</h2>
    <hr>

    <div class="row">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $totalProducts; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="products.php" class="btn btn-primary">Manage Products</a>
        <a href="orders.php" class="btn btn-success">View Orders</a>
        <a href="users.php" class="btn btn-warning">Manage Users</a>
        <a href="../dashboard.php" class="btn btn-secondary">Back</a>
    </div>
</div>
</body>
</html>
