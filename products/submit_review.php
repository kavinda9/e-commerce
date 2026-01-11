<?php
require_once '../config/database.php';
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to leave a review']);
    http_response_code(401);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    http_response_code(400);
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$reviewText = sanitizeInput($_POST['review_text'] ?? '');

// Validation
if ($productId <= 0 || $rating < 1 || $rating > 5) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid rating. Must be between 1 and 5']);
    http_response_code(400);
    exit;
}

try {
    // Check if product exists
    $stmt = $db->prepare("SELECT product_id FROM products WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $productId]);
    if (!$stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        http_response_code(404);
        exit;
    }

    // Check if user has already reviewed this product
    $stmt = $db->prepare("SELECT review_id FROM product_reviews WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    $existingReview = $stmt->fetch();

    if ($existingReview) {
        // Update existing review
        $stmt = $db->prepare("
            UPDATE product_reviews 
            SET rating = :rating, 
                review_text = :review_text,
                updated_at = CURRENT_TIMESTAMP
            WHERE review_id = :review_id
        ");
        $stmt->execute([
            ':rating' => $rating,
            ':review_text' => $reviewText,
            ':review_id' => $existingReview['review_id']
        ]);
        
        logAudit($userId, 'REVIEW_UPDATED', 'product', $productId, "Updated review for product: $productId");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
    } else {
        // Insert new review
        $stmt = $db->prepare("
            INSERT INTO product_reviews (product_id, user_id, rating, review_text)
            VALUES (:product_id, :user_id, :rating, :review_text)
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':rating' => $rating,
            ':review_text' => $reviewText
        ]);
        
        logAudit($userId, 'REVIEW_ADDED', 'product', $productId, "Added review for product: $productId");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    }
    
} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    http_response_code(500);
}
?>
