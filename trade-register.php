<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$existing = myCompany($pdo, $user['id']);
if ($existing) {
    redirect('company-setup.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $companyName = trim($_POST['company_name'] ?? '');
    $role        = $_POST['role'] ?? '';
    $businessType = $_POST['business_type'] ?? 'manufacturer';
    $country     = trim($_POST['country'] ?? '');

    if (mb_strlen($companyName) < 2) $errors[] = 'Please enter your company name.';
    if (!in_array($role, ['buyer', 'supplier', 'both'], true)) $errors[] = 'Please select whether you are a Buyer, Supplier, or Both.';
    if ($country === '') $errors[] = 'Please select your country.';

    if (!$errors) {
        $pdo->prepare('INSERT INTO companies (user_id, company_name, role, business_type, country) VALUES (?, ?, ?, ?, ?)')
            ->execute([$user['id'], $companyName, $role, $businessType, $country]);
        $pdo->prepare('UPDATE users SET trade_role = ? WHERE id = ?')->execute([$role, $user['id']]);
        flash('success', 'Your company profile has been created. Complete your profile to get verified and start trading.');
        redirect('company-setup.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join SocialSouk Trade — <?= e(SITE_NAME) ?></title>
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
    <a href="trade-how-it-works.php"><i data-lucide="circle-help" class="lucide-icon"></i> How It Works</a>
    <?php if ($user): ?><span class="sep">|</span><a href="trade-dashboard.php"><i data-lucide="building-2" class="lucide-icon"></i> My Trade Dashboard</a><?php endif; ?>
</div>
<div class="container section" style="max-width:680px">
    <h2 class="section-title">Join <span>SocialSouk Trade</span></h2>
    <p class="section-sub">Set up your company profile to buy or sell wholesale, anywhere in the world.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-group">
                <label class="form-label">I want to join as a...</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem">
                    <label class="card" style="cursor:pointer;text-align:center;padding:1.2rem .6rem;margin:0">
                        <input type="radio" name="role" value="buyer" style="width:auto;margin-bottom:.5rem" required>
                        <div style="font-size:1.6rem"><i data-lucide="shopping-cart" class="lucide-icon"></i></div>
                        <div style="font-weight:600;font-size:.88rem">Buyer</div>
                        <div style="font-size:.75rem;color:var(--text-light)">Source products</div>
                    </label>
                    <label class="card" style="cursor:pointer;text-align:center;padding:1.2rem .6rem;margin:0">
                        <input type="radio" name="role" value="supplier" style="width:auto;margin-bottom:.5rem">
                        <div style="font-size:1.6rem"><i data-lucide="factory" class="lucide-icon"></i></div>
                        <div style="font-weight:600;font-size:.88rem">Supplier</div>
                        <div style="font-size:.75rem;color:var(--text-light)">Sell wholesale</div>
                    </label>
                    <label class="card" style="cursor:pointer;text-align:center;padding:1.2rem .6rem;margin:0">
                        <input type="radio" name="role" value="both" style="width:auto;margin-bottom:.5rem">
                        <div style="font-size:1.6rem"><i data-lucide="repeat" class="lucide-icon"></i></div>
                        <div style="font-weight:600;font-size:.88rem">Both</div>
                        <div style="font-size:.75rem;color:var(--text-light)">Buy & sell</div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. Al-Hashemi Trading Co." required>
            </div>

            <div class="form-group">
                <label class="form-label">Business Type</label>
                <select name="business_type" class="form-control">
                    <option value="manufacturer">Manufacturer</option>
                    <option value="trading_company">Trading Company</option>
                    <option value="distributor_wholesaler">Distributor / Wholesaler</option>
                    <option value="retailer">Retailer</option>
                    <option value="individual">Individual</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <select name="country" class="form-control" required>
                    <option value="">Select country</option>
                    <?php foreach (['Pakistan','India','Bangladesh','Saudi Arabia','United Arab Emirates','Qatar','Kuwait','Bahrain','Oman','Turkey','Egypt','Indonesia','Malaysia','Afghanistan','Iran','Iraq','Jordan','Lebanon','Morocco','Tunisia','Algeria','Nigeria','South Africa','Sri Lanka','United Kingdom','United States','China','Canada','Australia','Germany','France','Other'] as $c): ?>
                        <option value="<?= e($c) ?>"><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Create Company Profile</button>
        </form>
    </div></div>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
