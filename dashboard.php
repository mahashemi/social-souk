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

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>Welcome back, <?= e($user['name']) ?></h2>
        <p>Manage your listings and track activity.</p>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-size:1.1rem;color:var(--green-deep)">My Listings (<?= count($myListings) ?>)</h3>
        <a href="create-listing.php" class="btn btn-primary btn-sm">+ New Listing</a>
    </div>

    <?php if (!$myListings): ?>
        <div class="empty-state">
            <div class="icon"><i data-lucide="package" class="lucide-icon"></i></div>
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
                <td><a href="listing.php?id=<?= (int) $l['id'] ?>"><?= catIcon($l['cat_icon']) ?> <?= e($l['title']) ?></a></td>
                <td><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free/Swap' ?></td>
                <td><?= e($l['city'] ?: '—') ?></td>
                <td><?= (int) $l['views'] ?></td>
                <td><span class="badge <?= $l['is_active'] ? 'badge-active' : 'badge-closed' ?>"><?= $l['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="action-row">
                    <a href="edit-listing.php?id=<?= (int) $l['id'] ?>" class="icon-btn" data-tip="Edit listing" aria-label="Edit listing"><i data-lucide="pencil" class="lucide-icon"></i></a>
                    <form method="post" onsubmit="return confirm('Delete this listing?')" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                        <button type="submit" name="delete_listing" value="<?= (int) $l['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete"><i data-lucide="trash-2" class="lucide-icon"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
