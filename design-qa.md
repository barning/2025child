# Design QA: Note thread permalink

- Source visual truth: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/note-thread-audit/02-middle-note.jpg`
- Supplemental link-style reference: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/link-style-reference/niklasbarning-date-links-reference.png`
- Implementation screenshot: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/note-thread-final-middle.jpg`
- Link-style implementation screenshot: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/link-style-reference/note-thread-date-links-final.png`
- Combined comparison: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/note-thread-design-qa-comparison.jpg`
- Focused link-style comparison: `/Users/niklasbarning/.codex/visualizations/2026/11/04/01a1a759-3b4d-77b3-9f43-985a4626ecdd/link-style-reference/date-links-comparison.png`
- Viewport: 1270 × 913 CSS px
- Source pixels: 1270 × 913
- Implementation pixels: 1270 × 913
- Density normalization: both captures are 1:1 browser screenshots at the same viewport; no resampling was required
- State: dark appearance, second Note permalink, authenticated Playground preview

## Full-view comparison evidence

The combined comparison shows the audited state before the implementation on the left and the revised state on the right. The implementation intentionally preserves the existing typography, colors, content width, and minimal thread presentation while applying the three approved changes.

## Focused region comparison

The original thread QA did not require a separate crop because the thread was the only primary content region. The later link-style refinement uses a focused side-by-side comparison: public `niklasbarning.de` post dates on the left and the Note thread dates on the right. Both source captures are 1270 × 913 CSS pixels at 1× density; the 1000 × 560 comparison scales 760 × 520 and 800 × 520 crops to equal 500 × 520 panels. The public reference uses a light appearance and the Playground uses dark appearance, so the comparison is intentionally limited to link decoration and hierarchy rather than palette matching.

## Required fidelity surfaces

- **Fonts and typography:** The parent theme font family, body size, line height, and wrapping are preserved. Only the active date receives the intended stronger weight. Like the public site's post dates, Note dates have no default underline.
- **Spacing and layout rhythm:** The thread keeps its original content width and item rhythm. The dedicated Note template removes the oversized blog-meta gap and places the short-form content higher on the page.
- **Colors and visual tokens:** The existing dark background, foreground, muted dates, and thread-line color remain unchanged. The active marker uses the inherited foreground color rather than adding a new palette.
- **Image quality and asset fidelity:** The target contains no imagery, icons, logos, or generated assets inside the Note experience.
- **Copy and content:** All Note text and dates are unchanged. The active permalink gains screen-reader-only “Current Note” context.

## Comparison history

### Initial findings

- P1: The current Note was visually and semantically indistinguishable from its relatives.
- P1: Parent-theme metadata, post navigation, and “More Posts” competed with the focused thread experience.
- P2: The timeline extended above the first dot and below the last dot, implying that the thread continued.

### Fixes made

- Added `aria-current="page"`, a screen-reader label, a ringed current dot, and stronger current-date styling.
- Added a dedicated `single-note.html` template containing only the site header, Note content, and footer.
- Replaced per-item borders with capped line segments that begin and end at the first and last dot; a standalone Note therefore has no connector tail.

### Post-fix evidence

- The middle-permalink screenshot shows the ring on the second Note and clean line endpoints.
- First- and last-permalink checks confirm that the active ring moves with navigation and the line remains correctly capped.
- Accessibility-tree checks confirm that only the active date link exposes “Current Note”.

### Link-style refinement

- P2: Note dates inherited the browser/theme underline, while comparable post dates on `niklasbarning.de` use `text-decoration: none` at rest and underline on hover.
- Fix: Applied the same rest/hover treatment to `.child-note-thread__date`, with the underline also exposed on `:focus-visible` for keyboard users.
- Post-fix evidence: The focused comparison shows no underline at rest on either surface. Browser-computed styles report `text-decoration-line: none` for all Note dates at rest, and the keyboard-focus check reports an underline plus the theme's 2px visible focus outline.

## Findings

No actionable P0, P1, or P2 differences remain for the approved scope.

## Primary interactions tested

- Opened the first, second, and third Note permalinks from their date links.
- Confirmed that the full thread remains visible on each permalink.
- Confirmed that the active marker and accessible current state move to the selected Note.

## Follow-up polish

- P3: A future pass could add visible times when several Notes share the same calendar date, but that was not part of the approved three-item implementation scope.

final result: passed
