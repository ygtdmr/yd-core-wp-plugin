# YD Core (WordPress plugin)

![WordPress 6.8+](https://img.shields.io/badge/WordPress-6.8+-21759b?logo=wordpress)
![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-8892BF?logo=php) ![License GPL-2.0+](https://img.shields.io/badge/License-GPLv2%2B-brightgreen)
[![WordPress PHPCS](https://github.com/ygtdmr/yd-core-wp-plugin/actions/workflows/phpcs.yml/badge.svg?branch=main)](https://github.com/ygtdmr/yd-core-wp-plugin/actions/workflows/phpcs.yml)
[![GitHub release](https://img.shields.io/github/v/release/ygtdmr/yd-core-wp-plugin)](https://github.com/ygtdmr/yd-core-wp-plugin/releases/latest)

_**YD Core** is a **required** plugin for all **YD-based** WordPress plugins. It’s a lightweight framework that provides shared features, UI components, and helper classes._

[![PHP Doc](https://img.shields.io/badge/PHP%20Doc-Code%20Review-orange)](https://ygtdmr.github.io/yd-core-wp-plugin/)

## Key Features

- **Single source of truth** – shared utilities, abstractions and admin UI components for all YD plugins.
- **Admin UI toolkit** – SCSS‑driven inputs, dropdowns, colour‑pickers and more ready to drop into your screens.
- **REST helpers** – fluent wrappers around the WP REST API for painless route creation and response handling.
- **WooCommerce shortcuts** – product, cart and order helpers that auto‑activate when WooCommerce is present.
- **Translation ready** – `.po`/`.mo` files reside in `/languages` and load automatically.
- **Zero runtime dependencies** – no Composer or Node required in production; compiled assets are committed.

## Folder Structure

```
yd-core/
├── assets/          # Compiled CSS/JS and raw SCSS sources
├── languages/       # i18n files
├── src/
│   ├── admin/       # Admin pages & UI controls
│   ├── rest-api/    # Request, Response, Router abstractions
│   ├── utils/       # Helper classes (Product, Cart, User…)
│   └── *.php        # Core domain objects
└── yd-core.php      # Plugin bootstrap & metadata
```

> **Tip:** keep the `vendor/` directory out of source control.  
> Add `vendor/` to your `.gitignore` and run `composer install --no-dev` after cloning.

## Requirements

| Dependency | Minimum Version |
|------------|-----------------|
| WordPress  | 6.8             |
| PHP        | 8.0             |
| WooCommerce| 9.8             |
| YD Core    | 1.0             |
| Composer   | 2.5             |

## Installation (development)

```bash
git clone https://github.com/ygtdmr/yd-core-wp-plugin.git yd-core
```

Then activate **YD Core** from the WordPress *Plugins* screen.
> [!IMPORTANT]
> To compile SCSS sources into CSS. Requires Node.js and a Sass compiler during development.
> ```bash
> cd yd-core/assets/css/admin
> sass .:.
> ```

## Upgrading

Deactivate and **delete the old version** before uploading an updated build, or use `git pull` if you deploy from VCS.

## Testing

The plugin ships with unit-test friendly architecture. Hook your favourite runner (e.g. Pest, PHPUnit) and mock WordPress functions with BrainMonkey.

## Contributing

Pull requests are welcome! Please follow the guidelines:

* One feature or bug-fix per branch.
* Write descriptive commit messages in English.
* Adhere to WordPress PHP coding standards (`composer phpcs`).

## License

This project is open-source software licensed under the **GNU General Public License v2.0 or later**. See `license.txt` for full text.
