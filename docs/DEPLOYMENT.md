# BEACON OSAS Portal — Deployment Guide

The application is a server-rendered PHP 8.3+ / MySQL application. The web
document root must be the `public/` directory, never the repository root.

## Requirements

- PHP 8.3 or newer (PHP 8.4 recommended)
- PHP extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `openssl`
- MySQL 8.x or MariaDB 10.6+
- Apache with `mod_rewrite` (or Nginx with an equivalent front-controller rule)
- Write access for the web-server user to:
  - `storage/logs/`
  - `storage/framework/sessions/`
  - `storage/uploads/`

## 1. Create the database

Using MySQL CLI:

```sql
CREATE DATABASE osas_beacon
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'beacon_app'@'localhost' IDENTIFIED BY 'change-this-password';
GRANT ALL PRIVILEGES ON osas_beacon.* TO 'beacon_app'@'localhost';
FLUSH PRIVILEGES;
```

You can also let `database/migrate.php` create the database when the configured
MySQL user has permission to do so.

## 2. Configure the environment

Copy `.env.example` to `.env` and update the values:

```dotenv
APP_NAME="BEACON OSAS Portal"
APP_URL=https://portal.example.edu
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
APP_DEMO_MODE=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osas_beacon
DB_USERNAME=beacon_app
DB_PASSWORD=change-this-password

SESSION_NAME=beacon_session
SESSION_SECURE=true

MAIL_MAILER=mail
MAIL_FROM_ADDRESS=osas.support@uc.edu.ph
MAIL_FROM_NAME="BEACON OSAS Portal"
```

Notes:

- `APP_DEMO_MODE=true` shows demo login hints. Turn it off in production.
- `APP_DEBUG=false` must be used in production so stack traces are not exposed.
- `SESSION_SECURE=true` requires HTTPS.
- Keep `MAIL_MAILER=log` during local development. Mail is written to
  `storage/logs/mail.log`; use `mail` or an SMTP relay on real hosting.
- The `.env` file is gitignored and must never be committed.

## 3. Install and seed

From the project root:

```bash
php database/migrate.php
php database/seed.php
```

Use `php database/seed.php --fresh` only for demo environments; it clears the
existing portal data before importing the seeded dataset.

Demo accounts after reseeding (password `Beacon@2026`):

| Role          | Email                       |
| ------------- | --------------------------- |
| Coordinator   | `josefina.reyes@uc.edu.ph`  |
| Administrator | `technical.admin@uc.edu.ph` |
| Student       | `ryan.delacruz@uc.edu.ph`   |

## 4. Laragon virtual host

1. Start Apache and MySQL in Laragon.
2. Create the site with **Menu → Apache → sites-enabled → Add site**.
3. Set the document root to:

   ```text
   C:\path\to\osas_beacon\public
   ```

4. Use a host such as `osas-beacon.test`.
5. Open `http://osas-beacon.test/login`.

Laragon's `public/.htaccess` already sends non-file requests to `index.php`.

## 5. XAMPP / Apache virtual host

Add a virtual host in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName osas-beacon.test
    DocumentRoot "C:/xampp/htdocs/osas_beacon/public"

    <Directory "C:/xampp/htdocs/osas_beacon/public">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/osas-beacon-error.log"
    CustomLog "logs/osas-beacon-access.log" combined
</VirtualHost>
```

Add the host entry to `C:\Windows\System32\drivers\etc\hosts`:

```text
127.0.0.1 osas-beacon.test
```

Restart Apache and open `http://osas-beacon.test/login`.

## 6. Local development server (no Apache)

```powershell
C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe -S 127.0.0.1:8081 -t public public/router.php
```

Then open `http://127.0.0.1:8081/login`.

## 7. Nginx equivalent

```nginx
server {
    listen 80;
    server_name osas-beacon.test;
    root /var/www/osas_beacon/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 8. Post-deployment checks

- Visit `/login` and sign in as each role.
- Confirm the student portal only exposes that student's records.
- Confirm an Administrator cannot edit Coordinator/Administrator accounts.
- Download a report CSV and open its print view.
- Submit a support reply and confirm the student bell updates.
- Upload a school ID or announcement attachment and download it through the
  authorised route.
- Check `storage/logs/mail.log` if `MAIL_MAILER=log`.
- Confirm `APP_DEBUG=false`, `APP_DEMO_MODE=false`, and HTTPS redirects.

## 9. Common problems

**`Unknown database 'osas_beacon'`**

Run `php database/migrate.php` against the same MySQL instance configured in
`.env`. If Laragon was upgraded, check that its data directory and port match
`DB_PORT`.

**404 for every route**

Apache `mod_rewrite` is disabled, `AllowOverride` is off, or the document root
is the repository root instead of `public/`.

**500 after upload or storage actions**

Check that `storage/uploads/` and `storage/logs/` are writable by the web user.
Also inspect `storage/logs/php-error.log`.

**Users cannot stay signed in**

Check `SESSION_NAME`, cookie permissions, `APP_URL`, and set
`SESSION_SECURE=true` only when the site is served over HTTPS.

## 10. Legacy URLs

The original static pages are archived in `legacy/` for reference. Requests for
old `.html` URLs are redirected by `LegacyRedirectController` after login. Do
not set the document root to `legacy/`.
