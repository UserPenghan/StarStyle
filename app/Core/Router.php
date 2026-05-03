<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$method][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo View::render('pages/admin/not-found', [
                'title' => 'Halaman Tidak Ditemukan',
                'message' => 'Rute yang Anda cari belum tersedia di StarStyle.',
                'page' => '404',
            ], 'guest');

            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $instance = new $class();
            $instance->{$action}();

            return;
        }

        $handler();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
