<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

$stmt = $pdo->prepare('SELECT c.*, u.name AS owner_name FROM companies c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->execute([$id]);
$company = $stmt->fetch();

if (!$company) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Company not found. <a href="trade.php">Go back</a></p>');
}

$isOwner = $user && (int) $company['user_id'] === (int) $user['id'];

$products = $pdo->prepare(
    "SELECT p.*, cat.name AS cat_name, cat.icon AS cat_icon FROM b2b_products p
     LEFT JOIN b2b_categories cat ON cat.id = p.category_id
     WHERE p.company_id = ? AND p.is_active = 1 ORDER BY p.created_at DESC"
);
$products->execute([$id]);
$products = $products->fetchAll();

$certs = $pdo->prepare('SELECT * FROM company_certifications WHERE company_id = ? ORDER BY created_at DESC');
$certs->execute([$id]);
$certs = $certs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($company['company_name']) ?> — <?= e(SITE_NAME) ?> Trade</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="trade.php">Trade</a>
        <?php if ($user): ?><span class="nav-user">👤 <?= e($user['name']) ?></span><a href="dashboard.php">Dashboard</a><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div style="background:linear-gradient(135deg, var(--green-deep), #0d2818); height:140px;<?= $company['banner_url'] ? 'background-image:url(' . e($company['banner_url']) . ');background-size:cover;background-position:center;' : '' ?>"></div>

<div class="container" style="margin-top:-50px">
    <div class="card" style="padding:1.5rem">
        <div style="display:flex;gap:1.2rem;align-items:flex-start;flex-wrap:wrap">
            <div class="company-logo" style="width:80px;height:80px;font-size:2rem">
                <?php if ($company['logo_url']): ?><img src="<?= e($company['logo_url']) ?>" alt=""><?php else: ?><?= e(mb_substr($company['company_name'], 0, 1)) ?><?php endif; ?>
            </div>
            <div style="flex:1;min-width:200px">
                <h1 style="font-size:1.4rem"><?= e($company['company_name']) ?></h1>
                <div style="display:flex;gap:.6rem;align-items:center;margin:.4rem 0;flex-wrap:wrap">
                    <?= verifiedBadge($company['verification_status']) ?>
                    <span class="badge" style="background:#f5f5f5;color:#555"><?= e(ucfirst(str_replace('_', ' ', $company['business_type']))) ?></span>
                    <span style="font-size:.85rem;color:var(--text-light)">📍 <?= e($company['city'] ? $company['city'] . ', ' : '') . e($company['country']) ?></span>
                </div>
                <p style="color:var(--text-mid);font-size:.9rem;max-width:600px"><?= e($company['main_products'] ?: 'No main products listed yet.') ?></p>
            </div>
            <?php if ($isOwner): ?>
                <a href="company-setup.php" class="btn btn-outline btn-sm">✏️ Edit Profile</a>
            <?php elseif ($user): ?>
                <a href="chat.php?with=<?= (int) $company['user_id'] ?>" class="btn btn-primary btn-sm">💬 Contact Supplier</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-tabs" style="margin-top:1.5rem">
        <a href="?id=<?= $id ?>&tab=overview" class="profile-tab <?= $tab==='overview'?'active':'' ?>">Overview</a>
        <a href="?id=<?= $id ?>&tab=products" class="profile-tab <?= $tab==='products'?'active':'' ?>">Products (<?= count($products) ?>)</a>
        <a href="?id=<?= $id ?>&tab=production" class="profile-tab <?= $tab==='production'?'active':'' ?>">Production Capacity</a>
        <a href="?id=<?= $id ?>&tab=trade" class="profile-tab <?= $tab==='trade'?'active':'' ?>">Trade Capacity</a>
        <a href="?id=<?= $id ?>&tab=certifications" class="profile-tab <?= $tab==='certifications'?'active':'' ?>">Certifications (<?= count($certs) ?>)</a>
    </div>

    <div class="section" style="padding-top:1rem">
    <?php if ($tab === 'overview'): ?>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Company Overview</h3>
            <p style="color:var(--text-mid);margin-bottom:1.5rem"><?= e($company['description'] ?: 'No description provided yet.') ?></p>
            <div class="grid-3" style="gap:1rem">
                <div><div style="font-size:.78rem;color:var(--text-light)">Year Established</div><div style="font-weight:600"><?= e((string) ($company['year_established'] ?? '—')) ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Employees</div><div style="font-weight:600"><?= e($company['employee_count'] ?? '—') ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Account Type</div><div style="font-weight:600"><?= e(ucfirst($company['role'])) ?></div></div>
            </div>
        </div></div>
    <?php elseif ($tab === 'products'): ?>
        <?php if (!$products): ?>
            <div class="empty-state"><div class="icon">📦</div><h3>No products listed yet</h3></div>
        <?php else: ?>
        <div class="grid-3">
            <?php foreach ($products as $p): ?>
            <a href="trade-product.php?id=<?= (int) $p['id'] ?>" class="b2b-product-card" style="text-decoration:none;color:inherit">
                <div class="b2b-product-img">
                    <?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt=""><?php else: ?><?= e($p['cat_icon'] ?: '📦') ?><?php endif; ?>
                </div>
                <div class="b2b-product-body">
                    <div class="b2b-product-title"><?= e($p['title']) ?></div>
                    <div class="b2b-product-price"><?= moneyRange((float) $p['price_min'], (float) $p['price_max']) ?></div>
                    <div class="b2b-product-moq">MOQ: <?= (int) $p['moq'] ?> <?= e($p['unit']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php elseif ($tab === 'production'): ?>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Production Capacity</h3>
            <div class="grid-2" style="gap:1rem">
                <div><div style="font-size:.78rem;color:var(--text-light)">Factory Size</div><div style="font-weight:600"><?= $company['factory_size_sqm'] ? number_format($company['factory_size_sqm']) . ' sqm' : '—' ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Production Lines</div><div style="font-weight:600"><?= e((string) ($company['production_lines'] ?? '—')) ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Monthly Output</div><div style="font-weight:600"><?= e($company['monthly_output'] ?: '—') ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">R&D Staff</div><div style="font-weight:600"><?= e((string) ($company['rd_staff_count'] ?? '—')) ?></div></div>
            </div>
        </div></div>
    <?php elseif ($tab === 'trade'): ?>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Trade Capacity</h3>
            <div class="grid-2" style="gap:1rem">
                <div><div style="font-size:.78rem;color:var(--text-light)">Importer / Exporter</div><div style="font-weight:600"><?= $company['is_importer'] ? 'Importer ' : '' ?><?= $company['is_exporter'] ? 'Exporter' : '' ?><?= (!$company['is_importer'] && !$company['is_exporter']) ? '—' : '' ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Main Markets</div><div style="font-weight:600"><?= e($company['main_export_markets'] ?: '—') ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Nearest Port</div><div style="font-weight:600"><?= e($company['nearest_port'] ?: '—') ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Avg. Lead Time</div><div style="font-weight:600"><?= $company['avg_lead_time_days'] ? (int) $company['avg_lead_time_days'] . ' days' : '—' ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Accepted Currencies</div><div style="font-weight:600"><?= e($company['accepted_currencies'] ?: '—') ?></div></div>
                <div><div style="font-size:.78rem;color:var(--text-light)">Payment Methods</div><div style="font-weight:600"><?= e($company['accepted_payment_methods'] ?: '—') ?></div></div>
            </div>
        </div></div>
    <?php elseif ($tab === 'certifications'): ?>
        <?php if (!$certs): ?>
            <div class="empty-state"><div class="icon">📜</div><h3>No certifications listed yet</h3></div>
        <?php else: ?>
            <?php foreach ($certs as $c): ?>
            <div class="card" style="margin-bottom:.8rem"><div class="card-body" style="display:flex;justify-content:space-between;align-items:center">
                <div><strong><?= e($c['name']) ?></strong><?= $c['issuing_body'] ? ' — ' . e($c['issuing_body']) : '' ?></div>
                <?php if ($c['file_url']): ?><a href="<?= e($c['file_url']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a><?php endif; ?>
            </div></div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<footer>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Built with ❤️ for the Ummah.</div>
</footer>
<script src="app.js" defer></script>
</body>
</html>
