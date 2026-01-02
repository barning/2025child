# Media Recommendation Block - Visual Guide

## Block Appearance

### In the Editor

When you add the block to your post, you'll see:

```
┌─────────────────────────────────────┐
│  Sidebar (Inspector Controls)      │
├─────────────────────────────────────┤
│                                     │
│  🔍 Film/Serie suchen              │
│  ┌─────────────────────────────┐   │
│  │ Search field: "Matrix"      │   │
│  └─────────────────────────────┘   │
│  [ Suchen ]                         │
│                                     │
│  Search Results:                    │
│  ┌─────────────────────────────┐   │
│  │ 🎬 The Matrix               │   │
│  │    1999 | Film                │   │
│  └─────────────────────────────┘   │
│  ┌─────────────────────────────┐   │
│  │ 📺 The Matrix Resurrections │   │
│  │    2021 | Film                │   │
│  └─────────────────────────────┘   │
│                                     │
│  Details:                           │
│  Type: [Film ▼]                     │
│  Title: The Matrix                  │
│  Year: 1999                         │
│  Poster URL: https://...            │
│                                     │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│         Block Preview               │
├─────────────────────────────────────┤
│          ┌───────────┐              │
│          │           │              │
│          │  POSTER   │  ◄── with   │
│          │   IMAGE   │      ambilight│
│          │           │      glow    │
│          └───────────┘              │
│                                     │
│        The Matrix                   │
│           1999                      │
│                                     │
└─────────────────────────────────────┘
```

### On the Frontend

The block displays as a centered card:

```
        ╔═══════════════════╗
        ║                   ║
        ║                   ║
        ║    Movie Poster   ║  ◄── Colored glow
        ║      Image        ║      adapts to poster
        ║                   ║
        ║                   ║
        ╚═══════════════════╝
        
           The Matrix
              1999
```

## Visual Features

### 1. Poster Display
- **Size**: Responsive (200px - 280px wide)
- **Aspect Ratio**: 2:3 (standard movie poster)
- **Border Radius**: 12px for rounded corners
- **Drop Shadow**: Multi-layer shadow for depth

### 2. Ambilight Effect
The glow behind the poster automatically adapts to the image colors:

- **Red/Orange Posters** → Warm red/orange glow
- **Blue Posters** → Cool blue glow  
- **Green Posters** → Green glow
- **Dark Posters** → Subtle gray glow

The effect samples colors from the poster edges and applies them as a blurred, saturated gradient.

### 3. Hover Effect
When hovering over the poster:
- Lifts up slightly (4px translateY)
- Ambilight glow becomes more intense (opacity increases)
- Smooth 0.2s transition

### 4. Typography
- **Title**: Large, bold (1.5rem - 2rem)
- **Year**: Smaller, italic, muted color
- **Centered alignment** for clean presentation

## Color Scheme

The block inherits WordPress theme colors:
- **Title**: `var(--wp--preset--color--contrast)` (typically dark)
- **Year**: 65% opacity of contrast color
- **Background**: White for poster container
- **Ambilight**: Extracted from poster (dynamic)

## Responsive Behavior

### Mobile (< 768px)
- Poster: ~200px wide
- Font size scales down
- Touch-friendly spacing

### Tablet (768px - 1024px)
- Poster: ~240px wide
- Medium font sizes

### Desktop (> 1024px)
- Poster: ~280px wide
- Larger, more readable text
- Enhanced hover effects

## Comparison with Book Rating Block

### Similarities:
- ✅ Centered layout
- ✅ Card-style presentation
- ✅ Rounded corners
- ✅ Clean typography
- ✅ Responsive sizing

### Differences:
- ✨ Ambilight glow (unique to media block)
- 📅 Shows year instead of stars
- 🎬 Icon badge for media type
- 🎨 Dynamic color effects

## States

### Empty State
When no movie/TV show is selected:
```
┌─────────────────────────────────────┐
│                                     │
│  Please select a movie or TV show   │
│  from the search results.           │
│                                     │
└─────────────────────────────────────┘
```

### Loading State
During search:
```
[ Searching... ]
    ⌛
```

### Error State
If API fails:
```
⚠️ An error occurred. Please try again.
```

### No Results
If search returns nothing:
```
❌ No results found.
```

## Accessibility

- ✅ Proper ARIA labels (`aria-label="Film"` or `aria-label="Serie"`)
- ✅ Alt text on images
- ✅ Screen reader friendly
- ✅ Keyboard navigable
- ✅ Semantic HTML structure

## Example Use Cases

1. **Movie Reviews**: Display the reviewed film's poster
2. **Recommendation Lists**: Multiple blocks for top picks
3. **Series Discussions**: Show TV series being discussed
4. **Coming Soon Posts**: Highlight upcoming releases
5. **Streaming Guides**: What to watch this month

## CSS Classes Reference

For custom styling:

```css
.wp-block-child-media-recommendation  /* Block wrapper */
.child-media-card                      /* Card container */
.child-media-card__media               /* Poster container */
.child-media-card__poster              /* Poster image */
.child-media-card__placeholder         /* Fallback when no poster */
.child-media-card__meta                /* Text container */
.child-media-card__title               /* Title text */
.child-media-card__year                /* Year text */
```

## Tips for Best Results

1. **High Quality Posters**: TMDB provides 500px wide images
2. **Colorful Posters**: Work best with ambilight effect
3. **Proper Spacing**: Use block spacing controls
4. **Group Layouts**: Combine multiple blocks for galleries
5. **Background**: Looks best on light backgrounds

## Browser Support

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| Basic Display | ✅ | ✅ | ✅ | ✅ |
| Ambilight | ✅ | ✅ | ✅ | ✅ |
| Hover Effects | ✅ | ✅ | ✅ | ✅ |
| Canvas API | ✅ | ✅ | ✅ | ✅ |

All modern browsers fully supported!
