<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();

$company = myCompany($pdo, $user['id']);
if (!$company) {
    redirect('trade-register.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $companyName     = trim($_POST['company_name'] ?? '');
    $role            = $_POST['role'] ?? $company['role'];
    $businessType    = $_POST['business_type'] ?? $company['business_type'];
    $yearEstablished = (int) ($_POST['year_established'] ?? 0) ?: null;
    $employeeCount   = $_POST['employee_count'] ?? null;
    $country         = trim($_POST['country'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $mainProducts    = trim($_POST['main_products'] ?? '');
    $mainMarkets     = trim($_POST['main_export_markets'] ?? '');
    $isImporter      = isset($_POST['is_importer']) ? 1 : 0;
    $isExporter      = isset($_POST['is_exporter']) ? 1 : 0;
    $annualRevenue   = $_POST['annual_revenue'] ?? null;
    $description     = trim($_POST['description'] ?? '');

    $factorySize     = (int) ($_POST['factory_size_sqm'] ?? 0) ?: null;
    $productionLines = (int) ($_POST['production_lines'] ?? 0) ?: null;
    $monthlyOutput   = trim($_POST['monthly_output'] ?? '');
    $rdStaff         = (int) ($_POST['rd_staff_count'] ?? 0) ?: null;

    $nearestPort     = trim($_POST['nearest_port'] ?? '');
    $currencies      = trim($_POST['accepted_currencies'] ?? 'USD');
    $paymentMethods  = trim($_POST['accepted_payment_methods'] ?? '');
    $leadTime        = (int) ($_POST['avg_lead_time_days'] ?? 0) ?: null;

    if (mb_strlen($companyName) < 2) $errors[] = 'Please enter your company name.';
    if ($country === '') $errors[] = 'Please select your country.';

    if (!$errors) {
        $logoPath = handleImageUpload('logo', 'companies') ?? $company['logo_url'];
        $bannerPath = handleImageUpload('banner', 'companies') ?? $company['banner_url'];
        $licensePath = handleImageUpload('business_license', 'companies') ?? $company['business_license_url'];

        // Uploading a business license (re-)submits the company for review.
        $newStatus = $company['verification_status'];
        if ($licensePath && $licensePath !== $company['business_license_url'] && $newStatus !== 'verified') {
            $newStatus = 'pending';
        }

        $pdo->prepare(
            'UPDATE companies SET company_name=?, role=?, business_type=?, year_established=?, employee_count=?,
                country=?, city=?, address=?, main_products=?, main_export_markets=?, is_importer=?, is_exporter=?,
                annual_revenue=?, description=?, factory_size_sqm=?, production_lines=?, monthly_output=?, rd_staff_count=?,
                nearest_port=?, accepted_currencies=?, accepted_payment_methods=?, avg_lead_time_days=?,
                logo_url=?, banner_url=?, business_license_url=?, verification_status=?, updated_at=NOW()
             WHERE id=?'
        )->execute([
            $companyName, $role, $businessType, $yearEstablished, $employeeCount,
            $country, $city, $address, $mainProducts, $mainMarkets, $isImporter, $isExporter,
            $annualRevenue, $description, $factorySize, $productionLines, $monthlyOutput, $rdStaff,
            $nearestPort, $currencies, $paymentMethods, $leadTime,
            $logoPath, $bannerPath, $licensePath, $newStatus, $company['id'],
        ]);
        $pdo->prepare('UPDATE users SET trade_role = ? WHERE id = ?')->execute([$role, $user['id']]);

        flash('success', 'Company profile updated.');
        redirect('company-setup.php');
    }
    $company = myCompany($pdo, $user['id']);
}

$certs = $pdo->prepare('SELECT * FROM company_certifications WHERE company_id = ? ORDER BY created_at DESC');
$certs->execute([$company['id']]);
$certs = $certs->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cert'])) {
    verifyCsrf();
    $certName = trim($_POST['cert_name'] ?? '');
    $issuingBody = trim($_POST['issuing_body'] ?? '');
    if ($certName !== '') {
        $certFile = handleImageUpload('cert_file', 'certifications');
        $pdo->prepare('INSERT INTO company_certifications (company_id, name, issuing_body, file_url) VALUES (?, ?, ?, ?)')
            ->execute([$company['id'], $certName, $issuingBody, $certFile]);
        redirect('company-setup.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Profile — <?= e(SITE_NAME) ?></title>
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
<div class="dashboard-wrap" style="max-width:760px">
    <div class="dashboard-header">
        <h2><i data-lucide="building-2" class="lucide-icon"></i> Company Profile</h2>
        <p><?= e($company['company_name']) ?></p>
        <?= verifiedBadge($company['verification_status']) ?>
    </div>

    <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

    <?php if ($company['verification_status'] === 'unverified'): ?>
        <div class="alert alert-info"><i data-lucide="clipboard-list" class="lucide-icon"></i> Upload your business license below to submit your company for admin verification and earn the <strong>Verified Supplier</strong> badge.</div>
    <?php elseif ($company['verification_status'] === 'rejected'): ?>
        <div class="alert alert-error"><i data-lucide="ban" class="lucide-icon"></i> Your verification was rejected. Please review your documents and re-upload your business license.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Company Overview</h3>
            <div class="form-group">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-control" value="<?= e($company['company_name']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Account Type</label>
                    <select name="role" class="form-control">
                        <option value="buyer" <?= $company['role']==='buyer'?'selected':'' ?>>Buyer</option>
                        <option value="supplier" <?= $company['role']==='supplier'?'selected':'' ?>>Supplier</option>
                        <option value="both" <?= $company['role']==='both'?'selected':'' ?>>Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Business Type</label>
                    <select name="business_type" class="form-control">
                        <?php foreach (['manufacturer'=>'Manufacturer','trading_company'=>'Trading Company','distributor_wholesaler'=>'Distributor / Wholesaler','retailer'=>'Retailer','individual'=>'Individual'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $company['business_type']===$k?'selected':'' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Year Established</label>
                    <input type="number" name="year_established" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= e((string) ($company['year_established'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Employees</label>
                    <select name="employee_count" class="form-control">
                        <option value="">Select range</option>
                        <?php foreach (['1-10','11-50','51-200','201-500','500+'] as $r): ?>
                        <option value="<?= $r ?>" <?= $company['employee_count']===$r?'selected':'' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="<?= e($company['country']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($company['city'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= e($company['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Main Products</label>
                <input type="text" name="main_products" class="form-control" placeholder="e.g. Cotton Textiles, Leather Goods" value="<?= e($company['main_products'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Company Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($company['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Company Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Banner</label>
                    <input type="file" name="banner" class="form-control" accept="image/*">
                </div>
            </div>
        </div></div>

        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Production Capacity</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Factory Size (sqm)</label>
                    <input type="number" name="factory_size_sqm" class="form-control" value="<?= e((string) ($company['factory_size_sqm'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Production Lines</label>
                    <input type="number" name="production_lines" class="form-control" value="<?= e((string) ($company['production_lines'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Monthly Output</label>
                    <input type="text" name="monthly_output" class="form-control" placeholder="e.g. 50,000 units/month" value="<?= e($company['monthly_output'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">R&D Staff Count</label>
                    <input type="number" name="rd_staff_count" class="form-control" value="<?= e((string) ($company['rd_staff_count'] ?? '')) ?>">
                </div>
            </div>
        </div></div>

        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Trade Capacity</h3>
            <div class="form-row">
                <div class="form-group" style="display:flex;align-items:center;gap:.5rem;margin-top:1.6rem">
                    <input type="checkbox" name="is_importer" value="1" style="width:auto" <?= $company['is_importer'] ? 'checked' : '' ?>>
                    <label class="form-label" style="margin:0">We Import</label>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:.5rem;margin-top:1.6rem">
                    <input type="checkbox" name="is_exporter" value="1" style="width:auto" <?= $company['is_exporter'] ? 'checked' : '' ?>>
                    <label class="form-label" style="margin:0">We Export</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Main Export/Import Markets</label>
                <input type="text" name="main_export_markets" class="form-control" placeholder="e.g. UAE, Saudi Arabia, UK" value="<?= e($company['main_export_markets'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nearest Port</label>
                    <input type="text" name="nearest_port" class="form-control" value="<?= e($company['nearest_port'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Avg. Lead Time (days)</label>
                    <input type="number" name="avg_lead_time_days" class="form-control" value="<?= e((string) ($company['avg_lead_time_days'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Accepted Currencies</label>
                    <input type="text" name="accepted_currencies" class="form-control" value="<?= e($company['accepted_currencies'] ?? 'USD') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Methods</label>
                    <input type="text" name="accepted_payment_methods" class="form-control" placeholder="e.g. T/T, L/C, Escrow" value="<?= e($company['accepted_payment_methods'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Annual Revenue</label>
                <select name="annual_revenue" class="form-control">
                    <option value="">Prefer not to say</option>
                    <?php foreach (['below_1m'=>'Below $1M','1m_10m'=>'$1M - $10M','10m_50m'=>'$10M - $50M','50m_above'=>'$50M+'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $company['annual_revenue']===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div></div>

        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Verification</h3>
            <div class="form-group">
                <label class="form-label">Business License / Registration Document</label>
                <input type="file" name="business_license" class="form-control" accept="image/*,.pdf">
                <div class="form-hint"><?= $company['business_license_url'] ? '<i data-lucide="check" class="lucide-icon"></i> A document is already on file.' : 'No document uploaded yet.' ?> Uploading a new one will submit your company for admin review.</div>
            </div>
        </div></div>

        <button type="submit" class="btn btn-primary btn-full">Save Company Profile</button>
    </form>

    <div class="card" style="margin-top:1.5rem"><div class="card-body">
        <h3 style="font-size:1rem;margin-bottom:1rem;color:var(--green-deep)">Certifications & Trademarks</h3>
        <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr auto;gap:.6rem;align-items:end;margin-bottom:1.2rem">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
            <div class="form-group" style="margin:0"><label class="form-label">Certification Name</label><input type="text" name="cert_name" class="form-control" required></div>
            <div class="form-group" style="margin:0"><label class="form-label">Issuing Body</label><input type="text" name="issuing_body" class="form-control"></div>
            <button type="submit" name="add_cert" value="1" class="btn btn-primary">+ Add</button>
            <input type="file" name="cert_file" accept="image/*,.pdf" style="grid-column:1/3">
        </form>
        <?php if (!$certs): ?>
            <p style="color:var(--text-light);font-size:.88rem">No certifications added yet.</p>
        <?php else: ?>
            <?php foreach ($certs as $c): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border)">
                <div><strong><?= e($c['name']) ?></strong> <?= $c['issuing_body'] ? '— ' . e($c['issuing_body']) : '' ?></div>
                <?php if ($c['file_url']): ?><a href="<?= e($c['file_url']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div></div>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
