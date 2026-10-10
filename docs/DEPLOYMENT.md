# Deployment, Build, Backup & Update

> Phase 6. Reproducible build and deployment instructions for the modernised
> Easy!Appointments (branch `feature/premium-ui`, app version 1.6.0). All
> commands below were actually executed and verified in this environment unless
> explicitly marked otherwise.

## 1. Requirements

- PHP **8.2+** (verified on 8.3) with extensions: `curl`, `json`, `mbstring`,
  `gd`, `simplexml`, `fileinfo` (see `composer.json`).
- **MySQL / MariaDB** (verified on MariaDB 10.11). Table prefix `ea_`.
- Web server: Apache or Nginx (production). For local checks the PHP built-in
  server works (`php -S`).
- Build toolchain (only to build assets from source): **Node ≥ 14** (verified on
  22) and **Composer 2**.
- A working mail transport (SMTP or PHP `mail()`) for notification e-mails. See
  §7 — without one, bookings still succeed but e-mails are logged as failed.

## 2. Install from a release archive (production)

1. Create a database (or use an existing one).
2. Upload the `easyappointments` folder to the server.
3. Make the `storage/` directory writable by the web server.
4. Copy `config-sample.php` to `config.php` and set `BASE_URL` and the `DB_*`
   credentials (and `DEBUG_MODE = false` for production).
5. Open the app in a browser and follow the setup wizard, **or** install from the
   CLI: `php index.php console install` (creates the schema and a default
   `administrator` / `administrator` account — change the password immediately).

> Production serves the minified assets. `asset_url()` rewrites `*.js`/`*.css`
> to `*.min.*` when `DEBUG_MODE = false`, so the built assets must be present
> (they ship inside the release archive; when deploying from a git checkout you
> must build them first — see §3).

## 3. Build from source

The compiled assets (`assets/css/**/*.css`, `assets/js/**/*.min.js`,
`assets/vendor/`) and `config.php` are intentionally **git-ignored**, so a fresh
checkout needs a build before it can serve a production (non-debug) page.

```bash
composer install            # PHP dependencies (vendor/)
npm ci                      # Node build dependencies
npm start                   # dev: compile assets once, then watch (gulp default)
# or
npm run build               # production: compile assets AND create the release zip
```

What the tasks do (see `gulpfile.js`):

- `gulp vendor` — copies third-party dist files into `assets/vendor/`.
- `gulp scripts` — Babel-transpiles and minifies JS to `*.min.js` (via
  `gulp-terser`).
- `gulp styles` — compiles the SCSS themes and app CSS to `*.css` + `*.min.css`.
- `gulp build` — all of the above plus `archive`: runs
  `composer install --no-dev --optimize-autoloader` into a `build/` copy and
  zips it to `easyappointments-0.0.0.zip`.

Verified: `npm run build` completes and produces an ~8 MB `easyappointments-0.0.0.zip`.

> Note on the build toolchain: this branch repaired two pre-existing breakages
> (the removed `del.sync` API and the Babel-8-incompatible `babel-preset-minify`,
> replaced by `gulp-terser`). See the CHANGELOG.

## 4. Theme & branding configuration

- **Premium theme:** build the assets, then in the backend go to
  **Settings → General → Theme** and select **premium** (it is populated from
  `assets/css/themes/*.min.css`, so it only appears after a build). It applies
  app-wide (booking, backend, login). The bundled Inter font lives under
  `assets/fonts/inter/` (SIL OFL).
- **White-label:** edit `application/config/brand.php` — set `brand_name`,
  `brand_tagline`, `brand_url` and toggle `brand_show_powered_by` /
  `brand_show_vendor_links`. Defaults reproduce the stock appearance. The
  GPL-3.0 and copyright notices must remain intact (see `docs/BRANDING.md`).
- Company logo / name / colour remain configurable via the existing
  `company_logo` / `company_name` / `company_color` settings.

## 5. Backups & restore

Create a database backup from the CLI:

```bash
php index.php console backup                 # writes into storage/backups/
php index.php console backup /path/to/folder # or a custom folder
```

Verified: produces a `easyappointments-backup-YYYY-MM-DD-His.gz` file.

Restore is a standard MySQL import of the decompressed dump, e.g.:

```bash
gunzip -c easyappointments-backup-*.gz | mysql -u <user> -p <database>
```

Always take a database backup (and keep a copy of `config.php` and any custom
`brand.php`) **before** updating.

## 6. Update process

1. Back up the database (§5) and copy `config.php`, `application/config/brand.php`
   and any replaced assets in `assets/img/` aside.
2. Deploy the new code (or `git pull` / merge) — keep your `config.php`.
3. `composer install` and `npm ci && npm run build` to refresh dependencies and
   assets.
4. Apply database migrations: open the app (the updater runs), or run
   `php index.php console migrate up`.
5. Verify the booking flow and the backend; clear `storage/cache/` if needed.

Migrations live in `application/migrations/` and are applied in order; this
branch adds no schema migration (the booking security and branding changes are
backward compatible).

## 7. Known environment notes

- **E-mail:** notification e-mails are sent after the appointment is saved. In an
  environment without a mail transport the booking still succeeds and the failure
  is logged as `Notifications - Could not ... Could not instantiate mail
  function` (observed here). Configure SMTP in **Settings → Business/Email** for
  real delivery. Verified separately: the e-mail templates themselves render
  without error (the render happens before the send attempt).
- **WordPress / iframe embedding:** the app sends `X-Frame-Options: SAMEORIGIN`
  (`application/config/routes.php` and `application/hooks/security_headers.php`),
  which blocks embedding on a different domain. Embedding on a foreign domain
  would require switching this to a configurable
  `Content-Security-Policy: frame-ancestors`. This is deferred (see the audit,
  §7-R7, and `docs/BRANDING.md`).
- **Secrets:** never commit `config.php`, real credentials, API keys or customer
  data. `config.php` is git-ignored.

## 8. Running the tests

```bash
# Unit tests (no database / no server needed beyond the app bootstrap)
APP_ENV=testing php vendor/bin/phpunit --testsuite Unit

# Booking security integration tests (opt-in): point at a running instance
EA_BASE_URL=http://127.0.0.1:8080 APP_ENV=testing php vendor/bin/phpunit --testsuite Integration
```

The default CI suite is **Unit**. The **Integration** suite
(`tests/Integration/BookingSecurityTest.php`) is skipped unless `EA_BASE_URL`
is set, so it never breaks a database-less CI run; it covers the booking IDOR
and double-booking regressions.

Verified in this environment: Unit **7/7**, Integration **3/3**.

## 9. Release acceptance checklist (verified)

- [x] A full booking works from start to confirmation (desktop + mobile).
- [x] Server-side validation and double-booking protection hold (R1/R2 tests,
      plus a 20-way concurrent booking resulting in exactly one appointment).
- [x] Reschedule (manage mode) updates the same appointment, no duplicate.
- [x] Loading / empty / server-error / validation states render; keyboard
      navigation and focus handling work; no horizontal overflow at 390px.
- [x] Dashboard record management (list/detail, selection) works; permissions
      are unchanged (server-side `can()`/`cannot()`).
- [x] E-mail templates render (delivery requires a configured mail transport).
- [x] White-label defaults keep the stock look; toggling replaces the brand and
      removes the "Powered by"/vendor links while keeping the GPL notice.
- [x] `npm run build` is reproducible and produces the release archive.
- [x] Database backup via the console works.
- [x] No real secrets or customer data are introduced in code, tests or docs.
