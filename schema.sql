-- SocialSouk Database Schema
-- Run this file once to set up your database.
-- Command: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS social_souk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE social_souk;

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
    is_admin    TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ── Listing Categories ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    slug  VARCHAR(100) NOT NULL UNIQUE,
    icon  VARCHAR(10)
) ENGINE=InnoDB;

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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_active (is_active),
    INDEX idx_user (user_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- ── Follow System ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS follows (
    follower_id  INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

-- ── Demo Admin User (password: Admin@123) ────────────────────────────────
INSERT INTO users (name, email, password, is_admin, is_verified) VALUES
('Admin', 'admin@socialsouk.net',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- ── Demo Listings ─────────────────────────────────────────────────────────
INSERT INTO users (name, email, password, city, country, phone) VALUES
('Fatima Khan',    'fatima@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karachi',  'Pakistan', '03001234567'),
('Ahmed Al-Noor',  'ahmed@example.com',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore',   'Pakistan', '03111234567'),
('Maryam Hussain', 'maryam@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Islamabad','Pakistan', '03211234567');

INSERT INTO listings (user_id, category_id, title, description, price, price_type, city, halal_badge) VALUES
(2, 1,  'Holy Quran — Arabic & English (Sahih Int)', 'Hardcover, mint condition. Purchased but have two copies now. Beautiful typography.', 1200.00, 'negotiable', 'Karachi',  1),
(2, 3,  'Premium Prayer Mat — Soft Velvet', 'Soft burgundy velvet prayer mat, 120x70cm, with compass. Like new.', 800.00, 'fixed', 'Karachi', 1),
(3, 2,  'Modest Abaya — Black, Size M', 'Flowy chiffon abaya, worn once. Perfect condition. Colour is true black.', 2500.00, 'negotiable', 'Lahore', 1),
(3, 5,  'Pure Oud Perfume — Alcohol Free 50ml', 'Authentic Arabic Oud fragrance, no alcohol. Very long lasting.', 1800.00, 'fixed', 'Lahore', 1),
(4, 4,  'Organic Medjool Dates — 1kg Box', 'Freshly imported Medjool dates from Madinah. Halal certified. Soft and sweet.', 950.00, 'fixed', 'Islamabad', 1),
(4, 1,  'Islamic History Books — Set of 5', 'Set of 5 Islamic history books in excellent condition. Great for students.', 1500.00, 'negotiable', 'Islamabad', 1);
