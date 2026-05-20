<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(string $databasePath)
    {
        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($databasePath)) {
            touch($databasePath);
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
