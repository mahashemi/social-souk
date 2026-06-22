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
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="trade.php">Trade</a>
        <a href="rfq-board.php">RFQ Board</a>
        <?php if ($user): ?><span class="nav-user">👤 <?= e($user['name']) ?></span><a href="logout.php" class="nav-btn">Logout</a>
        <?php else: ?><a href="login.php" class="nav-btn">Login</a><?php endif; ?>
    </div>
</nav>

<div class="container section" style="max-width:760px">
    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <div class="rfq-card" style="margin-bottom:1.5rem">
        <div class="rfq-card-title"><?= e($rfq['cat_icon'] ?: '📋') ?> <?= e($rfq['product_name']) ?></div>
        <div class="rfq-card-meta">
            <span>📦 Qty: <?= (int) $rfq['quantity'] ?> <?= e($rfq['unit']) ?></span>
            <?php if ($rfq['target_price']): ?><span>💰 Target: $<?= number_format((float) $rfq['target_price'], 2) ?></span><?php endif; ?>
            <?php if ($rfq['destination_country']): ?><span>📍 <?= e($rfq['destination_country']) ?></span><?php endif; ?>
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
            <div class="empty-state"><div class="icon">📭</div><h3>No quotes yet</h3></div>
        <?php else: ?>
            <?php foreach ($quotes as $q): ?>
            <div class="rfq-quote-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <a href="company.php?id=<?= (int) $q['company_id'] ?>" style="font-weight:700"><?= e($q['company_name']) ?></a>
                    <?= verifiedBadge($q['verification_status']) ?>
                </div>
                <div style="font-size:1.2rem;font-weight:700;color:var(--green-deep);margin-bottom:.4rem">$<?= number_format((float) $q['quoted_price'], 2) ?></div>
                <p style="font-size:.88rem;color:var(--text-mid);margin-bottom:.6rem"><?= e($q['message']) ?></p>
                <a href="chat.php?with=<?= (int) $q['supplier_user_id'] ?>" class="btn btn-sm btn-outline">💬 Message Supplier</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php elseif ($myQuote): ?>
        <div class="alert alert-success">✓ You submitted a quote of $<?= number_format((float) $myQuote['quoted_price'], 2) ?> for this request.</div>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
