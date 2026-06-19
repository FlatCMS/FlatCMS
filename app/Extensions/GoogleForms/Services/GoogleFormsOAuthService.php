<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Services;

use App\Core\I18n;
use App\Core\FlatFile;
use RuntimeException;

final class GoogleFormsOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    private FlatFile $oauth;
    private GoogleFormsSettingsService $settings;
    private GoogleFormsCryptoService $crypto;

    public function __construct()
    {
        I18n::load('GoogleForms');
        $this->oauth = FlatFile::for('extensions/google-forms/oauth');
        $this->settings = new GoogleFormsSettingsService();
        $this->crypto = new GoogleFormsCryptoService();
    }

    public function authorizationUrl(string $redirectUri, string $state): string
    {
        if (!$this->settings->isConfigured()) {
            throw new RuntimeException(__('google_forms_error_oauth_not_configured', 'GoogleForms'));
        }

        $params = [
            'client_id' => $this->settings->clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account consent',
            'state' => $state,
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $clientId = $this->settings->clientId();
        $clientSecret = $this->settings->clientSecret();

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException(__('google_forms_error_oauth_credentials_missing', 'GoogleForms'));
        }

        if (str_starts_with($clientSecret, 'flatcms-secret:v1:')) {
            throw new RuntimeException(__('google_forms_error_oauth_secret_resolution_failed', 'GoogleForms'));
        }

        $token = $this->postForm(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (empty($token['access_token'])) {
            throw new RuntimeException(__('google_forms_error_oauth_access_token_missing', 'GoogleForms'));
        }

        $connection = $this->connection() ?: [];

        $connection['access_token_encrypted'] = $this->crypto->encrypt((string) $token['access_token']);
        $connection['refresh_token_encrypted'] = !empty($token['refresh_token'])
            ? $this->crypto->encrypt((string) $token['refresh_token'])
            : (string) ($connection['refresh_token_encrypted'] ?? '');

        $connection['expires_at'] = time() + max(60, (int) ($token['expires_in'] ?? 3600)) - 60;
        $connection['scope'] = (string) ($token['scope'] ?? implode(' ', $this->scopes()));
        $connection['token_type'] = (string) ($token['token_type'] ?? 'Bearer');
        $connection['updated_at'] = date('Y-m-d H:i:s');

        $this->saveConnection($connection);

        $profile = $this->userInfo();
        $connection = $this->connection() ?: $connection;
        $connection['google_account_email'] = (string) ($profile['email'] ?? '');
        $connection['google_account_name'] = (string) ($profile['name'] ?? '');
        $connection['google_account_picture'] = (string) ($profile['picture'] ?? '');
        $connection['connected_at'] = (string) ($connection['connected_at'] ?? date('Y-m-d H:i:s'));
        $connection['updated_at'] = date('Y-m-d H:i:s');

        $this->saveConnection($connection);

        return $connection;
    }

    public function isConnected(): bool
    {
        $connection = $this->connection();

        return is_array($connection)
            && !empty($connection['access_token_encrypted'])
            && !empty($connection['refresh_token_encrypted']);
    }

    public function disconnect(): void
    {
        $items = $this->oauth->all();

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (is_array($item) && isset($item['id'])) {
                $this->oauth->delete((string) $item['id']);
            }
        }
    }

    public function accessToken(): string
    {
        $connection = $this->connection();

        if (!$connection) {
            throw new RuntimeException(__('google_forms_error_oauth_account_not_connected', 'GoogleForms'));
        }

        if ((int) ($connection['expires_at'] ?? 0) <= time()) {
            $connection = $this->refresh($connection);
        }

        return $this->crypto->decrypt((string) ($connection['access_token_encrypted'] ?? ''));
    }

    public function request(string $method, string $url, ?array $payload = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken(),
            'Accept: application/json',
        ];

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        return $this->http($method, $url, $headers, $payload !== null ? json_encode($payload) : null);
    }

    public function userInfo(): array
    {
        return $this->request('GET', self::USERINFO_URL);
    }

    public function connection(): ?array
    {
        $items = $this->oauth->all();

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

    private function refresh(array $connection): array
    {
        $refreshToken = $this->crypto->decrypt((string) ($connection['refresh_token_encrypted'] ?? ''));

        if ($refreshToken === '') {
            throw new RuntimeException(__('google_forms_error_oauth_refresh_token_missing', 'GoogleForms'));
        }

        $token = $this->postForm(self::TOKEN_URL, [
            'client_id' => $this->settings->clientId(),
            'client_secret' => $this->settings->clientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (empty($token['access_token'])) {
            throw new RuntimeException(__('google_forms_error_oauth_refresh_failed', 'GoogleForms'));
        }

        $connection['access_token_encrypted'] = $this->crypto->encrypt((string) $token['access_token']);
        $connection['expires_at'] = time() + max(60, (int) ($token['expires_in'] ?? 3600)) - 60;
        $connection['updated_at'] = date('Y-m-d H:i:s');

        $this->saveConnection($connection);

        return $connection;
    }

    private function saveConnection(array $connection): void
    {
        $items = $this->oauth->all();
        $existingId = null;

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $existingId = (string) $item['id'];
                    break;
                }
            }
        }

        if ($existingId !== null && $existingId !== '') {
            $this->oauth->update($existingId, $connection);
            return;
        }

        $this->oauth->create($connection);
    }

    private function postForm(string $url, array $params): array
    {
        return $this->http('POST', $url, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], http_build_query($params));
    }

    private function http(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        $statusCode = $this->lastHttpStatusCode($http_response_header ?? []);

        if ($response === false) {
            throw new RuntimeException(__('google_forms_error_google_api_unreachable', 'GoogleForms', [
                'status' => (string) ($statusCode ?: 'unknown'),
            ]));
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException(__('google_forms_error_google_api_invalid_json', 'GoogleForms', [
                'status' => (string) ($statusCode ?: 'unknown'),
                'response' => substr($response, 0, 500),
            ]));
        }

        if (isset($data['error'])) {
            $message = is_array($data['error'])
                ? (string) ($data['error']['message'] ?? json_encode($data['error']))
                : (string) $data['error'];

            $reason = '';
            if (is_array($data['error']) && isset($data['error']['status'])) {
                $reason = ' [' . (string) $data['error']['status'] . ']';
            }

            throw new RuntimeException(__('google_forms_error_google_api_failure', 'GoogleForms', [
                'reason' => $reason,
                'status' => (string) ($statusCode ?: 'unknown'),
                'message' => $message,
            ]));
        }

        if ($statusCode >= 400) {
            throw new RuntimeException(__('google_forms_error_google_api_http', 'GoogleForms', [
                'status' => (string) $statusCode,
                'response' => substr($response, 0, 500),
            ]));
        }

        return $data;
    }

    /** @param array<int, string> $headers */
    private function lastHttpStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function scopes(): array
    {
        return [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/forms.body.readonly',
            'https://www.googleapis.com/auth/forms.responses.readonly',
        ];
    }
}
