# Pro-Zurituutsch

**pro-zurituutsch.ch** — A PmWiki-based website for promoting and preserving Zurich German language and texts.

By Félix F. Wyss · [pro-zurituutsch.ch](https://pro-zurituutsch.ch)

## Overview

This repository contains the full website installation including:

- **PmWiki 2.2.84** — the wiki engine (`pmwiki-2.2.84/`)
- **Live site** — configuration, wiki pages, uploads, and custom skin (`doc/`)
- **Custom skin** — "pro-zurituutsch" theme based on Beeblebrox Gila (`doc/pub/skins/pro-zurituutsch/`)
- **Podcast audio** — ~154 Zurich German text recordings (`doc/uploads/PodCast/`), distributed via RSS feed (229 trail entries, but only ~154 have wiki pages)

## Requirements

- PHP with Apache and mod_rewrite
- PEAR Mail (for order email functionality)
- Git LFS (audio files are tracked with Git Large File Storage)

## Setup

1. Clone the repository: `git clone` (Git LFS will automatically fetch audio files)
2. Point the Apache document root to the `doc/` directory
3. Create `etc/pmwiki-secrets.php` with credentials (gitignored; see `doc/local/config.php` for expected variables)
4. Adjust `doc/local/config.php` for your environment

## Podcast / RSS

The site serves a podcast feed at `/PodCast/IndexPage?action=rss` with ~154 episodes of Zurich German text recordings (229 trail entries total; episodes without wiki pages are skipped by the RSS plugin). The feed is compatible with Spotify for Creators and other podcast platforms. Audio files are stored in `doc/uploads/PodCast/`.

The RSS plugin (`doc/cookbook/rssenclosures.php`) generates RSS 2.0 with MP3 enclosures. Each item includes a `<guid>` based on its page URL for unique episode identification. The feed includes a `<managingEditor>` tag for podcast author attribution. Duplicate trail entries are automatically deduplicated.

## License

GNU General Public License v3 — see [LICENSE](LICENSE)
