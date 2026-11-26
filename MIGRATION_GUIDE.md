# Supabase to Aiven Migration Guide

## What actually depends on Supabase

**Nothing but the Postgres connection string.** Verified before writing this guide:

| Concern | Where it actually lives |
|---|---|
| Database | Supabase Postgres (`pgsql` driver) — **this is what we're moving** |
| Authentication | Laravel's own `auth_*` tables + custom controllers (`app/Http/Controllers/Auth/`) |
| File storage | Cloudflare R2 (`app/Services/R2Storage.php`) |
| Email | Brevo / Gmail SMTP |
| Realtime, PostgREST, Edge Functions, Supabase Auth, Supabase Storage | **Not used at all** |

There are no Supabase extensions, RLS policies, `auth.uid()` functions, or RPC/PostgREST calls anywhere in `database/migrations/` or `app/`. It's a plain Postgres database, so Aiven is a drop-in replacement.

**Current state of the Supabase database:** 30 tables in `public`, **zero rows** (only the `migrations` table has 23 rows). There is no production data to carry over — only schema.

Supabase also owns these schemas, which are **not** part of your app and must not be copied: `auth`, `storage`, `realtime`, `extensions`, `graphql`, `graphql_public`, `vault`.

---

## Step 1: Create the Aiven service

1. Sign in at <https://console.aiven.io/> and **Create a service**.
2. Service type: **Aiven for PostgreSQL**.
3. Pick a cloud/region near your Render region (Render uses `singapore`, so choose a Singapore/Asia region if one is offered — lower latency between app and DB).
4. Choose your plan, then **Create**. Startup takes a few minutes.

### Step 1b: Get the connection details

On the service **Overview** page, open **Connection Information** and copy:

| Field | Your value |
|---|---|
| `DB_HOST` | `pg-2c2d6ca1-carlosnath991-aec2.h.aivencloud.com` |
| `DB_PORT` | **`26572`** — *not* 5432 |
| `DB_DATABASE` | `defaultdb` — *not* `postgres` |
| `DB_USERNAME` | `avnadmin` |
| `DB_PASSWORD` | set in `.env` |

> **⚠️ Two things in this table will silently break you:**
>
> 1. **Port `26572`, not 5432.** Aiven assigns a random port when 5432 is taken.
>    Copy it exactly — a stale `5432` here is the single most likely cause of a
>    future "connection refused".
> 2. **Database `defaultdb`, not `postgres`.** Aiven's Postgres ships with
>    `defaultdb` (plus its own internal `_aiven`). There is no `postgres`
>    database, and you get `FATAL: database "postgres" does not exist` rather
>    than a helpful "unknown database".
>
> Port 6432 is Aiven's pgbouncer pooler — don't use it either. Laravel uses PDO
> prepared statements and `DB::transaction()` (sessions, migrations), which
> misbehave through pgbouncer unless it's in transaction pooling mode. Same
> reason your old Supabase note said "use the direct connection."

> **Allow inbound traffic.** Aiven services have IP allowlists. Add your dev machine's public IP and Render's egress IPs, or temporarily allow `0.0.0.0/0` while migrating (tighten afterwards).

---

## Step 2: Migrate the schema — ✅ DONE

Supabase had 30 tables but **zero rows** (only `migrations` had 23), so there was
no data to copy. Option A was taken: schema rebuilt from Laravel migrations.

```powershell
# .env now points at Aiven
& "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" artisan migrate --force
```

All **23 migrations ran** against `defaultdb` (batch 1).

<details>
<summary>Option B — dump & restore instead (kept for reference)</summary>

Only needed if Supabase ever had real data. Dumps **only `public`**
(`--schema=public --no-owner --no-acl --clean --if-exists`) so Supabase's
`auth`/`storage`/`realtime`/`extensions`/`graphql`/`vault` schemas never reach
Aiven — they'd fail there (their roles don't exist) and the app doesn't use them.

```powershell
# Windows (prompts for the Aiven password; nothing stored in the script)
.\scripts\migrate-supabase-to-aiven.ps1
```

```bash
# Linux / macOS
export AIVEN_HOST=pg-2c2d6ca1-carlosnath991-aec2.h.aivencloud.com
export AIVEN_PORT=26572
export AIVEN_DATABASE=defaultdb
export AIVEN_USERNAME=avnadmin
export AIVEN_PASSWORD=...
bash scripts/migrate-supabase-to-aiven.sh
```

</details>

---

## Step 3: Verify — ✅ DONE

```powershell
& "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" artisan migrate:status
& "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" scripts\aiven-connection-probe.php
```

Latest run:

```
database: defaultdb
user:     avnadmin
server:   PostgreSQL 18.7 on x86_64-pc-linux-gnu ...
tables:   30

OK - PDO connection works.
```

`migrate:status` reports all 23 as `Ran`.

> Your local `pg_dump` is probably older than Aiven's **PostgreSQL 18**. If you
> ever use Option B and hit `server version mismatch`, either install a newer
> client or use Option A.

---

## Step 4: Update local `.env` — ✅ DONE

```
DB_CONNECTION="pgsql"
DB_HOST="pg-2c2d6ca1-carlosnath991-aec2.h.aivencloud.com"
DB_PORT="26572"
DB_DATABASE="defaultdb"
DB_USERNAME="avnadmin"
DB_PASSWORD=<your Aiven password>
DB_SSLMODE="require"
```

---

## Step 5: Update production (Render)

`render.yaml` now has `DB_HOST: REPLACE_WITH_AIVEN_HOST` — replace that with your real Aiven host, commit, and Render will redeploy.

Then in the **Render dashboard → osas-beacon → Environment**, set the `sync: false` variables it can't infer:

```
DB_DATABASE = defaultdb
DB_USERNAME  = avnadmin
DB_PASSWORD  = <your_aiven_password>
```

---

## Step 6: Lock it down

1. **Tighten the Aiven IP allowlist** — remove `0.0.0.0/0` if you added it.
2. **Rotate the `avnadmin` password** if it was ever printed anywhere.
3. **Delete the Supabase project** once the app has run against Aiven for a while. Deleting it is the only way to be sure nothing still points at it.
4. Delete the dump file if you used Option B: `Remove-Item supabase_backup.sql`.

---

## Troubleshooting

**`password authentication failed`**
Double-check the password — Aiven shows it once, and it's easy to grab the *service* password instead of the *database* password. Reset it from the Overview page if unsure.

**`Connection refused` / times out**
Almost always the **IP allowlist**. Your public IP and Render's IPs must be permitted.

**`SSL connection required` / `server does not support SSL`**
Set `DB_SSLMODE=require` in `.env` (Aiven mandates TLS).

**`pg_dump: error: aborting because of server version mismatch`**
Your local `pg_dump` is older than Aiven's Postgres (Aiven ships 15–17). Install a matching or newer client, or use Option A.

**`role "anon" does not exist` or `schema "auth" does not exist` during import**
Something dumped more than `public`. Re-run the script as-is — it passes `--schema=public` — or import manually with that flag.

**`relation already exists`**
Harmless with `--clean --if-exists`. If it persists and the DB is disposable: `DROP SCHEMA public CASCADE; CREATE SCHEMA public;` on **Aiven only**, then re-import.

**Afterwards: `php artisan` hangs or the app times out**
Check you pointed at port `5432` and not `6432`.
