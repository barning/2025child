# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

## [1.1.4] - 2025-01-02
### Changed
- **Visual Link Preview:** Changed from async to synchronous metadata fetching to always display rich cards with image/title/description immediately
- **Performance Optimizations:** Cached `glob()` results in functions.php to avoid repeated filesystem operations on every request
- **Performance Optimizations:** Added static caching for CSS file existence and modification time checks across all modules (book-rating, popular-posts, visual-link-preview)
- These improvements reduce filesystem I/O operations and improve page load performance

## [1.1.3] - 2025-12-29
### Changed
- README rewritten in English and updated documentation.
- Removed view-count tracking logic from `inc/popular-posts.php` (the Popular Posts block is now a curated list only).
- Bumped package version to `1.1.3` and created annotated tag `v1.1.3`.

### Commits
- chore: update README to English; remove view-count logic from Popular Posts (a3272d6)
- chore(release): bump version to 1.1.3 (776ccf3)

---

For previous releases see the Git history.
