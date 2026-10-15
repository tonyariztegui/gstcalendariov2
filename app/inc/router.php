<?php

if (!function_exists('app_routes')) {
    function app_routes(): array
    {
        static $routes = null;
        if ($routes === null) {
            $path = __DIR__ . '/../config/routes.php';
            $loaded = is_readable($path) ? require $path : [];
            $routes = is_array($loaded) ? $loaded : [];
        }

        return $routes;
    }
}

if (!function_exists('app_route')) {
    function app_route(string $scope, string $name): ?array
    {
        $routes = app_routes();
        $aliases = $routes[$scope]['aliases'] ?? [];
        if (is_array($aliases) && isset($aliases[$name])) {
            $name = (string) $aliases[$name];
        }
        $scopeRoutes = $routes[$scope]['routes'] ?? [];
        if (!is_array($scopeRoutes) || !isset($scopeRoutes[$name]) || !is_array($scopeRoutes[$name])) {
            return null;
        }

        return $scopeRoutes[$name];
    }
}

if (!function_exists('app_normalize_route_name')) {
    function app_normalize_route_name($value): string
    {
        $route = trim((string) $value);
        $route = preg_replace('/\.php$/i', '', $route);
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $route);
    }
}

if (!function_exists('app_route_view_path')) {
    function app_route_view_path(string $scope, string $name, string $baseDir): ?string
    {
        $route = app_route($scope, $name);
        $view = is_array($route) ? (string) ($route['view'] ?? '') : '';
        if ($view === '' || preg_match('#^[A-Za-z]:|^/#', $view)) {
            return null;
        }

        $baseReal = realpath($baseDir);
        if ($baseReal === false) {
            return null;
        }

        $path = $baseReal . DIRECTORY_SEPARATOR . $view;
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        $allowedRoots = [$baseReal];
        if ($scope === 'manager') {
            $ajaxRoot = realpath($baseReal . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ajax');
            if ($ajaxRoot !== false) {
                $allowedRoots[] = $ajaxRoot;
            }
        }

        foreach ($allowedRoots as $root) {
            if (strpos($realPath, rtrim($root, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR) === 0) {
                return $realPath;
            }
        }

        return null;
    }
}

if (!function_exists('app_manager_route_from_request')) {
    function app_manager_route_from_request(bool $loggedIn): string
    {
        $routes = app_routes();
        $manager = $routes['manager'] ?? [];
        $default = (string) ($manager['default'] ?? 'homepage');

        if ($loggedIn && isset($_GET['pg'])) {
            return app_normalize_route_name($_GET['pg']);
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $legacy = is_array($manager['legacy_uri'] ?? null) ? $manager['legacy_uri'] : [];
        foreach ($legacy as $needle => $routeName) {
            if ($needle !== '' && stripos($uri, (string) $needle) !== false) {
                return (string) $routeName;
            }
        }

        return $loggedIn ? $default : 'not_logged_in';
    }
}

if (!function_exists('app_public_route_from_request')) {
    function app_public_route_from_request(): ?string
    {
        if (isset($_GET['route'])) {
            $route = app_normalize_route_name($_GET['route']);
            return $route !== '' ? $route : null;
        }

        return null;
    }
}
