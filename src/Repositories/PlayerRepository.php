<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PlayerRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT id, name, rating, is_goalkeeper, is_active FROM players ORDER BY is_active DESC, name ASC');

        return $statement ? $statement->fetchAll() : [];
    }

    public function active(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, rating, is_goalkeeper, is_active
             FROM players
             WHERE is_active = 1
             ORDER BY is_goalkeeper DESC, rating DESC, name ASC'
        );

        return $statement ? $statement->fetchAll() : [];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, rating, is_goalkeeper, is_active FROM players WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $player = $statement->fetch();

        return $player ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO players (name, rating, is_goalkeeper, is_active, created_at, updated_at)
             VALUES (:name, :rating, :is_goalkeeper, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'name' => $data['name'],
            'rating' => $data['rating'],
            'is_goalkeeper' => $data['is_goalkeeper'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE players
             SET name = :name,
                 rating = :rating,
                 is_goalkeeper = :is_goalkeeper,
                 is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'rating' => $data['rating'],
            'is_goalkeeper' => $data['is_goalkeeper'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function toggleActive(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE players
             SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $statement->execute(['id' => $id]);
    }

    public function countActive(): int
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM players WHERE is_active = 1');

        return (int) ($statement?->fetchColumn() ?: 0);
    }
}
