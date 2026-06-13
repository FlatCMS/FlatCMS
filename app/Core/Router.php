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

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $middlewareGroups = [];
    private array $currentMiddleware = [];

    public function get(string $uri, string|array|callable $action): self
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, string|array|callable $action): self
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, string|array|callable $action): self
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, string|array|callable $action): self
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function any(string $uri, string|array|callable $action): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    public function group(array $options, callable $callback): void
    {
        $previousMiddleware = $this->currentMiddleware;
        
        if (isset($options['middleware'])) {
            $middleware = (array) $options['middleware'];
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        }

        $prefix = $options['prefix'] ?? '';
        
        // Preserve the original path before route normalization
        $this->currentPrefix = $prefix;
        
        $callback($this);
        
        $this->currentMiddleware = $previousMiddleware;
        $this->currentPrefix = '';
    }

    private string $currentPrefix = '';

    private function addRoute(string $method, string $uri, string|array|callable $action): self
    {
        // Apply prefix
        if ($this->currentPrefix) {
            $uri = '/' . trim($this->currentPrefix, '/') . '/' . trim($uri, '/');
        }
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $route = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $this->currentMiddleware,
            'pattern' => $this->compilePattern($uri),
        ];

        $this->routes[] = $route;
        
        return $this;
    }

    public function name(string $name): self
    {
        $lastRoute = array_key_last($this->routes);
        if ($lastRoute !== null) {
            $this->routes[$lastRoute]['name'] = $name;
            $this->namedRoutes[$name] = &$this->routes[$lastRoute];
        }
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $lastRoute = array_key_last($this->routes);
        if ($lastRoute !== null) {
            $middleware = (array) $middleware;
            $this->routes[$lastRoute]['middleware'] = array_merge(
                $this->routes[$lastRoute]['middleware'],
                $middleware
            );
        }
        return $this;
    }

    private function compilePattern(string $uri): string
    {
        // Convert {param*} to named capture groups that accept nested path segments
        $pattern = preg_replace('/\{([a-zA-Z_]+)\*\}/', '(?P<$1>.+)', $uri);
        // Convert {param*?} to optional named capture groups that accept nested path segments
        $pattern = preg_replace('/\{([a-zA-Z_]+)\*\?\}/', '(?P<$1>.*)?', $pattern ?? $uri);
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern ?? $uri);
        // Convert {param?} to optional named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\?\}/', '(?P<$1>[^/]*)?', $pattern ?? $uri);
        return '#^' . ($pattern ?? $uri) . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // Run middleware
                if (!$this->runMiddleware($route['middleware'], $request)) {
                    return;
                }

                // Execute action
                $this->executeAction($route['action'], $request, $params);
                return;
            }
        }

        // 404 Not Found
        $this->handleNotFound($request);
    }

    private function runMiddleware(array $middleware, Request $request): bool
    {
        foreach ($middleware as $m) {
            $middlewareClass = $this->resolveMiddleware($m);
            if ($middlewareClass) {
                $instance = new $middlewareClass();
                if (method_exists($instance, 'handle')) {
                    $result = $instance->handle($request);
                    if ($result === false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function resolveMiddleware(string $name): ?string
    {
        $map = [
            'auth' => \App\Modules\Auth\Middleware\AuthMiddleware::class,
            'guest' => \App\Modules\Auth\Middleware\GuestMiddleware::class,
        ];

        return $map[$name] ?? null;
    }

    private function executeAction(string|array|callable $action, Request $request, array $params): void
    {
        if (is_callable($action)) {
            call_user_func_array($action, array_values($params));
            return;
        }

        if (is_string($action)) {
            // Format: "Controller@method"
            [$controller, $method] = explode('@', $action);
        } elseif (is_array($action)) {
            [$controller, $method] = $action;
        }

        if (!class_exists($controller)) {
            throw new \RuntimeException("Controller '{$controller}' not found.");
        }

        $instance = new $controller();
        
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method '{$method}' not found in '{$controller}'.");
        }

        call_user_func_array([$instance, $method], array_values($params));
    }

    private function handleNotFound(Request $request): void
    {
        http_response_code(404);
        
        $resourcePath = BASE_PATH . '/resources/views/errors/404.php';
        $corePath = BASE_PATH . '/app/Modules/Core/Views/errors/404.php';

        if (file_exists($resourcePath)) {
            include $resourcePath;
        } elseif (file_exists($corePath)) {
            include $corePath;
        } else {
            echo '<h1>' . e(__('error.not_found', 'Core')) . '</h1>';
        }
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route '{$name}' not found.");
        }

        $uri = $this->namedRoutes[$name]['uri'];
        
        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '*}', (string) $value, $uri);
            $uri = str_replace('{' . $key . '*?}', (string) $value, $uri);
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
            $uri = str_replace('{' . $key . '?}', (string) $value, $uri);
        }

        // Remove remaining optional params
        $uri = preg_replace('/\{[a-zA-Z_]+\*\?\}/', '', $uri);
        $uri = preg_replace('/\{[a-zA-Z_]+\?\}/', '', $uri);

        return url($uri);
    }
}
