<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$categories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $productName = trim($_POST['product_name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'piece');
    $targetPrice = (float) ($_POST['target_price'] ?? 0) ?: null;
    $description = trim($_POST['description'] ?? '');
    $destination = trim($_POST['destination_country'] ?? '');

    if (mb_strlen($productName) < 3) $errors[] = 'Please enter the product you need.';
    if ($quantity < 1) $errors[] = 'Please enter a valid quantity.';
    if (mb_strlen($description) < 10) $errors[] = 'Please describe your requirement in more detail.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO rfqs (buyer_id, category_id, product_name, quantity, unit, target_price, description, destination_country)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $categoryId, $productName, $quantity, $unit, $targetPrice, $description, $destination]);
        $newId = (int) $pdo->lastInsertId();
        flash('success', 'Your request for quotation has been posted. Suppliers can now respond.');
        redirect('rfq-detail.php?id=' . $newId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post a Request for Quotation — <?= e(SITE_NAME) ?> Trade</title>
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

<div class="trade-subnav">
    <a href="trade.php">🏪 Trade Home</a><span class="sep">|</span>
    <a href="trade-products.php">📦 Browse Products</a><span class="sep">|</span>
    <a href="rfq-board.php">📋 RFQ (Request for Quotation) Board</a><span class="sep">|</span>
    <a href="trade-how-it-works.php">❓ How It Works</a>
    <?php if ($user): ?><span class="sep">|</span><a href="trade-dashboard.php">🏢 My Trade Dashboard</a><?php endif; ?>
</div>
<div class="container section" style="max-width:640px">
    <h2 class="section-title">Request a <span>Quotation</span></h2>
    <p class="section-sub">Tell suppliers what you need — verified suppliers will respond with quotes.</p>

    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
            <div class="form-group">
                <label class="form-label">Product / Service Needed</label>
                <input type="text" name="product_name" class="form-control" placeholder="e.g. Cotton T-Shirts, Plain White" required>
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
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity Needed</label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <input type="text" name="unit" class="form-control" value="piece">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Target Price (USD, optional)</label>
                    <input type="number" name="target_price" class="form-control" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Destination Country</label>
                    <input type="text" name="destination_country" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Detailed Requirements</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Specifications, packaging, delivery timeline..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Post Request for Quotation</button>
        </form>
    </div></div>
</div>
<script src="app.js" defer></script>
</body>
</html>
