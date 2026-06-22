<?php
require_once __DIR__ . '/db.php';
$user = auth();

$categoryId = (int) ($_GET['category'] ?? 0);
$categories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();

$sql = "SELECT r.*, u.name AS buyer_name, cat.name AS cat_name, cat.icon AS cat_icon,
               (SELECT COUNT(*) FROM rfq_quotes q WHERE q.rfq_id = r.id) AS quote_count
        FROM rfqs r
        JOIN users u ON u.id = r.buyer_id
        LEFT JOIN b2b_categories cat ON cat.id = r.category_id
        WHERE r.status = 'open'";
$params = [];
if ($categoryId > 0) { $sql .= ' AND r.category_id = ?'; $params[] = $categoryId; }
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rfqs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RFQ Board — <?= e(SITE_NAME) ?> Trade</title>
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
        <a href="trade-products.php">Products</a>
        <?php if ($user): ?><span class="nav-user">👤 <?= e($user['name']) ?></span><a href="trade-dashboard.php">Trade Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="container section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
            <h2 class="section-title">RFQ <span>Board</span></h2>
            <p class="section-sub">Buyers post what they need — suppliers respond with quotes.</p>
        </div>
        <?php if ($user): ?><a href="rfq-submit.php" class="btn btn-primary">+ Post a Request</a><?php endif; ?>
    </div>

    <div class="chip-row">
        <a href="rfq-board.php" class="cat-chip <?= !$categoryId ? 'active' : '' ?>">🌐 All Categories</a>
        <?php foreach ($categories as $c): ?>
            <a href="?category=<?= (int) $c['id'] ?>" class="cat-chip <?= $categoryId === (int) $c['id'] ? 'active' : '' ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$rfqs): ?>
        <div class="empty-state"><div class="icon">📋</div><h3>No open requests right now</h3></div>
    <?php else: ?>
    <div class="grid-2">
        <?php foreach ($rfqs as $r): ?>
        <a href="rfq-detail.php?id=<?= (int) $r['id'] ?>" class="rfq-card" style="text-decoration:none;color:inherit;display:block">
            <div class="rfq-card-title"><?= e($r['cat_icon'] ?: '📋') ?> <?= e($r['product_name']) ?></div>
            <div class="rfq-card-meta">
                <span>📦 Qty: <?= (int) $r['quantity'] ?> <?= e($r['unit']) ?></span>
                <?php if ($r['target_price']): ?><span>💰 Target: $<?= number_format((float) $r['target_price'], 2) ?></span><?php endif; ?>
                <?php if ($r['destination_country']): ?><span>📍 <?= e($r['destination_country']) ?></span><?php endif; ?>
            </div>
            <p style="font-size:.85rem;color:var(--text-mid);margin-bottom:.6rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e($r['description']) ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:.78rem;color:var(--text-light)">
                <span>by <?= e($r['buyer_name']) ?> · <?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                <span class="badge badge-active"><?= (int) $r['quote_count'] ?> quote(s)</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
