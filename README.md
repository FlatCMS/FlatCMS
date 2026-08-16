<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://flat-cms.fr/uploads/logo/new-logo-flatcms-transparent-dark.webp">
    <source media="(prefers-color-scheme: light)" srcset="https://flat-cms.fr/uploads/logo/new-logo-flatcms-transparent-light.webp">
    <img src="https://flat-cms.fr/uploads/logo/new-logo-flatcms-transparent-light.webp" alt="FlatCMS — Simple, léger, performant" width="640">
  </picture>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Release-1.0.0%20LTS-4F46E5?style=for-the-badge" alt="FlatCMS 1.0.0 LTS">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3 or newer">
  <img src="https://img.shields.io/badge/Storage-JSON-F59E0B?style=for-the-badge" alt="JSON flat-file storage">
  <img src="https://img.shields.io/badge/License-AGPL--3.0-0F766E?style=for-the-badge" alt="GNU AGPL 3.0 or later">
</p>

<p align="center">
  <strong>A lightweight, modular PHP CMS with no mandatory SQL database.</strong>
</p>

<p align="center">
  <a href="https://flat-cms.fr/">Official website</a> ·
  <a href="https://www.flat-cms.fr/fr-FR/wiki/">Documentation</a> ·
  <a href="https://github.com/FlatCMS/FlatCMS/releases/tag/v1.0.0">Download v1.0.0 LTS</a>
</p>

## Overview

FlatCMS is a native PHP content management system built around a readable HMVC
architecture, PSR-4 autoloading and JSON flat-file storage. It is designed for
sites that need a clear editorial workflow and a portable codebase without a
mandatory database server.

The public repository contains the stable open-source runtime used by the
official FlatCMS distribution. Experimental, internal and premium development
lanes are maintained separately.

## Main Features

- pages and multilingual HTML content
- blog posts, categories and comments
- media library with structured upload directories
- native contact forms and message inbox
- menus, footer, themes and appearance settings
- users, roles and permissions
- backups and restore tools
- human-readable sitemap and `sitemap.xml` generation
- optional AI assistant and content trash modules
- browser-based multilingual installer

## Requirements

- PHP 8.3 or newer
- required PHP extensions: `json`, `mbstring`, `session`, `fileinfo`
- recommended PHP extensions: `openssl`, `gd`, `zip`, `curl`
- Apache or LiteSpeed, Nginx, or Microsoft IIS
- write access to FlatCMS runtime directories during installation and use

FlatCMS does not require MySQL, MariaDB or another SQL service.

## Installation

### Web package

The recommended archive for a new installation is `package.zip` from the
[official release](https://github.com/FlatCMS/FlatCMS/releases/tag/v1.0.0).

1. Extract `package.zip` into the website document root.
2. Open the website URL in a browser.
3. The launcher extracts `flatcms.zip`, removes its temporary installation
   files and opens the FlatCMS installer.
4. Complete the environment, administrator and site configuration steps.

### Runtime archive

Use `flatcms.zip` when the hosting or deployment process already handles archive
extraction.

1. Extract `flatcms.zip` into the website document root.
2. Open `index.php?step=1` in a browser.

For Apache-compatible root deployments, `/install/` remains available as a
compatibility alias. The installer provides configuration guidance for Apache,
LiteSpeed, Nginx and IIS.

## Content Storage

Pages and posts keep metadata separate from human-readable HTML content:

```text
data/core/pages/page_home/
├── index.json
├── content.html
└── translations/
    └── en-US/
        ├── index.json
        └── content.html
```

Posts follow the same contract under `data/core/posts/`. Public media are stored
under `public/uploads/`, while secrets and private runtime data remain outside
the public document root.

## Repository Scope

This repository is the FlatCMS product source of truth. It intentionally omits
private release automation, internal governance material, commercial services
and non-public QA infrastructure.

The release archives are assembled and validated through a separate internal
pipeline. Generated archives and runtime data are therefore not committed here.

## License

Unless a file states otherwise, first-party FlatCMS source code is licensed
under the GNU Affero General Public License v3.0 or later.

- [GNU AGPL license](LICENSE)
- [FlatCMS licensing model](LICENSING.md)
- [Third-party notices](THIRD_PARTY_NOTICES.md)
- [Commercial components](COMMERCIAL_LICENSE.md)
- [Trademark policy](TRADEMARK.md)
- [Contributor License Agreement](CLA.md)
