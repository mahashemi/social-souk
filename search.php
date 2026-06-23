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
    <a class="nav-brand" href="index.php"><i data-lucide="shopping-bag" class="lucide-icon"></i> <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu"><i data-lucide="menu" class="lucide-icon"></i></button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="search.php">Search</a>
        <a href="trade.php">Trade</a>
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
        <?php if ($user): ?>
            <a href="create-listing.php">+ Sell Item</a>
            <a href="chat.php">Messages</a>
            <div class="nav-account">
                <button class="nav-account-trigger" type="button" onclick="toggleAccountMenu(event)" aria-label="Account menu">
                    <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                    <i data-lucide="chevron-down" class="lucide-icon"></i>
                </button>
                <div class="nav-account-menu">
                    <div class="nav-account-header">
                        <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                        <div>
                            <div class="nav-account-name"><?= e($user['name']) ?></div>
                            <div class="nav-account-email"><?= e($user['email']) ?></div>
                        </div>
                    </div>
                    <div class="nav-menu-divider"></div>
                    <a href="dashboard.php"><i data-lucide="layout-dashboard" class="lucide-icon"></i> Dashboard</a>
                    <a href="profile.php?id=<?= (int) $user['id'] ?>"><i data-lucide="user" class="lucide-icon"></i> My Profile</a>
                    <a href="chat.php"><i data-lucide="message-circle" class="lucide-icon"></i> Messages</a>
                    <?php if (!empty($user['is_admin'])): ?><a href="admin.php"><i data-lucide="shield-check" class="lucide-icon"></i> Admin Panel</a><?php endif; ?>
                    <div class="nav-menu-divider"></div>
                    <a href="logout.php"><i data-lucide="log-out" class="lucide-icon"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-btn">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container section">
    <h2 class="section-title">Search <span>Listings</span></h2>
    <form method="get" style="max-width:500px;margin-bottom:2rem;display:flex;gap:.6rem">
        <input type="text" name="q" class="form-control" placeholder="Search by title, description, or city..." value="<?= e($q) ?>" autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <?php if ($q === ''): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="search" class="lucide-icon"></i></div><h3>Type something to search listings</h3></div>
    <?php elseif (!$listings): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="inbox" class="lucide-icon"></i></div><h3>No results for "<?= e($q) ?>"</h3></div>
    <?php else: ?>
        <p class="section-sub"><?= count($listings) ?> result(s) for "<?= e($q) ?>"</p>
        <div class="grid-4">
            <?php foreach ($listings as $l): ?>
            <a href="listing.php?id=<?= (int) $l['id'] ?>" class="card">
                <div class="card-img"><?php if ($l['image_url']): ?><img src="<?= e($l['image_url']) ?>" alt=""><?php else: ?><?= catIcon($l['cat_icon']) ?><?php endif; ?></div>
                <div class="card-body">
                    <div class="card-title"><?= e($l['title']) ?></div>
                    <div class="card-price"><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free / Swap' ?></div>
                    <div class="card-meta"><span><i data-lucide="map-pin" class="lucide-icon"></i> <?= e($l['city'] ?: 'N/A') ?></span></div>
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
