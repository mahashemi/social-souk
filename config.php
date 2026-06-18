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

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
