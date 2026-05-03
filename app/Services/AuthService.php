<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    public function __construct(private readonly SalonRepository $repository)
    {
    }

    public function attempt(string $email, string $password, string $portal = 'internal'): bool
    {
        $user = $this->repository->findUserByEmail($email, $portal);

        if ($user === null || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['auth'][$portal] = $user['id'];

        return true;
    }

    public function user(string $portal = 'internal'): ?array
    {
        $userId = $_SESSION['auth'][$portal] ?? null;

        if ($userId === null) {
            return null;
        }

        return $this->repository->findUserById((int) $userId);
    }

    public function check(string $portal = 'internal'): bool
    {
        return $this->user($portal) !== null;
    }

    public function logout(string $portal = 'internal'): void
    {
        unset($_SESSION['auth'][$portal]);
    }
}
