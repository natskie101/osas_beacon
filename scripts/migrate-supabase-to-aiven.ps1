# Supabase to Aiven Migration Script (PowerShell)
# Copies the public schema (and any data in it) from Supabase to Aiven.
#
# WHY ONLY THE `public` SCHEMA: a Supabase database also contains
# auth / storage / realtime / extensions / graphql / vault schemas that belong
# to Supabase's own services. Dumping them would fail on Aiven (their roles do
# not exist there) and they are unused by this app -- Laravel only ever reads
# and writes `public` (see search_path in config/database.php).

$ErrorActionPreference = "Stop"

Write-Host "=================================" -ForegroundColor Cyan
Write-Host "Supabase to Aiven Migration Script" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Load environment variables from .env (source values: whatever DB_* points at
# right now, i.e. Supabase).
if (Test-Path .env) {
    Get-Content .env | ForEach-Object {
        if ($_ -match '^\s*([^#][^=]+?)\s*=\s*(.+?)\s*$') {
            [Environment]::SetEnvironmentVariable($matches[1], $matches[2].Trim('"'), "Process")
        }
    }
}

# --- Source: current Supabase connection -------------------------------------
$SRC_HOST     = $env:DB_HOST
$SRC_PORT     = if ($env:DB_PORT) { $env:DB_PORT } else { "5432" }
$SRC_DATABASE = $env:DB_DATABASE
$SRC_USERNAME = $env:DB_USERNAME
$SRC_PASSWORD = $env:DB_PASSWORD

if (-not $SRC_HOST) {
    Write-Host "✗ DB_HOST is not set in .env - cannot determine the source database." -ForegroundColor Red
    exit 1
}

# --- Destination: Aiven ------------------------------------------------------
# Find these on the Aiven console service Overview page -> Connection Information.
Write-Host "Enter your Aiven connection details (Aiven console > service > Overview):" -ForegroundColor Yellow
$AIVEN_HOST     = Read-Host "  Aiven host (e.g. pg-osas-xyz.aivencloud.com)"
$AIVEN_PORT     = Read-Host "  Aiven port [5432]"
if ([string]::IsNullOrWhiteSpace($AIVEN_PORT)) { $AIVEN_PORT = "5432" }
$AIVEN_DATABASE = Read-Host "  Aiven database [defaultdb]"
if ([string]::IsNullOrWhiteSpace($AIVEN_DATABASE)) { $AIVEN_DATABASE = "defaultdb" }
$AIVEN_USERNAME = Read-Host "  Aiven username [avnadmin]"
if ([string]::IsNullOrWhiteSpace($AIVEN_USERNAME)) { $AIVEN_USERNAME = "avnadmin" }

Write-Host "  Aiven password:" -ForegroundColor Yellow
$AIVEN_PASSWORD_SECURE = Read-Host -AsSecureString
$AIVEN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($AIVEN_PASSWORD_SECURE)
)

Write-Host ""

# --- Check for PostgreSQL client tools ---------------------------------------
Write-Host "Checking for PostgreSQL tools..." -ForegroundColor Yellow
try {
    Write-Host "✓ Found: $(pg_dump --version)" -ForegroundColor Green
    Write-Host "✓ Found: $(psql --version)" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: pg_dump/psql not found!" -ForegroundColor Red
    Write-Host "Install PostgreSQL client tools, or use Laragon:" -ForegroundColor Yellow
    Write-Host "  Tools > Quick add > PostgreSQL, then add its bin dir to PATH" -ForegroundColor Cyan
    exit 1
}

Write-Host ""

# Step 1: Export from Supabase (public schema only)
$DUMP_FILE = "supabase_backup.sql"
Write-Host "Step 1: Exporting public schema from $SRC_HOST ..." -ForegroundColor Yellow
$env:PGPASSWORD = $SRC_PASSWORD

try {
    # --schema=public   : skip Supabase-managed auth/storage/realtime/etc schemas
    # --no-owner/--no-acl: Aiven's avnadmin owns everything; do not carry over
    #                       Supabase role ownership or grants
    # --clean --if-exists: safe to re-run against a database that already has tables
    & pg_dump `
        --host=$SRC_HOST `
        --port=$SRC_PORT `
        --username=$SRC_USERNAME `
        --dbname=$SRC_DATABASE `
        --schema=public `
        --no-owner `
        --no-acl `
        --clean `
        --if-exists `
        --file=$DUMP_FILE

    Write-Host "✓ Export completed: $DUMP_FILE" -ForegroundColor Green
    Write-Host "  File size: $([math]::Round((Get-Item $DUMP_FILE).Length / 1KB, 2)) KB" -ForegroundColor Gray
} catch {
    Write-Host "✗ Export failed: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Review the first 50 lines of the dump:" -ForegroundColor Yellow
Get-Content $DUMP_FILE -TotalCount 50 | ForEach-Object { Write-Host "  $_" -ForegroundColor DarkGray }
Write-Host ""

$continue = Read-Host "Continue with import to Aiven? (y/N)"
if ($continue -ne "y" -and $continue -ne "Y") {
    Write-Host "Migration cancelled. Dump kept at $DUMP_FILE" -ForegroundColor Yellow
    exit 0
}

Write-Host ""

# Step 2: Import into Aiven
Write-Host "Step 2: Importing into $AIVEN_HOST ..." -ForegroundColor Yellow
$env:PGPASSWORD = $AIVEN_PASSWORD

try {
    & psql `
        --host=$AIVEN_HOST `
        --port=$AIVEN_PORT `
        --username=$AIVEN_USERNAME `
        --dbname=$AIVEN_DATABASE `
        --file=$DUMP_FILE `
        --quiet
    Write-Host "✓ Import completed" -ForegroundColor Green
} catch {
    Write-Host "✗ Import failed: $_" -ForegroundColor Red
    Write-Host "The dump is kept at $DUMP_FILE - review it and retry manually." -ForegroundColor Yellow
    exit 1
}

Write-Host ""

# Step 3: Verify
Write-Host "Step 3: Verifying migration..." -ForegroundColor Yellow
$env:PGPASSWORD = $AIVEN_PASSWORD

try {
    $tableCount = (& psql --host=$AIVEN_HOST --port=$AIVEN_PORT --username=$AIVEN_USERNAME `
        --dbname=$AIVEN_DATABASE --tuples-only --quiet `
        --command="SELECT COUNT(*) FROM pg_tables WHERE schemaname='public';").Trim()
    Write-Host "✓ Found $tableCount tables in Aiven public schema" -ForegroundColor Green

    $migrationCount = (& psql --host=$AIVEN_HOST --port=$AIVEN_PORT --username=$AIVEN_USERNAME `
        --dbname=$AIVEN_DATABASE --tuples-only --quiet `
        --command="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='migrations';").Trim()

    if ($migrationCount -eq "1") {
        $applied = (& psql --host=$AIVEN_HOST --port=$AIVEN_PORT --username=$AIVEN_USERNAME `
            --dbname=$AIVEN_DATABASE --tuples-only --quiet `
            --command="SELECT COUNT(*) FROM migrations;").Trim()
        Write-Host "✓ Laravel migrations table present ($applied applied)" -ForegroundColor Green
    } else {
        Write-Host "! 'migrations' table missing - run: php artisan migrate" -ForegroundColor Yellow
    }
} catch {
    Write-Host "✗ Verification failed: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update .env (DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD)" -ForegroundColor White
Write-Host "2. Test locally:" -ForegroundColor White
Write-Host "   php artisan migrate:status" -ForegroundColor Gray
Write-Host "   php artisan tinker --execute=`"DB::select('SELECT 1');`"" -ForegroundColor Gray
Write-Host "3. Update Render: DB_HOST (render.yaml) + DB_DATABASE/DB_USERNAME/DB_PASSWORD in the dashboard" -ForegroundColor White
Write-Host "4. Once verified, delete the Supabase project" -ForegroundColor White
Write-Host ""
Write-Host "Dump file saved: $DUMP_FILE (keep this safe, then delete it)" -ForegroundColor Yellow
