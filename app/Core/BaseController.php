<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    protected Request $request;
    protected Response $response;
    protected Session $session;
    protected View $view;

    public function __construct()
    {
        $this->request = app()->request();
        $this->response = new Response();
        $this->session = app()->session();
        $this->view = new View();
    }

    protected function render(string $template, array $data = [], ?string $layout = null): void
    {
        $this->view->render($template, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        $this->response->redirect($url, $status);
    }

    protected function back(): void
    {
        $this->response->back();
    }

    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($fieldRules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->applyRule($field, $value, $rule, $params, $data);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            $this->session->flash('old', $data);
            $this->back();
        }

        return $validated;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params, array $data): ?string
    {
        $label = str_replace('_', ' ', $field);

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return __('validation.required', 'Core', ['field' => $label]);
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return __('validation.email', 'Core', ['field' => $label]);
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (strlen((string) $value) < $min) {
                    return __('validation.min', 'Core', ['field' => $label, 'min' => $min]);
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 255);
                if (strlen((string) $value) > $max) {
                    return __('validation.max', 'Core', ['field' => $label, 'max' => $max]);
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($data[$confirmField] ?? null)) {
                    return __('validation.confirmed', 'Core', ['field' => $label]);
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return __('validation.numeric', 'Core', ['field' => $label]);
                }
                break;

            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    return __('validation.alpha', 'Core', ['field' => $label]);
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum($value)) {
                    return __('validation.alphanumeric', 'Core', ['field' => $label]);
                }
                break;

            case 'slug':
                if (!empty($value) && !preg_match('/^[a-z0-9-]+$/', $value)) {
                    return __('validation.slug', 'Core', ['field' => $label]);
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return __('validation.url', 'Core', ['field' => $label]);
                }
                break;
        }

        return null;
    }

    protected function authorize(string $permission): bool
    {
        $user = $this->session->get('user');

        if (!$user) {
            $this->redirect(url('/login'));
            return false;
        }

        $role = $user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER;

        if (!\App\Modules\Auth\Services\RoleService::hasPermission($role, $permission)) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));

            if (\App\Modules\Auth\Services\RoleService::hasPermission($role, 'profile.view')) {
                $this->redirect(url(\App\Modules\Auth\Services\RoleService::getLoginRedirect((string) $role)));
                return false;
            }

            if (\App\Modules\Auth\Services\RoleService::canAccessAdmin((string) $role)) {
                $this->redirect(url('/admin'));
                return false;
            }

            $this->redirect(url('/'));
            return false;
        }

        return true;
    }

    protected function csrfToken(): string
    {
        return $this->session->token();
    }

    protected function verifyCsrf(): bool
    {
        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        
        if (!$token || !$this->session->verifyToken($token)) {
            $this->session->flash('error', __('error.csrf', 'Core'));
            $this->back();
            return false;
        }

        return true;
    }
}
