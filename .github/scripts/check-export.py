"""
Checks a static export of a Novaris site, for the theme check workflow.

    python3 check-export.py <public dir> <site url> <export log>

Fails (exit 1) on:
- PHP warnings, notices, deprecations or errors in the export log
- pages the export skipped because they returned an error
- missing pages that the theme test data should always produce
- links or images to this site that don't point at an exported file,
  except the test data's deliberately missing files (`does-not-exist`)
- XML files (feeds, sitemaps) or search.json that don't parse
- HTML problems a browser would fix up badly, such as <p><figure>
- PHP error text in a page
"""

import html
import json
import os
import re
import sys
import xml.dom.minidom

public, site_url, log_file = sys.argv[1], sys.argv[2].rstrip('/'), sys.argv[3]
problems = []

# PHP messages in the export output.
log = open(log_file, encoding='utf-8', errors='replace').read()
for line in log.splitlines():
    if re.search(r'\b(PHP )?(Warning|Notice|Deprecated|Fatal error|Parse error)\b', line):
        problems.append(f'PHP message in export: {line.strip()[:300]}')
    if line.startswith('Skipped '):
        problems.append(f'Page not exported: {line[len("Skipped "):].strip()[:300]}')

# Pages the theme test data always produces.
expected = [
    'index.html',
    '404.html',
    'about/index.html',
    'blog/index.html',
    'blog/page/2/index.html',
    'blog/2025/06/01/markdown-reference/index.html',
    'blog/2025/05/15/no-title/index.html',
    'category/general/index.html',
    'category/empty-category/index.html',
    'parent-page/child-page/grandchild-page/index.html',
]
for path in expected:
    if not os.path.isfile(os.path.join(public, path)):
        problems.append(f'Missing page: {path}')

# Hidden posts must not be exported.
if os.path.exists(os.path.join(public, 'blog/2025/01/15/hidden-post')):
    problems.append('The hidden post was exported')

pages = 0
for root, _, files in os.walk(public):
    for name in files:
        path = os.path.join(root, name)
        rel = os.path.relpath(path, public)

        if name.endswith('.xml'):
            try:
                xml.dom.minidom.parse(path)
            except Exception as error:
                problems.append(f'Invalid XML in {rel}: {error}')

        if name == 'search.json':
            try:
                data = json.load(open(path, encoding='utf-8'))
                assert isinstance(data.get('entries'), list)
            except Exception as error:
                problems.append(f'Invalid search.json: {error}')

        if not name.endswith('.html'):
            continue

        pages += 1
        content = open(path, encoding='utf-8', errors='replace').read()

        if re.search(r'<p>\s*<figure', content):
            problems.append(f'<p><figure> in {rel}')

        if re.search(r'<b>(Warning|Notice|Deprecated|Fatal error)</b>|Uncaught |Stack trace:', content):
            problems.append(f'PHP error text in {rel}')

        # Links and images to this site.
        for match in re.finditer(r'(?:href|src)="([^"]+)"', content):
            url = html.unescape(match.group(1))

            if url.startswith(site_url):
                url = url[len(site_url):] or '/'
            elif not url.startswith('/') or url.startswith('//'):
                continue

            url = url.split('#')[0].split('?')[0]

            if 'does-not-exist' in url:
                continue

            target = os.path.join(public, url.lstrip('/'))
            if not (os.path.isfile(target) or os.path.isfile(os.path.join(target, 'index.html'))):
                problems.append(f'Broken link in {rel}: {url}')

print(f'Checked {pages} pages in {public}.')

# Report each problem once.
unique = list(dict.fromkeys(problems))
for problem in unique[:200]:
    print(f'::error::{problem}')

if unique:
    print(f'{len(unique)} problem(s) found.')
    sys.exit(1)

print('No problems found.')
