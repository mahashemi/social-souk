<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">User not found. <a href="index.php">Go back</a></p>');
}

$stmt = $pdo->prepare(
    'SELECT l.*, c.icon AS cat_icon FROM listings l LEFT JOIN categories c ON c.id = l.category_id
     WHERE l.user_id = ? AND l.is_active = 1 ORDER BY l.created_at DESC'
);
$stmt->execute([$id]);
$listings = $stmt->fetchAll();

$followers = $pdo->prepare('SELECT COUNT(*) c FROM follows WHERE following_id = ?');
$followers->execute([$id]);
$followerCount = $followers->fetch()['c'];

$isFollowing = false;
if ($user && $user['id'] != $id) {
    $f = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $f->execute([$user['id'], $id]);
    $isFollowing = (bool) $f->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    requireAuth();
    verifyCsrf();
    if ($isFollowing) {
        $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')->execute([$user['id'], $id]);
    } else {
        $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)')->execute([$user['id'], $id]);
    }
    redirect('profile.php?id=' . $id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($profile['name']) ?> — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ <?= e(SITE_NAME) ?></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <?php if ($user): ?><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="profile-header">
    <div class="profile-avatar"><?= e(mb_substr($profile['name'], 0, 1)) ?></div>
    <div class="profile-name"><?= e($profile['name']) ?></div>
    <div class="profile-city">📍 <?= e($profile['city'] ?: 'Location not set') ?></div>
    <div class="profile-stats">
        <div class="stat-item"><div class="stat-num"><?= count($listings) ?></div><div class="stat-lbl">Listings</div></div>
        <div class="stat-item"><div class="stat-num"><?= (int) $followerCount ?></div><div class="stat-lbl">Followers</div></div>
    </div>

    <?php if ($user && $user['id'] != $id): ?>
    <form method="post" style="margin-top:1.2rem">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="toggle_follow" value="1">
        <button type="submit" class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>">
            <?= $isFollowing ? '✓ Following' : '+ Follow' ?>
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="container section">
    <h2 class="section-title"><?= e($profile['name']) ?>'s <span>Listings</span></h2>

    <?php if (!$listings): ?>
        <div class="empty-state"><div class="icon">📭</div><h3>No active listings</h3></div>
    <?php else: ?>
    <div class="grid-4">
        <?php foreach ($listings as $l): ?>
        <a href="listing.php?id=<?= (int) $l['id'] ?>" class="card">
            <div class="card-img"><?php if ($l['image_url']): ?><img src="<?= e($l['image_url']) ?>" alt=""><?php else: ?><?= e($l['cat_icon'] ?: '📦') ?><?php endif; ?></div>
            <div class="card-body">
                <div class="card-title"><?= e($l['title']) ?></div>
                <div class="card-price"><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free / Swap' ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
