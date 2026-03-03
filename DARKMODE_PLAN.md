# Darkmode-Implementierungsplan für TwentyTwentyFive Child

## Ziel
Effizienter, wartbarer Darkmode mit möglichst wenig doppelter CSS-Logik und hoher Konsistenz zwischen Frontend und Block-Editor.

## Leitprinzip
**Token-first statt Override-first**: Bestehende harte Farben (`#fff`, `#1a1a1a` usw.) werden systematisch auf WordPress-Design-Tokens/CSS-Variablen umgestellt. Darkmode wird primär über Variablen gesteuert, nicht über viele verstreute Spezialregeln.

---

## Phase 1 – Audit & Priorisierung
1. Alle Stylesheets prüfen (`style.css`, `blocks/*/style.css`, `blocks/*/editor.css`).
2. Farbverwendung je Datei klassifizieren:
   - **A**: bereits tokenbasiert (nur Feintuning)
   - **B**: gemischt
   - **C**: stark hardcoded (zuerst migrieren)
3. Start mit C-Kandidaten (z. B. `blocks/videogame-recommendation/style.css`).

### Ergebnis der Phase
- Liste mit Hardcodes + betroffener Komponente + Priorität.

---

## Phase 2 – Token-Mapping definieren
Einheitliche Rollen definieren und in allen Blöcken gleich anwenden:

- `--child-surface-1` (Kartenhintergrund)
- `--child-surface-2` (subtile Flächen)
- `--child-text-1` (Haupttext)
- `--child-text-2` (Sekundärtext)
- `--child-border` (Rahmen/Divider)
- `--child-shadow` (Karten-Schatten)

Diese Rollen auf WP-Presets/Fallbacks abbilden (z. B. `--wp--preset--color--base`, `--wp--preset--color--contrast`, `color-mix(...)`).

### Ergebnis der Phase
- Kleine Token-Tabelle (Rolle → technische Variable → Fallback).

---

## Phase 3 – Frontend-Migration je Block
1. Pro Block alle Hex/RGB-Hardcodes auf Rollenvariablen umstellen.
2. Einheitliches Schema für Card/Title/Meta/Border/Hover verwenden.
3. Nur in Ausnahmefällen block-spezifische Darkmode-Regeln ergänzen.

**Empfohlene Reihenfolge:**
1. `videogame-recommendation`
2. `book-rating`
3. `media-recommendation`
4. `magic-cards`
5. `visual-link-preview`
6. `popular-posts` Feintuning

---

## Phase 4 – Darkmode-Strategie festlegen
Eine der beiden Varianten (oder kombiniert):

### Variante A: Automatisch
- `@media (prefers-color-scheme: dark)` für Token-Umbelegung.

### Variante B: User-Toggle
- Root-Attribut/Klasse (`html[data-theme="dark"]`) zur manuellen Umschaltung.
- Optional: gespeicherte Präferenz (LocalStorage) + Fallback auf Systempräferenz.

**Empfehlung:** erst A implementieren, danach optional B als UX-Upgrade.

---

## Phase 5 – Editor-Parität
1. `editor.css` je Block an die gleichen Rollen anbinden.
2. Sicherstellen, dass Kontraste und Flächen im Editor ähnlich zum Frontend sind.
3. Editor-spezifische Farben nur verwenden, wenn Gutenberg UI es zwingend benötigt.

---

## Phase 6 – Qualitätssicherung
Checkliste pro Block:

- Kontrast (WCAG AA) für Text, Secondary Text, Links, Placeholder.
- Hover/Focus/Active/Disabled Zustände.
- Bilder + Overlays + Ambilight/Gradient-Effekte.
- Mobile/Tablet/Desktop.
- Frontend vs Editor-Vergleich.

### Technische Checks
- CSS-Linting / Build (`npm run build`, falls vorhanden).
- Kurzer visueller Regressionstest mit Referenz-Screenshots.

---

## Rollout-Strategie
- Kleine, thematisch getrennte PRs (pro Block oder pro 1–2 Blöcke).
- Nach jedem PR: Smoke-Test und kurzer Changelog-Eintrag.
- Erst am Ende globale Feinanpassung in `style.css`.

---

## Abnahmekriterien
- Keine zentralen Flächen mehr mit harten Light-Farben.
- Einheitliche Token-Nutzung in Frontend **und** Editor.
- Darkmode wirkt konsistent ohne größere Sonderregeln.
- Gute Lesbarkeit/Kontraste auf allen Kernkomponenten.
