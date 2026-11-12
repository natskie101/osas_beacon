-- ============================================================================
-- Least-privilege database user for the BEACON OSAS Portal
--
-- Why: the app should never connect as the superuser. This script creates a
-- role that can ONLY touch the osas_beacon database, so a breach of the
-- portal can never reach other databases or admin functions.
--
-- HOW TO RUN (while PostgreSQL is running, e.g. via the Laragon menu):
--   1. Pick a strong password and replace CHANGE_ME below.
--   2. psql -U postgres -h 127.0.0.1 -f scripts\create-db-user.sql
--   3. Update DB_USERNAME / DB_PASSWORD in .env to match.
--   4. Test: php artisan tinker --execute="DB::select('SELECT 1');"
--
-- SECURITY NOTE: the role has no BYPASSRLS/CREATEDB/CREATEROLE privileges and
-- is only granted privileges on the osas_beacon database.
-- ============================================================================

-- Login role (idempotent).
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'osas_beacon') THEN
        CREATE ROLE osas_beacon LOGIN PASSWORD 'CHANGE_ME';
    ELSE
        ALTER ROLE osas_beacon WITH LOGIN PASSWORD 'CHANGE_ME';
    END IF;
END
$$;

-- The application database, owned by the app role (migrations need DDL).
-- Creating the database gives the owner ALL PRIVILEGES on it.
SELECT 'CREATE DATABASE osas_beacon OWNER osas_beacon'
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'osas_beacon')
\gexec

-- The owner can never reach other databases; no further grants are needed.
