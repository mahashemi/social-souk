<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

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

    if (mb_strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
    if (mb_strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!in_array($priceType, ['fixed','negotiable','free','swap'], true)) $errors[] = 'Invalid price type.';

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'listings');
        $stmt = $pdo->prepare(
            'INSERT INTO listings (user_id, category_id, title, description, price, price_type, city, halal_badge, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $categoryId ?: null, $title, $description, $price, $priceType, $city, $halalBadge, $imagePath]);
        $newId = (int) $pdo->lastInsertId();
        flash('success', 'Your listing is live!');
        redirect('listing.php?id=' . $newId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post a Listing — <?= e(SITE_NAME) ?></title>
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
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
    </div>
</nav>

<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2>📦 Post a New Listing</h2>
        <p>Fill in the details below — it takes less than a minute.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

                <div class="form-group">
                    <label class="form-label">Photo (optional)</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-hint">JPG, PNG, or WEBP. Max 5MB. Leave blank to use a category icon instead.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Holy Quran — Arabic & English" value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Describe condition, why you're selling, any flaws..." required><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="Karachi" value="<?= e($_POST['city'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price ($)</label>
                        <input type="number" name="price" class="form-control" placeholder="0" min="0" step="0.01" value="<?= e($_POST['price'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price Type</label>
                        <select name="price_type" class="form-control">
                            <option value="fixed">Fixed Price</option>
                            <option value="negotiable">Negotiable</option>
                            <option value="free">Free</option>
                            <option value="swap">Swap / Trade</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="halal_badge" value="1" style="width:auto">
                        This item carries a Halal Certification / is inherently halal
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Publish Listing</button>
            </form>
        </div>
    </div>
</div>
<script src="app.js" defer></script>
</body>
</html>
