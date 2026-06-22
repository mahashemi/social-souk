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
    <a class="nav-brand" href="index.php"><i data-lucide="shopping-bag" class="lucide-icon"></i> <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu"><i data-lucide="menu" class="lucide-icon"></i></button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="search.php">Search</a>
        <a href="trade.php">Trade</a>
        <?php if ($user): ?><a href="profile.php?id=<?= (int) $user['id'] ?>" class="nav-user"><i data-lucide="user" class="lucide-icon"></i> <?= e($user['name']) ?></a>
            <a href="create-listing.php">+ Sell Item</a>
            <a href="chat.php">Messages</a>
            <a href="dashboard.php">Dashboard</a>
            <?php if (!empty($user['is_admin'])): ?><a href="admin.php">Admin</a><?php endif; ?>
            <a href="about.php">About</a>
            <a href="feedback.php">Feedback</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?>
            <a href="about.php">About</a>
            <a href="feedback.php">Feedback</a>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-btn">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<div class="trade-subnav">
    <a href="trade.php"><i data-lucide="store" class="lucide-icon"></i> Trade Home</a><span class="sep">|</span>
    <a href="trade-products.php"><i data-lucide="package" class="lucide-icon"></i> Browse Products</a><span class="sep">|</span>
    <a href="rfq-board.php"><i data-lucide="clipboard-list" class="lucide-icon"></i> RFQ (Request for Quotation) Board</a><span class="sep">|</span>
    <a href="trade-how-it-works.php"><i data-lucide="circle-help" class="lucide-icon"></i> How It Works</a>
    <?php if ($user): ?><span class="sep">|</span><a href="trade-dashboard.php"><i data-lucide="building-2" class="lucide-icon"></i> My Trade Dashboard</a><?php endif; ?>
</div>
<div class="container section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
            <h2 class="section-title">RFQ <span>Board</span></h2>
            <p class="section-sub">RFQ = Request for Quotation. Buyers post what they need — suppliers respond with quotes.</p>
        </div>
        <?php if ($user): ?><a href="rfq-submit.php" class="btn btn-primary">+ Post a Request</a><?php endif; ?>
    </div>

    <div class="chip-row">
        <a href="rfq-board.php" class="cat-chip <?= !$categoryId ? 'active' : '' ?>"><i data-lucide="globe" class="lucide-icon"></i> All Categories</a>
        <?php foreach ($categories as $c): ?>
            <a href="?category=<?= (int) $c['id'] ?>" class="cat-chip <?= $categoryId === (int) $c['id'] ? 'active' : '' ?>"><?= catIcon($c['icon']) ?> <?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$rfqs): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="clipboard-list" class="lucide-icon"></i></div><h3>No open requests right now</h3></div>
    <?php else: ?>
    <div class="grid-2">
        <?php foreach ($rfqs as $r): ?>
        <a href="rfq-detail.php?id=<?= (int) $r['id'] ?>" class="rfq-card" style="text-decoration:none;color:inherit;display:block">
            <div class="rfq-card-title"><?= catIcon($r['cat_icon'] ?: 'clipboard-list') ?> <?= e($r['product_name']) ?></div>
            <div class="rfq-card-meta">
                <span><i data-lucide="package" class="lucide-icon"></i> Qty: <?= (int) $r['quantity'] ?> <?= e($r['unit']) ?></span>
                <?php if ($r['target_price']): ?><span><i data-lucide="circle-dollar-sign" class="lucide-icon"></i> Target: $<?= number_format((float) $r['target_price'], 2) ?></span><?php endif; ?>
                <?php if ($r['destination_country']): ?><span><i data-lucide="map-pin" class="lucide-icon"></i> <?= e($r['destination_country']) ?></span><?php endif; ?>
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
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
