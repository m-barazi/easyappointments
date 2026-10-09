# Technische Bestandsaufnahme – Easy!Appointments

> Phase 1 des Modernisierungs-Briefings. **Es wurde kein Anwendungscode verändert.**
> Hinzugefügt wurden ausschließlich diese Dokumentation (`docs/`) und Baseline-Screenshots
> (`docs/audit-screenshots/`). Dieses Dokument beschreibt den *tatsächlich vorliegenden*
> Repository-Stand, verifizierte Dateipfade, Build-/Teststatus, Risiken und einen
> priorisierten Datei-für-Datei-Umsetzungsplan.

Erstellt: 2026-10-09

---

## 1. Projektversion und Stand

| Angabe | Wert (verifiziert) |
|---|---|
| Produkt | Easy!Appointments |
| App-Version | **1.6.0** (`application/config/app.php:12` → `$config['version'] = '1.6.0';`) |
| Composer-Paketversion | `1.5.2` (`composer.json` → `"version": "1.5.2"`, nicht mit App-Version synchron) |
| Branch | `main` |
| HEAD-Commit | `10e45691f378773f6b9c32398256e5d1c17ab3db` (2026-10-06) |
| Remote (dieser Fork) | `https://github.com/m-barazi/easyappointments` |
| Upstream | `https://github.com/alextselegidis/easyappointments` |
| Arbeitsbaum zu Beginn | sauber (`git status`: nothing to commit) |
| Lizenz | **GPL-3.0** (Code), CC BY 3.0 (Inhalte) – `LICENSE`, `README.md` |

> Hinweis: Das `main` dieses Forks liegt bereits leicht vor dem offiziellen 1.6.0-Release
> und enthält eigene Sicherheits-/Housekeeping-Ergänzungen (z. B. `application/hooks/security_headers.php`,
> CSRF-Prüfung im Booking-Controller, `.github/copilot-instructions.md`). Upstream entwickelt
> auf `develop` – spätere Beiträge sollten das berücksichtigen.

---

## 2. Tatsächlich verwendeter Stack

### Backend
- **PHP ≥ 8.2** (lokal getestet mit PHP 8.3.6; CI nutzt PHP 8.3).
- **Framework: CodeIgniter 3** (nicht Laravel/Symfony). Der Framework-Kern liegt unter
  `system/` (`system/core/CodeIgniter.php`), die App unter `application/` im klassischen
  CI3-MVC-Layout. Eigene Framework-Erweiterungen in `application/core/EA_*.php`
  (z. B. `EA_Controller`, `EA_Model`, `EA_Loader`).
- Einstiegspunkt: `index.php` (Front-Controller, bootet CI3; dient auch als PHPUnit-Bootstrap).
- Bemerkenswerte Composer-Abhängigkeiten (`composer.json`): `google/apiclient` (Google-Calendar-Sync),
  `sabre/vobject` (CalDAV/ICS), `phpmailer/phpmailer`, `monolog/monolog`, `ezyang/htmlpurifier`,
  `gregwar/captcha`, `altcha-org/altcha`, `guzzlehttp/guzzle`, `symfony/finder`.

### Datenbank
- **MySQL/MariaDB**, Treiber `mysqli` (`system/database/drivers/mysqli`).
- Tabellenpräfix `ea_` (Standard). Schema wird über **Migrationen** in
  `application/migrations/` (nummeriert, aktuell bis `0xx_*.php`) und Seeds via Console aufgebaut.

### Frontend
- **Kein SPA-Framework.** Server-gerenderte PHP-Views + **jQuery 3.7**.
- **Bootstrap 5.3** als CSS-Framework, **SCSS** als Quellformat.
- Kalender: **FullCalendar 6** (Backend-Kalender) + **flatpickr** (Buchungs-Datepicker).
- Weitere JS-Abhängigkeiten: moment / moment-timezone, select2, tippy.js, trumbowyg (Rich-Text),
  jquery-jeditable, @popperjs/core, cookieconsent, @fortawesome/fontawesome-free (Icons).
- Eigene JS-Architektur im Namespace `App.*`: `assets/js/pages/`, `assets/js/http/`,
  `assets/js/components/`, `assets/js/layouts/`, `assets/js/utils/`.

### Build / Tooling
- **Node ≥ 14** (lokal 22), **Gulp 5** als Build-Runner (`gulpfile.js`).
- Gulp-Tasks: `vendor` (kopiert node_modules-Dist-Dateien nach `assets/vendor/`),
  `scripts` (Babel → `*.min.js`), `styles` (SCSS → `*.css` + `*.min.css`),
  `archive`/`build` (Release-ZIP), `watch`, `dev` (Default), `compile`.
- `asset_url()` (`application/helpers/asset_helper.php`) liefert bei `debug=false`
  automatisch die `*.min.*`-Varianten aus – d. h. **ohne Build keine Assets in Produktion**.
- Prettier + `@prettier/plugin-php` für Formatierung (`.prettierrc.json`).

### WordPress-Integration
- **Im Repository ist KEIN WordPress-Plugin- oder iframe-Integrationscode enthalten.**
  Suche nach `wordpress`/`wp-content`/`shortcode`/`iframe` ergab nur den `X-Frame-Options`-Header
  (`application/config/routes.php:64 ff.`, `application/hooks/security_headers.php`) sowie
  Sprachdateien. Die offizielle WordPress-Einbindung ist ein **separates Projekt/Plugin**
  (nicht Teil dieses Repos) und bettet die App typischerweise per iframe ein.
- **Konsequenz fürs Briefing:** `X-Frame-Options: SAMEORIGIN` verhindert aktuell die
  Einbettung per iframe auf einer *fremden* WordPress-Domain. Das ist für die geplante
  WP-Einbettung eine bewusste Entscheidung/ein Blocker, der in Phase 5 adressiert werden muss
  (z. B. konfigurierbare `Content-Security-Policy: frame-ancestors`). Branding im iframe muss –
  wie im Briefing richtig vermutet – **in dieser App** erfolgen, nicht im WP-Theme.

---

## 3. Build- und Testbefehle (tatsächlich ausgeführt)

Umgebung: Container ohne Docker-Daemon; lokale MariaDB 10.11 selbst gestartet; PHP 8.3.6.

| Schritt | Befehl | Ergebnis |
|---|---|---|
| PHP-Deps | `composer install` | ✅ OK (benötigt vorab `cp config-sample.php config.php`) |
| Node-Deps | `npm ci` | ✅ OK (npm 10.9, Node 22) |
| DB + Setup | `php index.php console install` | ✅ OK (Admin `administrator`/`administrator`) |
| Unit-Tests | `APP_ENV=testing php vendor/bin/phpunit` | ✅ **OK (7 Tests, 8 Assertions)** |
| Styles | `npx gulp styles` | ✅ OK (SCSS → CSS/min.css) |
| Scripts | `npx gulp scripts` | ⚠️ läuft durch, aber **Babel-Fehler** (s. u.), keine `*.min.js` erzeugt |
| Vendor | `npx gulp vendor` / `gulp compile` | ❌ **Bricht ab** (`del.sync is not a function`) |

### Bekannte, bereits im Repo vorhandene Fehler (nicht von uns verursacht)
1. **`gulp compile` / `gulp vendor` schlägt fehl:** `TypeError: del.sync is not a function`
   (`gulpfile.js:122`). Ursache: `del@8` ist ESM-only und exportiert kein `del.sync` mehr;
   der Gulpfile nutzt noch die CommonJS-API. → Build (`npm run build`) ist aktuell **gebrochen**.
2. **`gulp scripts` Babel-Fehler:** `path.visit is not a function` beim Minifizieren
   (z. B. `assets/js/utils/working_plan.js`). Ursache: inkompatible Kombination aus
   `@babel/core@8`, `@babel/preset-env@8` und `babel-preset-minify@0.5.2` (minify ist
   nicht Babel-8-kompatibel). → **`*.min.js` werden nicht erzeugt**; in Produktion (`debug=false`)
   würden JS-Assets fehlen.

> Workaround für Baseline-Demo: `config.php` mit `DEBUG_MODE = true`, dann lädt die App die
> nicht-minifizierten Quellen direkt; `assets/vendor/` wurde manuell aus `node_modules` befüllt
> (gleiche Dateiliste wie der `vendor`-Task). Dieser Fund ist **Phase-2-relevant** (Build zuerst
> reparieren, bevor neue SCSS/JS ergänzt werden), siehe Risiken.

### PHPUnit-Abdeckung (Ist)
- Nur 2 Testklassen: `tests/Unit/Helper/ArrayHelperTest.php`,
  `tests/Unit/Helper/ValidationHelperTest.php`. **Keine** Tests für Buchung, Verfügbarkeit,
  Controller, Modelle oder Browser. `phpunit.xml` kennt nur die Suite `Unit`
  (`./tests/Unit`), Verzeichnisse `integration/` und `browser/` existieren nicht.

---

## 4. Relevante Dateien – Kunden-Buchungsformular

Ablauf serverseitig gesteuert durch **`application/controllers/Booking.php`** (892 Zeilen):
- `index()` – rendert die Buchungsseite, setzt `manage_mode` bei vorhandenem `appointment_hash` (Reschedule).
- `register()` (ab Z. 368) – nimmt die Buchung entgegen, CSRF-Prüfung, CAPTCHA/ALTCHA,
  `check_datetime_availability()`, speichert Kunde + Termin, triggert Sync/Mail/Webhook.
- `get_available_hours()` (Z. 698), `get_unavailable_dates()` (Z. 788) – AJAX-Verfügbarkeiten.
- `check_datetime_availability()` / `search_any_provider()` – serverseitige Re-Validierung.

**Views (Buchung):**
- `application/views/layouts/booking_layout.php` – Grundgerüst, lädt Theme-CSS + Assets.
- `application/views/pages/booking.php` – Seitenrumpf.
- `application/views/components/booking_header.php`, `booking_footer.php`,
  `booking_type_step.php` (Leistung/Mitarbeiter), `booking_time_step.php` (Datum/Zeit),
  `booking_info_step.php` (Kundendaten), `booking_final_step.php` (Bestätigung),
  `booking_no_services_message.php`, `booking_cancellation_frame.php`.
- Bestätigung/Storno: `application/views/pages/booking_confirmation.php`,
  `booking_cancellation.php`, `booking_message.php` + Controller
  `Booking_confirmation.php`, `Booking_cancellation.php`.

**JS (Buchung):** `assets/js/pages/booking.js` (1135 Z., Wizard-Logik, `App.Pages.Booking`),
`assets/js/http/booking_http_client.js` (AJAX, Zeitzonen-Umrechnung mit moment-timezone),
`assets/js/layouts/booking_layout.js`.

**CSS:** `assets/css/frontend.scss`, `assets/css/general.scss`, `assets/css/themes/*.scss`
(Bootswatch-ähnliche Themes; aktives Theme über Einstellung `theme`, Default
`assets/css/themes/default.scss` – SF-Pro-inspirierte Font-Stack, **Inter wird nicht geladen**).

**Verifiziertes Verhalten (Baseline, lokal):** Vollständige Buchung Schritt 1→Bestätigung
funktioniert fehlerfrei (Desktop + Mobile), keine JS-Konsolenfehler. Bei nur *einer* Leistung +
*einem* Provider wird der Auswahlschritt automatisch übersprungen (direkt Datum/Zeit).
Screenshots: `docs/audit-screenshots/booking-*.png`.

---

## 5. Relevante Dateien – Admin-Dashboard

- Layout: `application/views/layouts/backend_layout.php`, Kopf/Fuß
  `application/views/components/backend_header.php`, `backend_footer.php`,
  `settings_nav.php`.
- Kalender: Controller `application/controllers/Calendar.php`, View
  `application/views/pages/calendar.php`, JS `assets/js/pages/calendar.js` +
  `assets/js/utils/calendar_*.js` (FullCalendar; Tages-/Wochen-/Monats- und Tabellenansicht).
- Verwaltung: Controller + gleichnamige Views/JS für `Customers`, `Services`,
  `Service_categories`, `Providers`, `Secretaries`, `Admins`, `Blocked_periods`,
  `Unavailabilities`, `Working_plan_exceptions`, `Webhooks`.
- Einstellungen: `General_settings`, `Booking_settings`, `Business_settings`,
  `Legal_settings`, `Api_settings`, `Altcha_settings`, Integrationen (Google/CalDAV/
  LDAP/Jitsi/Matomo/Google-Analytics).
- Modelle (Daten-/Buchungslogik): `application/models/Appointments_model.php` (747 Z.),
  `Availability`-Logik in `application/libraries/Availability.php` (647 Z.),
  `Providers_model.php`, `Services_model.php`, `Customers_model.php`,
  `Settings_model.php`, `Users_model.php`, `Roles_model.php` u. a.
- Rollen/Rechte: `application/libraries/Permissions.php`, `permission_helper.php`
  (`can()`/`cannot()`, `PRIV_*`-Konstanten). **Beizubehalten.**

**Verifiziertes Verhalten:** Login + Kalender + Kunden/Services/Providers/General-Settings
rendern korrekt (Desktop); Kunden auch Mobile. Screenshots: `docs/audit-screenshots/backend-*.png`.

---

## 6. API & E-Mail (Verträge – nicht ohne Not ändern)

- REST-API: `application/controllers/api/v1/*`, Routen in `application/config/routes.php`
  (`api/v1/...`), Spezifikation in **`openapi.yml`** (Repo-Root) und `docs/rest-api.md`.
  **API-Verträge bleiben unverändert** (Briefing-Vorgabe).
- E-Mail-Templates: `application/views/emails/appointment_saved_email.php`,
  `appointment_deleted_email.php`, `account_recovery_email.php`, `password_reset_email.php`.
  Versand über `application/libraries/Notifications.php` + `Email_messages.php` (PHPMailer).
  Logo wird als eingebettetes `cid:logo.png` aus `assets/img/logo.png` angehängt
  (`Email_messages.php:301`).

---

## 7. Zentrale Risiken

### Sicherheit / Buchungsintegrität (höchste Priorität)

> Verifiziert ausschließlich auf einer lokalen **Wegwerf-Instanz mit synthetischen Testdaten**.
> Keine echten Daten, keine Produktionsumgebung berührt.

- **R1 – Fehlende Autorisierung/Ownership-Prüfung in `Booking::register()` (IDOR/Broken Access Control).**
  Der öffentliche Endpoint `booking/register` übernimmt `post_data[appointment][id]` und
  `post_data[customer][id]` direkt aus dem Request. Da `Appointments_model::save()` und
  `Customers_model::save()` bei vorhandener `id` auf **UPDATE** umschalten (`save()` prüft nur
  `empty($id)`), kann ein nicht authentifizierter Besucher durch Mitsenden einer fremden
  `appointment`-/`customer`-ID **bestehende Termine und Kundendatensätze überschreiben**, ohne
  den zugehörigen `hash` zu kennen. Der `manage_mode`/`hash`-Pfad (für legitimes Reschedule via
  `index()`) wird in `register()` **nicht erzwungen** – `manage_mode` ist ein frei gesetztes
  POST-Feld und steuert nur die Doppelbuchungs-Ausnahme, nicht die Schreibberechtigung.
  *Lokal reproduziert:* ein Termin wurde ohne gültigen Hash auf einen anderen Kunden/eine andere
  Zeit umgebogen und ein fremder Kundendatensatz überschrieben.
  **Fix-Richtung (Phase 3/6):** Bei öffentlichen Buchungen serverseitig erzwingen, dass ein
  Update nur über einen gültigen, zum Datensatz passenden `appointment_hash` erfolgt; eingehende
  `id`-Felder im öffentlichen Pfad ignorieren bzw. gegen den Hash verifizieren. **Vor** UI-Arbeit
  mit Regressionstest absichern (siehe R-Test).

- **R2 – Keine DB-Transaktion / Locking gegen Race-Conditions bei Doppelbuchung.**
  `check_datetime_availability()` prüft Verfügbarkeit und `save()` schreibt den Termin in
  **getrennten** Schritten ohne Transaktion/`SELECT ... FOR UPDATE`. Zwei gleichzeitige Requests
  können denselben Slot passieren. Der Code kommentiert die Absicht, verlässt sich aber auf
  zeitliche Nähe. **Fix-Richtung:** Schreibpfad in eine Transaktion kapseln und
  Verfügbarkeit innerhalb der Transaktion final prüfen (oder DB-Constraint/Lock), Briefing-Punkt
  „Doppelbuchungen verhindern“.

### Build / Wartbarkeit
- **R3 – Build gebrochen** (`del.sync`, Babel-minify, s. Abschnitt 3). Muss **zuerst** in Phase 2
  repariert werden, sonst sind neue CSS/JS in Produktion nicht auslieferbar.
- **R4 – Sehr geringe Testabdeckung** (nur 2 Helper-Tests). Für alle UI-/Logikänderungen fehlen
  Regressionstests; `integration/`- und `browser/`-Suiten existieren nicht.
- **R5 – Versionsinkonsistenz** `composer.json` (1.5.2) vs. `app.php` (1.6.0).

### Branding / White-Label (Details in `docs/BRANDING.md`)
- **R6 – „Powered by Easy!Appointments“, Logos, Titel-Suffixe, Premium-Link und Autoren-Logo**
  sind an vielen Stellen **hart codiert** (Views, Layouts, E-Mails, Error-Seiten). Es existiert
  bereits ein `company_logo`-Setting (Migration 032) + `company_color` (033) + `theme` (038),
  aber kein zentraler Produktname/kein White-Label-Schalter.

### iframe / WordPress
- **R7 –** `X-Frame-Options: SAMEORIGIN` (doppelt gesetzt: `routes.php` + `security_headers.php`)
  blockiert Einbettung auf fremden Domains (Phase 5).

---

## 8. Priorisierter Datei-für-Datei-Umsetzungsplan

**Phase 2 – Fundament (zuerst):**
1. `gulpfile.js` – `del`-Nutzung auf ESM/`deleteSync` oder `fs`-Äquivalent umstellen; Build grün machen.
2. `babel.config.json` / `package.json` – Babel-Minify-Konflikt lösen (minify ersetzen/entfernen
   oder Babel-Version angleichen), sodass `gulp scripts` `*.min.js` erzeugt.
3. `assets/css/design-tokens.scss` (neu) – Tokens aus dem Briefing (Navy/Teal) als SCSS/CSS-Custom-Properties,
   in `general.scss` bzw. Theme einhängen; Inter-Font lokal (`assets/fonts/`) prüfen & einbinden.
4. Komponenten vereinheitlichen in `assets/css/*.scss` (Buttons/Formulare/Karten/Status/Fokus).

**Phase 3 – Buchungsformular (klein, getestet):**
5. **Zuerst Sicherheits-Regressionstest** zu R1/R2 (PHPUnit-Integration gegen Test-DB) – rot → Fix → grün.
6. `application/controllers/Booking.php` – R1/R2 serverseitig härten (ohne API-Vertragsbruch).
7. Views `booking_*step.php` + `booking.js` – Schrittfortschritt, Lade-/Leer-/Fehlerzustände,
   mobile Darstellung, Tastatur/ARIA. Buchungslogik/Datenmodell unverändert.

**Phase 4 – Dashboard:**
8. `backend_layout.php`, `backend_header.php`, `settings_nav.php` – Navigation/Layout.
9. `pages/calendar.php` + `calendar.js` – Ansichten/Status/Filter (FullCalendar beibehalten).
10. Tabellen (Customers/Services/Providers) responsiv; Rollen/Rechte via `Permissions` testen.

**Phase 5 – White Label:** zentrale Branding-Config + Austausch der in `docs/BRANDING.md`
gelisteten Fundstellen; `X-Frame-Options`/CSP für WP-Einbettung konfigurierbar machen;
Lizenz-/Copyright-Hinweise (GPL-3.0) erhalten.

**Phase 6 – Tests/Release:** Browser-Tests (Playwright ist im Container verfügbar),
Doppelbuchungs-/Zeitzonen-/DST-/E-Mail-Prüfung, Build+Deploy dokumentieren (`docs/DEPLOYMENT.md`).

---

## 9. Offene Entscheidungen (Rückfragen)

1. **WordPress-Einbettung:** Soll die App auf einer externen WP-Domain per iframe laufen
   (dann CSP/`frame-ancestors` konfigurierbar statt `SAMEORIGIN`)? Gibt es ein separates
   WP-Plugin-Repo, das mitgepflegt werden soll? (Im aktuellen Repo nicht vorhanden.)
2. **White-Label-Umfang:** „Powered by“/Autoren-Logo/Premium-Link **vollständig entfernen**
   oder durch **konfigurierbares** eigenes Branding ersetzen (Default = eigener Name)?
   Lizenzpflichtige GPL-Hinweise bleiben in jedem Fall erhalten.
3. **Theme-Strategie:** Neues Premium-Design als zusätzliches Theme (`themes/premium.scss`,
   abwählbar) oder Überarbeitung des `default`-Themes?
4. **Font:** Inter extern (Google Fonts, Datenschutz) vs. lokal ausliefern – Vorgabe Briefing
   tendiert zu lokal; bitte bestätigen.
5. **Scope R1/R2:** Dürfen diese Sicherheitsfixes im selben Projekt umgesetzt werden
   (empfohlen, da buchungskritisch) oder separat/vorab?
