<?php
// SocialSouk — Database & Site Configuration
// Update these values for your hosting environment

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change to your DB username
define('DB_PASS', '');              // Change to your DB password
define('DB_NAME', 'social_souk');

define('SITE_NAME', 'SocialSouk');
define('SITE_TAGLINE', 'Trade with Barakah');
define('SITE_URL', '');             // e.g. https://socialsouk.net — leave empty for relative URLs

// Email verification: when true, the verification link is also shown on screen
// after registration (useful when SMTP isn't configured yet, e.g. local XAMPP).
// Set this to false once real email delivery works in production.
define('DEV_SHOW_VERIFY_LINK', true);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
