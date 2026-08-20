<?php
/** FlatCMS update package component prerequisites. */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateRequirementService
{
    private const CATALOGS = ['modules', 'extensions', 'plugins', 'themes'];

    public function __construct(private ?InstalledPackageService $installed = null)
    {
    }

    /** @param mixed $requirements @return array<int,array{catalog:string,slug:string,version:string}> */
    public function normalize(mixed $requirements): array
    {
        if ($requirements === null) {
            return [];
        }
        if (!is_array($requirements)) {
            throw new \RuntimeException('update_package_requirements_invalid');
        }
        $normalized = [];
        $seen = [];
        foreach ($requirements as $entry) {
            if (!is_array($entry)) {
                throw new \RuntimeException('update_package_requirements_invalid');
            }
            $catalog = strtolower(trim((string) ($entry['catalog'] ?? '')));
            $slug = strtolower(trim((string) ($entry['slug'] ?? '')));
            $version = trim((string) ($entry['version'] ?? ''));
            if (!in_array($catalog, self::CATALOGS, true)
                || preg_match('/^[a-z0-9][a-z0-9._-]*$/', $slug) !== 1
                || preg_match('/^(>=|<=|>|<|==|=)?\s*[0-9][0-9A-Za-z._+-]*$/', $version) !== 1) {
                throw new \RuntimeException('update_package_requirements_invalid');
            }
            $key = $catalog . ':' . $slug;
            if (isset($seen[$key])) {
                throw new \RuntimeException('update_package_requirements_invalid');
            }
            $seen[$key] = true;
            $normalized[] = ['catalog' => $catalog, 'slug' => $slug, 'version' => $version];
        }
        return $normalized;
    }

    /** @param mixed $requirements @return array<int,array{catalog:string,slug:string,version:string,current:string}> */
    public function missing(mixed $requirements): array
    {
        $requirements = $this->normalize($requirements);
        if ($requirements === []) {
            return [];
        }
        $this->installed ??= new InstalledPackageService();
        $installed = $this->installed->all();
        $missing = [];
        foreach ($requirements as $requirement) {
            $current = '';
            foreach ((array) ($installed[$requirement['catalog']] ?? []) as $package) {
                if (is_array($package) && strtolower((string) ($package['slug'] ?? '')) === $requirement['slug']) {
                    $current = trim((string) ($package['version'] ?? ''));
                    break;
                }
            }
            if ($current === '' || !$this->meets($current, $requirement['version'])) {
                $missing[] = $requirement + ['current' => $current !== '' ? $current : 'missing'];
            }
        }
        return $missing;
    }

    /** @param mixed $requirements */
    public function assertInstalled(mixed $requirements): void
    {
        $missing = $this->missing($requirements);
        if ($missing === []) {
            return;
        }
        $requirement = $missing[0];
        throw new \RuntimeException('update_apply_package_requirement_failed:'
            . $requirement['catalog'] . ':' . $requirement['slug'] . ':'
            . $requirement['version'] . ':' . $requirement['current']);
    }

    private function meets(string $current, string $requirement): bool
    {
        preg_match('/^(>=|<=|>|<|==|=)?\s*([0-9][0-9A-Za-z._+-]*)$/', $requirement, $match);
        $operator = (string) ($match[1] ?? '');
        $operator = $operator === '' ? '>=' : ($operator === '=' ? '==' : $operator);
        return version_compare($this->comparable($current), $this->comparable((string) ($match[2] ?? '0.0.0')), $operator);
    }

    private function comparable(string $version): string
    {
        if (preg_match('/[0-9]+(?:\.[0-9A-Za-z_-]+)+/', trim($version), $match) === 1) {
            return (string) $match[0];
        }
        return trim($version) !== '' ? trim($version) : '0.0.0';
    }
}
