-- SocialSouk Database Schema (Production)
-- Run this file once to set up your database.
-- Command: mysql --default-character-set=utf8mb4 -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS social_souk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE social_souk;

-- ── Site Settings (editable by admins at /admin.php) ───────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('SITE_NAME', 'SocialSouk'),
('SITE_TAGLINE', 'Trade with Barakah');

-- ── Users ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(30),
    city        VARCHAR(100),
    country     VARCHAR(100) DEFAULT 'Pakistan',
    bio         TEXT,
    avatar      VARCHAR(300),
    is_verified TINYINT(1) DEFAULT 0,
    verification_token   VARCHAR(64) NULL,
    verification_expires  DATETIME NULL,
    is_admin    TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Listing Categories ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    slug  VARCHAR(100) NOT NULL UNIQUE,
    icon  VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, slug, icon) VALUES
('Books & Quran',        'books',      '📖'),
('Clothing & Hijab',     'clothing',   '👘'),
('Prayer Items',         'prayer',     '🕌'),
('Halal Food & Dates',   'food',       '🥗'),
('Fragrances & Oud',     'fragrance',  '🌹'),
('Electronics',          'electronics','📱'),
('Home & Decor',         'home',       '🏠'),
('Vehicles',             'vehicles',   '🚗'),
('Services',             'services',   '🛠️'),
('Other / Misc',         'other',      '📦');

-- ── Listings (Products for sale) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS listings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    price        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_type   ENUM('fixed','negotiable','free','swap') DEFAULT 'fixed',
    city         VARCHAR(100),
    image_url    VARCHAR(500),
    halal_badge  TINYINT(1) DEFAULT 0,
    is_active    TINYINT(1) DEFAULT 1,
    views        INT UNSIGNED DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by   INT UNSIGNED NULL,
    updated_at   TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active),
    INDEX idx_user (user_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Follow System ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS follows (
    follower_id  INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Messages (Chat) ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    listing_id  INT UNSIGNED,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id)  REFERENCES listings(id) ON DELETE SET NULL,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_receiver (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Reviews ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT UNSIGNED NOT NULL,
    seller_id   INT UNSIGNED NOT NULL,
    listing_id  INT UNSIGNED,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_review (reviewer_id, seller_id, listing_id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════════════════
-- B2B TRADE — Buyer/Supplier company profiles, products, RFQ marketplace
-- (Added alongside the existing consumer C2C marketplace above, not a
-- replacement — a user can have both a personal account and a company.)
-- ════════════════════════════════════════════════════════════════════════

-- ── Trade role on the user account ────────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS trade_role ENUM('none','buyer','supplier','both') NOT NULL DEFAULT 'none';

-- ── B2B Categories (separate from consumer `categories`) ──────────────────
CREATE TABLE IF NOT EXISTS b2b_categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    icon VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO b2b_categories (name, slug, icon) VALUES
('Agriculture & Food',              'agriculture-food',     '🌾'),
('Textiles & Apparel',              'textiles-apparel',     '🧵'),
('Electronics & Electrical',        'electronics',          '🔌'),
('Machinery & Industrial Equipment','machinery',            '⚙️'),
('Construction & Building Materials','construction',        '🏗️'),
('Health, Beauty & Personal Care',  'health-beauty',        '💊'),
('Home, Garden & Furniture',        'home-garden',          '🪑'),
('Packaging & Printing',            'packaging-printing',   '📦'),
('Automotive & Transportation',     'automotive',           '🚗'),
('Chemicals & Minerals',            'chemicals-minerals',   '🧪');

-- ── Company Profiles (one per user — the "showroom") ───────────────────────
CREATE TABLE IF NOT EXISTS companies (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                INT UNSIGNED NOT NULL,
    company_name           VARCHAR(200) NOT NULL,
    role                   ENUM('buyer','supplier','both') NOT NULL DEFAULT 'supplier',
    business_type          ENUM('manufacturer','trading_company','distributor_wholesaler','retailer','individual') DEFAULT 'manufacturer',
    year_established       YEAR NULL,
    employee_count         ENUM('1-10','11-50','51-200','201-500','500+') NULL,
    country                VARCHAR(100),
    city                   VARCHAR(100),
    address                TEXT,
    main_products          VARCHAR(300),
    main_export_markets    VARCHAR(300),
    is_importer            TINYINT(1) DEFAULT 0,
    is_exporter            TINYINT(1) DEFAULT 0,
    annual_revenue         ENUM('below_1m','1m_10m','10m_50m','50m_above') NULL,
    -- Production capacity tab
    factory_size_sqm       INT UNSIGNED NULL,
    production_lines       INT UNSIGNED NULL,
    monthly_output         VARCHAR(150),
    rd_staff_count         INT UNSIGNED NULL,
    -- Trade capacity tab
    nearest_port               VARCHAR(150),
    accepted_currencies        VARCHAR(150) DEFAULT 'USD',
    accepted_payment_methods   VARCHAR(200),
    avg_lead_time_days         INT UNSIGNED NULL,
    description             TEXT,
    logo_url                VARCHAR(500),
    banner_url              VARCHAR(500),
    business_license_url   VARCHAR(500),
    verification_status    ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
    verified_at             TIMESTAMP NULL,
    verified_by             INT UNSIGNED NULL,
    response_rate           TINYINT UNSIGNED DEFAULT 0,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY one_company_per_user (user_id),
    INDEX idx_verification (verification_status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Certifications / Trademarks / Patents (Certifications tab) ────────────
CREATE TABLE IF NOT EXISTS company_certifications (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    name         VARCHAR(200) NOT NULL,
    issuing_body VARCHAR(200),
    file_url     VARCHAR(500),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── B2B Products (separate from consumer `listings`) ───────────────────────
CREATE TABLE IF NOT EXISTS b2b_products (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NULL,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    price_min    DECIMAL(12,2) NOT NULL DEFAULT 0,
    price_max    DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit         VARCHAR(50) DEFAULT 'piece',
    moq          INT UNSIGNED NOT NULL DEFAULT 1,
    image_url    VARCHAR(500),
    is_active    TINYINT(1) DEFAULT 1,
    views        INT UNSIGNED DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by   INT UNSIGNED NULL,
    updated_at   TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES b2b_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company (company_id),
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── RFQ — Request For Quotation board ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS rfqs (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id             INT UNSIGNED NOT NULL,
    category_id          INT UNSIGNED NULL,
    product_name         VARCHAR(200) NOT NULL,
    quantity             INT UNSIGNED NOT NULL,
    unit                 VARCHAR(50) DEFAULT 'piece',
    target_price         DECIMAL(12,2) NULL,
    description          TEXT,
    destination_country  VARCHAR(100),
    status               ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES b2b_categories(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rfq_quotes (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rfq_id       INT UNSIGNED NOT NULL,
    company_id   INT UNSIGNED NOT NULL,
    quoted_price DECIMAL(12,2) NOT NULL,
    message      TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_quote_per_company (rfq_id, company_id),
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Feedback / Advice ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedback (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Initial Admin Account ───────────────────────────────────────────────
-- Default password: Admin@123
-- IMPORTANT: Log in immediately and change this password via your profile.
INSERT INTO users (name, email, password, is_admin, is_verified) VALUES
('Site Admin', 'admin@socialsouk.net',
 '$2y$10$Rn49XbRBi1VaO9H6AnkdfOhBEGhhe.D.4.HYAJaquZDWuHT7qXS2q', 1, 1);
