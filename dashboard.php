<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_listing'])) {
    verifyCsrf();
    $lid = (int) $_POST['delete_listing'];
    $pdo->prepare('DELETE FROM listings WHERE id = ? AND user_id = ?')->execute([$lid, $user['id']]);
    redirect('dashboard.php');
}

$stmt = $pdo->prepare(
    'SELECT l.*, c.icon AS cat_icon FROM listings l LEFT JOIN categories c ON c.id = l.category_id
     WHERE l.user_id = ? ORDER BY l.created_at DESC'
);
$stmt->execute([$user['id']]);
$myListings = $stmt->fetchAll();

$unread = $pdo->prepare('SELECT COUNT(*) c FROM messages WHERE receiver_id = ? AND is_read = 0');
$unread->execute([$user['id']]);
$unreadCount = $unread->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <span class="nav-user">👤 <?= e($user['name']) ?></span>
        <a href="index.php">Browse</a>
        <a href="create-listing.php">+ Sell Item</a>
        <a href="chat.php">Messages <?= $unreadCount ? '(' . (int) $unreadCount . ')' : '' ?></a>
        <a href="edit-profile.php">Edit Profile</a>
        <?php if (!empty($user['is_admin'])): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="logout.php" class="nav-btn">Logout</a>
        <a href="trade.php">Trade</a>
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
    </div>
</nav>

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>👋 Welcome back, <?= e($user['name']) ?></h2>
        <p>Manage your listings and track activity.</p>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-size:1.1rem;color:var(--green-deep)">My Listings (<?= count($myListings) ?>)</h3>
        <a href="create-listing.php" class="btn btn-primary btn-sm">+ New Listing</a>
    </div>

    <?php if (!$myListings): ?>
        <div class="empty-state">
            <div class="icon">📦</div>
            <h3>You haven't posted anything yet</h3>
            <p>Start selling — post your first listing now.</p>
        </div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Item</th><th>Price</th><th>City</th><th>Views</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($myListings as $l): ?>
            <tr>
                <td><a href="listing.php?id=<?= (int) $l['id'] ?>"><?= e($l['cat_icon'] ?: '📦') ?> <?= e($l['title']) ?></a></td>
                <td><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free/Swap' ?></td>
                <td><?= e($l['city'] ?: '—') ?></td>
                <td><?= (int) $l['views'] ?></td>
                <td><span class="badge <?= $l['is_active'] ? 'badge-active' : 'badge-closed' ?>"><?= $l['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="action-row">
                    <a href="edit-listing.php?id=<?= (int) $l['id'] ?>" class="icon-btn" data-tip="Edit listing" aria-label="Edit listing">✏️</a>
                    <form method="post" onsubmit="return confirm('Delete this listing?')" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                        <button type="submit" name="delete_listing" value="<?= (int) $l['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
