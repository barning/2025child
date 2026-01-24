# Videogame Recommendation Block

Ein WordPress Gutenberg Block zur Anzeige von Videospiel-Empfehlungen mit RAWG API Integration.

## Funktionen

- **RAWG API Integration**: Durchsuche Videospiele und importiere automatisch Metadaten
- **Light Mode Design**: Moderne Karten-UI im RAWG-Stil
- **Plattform-Chips**: Farbcodierte Badges für Gaming-Plattformen
- **Metadaten**: Release-Datum und Genres
- **Responsive**: Optimiert für alle Bildschirmgrößen
- **Performance**: Lazy Loading und optimierte Assets

## Struktur

```
videogame-recommendation/
├── index.js              # Haupt-Block-Registrierung
├── block.json            # Block-Konfiguration
├── render.php            # Server-side Rendering
├── style.css             # Frontend & Editor Styles
├── editor.css            # Editor-spezifische Styles
├── utils.js              # JavaScript Utilities
├── utils.php             # PHP Utilities
├── components/
│   ├── GamePreview.js    # Spielkarten-Komponente
│   └── SearchResults.js  # Suchergebnis-Komponente
└── hooks/
    └── useGameSearch.js  # Such-Hook
```

## Komponenten

### GamePreview
Zeigt die Spielkarte mit Cover, Plattformen, Titel und Metadaten an.

### SearchResults
Zeigt Suchergebnisse in der Seitenleiste an.

### useGameSearch Hook
Custom Hook für die Spielsuche-Logik.

## Utilities

### utils.js
- `getPlatformInfo()`: Platform-Name zu Display-Name und Farbe
- `formatReleaseDate()`: Datum-Formatierung
- `transformGameData()`: API-Daten zu Block-Format

### utils.php
- `child_get_platform_info()`: PHP Version der Platform-Info-Funktion

## Plattform-Farben

| Plattform | Farbe |
|-----------|-------|
| PlayStation | #003087 |
| Xbox | #107C10 |
| Nintendo Switch | #E60012 |
| PC | #0078D4 |
| iOS | #555555 |
| Android | #3DDC84 |
| Linux | #FCC624 |
| macOS | #999999 |

## Development

```bash
# Build
npm run build

# Watch mode
npm run start
```

## API

Verwendet die RAWG API für Spieldaten. API-Key in WordPress Admin unter Settings > Videogame Recommendation konfigurieren.
