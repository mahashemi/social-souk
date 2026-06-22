<?php
require_once __DIR__ . '/db.php';
$user = auth();
$myCo = $user ? myCompany($pdo, $user['id']) : null;

$categories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();

$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM companies WHERE verification_status='verified') AS verified_suppliers,
            (SELECT COUNT(*) FROM companies) AS total_companies,
            (SELECT COUNT(*) FROM b2b_products WHERE is_active=1) AS total_products,
            (SELECT COUNT(*) FROM rfqs WHERE status='open') AS open_rfqs"
)->fetch();

$featured = $pdo->query(
    "SELECT p.*, c.company_name, c.verification_status, c.country, cat.icon AS cat_icon
     FROM b2b_products p JOIN companies c ON c.id = p.company_id
     LEFT JOIN b2b_categories cat ON cat.id = p.category_id
     WHERE p.is_active = 1 ORDER BY p.views DESC, p.created_at DESC LIMIT 8"
)->fetchAll();

$topSuppliers = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM b2b_products WHERE company_id = c.id AND is_active = 1) AS product_count
     FROM companies c WHERE c.verification_status = 'verified' ORDER BY c.created_at DESC LIMIT 6"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SocialSouk Trade — Verified B2B Suppliers & Buyers Worldwide</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Marketplace</a>
        <a href="trade-products.php">Products</a>
        <a href="rfq-board.php">RFQ Board</a>
        <?php if ($user): ?><span class="nav-user">👤 <?= e($user['name']) ?></span>
            <?php if ($myCo): ?><a href="trade-dashboard.php">Trade Dashboard</a><?php else: ?><a href="trade-register.php" class="nav-btn">Join as Trader</a><?php endif; ?>
            <a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="trade-register.php" class="nav-btn">Join as Trader</a>
        <?php endif; ?>
    </div>
</nav>

<header class="trade-hero">
    <div class="hero-content">
        <h1>Source & Sell <span style="color:var(--gold)">Wholesale</span>, Anywhere</h1>
        <p style="opacity:.9;max-width:560px;margin:0 auto">Verified suppliers and buyers, making it easy to do business anywhere and everywhere — built on trust, halal values, and barakah.</p>
        <form action="trade-products.php" method="get" class="trade-search-bar">
            <input type="text" name="q" placeholder="Search products, suppliers, or categories...">
            <button type="submit">Search</button>
        </form>
        <div class="trade-trust-bar">
            <span>✔️ <?= (int) $stats['verified_suppliers'] ?> Verified Suppliers</span>
            <span>📦 <?= (int) $stats['total_products'] ?> Products Listed</span>
            <span>📋 <?= (int) $stats['open_rfqs'] ?> Open Requests</span>
            <span>🛡️ Admin-Reviewed Suppliers</span>
        </div>
        <?php if (!$myCo): ?>
        <div style="margin-top:1.5rem">
            <a href="<?= $user ? 'trade-register.php' : 'register.php' ?>" class="btn btn-secondary">Join Free as Buyer or Supplier</a>
            <a href="rfq-submit.php" class="btn btn-secondary">Post a Request for Quotation</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<div class="container section">
    <h2 class="section-title">Browse by <span>Category</span></h2>
    <div class="trade-category-grid">
        <?php foreach ($categories as $c): ?>
        <a href="trade-products.php?category=<?= (int) $c['id'] ?>" class="trade-category-tile">
            <span class="icon"><?= e($c['icon']) ?></span>
            <span class="name"><?= e($c['name']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($featured): ?>
<div class="container section" style="padding-top:0">
    <div style="display:flex;justify-content:space-between;align-items:baseline">
        <h2 class="section-title">Featured <span>Products</span></h2>
        <a href="trade-products.php" class="chip-view-all">View All →</a>
    </div>
    <div class="grid-4">
        <?php foreach ($featured as $p): ?>
        <a href="trade-product.php?id=<?= (int) $p['id'] ?>" class="b2b-product-card" style="text-decoration:none;color:inherit">
            <div class="b2b-product-img">
                <?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt=""><?php else: ?><?= e($p['cat_icon'] ?: '📦') ?><?php endif; ?>
            </div>
            <div class="b2b-product-body">
                <div class="b2b-product-title"><?= e($p['title']) ?></div>
                <div class="b2b-product-price"><?= moneyRange((float) $p['price_min'], (float) $p['price_max']) ?></div>
                <div class="b2b-product-moq">MOQ: <?= (int) $p['moq'] ?> <?= e($p['unit']) ?></div>
            </div>
            <div class="b2b-product-footer">
                <span><?= e($p['company_name']) ?></span>
                <?php if ($p['verification_status'] === 'verified'): ?><span>✔️</span><?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($topSuppliers): ?>
<div class="container section" style="padding-top:0">
    <h2 class="section-title">Verified <span>Suppliers</span></h2>
    <div class="grid-3">
        <?php foreach ($topSuppliers as $c): ?>
        <a href="company.php?id=<?= (int) $c['id'] ?>" class="company-card" style="text-decoration:none;color:inherit;display:block">
            <div class="company-card-header">
                <div class="company-logo">
                    <?php if ($c['logo_url']): ?><img src="<?= e($c['logo_url']) ?>" alt=""><?php else: ?><?= e(mb_substr($c['company_name'], 0, 1)) ?><?php endif; ?>
                </div>
                <div>
                    <div class="company-card-name"><?= e($c['company_name']) ?></div>
                    <div class="company-card-meta">📍 <?= e($c['country']) ?> · <?= (int) $c['product_count'] ?> products</div>
                </div>
            </div>
            <?= verifiedBadge($c['verification_status']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="container section">
    <div class="grid-3">
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2rem;margin-bottom:.5rem">🛡️</div>
            <h3 style="font-size:1rem;margin-bottom:.4rem">Admin-Verified Suppliers</h3>
            <p style="font-size:.85rem;color:var(--text-mid)">Every Verified Supplier badge means our team has reviewed real business documents — not just a self-declared claim.</p>
        </div></div>
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2rem;margin-bottom:.5rem">📋</div>
            <h3 style="font-size:1rem;margin-bottom:.4rem">Request for Quotation</h3>
            <p style="font-size:.85rem;color:var(--text-mid)">Post exactly what you need and let suppliers compete for your business with real quotes.</p>
        </div></div>
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2rem;margin-bottom:.5rem">🤝</div>
            <h3 style="font-size:1rem;margin-bottom:.4rem">Trade with Barakah</h3>
            <p style="font-size:.85rem;color:var(--text-mid)">Built on the same halal values and trust as the rest of SocialSouk — for the global Muslim trading community.</p>
        </div></div>
    </div>
</div>

<footer>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> Trade. Built with ❤️ for the Ummah.</div>
</footer>
<script src="app.js" defer></script>
</body>
</html>
