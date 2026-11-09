# Novaris Framework

Novaris is a lightweight PHP framework designed to provide the core foundation for building flat-file, content-driven websites and applications.

It provides the underlying architecture for the **Novaris Content Management System (CMS)**, including content management, routing, caching, configuration, templates, service providers, dependency injection, and other tools needed to build dynamic websites without requiring a traditional database.

## Requirements

- PHP 8.1 or higher
- Composer

PHP 8.2 or newer is recommended for development.

## Installation

Install Novaris using Composer:

```bash
composer require novaris-dev/framework
```

## Content folder

A site's content and media live in its `user/` folder, in `user/content` and `user/media`.

To use another folder for a while, such as a clone of [theme-test-data](https://github.com/novaris-dev/theme-test-data) when testing a theme, add `NOVARIS_USER_PATH` to the site's `.env`:

```
NOVARIS_USER_PATH=../theme-test-data
```

The folder needs `content` and `media` folders, like `user/`. A relative path is relative to the site root. Remove the line to go back to the site's own `user/` folder.

Content links to images as `/user/media/...`, and Novaris rewrites those links to wherever the folder is:

- **A folder inside the site**, such as `NOVARIS_USER_PATH="theme-test-data"`: images link to `/theme-test-data/media/...`, so the web server serves them directly. Nothing else to set up.
- **A folder outside the site**, such as `../theme-test-data`: images keep their `/user/media/...` links and Novaris serves them, which works with servers that send missing files to `index.php`. PHP's built-in server doesn't, so use `vendor/bin/novaris serve` with it.

Resized images are saved next to the originals, so they are written into that folder.

## Search

Novaris can build a search index that themes search in the browser, so search works the same on live sites and static exports. Turn it on in `config/app.php`, under `supports`:

```php
'supports' => [
    'search' => true,                  // or [ 'length' => 5000 ]
],
```

A theme can turn it on in its own `config/app.php`, and a site can turn it off with `'search' => false`.

With search on:

- **`/search.json`** lists every public post and page: its `title`, `url`, content `type`, `date`, `terms` (such as categories), a short `excerpt`, and its `text` as plain text, without Markdown or HTML. Hidden entries (`visibility: hidden`), private content types and private `_` folders are left out, and so are taxonomies such as categories. `length` sets the most characters of text per entry (default 5000).
- **`/search`** is a page for the results. It uses the theme's `search` template, or the `page` template if there isn't one, and `is_search()` is true on it.
- **`novaris export`** writes both, as `search.json` and `search/index.html`, with URLs for the exported site.

A content type can be left out, or a taxonomy added, with its `search` option in `config/content.php`:

```php
'post' => [ 'path' => '_posts', 'search' => false ],
```

Themes show the search box and results. These functions in `Novaris\Theme\Site` help:

| Function | Returns |
|----------|---------|
| `search_enabled()` | Whether search is on |
| `search_url( $query = '' )` | The search page URL, such as `/search?q=gallery` |
| `search_index_url()` | The URL of `search.json`, for the theme's script to load |
| `search_query()` | The `?q=` query on live sites, for filling in the search box. Static exports have no request, so scripts should read the query from the address |

## Embeds

With embeds on, a link on its own line in Markdown becomes an embed:

```markdown
Some text.

https://www.youtube.com/watch?v=dQw4w9WgXcQ

https://vimeo.com/76979871

/user/media/clip.mp4
```

- **YouTube** (`youtube.com/watch`, `youtu.be`, `/shorts/`, `/live/`, `/embed/`, with a `t=` start time) and **Vimeo** (including unlisted and channel links) become videos.
- **Video and audio files** (`.mp4`, `.webm`, `.ogv`, `.mov`, `.mp3`, `.m4a`, `.ogg`, `.wav`, `.flac`, `.opus`) get a `<video>` or `<audio>` player.

Nothing is fetched from the internet while building the page, so embeds work the same on live sites and static exports. Links inside a sentence, links with their own text (`[My video](…)`), and links to anything else stay links.

Turn embeds on in `config/app.php`, under `supports`:

```php
'supports' => [
    'embeds' => true,   // or [ 'click_to_load' => true, 'privacy' => true ]
],
```

- **`click_to_load`** (default `true`): YouTube and Vimeo show a play button, with the video's thumbnail for YouTube, and the theme's script swaps in the real player when it's clicked (`.embed__play[data-embed-src]`). The provider's scripts only load for people who play the video, and without the script the play button is a link to the video. Set it to `false` to put the player in the page straight away, as a lazy-loaded iframe, with no script needed.
- **`privacy`** (default `true`): YouTube players use youtube-nocookie.com and Vimeo players use `dnt=1`.

Embeds render as `<figure class="embed embed--youtube">` (or `--vimeo`, `--video`, `--audio`), with the player inside `.embed__frame`. Themes style them, usually as a 16:9 box.

## Themes

A site picks its theme in `config/app.php`:

```php
'theme' => 'amicable',             // installs from novaris-dev/amicable
'theme' => 'someone/their-theme',  // installs from any GitHub user or organization
```

A missing theme is downloaded from the repository's latest GitHub release into `themes/{slug}`.

### theme.json

Every theme has a `theme.json` at its root:

```json
{
    "$schema": "https://raw.githubusercontent.com/novaris-dev/framework/develop/schemas/theme.schema.json",
    "name": "Amicable",
    "slug": "amicable",
    "version": "0.2.0",
    "repository": "novaris-dev/amicable"
}
```

| Field | Required | Description |
|---|---|---|
| `name` | Yes | Theme name shown to people. |
| `slug` | Yes | Folder name. Must match the GitHub repository name. |
| `version` | Yes | Version without a leading `v`, such as `1.0.0`. |
| `repository` | For updates | GitHub repository updates come from, as `owner/slug`. |
| `parent` | No | Parent theme, as `slug` or `owner/slug`. |

Theme features, such as featured images and custom headers, are configured in `config/app.php` under `supports`, not in `theme.json`.

The `$schema` line is optional. It gives editors such as VS Code autocomplete and warnings for missing or misspelled fields. This file is not the WordPress `theme.json` format.

To be installable, a theme's GitHub release needs an attached zip named `{slug}.{version}.zip`, such as `amicable.0.2.0.zip`, containing the theme folder and its `theme.json`.
