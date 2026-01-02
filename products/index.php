<?php
/**
 * Products Listing Page
 * Group 9 - Secure E-Commerce System
 */

require_once '../config/database.php';
startSecureSession();

$db = Database::getInstance()->getConnection();

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');
$sort = sanitizeInput($_GET['sort'] ?? 'newest');

// Build query
$query = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

// Sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories
$categories = $db->query("SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
        .filter-sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-shopping-cart"></i> Secure E-Commerce
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="../dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="../cart/index.php">
                        <i class="fas fa-shopping-bag"></i> Cart
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="../auth/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box-open"></i> All Products</h2>
            <span class="badge bg-primary"><?php echo count($products); ?> products found</span>
        </div>

        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filters</h5>
                    
                    <form method="GET" action="">
                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="search" 
                                placeholder="Search products..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label">Category</label>
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

                        <!-- Sort -->
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        
                        <?php if (!empty($search) || !empty($category) || $sort !== 'newest'): ?>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your filters</p>
                        <a href="index.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4">
                                <div class="card product-card">
                                    <div class="product-image">
                                        <i class="fas fa-box fa-4x text-muted"></i>
                                    </div>
                                    <div class="card-body">
                                        <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($product['category'] ?? 'General'); ?></span>
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="price-tag">$<?php echo number_format($product['price'], 2); ?></span>
                                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                            
                                            <?php if (isLoggedIn()): ?>
                                                <form action="../cart/add.php" method="POST">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                    <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="../auth/login.php" class="btn btn-primary w-100">
                                                    <i class="fas fa-sign-in-alt"></i> Login to Purchase
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>