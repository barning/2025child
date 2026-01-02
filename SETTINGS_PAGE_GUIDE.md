# Settings Page Visual Guide

## Accessing the Settings Page

After the update, you can configure the TMDB API key through the WordPress admin interface without editing any code files.

### Navigation Path
WordPress Admin Dashboard → Settings → Media Recommendation

### Settings Page Layout

```
┌────────────────────────────────────────────────────────────┐
│ WordPress Admin Header                                     │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  Media Recommendation Settings                             │
│  ═══════════════════════════════════════                  │
│                                                            │
│  TMDB API Configuration                                    │
│  ─────────────────────────────────────────                │
│                                                            │
│  To use the Media Recommendation block, you need a free    │
│  API key from The Movie Database. Get your API key at     │
│  themoviedb.org/settings/api.                             │
│                                                            │
│  TMDB API Key                                             │
│  ┌──────────────────────────────────────────────────┐    │
│  │ [Enter your TMDB API key]                        │    │
│  └──────────────────────────────────────────────────┘    │
│  Your API key will be stored securely in the database.    │
│                                                            │
│  ┌──────────────┐                                         │
│  │ Save Settings │                                         │
│  └──────────────┘                                         │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## Features

### 1. Easy Access
- Found under standard WordPress Settings menu
- No need to edit wp-config.php or any code files
- Changes take effect immediately after saving

### 2. Secure Storage
- API key stored in WordPress options table
- Uses WordPress's native settings API
- Properly sanitized on save

### 3. Clear Instructions
- Clickable link to TMDB API settings page
- Helpful description text
- Shows current status

### 4. Backwards Compatibility
If you previously configured the API key in wp-config.php:
- Both methods continue to work
- Settings page value takes priority
- Shows a note: "Currently using API key from wp-config.php. Enter a key here to override it."

### 5. Error Messages
If the API key is not configured, the block editor will show:
"TMDB API key not configured. Please configure it in Settings > Media Recommendation or add TMDB_API_KEY to wp-config.php"

## Step-by-Step Setup

1. **Get Your API Key**
   - Go to https://www.themoviedb.org/settings/api
   - Sign up/log in
   - Request a free API key

2. **Configure in WordPress**
   - Log in to WordPress admin
   - Go to Settings → Media Recommendation
   - Paste your API key in the text field
   - Click "Save Settings"

3. **Start Using the Block**
   - Create or edit a post
   - Add the "Film/Serie" block
   - Search for movies and TV shows
   - Select and display them

## Benefits Over wp-config.php Method

✅ **No Code Editing**: Non-technical users can configure it  
✅ **Safer**: No risk of breaking wp-config.php  
✅ **User-Friendly**: Standard WordPress interface  
✅ **Discoverable**: Easy to find in Settings menu  
✅ **Changeable**: Easy to update the key if needed  
✅ **Still Flexible**: Can still use wp-config.php if preferred  

## Security Considerations

- Only administrators (users with `manage_options` capability) can access the settings page
- API key is sanitized before storage
- Stored in WordPress database with standard WordPress security
- Not exposed in frontend code (server-side only)
- Uses WordPress Settings API best practices

## Implementation Details

### Database Storage
- Option name: `child_tmdb_api_key`
- Stored in: `wp_options` table
- Autoloaded: Yes (for performance)

### Retrieval Priority
1. First checks: Settings page value (`get_option('child_tmdb_api_key')`)
2. Fallback: wp-config.php constant (`TMDB_API_KEY`)
3. If neither: Shows error message

### Permission Requirements
- Viewing/editing settings: `manage_options` (Administrator role)
- Using the block: `edit_posts` (Editor role and above)

This makes the block much more accessible for WordPress site owners who aren't comfortable editing PHP configuration files!
