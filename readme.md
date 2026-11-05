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
