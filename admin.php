<?php
require_once __DIR__ . '/db.php';
requireAuth();
$user = auth();
if (empty($user['is_admin'])) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:3rem;text-align:center">Access denied. Admins only. <a href="index.php">Go back</a></p>');
}

// ── CSV Export (must run before any HTML output) ──────────────────────────
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $map = [
        'users'    => ['sql' => 'SELECT id, name, email, phone, city, country, is_verified, is_admin, created_at FROM users ORDER BY id', 'file' => 'socialsouk_users.csv'],
        'listings' => ['sql' => "SELECT l.id, l.title, u.name AS seller, l.price, l.price_type, l.city, l.halal_badge, l.is_active, l.views, l.created_at
                                  FROM listings l JOIN users u ON u.id = l.user_id ORDER BY l.id", 'file' => 'socialsouk_listings.csv'],
        'feedback' => ['sql' => 'SELECT id, name, email, message, is_read, created_at FROM feedback ORDER BY id DESC', 'file' => 'socialsouk_feedback.csv'],
    ];
    if (isset($map[$type])) {
        $rows = $pdo->query($map[$type]['sql'])->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $map[$type]['file'] . '"');
        $out = fopen('php://output', 'w');
        if ($rows) fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}

// ── Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['toggle_listing'])) {
        $pdo->prepare('UPDATE listings SET is_active = 1 - is_active WHERE id = ?')->execute([(int) $_POST['toggle_listing']]);
    } elseif (isset($_POST['toggle_verify'])) {
        $pdo->prepare('UPDATE users SET is_verified = 1 - is_verified WHERE id = ?')->execute([(int) $_POST['toggle_verify']]);
    } elseif (isset($_POST['toggle_admin'])) {
        $targetId = (int) $_POST['toggle_admin'];
        if ($targetId !== (int) $user['id']) {
            $pdo->prepare('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?')->execute([$targetId]);
        }
    } elseif (isset($_POST['delete_listing'])) {
        $pdo->prepare('DELETE FROM listings WHERE id = ?')->execute([(int) $_POST['delete_listing']]);
    } elseif (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $icon = trim($_POST['icon'] ?? '');
        if ($name !== '') {
            $pdo->prepare('INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)')->execute([$name, $slug, $icon]);
        }
    } elseif (isset($_POST['edit_category'])) {
        $id = (int) $_POST['edit_category'];
        $pdo->prepare('UPDATE categories SET name=?, slug=?, icon=? WHERE id=?')
            ->execute([trim($_POST['name']), trim($_POST['slug']), trim($_POST['icon']), $id]);
    } elseif (isset($_POST['delete_category'])) {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int) $_POST['delete_category']]);
    } elseif (isset($_POST['save_settings'])) {
        foreach (['SITE_NAME', 'SITE_TAGLINE'] as $key) {
            $val = trim($_POST[$key] ?? '');
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?')
                ->execute([$key, $val, $val]);
        }
        flash('success', 'Settings updated.');
    } elseif (isset($_POST['toggle_feedback_read'])) {
        $pdo->prepare('UPDATE feedback SET is_read = 1 - is_read WHERE id = ?')->execute([(int) $_POST['toggle_feedback_read']]);
    } elseif (isset($_POST['delete_feedback'])) {
        $pdo->prepare('DELETE FROM feedback WHERE id = ?')->execute([(int) $_POST['delete_feedback']]);
    } elseif (isset($_POST['approve_company'])) {
        $pdo->prepare("UPDATE companies SET verification_status='verified', verified_at=NOW(), verified_by=? WHERE id=?")
            ->execute([$user['id'], (int) $_POST['approve_company']]);
    } elseif (isset($_POST['reject_company'])) {
        $pdo->prepare("UPDATE companies SET verification_status='rejected' WHERE id=?")->execute([(int) $_POST['reject_company']]);
    } elseif (isset($_POST['add_b2b_category'])) {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        if ($name !== '') {
            $pdo->prepare('INSERT INTO b2b_categories (name, slug, icon) VALUES (?, ?, ?)')->execute([$name, $slug, $icon]);
        }
    } elseif (isset($_POST['delete_b2b_category'])) {
        $pdo->prepare('DELETE FROM b2b_categories WHERE id = ?')->execute([(int) $_POST['delete_b2b_category']]);
    } elseif (isset($_POST['upload_site_image'])) {
        $slot = $_POST['upload_site_image'];
        $allowedSlots = ['trade_hero_bg', 'trade_banner_default'];
        if (in_array($slot, $allowedSlots, true)) {
            $path = handleImageUpload('image', 'site');
            if ($path) {
                $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?')
                    ->execute([$slot, $path, $path]);
                flash('success', 'Image updated.');
            } else {
                flash('success', 'Upload failed — please use a JPG, PNG, or WEBP under 5MB.');
            }
        }
    } elseif (isset($_POST['remove_site_image'])) {
        $slot = $_POST['remove_site_image'];
        $allowedSlots = ['trade_hero_bg', 'trade_banner_default'];
        if (in_array($slot, $allowedSlots, true)) {
            $pdo->prepare('DELETE FROM settings WHERE setting_key = ?')->execute([$slot]);
        }
    }
    redirect('admin.php?tab=' . ($_GET['tab'] ?? 'overview'));
}

$tab = $_GET['tab'] ?? 'users';

$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM listings) AS total_listings,
            (SELECT COUNT(*) FROM listings WHERE is_active = 1) AS active_listings,
            (SELECT COUNT(*) FROM messages) AS total_messages,
            (SELECT COUNT(*) FROM follows) AS total_follows"
)->fetch();

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
$listings = $pdo->query(
    'SELECT l.*, u.name AS seller_name FROM listings l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC'
)->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$currentSettings = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$feedback = $pdo->query('SELECT * FROM feedback ORDER BY created_at DESC')->fetchAll();
$companies = $pdo->query(
    "SELECT c.*, u.name AS owner_name, u.email AS owner_email,
            (SELECT COUNT(*) FROM b2b_products WHERE company_id = c.id) AS product_count
     FROM companies c JOIN users u ON u.id = c.user_id ORDER BY c.created_at DESC"
)->fetchAll();
$pendingCompanies = array_values(array_filter($companies, fn($c) => $c['verification_status'] === 'pending'));
$b2bCategories = $pdo->query('SELECT * FROM b2b_categories ORDER BY name')->fetchAll();
$siteImageSlots = [
    'trade_hero_bg'        => ['label' => 'Trade Hero Background', 'hint' => 'Shown behind the headline on the Trade landing page. Recommended: wide image, at least 1600x500.'],
    'trade_banner_default' => ['label' => 'Default Company Banner', 'hint' => 'Fallback banner shown on a supplier\'s public profile page when they have not uploaded their own. Recommended: 1200x300.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?> <small style="color:var(--gold);font-size:.7rem">ADMIN</small></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="profile.php?id=<?= (int) $user['id'] ?>" class="nav-user">👤 <?= e($user['name']) ?></a>
        <a href="index.php">Site</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
        <a href="trade.php">Trade</a>
        <a href="about.php">About</a>
        <a href="feedback.php">Feedback</a>
    </div>
</nav>

<div class="dashboard-wrap" style="max-width:1100px">
    <div class="dashboard-header">
        <h2>🛠️ Admin Panel</h2>
        <p>Manage users, listings, and review platform activity.</p>
    </div>

    <div class="stat-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="stat-card" style="background:var(--white);border-radius:var(--radius-sm);padding:1.2rem;box-shadow:var(--shadow);border:1.5px solid var(--border);text-align:center">
            <div style="font-size:1.8rem;font-weight:800;color:var(--green-deep)"><?= (int) $stats['total_users'] ?></div>
            <div style="font-size:.8rem;color:var(--text-mid)">Total Users</div>
        </div>
        <div class="stat-card" style="background:var(--white);border-radius:var(--radius-sm);padding:1.2rem;box-shadow:var(--shadow);border:1.5px solid var(--border);text-align:center">
            <div style="font-size:1.8rem;font-weight:800;color:var(--green-deep)"><?= (int) $stats['active_listings'] ?> / <?= (int) $stats['total_listings'] ?></div>
            <div style="font-size:.8rem;color:var(--text-mid)">Active / Total Listings</div>
        </div>
        <div class="stat-card" style="background:var(--white);border-radius:var(--radius-sm);padding:1.2rem;box-shadow:var(--shadow);border:1.5px solid var(--border);text-align:center">
            <div style="font-size:1.8rem;font-weight:800;color:var(--green-deep)"><?= (int) $stats['total_messages'] ?></div>
            <div style="font-size:.8rem;color:var(--text-mid)">Messages Sent</div>
        </div>
        <div class="stat-card" style="background:var(--white);border-radius:var(--radius-sm);padding:1.2rem;box-shadow:var(--shadow);border:1.5px solid var(--border);text-align:center">
            <div style="font-size:1.8rem;font-weight:800;color:var(--green-deep)"><?= (int) $stats['total_follows'] ?></div>
            <div style="font-size:.8rem;color:var(--text-mid)">Follow Relationships</div>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=users" class="tab-btn <?= $tab === 'users' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">👥 Users (<?= count($users) ?>)</a>
        <a href="?tab=listings" class="tab-btn <?= $tab === 'listings' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">📦 Listings (<?= count($listings) ?>)</a>
        <a href="?tab=categories" class="tab-btn <?= $tab === 'categories' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">🏷️ Categories (<?= count($categories) ?>)</a>
        <a href="?tab=settings" class="tab-btn <?= $tab === 'settings' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">⚙️ Settings</a>
        <a href="?tab=feedback" class="tab-btn <?= $tab === 'feedback' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">💬 Feedback (<?= count($feedback) ?>)</a>
        <a href="?tab=companies" class="tab-btn <?= $tab === 'companies' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">🏢 Trade Companies (<?= count($pendingCompanies) ?> pending)</a>
        <a href="?tab=b2b_categories" class="tab-btn <?= $tab === 'b2b_categories' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">🏷️ B2B Categories (<?= count($b2bCategories) ?>)</a>
        <a href="?tab=site_images" class="tab-btn <?= $tab === 'site_images' ? 'active' : '' ?>" style="text-decoration:none;display:block;text-align:center">🖼️ Site Images</a>
    </div>

    <?php if ($tab === 'listings'): ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
            <a href="?export=listings" class="btn btn-outline btn-sm">⬇ Download CSV</a>
        </div>
        <table class="table">
            <thead><tr><th>Title</th><th>Seller</th><th>Price</th><th>City</th><th>Halal</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td><a href="listing.php?id=<?= (int) $l['id'] ?>" target="_blank"><?= e($l['title']) ?></a></td>
                    <td><?= e($l['seller_name']) ?></td>
                    <td><?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free/Swap' ?></td>
                    <td><?= e($l['city'] ?: '—') ?></td>
                    <td><?= $l['halal_badge'] ? '✓' : '—' ?></td>
                    <td><?= (int) $l['views'] ?></td>
                    <td><span class="badge <?= $l['is_active'] ? 'badge-active' : 'badge-closed' ?>"><?= $l['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                    <td class="action-row">
                        <a href="edit-listing.php?id=<?= (int) $l['id'] ?>" class="icon-btn" data-tip="Edit listing" aria-label="Edit listing">✏️</a>
                        <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_listing" value="<?= (int) $l['id'] ?>" class="icon-btn" data-tip="<?= $l['is_active'] ? 'Hide' : 'Show' ?>" aria-label="<?= $l['is_active'] ? 'Hide' : 'Show' ?>"><?= $l['is_active'] ? '🙈' : '👁️' ?></button></form>
                        <form method="post" onsubmit="return confirm('Delete this listing permanently?')" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="delete_listing" value="<?= (int) $l['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete">🗑️</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'categories'): ?>
        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem">+ Add New Category</h3>
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr 100px auto;gap:.6rem;align-items:end">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                <div class="form-group" style="margin:0"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group" style="margin:0"><label class="form-label">Slug (optional)</label><input type="text" name="slug" class="form-control" placeholder="auto-generated"></div>
                <div class="form-group" style="margin:0"><label class="form-label">Icon</label><input type="text" name="icon" class="form-control" placeholder="📦"></div>
                <button type="submit" name="add_category" value="1" class="btn btn-primary">+ Add</button>
            </form>
        </div></div>

        <table class="table">
            <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $c): $fid = 'cat-' . (int) $c['id']; ?>
                <tr>
                    <td><input type="text" name="icon" form="<?= $fid ?>" value="<?= e($c['icon']) ?>" class="form-control" style="width:70px;padding:.4rem"></td>
                    <td><input type="text" name="name" form="<?= $fid ?>" value="<?= e($c['name']) ?>" class="form-control" style="padding:.4rem"></td>
                    <td><input type="text" name="slug" form="<?= $fid ?>" value="<?= e($c['slug']) ?>" class="form-control" style="padding:.4rem"></td>
                    <td class="action-row">
                        <form method="post" id="<?= $fid ?>" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="edit_category" value="<?= (int) $c['id'] ?>" class="icon-btn" data-tip="Save" aria-label="Save">💾</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Delete this category? Listings using it will become uncategorized.')" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="delete_category" value="<?= (int) $c['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($tab === 'settings'): ?>
        <?php if (flash('success')): ?><div class="alert alert-success"><?= e(flash('success')) ?></div><?php endif; ?>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem">Site Branding</h3>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="SITE_NAME" class="form-control" value="<?= e($currentSettings['SITE_NAME'] ?? SITE_NAME) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="SITE_TAGLINE" class="form-control" value="<?= e($currentSettings['SITE_TAGLINE'] ?? SITE_TAGLINE) ?>">
                </div>
                <button type="submit" name="save_settings" value="1" class="btn btn-primary">Save Settings</button>
            </form>
        </div></div>
    <?php elseif ($tab === 'feedback'): ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
            <a href="?export=feedback" class="btn btn-outline btn-sm">⬇ Download CSV</a>
        </div>
        <?php if (!$feedback): ?>
            <div class="empty-state"><div class="icon">💬</div><h3>No feedback yet</h3></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>From</th><th>Email</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($feedback as $f): ?>
                <tr style="<?= $f['is_read'] ? 'opacity:.6' : '' ?>">
                    <td><?= e($f['name']) ?></td>
                    <td><?= e($f['email']) ?></td>
                    <td style="max-width:320px"><?= e($f['message']) ?></td>
                    <td><?= date('M j, Y', strtotime($f['created_at'])) ?></td>
                    <td><span class="badge <?= $f['is_read'] ? 'badge-closed' : 'badge-pending' ?>"><?= $f['is_read'] ? 'Read' : 'New' ?></span></td>
                    <td class="action-row">
                        <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_feedback_read" value="<?= (int) $f['id'] ?>" class="icon-btn" data-tip="<?= $f['is_read'] ? 'Mark unread' : 'Mark read' ?>" aria-label="<?= $f['is_read'] ? 'Mark unread' : 'Mark read' ?>"><?= $f['is_read'] ? '📩' : '✔️' ?></button></form>
                        <form method="post" onsubmit="return confirm('Delete this feedback?')" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="delete_feedback" value="<?= (int) $f['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete">🗑️</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    <?php elseif ($tab === 'companies'): ?>
        <p class="section-sub">Companies awaiting review appear first. Approving grants the Verified Supplier badge.</p>
        <table class="table">
            <thead><tr><th>Company</th><th>Owner</th><th>Role</th><th>Country</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($companies as $c): ?>
                <tr>
                    <td><a href="company.php?id=<?= (int) $c['id'] ?>" target="_blank"><?= e($c['company_name']) ?></a></td>
                    <td><?= e($c['owner_name']) ?> <span style="color:var(--text-light);font-size:.78rem">(<?= e($c['owner_email']) ?>)</span></td>
                    <td><span class="badge" style="background:#f5f5f5;color:#555"><?= e(ucfirst($c['role'])) ?></span></td>
                    <td><?= e($c['country'] ?: '—') ?></td>
                    <td><?= (int) $c['product_count'] ?></td>
                    <td>
                        <?php if ($c['verification_status'] === 'verified'): ?><span class="badge-verified">✔ Verified</span>
                        <?php elseif ($c['verification_status'] === 'pending'): ?><span class="badge-pending-review">⏳ Pending</span>
                        <?php elseif ($c['verification_status'] === 'rejected'): ?><span class="badge badge-paid">⛔ Rejected</span>
                        <?php else: ?><span class="badge" style="background:#f5f5f5;color:#888">Unverified</span><?php endif; ?>
                    </td>
                    <td class="action-row">
                        <?php if ($c['business_license_url']): ?><a href="<?= e($c['business_license_url']) ?>" target="_blank" class="icon-btn" data-tip="View license" aria-label="View license">📄</a><?php endif; ?>
                        <a href="chat.php?with=<?= (int) $c['user_id'] ?>" class="icon-btn" data-tip="Message" aria-label="Message">💬</a>
                        <?php if ($c['verification_status'] !== 'verified'): ?>
                        <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="approve_company" value="<?= (int) $c['id'] ?>" class="icon-btn" data-tip="Approve" aria-label="Approve">✅</button></form>
                        <?php endif; ?>
                        <?php if ($c['verification_status'] !== 'rejected'): ?>
                        <form method="post" onsubmit="return confirm('Reject this company\'s verification?')" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="reject_company" value="<?= (int) $c['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Reject" aria-label="Reject">⛔</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'b2b_categories'): ?>
        <div class="card" style="margin-bottom:1.5rem"><div class="card-body">
            <h3 style="font-size:1rem;margin-bottom:1rem">+ Add B2B Category</h3>
            <form method="post" style="display:grid;grid-template-columns:1fr 100px auto;gap:.6rem;align-items:end">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                <div class="form-group" style="margin:0"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group" style="margin:0"><label class="form-label">Icon</label><input type="text" name="icon" class="form-control" placeholder="📦"></div>
                <button type="submit" name="add_b2b_category" value="1" class="btn btn-primary">+ Add</button>
            </form>
        </div></div>
        <table class="table">
            <thead><tr><th>Icon</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($b2bCategories as $c): ?>
                <tr>
                    <td><?= e($c['icon']) ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td class="action-row">
                        <form method="post" onsubmit="return confirm('Delete this category? Products using it will become uncategorized.')" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="delete_b2b_category" value="<?= (int) $c['id'] ?>" class="icon-btn icon-btn-danger" data-tip="Delete" aria-label="Delete">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($tab === 'site_images'): ?>
        <p class="section-sub" style="margin-bottom:1.5rem">Upload custom images for key Trade sections. If a slot is left empty, a sensible default (a solid color/gradient) is used instead.</p>
        <div class="grid-2" style="gap:1.5rem">
            <?php foreach ($siteImageSlots as $slotKey => $slotInfo): $current = siteSetting($pdo, $slotKey); ?>
            <div class="card"><div class="card-body">
                <h3 style="font-size:1rem;margin-bottom:.4rem"><?= e($slotInfo['label']) ?></h3>
                <p style="font-size:.8rem;color:var(--text-light);margin-bottom:1rem"><?= e($slotInfo['hint']) ?></p>
                <?php if ($current): ?>
                    <img src="<?= e($current) ?>" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:var(--radius);margin-bottom:1rem">
                <?php else: ?>
                    <div style="width:100%;height:140px;border-radius:var(--radius);margin-bottom:1rem;background:var(--cream);display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:.85rem">No image set — using default</div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" style="display:flex;gap:.6rem;align-items:center">
                    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required style="flex:1;font-size:.82rem">
                    <button type="submit" name="upload_site_image" value="<?= e($slotKey) ?>" class="btn btn-primary btn-sm">Upload</button>
                </form>
                <?php if ($current): ?>
                <form method="post" onsubmit="return confirm('Remove this image and revert to the default?')" style="margin-top:.5rem">
                    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                    <button type="submit" name="remove_site_image" value="<?= e($slotKey) ?>" class="btn btn-outline btn-sm">Remove</button>
                </form>
                <?php endif; ?>
            </div></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:1rem">
            <a href="?export=users" class="btn btn-outline btn-sm">⬇ Download CSV</a>
        </div>
        <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>City</th><th>Phone</th><th>Verified</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><a href="profile.php?id=<?= (int) $u['id'] ?>" target="_blank"><?= e($u['name']) ?></a> <?= $u['is_admin'] ? '<span class="badge" style="background:#fff8e1;color:#e65100">Admin</span>' : '' ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['city'] ?: '—') ?></td>
                    <td><?= e($u['phone'] ?: '—') ?></td>
                    <td><?= $u['is_verified'] ? '✓ Verified' : '—' ?></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td class="action-row">
                        <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_verify" value="<?= (int) $u['id'] ?>" class="icon-btn" data-tip="<?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>" aria-label="<?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>"><?= $u['is_verified'] ? '🚫' : '✔️' ?></button></form>
                        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" onsubmit="return confirm('<?= $u['is_admin'] ? 'Remove admin privileges from' : 'Grant admin privileges to' ?> <?= e($u['name']) ?>?')" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="toggle_admin" value="<?= (int) $u['id'] ?>" class="icon-btn <?= $u['is_admin'] ? 'icon-btn-danger' : '' ?>" data-tip="<?= $u['is_admin'] ? 'Revoke admin' : 'Make admin' ?>" aria-label="<?= $u['is_admin'] ? 'Revoke admin' : 'Make admin' ?>"><?= $u['is_admin'] ? '👑' : '⭐' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script src="app.js" defer></script>
</body>
</html>
