<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT l.*, u.name AS seller_name, u.city AS seller_city, u.id AS seller_id, c.name AS cat_name, c.icon AS cat_icon,
            e.name AS editor_name, e.is_admin AS editor_is_admin
     FROM listings l
     JOIN users u ON u.id = l.user_id
     LEFT JOIN categories c ON c.id = l.category_id
     LEFT JOIN users e ON e.id = l.updated_by
     WHERE l.id = ?'
);
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Listing not found. <a href="index.php">Go back</a></p>');
}

$pdo->prepare('UPDATE listings SET views = views + 1 WHERE id = ?')->execute([$id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_body'])) {
    requireAuth();
    verifyCsrf();
    $body = trim($_POST['message_body']);
    if ($body !== '' && $user['id'] != $listing['seller_id']) {
        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, listing_id, body) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $listing['seller_id'], $id, $body]);
        flash('success', 'Message sent to the seller!');
        redirect('chat.php?with=' . $listing['seller_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($listing['title']) ?> — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="search.php">Search</a>
        <a href="trade.php">Trade</a>
        <?php if ($user): ?><a href="profile.php?id=<?= (int) $user['id'] ?>" class="nav-user">👤 <?= e($user['name']) ?></a>
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

<div class="dashboard-wrap" style="max-width:900px">
    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-img" style="height:280px;font-size:5rem">
            <?php if ($listing['image_url']): ?><img src="<?= e($listing['image_url']) ?>" alt=""><?php else: ?><?= e($listing['cat_icon'] ?: '📦') ?><?php endif; ?>
        </div>
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:1rem">
                <div>
                    <div style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap">
                        <h1 style="font-size:1.5rem;margin-bottom:.4rem"><?= e($listing['title']) ?></h1>
                        <?php if ($user && ($user['id'] == $listing['seller_id'] || !empty($user['is_admin']))): ?>
                            <a href="edit-listing.php?id=<?= $id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-meta">
                        <span>📍 <?= e($listing['city'] ?: 'N/A') ?></span>
                        <span>👁️ <?= (int) $listing['views'] ?> views</span>
                        <?php if ($listing['halal_badge']): ?><span class="halal-badge">✓ Halal</span><?php endif; ?>
                    </div>
                    <?php if ($listing['editor_name']): ?>
                        <div style="font-size:.78rem;color:var(--text-light);margin-top:.3rem">
                            Last edited by <?= e($listing['editor_name']) ?><?= $listing['editor_is_admin'] ? ' (Admin)' : '' ?>
                            on <?= date('M j, Y', strtotime($listing['updated_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-price" style="font-size:1.8rem">
                    <?= $listing['price'] > 0 ? '$' . number_format((float) $listing['price']) : 'Free / Swap' ?>
                    <?php if ($listing['price_type'] === 'negotiable'): ?><div style="font-size:.8rem;color:var(--text-light);font-weight:400">Negotiable</div><?php endif; ?>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:1.2rem 0">

            <h3 style="font-size:1rem;margin-bottom:.6rem">Description</h3>
            <p style="color:var(--text-mid);white-space:pre-line"><?= e($listing['description']) ?></p>

            <hr style="border:none;border-top:1px solid var(--border);margin:1.2rem 0">

            <div style="display:flex;align-items:center;gap:1rem">
                <div class="profile-avatar" style="width:50px;height:50px;font-size:1.2rem;margin:0">
                    <?= e(mb_substr($listing['seller_name'], 0, 1)) ?>
                </div>
                <div>
                    <a href="profile.php?id=<?= (int) $listing['seller_id'] ?>" style="font-weight:600;color:var(--text)"><?= e($listing['seller_name']) ?></a>
                    <div style="font-size:.82rem;color:var(--text-light)">📍 <?= e($listing['seller_city'] ?: 'N/A') ?></div>
                </div>
            </div>
        </div>

        <?php if ($user && $user['id'] != $listing['seller_id']): ?>
        <div class="card-footer" style="display:block">
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                <div class="form-group">
                    <label class="form-label">Message the seller</label>
                    <textarea name="message_body" class="form-control" placeholder="Hi, is this still available?" required></textarea>
                </div>
                <button type="submit" class="btn btn-green">💬 Send Message</button>
            </form>
        </div>
        <?php elseif (!$user): ?>
        <div class="card-footer">
            <a href="login.php" class="btn btn-primary">Login to message seller</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="app.js" defer></script>
</body>
</html>
