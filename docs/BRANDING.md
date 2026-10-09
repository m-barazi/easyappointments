# Branding-Inventur (White-Label)

> Phase 1: **Bestandsaufnahme der sichtbaren Markenelemente.** Noch keine Änderungen.
> Zweck: vollständige Liste der Fundstellen für Produktname, Logos, „Powered by“, Autoren-
> Hinweise und Metadaten, als Grundlage für Phase 5. Pfade und Zeilennummern sind
> gegen HEAD `10e4569` verifiziert (können sich bei Änderungen verschieben).

Erstellt: 2026-10-09

## Bereits vorhandene Branding-Konfiguration (wiederverwenden!)

- `company_logo` – Einstellung, Migration `application/migrations/032_add_company_logo_setting.php`.
  Genutzt u. a. im Buchungs-Header (`booking_header.php:11`, Fallback `assets/img/logo.png`).
- `company_color` – Migration `033...` + `application/views/components/company_color_style.php`
  (überschreibt Bootstrap-Primärfarbe global). `company_name`, `company_email`, `company_link`
  existieren als Settings (siehe `Booking.php:register()`).
- `theme` – Migration `038...`, aktives CSS-Theme (`assets/css/themes/*`).

→ Für White-Label empfiehlt sich eine **zentrale Erweiterung** (z. B. Settings `brand_name`,
`brand_favicon`, Schalter `hide_powered_by`) statt weiterer hart codierter Strings.

## Fundstellen „Powered by“ / Produktname / Links (hart codiert)

| Datei | Zeile(n) | Inhalt |
|---|---|---|
| `application/views/components/booking_footer.php` | 13–15 | „Powered By **Easy!Appointments**“ (Link easyappointments.org) |
| `application/views/components/backend_header.php` | 15–16 | „EASY!APPOINTMENTS“ + „Online Appointment Scheduler“ |
| `application/views/components/backend_header.php` | 111 | Link **easyappointments.org/premium** (Premium) |
| `application/views/components/backend_footer.php` | 10–20 | Logo + „Easy!Appointments“ + **Alex Tselegidis**-Logo/Link |
| `application/views/layouts/backend_layout.php` | 12 | `<title> … | Easy!Appointments` |
| `application/views/layouts/booking_layout.php` | 8,18 | og:title + `<title>` Suffix „| Easy!Appointments“ |
| `application/views/layouts/account_layout.php` | 12,35–36 | Titel-Suffix + „Powered by“ |
| `application/views/layouts/message_layout.php` | 12,34–35 | Titel-Suffix + „Powered by“ |
| `application/views/errors/html/error_404.php` | 16,76–77 | Titel + „Powered by“ |
| `application/views/errors/html/error_db.php` | 16,76–77 | Titel + „Powered by“ |
| `application/views/errors/html/error_general.php` | 16,76–77 | Titel + „Powered by“ |
| `application/views/pages/about.php` | 9–15, 78, 94, 110 | Logo, Name, Scheduler-Text, Premium-/Website-/GitHub-Links |
| `application/views/pages/installation.php` | 7,24,36–50,182–205 | Titel, Überschriften, Doku-/GitHub-Links, „Powered by“ |
| `application/views/pages/login.php` | 6–7 | Logo `logo.png`, alt „Easy!Appointments“ |
| `application/views/pages/logout.php` | 6–7 | Logo + alt |
| `application/views/pages/recovery.php` | 6–7 | Logo + alt |
| `application/views/pages/password_reset.php` | 6 | Logo + alt |
| `application/views/pages/update.php` | 5,14 | „Easy!Appointments Update“ |

## E-Mail-Templates (Produktname, Logo `cid:logo.png`, „Powered by“)

| Datei | Zeile(n) |
|---|---|
| `application/views/emails/appointment_saved_email.php` | 30 (Titel), 409 (Logo), 644–647 (Powered by) |
| `application/views/emails/appointment_deleted_email.php` | 28, 407, 630–633 |
| `application/views/emails/account_recovery_email.php` | 17, 386, 411–414 |
| `application/views/emails/password_reset_email.php` | 18, 384, 432–435 |
| `application/libraries/Email_messages.php` | 301 – bettet `assets/img/logo.png` als `cid:logo.png` ein |

## Grafiken / Favicons / Social

| Datei | Verwendung |
|---|---|
| `assets/img/logo.png` | Haupt-Logo (Buchung, Backend, Login, E-Mails) |
| `assets/img/logo-16x16.png` | Footer-Logo Backend |
| `assets/img/alextselegidis-logo-16x16.png` | **Autoren-Logo** (Backend-Footer) |
| `assets/img/favicon.ico` | Favicon (alle Layouts) |
| `assets/img/social-card.png` | og:image (`booking_layout.php:13`) |
| `logo.png` (Repo-Root), `docs/images/logo.png`, `screenshot.png` | README/Doku-Assets |
| `<meta name="theme-color" content="#35A768">` | `booking_layout.php:7` (altes Grün, nicht Token-Farbe) |

## Serverseitige/sonstige Namensnennungen (meist intern, prüfen)

| Datei | Zeile | Kontext |
|---|---|---|
| `application/libraries/Api.php` | 156 | `WWW-Authenticate: Basic realm="Easy!Appointments"` |
| `application/libraries/Google_sync.php` | 104 | `setApplicationName('Easy!Appointments')` (Google-API, ggf. belassen) |
| `application/config/email.php` | 7 | `useragent = 'Easy!Appointments'` |
| `application/controllers/About.php` | 96–101 | RSS-Feed easyappointments.org + User-Agent |
| `application/controllers/Console.php` | 188 | CLI-Banner |
| `assets/js/app.js` | 33 | Fehler-Toast-Titel „Easy!Appointments“ |
| `assets/js/pages/installation.js` | 66 | Erfolgsmeldung |
| `assets/js/utils/calendar_sync.js` | 61 | Popup-Fenstername (unkritisch) |
| Sprachdateien `application/language/*/translations_lang.php` | diverse (`about_app*`) | Produktname in Texten (de/en/…) |

## Zu erhaltende Lizenz-/Copyright-Hinweise (NICHT entfernen)

- `LICENSE` (GPL-3.0 Volltext) – bleibt.
- Dateikopf-Kommentare `@copyright Copyright (c) Alex Tselegidis` / `@license GPLv3` in den
  PHP-/JS-Quellen – als Urheber-/Lizenzhinweis **erhalten** (GPL-Pflicht). Das ist zu
  unterscheiden von der sichtbaren Produktwerbung in der UI.
- Der sichtbare GPL-3.0-Hinweis im Backend-Footer/Installer ist zulässig und kann bleiben;
  nur Produktname/Autoren-Link sind White-Label-Kandidaten.

## Hinweis WordPress/iframe

`X-Frame-Options: SAMEORIGIN` (`application/config/routes.php` + `application/hooks/security_headers.php`)
verhindert iframe-Einbettung auf fremden Domains. Branding im eingebetteten Zustand muss in
**dieser** App geändert werden (WP-Theme-CSS greift nicht in den iframe). Siehe Audit §2/§7-R7.
