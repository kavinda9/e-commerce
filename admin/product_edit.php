<?php
require_once '../config/database.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Get product ID
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

// Get product data
$stmt = $db->prepare("SELECT * FROM products WHERE product_id = :id");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php?error=notfound');
    exit;
}

// Get all categories for dropdown
$categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
    $category = sanitizeInput($_POST['category'] ?? '');
    $newCategory = sanitizeInput($_POST['new_category'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $customImageName = sanitizeInput($_POST['image_name'] ?? '');
    $removeImage = isset($_POST['remove_image']);
    
    // Use new category if provided
    if (!empty($newCategory)) {
        $category = $newCategory;
    }
    
    // Validation
    if (empty($name) || empty($price) || empty($category)) {
        $error = 'Product name, price, and category are required';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative';
    } elseif ($stockQuantity < 0) {
        $error = 'Stock quantity cannot be negative';
    } else {
        // Handle image
        $imageUrl = $product['image_url'];
        
        // Remove existing image if requested
        if ($removeImage && !empty($imageUrl)) {
            $imagePath = '../' . $imageUrl;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $imageUrl = '';
        }
        
        // Handle new image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/products/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Delete old image if exists
            if (!empty($product['image_url'])) {
                $oldImagePath = '../' . $product['image_url'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Invalid file type. Allowed: JPG, JPEG, PNG, GIF, WEBP';
            } else {
                // Use custom name or generate one
                if (!empty($customImageName)) {
                    $customImageName = preg_replace('/\.[^.]+$/', '', $customImageName);
                    $customImageName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customImageName);
                    $fileName = $customImageName . '.' . $fileExtension;
                } else {
                    $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
                    $fileName .= '_' . time() . '.' . $fileExtension;
                }
                
                $destPath = $uploadDir . $fileName;
                
                // Check if file already exists
                if (file_exists($destPath)) {
                    $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $fileExtension;
                    $destPath = $uploadDir . $fileName;
                }
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageUrl = 'assets/images/products/' . $fileName;
                } else {
                    $error = 'Failed to upload image';
                }
            }
        }
        
        // Update product if no errors
        if (empty($error)) {
            try {
                $stmt = $db->prepare("
                    UPDATE products 
                    SET name = :name, 
                        description = :description, 
                        price = :price, 
                        stock_quantity = :stock_quantity, 
                        category = :category, 
                        image_url = :image_url, 
                        is_active = :is_active
                    WHERE product_id = :id
                ");
                
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':stock_quantity' => $stockQuantity,
                    ':category' => $category,
                    ':image_url' => $imageUrl,
                    ':is_active' => $isActive,
                    ':id' => $productId
                ]);
                
                logAudit($_SESSION['user_id'], 'PRODUCT_UPDATED', 'product', $productId, "Updated product: $name");
                
                header('Location: products.php?updated=1');
                exit;
                
            } catch (Exception $e) {
                error_log("Update product error: " . $e->getMessage());
                $error = 'Failed to update product. Please try again.';
            }
        }
    }
    
    // Refresh product data after attempted update
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
            overflow: hidden;
            position: relative;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .current-image {
            position: relative;
        }
        .remove-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10;
        }
        .required::after {
            content: " *";
            color: red;
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
                <a class="nav-link" href="products.php"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <h2 class="mb-4">
                        <i class="fas fa-edit text-primary"></i> Edit Product #<?php echo $product['product_id']; ?>
                    </h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Product Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label required">Product Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>"
                                   required>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <!-- Price -->
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label required">Price ($)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       step="0.01" 
                                       min="0"
                                       value="<?php echo $product['price']; ?>"
                                       required>
                            </div>

                            <!-- Stock Quantity -->
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label required">Stock Quantity</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="stock_quantity" 
                                       name="stock_quantity" 
                                       min="0"
                                       value="<?php echo $product['stock_quantity']; ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label required">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"
                                            <?php echo $product['category'] === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">Or create new category:</small>
                                <input type="text" 
                                       class="form-control mt-1" 
                                       id="new_category" 
                                       name="new_category" 
                                       placeholder="Enter new category name">
                            </div>
                        </div>

                        <!-- Current Image -->
                        <?php if (!empty($product['image_url'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <div class="current-image">
                                    <?php if (file_exists('../' . $product['image_url'])): ?>
                                        <div class="image-preview">
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Product Image">
                                        </div>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                                            <label class="form-check-label text-danger" for="remove_image">
                                                <i class="fas fa-trash"></i> Remove current image
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Image file not found: <?php echo htmlspecialchars($product['image_url']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- New Product Image -->
                        <div class="mb-3">
                            <label for="product_image" class="form-label">
                                <?php echo empty($product['image_url']) ? 'Upload' : 'Replace'; ?> Product Image
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="product_image" 
                                   name="product_image" 
                                   accept="image/*"
                                   onchange="previewImage(this)">
                            <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
                            
                            <!-- Custom Image Name -->
                            <div class="mt-2">
                                <label for="image_name" class="form-label">Custom Image Filename (Optional)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="image_name" 
                                       name="image_name" 
                                       placeholder="e.g., laptop_dell_xps">
                                <small class="text-muted">Leave empty to auto-generate filename. Extension will be added automatically.</small>
                            </div>
                            
                            <!-- New Image Preview -->
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <span class="text-muted"><i class="fas fa-image fa-3x"></i></span>
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_active" 
                                   name="is_active" 
                                   <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-eye"></i> Product is active (visible to customers)
                            </label>
                        </div>

                        <!-- Product Info -->
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Product Information:</strong><br>
                            <small>
                                Created: <?php echo date('M d, Y', strtotime($product['created_at'])); ?><br>
                                Last Updated: <?php echo date('M d, Y', strtotime($product['updated_at'])); ?>
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <div>
                                <a href="product_delete.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-danger me-2"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i> Delete Product
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Product
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                preview.style.display = 'flex';
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>