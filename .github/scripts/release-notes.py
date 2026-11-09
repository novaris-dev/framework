"""
Prints the changelog section for a version, for the release notes.

    python3 release-notes.py <changelog> <version>

Exits with an error if the changelog has no section for the version.
"""

import re
import sys

changelog, version = sys.argv[1], sys.argv[2]
text = open(changelog, encoding='utf-8').read()

match = re.search(
    r'^## \[' + re.escape(version) + r'\][^\n]*\n(.*?)(?=^## \[|^\[[^\]]+\]: |\Z)',
    text,
    re.S | re.M,
)

if not match or not match.group(1).strip():
    print(f'::error::changelog.md has no "## [{version}]" section.', file=sys.stderr)
    sys.exit(1)

print(match.group(1).strip())
