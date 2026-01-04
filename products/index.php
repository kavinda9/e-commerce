<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();


$catsStmt = $db->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);


$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$min = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$max = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;


$sortMap = [
    'newest' => 'created_at DESC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'name_asc' => 'name ASC'
];
$orderBy = isset($sortMap[$sort]) ? $sortMap[$sort] : $sortMap['newest'];


$sql = "SELECT SQL_CALC_FOUND_ROWS p.* FROM products p WHERE p.is_active = 1";
$params = [];

if ($q !== '') {
    $sql .= " AND (p.name LIKE :q OR p.description LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($category !== null) {
    $sql .= " AND p.category_id = :category";
    $params[':category'] = $category;
}
if ($min !== null) {
    $sql .= " AND p.price >= :min";
    $params[':min'] = $min;
}
if ($max !== null) {
    $sql .= " AND p.price <= :max";
    $params[':max'] = $max;
}

$sql .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


$total = (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Products</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h1>Products</h1>
  <a href="<?= rtrim(BASE_URL, '/') ?>" class="btn btn-link">Home</a>

  <form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Search products...">
    </div>
    <div class="col-md-3">
      <select name="category" class="form-select">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($category !== null && $category == $c['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <input type="number" step="0.01" name="min" class="form-control" placeholder="Min price" value="<?= ($min !== null) ? htmlspecialchars($min) : '' ?>">
    </div>
    <div class="col-auto">
      <input type="number" step="0.01" name="max" class="form-control" placeholder="Max price" value="<?= ($max !== null) ? htmlspecialchars($max) : '' ?>">
    </div>
    <div class="col-md-2">
      <select name="sort" class="form-select">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price ↑</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name A–Z</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary">Filter</button>
    </div>
  </form>

  <div class="row">
    <?php if (empty($products)): ?>
      <div class="col-12"><p>No products found.</p></div>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <?php if (!empty($p['image_url'])): ?>
            <img src="<?= htmlspecialchars($p['image_url']) ?>" class="card-img-top" style="object-fit:cover;height:140px" alt="<?= htmlspecialchars($p['name']) ?>">
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
            <p class="card-text mb-2">$<?= number_format($p['price'], 2) ?></p>
            <a href="detail.php?id=<?= $p['id'] ?>" class="mt-auto btn btn-outline-primary">View</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>


  <?php if ($totalPages > 1): ?>
    <nav aria-label="pagination">
      <ul class="pagination">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
          <?php
            $qs = $_GET;
            $qs['page'] = $i;
            $url = '?' . http_build_query($qs);
          ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
</body>
</html>