# BEACON OSAS Portal

Server-rendered PHP + MySQL portal for the Office of Student Affairs and
Services. The application combines the Coordinator/Administrator workspace and
the student portal with authentication, policy-based access, audit logging,
notifications, CSV reports and printable HTML reports.

## Stack

- PHP 8.3+ (8.4 recommended), vanilla PHP with PDO
- MySQL 8 / MariaDB
- No framework or Composer dependencies
- Laravel-style layout to make a later framework port straightforward
- Original portal visual design and CSS preserved

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
