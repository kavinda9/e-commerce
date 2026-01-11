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

// Compute discount and final price (support discount_percentage or legacy discount)
$originalPrice = (float)($product['price'] ?? 0);
$discount = 0;
if (isset($product['discount_percentage'])) {
    $discount = (float)$product['discount_percentage'];
} elseif (isset($product['discount'])) {
    $discount = (float)$product['discount'];
}
$finalPrice = $originalPrice;
if ($discount > 0 && $discount < 100) {
    $finalPrice = $originalPrice * (1 - ($discount / 100));
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
        .review-card {
            background: #ffffff;
            border: 1px solid #e6eef0;
            border-radius: 12px;
        }
        .card-body{
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .review-stars i {
            color: #f59e0b;
            border: #f09b07;
        }
        .reviewer-name {
            color: #0d6efd; 
            font-weight: 600;
        }
        .review-form .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #6b46c1 0%, #5a67d8 100%);
        }
        .review-badge {
            background: #fef3c7; color: #92400e; border-radius: 6px; padding: 3px 6px; font-size: 0.85rem;
        }
        #reviewText {
            background: #FBFBFB;
            border: 1px solid #667eea;
        }
        #reviewText:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.13rem rgba(102, 126, 234, 0.2);
            outline: none;
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

                    <!-- Discount Badge -->


                    <!-- Product Name -->
                    <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <!-- Price -->
                    <div class="price-display mb-4">
                        <?php if ($discount > 0 && $discount < 100): ?>
                            <small class="text-muted text-decoration-line-through">
                                $<?php echo number_format($originalPrice, 2); ?>
                            </small>
                            <div>
                                $<?php echo number_format($finalPrice, 2); ?>
                                <span class="badge bg-danger ms-2" style="font-size: 23px;"><?php echo intval($discount); ?>% OFF</span>
                            </div>
                        <?php else: ?>
                            $<?php echo number_format($originalPrice, 2); ?>
                        <?php endif; ?>
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
                            <li><strong>Price:</strong>
                                <?php if ($discount > 0 && $discount < 100): ?>
                                    <span class="text-muted text-decoration-line-through">$<?php echo number_format($originalPrice, 2); ?></span>
                                    <span class="ms-2">$<?php echo number_format($finalPrice, 2); ?></span>
                                <?php else: ?>
                                    $<?php echo number_format($originalPrice, 2); ?>
                                <?php endif; ?>
                            </li>
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

        <!-- Customer Reviews Section -->
        <div class="mt-5">
            <div class="row">
                <div class="col-lg-8">
                    <h3 class="mb-4"><i class="fas fa-comments"></i> Customer Reviews</h3>

                    <?php 
                    // Check if product_reviews table exists
                    $tableExists = false;
                    $tableError = null;
                    try {
                        $checkTable = $db->query("SHOW TABLES LIKE 'product_reviews'");
                        $tableExists = $checkTable->rowCount() > 0;
                    } catch (Exception $e) {
                        $tableExists = false;
                        $tableError = $e->getMessage();
                    }

                    $reviews = [];
                    $userReview = null;

                    if ($tableExists) {
                        // Get existing reviews - with error handling
                        try {
                            $stmt = $db->prepare("
                                SELECT pr.review_id, pr.rating, pr.review_text, pr.created_at, 
                                       u.first_name, u.last_name
                                FROM product_reviews pr
                                JOIN users u ON pr.user_id = u.user_id
                                WHERE pr.product_id = :product_id AND pr.is_approved = TRUE
                                ORDER BY pr.created_at DESC
                            ");
                            $stmt->execute([':product_id' => $productId]);
                            $reviews = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            $tableExists = false;
                            $tableError = "Column missing or table corrupted: " . $e->getMessage();
                        }

                        // Get current user's review if exists
                        if (isLoggedIn() && $tableExists) {
                            try {
                                $stmt = $db->prepare("
                                    SELECT review_id, rating, review_text
                                    FROM product_reviews
                                    WHERE product_id = :product_id AND user_id = :user_id
                                ");
                                $stmt->execute([
                                    ':product_id' => $productId,
                                    ':user_id' => $_SESSION['user_id']
                                ]);
                                $userReview = $stmt->fetch();
                            } catch (PDOException $e) {
                                // Silently fail if table is corrupted
                                $userReview = null;
                            }
                        }
                    }
                    ?>

                    <!-- Review Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php if (!$tableExists): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Reviews system not initialized.</strong> 
                                    The database migration has not been run yet. Please run the SQL migration first.
                                    <br><small>See RATINGS_SETUP_GUIDE.md for instructions.</small>
                                    <?php if ($tableError): ?>
                                        <br><code><?php echo htmlspecialchars($tableError); ?></code>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (isLoggedIn()): ?>
                                <h5 class="card-title">
                                    <?php echo $userReview ? 'Update Your Review' : 'Leave a Review'; ?>
                                </h5>
                                
                                <form id="reviewForm" class="review-form">
                                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Your Rating</label>
                                        <div class="rating-input" id="ratingInput">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star" data-value="<?php echo $i; ?>" 
                                                      style="font-size: 1.5rem; color: #f59e0b; cursor: pointer; margin-right: 5px;">
                                                    <i class="<?php echo ($i <= ($userReview['rating'] ?? 0)) ? 'fas' : 'far'; ?> fa-star"></i>
                                                </span>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" id="ratingValue" name="rating" 
                                               value="<?php echo $userReview['rating'] ?? ''; ?>" required>
                                        <small class="text-muted">Click to select a rating</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="reviewText" class="form-label">Your Review (Optional)</label>
                                        <textarea class="form-control" id="reviewText" name="review_text" 
                                                  rows="4" placeholder="Share your experience with this product..."><?php echo htmlspecialchars($userReview['review_text'] ?? ''); ?></textarea>
                                        <small class="text-muted">Max 1000 characters</small>
                                    </div>

                                    <div id="reviewMessage"></div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> 
                                        <?php echo $userReview ? 'Update Review' : 'Submit Review'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Please <a href="../auth/login.php" class="alert-link">login</a> to leave a review
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <?php if (!empty($reviews)): ?>
                        <div>
                            <h5 class="mb-3">Reviews from Customers (<?php echo count($reviews); ?>)</h5>
                            <?php foreach ($reviews as $review): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title reviewer-name">
                                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                </h6>
                                                <div class="text-warning mb-2 review-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $review['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="ms-2 text-muted"><?php echo $review['rating']; ?>/5</span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($review['review_text']): ?>
                                            <p class="card-text text-muted">
                                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <i class="fas fa-star"></i> No reviews yet. Be the first to review this product!
                        </div>
                    <?php endif; ?>
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
    
    <script>
        // Star rating functionality
        document.querySelectorAll('.rating-input .star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-value');
                document.getElementById('ratingValue').value = rating;
                
                // Update star display
                document.querySelectorAll('.rating-input .star').forEach((s, index) => {
                    const icon = s.querySelector('i');
                    if (index + 1 <= rating) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                });
            });
            
            // Hover effect
            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-value');
                document.querySelectorAll('.rating-input .star').forEach((s, index) => {
                    const icon = s.querySelector('i');
                    if (index + 1 <= rating) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                });
            });
        });
        
        // Reset hover effect
        document.getElementById('ratingInput').addEventListener('mouseleave', function() {
            const currentRating = document.getElementById('ratingValue').value;
            document.querySelectorAll('.rating-input .star').forEach((s, index) => {
                const icon = s.querySelector('i');
                if (currentRating && index + 1 <= currentRating) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });

        // Form submission
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const rating = document.getElementById('ratingValue').value;
            if (!rating) {
                showMessage('Please select a rating', 'danger');
                return;
            }

            const formData = new FormData(this);
            
            try {
                const response = await fetch('../products/submit_review.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Reload the page after 1 second to show new review
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'danger');
            }
        });

        function showMessage(message, type) {
            const messageDiv = document.getElementById('reviewMessage');
            messageDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        }
    </script>
</body>
</html>
