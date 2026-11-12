-- ============================================================================
-- Least-privilege database user for the BEACON OSAS Portal
--
-- Why: the app currently connects as root with no password. This script
-- creates a user that can ONLY touch the osas_beacon database, so a breach of
-- the portal can never reach other databases or admin functions.
--
-- HOW TO RUN (while MySQL is running, e.g. via the Laragon menu):
--   1. Pick a strong password and replace CHANGE_ME below.
--   2. mysql -u root -p < scripts\create-db-user.sql
--   3. Update DB_USERNAME / DB_PASSWORD in .env to match.
--   4. Test: php artisan tinker --execute="DB::select('SELECT 1');"
--
-- SECURITY NOTE: 'localhost' only — this user cannot connect remotely.
-- ============================================================================

CREATE USER IF NOT EXISTS 'osas_beacon'@'localhost' IDENTIFIED BY 'CHANGE_ME';
ALTER USER 'osas_beacon'@'localhost' IDENTIFIED BY 'CHANGE_ME';

-- Full rights on the app database only (migrations need DDL).
GRANT ALL PRIVILEGES ON osas_beacon.* TO 'osas_beacon'@'localhost';

-- Explicitly revoke anything outside the app database.
REVOKE ALL PRIVILEGES ON *.* FROM 'osas_beacon'@'localhost';
GRANT ALL PRIVILEGES ON osas_beacon.* TO 'osas_beacon'@'localhost';

FLUSH PRIVILEGES;
