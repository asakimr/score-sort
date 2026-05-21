<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\TeamDrawService;

$basePlayers = [
    ['id' => 303, 'name' => 'Emival', 'rating' => 3.5, 'is_goalkeeper' => 1],
    ['id' => 404, 'name' => 'Iago Santos', 'rating' => 4.5, 'is_goalkeeper' => 0],
    ['id' => 505, 'name' => 'José Almir', 'rating' => 4.0, 'is_goalkeeper' => 0],
    ['id' => 606, 'name' => 'Guilherme Theo', 'rating' => 2.5, 'is_goalkeeper' => 0],
    ['id' => 707, 'name' => 'Ilmar', 'rating' => 4.0, 'is_goalkeeper' => 0],
    ['id' => 808, 'name' => 'Jéferson Alcantara', 'rating' => 4.0, 'is_goalkeeper' => 0],
];

$scenarios = [
    'nome solicitado' => [
        ['id' => 101, 'name' => 'Paulo Gabriel', 'rating' => 2.5, 'is_goalkeeper' => 0],
        ['id' => 202, 'name' => 'Jonatham Zabham', 'rating' => 3.0, 'is_goalkeeper' => 1],
    ],
    'grafia atual do banco local' => [
        ['id' => 9991, 'name' => 'Paulo Gabriel', 'rating' => 2.5, 'is_goalkeeper' => 0],
        ['id' => 9992, 'name' => 'Jonatham Bazam', 'rating' => 3.0, 'is_goalkeeper' => 1],
    ],
];

$service = new TeamDrawService();
$iterations = 200;

foreach ($scenarios as $scenarioName => $scenarioPlayers) {
    $players = array_merge($scenarioPlayers, $basePlayers);

    foreach (['balanced', 'random'] as $drawMode) {
        for ($iteration = 1; $iteration <= $iterations; $iteration++) {
            $summary = $service->generate($players, 8, $drawMode, true);

            foreach ($summary['teams'] as $team) {
                $names = array_map(
                    static fn (array $player): string => normalizeTestPlayerName((string) $player['name']),
                    $team['players']
                );

                if (hasAny($names, ['paulo gabriel']) && hasAny($names, ['jonatham zabham', 'jonatham bazam'])) {
                    fwrite(
                        STDERR,
                        sprintf(
                            "Falha: Paulo Gabriel e Jonatham cairam no mesmo time no cenario %s, modo %s, iteracao %d.\n",
                            $scenarioName,
                            $drawMode,
                            $iteration
                        )
                    );
                    exit(1);
                }
            }
        }
    }
}

echo "OK: Paulo Gabriel e Jonatham ficaram separados em {$iterations} iteracoes por modo/cenario, usando nomes e IDs variaveis.\n";

function hasAny(array $names, array $expectedNames): bool
{
    foreach ($expectedNames as $expectedName) {
        if (in_array($expectedName, $names, true)) {
            return true;
        }
    }

    return false;
}

function normalizeTestPlayerName(string $name): string
{
    $normalized = trim($name);
    if (function_exists('iconv')) {
        $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if ($withoutAccents !== false) {
            $normalized = $withoutAccents;
        }
    }

    $normalized = strtolower($normalized);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

    return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
}
