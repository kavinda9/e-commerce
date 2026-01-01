<?php
require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$users = $db->query("SELECT user_id, first_name, last_name, email, role, is_active FROM users")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>👤 Users</h2>
    <a href="index.php" class="btn btn-secondary mb-3">Back</a>

    <table class="table table-hover">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo $u['first_name'].' '.$u['last_name']; ?></td>
            <td><?php echo $u['email']; ?></td>
            <td><?php echo ucfirst($u['role']); ?></td>
            <td><?php echo $u['is_active'] ? 'Active' : 'Disabled'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
