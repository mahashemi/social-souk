<?php
require_once __DIR__ . '/db.php';
$user = auth();

$categoryId = (int) ($_GET['category'] ?? 0);
$q = trim($_GET['q'] ?? '');
$categories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();

$sql = "SELECT p.*, c.company_name, c.verification_status, c.country, cat.name AS cat_name, cat.icon AS cat_icon
        FROM b2b_products p
        JOIN companies c ON c.id = p.company_id
        LEFT JOIN b2b_categories cat ON cat.id = p.category_id
        WHERE p.is_active = 1";
$params = [];
if ($categoryId > 0) { $sql .= ' AND p.category_id = ?'; $params[] = $categoryId; }
if ($q !== '') { $sql .= ' AND (p.title LIKE ? OR p.description LIKE ? OR c.company_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Products — <?= e(SITE_NAME) ?> Trade</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="trade.php">Trade</a>
        <a href="rfq-board.php">RFQ Board</a>
        <?php if ($user): ?><span class="nav-user">👤 <?= e($user['name']) ?></span><a href="trade-dashboard.php">Trade Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="container section">
    <h2 class="section-title">Browse <span>Products</span></h2>

    <form method="get" style="display:flex;gap:.6rem;margin-bottom:1.5rem;max-width:600px">
        <input type="text" name="q" class="form-control" placeholder="Search products, suppliers..." value="<?= e($q) ?>">
        <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="chip-row">
        <a href="trade-products.php<?= $q ? '?q=' . urlencode($q) : '' ?>" class="cat-chip <?= !$categoryId ? 'active' : '' ?>">🌐 All Categories</a>
        <?php foreach ($categories as $c): ?>
            <a href="?category=<?= (int) $c['id'] ?><?= $q ? '&q=' . urlencode($q) : '' ?>" class="cat-chip <?= $categoryId === (int) $c['id'] ? 'active' : '' ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <p class="section-sub"><?= count($products) ?> product(s) found</p>

    <?php if (!$products): ?>
        <div class="empty-state"><div class="icon">📦</div><h3>No products found</h3></div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($products as $p): ?>
        <a href="trade-product.php?id=<?= (int) $p['id'] ?>" class="b2b-product-card" style="text-decoration:none;color:inherit">
            <div class="b2b-product-img">
                <?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt=""><?php else: ?><?= e($p['cat_icon'] ?: '📦') ?><?php endif; ?>
            </div>
            <div class="b2b-product-body">
                <div class="b2b-product-title"><?= e($p['title']) ?></div>
                <div class="b2b-product-price"><?= moneyRange((float) $p['price_min'], (float) $p['price_max']) ?></div>
                <div class="b2b-product-moq">MOQ: <?= (int) $p['moq'] ?> <?= e($p['unit']) ?></div>
            </div>
            <div class="b2b-product-footer">
                <span><?= e($p['company_name']) ?></span>
                <?php if ($p['verification_status'] === 'verified'): ?><span title="Verified Supplier">✔️</span><?php endif; ?>
                <span style="margin-left:auto">📍 <?= e($p['country']) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
