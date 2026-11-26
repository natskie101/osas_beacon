#!/bin/bash
set -e

echo "================================"
echo "Neon to Supabase Migration Script"
echo "================================"
echo ""

# Neon connection details
NEON_HOST="ep-weathered-hat-a1ets3w2.ap-southeast-1.aws.neon.tech"
NEON_PORT="5432"
NEON_DATABASE="$DB_DATABASE"
NEON_USERNAME="$DB_USERNAME"
NEON_PASSWORD="$DB_PASSWORD"

# Supabase connection details
SUPABASE_HOST="db.auzgbleenlypuxogwanx.supabase.co"
SUPABASE_PORT="5432"
SUPABASE_DATABASE="postgres"
SUPABASE_USERNAME="postgres"
SUPABASE_PASSWORD="$SUPABASE_PASSWORD"

# Step 1: Export schema and data from Neon
echo "Step 1: Exporting from Neon..."
PGPASSWORD="$NEON_PASSWORD" pg_dump \
  --host="$NEON_HOST" \
  --port="$NEON_PORT" \
  --username="$NEON_USERNAME" \
  --dbname="$NEON_DATABASE" \
  --no-owner \
  --no-acl \
  --clean \
  --if-exists \
  --file="neon_backup.sql"

echo "✓ Export completed: neon_backup.sql"
echo ""

# Step 2: Review the dump (optional - uncomment to see what will be imported)
# echo "Review the first 50 lines of the dump:"
# head -n 50 neon_backup.sql
# echo ""
# read -p "Press Enter to continue with import..."

# Step 3: Import into Supabase
echo "Step 2: Importing to Supabase..."
PGPASSWORD="$SUPABASE_PASSWORD" psql \
  --host="$SUPABASE_HOST" \
  --port="$SUPABASE_PORT" \
  --username="$SUPABASE_USERNAME" \
  --dbname="$SUPABASE_DATABASE" \
  --file="neon_backup.sql" \
  --quiet

echo "✓ Import completed"
echo ""

# Step 4: Verify the migration
echo "Step 3: Verifying migration..."
PGPASSWORD="$SUPABASE_PASSWORD" psql \
  --host="$SUPABASE_HOST" \
  --port="$SUPABASE_PORT" \
  --username="$SUPABASE_USERNAME" \
  --dbname="$SUPABASE_DATABASE" \
  --command="SELECT schemaname, tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;"

echo ""
echo "================================"
echo "Migration Complete!"
echo "================================"
echo ""
echo "Next steps:"
echo "1. Test your application with the new Supabase connection"
echo "2. Update your .env file with Supabase credentials"
echo "3. Update Render environment variables"
echo "4. Once verified, you can decommission the Neon database"
