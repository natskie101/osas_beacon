# BEACON OSAS Portal

Server-rendered PHP + PostgreSQL portal for the Office of Student Affairs and
Services. The application combines the Coordinator/Administrator workspace and
the student portal with authentication, policy-based access, audit logging,
notifications, CSV reports and printable HTML reports.

## Stack

- PHP 8.3+ (8.4 recommended), Laravel 11
- PostgreSQL 14+
- Composer dependencies (Laravel framework)
- Laravel-style layout preserved from the original portal
- Original portal visual design and CSS preserved

## Technical Admin chart assets

The System Overview uses locally bundled Chart.js and Tailwind assets; no CDN
is required at runtime. After installing the Node dependencies, rebuild the
Technical Admin assets with:

```bash
npm install
npm run build:technical-assets
```

The generated styles and chart scripts are loaded only on the Technical Admin
System Overview page.

## Quick start

1. Copy `.env.example` to `.env` and set the database credentials.
2. Create/migrate the database and import the demo dataset:

   ```bash
   php database/migrate.php
   php database/seed.php --fresh
   ```

3. Serve the `public/` directory:

   ```powershell
   C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe -S 127.0.0.1:8081 -t public public/router.php
   ```

4. Open <http://127.0.0.1:8081/login>.

Demo password: `Beacon@2026`

| Role | Email |
| --- | --- |
| Coordinator | `josefina.reyes@uc.edu.ph` |
| Administrator | `technical.admin@uc.edu.ph` |
| Student | `ryan.delacruz@uc.edu.ph` |

### Clean hosting test setup

For a new, empty hosting database, configure unique passwords of at least 12
characters for `SEED_COORDINATOR_PASSWORD`, `SEED_STUDENT_PASSWORD`, and
`SEED_ADMIN_PASSWORD`, then run:

```bash
php database/migrate.php
php database/seed.php --fresh --minimal
```

Minimal seeding creates only the Coordinator (`josefina.reyes@uc.edu.ph`),
Ryan Dela Cruz's student account (`ryan.delacruz@uc.edu.ph`), and the
Administrator (`technical.admin@uc.edu.ph`). It skips all sample content,
synthetic progress, notifications, and activity records. Remove the temporary
`SEED_*_PASSWORD` values from the hosting environment after seeding. The
`--fresh` option deletes existing database records, so use it only with a new
test database. The regular `--fresh` command still loads the full demo dataset.

## Features

- Coordinator and Administrator dashboards with role-scoped navigation
- Public student account creation with school-ID verification
- User account management, verification, school-ID uploads and activity
- Announcements with rich HTML sanitising and authorised attachments
- Rules and regulations library with sanctions and publishing workflow
- Orientation modules, student progress and completion tracking
- Events, lost and found, claims and document request processing
- Student support tickets with threaded replies, statuses and ratings
- Broadcasts delivered to portal notifications and logged email
- Per-user notification bell with read/unread state
- Viewing analytics, audit trail and report generation
- CSV exports and server-rendered printable report pages

## Project layout

```text
app/                 HTTP controllers, middleware, models, services, support
bootstrap/           application and console bootstrapping
config/              environment-backed configuration
database/            numbered migrations, seeders and seed data
docs/                deployment documentation
legacy/              archived static portal, assets and QA harness
public/              web document root and front controller
resources/views/     server-rendered PHP views
routes/web.php       declarative application routes
storage/             logs, sessions and protected uploads
```

## Security notes

- Passwords use `password_hash()` / `password_verify()`.
- CSRF tokens are verified on state-changing requests.
- Sessions are server-side and support remember-me tokens.
- Login attempts are throttled.
- Uploads are validated by MIME type and size and stored outside the document
  root; downloads stream only through authorised routes.
- Role and record access is enforced server-side, including student ownership.
- Audit logging records account, content, document, support and claim actions.

## Deployment

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for Laragon, XAMPP, Apache, Nginx
and production configuration.
