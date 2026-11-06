# Changelog

All notable changes to the Novaris Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `$engine->social()` prints the links from the `social` list in
  `config/app.php`, as a `.social-menu` list. Full URLs such as
  `https://github.com/you` stay as they are, and paths such as `/about` get the
  site's address. It prints nothing when the list is empty.

## [1.0.9] - 11.04.2026

### Fixed

- With `NOVARIS_USER_PATH` pointing at a folder inside the site, such as
  `NOVARIS_USER_PATH="theme-test-data"`, images still linked to `/user/media`,
  so web servers that serve images directly from disk (such as nginx) showed
  them as missing. Links in content written as `/user/media/...` now point at
  the folder's real address, such as `/theme-test-data/media/...`, and the
  static export copies the media there. A folder outside the site still uses
  `/user/media`, served by Novaris.

## [1.0.8] - 11.04.2026

### Added

- `novaris serve`, which runs the site with PHP's built-in web server
  (`--host` and `--port` options, default `localhost:8000`).

### Fixed

- With `NOVARIS_USER_PATH`, images under `/user/media` (such as thumbnails)
  returned 404 on live sites, because the web server looked for them in the
  site's own `user/media` folder. Novaris now serves them from the configured
  folder. PHP's built-in server answers missing files itself, so use
  `novaris serve` with it.

## [1.0.7] - 11.04.2026

### Added

- `NOVARIS_USER_PATH`, an optional `.env` setting that reads a site's
  `content` and `media` from another folder instead of `user/`, such as a
  clone of the theme test data. A relative path is relative to the site
  root. URLs stay under `/user`, so links in content keep working, and the
  static export copies the media to `user/media` as before. Without the
  setting, sites use their `user/` folder as before.

## [1.0.6] - 11.04.2026

### Fixed

- `novaris export --watch` now re-exports when files in the site's `config/` directory change.

## [1.0.5] - 11.04.2026

### Fixed

- `novaris export --watch` now re-exports when a parent theme's files change.

## [1.0.4] - 11.04.2026

### Fixed

- `featured_image()` treated `'featured-image' => false` (or any other value)
  in `config/app.php` as on, because it only checked that the key existed.
  Only `true` or an array of options turns featured images on.

## [1.0.3] - 11.03.2026

### Added

- A JSON schema for `theme.json` at `schemas/theme.schema.json`. Add
  `"$schema": "https://raw.githubusercontent.com/novaris-dev/framework/develop/schemas/theme.schema.json"`
  to a theme's `theme.json` for editor autocomplete and warnings about missing
  or misspelled fields.
- Readme documentation for themes and `theme.json`.

### Changed

- Theme features are configured only in `config/app.php`, under `supports`.
  Featured images use `supports.featured-image` (next to their image sizes) and
  custom headers use `supports.custom-header.default`. `featured_image()` and
  `custom_header()` no longer read `supports` from `theme.json`, and the
  `theme.json` schema no longer includes `supports`.

### Fixed

- The readme's installation example was missing its closing code fence.
- `featured_image()` returned `null` for themes that turn on featured images in
  `config/app.php`, because it only checked `theme.json`.

## [1.0.2] - 11.03.2026

### Added

- Themes can be installed from any GitHub user or organization. Set
  `'theme' => 'owner/slug'` in `config/app.php` (or `"parent": "owner/slug"` in
  `theme.json`) to install from that repository. A plain slug, such as
  `'amicable'`, still installs from `novaris-dev`. The theme's folder is always
  the slug, for example `themes/amicable`.

### Changed

- Theme names are now validated. Names other than `slug` or `owner/slug`, such
  as `../x`, stop with a clear message instead of being used in folder paths
  and GitHub URLs.

## [1.0.1] - 10.20.2026

### Changed

- PHP 8.1 or newer is now required. The framework could not be installed on
  PHP 8.0, because its dependencies already required 8.1.
- The test workflow now also runs on PHP 8.5.

### Fixed

- `updated()` returned a garbled string instead of a date. It now returns the
  `updated` date, then the `published` date, then the file's modified time.
- `featured_image()` crashed with a call to an undefined `featuredImage()`
  method. It now returns the entry's thumbnail.

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

[Unreleased]: https://github.com/novaris-dev/framework/compare/v1.0.9...develop
[1.0.9]: https://github.com/novaris-dev/framework/releases/tag/v1.0.9
[1.0.8]: https://github.com/novaris-dev/framework/releases/tag/v1.0.8
[1.0.7]: https://github.com/novaris-dev/framework/releases/tag/v1.0.7
[1.0.6]: https://github.com/novaris-dev/framework/releases/tag/v1.0.6
[1.0.5]: https://github.com/novaris-dev/framework/releases/tag/v1.0.5
[1.0.4]: https://github.com/novaris-dev/framework/releases/tag/v1.0.4
[1.0.3]: https://github.com/novaris-dev/framework/releases/tag/v1.0.3
[1.0.2]: https://github.com/novaris-dev/framework/releases/tag/v1.0.2
[1.0.1]: https://github.com/novaris-dev/framework/releases/tag/v1.0.1
[1.0.0]: https://github.com/novaris-dev/framework/releases/tag/v1.0.0
