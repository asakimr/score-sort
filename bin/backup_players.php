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

$players = $pdo->query('SELECT id, name, rating, is_goalkeeper, is_active, created_at, updated_at FROM players ORDER BY id ASC')
    ?->fetchAll() ?: [];

$backupDir = $root . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$timestamp = date('Ymd-His');
$backupPath = $backupDir . '/players-backup-' . $timestamp . '.json';

$payload = [
    'exported_at' => date(DATE_ATOM),
    'source' => str_replace($root . '/', '', $databasePath),
    'player_count' => count($players),
    'players' => $players,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    throw new RuntimeException('Falha ao serializar backup dos jogadores.');
}

file_put_contents($backupPath, $json);

echo 'Backup criado em: ' . $backupPath . PHP_EOL;
echo 'Jogadores exportados: ' . count($players) . PHP_EOL;
