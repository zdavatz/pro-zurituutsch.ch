# Pro-Zurituutsch

**pro-zurituutsch.ch** — A PmWiki-based website for promoting and preserving Zurich German language and texts.

By Félix F. Wyss · [pro-zurituutsch.ch](https://pro-zurituutsch.ch)

## Overview

This repository contains the full website installation including:

- **PmWiki 2.2.84** — the wiki engine (`pmwiki-2.2.84/`)
- **Live site** — configuration, wiki pages, uploads, and custom skin (`doc/`)
- **Custom skin** — "pro-zurituutsch" theme based on Beeblebrox Gila (`doc/pub/skins/pro-zurituutsch/`)
- **Podcast audio** — 239 episodes in RSS feed (224 with MP3 enclosures), stored in `doc/uploads/`

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

The site serves a podcast feed at `/PodCast/IndexPage?action=rss` with 239 episodes of Zurich German text recordings (224 with MP3 enclosures, 15 without audio). The 268 trail entries are filtered: episodes without wiki pages are skipped by the RSS plugin. The feed is compatible with Spotify for Creators and other podcast platforms. Audio files are stored in `doc/uploads/PodCast/`.

Episodes without MP3 audio (missing files):
SprAlAner, RezPflInt, SpruchZ, Limerick29, JBJKap1, PrSidNtewaal, Fanatiker, Schlaaffloos1, Schlaaffloos2, Eereroueff, Eereroueff2, Site192HouhsRech1509, Site191Rech1509, 161Predigerchoor1489, 63Frawmeuischter1470

The RSS plugin (`doc/cookbook/rssenclosures.php`) generates RSS 2.0 with MP3 enclosures and full iTunes namespace support for Spotify and Apple Podcasts compatibility. Features include:

- `<guid>` per item for unique episode identification
- iTunes tags: `itunes:author`, `itunes:image`, `itunes:category`, `itunes:owner`, `itunes:explicit`, `itunes:type`, `itunes:summary`
- `<managingEditor>` and `<language>` tags
- Automatic deduplication of duplicate trail entries
- UTF-8 aware `entityencode()` — correctly converts multibyte characters (ä, ü, è, etc.) to Unicode codepoint entities, with Latin-1 fallback for legacy content

## License

GNU General Public License v3 — see [LICENSE](LICENSE)
