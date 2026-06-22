<?php
require_once __DIR__ . '/db.php';
$user = auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%F0%9F%9B%8D%EF%B8%8F%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a class="nav-brand" href="index.php">🛍️ <?= e(SITE_NAME) ?></a>
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>
    <div class="nav-scrim" onclick="toggleNav()"></div>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="search.php">Search</a>
        <a href="trade.php">Trade</a>
        <?php if ($user): ?><a href="profile.php?id=<?= (int) $user['id'] ?>" class="nav-user">👤 <?= e($user['name']) ?></a>
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

<section class="mission-band">
    <div class="mission-grid">
        <div>
            <h3>🎯 Our Vision</h3>
            <p>To be the world's leading Islamic social commerce platform — a trusted digital space where Muslims across the globe trade, connect, and build community in harmony with their faith.</p>
        </div>
        <div>
            <h3>🌍 Our Mission</h3>
            <p>SocialSouk combines the connectivity of social media with the functionality of a marketplace, empowering Muslims to buy, sell, and chat in an environment built on halal values, transparency, and brotherhood (ukhuwwah).</p>
        </div>
    </div>
</section>

<div class="container section">
    <h2 class="section-title">What Makes Us <span>Different</span></h2>
    <div class="grid-3">
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)">🕌 Halal by Design</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Every listing can carry a halal-certified badge, and the platform itself is built around Islamic values of fairness and transparency.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)">🤝 Trust First</h3>
            <p style="color:var(--text-mid);font-size:.92rem">Public seller profiles, follower counts, and direct chat mean you know who you're trading with before you commit.</p>
        </div></div>
        <div class="card"><div class="card-body">
            <h3 style="font-size:1.05rem;margin-bottom:.5rem;color:var(--green-deep)">🌍 Built for the Ummah</h3>
            <p style="color:var(--text-mid);font-size:.92rem">From books and modest clothing to halal food and home goods — every category reflects what our community actually needs.</p>
        </div></div>
    </div>
    <div style="text-align:center;margin-top:2.5rem">
        <p style="color:var(--text-mid);margin-bottom:1rem">Have a question or suggestion?</p>
        <a href="feedback.php" class="btn btn-primary">Send Us Feedback</a>
    </div>
</div>

<footer>
    <div class="footer-bottom">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Built with ❤️ for the Ummah.</div>
</footer>
<script src="app.js" defer></script>
</body>
</html>
