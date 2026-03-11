# Pro-Zurituutsch

**pro-zurituutsch.ch** — A PmWiki-based website for promoting and preserving Zurich German language and texts.

By Félix F. Wyss · [pro-zurituutsch.ch](https://pro-zurituutsch.ch)

## Overview

This repository contains the full website installation including:

- **PmWiki 2.2.84** — the wiki engine (`pmwiki-2.2.84/`)
- **Live site** — configuration, wiki pages, uploads, and custom skin (`doc/`)
- **Custom skin** — "pro-zurituutsch" theme based on Beeblebrox Gila (`doc/pub/skins/pro-zurituutsch/`)
- **Podcast audio** — Zurich German text recordings (`doc/uploads/PodCast/`)

## Requirements

- PHP with Apache and mod_rewrite
- PEAR Mail (for order email functionality)
- Git LFS (audio files are tracked with Git Large File Storage)

## Setup

1. Clone the repository: `git clone` (Git LFS will automatically fetch audio files)
2. Point the Apache document root to the `doc/` directory
3. Adjust `doc/local/config.php` for your environment

## License

GNU General Public License v3 — see [LICENSE](LICENSE)
