<?php
require_once __DIR__ . '/db.php';
$user = auth();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.*, u.name AS buyer_name, cat.name AS cat_name, cat.icon AS cat_icon
     FROM rfqs r JOIN users u ON u.id = r.buyer_id LEFT JOIN b2b_categories cat ON cat.id = r.category_id
     WHERE r.id = ?"
);
$stmt->execute([$id]);
$rfq = $stmt->fetch();

if (!$rfq) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Request not found. <a href="rfq-board.php">Go back</a></p>');
}

$isBuyer = $user && (int) $rfq['buyer_id'] === (int) $user['id'];
$myCompany = $user ? myCompany($pdo, $user['id']) : null;
$isSupplier = $myCompany && in_array($myCompany['role'], ['supplier', 'both'], true);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quoted_price'])) {
    requireAuth();
    verifyCsrf();
    if (!$isSupplier) {
        $errors[] = 'Only suppliers can submit quotes.';
    } else {
        $quotedPrice = (float) ($_POST['quoted_price'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($quotedPrice <= 0) $errors[] = 'Please enter a valid quoted price.';
        if (!$errors) {
            $pdo->prepare('INSERT INTO rfq_quotes (rfq_id, company_id, quoted_price, message) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quoted_price=?, message=?')
                ->execute([$id, $myCompany['id'], $quotedPrice, $message, $quotedPrice, $message]);
            flash('success', 'Your quote has been submitted.');
            redirect('rfq-detail.php?id=' . $id);
        }
    }
}

// Buyer sees all quotes; a supplier sees only their own; others see none.
if ($isBuyer) {
    $quotes = $pdo->prepare(
        "SELECT q.*, c.company_name, c.verification_status, c.id AS company_id, c.user_id AS supplier_user_id
         FROM rfq_quotes q JOIN companies c ON c.id = q.company_id WHERE q.rfq_id = ? ORDER BY q.quoted_price ASC"
    );
    $quotes->execute([$id]);
    $quotes = $quotes->fetchAll();
} elseif ($myCompany) {
    $quotes = $pdo->prepare(
        "SELECT q.*, c.company_name, c.verification_status, c.id AS company_id, c.user_id AS supplier_user_id
         FROM rfq_quotes q JOIN companies c ON c.id = q.company_id WHERE q.rfq_id = ? AND q.company_id = ?"
    );
    $quotes->execute([$id, $myCompany['id']]);
    $quotes = $quotes->fetchAll();
} else {
    $quotes = [];
}
$myQuote = null;
if ($myCompany) {
    foreach ($quotes as $q) { if ((int) $q['company_id'] === (int) $myCompany['id']) $myQuote = $q; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($rfq['product_name']) ?> RFQ — <?= e(SITE_NAME) ?> Trade</title>
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
<div class="container section" style="max-width:760px">
    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="rfq-card" style="margin-bottom:1.5rem">
        <div class="rfq-card-title"><?= catIcon($rfq['cat_icon'] ?: 'clipboard-list') ?> <?= e($rfq['product_name']) ?></div>
        <div class="rfq-card-meta">
            <span><i data-lucide="package" class="lucide-icon"></i> Qty: <?= (int) $rfq['quantity'] ?> <?= e($rfq['unit']) ?></span>
            <?php if ($rfq['target_price']): ?><span><i data-lucide="circle-dollar-sign" class="lucide-icon"></i> Target: $<?= number_format((float) $rfq['target_price'], 2) ?></span><?php endif; ?>
            <?php if ($rfq['destination_country']): ?><span><i data-lucide="map-pin" class="lucide-icon"></i> <?= e($rfq['destination_country']) ?></span><?php endif; ?>
            <span class="badge <?= $rfq['status']==='open'?'badge-active':'badge-closed' ?>"><?= e(ucfirst($rfq['status'])) ?></span>
        </div>
        <p style="color:var(--text-mid);margin:.8rem 0"><?= nl2br(e($rfq['description'])) ?></p>
        <div style="font-size:.8rem;color:var(--text-light)">Posted by <?= e($rfq['buyer_name']) ?> on <?= date('M j, Y', strtotime($rfq['created_at'])) ?></div>
    </div>

    <?php if ($isSupplier && !$isBuyer): ?>
        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)"><?= $myQuote ? 'Update Your Quote' : 'Submit a Quote' ?></h3>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Your Quoted Price (USD per <?= e($rfq['unit']) ?>)</label>
                        <input type="number" name="quoted_price" class="form-control" step="0.01" min="0" value="<?= e((string) ($myQuote['quoted_price'] ?? '')) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Message to Buyer</label>
                    <textarea name="message" class="form-control" rows="3"><?= e($myQuote['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-full"><?= $myQuote ? 'Update Quote' : 'Submit Quote' ?></button>
            </form>
        </div></div>
    <?php elseif (!$user): ?>
        <div class="alert alert-info">Login as a supplier to respond to this request. <a href="login.php">Login</a></div>
    <?php endif; ?>

    <?php if ($isBuyer): ?>
        <h3 style="font-size:1.1rem;color:var(--green-deep);margin-bottom:1rem">Quotes Received (<?= count($quotes) ?>)</h3>
        <?php if (!$quotes): ?>
            <div class="empty-state"><div class="icon"><i data-lucide="inbox" class="lucide-icon"></i></div><h3>No quotes yet</h3></div>
        <?php else: ?>
            <?php foreach ($quotes as $q): ?>
            <div class="rfq-quote-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <a href="company.php?id=<?= (int) $q['company_id'] ?>" style="font-weight:700"><?= e($q['company_name']) ?></a>
                    <?= verifiedBadge($q['verification_status']) ?>
                </div>
                <div style="font-size:1.2rem;font-weight:700;color:var(--green-deep);margin-bottom:.4rem">$<?= number_format((float) $q['quoted_price'], 2) ?></div>
                <p style="font-size:.88rem;color:var(--text-mid);margin-bottom:.6rem"><?= e($q['message']) ?></p>
                <a href="chat.php?with=<?= (int) $q['supplier_user_id'] ?>" class="btn btn-sm btn-outline"><i data-lucide="message-circle" class="lucide-icon"></i> Message Supplier</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php elseif ($myQuote): ?>
        <div class="alert alert-success"><i data-lucide="check" class="lucide-icon"></i> You submitted a quote of $<?= number_format((float) $myQuote['quoted_price'], 2) ?> for this request.</div>
    <?php endif; ?>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
