<?php
require_once __DIR__ . '/db.php';

if (auth()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $city     = trim($_POST['city'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, city, phone) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $city, $phone]);

        $userId = (int) $pdo->lastInsertId();
        $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email, 'is_admin' => 0];
        flash('success', 'Welcome to SocialSouk, ' . $name . '! Your account is ready.');
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-logo">
            <h2>🛍️ <?= e(SITE_NAME) ?></h2>
            <p><?= e(SITE_TAGLINE) ?></p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Fatima Khan" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" placeholder="Karachi" value="<?= e($_POST['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="03001234567" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Create My Account</button>
        </form>

        <p style="text-align:center;margin-top:1.2rem;font-size:.88rem;color:var(--text-light)">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</div>
</body>
</html>
