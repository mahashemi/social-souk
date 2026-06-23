<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT p.*, c.id AS company_id, c.company_name, c.verification_status, c.country, c.city, c.user_id AS owner_id,
            c.logo_url, c.main_export_markets, c.avg_lead_time_days, cat.name AS cat_name, cat.icon AS cat_icon
     FROM b2b_products p
     JOIN companies c ON c.id = p.company_id
     LEFT JOIN b2b_categories cat ON cat.id = p.category_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Product not found. <a href="trade-products.php">Go back</a></p>');
}

$pdo->prepare('UPDATE b2b_products SET views = views + 1 WHERE id = ?')->execute([$id]);
$isOwner = $user && (int) $product['owner_id'] === (int) $user['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($product['title']) ?> — <?= e(SITE_NAME) ?> Trade</title>
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
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
        <?php if ($user): ?>
            <a href="create-listing.php">+ Sell Item</a>
            <a href="chat.php">Messages</a>
            <div class="nav-account">
                <button class="nav-account-trigger" type="button" onclick="toggleAccountMenu(event)" aria-label="Account menu">
                    <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                    <i data-lucide="chevron-down" class="lucide-icon"></i>
                </button>
                <div class="nav-account-menu">
                    <div class="nav-account-header">
                        <span class="nav-avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                        <div>
                            <div class="nav-account-name"><?= e($user['name']) ?></div>
                            <div class="nav-account-email"><?= e($user['email']) ?></div>
                        </div>
                    </div>
                    <div class="nav-menu-divider"></div>
                    <a href="dashboard.php"><i data-lucide="layout-dashboard" class="lucide-icon"></i> Dashboard</a>
                    <a href="profile.php?id=<?= (int) $user['id'] ?>"><i data-lucide="user" class="lucide-icon"></i> My Profile</a>
                    <a href="chat.php"><i data-lucide="message-circle" class="lucide-icon"></i> Messages</a>
                    <?php if (!empty($user['is_admin'])): ?><a href="admin.php"><i data-lucide="shield-check" class="lucide-icon"></i> Admin Panel</a><?php endif; ?>
                    <div class="nav-menu-divider"></div>
                    <a href="logout.php"><i data-lucide="log-out" class="lucide-icon"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="nav-btn">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<div class="trade-subnav">
    <a href="trade.php"><i data-lucide="store" class="lucide-icon"></i> Trade Home</a><span class="sep">|</span>
    <a href="trade-products.php"><i data-lucide="package" class="lucide-icon"></i> Browse Products</a><span class="sep">|</span>
    <a href="rfq-board.php"><i data-lucide="clipboard-list" class="lucide-icon"></i> RFQ (Request for Quotation) Board</a><span class="sep">|</span>
    <a href="trade-how-it-works.php"><i data-lucide="circle-help" class="lucide-icon"></i> How It Works</a>
    <?php if ($user): ?><span class="sep">|</span><a href="trade-dashboard.php"><i data-lucide="building-2" class="lucide-icon"></i> My Trade Dashboard</a><?php endif; ?>
</div>
<div class="container section">
    <div class="grid-2" style="grid-template-columns:1fr 1fr;align-items:start">
        <div class="card">
            <div class="b2b-product-img" style="height:340px;font-size:5rem">
                <?php if ($product['image_url']): ?><img src="<?= e($product['image_url']) ?>" alt=""><?php else: ?><?= catIcon($product['cat_icon']) ?><?php endif; ?>
            </div>
        </div>
        <div>
            <?php if ($product['cat_name']): ?><div style="font-size:.8rem;color:var(--text-light);margin-bottom:.4rem"><?= catIcon($product['cat_icon']) ?> <?= e($product['cat_name']) ?></div><?php endif; ?>
            <h1 style="font-size:1.5rem;margin-bottom:.6rem"><?= e($product['title']) ?></h1>
            <div class="b2b-product-price" style="font-size:1.6rem;margin-bottom:.8rem"><?= moneyRange((float) $product['price_min'], (float) $product['price_max']) ?> <span style="font-size:.9rem;color:var(--text-light)">/ <?= e($product['unit']) ?></span></div>
            <div style="display:flex;gap:1rem;margin-bottom:1.2rem;font-size:.88rem;color:var(--text-mid)">
                <span><strong>MOQ:</strong> <?= (int) $product['moq'] ?> <?= e($product['unit']) ?></span>
                <span><strong><i data-lucide="eye" class="lucide-icon"></i> Views:</strong> <?= (int) $product['views'] ?></span>
            </div>
            <p style="color:var(--text-mid);margin-bottom:1.5rem;line-height:1.6"><?= nl2br(e($product['description'])) ?></p>

            <div class="card" style="background:var(--cream);box-shadow:none;margin-bottom:1.2rem">
                <div class="card-body" style="display:flex;align-items:center;gap:1rem">
                    <div class="company-logo">
                        <?php if ($product['logo_url']): ?><img src="<?= e($product['logo_url']) ?>" alt=""><?php else: ?><?= e(mb_substr($product['company_name'], 0, 1)) ?><?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <a href="company.php?id=<?= (int) $product['company_id'] ?>" style="font-weight:700"><?= e($product['company_name']) ?></a>
                        <div style="font-size:.78rem;color:var(--text-light)"><i data-lucide="map-pin" class="lucide-icon"></i> <?= e($product['city'] ? $product['city'] . ', ' : '') . e($product['country']) ?></div>
                    </div>
                    <?= verifiedBadge($product['verification_status']) ?>
                </div>
            </div>

            <?php if ($isOwner): ?>
                <a href="edit-trade-product.php?id=<?= (int) $product['id'] ?>" class="btn btn-outline btn-full"><i data-lucide="pencil" class="lucide-icon"></i> Edit Product</a>
            <?php elseif ($user): ?>
                <a href="chat.php?with=<?= (int) $product['owner_id'] ?>" class="btn btn-primary btn-full"><i data-lucide="message-circle" class="lucide-icon"></i> Contact Supplier</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-full">Login to Contact Supplier</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
