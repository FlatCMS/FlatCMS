<?php
/**
 * FlatCMS Update Manager repositories.
 *
 * All official releases are distributed by flat-cms.fr.
 */

declare(strict_types=1);

return [
    'core' => (string) env('FLATCMS_UPDATE_CORE_CATALOG_URL', 'https://flat-cms.fr/api/updates/core.json'),
    'appliances' => (string) env('FLATCMS_UPDATE_APPLIANCES_CATALOG_URL', 'https://flat-cms.fr/api/updates/appliances.json'),
    'modules' => (string) env('FLATCMS_UPDATE_MODULES_CATALOG_URL', 'https://flat-cms.fr/api/updates/modules.json'),
    'extensions' => (string) env('FLATCMS_UPDATE_EXTENSIONS_CATALOG_URL', 'https://flat-cms.fr/api/updates/extensions.json'),
    'plugins' => (string) env('FLATCMS_UPDATE_PLUGINS_CATALOG_URL', 'https://flat-cms.fr/api/updates/plugins.json'),
    'themes' => (string) env('FLATCMS_UPDATE_THEMES_CATALOG_URL', 'https://flat-cms.fr/api/updates/themes.json'),
];
