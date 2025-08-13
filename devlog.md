# Project: Replace WordPress Plugins with Child Theme Modules

---

## Jira Ticket 1 — Plugin Audit Documentation

**Goal:**  
Create a living document that lists all active WordPress plugins, their purpose, complexity, and feasibility of replacement by the child theme.

**Acceptance Criteria:**  
- A markdown file `docs/plugin-audit.md` exists with a table including these columns: Plugin Name, Purpose, Complexity, Replaceable in Theme, Priority, and Notes.  
- The document includes a clear explanation of complexity and priority definitions.  
- The document is ready to be updated continuously during development.

---

## Jira Ticket 2 — Complete Plugin Inventory

**Goal:**  
Populate the plugin audit document with all active plugins and their core purposes.

**Acceptance Criteria:**  
- All active plugins on the development site are listed by name and purpose in the audit document.  
- No judgments or classifications are made yet; only listing is done.

---

## Jira Ticket 3 — Establish Child Theme Base

**Goal:**  
Set up a functional child theme for TwentyTwentyFive that can be activated without errors.

**Acceptance Criteria:**  
- Child theme folder with `style.css` and `functions.php` exists and references the parent correctly.  
- Parent and child stylesheets are properly enqueued and load on the frontend.  
- Theme activates in WordPress without errors or warnings.

---

## Jira Ticket 4 — Modular Code Structure

**Goal:**  
Create a modular code structure inside the child theme to allow incremental feature development.

**Acceptance Criteria:**  
- An `inc/` folder exists containing placeholder files for different functional areas (e.g., cleanup, SEO, forms).  
- These files are loaded automatically by the theme.  
- Theme activation remains stable and error-free.

---

## Jira Ticket 5 — Plugin Complexity Assessment

**Goal:**  
Assign complexity levels and priorities to each plugin based on potential ease or difficulty of replacement.

**Acceptance Criteria:**  
- Complexity (Low, Medium, High) and Priority (High, Medium, Low) are assigned to each plugin in the audit document.  
- Rationales for ratings are documented clearly in notes.

---

## Jira Ticket 6 — Replacement Strategy Documentation

**Goal:**  
For each high-priority plugin, document which features will be replaced in the child theme and any limitations or expected differences.

**Acceptance Criteria:**  
- Each high-priority plugin has a documented replacement plan inside the corresponding module file as a developer comment.  
- Plans are clear and provide enough context to guide implementation.

---

## Jira Ticket 7 — Implement Plugin Replacement Module

**Goal:**  
Deliver a fully functional child theme module that replicates the core functionality of a prioritized plugin.

**Acceptance Criteria:**  
- Module covers the core features targeted for replacement.  
- No regressions or errors are introduced.  
- Module code adheres to WordPress standards and best practices.  
- User-facing strings are translatable.

---

## Jira Ticket 8 — Plugin Deactivation and Verification

**Goal:**  
Safely deactivate the replaced plugin and verify the child theme module fully assumes its responsibilities.

**Acceptance Criteria:**  
- Plugin deactivation causes no loss of functionality or site errors.  
- The replacement module performs equivalently or better.  
- Any deviations are noted in the audit document.

---

## Jira Ticket 9 — Iterate Replacement Process

**Goal:**  
Continue replacing additional prioritized plugins with child theme modules following the defined workflow.

**Acceptance Criteria:**  
- Audit document and modular codebase remain up to date.  
- Site remains stable throughout replacements.  
- Each plugin’s replacement is tracked with success criteria met.

---
