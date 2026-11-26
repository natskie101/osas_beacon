# Neon to Supabase Migration Script (PowerShell)
# Run this script to migrate your database from Neon to Supabase

$ErrorActionPreference = "Stop"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "Neon to Supabase Migration Script" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Load environment variables from .env
if (Test-Path .env) {
    Get-Content .env | ForEach-Object {
        if ($_ -match '^\s*([^#][^=]+?)\s*=\s*(.+?)\s*$') {
            $name = $matches[1]
            $value = $matches[2]
            [Environment]::SetEnvironmentVariable($name, $value, "Process")
        }
    }
}

# Neon connection details (from your current .env)
$NEON_HOST = "ep-weathered-hat-a1ets3w2.ap-southeast-1.aws.neon.tech"
$NEON_PORT = "5432"
$NEON_DATABASE = $env:DB_DATABASE
$NEON_USERNAME = $env:DB_USERNAME
$NEON_PASSWORD = $env:DB_PASSWORD

# Supabase connection details
$SUPABASE_HOST = "db.auzgbleenlypuxogwanx.supabase.co"
$SUPABASE_PORT = "5432"
$SUPABASE_DATABASE = "postgres"
$SUPABASE_USERNAME = "postgres"

# Prompt for Supabase password (more secure than storing in script)
Write-Host "Enter your Supabase database password:" -ForegroundColor Yellow
$SUPABASE_PASSWORD_SECURE = Read-Host -AsSecureString
$SUPABASE_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SUPABASE_PASSWORD_SECURE)
)

Write-Host ""

# Check if pg_dump is available
Write-Host "Checking for PostgreSQL tools..." -ForegroundColor Yellow
try {
    $pgDumpVersion = & pg_dump --version 2>&1
    Write-Host "✓ Found: $pgDumpVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: pg_dump not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PostgreSQL client tools:" -ForegroundColor Yellow
    Write-Host "1. Download from: https://www.postgresql.org/download/windows/" -ForegroundColor Cyan
    Write-Host "2. Or install via Laragon (Tools > Quick add > PostgreSQL)" -ForegroundColor Cyan
    Write-Host "3. Add to PATH: C:\Program Files\PostgreSQL\16\bin" -ForegroundColor Cyan
    exit 1
}

Write-Host ""

# Step 1: Export from Neon
Write-Host "Step 1: Exporting from Neon..." -ForegroundColor Yellow
$env:PGPASSWORD = $NEON_PASSWORD

try {
    & pg_dump `
        --host=$NEON_HOST `
        --port=$NEON_PORT `
        --username=$NEON_USERNAME `
        --dbname=$NEON_DATABASE `
        --no-owner `
        --no-acl `
        --clean `
        --if-exists `
        --file="neon_backup.sql"
    
    Write-Host "✓ Export completed: neon_backup.sql" -ForegroundColor Green
    
    $fileSize = (Get-Item neon_backup.sql).Length / 1KB
    Write-Host "  File size: $([math]::Round($fileSize, 2)) KB" -ForegroundColor Gray
} catch {
    Write-Host "✗ Export failed: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 2: Review what will be imported
Write-Host "Step 2: Reviewing backup contents..." -ForegroundColor Yellow
$lines = Get-Content neon_backup.sql -TotalCount 50
Write-Host "First 50 lines of backup:" -ForegroundColor Gray
$lines | ForEach-Object { Write-Host "  $_" -ForegroundColor DarkGray }
Write-Host ""

$continue = Read-Host "Continue with import to Supabase? (y/N)"
if ($continue -ne "y" -and $continue -ne "Y") {
    Write-Host "Migration cancelled." -ForegroundColor Yellow
    exit 0
}

Write-Host ""

# Step 3: Import to Supabase
Write-Host "Step 3: Importing to Supabase..." -ForegroundColor Yellow
$env:PGPASSWORD = $SUPABASE_PASSWORD

try {
    & psql `
        --host=$SUPABASE_HOST `
        --port=$SUPABASE_PORT `
        --username=$SUPABASE_USERNAME `
        --dbname=$SUPABASE_DATABASE `
        --file="neon_backup.sql" `
        --quiet
    
    Write-Host "✓ Import completed" -ForegroundColor Green
} catch {
    Write-Host "✗ Import failed: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "The backup file is saved as neon_backup.sql" -ForegroundColor Yellow
    Write-Host "You can manually review and import it." -ForegroundColor Yellow
    exit 1
}

Write-Host ""

# Step 4: Verify the migration
Write-Host "Step 4: Verifying migration..." -ForegroundColor Yellow
$env:PGPASSWORD = $SUPABASE_PASSWORD

try {
    $result = & psql `
        --host=$SUPABASE_HOST `
        --port=$SUPABASE_PORT `
        --username=$SUPABASE_USERNAME `
        --dbname=$SUPABASE_DATABASE `
        --tuples-only `
        --command="SELECT COUNT(*) as table_count FROM pg_tables WHERE schemaname = 'public';"
    
    $tableCount = $result.Trim()
    Write-Host "✓ Found $tableCount tables in public schema" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Tables in Supabase:" -ForegroundColor Cyan
    & psql `
        --host=$SUPABASE_HOST `
        --port=$SUPABASE_PORT `
        --username=$SUPABASE_USERNAME `
        --dbname=$SUPABASE_DATABASE `
        --command="SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;"
} catch {
    Write-Host "✗ Verification failed: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Migration Complete!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update your local .env file:" -ForegroundColor White
Write-Host "   DB_HOST=db.auzgbleenlypuxogwanx.supabase.co" -ForegroundColor Gray
Write-Host "   DB_DATABASE=postgres" -ForegroundColor Gray
Write-Host "   DB_USERNAME=postgres" -ForegroundColor Gray
Write-Host "   DB_PASSWORD=<your_supabase_password>" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Test your application locally" -ForegroundColor White
Write-Host ""
Write-Host "3. Update Render environment variables (already done in render.yaml)" -ForegroundColor White
Write-Host "   - Set DB_DATABASE, DB_USERNAME, DB_PASSWORD in Render dashboard" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Once verified, you can decommission the Neon database" -ForegroundColor White
Write-Host ""
Write-Host "Backup file saved: neon_backup.sql (keep this safe!)" -ForegroundColor Yellow
