# Neon to Supabase Migration Guide

## Current Status
- **Local `.env`**: Already pointing to Supabase (empty database)
- **Production (Render)**: `render.yaml` updated to point to Supabase
- **Neon Database**: Contains your production data (needs to be exported)

## Migration Steps

### Step 1: Get Your Neon Credentials

You need three pieces of information from your Neon database:

#### Option A: From Render Dashboard
1. Go to https://dashboard.render.com
2. Click on your `osas-beacon` service
3. Go to **Environment** tab
4. Find these values:
   - `DB_DATABASE` = ?
   - `DB_USERNAME` = ?
   - `DB_PASSWORD` = ?

#### Option B: From Neon Console
1. Go to https://console.neon.tech
2. Select your project
3. Go to **Connection Details**
4. Copy the connection string or individual values

**Neon Host** (from render.yaml): `ep-weathered-hat-a1ets3w2.ap-southeast-1.aws.neon.tech`

---

### Step 2: Export from Neon

Once you have the credentials, run this command in PowerShell:

```powershell
$env:PGPASSWORD="<your_neon_password>"
& "C:\laragon\bin\postgresql\postgresql\bin\pg_dump.exe" `
  --host="ep-weathered-hat-a1ets3w2.ap-southeast-1.aws.neon.tech" `
  --port="5432" `
  --username="<your_neon_username>" `
  --dbname="<your_neon_database>" `
  --no-owner `
  --no-acl `
  --clean `
  --if-exists `
  --file="neon_backup.sql"
```

**Note**: Neon requires SSL, so this should work. If you get an SSL error, the pg_dump command will automatically use SSL.

---

### Step 3: Import to Supabase

After the export completes, import to Supabase:

```powershell
$env:PGPASSWORD="UcUbUECqfz/_9@9"
& "C:\laragon\bin\postgresql\postgresql\bin\psql.exe" `
  --host="db.auzgbleenlypuxogwanx.supabase.co" `
  --port="5432" `
  --username="postgres" `
  --dbname="postgres" `
  --file="neon_backup.sql"
```

---

### Step 4: Verify the Migration

Check that your tables were imported:

```powershell
$env:PGPASSWORD="UcUbUECqfz/_9@9"
& "C:\laragon\bin\postgresql\postgresql\bin\psql.exe" `
  --host="db.auzgbleenlypuxogwanx.supabase.co" `
  --port="5432" `
  --username="postgres" `
  --dbname="postgres" `
  --command="SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;"
```

---

### Step 5: Test Locally

Your local `.env` is already configured for Supabase. Test your application:

```powershell
& "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" artisan migrate:status
```

If everything looks good, start your local server and test:

```powershell
& "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" artisan serve
```

---

### Step 6: Update Production (Render)

1. Go to https://dashboard.render.com
2. Find your `osas-beacon` service
3. Go to **Environment** tab
4. Update these three variables:
   ```
   DB_DATABASE = postgres
   DB_USERNAME = postgres
   DB_PASSWORD = UcUbUECqfz/_9@9
   ```
5. Render will automatically redeploy

---

### Step 7: Security - Rotate Credentials

**IMPORTANT**: After the migration succeeds, rotate your Supabase password since it's been exposed in this conversation:

1. Go to https://supabase.com/dashboard/project/auzgbleenlypuxogwanx/settings/database
2. Reset the database password
3. Update:
   - Local `.env` → `DB_PASSWORD=<new_password>`
   - Render dashboard → `DB_PASSWORD=<new_password>`

---

## Troubleshooting

### Error: "password authentication failed"
- Double-check your Neon credentials
- Make sure you're using the exact values from Render dashboard or Neon console

### Error: "connection is insecure"
- This is normal with Neon - the pg_dump command should automatically use SSL
- If it persists, add `PGSSLMODE=require` before the command:
  ```powershell
  $env:PGSSLMODE="require"
  $env:PGPASSWORD="<password>"
  ```

### Error: "relation already exists" during import
- This is usually safe to ignore if using `--clean --if-exists`
- The import will drop existing objects before creating new ones

### No tables after import
- Check the neon_backup.sql file to see if it contains data
- Verify you exported from the correct database

---

## Need Help?

If you encounter any issues, share:
1. The exact error message
2. Which step you're on
3. The neon_backup.sql file size (to confirm export worked)
