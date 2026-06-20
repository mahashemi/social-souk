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
| Email verification required before login (24h token, resend supported) | ✅ |
| Edit your own profile (name, country, phone, bio, password) | ✅ |
| Post / edit / delete listings with category, price type, city | ✅ |
| Owners and admins can edit any listing — "Last edited by" shown on the listing | ✅ |
| Browse & search listings | ✅ |
| Public seller profiles + follow/unfollow | ✅ |
| Direct messaging between buyer and seller | ✅ |
| Halal-certified badge on listings | ✅ |
| Country selector with auto-filled dial code + validated 10-digit phone | ✅ |
| Admin panel — manage users & listings, grant/revoke admin, export CSV | ✅ |
| Image upload for listings (JPG/PNG/WEBP, 5MB max, validated server-side) | ✅ |
| Reviews & ratings on completed trades | 🔜 planned |

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
├── edit-listing.php        # Edit a listing (owner or admin)
├── edit-profile.php        # Edit your own profile
├── verify.php / verify-pending.php / resend-verification.php   # Email verification flow
├── admin.php               # Admin panel (users, listings, privileges, CSV export)
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
- **Grant or revoke admin privileges** for any other user (you cannot demote yourself)
- View, edit, hide/show, delete, and CSV-export all listings

## Email Verification

New accounts must verify their email before logging in. `mail()` is attempted on registration, but **most local environments (XAMPP) have no SMTP configured**, so delivery will silently fail. To make local testing possible, `config.php` has `DEV_SHOW_VERIFY_LINK = true`, which shows the verification link directly on the "check your email" page after registering. **Set this to `false` once real SMTP/email delivery is wired up in production** — otherwise anyone could self-verify without owning the email address.

## Image Uploads

Listings support a photo upload (JPG/PNG/WEBP, max 5MB). Files are validated server-side with `getimagesize()` (not just by extension), renamed to a random filename, and stored in `/uploads/listings/`. That folder has a `.htaccess` blocking PHP/script execution, so an uploaded file can never run as code even if disguised with a fake extension. If no photo is uploaded, listings fall back to a category icon.

## Editing & Attribution

Listing owners can edit their own listings from `dashboard.php` or the listing page. Admins can edit *any* listing the same way. Whenever an admin edits someone else's listing, the listing page shows "Last edited by [Admin Name] (Admin)" so changes are always traceable.

## Deployment

See [DEPLOY.md](DEPLOY.md) for the full commit → push → deploy workflow, including both shared-hosting (cPanel/FTP) and VPS (SSH + git pull) paths.

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt)
- All database queries use PDO prepared statements
- All forms are CSRF-protected
- `config.php` ships with local XAMPP defaults (`root` / no password) — **you must change these before deploying to production**

## License

Private project. All rights reserved.
