<?php
require_once __DIR__ . '/db.php';

$user = auth();
$catSlug = $_GET['cat'] ?? '';

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$heroBg = siteSetting($pdo, 'home_hero_bg');

$sql = "SELECT l.*, u.name AS seller_name, c.name AS cat_name, c.icon AS cat_icon
        FROM listings l
        JOIN users u ON u.id = l.user_id
        LEFT JOIN categories c ON c.id = l.category_id
        WHERE l.is_active = 1";
$params = [];
if ($catSlug !== '') {
    $sql .= ' AND c.slug = ?';
    $params[] = $catSlug;
}
$sql .= ' ORDER BY l.created_at DESC LIMIT 24';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(SITE_NAME) ?> — <?= e(SITE_TAGLINE) ?></title>
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

<header class="hero" <?php if ($heroBg): ?>style="background-image:linear-gradient(135deg, rgba(10,61,31,.85), rgba(13,40,24,.85)), url('<?= e($heroBg) ?>');background-size:cover;background-position:center"<?php endif; ?>>
    <div class="hero-content">
        <h1>Trade with <span>Barakah</span></h1>
        <p>The social marketplace for Muslims — buy, sell, chat, and connect with your community. Every listing built on trust and halal values.</p>
        <div class="hero-actions">
            <?php if ($user): ?>
                <a href="create-listing.php" class="btn btn-primary">+ Post a Listing</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Join SocialSouk</a>
            <?php endif; ?>
            <a href="#listings" class="btn btn-secondary">Browse Listings</a>
        </div>
    </div>
</header>

<?php if (!$user): ?>
<section class="mission-band">
    <div class="mission-grid">
        <div>
            <h3><i data-lucide="target" class="lucide-icon"></i> Our Vision</h3>
            <p>To be the world's leading Islamic social commerce platform — a trusted digital space where Muslims across the globe trade, connect, and build community in harmony with their faith.</p>
        </div>
        <div>
            <h3><i data-lucide="globe" class="lucide-icon"></i> Our Mission</h3>
            <p>SocialSouk combines the connectivity of social media with the functionality of a marketplace, empowering Muslims to buy, sell, and chat in an environment built on halal values, transparency, and brotherhood (ukhuwwah).</p>
        </div>
    </div>
    <div class="mission-cta">
        <p>Already have an account?</p>
        <div class="hero-actions" style="justify-content:center">
            <a href="login.php" class="btn btn-primary">Log In</a>
            <a href="register.php" class="btn btn-outline">Create Free Account</a>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container" id="listings" style="padding-top:3rem">
    <h2 class="section-title">Shop by <span>Category</span></h2>
    <div class="chip-row">
        <a href="index.php" class="cat-chip <?= $catSlug === '' ? 'active' : '' ?>"><i data-lucide="shopping-cart" class="lucide-icon"></i> All</a>
        <?php foreach ($categories as $c): ?>
            <a href="?cat=<?= e($c['slug']) ?>" class="cat-chip <?= $catSlug === $c['slug'] ? 'active' : '' ?>">
                <?= catIcon($c['icon']) ?> <?= e($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="container section">
    <h2 class="section-title">Latest <span>Listings</span></h2>
    <p class="section-sub"><?= count($listings) ?> item(s) available right now</p>

    <?php if (!$listings): ?>
        <div class="empty-state">
            <div class="icon"><i data-lucide="inbox" class="lucide-icon"></i></div>
            <h3>No listings yet in this category</h3>
            <p>Be the first to post something here.</p>
        </div>
    <?php else: ?>
    <div class="grid-4">
        <?php foreach ($listings as $l): ?>
        <a href="listing.php?id=<?= (int) $l['id'] ?>" class="card">
            <div class="card-img"><?php if ($l['image_url']): ?><img src="<?= e($l['image_url']) ?>" alt=""><?php else: ?><?= catIcon($l['cat_icon']) ?><?php endif; ?></div>
            <div class="card-body">
                <div class="card-title"><?= e($l['title']) ?></div>
                <div class="card-price">
                    <?= $l['price'] > 0 ? '$' . number_format((float) $l['price']) : 'Free / Swap' ?>
                </div>
                <div class="card-meta">
                    <span><i data-lucide="map-pin" class="lucide-icon"></i> <?= e($l['city'] ?: 'N/A') ?></span>
                    <?php if ($l['halal_badge']): ?><span class="halal-badge"><i data-lucide="check" class="lucide-icon"></i> Halal</span><?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-brand"><i data-lucide="shopping-bag" class="lucide-icon"></i> <?= e(SITE_NAME) ?></div>
            <p>Trade with Barakah. A halal social marketplace built for the Ummah.</p>
        </div>
        <div>
            <div class="footer-heading">Explore</div>
            <ul class="footer-links">
                <li><a href="index.php">Browse Listings</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="register.php">Join Free</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-heading">Account</div>
            <ul class="footer-links">
                <li><a href="login.php">Login</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="chat.php">Messages</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Built with <i data-lucide="heart" class="lucide-icon"></i> for the Ummah.</div>
</footer>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="app.js" defer></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
