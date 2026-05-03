<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\PermissionService;
use App\Services\SalonRepository;

abstract class BaseController
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    protected function repo(): SalonRepository
    {
        return app('repository');
    }

    protected function auth(): AuthService
    {
        return app('auth');
    }

    protected function permissions(): PermissionService
    {
        return app('permission');
    }

    protected function internalUser(): array
    {
        $user = $this->auth()->user('internal');

        if ($user === null) {
            $this->redirect('/login');
        }

        return $user;
    }

    protected function customerUser(): array
    {
        $user = $this->auth()->user('customer');

        if ($user === null) {
            $this->redirect('/customer/login');
        }

        return $user;
    }

    protected function authorize(string $permission): void
    {
        if ($this->permissions()->can($permission, 'internal')) {
            return;
        }

        http_response_code(403);
        echo View::render('pages/admin/forbidden', [
            'title' => 'Akses Ditolak',
            'page' => 'forbidden',
            'message' => 'Hak akses Anda belum diaktifkan oleh admin.',
        ], 'guest');
        exit;
    }

    protected function internalPage(string $page, string $title, array $data = []): void
    {
        $user = $this->internalUser();
        $modules = $this->permissions()->visibleModules(config('internal_nav'), 'internal');

        $this->view($page, array_merge($data, [
            'title' => $title,
            'pageTitle' => $title,
            'page' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            'currentUser' => $user,
            'sidebarModules' => $modules,
            'notifications' => $this->repo()->getNotifications(),
            'success' => flash('success'),
            'error' => flash('error'),
        ]));
    }
}
