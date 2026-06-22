<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, c.user_id AS owner_id FROM b2b_products p JOIN companies c ON c.id = p.company_id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Product not found. <a href="trade.php">Go back</a></p>');
}
if ((int) $product['owner_id'] !== (int) $user['id'] && empty($user['is_admin'])) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">You do not have permission to edit this product. <a href="trade.php">Go back</a></p>');
}

$categories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $priceMin = (float) ($_POST['price_min'] ?? 0);
    $priceMax = (float) ($_POST['price_max'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'piece');
    $moq = (int) ($_POST['moq'] ?? 1);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
    if (mb_strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'trade-products') ?? $product['image_url'];
        $pdo->prepare(
            'UPDATE b2b_products SET title=?, description=?, category_id=?, price_min=?, price_max=?, unit=?, moq=?, image_url=?, is_active=?, updated_by=?, updated_at=NOW() WHERE id=?'
        )->execute([$title, $description, $categoryId, $priceMin, $priceMax, $unit, $moq, $imagePath, $isActive, $user['id'], $id]);
        flash('success', 'Product updated.');
        redirect('trade-dashboard.php');
    }
    $product = array_merge($product, compact('title', 'description', 'priceMin', 'priceMax', 'unit', 'moq'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product — <?= e(SITE_NAME) ?> Trade</title>
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
        <a href="trade.php">Trade</a>
        <a href="trade-dashboard.php">Trade Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
    </div>
</nav>

<div class="container section" style="max-width:640px">
    <h2 class="section-title">Edit <span>Product</span></h2>

    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
            <div class="form-group">
                <label class="form-label">Product Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($product['title']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $product['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" required><?= e($product['description']) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Min Price (USD)</label>
                    <input type="number" name="price_min" class="form-control" step="0.01" min="0" value="<?= e((string) $product['price_min']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Price (USD)</label>
                    <input type="number" name="price_max" class="form-control" step="0.01" min="0" value="<?= e((string) $product['price_max']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="<?= e($product['unit']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order Quantity (MOQ)</label>
                    <input type="number" name="moq" class="form-control" value="<?= (int) $product['moq'] ?>" min="1" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Product Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:.5rem">
                <input type="checkbox" name="is_active" value="1" style="width:auto" <?= $product['is_active'] ? 'checked' : '' ?>>
                <label class="form-label" style="margin:0">Active (visible to buyers)</label>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </form>
    </div></div>
</div>
<script src="app.js" defer></script>
</body>
</html>
