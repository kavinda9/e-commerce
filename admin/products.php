<?php
/**
 * Admin Product Management
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Handle product activation/deactivation
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    $currentStatus = (int)$_GET['toggle'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $stmt = $db->prepare("UPDATE products SET is_active = :status WHERE product_id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $productId]);
    
    logAudit($_SESSION['user_id'], 'PRODUCT_STATUS_CHANGED', 'product', $productId, "Status changed to: " . ($newStatus ? 'Active' : 'Inactive'));
    
    header('Location: products.php?updated=1');
    exit;
}

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');
$status = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

if ($status === 'active') {
    $query .= " AND is_active = 1";
} elseif ($status === 'inactive') {
    $query .= " AND is_active = 0";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .product-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .action-btn {
            margin: 2px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.85rem;
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
                <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="../dashboard.php"><i class="fas fa-store"></i> Store</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box"></i> Product Management</h2>
            <a href="product_add.php" class="btn btn-success btn-lg">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Product added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> Product updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-trash"></i> Product deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-search"></i> Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by name or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-tags"></i> Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-toggle-on"></i> Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($category) || $status !== 'all'): ?>
                <div class="mt-2">
                    <a href="products.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Table -->
        <div class="product-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-list"></i> Products List</h5>
                <span class="badge bg-primary"><?php echo count($products); ?> products</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">No products found</p>
                                    <a href="product_add.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add First Product
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong>#<?php echo $product['product_id']; ?></strong></td>
                                    <td>
                                        <?php if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 class="product-image-thumb" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image-thumb bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">$<?php echo number_format($product['price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($product['stock_quantity'] > 10): ?>
                                            <span class="badge bg-success status-badge">
                                                <?php echo $product['stock_quantity']; ?> units
                                            </span>
                                        <?php elseif ($product['stock_quantity'] > 0): ?>
                                            <span class="badge bg-warning status-badge">
                                                <?php echo $product['stock_quantity']; ?> units
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger status-badge">
                                                Out of stock
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge bg-success status-badge">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary status-badge">
                                                <i class="fas fa-times-circle"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="product_edit.php?id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-sm btn-primary action-btn" 
                                               title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $product['is_active']; ?>&id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-sm btn-<?php echo $product['is_active'] ? 'warning' : 'success'; ?> action-btn"
                                               onclick="return confirm('<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?> this product?')"
                                               title="<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo $product['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </a>
                                            <a href="product_delete.php?id=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-sm btn-danger action-btn"
                                               onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone!')"
                                               title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo count($products); ?></h3>
                        <p class="mb-0">Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <?php $activeCount = count(array_filter($products, fn($p) => $p['is_active'])); ?>
                        <h3 class="text-success"><?php echo $activeCount; ?></h3>
                        <p class="mb-0">Active Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <?php $lowStock = count(array_filter($products, fn($p) => $p['stock_quantity'] < 10 && $p['stock_quantity'] > 0)); ?>
                        <h3 class="text-warning"><?php echo $lowStock; ?></h3>
                        <p class="mb-0">Low Stock</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <?php $outOfStock = count(array_filter($products, fn($p) => $p['stock_quantity'] == 0)); ?>
                        <h3 class="text-danger"><?php echo $outOfStock; ?></h3>
                        <p class="mb-0">Out of Stock</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
