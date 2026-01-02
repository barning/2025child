# Media Recommendation Block Setup

## Prerequisites

To use the Media Recommendation block (Film/Serie), you need to configure a TMDB (The Movie Database) API key.

## Getting a TMDB API Key

1. Go to https://www.themoviedb.org/
2. Create a free account or sign in
3. Go to https://www.themoviedb.org/settings/api
4. Request an API key (choose "Developer" option)
5. Fill out the required information
6. You'll receive an API key immediately

## Configuration

### Method 1: WordPress Admin (Recommended)

The easiest way to configure your API key:

1. Log in to your WordPress admin dashboard
2. Go to **Settings > Media Recommendation**
3. Enter your TMDB API key in the field
4. Click "Save Settings"

That's it! No code changes needed.

### Method 2: wp-config.php (Alternative)

If you prefer to define the API key in code, add this line to your `wp-config.php` file (before the "That's all, stop editing!" line):

```php
define('TMDB_API_KEY', 'your_api_key_here');
```

Replace `your_api_key_here` with your actual TMDB API key.

**Note**: If you configure the API key in both places, the settings page value takes priority.

## Usage

Once configured, the Media Recommendation block will be available in the WordPress block editor:

1. Click the "+" button to add a new block
2. Search for "Film/Serie" or "Media Recommendation"
3. In the sidebar, use the search field to find movies or TV shows
4. Select a result to display the poster and title
5. The block will display with a nice drop shadow effect similar to the book recommendation block

## Features

- Search for both movies and TV shows from TMDB database
- Displays poster image and title
- Shows release year
- Responsive design that matches the book recommendation block style
- Subtle drop shadow for visual depth
- Server-side API calls for security (API key not exposed to client)
