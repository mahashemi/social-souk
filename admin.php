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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">🛍️ <?= e(SITE_NAME) ?> <small style="color:var(--gold);font-size:.7rem">ADMIN</small></div>
    <div class="nav-links">
        <a href="index.php">Site</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-btn">Logout</a>
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
                    <td><?= $l['price'] > 0 ? 'Rs ' . number_format((float) $l['price']) : 'Free/Swap' ?></td>
                    <td><?= e($l['city'] ?: '—') ?></td>
                    <td><?= $l['halal_badge'] ? '✓' : '—' ?></td>
                    <td><?= (int) $l['views'] ?></td>
                    <td><span class="badge <?= $l['is_active'] ? 'badge-active' : 'badge-closed' ?>"><?= $l['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                    <td style="display:flex;gap:.4rem">
                        <a href="edit-listing.php?id=<?= (int) $l['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_listing" value="<?= (int) $l['id'] ?>" class="btn btn-sm btn-outline"><?= $l['is_active'] ? 'Hide' : 'Show' ?></button></form>
                        <form method="post" onsubmit="return confirm('Delete this listing permanently?')"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="delete_listing" value="<?= (int) $l['id'] ?>" class="btn btn-sm btn-outline" style="color:#c00;border-color:#c00">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
                    <td style="display:flex;gap:.4rem">
                        <form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" name="toggle_verify" value="<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline"><?= $u['is_verified'] ? 'Unverify' : 'Verify' ?></button></form>
                        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" onsubmit="return confirm('<?= $u['is_admin'] ? 'Remove admin privileges from' : 'Grant admin privileges to' ?> <?= e($u['name']) ?>?')">
                            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
                            <button type="submit" name="toggle_admin" value="<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline"><?= $u['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
