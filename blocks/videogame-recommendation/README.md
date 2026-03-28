# Videogame Recommendation Block

A WordPress Gutenberg block for displaying videogame recommendations with RAWG API integration.

## Features

- **RAWG API integration:** Search games and import metadata automatically.
- **Light mode design:** Modern card UI inspired by RAWG.
- **Platform chips:** Color-coded badges for gaming platforms.
- **Metadata:** Release date and genre display.
- **Responsive layout:** Optimized for all screen sizes.
- **Performance:** Lazy loading and optimized assets.

## Structure

```text
videogame-recommendation/
├── index.js              # Main block registration
├── block.json            # Block configuration
├── render.php            # Server-side rendering
├── style.css             # Frontend + editor styles
├── editor.css            # Editor-specific styles
├── utils.js              # JavaScript utilities
├── utils.php             # PHP utilities
├── components/
│   ├── GamePreview.js    # Game card component
│   └── SearchResults.js  # Search result component
└── hooks/
    └── useGameSearch.js  # Search hook
```

## Components

### GamePreview

Displays the game card with cover image, platforms, title, and metadata.

### SearchResults

Displays search results in the editor sidebar.

### useGameSearch Hook

Custom hook for game-search logic.

## Utilities

### `utils.js`

- `getPlatformInfo()`: Maps platform names to display names and colors.
- `formatReleaseDate()`: Formats release dates.
- `transformGameData()`: Transforms API data into block-friendly shape.

### `utils.php`

- `child_get_platform_info()`: PHP equivalent of platform info mapping.

## Platform Colors

| Platform | Color |
| --- | --- |
| PlayStation | `#003087` |
| Xbox | `#107C10` |
| Nintendo Switch | `#E60012` |
| PC | `#0078D4` |
| iOS | `#555555` |
| Android | `#3DDC84` |
| Linux | `#FCC624` |
| macOS | `#999999` |

## Development

```bash
# Build
npm run build

# Watch mode
npm run start
```

## API

Uses the RAWG API for game data. Configure the API key in WordPress Admin under **Settings → Videogame Recommendation**.
