<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user !== false ? $user : null;
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE lower(username) = lower(:username) LIMIT 1');
        $statement->execute(['username' => trim($username)]);
        $user = $statement->fetch();

        return $user !== false ? $user : null;
    }

    public function create(string $username, string $passwordHash, string $role = 'viewer'): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, role, created_at, updated_at) VALUES (:username, :password_hash, :role, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'username' => trim($username),
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePasswordAndRole(int $id, string $passwordHash, string $role): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash, role = :role, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);
    }
}
