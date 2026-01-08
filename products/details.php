<?php
require_once __DIR__ . '/../config/database.php';
startSecureSession();

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get product details
$stmt = $db->prepare("SELECT * FROM products WHERE product_id = :product_id AND is_active = 1");
$stmt->execute([':product_id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php?error=notfound');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Secure E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .product-image-large {
            height: 400px;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .product-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .price-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .quantity-selector {
            width: 100px;
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
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="../dashboard.php">Dashboard</a>
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
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php
                $errorMsg = match($_GET['error']) {
                    'stock' => 'Insufficient stock available',
                    'invalid' => 'Invalid request',
                    default => 'An error occurred'
                };
                ?>
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
                <div class="product-image-large">
                    <?php 
                        $imagePath = '../assets/images/products/' . $product['name'] . '.png';
                        if (file_exists($imagePath)): 
                    ?>
                        <img src="<?php echo $imagePath; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;">
                    <?php else: ?>
                        <i class="fas fa-box fa-10x text-muted"></i>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-lg-6 mb-4">
                <div class="product-details">
                    <!-- Category Badge -->
                    <?php if ($product['category']): ?>
                        <span class="badge bg-secondary mb-2">
                            <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                    <?php endif; ?>

                    <!-- Product Name -->
                    <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <!-- Price -->
                    <div class="price-display mb-4">
                        $<?php echo number_format($product['price'], 2); ?>
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-4">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle"></i> 
                                <?php echo $product['stock_quantity']; ?> in stock
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-6">
                                <i class="fas fa-times-circle"></i> Out of stock
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h5><i class="fas fa-info-circle"></i> Description</h5>
                        <p class="text-muted">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                    </div>

                    <!-- Product Details -->
                    <div class="mb-4">
                        <h5><i class="fas fa-list"></i> Product Details</h5>
                        <ul class="list-unstyled">
                            <li><strong>Product ID:</strong> #<?php echo $product['product_id']; ?></li>
                            <li><strong>Category:</strong> <?php echo htmlspecialchars($product['category'] ?? 'General'); ?></li>
                            <li><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></li>
                            <li><strong>Available Stock:</strong> <?php echo $product['stock_quantity']; ?> units</li>
                        </ul>
                    </div>

                    <hr>

                    <!-- Add to Cart Form -->
                    <?php if (isLoggedIn()): ?>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <form action="../cart/add.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                
                                <div class="row align-items-center mb-3">
                                    <div class="col-auto">
                                        <label for="quantity" class="form-label">Quantity:</label>
                                    </div>
                                    <div class="col-auto">
                                        <input 
                                            type="number" 
                                            class="form-control quantity-selector" 
                                            id="quantity" 
                                            name="quantity" 
                                            value="1" 
                                            min="1" 
                                            max="<?php echo $product['stock_quantity']; ?>"
                                        >
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" 
                                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Continue Shopping
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                This product is currently out of stock
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Please <a href="../auth/login.php" class="alert-link">login</a> to purchase this product
                        </div>
                        <div class="d-grid gap-2">
                            <a href="../auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login to Purchase
                            </a>
                            <a href="../auth/register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Create Account
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Security Badge -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt text-success"></i> 
                            <strong>Secure Shopping</strong><br>
                            Your transaction is protected with industry-standard encryption
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products Section (Optional) -->
        <div class="mt-5">
            <h3 class="mb-4"><i class="fas fa-box-open"></i> Related Products</h3>
            <?php
            // Get related products from same category
            $stmt = $db->prepare("
                SELECT * FROM products 
                WHERE category = :category 
                AND product_id != :product_id 
                AND is_active = 1 
                LIMIT 4
            ");
            $stmt->execute([
                ':category' => $product['category'],
                ':product_id' => $productId
            ]);
            $relatedProducts = $stmt->fetchAll();
            ?>

            <?php if (!empty($relatedProducts)): ?>
                <div class="row g-4">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="product-image-large" style="height: 150px;">
                                    <i class="fas fa-box fa-3x text-muted"></i>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                                    <p class="text-primary fw-bold">$<?php echo number_format($related['price'], 2); ?></p>
                                    <a href="details.php?id=<?php echo $related['product_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
