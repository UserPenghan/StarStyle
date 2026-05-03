<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

if (!is_dir(BASE_PATH . '/storage/cache')) {
    mkdir(BASE_PATH . '/storage/cache', 0777, true);
}

session_save_path(BASE_PATH . '/storage/cache');
session_start();

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$config = require BASE_PATH . '/config/app.php';
$permissions = require BASE_PATH . '/config/permissions.php';

$repository = new App\Services\SalonRepository($config, $permissions);
$db = new App\Services\Database($config);
$auth = new App\Services\AuthService($repository);
$permissionService = new App\Services\PermissionService($repository, $auth);

$GLOBALS['starstyle'] = [
    'config' => $config,
    'permissions' => $permissions,
    'repository' => $repository,
    'db' => $db,
    'auth' => $auth,
    'permission' => $permissionService,
];

function app(?string $key = null): mixed
{
    $app = $GLOBALS['starstyle'] ?? [];

    return $key === null ? $app : ($app[$key] ?? null);
}

function config(?string $key = null, mixed $default = null): mixed
{
    $config = app('config') ?? [];

    if ($key === null) {
        return $config;
    }

    $value = $config;

    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url = '/assets/' . $path;
    $file = __DIR__ . '/public/assets/' . $path;

    return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
}

function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');

    return $path === '//' ? '/' : $path;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('CSRF token tidak valid.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function remember_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function auth(): App\Services\AuthService
{
    return app('auth');
}

function money(float|int $value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function active_path(string $path, string $currentPath): bool
{
    return $path === '/'
        ? $currentPath === '/'
        : str_starts_with($currentPath, $path);
}
