# Plugin Audit

This document lists all active WordPress plugins, their purpose, complexity, and feasibility of replacement by the child theme.

## Complexity & Priority Definitions

- **Complexity:**
  - **Low:** Simple functionality, minimal integration, easy to replicate.
  - **Medium:** Moderate logic, some integration, may require custom code.
  - **High:** Complex features, deep integration, or third-party dependencies.
- **Priority:**
  - **High:** Replacement is urgent or highly beneficial.
  - **Medium:** Replacement is useful but not urgent.
  - **Low:** Replacement is optional or low impact.

---

| Plugin Name                   | Purpose (inferred)                                   | Complexity | Replaceable in Theme | Priority | Notes |
|-------------------------------|------------------------------------------------------|------------|---------------------|----------|-------|
| ActivityPub                   | Federated publishing (ActivityPub protocol)          | High       | No                  | Low      | Requires protocol support and federation; not feasible in theme. |
| Antispam Bee                  | Spam protection                                      | High       | No                  | Low      | Advanced spam filtering; best left to plugin. |
| Autoptimize                   | Asset optimization (CSS/JS)                          | High       | No                  | Low      | Handles minification, caching, async loading. |
| Code Syntax Block             | Code syntax highlighting in posts                    | Medium     | Yes                 | Medium   | Could be replaced with a custom block or JS lib. |
| Enable Linked Groups          | Group linking functionality                          | Medium     | Maybe               | Medium   | Depends on implementation; may require custom post types. |
| Fediverse Embeds              | Embedding Fediverse content                          | Medium     | Maybe               | Medium   | If simple oEmbed, possible; else complex. |
| Flexible Spacer Block         | Custom spacing blocks for editor                     | —          | Removed             | —        | Removed from scope; not needed. |
| Head, Footer and Post Injections | Custom code injection in head/footer/posts         | Medium     | Yes                 | High     | Implemented for `fediverse:creator` meta tag only. |
| Image Placeholders            | Placeholder images                                   | —          | Removed             | —        | Removed from scope. |
| Integrate Umami               | Analytics integration (Umami)                        | Low        | Yes                 | Medium   | Implemented via Customizer script URL + website ID. |
| Language Locale Overwrite     | Locale/language override                             | Medium     | Maybe               | Medium   | May require filter hooks; possible in theme. |
| Maintenance                   | Maintenance mode for site                            | Low        | Yes                 | High     | Simple template override. |
| Media Cleaner                 | Media library cleanup                                | High       | No                  | Low      | Involves DB/media ops; not suitable for theme. |
| Microformats 2                | Microformats markup support                          | Medium     | Yes                 | Medium   | Can be added to theme templates. |
| Modern Image Formats          | WebP/AVIF image support                              | Medium     | Maybe               | Medium   | Some support via theme, but plugin may do more. |
| NodeInfo                      | Node metadata for federated networks                 | High       | No                  | Low      | Protocol-level; not feasible in theme. |
| Performance Lab               | Performance improvements/tools                       | High       | No                  | Low      | Multiple features, some core-level. |
| Sharing Image                 | Social sharing images                                | Medium     | Yes                 | Medium   | Can be handled in theme with meta tags. |
| Simple Local Avatars          | Local avatar support                                 | Medium     | Maybe               | Medium   | May require user meta ops. |
| Speculative Loading           | Performance: preloading resources                    | —          | Removed             | —        | Removed from scope. |
| Syndication Links             | Syndication/feed links                               | Medium     | Maybe               | Medium   | Depends on implementation. |
| The SEO Framework             | SEO optimization                                     | High       | No                  | Low      | Advanced SEO; best left to plugin. |
| Visual Link Preview           | Link preview cards                                   | Medium     | Yes                 | Medium   | Implemented as a block in theme. |
| WordPress Importer            | Import/export content                                | High       | No                  | Low      | One-time use; not needed in theme. |
| WordPress Popular Posts       | Popular posts widget                                 | Medium     | Yes                 | High     | Implemented as a block in theme. |
| WP Dark Mode                  | Dark mode toggle                                     | Low        | Yes                 | High     | Implemented in theme. |
