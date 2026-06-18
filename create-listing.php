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
        $stmt = $pdo->prepare(
            'INSERT INTO listings (user_id, category_id, title, description, price, price_type, city, halal_badge)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $categoryId ?: null, $title, $description, $price, $priceType, $city, $halalBadge]);
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
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ <?= e(SITE_NAME) ?></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
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
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

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
                        <label class="form-label">Price (Rs)</label>
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
</body>
</html>
