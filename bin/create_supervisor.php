#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Support\Database;

require __DIR__ . '/../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script deve ser executado via CLI.\n");
    exit(1);
}

$username = trim((string) ($argv[1] ?? ''));
$password = (string) ($argv[2] ?? '');

if ($username === '' || $password === '') {
    fwrite(STDERR, "Uso: php bin/create_supervisor.php <usuario> <senha>\n");
    exit(1);
}

$envPath = dirname(__DIR__) . '/.env';
$env = file_exists($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_TYPED) : [];
$databasePath = dirname(__DIR__) . '/' . ($env['DB_DATABASE'] ?? 'storage/database.sqlite');

$database = new Database($databasePath);
$pdo = $database->pdo();

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT \'viewer\',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)');
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)');

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$select = $pdo->prepare('SELECT id FROM users WHERE lower(username) = lower(:username) LIMIT 1');
$select->execute(['username' => $username]);
$userId = $select->fetchColumn();

if ($userId !== false) {
    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash, role = :role, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $update->execute([
        'id' => (int) $userId,
        'password_hash' => $passwordHash,
        'role' => 'supervisor',
    ]);

    fwrite(STDOUT, "Supervisor atualizado com sucesso: {$username}\n");
    exit(0);
}

$insert = $pdo->prepare('INSERT INTO users (username, password_hash, role, created_at, updated_at) VALUES (:username, :password_hash, :role, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
$insert->execute([
    'username' => $username,
    'password_hash' => $passwordHash,
    'role' => 'supervisor',
]);

fwrite(STDOUT, "Supervisor criado com sucesso: {$username}\n");
