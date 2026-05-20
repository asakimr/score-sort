<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\UserRepository;

final class Auth
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function user(): ?array
    {
        $userId = isset($_SESSION['auth_user_id']) ? (int) $_SESSION['auth_user_id'] : 0;

        if ($userId <= 0) {
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            $this->logout();

            return null;
        }

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) $user['role'],
        ];

        return $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isSupervisor(): bool
    {
        $user = $this->user();

        return $user !== null && ($user['role'] ?? 'viewer') === 'supervisor';
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if ($user === null) {
            return false;
        }

        if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int) $user['id'];
        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) $user['role'],
        ];

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['auth_user_id'], $_SESSION['auth_user']);
        session_regenerate_id(true);
    }
}
