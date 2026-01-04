<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}


$stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id AND p.is_active = 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($product['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <a href="index.php" class="btn btn-secondary mb-3">← Back to products</a>
  <div class="row">
    <div class="col-md-5">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?= htmlspecialchars($product['image_url']) ?>" class="img-fluid" alt="<?= htmlspecialchars($product['name']) ?>">
      <?php endif; ?>
    </div>
    <div class="col-md-7">
      <h2><?= htmlspecialchars($product['name']) ?></h2>
      <p class="text-muted"><?= htmlspecialchars($product['category_name']) ?></p>
      <h3>$<?= number_format($product['price'], 2) ?></h3>
      <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      <p>Stock: <?= (int)$product['stock_quantity'] ?></p>


      <form method="post" action="../cart/add.php">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <div class="mb-2">
          <label>Quantity</label>
          <input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?>" class="form-control" style="width:120px">
        </div>
        <button class="btn btn-success">Add to cart</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>