# Design-System – Premium-Theme

> Phase 2 des Modernisierungs-Briefings. Liefert ein hochwertiges, ruhiges
> Erscheinungsbild als **neues, abwählbares Theme** `premium`, ohne bestehende
> Themes zu verändern. Umgesetzt auf dem vorhandenen Stack (Bootstrap 5 + SCSS),
> keine Framework-Migration.

Erstellt: 2026-10-09 · Branch: `feature/premium-ui`

## Was wurde hinzugefügt

| Datei | Zweck |
|---|---|
| `assets/css/themes/premium.scss` | Das Premium-Theme: Bootstrap-Variablen-Overrides + `:root`-Design-Tokens + Komponenten-Refinements |
| `assets/fonts/inter/inter-{400,500,600,700}.woff2` | Lokal ausgelieferte Inter-Schrift (kein externer Font-CDN → Datenschutz) |
| `assets/fonts/inter/LICENSE.txt` | SIL Open Font License 1.1 für Inter (Pflicht-Lizenzhinweis) |

Bestehende Themes (`default`, `cosmo`, `darkly`, …) bleiben **unverändert**. Das
Premium-Theme ist additiv: Nach einem Build erscheint `premium` automatisch im
Theme-Dropdown unter **Einstellungen → Allgemein**, da dieses aus
`assets/css/themes/*.min.css` befüllt wird (`application/controllers/General_settings.php`).

## Aktivieren

1. Assets bauen: `npm start` (Watch) oder `npm run build`.
2. Backend → **Einstellungen → Allgemein → Theme** auf **premium** stellen und speichern.
   (Alternativ für die Buchungsseite per URL-Parameter `?theme=premium`.)

Das Theme-CSS wird von allen Layouts geladen (Buchung, Backend, Login/Account,
Meldungsseiten), d. h. die Umstellung wirkt app-weit.

## Design-Tokens

Primärquelle sind SCSS-Variablen (zur Bootstrap-Konfiguration). Zusätzlich
werden die Tokens als **CSS Custom Properties** auf `:root` veröffentlicht, damit
Anwendungscode sie direkt referenzieren kann:

| Token (CSS-Variable) | Wert | Verwendung |
|---|---|---|
| `--color-text` | `#0F172A` (slate-900) | Fließtext, Überschriften |
| `--color-primary` | `#0D9488` (teal-600) | Primäraktionen, Links, Akzente |
| `--color-primary-hover` | `#0F766E` (teal-700) | Hover/Active der Primärfarbe |
| `--color-background` | `#F8FAFC` (slate-50) | Seitenhintergrund |
| `--color-surface` | `#FFFFFF` | Karten, Eingabefelder, Menüs |
| `--color-border` | `#E2E8F0` (slate-200) | Rahmen, Trennlinien |
| `--color-muted` | `#64748B` (slate-500) | Sekundärtext, Labels |
| `--radius-card` | `0.875rem` (14px) | Karten, Dialoge |
| `--radius-control` | `0.625rem` (10px) | Buttons, Inputs, Selects |
| `--shadow-card` | `0 4px 20px rgb(15 23 42 / 0.05)` | Karten |
| `--shadow-lg` | `0 12px 32px rgb(15 23 42 / 0.1)` | Menüs, Dialoge, Toasts |
| `--space-1…8` | 4 / 8 / 12 / 16 / 24 / 32 px | Abstände |

Die vollständige Graustufen-/Statusfarbpalette (Slate-Neutraltöne, `success`
`#16A34A`, `info` `#0EA5E9`, `warning` `#D97706`, `danger` `#DC2626`) ist in
`premium.scss` als SCSS-Variablen definiert und speist die Bootstrap-Komponenten.

## Typografie

- **Inter**, lokal in vier Schnitten ausgeliefert (400/500/600/700), mit
  `font-display: swap`. Fallback: System-Sans-Serif-Stack.
- Zeilenhöhe Fließtext 1.6, Überschriften 1.25, leichtes negatives
  Letter-Spacing (`-0.011em`) für Überschriften.

## Komponenten & Zustände

Das Theme vereinheitlicht die Bootstrap-Komponenten im Premium-Stil:

- **Buttons** – klare Hierarchie (`.btn-primary` teal, `.btn-secondary` neutral
  weiß, `.btn-outline-secondary`), 10px-Radius, dezente Schatten, sichtbarer Fokus.
- **Formulare** – größere Felder, 10px-Radius, Labels in gedämpftem Slate,
  Fokus-Border teal + Fokus-Ring. Validierungs-/Fehlertexte in lesbarer Größe.
- **Karten/Flächen** – weiße Oberflächen, feine Rahmen, `--shadow-card`, 14px-Radius.
- **Navbar & Dropdowns** – feine Rahmen, weiche Schatten, teal Active-States.
- **Tabellen** – Kopfzeilen als gedämpfte Kapitälchen, feine Zeilentrenner, teal Hover.
- **Badges/Status** – zusätzlich `.badge-soft-{success,warning,danger,info}` für
  Statusanzeigen; Status wird stets über **Text/Icon + Farbe** vermittelt, nie Farbe allein.
- **Alerts, Tabs, Pills, Tooltips, Toasts, Popover, Pagination, Progress, List-Group** –
  konsistent auf Tokens abgestimmt.

### Interaktions- & Barrierefreiheits-Zustände

- **Fokus:** sichtbarer Tastaturfokus (`:focus-visible` → teal Outline + Ring);
  Outline wird nur entfernt, wenn ein sichtbarer Ersatz vorhanden ist.
- **Hover/Active:** dezente Farb-/Hintergrundwechsel, kurze Transitions (0.18s).
- **Laden:** Hilfsklasse `.is-loading` (reduzierte Deckkraft, keine Pointer-Events).
- **Kontwraste:** Slate/Teal-Palette auf WCAG-Lesbarkeit ausgelegt
  (`$min-contrast-ratio: 3`, weiße Button-Schrift auf Teal erzwungen).
- **Reduzierte Bewegung:** `@media (prefers-reduced-motion: reduce)` schaltet
  Animationen/Transitions praktisch ab.

## Grenzen / nächste Schritte

- Dieses Theme setzt das **visuelle Fundament**. Buchungsspezifische
  Layout-Verbesserungen (Schrittfortschritt, Leer-/Fehlerzustände im Wizard,
  Tabellen-Umbau im Dashboard) gehören in Phase 3/4 und bauen auf diesen Tokens auf.
- Die gebauten CSS-/JS-Dateien sind `.gitignore`-t (Build-Artefakte); committet
  wird nur die SCSS-Quelle. Nach dem Checkout ist daher ein Build nötig, bevor
  `premium` im Theme-Dropdown erscheint.
- Inter wird lokal gebündelt (SIL OFL, Hinweis unter `assets/fonts/inter/LICENSE.txt`).
