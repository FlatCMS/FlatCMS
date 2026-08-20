<?php
/**
 * FlatCMS Update Manager repositories.
 *
 * Core and appliance releases are distributed by flat-cms.fr.
 * Marketplace components are distributed by marketplace.flat-cms.fr.
 */

declare(strict_types=1);

return [
    'core' => (string) env('FLATCMS_UPDATE_CORE_CATALOG_URL', 'https://flat-cms.fr/api/updates/core.json'),
    'appliances' => (string) env('FLATCMS_UPDATE_APPLIANCES_CATALOG_URL', 'https://flat-cms.fr/api/updates/appliances.json'),
    'modules' => (string) env('FLATCMS_UPDATE_MODULES_CATALOG_URL', 'https://marketplace.flat-cms.fr/api/updates/modules.json'),
    'extensions' => (string) env('FLATCMS_UPDATE_EXTENSIONS_CATALOG_URL', 'https://marketplace.flat-cms.fr/api/updates/extensions.json'),
    'plugins' => (string) env('FLATCMS_UPDATE_PLUGINS_CATALOG_URL', 'https://marketplace.flat-cms.fr/api/updates/plugins.json'),
    'themes' => (string) env('FLATCMS_UPDATE_THEMES_CATALOG_URL', 'https://marketplace.flat-cms.fr/api/updates/themes.json'),
];
