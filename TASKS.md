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
- [ ] Finalize color scheme and UI style — P1
- [ ] Wireframe: Home/Feed page — P1
- [ ] Wireframe: Profile page — P1
- [ ] Wireframe: Listing detail page — P1
- [ ] Wireframe: Chat page — P1
- [ ] Define database schema — P1

## Phase 2 — Backend / Database
- [~] Write database schema (schema.sql) — P1
- [~] Create config.php (DB credentials) — P1
- [~] Create db.php (PDO connection + helpers) — P1
- [ ] Implement user registration (hashed password) — P1
- [ ] Implement user login / session management — P1
- [ ] Implement logout — P1
- [ ] Implement create listing (product post) — P1
- [ ] Implement edit / delete listing — P1
- [ ] Implement listing detail view — P1
- [ ] Implement follow / unfollow user — P2
- [ ] Implement social feed (listings from followed users) — P2
- [ ] Implement direct messaging (chat) — P1
- [ ] Implement search (by keyword, category, city) — P1
- [ ] Implement halal badge self-declaration — P2
- [ ] Implement reviews / ratings — P2
- [ ] Implement admin moderation panel — P2
- [ ] Image upload (to server or cloud) — P2

## Phase 3 — Frontend / UI
- [~] Create style.css (Islamic design system) — P1
- [~] Build index.php (landing + feed) — P1
- [~] Build register.php — P1
- [~] Build login.php — P1
- [ ] Build profile.php (user listings + bio) — P1
- [ ] Build listing.php (single listing) — P1
- [ ] Build create-listing.php (post form) — P1
- [ ] Build chat.php (messaging UI) — P1
- [ ] Build search.php (results page) — P1
- [ ] Build dashboard.php (my listings, my orders) — P1
- [ ] Make fully mobile responsive — P1
- [ ] Add Arabic / RTL support — P3

## Phase 4 — Testing
- [ ] Test registration & login on localhost — P1
- [ ] Test listing creation and display — P1
- [ ] Test chat functionality — P1
- [ ] Test on mobile browsers — P1
- [ ] Cross-browser testing (Chrome, Firefox, Safari) — P2
- [ ] SQL injection / XSS security audit — P1
- [ ] Test all form validations — P1

## Phase 5 — Deployment
- [ ] Choose hosting provider (cPanel recommended) — P1
- [x] Register domain: socialsouk.net — P1
- [ ] Set up MySQL database on hosting — P1
- [ ] Upload files via FTP/File Manager — P1
- [ ] Run schema.sql on production DB — P1
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
- [ ] !] Will payments be handled on-platform or off-platform (WhatsApp/cash)?
- [!] Who moderates halal badge claims?
- [!] Image hosting: server upload or link-based (URL)?
- [!] Which country/currency to target first?

---

*Last updated:* 2026-06-17
