<?php
require_once 'config/database.php';
requireLogin(); // Check if user is logged in

$db = Database::getInstance()->getConnection();

// Get current user info
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Get products
$stmt = $db->query("SELECT * FROM products WHERE is_active = TRUE ORDER BY created_at DESC LIMIT 8");
$products = $stmt->fetchAll();

// Handle logout
if (isset($_GET['logout'])) {
    logAudit($_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id']);
    session_unset();
    session_destroy();
    header('Location: auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .btn-add-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-add-cart:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shopping-cart"></i> Secure E-Commerce
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products/index.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart/index.php">
                            <i class="fas fa-shopping-bag"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user/profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="user/orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><i class="fas fa-hand-wave"></i> Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <p class="mb-0">Browse our secure collection of products. Your data is protected with industry-standard security measures.</p>
            <?php if ($user['role'] === 'admin'): ?>
                <div class="mt-3">
                    <span class="badge bg-light text-dark"><i class="fas fa-shield-alt"></i> Admin Access Enabled</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-box fa-3x text-primary mb-2"></i>
                        <h3><?php echo count($products); ?>+</h3>
                        <p class="text-muted">Products Available</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x text-success mb-2"></i>
                        <h3>100%</h3>
                        <p class="text-muted">Secure Transactions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-truck fa-3x text-warning mb-2"></i>
                        <h3>Fast</h3>
                        <p class="text-muted">Delivery Service</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box-open"></i> Featured Products</h2>
            <a href="products/index.php" class="btn btn-primary">View All</a>
        </div>

        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-3">
                    <div class="card product-card">
                        <div class="product-image">
                            <?php 
                                $imagePath = 'assets/images/products/' . $product['name'] . '.png';
                                if (file_exists($imagePath)): 
                            ?>
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-box fa-4x text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="price-tag">$<?php echo number_format($product['price'], 2); ?></span>
                                <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                            </div>
                            <form action="cart/add.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <button type="submit" class="btn btn-add-cart w-100">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h3>No products available</h3>
                <p class="text-muted">Check back later for new arrivals!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-dark text-white text-center">
        <div class="container">
            <p class="mb-0">
                <i class="fas fa-shield-alt"></i> Secure E-Commerce System<br>
                <small class="text-muted">Protected by advanced security measures | All data encrypted</small>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>