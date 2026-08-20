<?php
/** FlatCMS standalone recovery entrypoint. */
declare(strict_types=1);
$basePath = dirname(__DIR__);
$runtime = $basePath . '/storage/recovery/runtime/recovery-runtime.php';
if (!is_file($runtime)) { http_response_code(404); exit; }
require $runtime;
if (!function_exists('flatcms_recovery_run')) { http_response_code(404); exit; }
flatcms_recovery_run($basePath);
