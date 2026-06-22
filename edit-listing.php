<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ?');
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Listing not found. <a href="index.php">Go back</a></p>');
}

$isOwner = $listing['user_id'] == $user['id'];
$isAdmin = !empty($user['is_admin']);
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">You do not have permission to edit this listing. <a href="listing.php?id=' . $id . '">Go back</a></p>');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float) ($_POST['price'] ?? 0);
    $priceType   = $_POST['price_type'] ?? 'fixed';
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $city        = trim($_POST['city'] ?? '');
    $halalBadge  = isset($_POST['halal_badge']) ? 1 : 0;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
    if (mb_strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!in_array($priceType, ['fixed','negotiable','free','swap'], true)) $errors[] = 'Invalid price type.';

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'listings') ?? $listing['image_url'];
        $stmt = $pdo->prepare(
            'UPDATE listings SET title=?, description=?, price=?, price_type=?, category_id=?, city=?, halal_badge=?, is_active=?, image_url=?, updated_by=?, updated_at=NOW()
             WHERE id=?'
        );
        $stmt->execute([$title, $description, $price, $priceType, $categoryId ?: null, $city, $halalBadge, $isActive, $imagePath, $user['id'], $id]);
        flash('success', 'Listing updated.');
        redirect('listing.php?id=' . $id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Listing — <?= e(SITE_NAME) ?></title>
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
        <h2><i data-lucide="pencil" class="lucide-icon"></i> Edit Listing</h2>
        <p><?= $isAdmin && !$isOwner ? 'You are editing this listing as an admin.' : 'Update your listing details below.' ?></p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

                <div class="form-group">
                    <label class="form-label">Photo</label>
                    <?php if ($listing['image_url']): ?>
                        <img src="<?= e($listing['image_url']) ?>" style="max-width:200px;border-radius:8px;margin-bottom:.6rem;display:block">
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-hint">Upload a new photo to replace the current one, or leave blank to keep it.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? $listing['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" required><?= e($_POST['description'] ?? $listing['description']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= $listing['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?= e($_POST['city'] ?? $listing['city']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price ($)</label>
                        <input type="number" name="price" class="form-control" min="0" step="0.01" value="<?= e($_POST['price'] ?? $listing['price']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price Type</label>
                        <select name="price_type" class="form-control">
                            <?php foreach (['fixed'=>'Fixed Price','negotiable'=>'Negotiable','free'=>'Free','swap'=>'Swap / Trade'] as $val=>$label): ?>
                                <option value="<?= $val ?>" <?= $listing['price_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="halal_badge" value="1" style="width:auto" <?= $listing['halal_badge'] ? 'checked' : '' ?>>
                        This item carries a Halal Certification / is inherently halal
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" style="width:auto" <?= $listing['is_active'] ? 'checked' : '' ?>>
                        Listing is active (visible to buyers)
                    </label>
                </div>

                <div style="display:flex;gap:.8rem">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="listing.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
