<?php
require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>📦 Products</h2>
    <a href="index.php" class="btn btn-secondary mb-3">Back</a>

    <table class="table table-bordered">
        <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
        </tr>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>$<?php echo $p['price']; ?></td>
            <td><?php echo $p['stock_quantity']; ?></td>
            <td><?php echo $p['is_active'] ? 'Active' : 'Disabled'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
