<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('validate')) {
    function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $error = apply_validation_rule($field, $value, $rule, $data);
                if ($error) {
                    $errors[$field][] = $error;
                    break;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'data' => $validated];
    }
}

if (!function_exists('apply_validation_rule')) {
    function apply_validation_rule(string $field, mixed $value, string $rule, array $data): ?string
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => empty($value) && $value !== '0' ? __('validation.required', 'Core', ['field' => $label]) : null,
            'email' => !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) ? __('validation.email', 'Core', ['field' => $label]) : null,
            'min' => strlen((string) $value) < (int) ($params[0] ?? 0) ? __('validation.min', 'Core', ['field' => $label, 'min' => (string) ($params[0] ?? 0)]) : null,
            'max' => strlen((string) $value) > (int) ($params[0] ?? 255) ? __('validation.max', 'Core', ['field' => $label, 'max' => (string) ($params[0] ?? 255)]) : null,
            'numeric' => !empty($value) && !is_numeric($value) ? __('validation.numeric', 'Core', ['field' => $label]) : null,
            'url' => !empty($value) && !filter_var($value, FILTER_VALIDATE_URL) ? __('validation.url', 'Core', ['field' => $label]) : null,
            'confirmed' => $value !== ($data[$field . '_confirmation'] ?? null) ? __('validation.confirmed', 'Core', ['field' => $label]) : null,
            'slug' => !empty($value) && !preg_match('/^[a-z0-9-]+$/', $value) ? __('validation.slug', 'Core', ['field' => $label]) : null,
            default => null
        };
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_valid_url')) {
    function is_valid_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_valid_slug')) {
    function is_valid_slug(string $slug): bool
    {
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_html')) {
    function sanitize_html(?string $value): string
    {
        $allowed = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code>';
        return strip_tags($value ?? '', $allowed);
    }
}
