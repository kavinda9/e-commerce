<?php
require_once '../config/database.php';


startSecureSession();

// Check if user is logged in
requireLogin();
//my
// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
//thi part
// Get dashboard statistics
try {
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // Get recent orders
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $todayOrders = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $totalUsers = $totalProducts = $totalOrders = $todayOrders = 0;
}

// Log admin access
logAudit($_SESSION['user_id'], 'ACCESS_ADMIN_DASHBOARD', 'admin', null, 'Admin dashboard accessed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            transition: transform 0.3s;
            border-left: 4px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .admin-btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-crown"></i> Admin Dashboard</h2>
                <div>
                    <span class="me-3">
                        <i class="fas fa-user-shield"></i> 
                        <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?>
                    </span>
                    <a href="../dashboard.php" class="btn btn-light btn-sm">
                        <i class="fas fa-home"></i> Back to Shop
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Users</h6>
                                <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                            </div>
                            <i class="fas fa-users stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #28a745;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Products</h6>
                                <h2 class="mb-0"><?php echo $totalProducts; ?></h2>
                            </div>
                            <i class="fas fa-box stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h2 class="mb-0"><?php echo $totalOrders; ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card" style="border-left-color: #17a2b8;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Today's Orders</h6>
                                <h2 class="mb-0"><?php echo $todayOrders; ?></h2>
                            </div>
                            <i class="fas fa-calendar-day stat-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="products.php" class="btn btn-primary admin-btn">
                    <i class="fas fa-box"></i> Manage Products
                </a>
                <a href="orders.php" class="btn btn-success admin-btn">
                    <i class="fas fa-shopping-cart"></i> View Orders
                </a>
                <a href="users.php" class="btn btn-warning admin-btn">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="../dashboard.php" class="btn btn-secondary admin-btn">
                    <i class="fas fa-home"></i> Back to Shop
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock"></i> System Status</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> All systems operational
                </div>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-database"></i> Database Connection</span>
                        <span class="badge bg-success rounded-pill">Connected</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-lock"></i> Security Status</span>
                        <span class="badge bg-success rounded-pill">Secure</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-server"></i> Server Status</span>
                        <span class="badge bg-success rounded-pill">Running</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-3 bg-dark text-white text-center">
        <div class="container">
            <p class="mb-0">
                <i class="fas fa-shield-alt"></i> Admin Panel - Secure E-Commerce System
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
