<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envPath = $root . '/.env';
$env = file_exists($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_TYPED) : [];
$databasePath = $root . '/' . ($env['DB_DATABASE'] ?? 'storage/database.sqlite');

if (!is_dir(dirname($databasePath))) {
    mkdir(dirname($databasePath), 0777, true);
}

if (!file_exists($databasePath)) {
    touch($databasePath);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename TEXT PRIMARY KEY,
        applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);

$applied = $pdo->query('SELECT filename FROM schema_migrations');
$appliedFiles = $applied ? $applied->fetchAll(PDO::FETCH_COLUMN) : [];
$appliedMap = array_fill_keys($appliedFiles, true);

$files = glob($root . '/database/migrations/*.sql');
sort($files);

$markApplied = $pdo->prepare('INSERT INTO schema_migrations (filename, applied_at) VALUES (:filename, CURRENT_TIMESTAMP)');

foreach ($files as $file) {
    $filename = basename($file);

    if (isset($appliedMap[$filename])) {
        echo 'Ignorado (já aplicado): ' . $filename . PHP_EOL;
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Falha ao ler migration: ' . $file);
    }

    $pdo->beginTransaction();

    try {
        $pdo->exec($sql);
        $markApplied->execute(['filename' => $filename]);
        $pdo->commit();
        echo 'Migrado: ' . $filename . PHP_EOL;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

echo 'Banco pronto em ' . $databasePath . PHP_EOL;
