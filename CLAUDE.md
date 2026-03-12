# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Pro-Zurituutsch** is a PmWiki-based website for a Swiss German language/cultural organization, promoting Zurich German ("Zurituutsch"). Built on PmWiki 2.2.84 with flat-file storage (no database). Author: Félix F. Wyss. License: GPLv3.

Live URL: https://pro-zurituutsch.ch

## Architecture

- **PmWiki core**: `pmwiki-2.2.84/` — unmodified PmWiki engine (PHP)
- **Live installation**: `doc/` — the deployed site with config, wiki data, uploads, and custom skin
- **Custom skin**: `doc/pub/skins/pro-zurituutsch/` — based on the Beeblebrox Gila theme (`beeblebrox-pmwiki2-theme-1.1/`)
- **Wiki pages**: `doc/wiki.d/` — flat-file page database (~1000+ pages, 6.8 MB)
- **Uploads**: `doc/uploads/` — user-uploaded files including podcast audio
- **Plugins**: `doc/cookbook/` — installed PmWiki plugins
- **Theme base**: `beeblebrox-pmwiki2-theme-1.1/` — original Gila theme for reference
- **CMS plugin**: `cmslike-0.32/` — CMS-like interface plugin (not currently enabled)

## Key Files

- `doc/local/config.php` — main site configuration (skin, auth, uploads, plugins, language); loads credentials from `etc/pmwiki-secrets.php`
- `etc/pmwiki-secrets.php` — credentials file (gitignored, must be created manually on each environment)
- `doc/local/ordermail.php` — custom book order handler (POST form → email via PEAR Mail)
- `doc/pub/skins/pro-zurituutsch/gila.tmpl` — XHTML 1.1 page template (includes Google Analytics)
- `doc/pub/skins/pro-zurituutsch/gila.css` — site stylesheet
- `doc/pub/skins/pro-zurituutsch/skin.php` — skin logic (PHP)
- `doc/cookbook/rssenclosures.php` — RSS/podcast feed plugin with MP3 enclosures, GUIDs, author attribution, and trail deduplication
- `pmwiki-2.2.84/pmwiki.php` — PmWiki engine entry point

## Podcast

- RSS feed: `/PodCast/IndexPage?action=rss` — ~154 episodes with MP3 enclosures
- WikiTrail on `PodCast/IndexPage` has 229 entries, but only ~154 have wiki pages (the RSS plugin skips missing pages)
- Each RSS item has a `<guid isPermaLink="true">` based on its page URL for unique episode identification
- Feed includes `<managingEditor>` tag; item `<author>` falls back to `$RssFeedAuthor` when the wiki page has no author
- Duplicate trail entries are automatically deduplicated (e.g., `JissäiBänJoussouff4.02` appears twice in the trail)
- `$RssMaxItems = 250` in config.php (must be >= number of trail entries with wiki pages)
- `$RssFeedAuthor = "Pro-Zurituutsch"` in config.php — used as feed managing editor and fallback item author
- Audio files in `doc/uploads/PodCast/`
- `check_trail.py` — script to analyze which trail entries have wiki pages and/or MP3 files

## Tech Stack

- **PHP** on Apache with mod_rewrite
- **Flat-file storage** (no SQL database) — wiki pages stored as text files in `wiki.d/`
- **XHTML 1.1 + CSS 2.0** — standards-compliant, no JavaScript frameworks
- **PEAR Mail** for email functionality (order confirmations)
- **German language** interface via PmWiki's XLPage system; content in Swiss German

## Development

No build system, CI/CD, or Docker. This is a standard PHP/Apache application:

- Deploy via `git clone` on the live server (Git LFS required for audio files)
- After cloning, create `etc/pmwiki-secrets.php` with `$PmWikiSecret`, `$AuthUser`, and `$DefaultPasswords` (see template in git history)
- Configuration via `doc/local/config.php`
- Content editing happens through the PmWiki web interface
- `.htaccess` files protect `local/` and `cookbook/` directories from direct access
- MP3 audio files (282 files, ~3.4 GB) are tracked with **Git LFS**

## Conventions

- The custom skin follows XHTML 1.1 strict compliance and CSS 2.0 (no table layouts)
- Font sizes use relative units for accessibility
- External links get a visual indicator icon
- All user-facing text is in German
- Email communications (order confirmations) are in German
- Color scheme: light gray/white theme (body `#f5f5f5`, content `#ffffff`, accents `#5b9bd5` light blue) — matching the Jimdo reference site
