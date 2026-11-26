#!/bin/bash
# Supabase to Aiven Migration Script (bash)
#
# WHY ONLY THE `public` SCHEMA: a Supabase database also contains
# auth / storage / realtime / extensions / graphql / vault schemas that belong
# to Supabase's own services. Dumping them would fail on Aiven (their roles do
# not exist there) and they are unused by this app -- Laravel only ever reads
# and writes `public` (see search_path in config/database.php).
#
# Usage:
#   export AIVEN_HOST=pg-osas-xyz.aivencloud.com
#   export AIVEN_PORT=5432
#   export AIVEN_DATABASE=defaultdb
#   export AIVEN_USERNAME=avnadmin
#   export AIVEN_PASSWORD=...
#   # source values come from .env (DB_* = Supabase today)
#   bash scripts/migrate-supabase-to-aiven.sh
set -euo pipefail

echo "================================="
echo "Supabase to Aiven Migration Script"
echo "================================="
echo ""

# --- Source: current Supabase connection (read from .env) --------------------
if [ -f .env ]; then
  set -a
  # shellcheck disable=SC1091
  . ./.env
  set +a
fi

SRC_HOST="${DB_HOST:?DB_HOST is not set in .env - cannot determine the source database}"
SRC_PORT="${DB_PORT:-5432}"
SRC_DATABASE="${DB_DATABASE:?DB_DATABASE is not set in .env}"
SRC_USERNAME="${DB_USERNAME:?DB_USERNAME is not set in .env}"
SRC_PASSWORD="${DB_PASSWORD:?DB_PASSWORD is not set in .env}"

# --- Destination: Aiven ------------------------------------------------------
AIVEN_HOST="${AIVEN_HOST:?Set AIVEN_HOST (Aiven console > service > Overview)}"
AIVEN_PORT="${AIVEN_PORT:-5432}"
AIVEN_DATABASE="${AIVEN_DATABASE:-defaultdb}"
AIVEN_USERNAME="${AIVEN_USERNAME:-avnadmin}"
AIVEN_PASSWORD="${AIVEN_PASSWORD:?Set AIVEN_PASSWORD}"

command -v pg_dump >/dev/null || { echo "✗ pg_dump not found"; exit 1; }
command -v psql    >/dev/null || { echo "✗ psql not found";    exit 1; }

DUMP_FILE="supabase_backup.sql"

# Step 1: Export from Supabase (public schema only)
echo "Step 1: Exporting public schema from $SRC_HOST ..."
PGPASSWORD="$SRC_PASSWORD" pg_dump \
  --host="$SRC_HOST" \
  --port="$SRC_PORT" \
  --username="$SRC_USERNAME" \
  --dbname="$SRC_DATABASE" \
  --schema=public \
  --no-owner \
  --no-acl \
  --clean \
  --if-exists \
  --file="$DUMP_FILE"

echo "✓ Export completed: $DUMP_FILE ($(du -h "$DUMP_FILE" | cut -f1))"
echo ""

# Step 2: Import into Aiven
echo "Step 2: Importing into $AIVEN_HOST ..."
PGPASSWORD="$AIVEN_PASSWORD" psql \
  --host="$AIVEN_HOST" \
  --port="$AIVEN_PORT" \
  --username="$AIVEN_USERNAME" \
  --dbname="$AIVEN_DATABASE" \
  --file="$DUMP_FILE" \
  --quiet

echo "✓ Import completed"
echo ""

# Step 3: Verify
echo "Step 3: Verifying migration..."
PGPASSWORD="$AIVEN_PASSWORD" psql \
  --host="$AIVEN_HOST" \
  --port="$AIVEN_PORT" \
  --username="$AIVEN_USERNAME" \
  --dbname="$AIVEN_DATABASE" \
  --command="SELECT COUNT(*) AS tables_in_public FROM pg_tables WHERE schemaname='public';"

echo ""
echo "================================="
echo "Migration Complete!"
echo "================================="
echo ""
echo "Next steps:"
echo "1. Update .env (DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD)"
echo "2. Test locally: php artisan migrate:status"
echo "3. Update Render: DB_HOST (render.yaml) + DB_DATABASE/DB_USERNAME/DB_PASSWORD in the dashboard"
echo "4. Once verified, delete the Supabase project"
echo ""
echo "Dump file saved: $DUMP_FILE (keep this safe, then delete it)"
