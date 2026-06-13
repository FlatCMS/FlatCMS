<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('view')) {
    function view(string $template, array $data = [], ?string $layout = null): void
    {
        $view = new \App\Core\View();
        $view->render($template, $data, $layout);
    }
}

if (!function_exists('partial')) {
    function partial(string $name, array $data = []): void
    {
        $view = \App\Core\View::getInstance();
        if ($view) {
            $view->include($name, $data);
        }
    }
}

if (!function_exists('component')) {
    function component(string $name, array $data = []): void
    {
        $view = \App\Core\View::getInstance();
        if ($view) {
            $view->component($name, $data);
        }
    }
}

if (!function_exists('section')) {
    function section(string $name): void
    {
        $view = \App\Core\View::getInstance();
        if ($view) {
            $view->section($name);
        }
    }
}

if (!function_exists('end_section')) {
    function end_section(): void
    {
        $view = \App\Core\View::getInstance();
        if ($view) {
            $view->endSection();
        }
    }
}

if (!function_exists('yield_section')) {
    function yield_section(string $name, string $default = ''): string
    {
        $view = \App\Core\View::getInstance();
        return $view ? $view->yield($name, $default) : $default;
    }
}

if (!function_exists('extends_layout')) {
    function extends_layout(string $layout): void
    {
        $view = \App\Core\View::getInstance();
        if ($view) {
            $view->extends($layout);
        }
    }
}

if (!function_exists('active_class')) {
    function active_class(string $path, string $class = 'active'): string
    {
        $currentPath = rtrim(app()->request()->uri(), '/') ?: '/';
        $normalizedPath = rtrim($path, '/') ?: '/';

        if ($normalizedPath === '/admin') {
            if ($currentPath === '/admin' || $currentPath === '/admin/dashboard') {
                return $class;
            }

            return '';
        }

        // Exact match or starts with (for sections)
        if ($currentPath === $normalizedPath || str_starts_with($currentPath, $normalizedPath . '/')) {
            return $class;
        }

        return '';
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path): bool
    {
        return active_class($path) !== '';
    }
}

if (!function_exists('selected')) {
    function selected(mixed $value, mixed $current): string
    {
        return $value == $current ? 'selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked(mixed $value, mixed $current = true): string
    {
        if (is_array($current)) {
            return in_array($value, $current) ? 'checked' : '';
        }
        return $value == $current ? 'checked' : '';
    }
}

if (!function_exists('disabled')) {
    function disabled(bool $condition): string
    {
        return $condition ? 'disabled' : '';
    }
}

if (!function_exists('readonly')) {
    function readonly(bool $condition): string
    {
        return $condition ? 'readonly' : '';
    }
}

if (!function_exists('required_attr')) {
    function required_attr(bool $condition = true): string
    {
        return $condition ? 'required' : '';
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool
    {
        $view = \App\Core\View::getInstance();
        $errors = $view ? $view->get('errors', []) : session()->getFlash('errors', []);
        return isset($errors[$field]);
    }
}

if (!function_exists('error_class')) {
    function error_class(string $field, string $class = 'is-invalid'): string
    {
        return has_error($field) ? $class : '';
    }
}

if (!function_exists('error_message')) {
    function error_message(string $field): string
    {
        $view = \App\Core\View::getInstance();
        $errors = $view ? $view->get('errors', []) : session()->getFlash('errors', []);
        if (isset($errors[$field]) && is_array($errors[$field])) {
            return $errors[$field][0] ?? '';
        }
        return '';
    }
}

if (!function_exists('pagination')) {
    function pagination(array $paginated, string $baseUrl): string
    {
        $totalPages = max(1, (int) ($paginated['total_pages'] ?? 1));
        $currentPage = max(1, min((int) ($paginated['current_page'] ?? 1), $totalPages));

        if ($totalPages <= 1) {
            return '';
        }

        $html = '';

        $buildPageUrl = static function (int $pageNumber) use ($baseUrl): string {
            $parts = parse_url($baseUrl);
            $path = (string) ($parts['path'] ?? $baseUrl);

            $params = [];
            $query = (string) ($parts['query'] ?? '');
            if ($query !== '') {
                parse_str($query, $params);
            }

            $params['page'] = $pageNumber;
            $queryString = http_build_query($params);

            if ($queryString === '') {
                return $path;
            }

            return $path . '?' . $queryString;
        };

        $pagesToShow = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            if (
                $i === 1
                || $i === $totalPages
                || abs($i - $currentPage) <= 1
            ) {
                $pagesToShow[] = $i;
            }
        }

        $renderButton = static function (
            string $label,
            ?string $href = null,
            bool $active = false,
            bool $disabled = false,
            bool $isCurrent = false
        ): string {
            $classes = ['btn', $active ? 'btn-primary' : 'btn-secondary', 'pagination-btn'];
            if ($active) {
                $classes[] = 'active';
            }
            if ($disabled) {
                $classes[] = 'is-disabled';
            }

            $classAttr = implode(' ', $classes);
            $escapedLabel = e($label);

            if ($href !== null && !$disabled && !$active) {
                return '<a class="' . $classAttr . '" href="' . e($href) . '">' . $escapedLabel . '</a>';
            }

            $attrs = 'class="' . $classAttr . '"';
            if ($isCurrent) {
                $attrs .= ' aria-current="page"';
            }
            if ($disabled) {
                $attrs .= ' aria-disabled="true"';
            }

            return '<span ' . $attrs . '>' . $escapedLabel . '</span>';
        };

        // Previous
        $html .= '<nav class="pagination-wrapper" aria-label="Pagination"><div class="pagination">';
        if ($currentPage > 1) {
            $prevUrl = $buildPageUrl($currentPage - 1);
            $html .= $renderButton('←', $prevUrl);
        } else {
            $html .= $renderButton('←', null, false, true);
        }

        $lastRendered = 0;
        foreach ($pagesToShow as $pageNumber) {
            if ($lastRendered !== 0 && $pageNumber > $lastRendered + 1) {
                $html .= '<span class="pagination-ellipsis" aria-hidden="true">…</span>';
            }

            if ($pageNumber === $currentPage) {
                $html .= $renderButton((string) $pageNumber, null, true, false, true);
            } else {
                $html .= $renderButton((string) $pageNumber, $buildPageUrl($pageNumber));
            }

            $lastRendered = $pageNumber;
        }

        // Next
        if (!empty($paginated['has_more']) && $currentPage < $totalPages) {
            $nextUrl = $buildPageUrl($currentPage + 1);
            $html .= $renderButton('→', $nextUrl);
        } else {
            $html .= $renderButton('→', null, false, true);
        }

        $html .= '</div></nav>';
        
        return $html;
    }
}
