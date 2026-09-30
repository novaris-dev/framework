# Changelog

All notable changes to the Novaris Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 09.30.2026

### Added

- The page cache (`cache.global`) now checks whether content, config, view or
  theme files (including the parent theme) have changed since a page was
  cached, and rebuilds the page if they have. See `Router::lastModified()`.
- The static export now writes a manifest of generated files to
  `storage/export.json`, used to clean up the previous export safely.
- A `directory` cache store for ClassicPress and WordPress directory lookups.
- A PHPUnit test suite (`composer test`) that builds small throwaway sites and
  covers routing, content, caching, the static export and the CLI, plus a
  GitHub Actions workflow that runs it on PHP 8.2, 8.3 and 8.4.

### Changed

- `cache.expires` set to `0` now means "cache until something changes" for
  full-page caching. A value above `0` also rebuilds pages after that many
  seconds.
- Global cache exclusions (`cache.global_exclude`, plus the built-in `feed` and
  `purge/cache`) now match a path exactly or as a parent folder, instead of any
  path that starts with the same text.
- The content locator now checks both the content folder and each content file
  when deciding whether its cache is stale.
- `export --watch` prints the error output when an export fails, and reads the
  page count from the last line of output so PHP warnings no longer cause a
  false failure.
- The site URL is now required. If `url` in `config/app.php` is missing or
  empty (usually because `APP_URL` is not set), the framework stops early with
  a clear message instead of failing later with
  `app › url expects to be string, null given`. Sites that set `url` directly
  in `config/app.php` no longer need `APP_URL`.
- `Application::loadTheme()` no longer installs themes. The theme and its
  parent are installed during construction, where theme config is merged.
- The router now caches the newest file time per router instance instead of
  per process, and proxy aliases are only declared once per process, so more
  than one application can boot in the same process.

### Fixed

- Static export only exported entries for the first content type. The content
  locator now resolves every path from the content root and resets its state
  when the path changes.
- The cache purge page (`purge/cache/...`) crashed with a `TypeError` after
  flushing, because the view was called with the wrong arguments.
- Edited content files did not refresh the content cache, because only the
  folder's modified time was checked.
- Deleted or renamed content files could stay in the content cache.
- Front matter larger than 4 KB was ignored, both when listing content and when
  reading an entry's metadata.
- Pages could be served from the page cache indefinitely after content changed.
- Edits saved in the same second a page or content list was cached were not
  picked up.
- Pages such as `/feedback` were never cached because they start with `feed`.
- A corrupt or unreadable cache file caused a fatal error. It is now discarded
  and treated as a cache miss.
- Watch mode failed silently when a re-export failed.
- Exporting deleted every `.html` file in `public/`, including files not
  created by the export. Only files listed in the previous export's manifest
  are now removed.
- A theme (and its parent theme) that is not yet installed is now downloaded
  before its metadata and config are read, so the theme's config applies on
  the first run.
- Asset URLs for private apps: `asset()` now returns URLs under
  `public/assets/`, and the static export rewrites them to `/assets/` like
  theme assets.
- The static export could remove page content or rewrite other sites' URLs
  when rewriting asset URLs. It now only rewrites this site's own asset URLs,
  including sites installed in a subfolder.
- Visiting `/sitemap/{type}` with an unknown content type crashed instead of
  returning a 404.
- Sitemaps listed content types marked `sitemap: false` or `public: false`,
  and `Type::hasSitemap()` threw an error because it read the wrong config
  key.
- One content file with invalid front matter broke every page listing its
  folder. The file is now skipped and the error is logged.
- `/page/0` on the home page, collections, date archives, taxonomies and author
  archives showed the second page's entries instead of a 404.
- Content paths containing `..` could read `.md` files outside the content
  folder.
- Thumbnails crashed on servers without the Imagick extension. GD is now used
  as a fallback, and the original image is returned if neither is installed.
- Directory pages called the ClassicPress and WordPress APIs on every visit.
  Results are now cached for a day, or for 5 minutes when every request failed.
- A site without `index.md` returned an empty home page with a 200 status,
  which was cached and exported. It now returns a 404 with the notice, and
  shows the path relative to the site instead of the full server path.
- `bin/novaris` failed when the framework was symlinked into `vendor/` (for
  example with a Composer path repository). It now uses the autoload path from
  Composer's `vendor/bin/novaris`, and shows a clear message if no autoloader
  is found.
- Sites without `CommonMarkCoreExtension` in `config/markdown.php` (or without
  that file) failed to render any page. The core extension is now always
  included.
- Pagination on taxonomy terms linked to the taxonomy's base path (for example
  `/category/page/2`) instead of the term (`/category/uncategorized/page/2`).
- The static export wrote only the first page of each year, month and day
  archive, leaving broken pager links. Every page of a date archive is now
  exported.
- Sorting by `published` or `updated` put quoted or ISO-with-`T` dates above
  unquoted ones, because YAML parses them as text and numbers respectively.
  Dates are now compared as timestamps.
- Very large page numbers in the URL crashed paginated pages with a
  `TypeError` instead of returning a 404.
- Content types could not be marked private: `public` was missing from the
  content config schema, so `'public' => false` was rejected.
- Asking for a thumbnail size that is not configured crashed the page instead
  of returning `null`.
- Menus for a location with no configured items crashed instead of running
  `fallback_cb`.

### Removed

- `Novaris\Console\Kernel` and `Novaris\Console\Output`. They were unused and
  duplicated `bin/novaris` (which remains the command-line entry point).

[Unreleased]: https://github.com/novaris-dev/framework/compare/v1.0.0...develop
[1.0.0]: https://github.com/novaris-dev/framework/releases/tag/v1.0.0
