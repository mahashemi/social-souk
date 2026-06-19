<?php
require_once __DIR__ . '/db.php';

if (auth()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $city       = trim($_POST['city'] ?? '');
    $country    = trim($_POST['country'] ?? '');
    $dialCode   = trim($_POST['dial_code'] ?? '');
    $phoneDigits = preg_replace('/\D/', '', $_POST['phone_number'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($country === '') $errors[] = 'Please select your country.';
    if ($phoneDigits !== '' || $dialCode !== '') {
        if (!preg_match('/^\+\d{1,4}$/', $dialCode)) $errors[] = 'Please select a valid country code.';
        if (!preg_match('/^\d{10}$/', $phoneDigits)) $errors[] = 'Phone number must be exactly 10 digits (without the leading 0 or country code).';
    }
    $phone = $phoneDigits !== '' ? $dialCode . ' ' . $phoneDigits : '';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, city, country, phone) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $city, $country, $phone]);

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
<script>
const COUNTRIES = [
    {name:"Pakistan", dial:"+92"}, {name:"India", dial:"+91"}, {name:"Bangladesh", dial:"+880"},
    {name:"Saudi Arabia", dial:"+966"}, {name:"United Arab Emirates", dial:"+971"}, {name:"Qatar", dial:"+974"},
    {name:"Kuwait", dial:"+965"}, {name:"Bahrain", dial:"+973"}, {name:"Oman", dial:"+968"},
    {name:"Turkey", dial:"+90"}, {name:"Egypt", dial:"+20"}, {name:"Indonesia", dial:"+62"},
    {name:"Malaysia", dial:"+60"}, {name:"Afghanistan", dial:"+93"}, {name:"Iran", dial:"+98"},
    {name:"Iraq", dial:"+964"}, {name:"Jordan", dial:"+962"}, {name:"Lebanon", dial:"+961"},
    {name:"Morocco", dial:"+212"}, {name:"Tunisia", dial:"+216"}, {name:"Algeria", dial:"+213"},
    {name:"Nigeria", dial:"+234"}, {name:"South Africa", dial:"+27"}, {name:"Sri Lanka", dial:"+94"},
    {name:"United Kingdom", dial:"+44"}, {name:"United States", dial:"+1"}, {name:"Canada", dial:"+1"},
    {name:"Australia", dial:"+61"}, {name:"Germany", dial:"+49"}, {name:"France", dial:"+33"},
    {name:"Other", dial:""}
];
function updateDialCode() {
    const sel = document.getElementById('countrySelect');
    const c = COUNTRIES.find(c => c.name === sel.value);
    document.getElementById('dialCode').value = c ? c.dial : '';
}
function cleanPhoneInput(el) {
    el.value = el.value.replace(/\D/g, '').slice(0, 10);
}
</script>
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
                    <label class="form-label">Country</label>
                    <select name="country" id="countrySelect" class="form-control" onchange="updateDialCode()" required>
                        <option value="">Select country</option>
                        <?php foreach (['Pakistan','India','Bangladesh','Saudi Arabia','United Arab Emirates','Qatar','Kuwait','Bahrain','Oman','Turkey','Egypt','Indonesia','Malaysia','Afghanistan','Iran','Iraq','Jordan','Lebanon','Morocco','Tunisia','Algeria','Nigeria','South Africa','Sri Lanka','United Kingdom','United States','Canada','Australia','Germany','France','Other'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= ($_POST['country'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" placeholder="Karachi" value="<?= e($_POST['city'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <div style="display:grid;grid-template-columns:90px 1fr;gap:.6rem">
                    <input type="text" id="dialCode" name="dial_code" class="form-control" placeholder="+92" value="<?= e($_POST['dial_code'] ?? '') ?>">
                    <input type="text" name="phone_number" class="form-control" placeholder="3001234567" maxlength="10" inputmode="numeric" oninput="cleanPhoneInput(this)" value="<?= e($_POST['phone_number'] ?? '') ?>">
                </div>
                <div class="form-hint">Select your country above to auto-fill the code, then enter your 10-digit number without the leading 0.</div>
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
