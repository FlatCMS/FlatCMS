<p align="center">
  <img src="https://wiki.flat-cms.fr/uploads/images/logo-flatcms.webp" alt="FlatCMS Logo" width="700">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Architecture-HMVC-4F46E5?style=for-the-badge" alt="Architecture">
  <img src="https://img.shields.io/badge/Storage-JSON-F59E0B?style=for-the-badge" alt="Storage">
  <img src="https://img.shields.io/badge/Line-LTS%20Core-0F766E?style=for-the-badge" alt="LTS Core">
</p>

## FlatCMS LTS Core

`FlatCMS LTS Core` is the stable core source line of FlatCMS.

This repository is intentionally limited to the durable CMS perimeter:

- native PHP
- HMVC architecture
- PSR-4 autoloading
- JSON file storage
- modular admin and frontend runtime
- auth, pages, posts, comments, contact, media, menus, footer, themes
- installer runtime

Experimental, commercial, and unstable authoring lanes are intentionally kept
outside this repository.

## Repository Nature

This repository is a **runtime core repository**.

It excludes packaging automation and operational release tooling. Distribution
assembly belongs to a separate release lane.

## Installer Contract

Canonical installer entry after extraction:

- `index.php?step=1`

Apache compatibility alias kept for root docroot deployments:

- `/install/`

## Validation

Runtime validation in this repository focuses on:

- PHP syntax integrity
- JSON flat-file integrity
- stable admin/frontend runtime behavior
- install flow behavior through the shipped runtime

## License

FlatCMS uses a mixed licensing model across the broader product line.

This repository is intended to ship the open-source core line. See:

- [LICENSE](LICENSE)
- [LICENSING.md](LICENSING.md)
- [COMMERCIAL_LICENSE.md](COMMERCIAL_LICENSE.md)
- [TRADEMARK.md](TRADEMARK.md)
- [CLA.md](CLA.md)
