#!/usr/bin/env python3
"""Check WikiTrail entries from PodCast.IndexPage for missing wiki pages and MP3 files.

Handles encoding issues where PmWiki's targets= field has ASCII-stripped versions
of filenames that actually contain Latin-1 special characters on disk.
"""

import os
import re
import urllib.parse
from collections import Counter

WIKI_D = "/home/zeno/.software/pro-zurituutsch.ch/doc/wiki.d"
UPLOADS = "/home/zeno/.software/pro-zurituutsch.ch/doc/uploads"
INDEX_PAGE = os.path.join(WIKI_D, "PodCast.IndexPage")

with open(INDEX_PAGE, "rb") as f:
    raw = f.read()
content = raw.decode("latin-1")

targets_line = None
text_line = None
for line in content.split("\n"):
    if line.startswith("targets="):
        targets_line = line[len("targets="):]
    if line.startswith("text="):
        text_line = line[len("text="):]

targets = [t.strip() for t in targets_line.split(",") if t.strip()]
decoded_text = urllib.parse.unquote(text_line)
raw_links = re.findall(r'\[\[([^\]]+)\]\]', decoded_text)
clean_links = []
for link in raw_links:
    if "|" in link:
        link = link.split("|")[0]
    if "#" in link:
        link = link.split("#")[0]
    link = link.strip()
    if link:
        clean_links.append(link)

def ascii_skeleton(s):
    """Remove all non-ASCII chars from string."""
    return ''.join(c for c in s if ord(c) < 128)

# Build wiki file index - exact + skeleton-based
wiki_exact = set()
wiki_by_skeleton = {}  # ascii_skeleton -> [actual_names]
for fb in os.listdir(WIKI_D.encode()):
    name = fb.decode("latin-1")
    wiki_exact.add(name)
    skel = ascii_skeleton(name)
    wiki_by_skeleton.setdefault(skel, []).append(name)
    wiki_by_skeleton.setdefault(skel.lower(), []).append(name)

# Build MP3 index - exact + skeleton-based
mp3_exact = set()
mp3_by_skeleton = {}
for dp, _, fns in os.walk(UPLOADS.encode()):
    for fn in fns:
        if fn.lower().endswith(b".mp3"):
            rel = os.path.relpath(os.path.join(dp, fn), UPLOADS.encode()).decode("latin-1")
            mp3_exact.add(rel)
            skel = ascii_skeleton(rel)
            mp3_by_skeleton.setdefault(skel, []).append(rel)
            mp3_by_skeleton.setdefault(skel.lower(), []).append(rel)

def find_wiki(target):
    """Returns (found, actual_name_or_None)."""
    if target in wiki_exact:
        return True, target
    # Case insensitive
    for name in wiki_exact:
        if name.lower() == target.lower():
            return True, name
    # Skeleton match (target IS already ASCII from targets= field)
    matches = wiki_by_skeleton.get(target, []) + wiki_by_skeleton.get(target.lower(), [])
    matches = list(set(matches))
    if matches:
        return True, matches[0]
    return False, None

def find_mp3(mp3_key):
    """Returns (found, actual_name_or_None)."""
    if mp3_key in mp3_exact:
        return True, mp3_key
    for name in mp3_exact:
        if name.lower() == mp3_key.lower():
            return True, name
    matches = mp3_by_skeleton.get(mp3_key, []) + mp3_by_skeleton.get(mp3_key.lower(), [])
    matches = list(set(matches))
    if matches:
        return True, matches[0]
    return False, None

print("=== WikiTrail Analysis for PodCast.IndexPage ===")
print(f"Total [[...]] entries in text field: {len(clean_links)}")
print(f"Total targets in targets= field:    {len(targets)}")
print()

missing_pages = []
missing_mp3s = []
missing_both = []
ok_entries = []

for target in targets:
    dot_pos = target.index(".")
    group = target[:dot_pos]
    page = target[dot_pos+1:]

    page_found, actual_page = find_wiki(target)
    mp3_key = f"{group}/{page}.mp3"
    mp3_found, actual_mp3 = find_mp3(mp3_key)

    entry = {
        "target": target,
        "actual_page": actual_page,
        "mp3_key": mp3_key,
        "actual_mp3": actual_mp3,
        "page_found": page_found,
        "mp3_found": mp3_found,
    }

    if not page_found and not mp3_found:
        missing_both.append(entry)
    elif not page_found:
        missing_pages.append(entry)
    elif not mp3_found:
        missing_mp3s.append(entry)
    else:
        ok_entries.append(entry)

def safe_print(s):
    try:
        print(s)
    except UnicodeEncodeError:
        print(s.encode("utf-8", errors="replace").decode("utf-8"))

safe_print(f"OK (both wiki page AND MP3 exist): {len(ok_entries)}")
print()

# Show encoding normalization matches
enc_matches = [e for e in ok_entries
               if (e['actual_page'] and e['actual_page'] != e['target'])
               or (e['actual_mp3'] and e['actual_mp3'] != e['mp3_key'])]
if enc_matches:
    safe_print(f"--- {len(enc_matches)} OK entries matched via encoding/case normalization ---")
    for e in enc_matches:
        parts = []
        if e['actual_page'] and e['actual_page'] != e['target']:
            parts.append(f"page={e['actual_page']}")
        if e['actual_mp3'] and e['actual_mp3'] != e['mp3_key']:
            parts.append(f"mp3={e['actual_mp3']}")
        safe_print(f"  {e['target']} -> {', '.join(parts)}")
    print()

if missing_both:
    safe_print("=" * 70)
    safe_print(f"MISSING BOTH wiki page AND MP3: {len(missing_both)}")
    safe_print("=" * 70)
    for e in missing_both:
        safe_print(f"  {e['target']}")
    print()

if missing_pages:
    safe_print("=" * 70)
    safe_print(f"MISSING WIKI PAGE only (MP3 exists): {len(missing_pages)}")
    safe_print("=" * 70)
    for e in missing_pages:
        mp3_note = ""
        if e['actual_mp3'] and e['actual_mp3'] != e['mp3_key']:
            mp3_note = f"  [mp3 found as: {e['actual_mp3']}]"
        safe_print(f"  {e['target']}{mp3_note}")
    print()

if missing_mp3s:
    safe_print("=" * 70)
    safe_print(f"MISSING MP3 only (wiki page exists): {len(missing_mp3s)}")
    safe_print("=" * 70)
    for e in missing_mp3s:
        page_note = ""
        if e['actual_page'] and e['actual_page'] != e['target']:
            page_note = f"  [page found as: {e['actual_page']}]"
        safe_print(f"  {e['target']}  ->  expected: uploads/{e['mp3_key']}{page_note}")
    print()

safe_print("")
safe_print("=" * 70)
safe_print("SUMMARY")
safe_print("=" * 70)
safe_print(f"Total trail entries (targets=):       {len(targets)}")
safe_print(f"OK (both page and MP3):               {len(ok_entries)}")
safe_print(f"Missing wiki page only:               {len(missing_pages)}")
safe_print(f"Missing MP3 only:                     {len(missing_mp3s)}")
safe_print(f"Missing both page and MP3:            {len(missing_both)}")

dupes = {k: v for k, v in Counter(targets).items() if v > 1}
if dupes:
    print(f"\nDuplicate entries in targets=:")
    for k, v in dupes.items():
        safe_print(f"  {k}: {v} times")

raw_dupes = {k: v for k, v in Counter(clean_links).items() if v > 1}
if raw_dupes:
    print(f"\nDuplicate [[...]] links in text field:")
    for k, v in raw_dupes.items():
        safe_print(f"  [[{k}]]: {v} times")

print(f"\nNote: text has {len(clean_links)} links, targets has {len(targets)} entries.")
print("The difference is because [[JissiBnJoussouff4.02]] appears twice in text")
print("but PmWiki de-duplicates in targets=.")
