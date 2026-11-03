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
| `supports` | No | Features the theme supports, such as `custom-header`. Featured images are turned on in `config/app.php` instead, under `supports.featured-image`. |

The `$schema` line is optional. It gives editors such as VS Code autocomplete and warnings for missing or misspelled fields. This file is not the WordPress `theme.json` format.

To be installable, a theme's GitHub release needs an attached zip named `{slug}.{version}.zip`, such as `amicable.0.2.0.zip`, containing the theme folder and its `theme.json`.
