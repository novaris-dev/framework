# Changelog

All notable changes to the Novaris Framework are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- The page cache (`cache.global`) now checks whether content, config, view or
  theme files (including the parent theme) have changed since a page was
  cached, and rebuilds the page if they have. See `Router::lastModified()`.
- The static export now writes a manifest of generated files to
  `storage/export.json`, used to clean up the previous export safely.

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
- `APP_URL` is now required. If it is missing or empty, the framework stops
  early with a clear message instead of failing later with
  `app › url expects to be string, null given`.

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

### Removed

- `Novaris\Console\Kernel` and `Novaris\Console\Output`. They were unused and
  duplicated `bin/novaris` (which remains the command-line entry point).
