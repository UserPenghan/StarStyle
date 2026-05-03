<?php

declare(strict_types=1);

namespace App\Services;

final class PermissionService
{
    public function __construct(
        private readonly SalonRepository $repository,
        private readonly AuthService $auth,
    ) {
    }

    public function can(string $permission, string $portal = 'internal'): bool
    {
        $user = $this->auth->user($portal);

        if ($user === null) {
            return false;
        }

        if ($user['role'] === 'admin') {
            return true;
        }

        if ($user['role'] === 'staff') {
            return in_array($permission, $this->repository->getPermissionsForUser($user), true);
        }

        return $permission === 'customer.portal';
    }

    public function visibleModules(array $modules, string $portal = 'internal'): array
    {
        $user = $this->auth->user($portal);

        if ($user === null) {
            return [];
        }

        if ($user['role'] === 'admin') {
            return $modules;
        }

        return array_values(array_filter($modules, fn (array $module): bool => $this->can($module['permission'], $portal)));
    }
}
