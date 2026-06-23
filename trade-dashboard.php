<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$company = myCompany($pdo, $user['id']);
if (!$company) {
    redirect('trade-register.php');
}

$products = $pdo->prepare('SELECT p.*, cat.name AS cat_name FROM b2b_products p LEFT JOIN b2b_categories cat ON cat.id = p.category_id WHERE p.company_id = ? ORDER BY p.created_at DESC');
$products->execute([$company['id']]);
$products = $products->fetchAll();

$myQuotes = $pdo->prepare(
    "SELECT q.*, r.product_name, r.quantity, r.unit, r.status AS rfq_status
     FROM rfq_quotes q JOIN rfqs r ON r.id = q.rfq_id WHERE q.company_id = ? ORDER BY q.created_at DESC"
);
$myQuotes->execute([$company['id']]);
$myQuotes = $myQuotes->fetchAll();

$myRfqs = $pdo->prepare(
    "SELECT r.*, (SELECT COUNT(*) FROM rfq_quotes q WHERE q.rfq_id = r.id) AS quote_count
     FROM rfqs r WHERE r.buyer_id = ? ORDER BY r.created_at DESC"
);
$myRfqs->execute([$user['id']]);
$myRfqs = $myRfqs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trade Dashboard — <?= e(SITE_NAME) ?></title>
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
<div class="dashboard-wrap">
    <div class="dashboard-header">
        <h2><i data-lucide="building-2" class="lucide-icon"></i> <?= e($company['company_name']) ?></h2>
        <p>Trade Dashboard</p>
        <?= verifiedBadge($company['verification_status']) ?>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>

    <?php if (in_array($company['role'], ['supplier', 'both'], true)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-size:1.1rem;color:var(--green-deep)">My Products (<?= count($products) ?>)</h3>
        <a href="add-trade-product.php" class="btn btn-primary btn-sm">+ List Product</a>
    </div>
    <?php if (!$products): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="package" class="lucide-icon"></i></div><h3>No products listed yet</h3></div>
    <?php else: ?>
    <table class="table" style="margin-bottom:2rem">
        <thead><tr><th>Title</th><th>Category</th><th>Price</th><th>MOQ</th><th>Views</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td data-label="Title"><a href="trade-product.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a></td>
                <td data-label="Category"><?= e($p['cat_name'] ?? '—') ?></td>
                <td data-label="Price"><?= moneyRange((float) $p['price_min'], (float) $p['price_max']) ?></td>
                <td data-label="MOQ"><?= (int) $p['moq'] ?> <?= e($p['unit']) ?></td>
                <td data-label="Views"><?= (int) $p['views'] ?></td>
                <td data-label="Status"><span class="badge <?= $p['is_active'] ? 'badge-free' : 'badge-paid' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td data-label="Actions" class="action-row">
                    <a href="edit-trade-product.php?id=<?= (int) $p['id'] ?>" class="icon-btn" data-tip="Edit" aria-label="Edit"><i data-lucide="pencil" class="lucide-icon"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3 style="font-size:1.1rem;color:var(--green-deep);margin-bottom:1rem">My Quotes Submitted (<?= count($myQuotes) ?>)</h3>
    <?php if (!$myQuotes): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="clipboard-list" class="lucide-icon"></i></div><h3>No quotes submitted yet</h3><p><a href="rfq-board.php" class="btn btn-primary" style="margin-top:1rem">Browse Open Requests</a></p></div>
    <?php else: ?>
    <table class="table" style="margin-bottom:2rem">
        <thead><tr><th>Request</th><th>Quantity</th><th>My Quote</th><th>RFQ Status</th></tr></thead>
        <tbody>
            <?php foreach ($myQuotes as $q): ?>
            <tr>
                <td data-label="Request"><?= e($q['product_name']) ?></td>
                <td data-label="Quantity"><?= (int) $q['quantity'] ?> <?= e($q['unit']) ?></td>
                <td data-label="My Quote">$<?= number_format((float) $q['quoted_price'], 2) ?></td>
                <td data-label="RFQ Status"><span class="badge <?= $q['rfq_status']==='open'?'badge-active':'badge-closed' ?>"><?= e(ucfirst($q['rfq_status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array($company['role'], ['buyer', 'both'], true)): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-size:1.1rem;color:var(--green-deep)">My Requests for Quotation (<?= count($myRfqs) ?>)</h3>
        <a href="rfq-submit.php" class="btn btn-primary btn-sm">+ Post Request</a>
    </div>
    <?php if (!$myRfqs): ?>
        <div class="empty-state"><div class="icon"><i data-lucide="clipboard-list" class="lucide-icon"></i></div><h3>You haven't posted any requests yet</h3></div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Product</th><th>Quantity</th><th>Quotes Received</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($myRfqs as $r): ?>
            <tr>
                <td data-label="Product"><a href="rfq-detail.php?id=<?= (int) $r['id'] ?>"><?= e($r['product_name']) ?></a></td>
                <td data-label="Quantity"><?= (int) $r['quantity'] ?> <?= e($r['unit']) ?></td>
                <td data-label="Quotes Received"><?= (int) $r['quote_count'] ?></td>
                <td data-label="Status"><span class="badge <?= $r['status']==='open'?'badge-active':'badge-closed' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                <td data-label="Actions"><a href="rfq-detail.php?id=<?= (int) $r['id'] ?>" class="icon-btn" data-tip="View" aria-label="View"><i data-lucide="eye" class="lucide-icon"></i></a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endif; ?>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
