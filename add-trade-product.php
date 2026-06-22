<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$company = myCompany($pdo, $user['id']);
if (!$company || !in_array($company['role'], ['supplier', 'both'], true)) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Only suppliers can list products. <a href="trade.php">Go back</a></p>');
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

    if (mb_strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
    if (mb_strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';
    if ($priceMax > 0 && $priceMax < $priceMin) $errors[] = 'Maximum price cannot be less than minimum price.';
    if ($moq < 1) $errors[] = 'Minimum order quantity must be at least 1.';

    if (!$errors) {
        $imagePath = handleImageUpload('image', 'trade-products');
        $pdo->prepare(
            'INSERT INTO b2b_products (company_id, category_id, title, description, price_min, price_max, unit, moq, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$company['id'], $categoryId, $title, $description, $priceMin, $priceMax, $unit, $moq, $imagePath]);
        flash('success', 'Product listed successfully.');
        redirect('trade-dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>List a Product — <?= e(SITE_NAME) ?> Trade</title>
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
    <h2 class="section-title">List a New <span>Product</span></h2>

    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
            <div class="form-group">
                <label class="form-label">Product Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
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
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Min Price (USD)</label>
                    <input type="number" name="price_min" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Price (USD)</label>
                    <input type="number" name="price_max" class="form-control" step="0.01" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="piece" placeholder="piece, kg, ton, carton, set">
                </div>
                <div class="form-group">
                    <label class="form-label">Minimum Order Quantity (MOQ)</label>
                    <input type="number" name="moq" class="form-control" value="1" min="1" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Product Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary btn-full">List Product</button>
        </form>
    </div></div>
</div>
<script src="app.js" defer></script>
</body>
</html>
