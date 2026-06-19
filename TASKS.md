# SocialSouk — Project Tasks

## Status Legend
- `[ ]` Not started
- `[~]` In progress
- `[x]` Complete
- `[!]` Blocked / Needs decision

## Priority
- `P1` Critical / MVP must-have
- `P2` Important but can follow MVP
- `P3` Nice to have / future

---

## Phase 1 — Planning & Design
- [x] Define vision and mission (VISION.md) — P1
- [x] Choose app name: SocialSouk — P1
- [x] Choose domain: socialsouk.net — P1
- [x] Finalize color scheme and UI style — P1
- [x] Define database schema — P1

## Phase 2 — Backend / Database
- [x] Write database schema (schema.sql) — P1
- [x] Create config.php (DB credentials) — P1
- [x] Create db.php (PDO connection + helpers) — P1
- [x] Implement user registration (hashed password) — P1
- [x] Implement user login / session management — P1
- [x] Implement logout — P1
- [x] Implement create listing (product post) — P1
- [x] Implement delete listing — P1
- [x] Implement listing detail view — P1
- [x] Implement follow / unfollow user — P2
- [x] Implement direct messaging (chat) — P1
- [x] Implement search (by keyword, city) — P1
- [x] Implement halal badge self-declaration — P2
- [x] Implement admin panel (users, listings, CSV export) — P2
- [ ] Implement edit listing (currently delete + repost only) — P2
- [ ] Implement social feed scoped to followed users only — P2
- [ ] Implement reviews / ratings — P2
- [ ] Image upload (to server or cloud) — currently no image support — P2

## Phase 3 — Frontend / UI
- [x] Create style.css (Islamic design system) — P1
- [x] Build index.php (landing + feed) — P1
- [x] Build register.php — P1
- [x] Build login.php — P1
- [x] Build profile.php (user listings + bio) — P1
- [x] Build listing.php (single listing) — P1
- [x] Build create-listing.php (post form) — P1
- [x] Build chat.php (messaging UI) — P1
- [x] Build search.php (results page) — P1
- [x] Build dashboard.php (my listings) — P1
- [x] Build admin.php (admin panel) — P2
- [x] Mobile responsive layout — P1
- [ ] Add Arabic / RTL support — P3

## Phase 4 — Production Readiness
- [x] Remove all demo/seed data — production DB starts with one admin account only — P1
- [x] Fix UTF-8 emoji encoding bug in category icons (was corrupting to `?`) — P1
- [x] Write README.md with setup, admin credentials, and security notes — P1
- [x] Write DEPLOY.md with commit → push → deploy workflow — P1
- [ ] Add a "change password" UI (currently requires direct DB update) — P1
- [ ] Test registration & login on localhost — P1
- [ ] Test listing creation and display end-to-end with real data — P1
- [ ] Test chat functionality with two real accounts — P1
- [ ] Test on mobile browsers — P1
- [ ] Cross-browser testing (Chrome, Firefox, Safari) — P2
- [ ] SQL injection / XSS security audit — P1
- [ ] Test all form validations — P1

## Phase 5 — Deployment
- [ ] Choose hosting provider (cPanel recommended) — P1
- [x] Register domain: socialsouk.net — P1
- [ ] Set up MySQL database on hosting — P1
- [ ] Upload files via FTP/File Manager — P1
- [ ] Run schema.sql on production DB (remember `--default-character-set=utf8mb4`) — P1
- [ ] Update config.php with production credentials — P1
- [ ] Test all features on live server — P1
- [ ] Set up SSL certificate (free Let's Encrypt) — P1
- [ ] Configure email (SMTP for notifications) — P2
- [ ] Set up backups — P2

## Phase 6 — Launch & Growth
- [ ] Create social media accounts (Instagram, Facebook, WhatsApp) — P2
- [ ] Write launch announcement post — P2
- [ ] Onboard first 10 sellers manually — P2
- [ ] Collect feedback from first users — P2
- [ ] Iterate on UI/UX based on feedback — P2
- [ ] Add payment integration (Stripe, PayPal) — P3
- [ ] Add Zakat/charity donation feature — P3
- [ ] Multi-language support (Arabic, Urdu, Farsi) — P3

---

## Open Questions / Decisions Needed
- [!] Will payments be handled on-platform or off-platform (WhatsApp/cash)?
- [!] Who moderates halal badge claims?
- [!] Image hosting: server upload or link-based (URL)?
- [!] Which country/currency to target first?

---

*Last updated:* 2026-06-19
