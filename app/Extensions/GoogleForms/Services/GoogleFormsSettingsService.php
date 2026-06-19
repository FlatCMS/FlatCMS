<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Services;

use App\Core\FlatFile;
use App\Core\Security\SecretBox;

final class GoogleFormsSettingsService
{
    private FlatFile $settings;
    private SecretBox $secretBox;

    public function __construct()
    {
        $this->settings = FlatFile::for('extensions/google-forms/settings');
        $this->secretBox = new SecretBox();
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $item = $this->first();
        $settings = $item === null
            ? $this->defaults()
            : array_replace($this->defaults(), $item);

        $settings['google_client_id'] = $this->envValue('GOOGLE_OAUTH_CLIENT_ID');
        $settings['google_client_secret'] = $this->envValue('GOOGLE_OAUTH_CLIENT_SECRET');

        unset($settings['google_client_secret_encrypted']);

        return $settings;
    }

    /** @return array<string, mixed> */
    public function public(): array
    {
        $settings = $this->all();

        unset(
            $settings['google_client_secret'],
            $settings['google_client_secret_encrypted']
        );

        return $settings;
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): bool
    {
        $current = $this->all();
        $existing = $this->first();
        $existingId = is_array($existing) && isset($existing['id']) ? (string) $existing['id'] : null;

        $payload = array_replace($current, [
            'redirect_uri' => trim((string) ($data['redirect_uri'] ?? $current['redirect_uri'])),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        unset(
            $payload['google_client_id'],
            $payload['google_client_secret'],
            $payload['google_client_secret_encrypted']
        );

        if ($existingId !== null && $existingId !== '') {
            return (bool) $this->settings->update($existingId, $payload);
        }

        return is_array($this->settings->create($payload));
    }

    public function saveSelectedForm(array $form): bool
    {
        $current = $this->all();

        return $this->saveRaw(array_replace($current, [
            'selected_form_id' => (string) ($form['id'] ?? ''),
            'selected_form_title' => (string) ($form['name'] ?? $form['title'] ?? ''),
            'selected_form_url' => (string) ($form['webViewLink'] ?? ''),
            'selected_form_updated_at' => date('Y-m-d H:i:s'),
        ]));
    }

    public function clearSelectedForm(): bool
    {
        $current = $this->all();

        unset(
            $current['selected_form_id'],
            $current['selected_form_title'],
            $current['selected_form_url'],
            $current['selected_form_updated_at']
        );

        return $this->saveRaw($current);
    }

    public function oauthStatus(): string
    {
        $settings = $this->all();
        $hasClientId = trim((string) ($settings['google_client_id'] ?? '')) !== '';
        $hasClientSecret = trim((string) ($settings['google_client_secret'] ?? '')) !== '';

        if ($hasClientId && $hasClientSecret) {
            return 'configured';
        }

        if ($hasClientId || $hasClientSecret) {
            return 'partial';
        }

        return 'missing';
    }

    public function isConfigured(): bool
    {
        return $this->oauthStatus() === 'configured';
    }

    public function clientId(): string
    {
        return trim((string) ($this->all()['google_client_id'] ?? ''));
    }

    public function clientSecret(): string
    {
        return trim((string) ($this->all()['google_client_secret'] ?? ''));
    }

    public function redirectUri(string $fallback): string
    {
        // GoogleForms: the OAuth callback URI is generated from the current site URL.
        // This avoids stale local values when the site moves between local, staging and production.
        return $fallback;
    }

    private function envValue(string $key): string
    {
        $value = trim((string) env($key, ''));

        if ($value === '') {
            return '';
        }

        return trim($this->secretBox->decrypt($value));
    }

    /** @return array<string, mixed>|null */
    private function first(): ?array
    {
        $items = $this->settings->all();

        if (!is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (is_array($item)) {
                return $item;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $payload */
    private function saveRaw(array $payload): bool
    {
        $existing = $this->first();
        $existingId = is_array($existing) && isset($existing['id']) ? (string) $existing['id'] : null;

        unset($payload['google_client_secret']);

        if ($existingId !== null && $existingId !== '') {
            return (bool) $this->settings->update($existingId, $payload);
        }

        return is_array($this->settings->create($payload));
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'google_client_id' => $this->envValue('GOOGLE_OAUTH_CLIENT_ID'),
            'google_client_secret' => $this->envValue('GOOGLE_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => '',
            'selected_form_id' => '',
            'selected_form_title' => '',
            'selected_form_url' => '',
            'updated_at' => null,
        ];
    }
}
