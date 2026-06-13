#!/usr/bin/env bash

set -euo pipefail
IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PACKAGE_DIR="$ROOT_DIR/Package"
KIT_DIR="$PACKAGE_DIR/installer-kit"
TEMPLATE_DIR="$ROOT_DIR/scripts/release/templates/installer-kit"
RUNTIME_STAGE="$(mktemp -d "${TMPDIR:-/tmp}/flatcms-lts-runtime.XXXXXX")"
EXTENSION_STAGE="$(mktemp -d "${TMPDIR:-/tmp}/flatcms-lts-extension.XXXXXX")"

cleanup() {
  rm -rf "$RUNTIME_STAGE"
  rm -rf "$EXTENSION_STAGE"
}
trap cleanup EXIT

require_bin() {
  local bin="$1"
  if ! command -v "$bin" >/dev/null 2>&1; then
    printf 'Missing required binary: %s\n' "$bin" >&2
    exit 1
  fi
}

require_file() {
  local path="$1"
  if [[ ! -f "$path" ]]; then
    printf 'Missing required file: %s\n' "$path" >&2
    exit 1
  fi
}

require_bin rsync
require_bin zip
require_bin php

RUNTIME_DIR="$RUNTIME_STAGE/runtime"
EXTENSION_DIR="$EXTENSION_STAGE/PagesBuilder"
KIT_ZIP_PATH="$KIT_DIR/flatcms.zip"
CORE_ZIP_PATH="$PACKAGE_DIR/flatcms.zip"
PAGES_BUILDER_ZIP="$PACKAGE_DIR/PagesBuilder.zip"
OUTER_PACKAGE_ZIP="$PACKAGE_DIR/package.zip"
INDEX_TEMPLATE="$TEMPLATE_DIR/index.html"
UNPACK_TEMPLATE="$TEMPLATE_DIR/unpack.php"

require_file "$INDEX_TEMPLATE"
require_file "$UNPACK_TEMPLATE"
require_file "$ROOT_DIR/data/modules.json"
require_file "$ROOT_DIR/app/Extensions/PagesBuilder/extension.json"
require_file "$ROOT_DIR/README.md"
require_file "$ROOT_DIR/LICENSING.md"
require_file "$ROOT_DIR/COMMERCIAL_LICENSE.md"
require_file "$ROOT_DIR/TRADEMARK.md"
require_file "$ROOT_DIR/CLA.md"

rm -rf "$KIT_DIR" "$OUTER_PACKAGE_ZIP" "$CORE_ZIP_PATH" "$PAGES_BUILDER_ZIP"
mkdir -p "$RUNTIME_DIR" "$KIT_DIR" "$EXTENSION_DIR"

rsync -a \
  --delete \
  --exclude='/.git/' \
  --exclude='/.gitignore' \
  --exclude='/.DS_Store' \
  --exclude='/.env' \
  --exclude='/.env.local' \
  --exclude='/.env.example' \
  --exclude='/.codex/' \
  --exclude='/Package/' \
  --exclude='/docs/' \
  --exclude='/scripts/' \
  --exclude='/tests/' \
  --exclude='/test-results/' \
  --exclude='*.md' \
  --exclude='*.xlsx' \
  --exclude='*.xls' \
  --exclude='*.docx' \
  --exclude='*.pptx' \
  --exclude='*.BAK' \
  --exclude='*.bak' \
  --exclude='*.tmp' \
  --exclude='*.orig' \
  --exclude='*.rej' \
  --exclude='*.zip' \
  --exclude='*.tar' \
  --exclude='*.tgz' \
  --exclude='*.gz' \
  --exclude='/storage/backups/' \
  --exclude='/storage/logs/' \
  --exclude='/storage/sessions/' \
  --exclude='/storage/tmp/' \
  --exclude='/storage/uploads/' \
  --exclude='/storage/app/secretbox.key' \
  --exclude='/public/.user.ini' \
  --exclude='/public/uploads/' \
  --exclude='/public/modules/' \
  --exclude='/public/widgets/' \
  --exclude='/public/release/' \
  --exclude='/app/Extensions/' \
  --exclude='/resources/licenses/' \
  --exclude='/resources/uploads/' \
  --exclude='/uploads/' \
  --exclude='/data/' \
  "$ROOT_DIR/" "$RUNTIME_DIR/"

find "$RUNTIME_DIR" -name '.DS_Store' -delete
find "$RUNTIME_DIR" -name '.gitkeep' -delete
find "$RUNTIME_DIR" -type f \( \
  -name '*.BAK' -o \
  -name '*.bak' -o \
  -name '*.tmp' -o \
  -name '*.orig' -o \
  -name '*.rej' -o \
  -name '*.zip' -o \
  -name '*.tar' -o \
  -name '*.tgz' -o \
  -name '*.gz' -o \
  -name '*.xlsx' -o \
  -name '*.xls' -o \
  -name '*.docx' -o \
  -name '*.pptx' \
\) -delete

rm -rf "$RUNTIME_DIR/data"
mkdir -p \
  "$RUNTIME_DIR/data/core/categories" \
  "$RUNTIME_DIR/data/core/contact_forms" \
  "$RUNTIME_DIR/data/core/media" \
  "$RUNTIME_DIR/data/core/pages" \
  "$RUNTIME_DIR/data/core/posts" \
  "$RUNTIME_DIR/data/users"

if [[ -f "$ROOT_DIR/data/.htaccess" ]]; then
  cp "$ROOT_DIR/data/.htaccess" "$RUNTIME_DIR/data/.htaccess"
fi
cp "$ROOT_DIR/data/modules.json" "$RUNTIME_DIR/data/modules.json"

cp "$ROOT_DIR/README.md" "$RUNTIME_DIR/README.md"
cp "$ROOT_DIR/LICENSING.md" "$RUNTIME_DIR/LICENSING.md"
cp "$ROOT_DIR/COMMERCIAL_LICENSE.md" "$RUNTIME_DIR/COMMERCIAL_LICENSE.md"
cp "$ROOT_DIR/TRADEMARK.md" "$RUNTIME_DIR/TRADEMARK.md"
cp "$ROOT_DIR/CLA.md" "$RUNTIME_DIR/CLA.md"

rm -rf \
  "$RUNTIME_DIR/public/uploads" \
  "$RUNTIME_DIR/public/modules" \
  "$RUNTIME_DIR/public/widgets" \
  "$RUNTIME_DIR/public/release"
mkdir -p "$RUNTIME_DIR/public/uploads"

rm -rf \
  "$RUNTIME_DIR/storage/backups" \
  "$RUNTIME_DIR/storage/logs" \
  "$RUNTIME_DIR/storage/sessions" \
  "$RUNTIME_DIR/storage/tmp" \
  "$RUNTIME_DIR/storage/uploads" \
  "$RUNTIME_DIR/storage/app/secretbox.key"
mkdir -p \
  "$RUNTIME_DIR/storage/cache/data" \
  "$RUNTIME_DIR/storage/cache/views" \
  "$RUNTIME_DIR/storage/logs" \
  "$RUNTIME_DIR/storage/sessions" \
  "$RUNTIME_DIR/storage/tmp"

cp "$INDEX_TEMPLATE" "$KIT_DIR/index.html"
cp "$UNPACK_TEMPLATE" "$KIT_DIR/unpack.php"

(
  cd "$RUNTIME_DIR"
  zip -rq "$KIT_ZIP_PATH" .
)

cp "$KIT_ZIP_PATH" "$CORE_ZIP_PATH"

rsync -a \
  --delete \
  --exclude='.DS_Store' \
  --exclude='.gitignore' \
  --exclude='*.zip' \
  --exclude='*.tar' \
  --exclude='*.tgz' \
  --exclude='*.gz' \
  "$ROOT_DIR/app/Extensions/PagesBuilder/" "$EXTENSION_DIR/"

find "$EXTENSION_DIR" -name '.DS_Store' -delete
find "$EXTENSION_DIR" -name '.gitkeep' -delete

(
  cd "$EXTENSION_STAGE"
  zip -rq "$PAGES_BUILDER_ZIP" PagesBuilder
)

(
  cd "$KIT_DIR"
  zip -rq "$OUTER_PACKAGE_ZIP" index.html unpack.php flatcms.zip
)

php -r '
$outer = $argv[1];
$core = $argv[2];
$extension = $argv[3];
foreach ([$outer, $core, $extension] as $path) {
    if (!is_file($path) || filesize($path) <= 0) {
        fwrite(STDERR, "Invalid archive: {$path}\n");
        exit(1);
    }
}
$zip = new ZipArchive();
if ($zip->open($outer) !== true) {
    fwrite(STDERR, "Unable to open package archive.\n");
    exit(1);
}
$entries = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entries[] = $zip->getNameIndex($i);
}
sort($entries);
$expected = ["flatcms.zip", "index.html", "unpack.php"];
if ($entries !== $expected) {
    fwrite(STDERR, "Invalid package root layout: " . implode(", ", $entries) . "\n");
    exit(1);
}
$zip->close();

$zip = new ZipArchive();
if ($zip->open($core) !== true) {
    fwrite(STDERR, "Unable to open core archive.\n");
    exit(1);
}
$requiredCore = [
    "index.php",
    "public/index.php",
    "app/Modules/Install/Controllers/InstallController.php",
    "app/Modules/AiAgent/module.json",
    "app/Modules/Backups/module.json",
    "app/Modules/Trash/module.json",
    "README.md",
    "LICENSING.md",
    "COMMERCIAL_LICENSE.md",
    "TRADEMARK.md",
    "CLA.md",
];
foreach ($requiredCore as $entry) {
    if ($zip->locateName($entry) === false) {
        fwrite(STDERR, "Missing core archive entry: {$entry}\n");
        exit(1);
    }
}
$forbiddenCorePrefixes = [
    "docs/",
    "scripts/",
    "tests/",
    "app/Extensions/",
    "data/extensions/pages-builder/",
];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = $zip->getNameIndex($i);
    foreach ($forbiddenCorePrefixes as $prefix) {
        if (str_starts_with($entry, $prefix)) {
            fwrite(STDERR, "Forbidden core archive entry: {$entry}\n");
            exit(1);
        }
    }
}
$zip->close();

$zip = new ZipArchive();
if ($zip->open($extension) !== true) {
    fwrite(STDERR, "Unable to open PagesBuilder archive.\n");
    exit(1);
}
if ($zip->locateName("PagesBuilder/extension.json") === false) {
    fwrite(STDERR, "PagesBuilder archive missing manifest.\n");
    exit(1);
}
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = $zip->getNameIndex($i);
    if (str_starts_with($entry, "app/")) {
        fwrite(STDERR, "PagesBuilder archive must not embed app/ paths: {$entry}\n");
        exit(1);
    }
}
$zip->close();
' "$OUTER_PACKAGE_ZIP" "$CORE_ZIP_PATH" "$PAGES_BUILDER_ZIP"

printf 'Installer kit ready:\n'
printf '  %s\n' "$KIT_DIR/index.html"
printf '  %s\n' "$KIT_DIR/unpack.php"
printf '  %s\n' "$KIT_ZIP_PATH"
printf 'Wrapper archive:\n'
printf '  %s\n' "$OUTER_PACKAGE_ZIP"
printf 'Standalone archives:\n'
printf '  %s\n' "$CORE_ZIP_PATH"
printf '  %s\n' "$PAGES_BUILDER_ZIP"
