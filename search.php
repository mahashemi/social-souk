<?php
require_once __DIR__ . '/db.php';
$user = auth();

$q = trim($_GET['q'] ?? '');
$listings = [];

if ($q !== '') {
    $stmt = $pdo->prepare(
        "SELECT l.*, c.icon AS cat_icon FROM listings l LEFT JOIN categories c ON c.id = l.category_id
         WHERE l.is_active = 1 AND (l.title LIKE ? OR l.description LIKE ? OR l.city LIKE ?)
         ORDER BY l.created_at DESC LIMIT 40"
    );
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $like]);
    $listings = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ <?= e(SITE_NAME) ?></div>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <?php if ($user): ?><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="container section">
    <h2 class="section-title">Search <span>Listings</span></h2>
    <form method="get" style="max-width:500px;margin-bottom:2rem;display:flex;gap:.6rem">
        <input type="text" name="q" class="form-control" placeholder="Search by title, description, or city..." value="<?= e($q) ?>" autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($q === ''): ?>
        <div class="empty-state"><div class="icon">🔍</div><h3>Type something to search listings</h3></div>
    <?php elseif (!$listings): ?>
        <div class="empty-state"><div class="icon">📭</div><h3>No results for "<?= e($q) ?>"</h3></div>
    <?php else: ?>
        <p class="section-sub"><?= count($listings) ?> result(s) for "<?= e($q) ?>"</p>
        <div class="grid-4">
            <?php foreach ($listings as $l): ?>
            <a href="listing.php?id=<?= (int) $l['id'] ?>" class="card">
                <div class="card-img"><?php if ($l['image_url']): ?><img src="<?= e($l['image_url']) ?>" alt=""><?php else: ?><?= e($l['cat_icon'] ?: '📦') ?><?php endif; ?></div>
                <div class="card-body">
                    <div class="card-title"><?= e($l['title']) ?></div>
                    <div class="card-price"><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free / Swap' ?></div>
                    <div class="card-meta"><span>📍 <?= e($l['city'] ?: 'N/A') ?></span></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
