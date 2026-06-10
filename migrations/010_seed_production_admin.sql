-- Migration 010: Production admin account seed
-- Database: survevap_ux_shop (remote production) or uxmerchandise (local)
--
-- Login email : admin@uxpacific.com  (admin@UXPacific.com — case-insensitive)
-- Password    : UXP@512#0105
--
-- Admin panel auth (api/auth/admin-login.php) checks the `users` table
-- with role = 'admin'. The `admins` table is kept in sync for reference.
--
-- Run once in DBeaver / phpMyAdmin on the remote database:
--   SOURCE migrations/010_seed_production_admin.sql;
-- Or paste and execute this file.

SET NAMES utf8mb4;

-- ── 1. Admin user (required for admin panel login) ───────────────────────────
INSERT INTO users (
    email,
    password_hash,
    first_name,
    last_name,
    phone,
    role,
    is_blocked
) VALUES (
    'admin@uxpacific.com',
    '$2y$10$CGAog74dwsw26iVN7Ag7QOMbMWhWT5.L.VG1qCZ12StoYlluqgPtW',
    'UX Pacific',
    'Admin',
    NULL,
    'admin',
    0
)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    first_name    = VALUES(first_name),
    last_name     = VALUES(last_name),
    role          = 'admin',
    is_blocked    = 0,
    updated_at    = CURRENT_TIMESTAMP;

-- ── 2. admins table row (schema / audit trail) ───────────────────────────────
INSERT INTO admins (
    user_id,
    email,
    password_hash,
    name,
    is_active
)
SELECT
    u.id,
    u.email,
    u.password_hash,
    'UX Pacific Admin',
    1
FROM users u
WHERE LOWER(u.email) = 'admin@uxpacific.com'
LIMIT 1
ON DUPLICATE KEY UPDATE
    user_id       = VALUES(user_id),
    password_hash = VALUES(password_hash),
    name          = VALUES(name),
    is_active     = 1;
