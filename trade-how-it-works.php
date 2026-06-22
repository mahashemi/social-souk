<?php
require_once __DIR__ . '/db.php';
$user = auth();
$tab = $_GET['as'] ?? 'buyer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>How SocialSouk Trade Works — <?= e(SITE_NAME) ?></title>
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
        <?php if ($user): ?><a href="profile.php?id=<?= (int) $user['id'] ?>" class="nav-user"><i data-lucide="user" class="lucide-icon"></i> <?= e($user['name']) ?></a>
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
    <a href="trade.php"><i data-lucide="store" class="lucide-icon"></i> Trade Home</a><span class="sep">|</span>
    <a href="trade-products.php"><i data-lucide="package" class="lucide-icon"></i> Browse Products</a><span class="sep">|</span>
    <a href="rfq-board.php"><i data-lucide="clipboard-list" class="lucide-icon"></i> RFQ (Request for Quotation) Board</a><span class="sep">|</span>
    <a href="trade-how-it-works.php" class="active"><i data-lucide="circle-help" class="lucide-icon"></i> How It Works</a>
    <?php if ($user): ?><span class="sep">|</span><a href="trade-dashboard.php"><i data-lucide="building-2" class="lucide-icon"></i> My Trade Dashboard</a><?php endif; ?>
</div>

<header class="trade-hero" style="padding:3rem 1.5rem">
    <div class="hero-content">
        <h1>How SocialSouk <span style="color:var(--gold)">Trade</span> Works</h1>
        <p style="opacity:.9">Verified suppliers and buyers, making it easy to do business anywhere and everywhere.</p>
    </div>
</header>

<div class="container section">
    <div class="profile-tabs" style="justify-content:center">
        <a href="?as=buyer" class="profile-tab <?= $tab==='buyer'?'active':'' ?>">I'm a Buyer</a>
        <a href="?as=supplier" class="profile-tab <?= $tab==='supplier'?'active':'' ?>">I'm a Supplier</a>
    </div>

    <?php if ($tab === 'buyer'): ?>
    <div class="grid-2" style="gap:1.5rem">
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">1</span> <i data-lucide="search" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Find Products & Suppliers</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Search <a href="trade-products.php">Browse Products</a> by keyword or category. Look for the <span class="badge-verified" style="font-size:.68rem"><i data-lucide="check" class="lucide-icon"></i> Verified Supplier</span> badge — it means our admin team has reviewed that company's real business documents, not just a self-declared claim.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">2</span> <i data-lucide="clipboard-list" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Or Post an RFQ</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Can't find exactly what you need? <a href="rfq-submit.php">Post a Request for Quotation (RFQ)</a> — describe the product, quantity, and target price. Suppliers come to you with competing quotes instead of you searching one by one.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">3</span> <i data-lucide="message-circle" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Connect Directly</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Message any supplier directly through our built-in chat — ask about samples, lead times, certifications, or anything else before committing.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">4</span> <i data-lucide="handshake" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Negotiate & Agree Terms</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Confirm price, MOQ (Minimum Order Quantity), payment method, and delivery timeline directly with the supplier through chat.</p>
        </div></div>
        <div class="card" style="grid-column:1/-1"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">5</span> <i data-lucide="package" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Finalize the Deal</h3>
            <p style="color:var(--text-mid);font-size:.92rem"><strong>Important:</strong> SocialSouk Trade is a discovery and connection platform — we help you find and vet verified suppliers and communicate directly. We do not currently process payments or hold funds in escrow (no "Trade Assurance" style payment protection yet). Always agree on clear terms, request samples where possible, and use a payment method you trust before sending money.</p>
        </div></div>
    </div>
    <?php else: ?>
    <div class="grid-2" style="gap:1.5rem">
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">1</span> <i data-lucide="building-2" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Create Your Company Profile</h3>
            <p style="color:var(--text-mid);font-size:.92rem"><a href="trade-register.php">Register as a Supplier</a> and fill in your Company Overview, Production Capacity, and Trade Capacity details — buyers judge suppliers on these before reaching out.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">2</span> <i data-lucide="file-text" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Get Verified</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Upload your business license from <a href="company-setup.php">Company Profile</a>. An admin reviews it and grants the <span class="badge-verified" style="font-size:.68rem"><i data-lucide="check" class="lucide-icon"></i> Verified Supplier</span> badge — this is the single biggest trust signal buyers look for.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">3</span> <i data-lucide="package" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">List Your Products</h3>
            <p style="color:var(--text-mid);font-size:.92rem"><a href="add-trade-product.php">List products</a> with clear pricing ranges, MOQ (Minimum Order Quantity), and photos. Listings appear in category browsing and search immediately.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">4</span> <i data-lucide="clipboard-list" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Respond to RFQs</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Check the <a href="rfq-board.php">RFQ Board</a> regularly — buyers post exactly what they need. Submit a competitive quote with your price and a short message.</p>
        </div></div>
        <div class="card" style="grid-column:1/-1"><div class="card-body">
            <div style="font-size:1.8rem;margin-bottom:.6rem"><span class="step-num">5</span> <i data-lucide="sprout" class="lucide-icon"></i></div>
            <h3 style="margin-bottom:.5rem">Grow Your Trade</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Respond quickly to buyer messages, keep your product listings up to date, and your Verified Supplier badge will keep building trust as more buyers discover you.</p>
        </div></div>
    </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2.5rem">
        <a href="<?= $user ? 'trade-dashboard.php' : 'trade-register.php' ?>" class="btn btn-primary">Get Started</a>
    </div>
</div>

<footer>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?> Trade. Built with <i data-lucide="heart" class="lucide-icon"></i> for the Ummah.</div>
</footer>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
