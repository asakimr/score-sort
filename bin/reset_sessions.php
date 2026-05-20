<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envPath = $root . '/.env';
$env = file_exists($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_TYPED) : [];
$databasePath = $root . '/' . ($env['DB_DATABASE'] ?? 'storage/database.sqlite');

if (!file_exists($databasePath)) {
    throw new RuntimeException('Banco não encontrado em ' . $databasePath);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

$tables = [
    'match_events',
    'match_transfers',
    'matches',
    'team_players',
    'teams',
    'session_attendances',
    'sessions',
];

$counts = [];
foreach ($tables as $table) {
    $statement = $pdo->query('SELECT COUNT(*) FROM ' . $table);
    $counts[$table] = (int) ($statement?->fetchColumn() ?: 0);
}

$pdo->beginTransaction();

try {
    foreach ($tables as $table) {
        $pdo->exec('DELETE FROM ' . $table);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}

echo 'Reset concluído. Cadastro de jogadores preservado.' . PHP_EOL;
foreach ($counts as $table => $count) {
    echo '- ' . $table . ': ' . $count . ' registro(s) removido(s)' . PHP_EOL;
}
