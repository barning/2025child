# Proposal: Gutenberg Apple MusicKit Block

## Goal
Create a Gutenberg block that can play an Apple Music song, album, or playlist using MusicKit JS v3.

## Feasibility for WordPress
This is feasible in WordPress as a dynamic block with a frontend `view.js` script. The main technical constraint is **MusicKit authentication**:

1. A valid Apple MusicKit developer token must be generated server-side (Apple Developer account required).
2. Token lifecycle needs operational handling (rotation before expiry).
3. Some playback behavior may require user authorization in Apple Music depending on entitlement and account state.

## Proposed Architecture
1. Gutenberg block (`child/apple-music-player`) with attributes:
   - `resourceType` (`song|album|playlist`)
   - `resourceId`
   - `storefront`
   - `buttonLabel`
2. PHP render callback outputs semantic markup + data attributes and enqueues `https://js-cdn.music.apple.com/musickit/v3/musickit.js`.
3. Frontend `view.js` configures MusicKit once and triggers queue/play on button click.
4. Configuration hook in PHP filter `child_music_kit_config` for `developerToken`, `appName`, and `appBuild`.

## Security and Ops Notes
- Developer token is present client-side by design with MusicKit JS.
- Use short-lived tokens and rotation automation.
- Never hardcode private key material in the theme.

## Example Theme-Level Configuration
```php
add_filter( 'child_music_kit_config', static function( array $config ): array {
	$config['developerToken'] = 'YOUR_SHORT_LIVED_MUSICKIT_TOKEN';
	$config['appName'] = 'My WP Site';
	$config['appBuild'] = '1.0.0';
	return $config;
} );
```

## Implementation Status
A first-pass block implementation is included in this repository under `blocks/apple-music-player` and registered as a dynamic block.
