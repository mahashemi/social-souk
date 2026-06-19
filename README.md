# SocialSouk

**Trade with Barakah.** A halal social commerce platform — part social feed, part marketplace, part chat app — built for the Muslim community to buy, sell, and connect with trust.

🌐 Domain: **socialsouk.net**

---

## What It Does

SocialSouk combines three things most platforms keep separate:

- **Marketplace** — post listings with price, category, city, and an optional halal-certified badge
- **Social profiles** — every seller has a public profile, follower count, and listing history
- **Direct chat** — buyers message sellers right from a listing, no third-party app needed

## Tech Stack

- **PHP 7.4+** (no framework — plain PHP with PDO)
- **MySQL / MariaDB**
- **Vanilla HTML/CSS/JS** — no build step, no npm, deploy by copying files

## Features

| Feature | Status |
|---|---|
| User registration & login (hashed passwords, CSRF-protected forms) | ✅ |
| Post / edit / delete listings with category, price type, city | ✅ |
| Browse & search listings | ✅ |
| Public seller profiles + follow/unfollow | ✅ |
| Direct messaging between buyer and seller | ✅ |
| Halal-certified badge on listings | ✅ |
| Admin panel — manage users & listings, export CSV | ✅ |
| Reviews & ratings on completed trades | 🔜 planned |
| Image upload for listings | 🔜 planned |

## Project Structure

```
social_souk/
├── config.php          # DB credentials & site settings — EDIT THIS for your environment
├── db.php               # PDO connection + shared helper functions
├── schema.sql            # Database schema + one starter admin account
├── style.css             # Design system (Islamic green & gold theme)
├── index.php             # Homepage / listing feed
├── register.php / login.php / logout.php
├── create-listing.php    # Post a new listing
├── listing.php            # Single listing detail + message seller
├── profile.php            # Public seller profile
├── search.php              # Search listings
├── chat.php                # Messaging inbox
├── dashboard.php           # User's own listings
├── admin.php               # Admin panel (users, listings, CSV export)
├── VISION.md                # Product vision & mission
└── TASKS.md                  # Project task tracker
```

## Setup (Local — XAMPP)

1. Copy this folder into `C:\xampp\htdocs\social_souk`
2. Start Apache + MySQL in the XAMPP Control Panel
3. Import the schema:
   ```
   C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root < schema.sql
   ```
   > **Important:** always import with `--default-character-set=utf8mb4` — without it, the emoji category icons get corrupted into `?` characters.
4. Visit `http://localhost/social_souk/`

## First Login

A single admin account is seeded by `schema.sql`:

- **Email:** `admin@socialsouk.net`
- **Password:** `Admin@123`

**Change this password immediately after your first login.** This project does not yet have a "change password" UI — update it directly in the database for now:

```sql
UPDATE users SET password = '<new bcrypt hash>' WHERE email = 'admin@socialsouk.net';
```
(Generate a hash with PHP: `php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"`)

## Admin Panel

Visit `/admin.php` while logged in as an admin (`is_admin = 1`) to:
- View platform stats (users, listings, messages, follows)
- View, verify/unverify, and CSV-export all users
- View, hide/show, delete, and CSV-export all listings

## Deployment

See [DEPLOY.md](DEPLOY.md) for the full commit → push → deploy workflow, including both shared-hosting (cPanel/FTP) and VPS (SSH + git pull) paths.

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt)
- All database queries use PDO prepared statements
- All forms are CSRF-protected
- `config.php` ships with local XAMPP defaults (`root` / no password) — **you must change these before deploying to production**

## License

Private project. All rights reserved.
