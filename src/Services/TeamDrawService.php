<?php

declare(strict_types=1);

namespace App\Services;

final class TeamDrawService
{
    public function generate(array $players, int $maxPlayersPerMatch, string $drawMode = 'balanced', bool $prioritizeGoalkeepers = true): array
    {
        $normalizedPlayers = array_values(array_map(
            static fn (array $player): array => [
                'id' => (int) ($player['id'] ?? 0),
                'name' => (string) ($player['name'] ?? ''),
                'rating' => (float) ($player['rating'] ?? 0),
                'is_goalkeeper' => (int) ($player['is_goalkeeper'] ?? 0),
            ],
            $players
        ));

        $presentCount = count($normalizedPlayers);
        $playersPerTeam = max(1, (int) floor($maxPlayersPerMatch / 2));

        if ($presentCount === 0) {
            return [
                'players_per_team' => $playersPerTeam,
                'active_match_count' => 0,
                'waiting_count' => 0,
                'full_waiting_teams' => 0,
                'missing_for_next_team' => 0,
                'goalkeeper_count' => 0,
                'team_count' => 0,
                'teams' => [],
            ];
        }

        $targets = $this->buildTargets($presentCount, $playersPerTeam, $maxPlayersPerMatch);

        $teams = $drawMode === 'random'
            ? $this->generateRandomTeams($normalizedPlayers, $targets, $prioritizeGoalkeepers)
            : $this->generateBalancedTeams($normalizedPlayers, $targets, $prioritizeGoalkeepers);

        foreach ($teams as &$team) {
            usort($team['players'], static function (array $left, array $right): int {
                if ($left['is_goalkeeper'] !== $right['is_goalkeeper']) {
                    return $right['is_goalkeeper'] <=> $left['is_goalkeeper'];
                }

                if ($left['rating'] !== $right['rating']) {
                    return $right['rating'] <=> $left['rating'];
                }

                return strcmp($left['name'], $right['name']);
            });
        }
        unset($team);

        $activeMatchCount = 0;
        $waitingCount = 0;
        $fullWaitingTeams = 0;
        $missingForNextTeam = 0;

        foreach ($teams as $team) {
            $playerCount = count($team['players']);
            if ($team['status'] === 'active') {
                $activeMatchCount += $playerCount;
                continue;
            }

            $waitingCount += $playerCount;
            if ($playerCount >= $playersPerTeam) {
                $fullWaitingTeams++;
            }
        }

        if ($waitingCount > 0 && $playersPerTeam > 0) {
            $lastTeam = end($teams);
            $missingForNextTeam = max(0, $playersPerTeam - count($lastTeam['players'] ?? []));
            if (($lastTeam['status'] ?? 'active') === 'active' || count($lastTeam['players'] ?? []) >= $playersPerTeam) {
                $missingForNextTeam = 0;
            }
        }

        return [
            'players_per_team' => $playersPerTeam,
            'active_match_count' => $activeMatchCount,
            'waiting_count' => $waitingCount,
            'full_waiting_teams' => $fullWaitingTeams,
            'missing_for_next_team' => $missingForNextTeam,
            'goalkeeper_count' => count(array_filter($normalizedPlayers, static fn (array $player): bool => $player['is_goalkeeper'] === 1)),
            'team_count' => count($teams),
            'teams' => $teams,
        ];
    }

    private function generateRandomTeams(array $players, array $targets, bool $prioritizeGoalkeepers): array
    {
        $teams = $this->createEmptyTeams($targets);
        $goalkeepers = array_values(array_filter($players, static fn (array $player): bool => $player['is_goalkeeper'] === 1));
        $fieldPlayers = array_values(array_filter($players, static fn (array $player): bool => $player['is_goalkeeper'] !== 1));

        shuffle($goalkeepers);
        shuffle($fieldPlayers);

        if ($prioritizeGoalkeepers && $goalkeepers !== []) {
            $preferredIndexes = $this->preferredGoalkeeperIndexes($teams);
            shuffle($preferredIndexes);

            foreach ($preferredIndexes as $teamIndex) {
                if ($goalkeepers === [] || $this->teamIsFull($teams[$teamIndex])) {
                    continue;
                }

                $this->pushPlayer($teams[$teamIndex], array_shift($goalkeepers));
            }
        }

        $remainingPlayers = array_merge($goalkeepers, $fieldPlayers);
        shuffle($remainingPlayers);

        foreach ($remainingPlayers as $player) {
            $teamIndex = $this->nextSequentialTeamIndex($teams);
            if ($teamIndex === null) {
                break;
            }

            $this->pushPlayer($teams[$teamIndex], $player);
        }

        return $teams;
    }

    private function generateBalancedTeams(array $players, array $targets, bool $prioritizeGoalkeepers): array
    {
        $candidatePool = [];
        $attempts = 48;

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $teams = $this->createEmptyTeams($targets);
            $goalkeepers = array_values(array_filter($players, static fn (array $player): bool => $player['is_goalkeeper'] === 1));
            $fieldPlayers = array_values(array_filter($players, static fn (array $player): bool => $player['is_goalkeeper'] !== 1));

            $goalkeepers = $this->sortPlayersWithVariation($goalkeepers);
            $fieldPlayers = $this->sortPlayersWithVariation($fieldPlayers);

            if ($prioritizeGoalkeepers && $goalkeepers !== []) {
                $goalkeepers = $this->seedGoalkeepers($teams, $goalkeepers, $attempt);
            }

            $remainingPlayers = $this->interleaveVariedPools($goalkeepers, $fieldPlayers);

            foreach ($remainingPlayers as $player) {
                $teamIndex = $this->pickBestTeamIndex($teams, $player, $prioritizeGoalkeepers);
                if ($teamIndex === null) {
                    break;
                }

                $this->pushPlayer($teams[$teamIndex], $player);
            }

            $teams = $this->refineBySwaps($teams);
            $candidatePool[] = [
                'score' => $this->scoreTeams($teams, $prioritizeGoalkeepers),
                'teams' => $teams,
            ];
        }

        usort($candidatePool, static fn (array $a, array $b): int => $a['score'] <=> $b['score']);
        $top = array_slice($candidatePool, 0, min(5, count($candidatePool)));

        return $top[random_int(0, max(0, count($top) - 1))]['teams'] ?? $this->createEmptyTeams($targets);
    }

    private function createEmptyTeams(array $targets): array
    {
        return array_map(
            static fn (int $index, int $target): array => [
                'name' => 'Time ' . ($index + 1),
                'team_order' => $index + 1,
                'status' => $index < 2 ? 'active' : 'waiting',
                'target_size' => $target,
                'players' => [],
                'total_rating' => 0.0,
                'goalkeeper_count' => 0,
            ],
            array_keys($targets),
            $targets
        );
    }

    private function sortPlayersWithVariation(array $players): array
    {
        $groups = [];
        foreach ($players as $player) {
            $key = number_format((float) $player['rating'], 1, '.', '');
            $groups[$key][] = $player;
        }

        $ratings = array_map('floatval', array_keys($groups));
        rsort($ratings, SORT_NUMERIC);

        $sorted = [];
        foreach ($ratings as $rating) {
            $key = number_format($rating, 1, '.', '');
            $bucket = $groups[$key] ?? [];
            shuffle($bucket);

            if (random_int(0, 100) < 35 && count($sorted) > 0 && abs(((float) $sorted[array_key_last($sorted)]['rating']) - $rating) <= 0.5) {
                $pivot = array_pop($sorted);
                $bucket[] = $pivot;
                shuffle($bucket);
            }

            foreach ($bucket as $player) {
                $sorted[] = $player;
            }
        }

        return $sorted;
    }

    private function interleaveVariedPools(array $goalkeepers, array $fieldPlayers): array
    {
        $merged = array_merge($goalkeepers, $fieldPlayers);
        usort($merged, static function (array $left, array $right): int {
            $diff = (float) $right['rating'] <=> (float) $left['rating'];
            if ($diff !== 0) {
                return $diff;
            }

            return random_int(-1, 1);
        });

        for ($i = 0; $i < count($merged) - 1; $i++) {
            $current = (float) $merged[$i]['rating'];
            $next = (float) $merged[$i + 1]['rating'];
            if (abs($current - $next) <= 0.5 && random_int(0, 100) < 45) {
                [$merged[$i], $merged[$i + 1]] = [$merged[$i + 1], $merged[$i]];
            }
        }

        return $merged;
    }

    private function seedGoalkeepers(array &$teams, array $goalkeepers, int $attempt): array
    {
        $preferredIndexes = $this->preferredGoalkeeperIndexes($teams);
        if ($attempt % 2 === 1) {
            $active = array_slice($preferredIndexes, 0, 2);
            shuffle($active);
            $preferredIndexes = array_merge($active, array_slice($preferredIndexes, 2));
        }

        foreach ($preferredIndexes as $teamIndex) {
            if ($goalkeepers === [] || $this->teamIsFull($teams[$teamIndex])) {
                continue;
            }

            $this->pushPlayer($teams[$teamIndex], array_shift($goalkeepers));
        }

        return $goalkeepers;
    }

    private function pickBestTeamIndex(array $teams, array $player, bool $prioritizeGoalkeepers): ?int
    {
        $eligibleIndexes = [];
        foreach ($teams as $index => $team) {
            if (!$this->teamIsFull($team)) {
                $eligibleIndexes[] = $index;
            }
        }

        if ($eligibleIndexes === []) {
            return null;
        }

        $bestIndex = null;
        $bestScore = null;
        $remainingGoalkeeperSlots = $this->countGoalkeeperNeeds($teams);

        foreach ($eligibleIndexes as $index) {
            $team = $teams[$index];
            $projectedCount = count($team['players']) + 1;
            $projectedRating = (float) $team['total_rating'] + (float) $player['rating'];
            $projectedAverage = $projectedRating / max(1, $projectedCount);
            $score = $projectedRating + ($projectedAverage * 0.35);

            if ($team['status'] === 'waiting') {
                $score += 0.15;
            }

            if ($prioritizeGoalkeepers) {
                if ((int) $player['is_goalkeeper'] === 1 && (int) $team['goalkeeper_count'] === 0) {
                    $score -= $team['status'] === 'active' ? 2.4 : 1.2;
                }

                if ((int) $player['is_goalkeeper'] !== 1 && (int) $team['goalkeeper_count'] === 0 && $remainingGoalkeeperSlots > 0) {
                    $score += $team['status'] === 'active' ? 1.25 : 0.55;
                }
            }

            $vacancyPenalty = max(0, count($team['players']) - ((int) $team['target_size'] - 1));
            $score += $vacancyPenalty * 3;

            $noise = random_int(0, 20) / 1000;
            $score += $noise;

            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    private function countGoalkeeperNeeds(array $teams): int
    {
        $needs = 0;
        foreach ($teams as $team) {
            if ((int) $team['goalkeeper_count'] === 0 && !$this->teamIsFull($team)) {
                $needs++;
            }
        }

        return $needs;
    }

    private function refineBySwaps(array $teams): array
    {
        if (count($teams) < 2) {
            return $teams;
        }

        for ($pass = 0; $pass < 12; $pass++) {
            $improved = false;

            for ($left = 0; $left < count($teams) - 1; $left++) {
                for ($right = $left + 1; $right < count($teams); $right++) {
                    if (count($teams[$left]['players']) !== count($teams[$right]['players'])) {
                        continue;
                    }

                    $currentDiff = abs((float) $teams[$left]['total_rating'] - (float) $teams[$right]['total_rating']);
                    $bestSwap = null;

                    foreach ($teams[$left]['players'] as $leftIndex => $leftPlayer) {
                        foreach ($teams[$right]['players'] as $rightIndex => $rightPlayer) {
                            if (abs((float) $leftPlayer['rating'] - (float) $rightPlayer['rating']) > 0.5) {
                                continue;
                            }

                            $newLeft = (float) $teams[$left]['total_rating'] - (float) $leftPlayer['rating'] + (float) $rightPlayer['rating'];
                            $newRight = (float) $teams[$right]['total_rating'] - (float) $rightPlayer['rating'] + (float) $leftPlayer['rating'];
                            $newDiff = abs($newLeft - $newRight);

                            if ($newDiff + 0.09 < $currentDiff) {
                                $bestSwap = [$leftIndex, $rightIndex];
                                $currentDiff = $newDiff;
                            }
                        }
                    }

                    if ($bestSwap !== null) {
                        [$leftIndex, $rightIndex] = $bestSwap;
                        $leftPlayer = $teams[$left]['players'][$leftIndex];
                        $rightPlayer = $teams[$right]['players'][$rightIndex];
                        $teams[$left]['players'][$leftIndex] = $rightPlayer;
                        $teams[$right]['players'][$rightIndex] = $leftPlayer;
                        $teams[$left]['total_rating'] = (float) $teams[$left]['total_rating'] - (float) $leftPlayer['rating'] + (float) $rightPlayer['rating'];
                        $teams[$right]['total_rating'] = (float) $teams[$right]['total_rating'] - (float) $rightPlayer['rating'] + (float) $leftPlayer['rating'];
                        $teams[$left]['goalkeeper_count'] = $this->countGoalkeepers($teams[$left]['players']);
                        $teams[$right]['goalkeeper_count'] = $this->countGoalkeepers($teams[$right]['players']);
                        $improved = true;
                    }
                }
            }

            if (!$improved) {
                break;
            }
        }

        return $teams;
    }

    private function scoreTeams(array $teams, bool $prioritizeGoalkeepers): float
    {
        $score = 0.0;
        $activeTotals = [];
        $waitingTotals = [];

        foreach ($teams as $team) {
            if (($team['status'] ?? 'waiting') === 'active') {
                $activeTotals[] = (float) $team['total_rating'];
            } else {
                $waitingTotals[] = (float) $team['total_rating'];
            }

            if ($prioritizeGoalkeepers && ($team['status'] ?? 'waiting') === 'active' && count($team['players']) > 0 && (int) $team['goalkeeper_count'] === 0) {
                $score += 6;
            }
        }

        if (count($activeTotals) >= 2) {
            $score += (max($activeTotals) - min($activeTotals)) * 10;
        }

        if (count($waitingTotals) >= 2) {
            $score += (max($waitingTotals) - min($waitingTotals)) * 3;
        }

        foreach ($teams as $team) {
            if (count($team['players']) === 0) {
                continue;
            }

            $score += abs(count($team['players']) - (int) $team['target_size']) * 20;
        }

        return $score;
    }

    private function countGoalkeepers(array $players): int
    {
        return count(array_filter($players, static fn (array $player): bool => (int) ($player['is_goalkeeper'] ?? 0) === 1));
    }

    private function buildTargets(int $presentCount, int $playersPerTeam, int $maxPlayersPerMatch): array
    {
        if ($presentCount <= $maxPlayersPerMatch) {
            $first = (int) ceil($presentCount / 2);
            $second = max(0, $presentCount - $first);

            return [$first, $second];
        }

        $teamCount = max(2, (int) ceil($presentCount / $playersPerTeam));
        $targets = [];
        $remaining = $presentCount;

        for ($index = 0; $index < $teamCount; $index++) {
            $target = min($playersPerTeam, $remaining);
            $targets[] = $target;
            $remaining -= $target;
        }

        return $targets;
    }

    private function preferredGoalkeeperIndexes(array $teams): array
    {
        $indexes = [];

        foreach ([0, 1] as $index) {
            if (isset($teams[$index]) && $teams[$index]['target_size'] > 0) {
                $indexes[] = $index;
            }
        }

        foreach (array_keys($teams) as $index) {
            if (!in_array($index, $indexes, true) && $teams[$index]['target_size'] > 0) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function nextSequentialTeamIndex(array $teams): ?int
    {
        foreach ($teams as $index => $team) {
            if (!$this->teamIsFull($team)) {
                return $index;
            }
        }

        return null;
    }

    private function teamIsFull(array $team): bool
    {
        return count($team['players']) >= (int) $team['target_size'];
    }

    private function pushPlayer(array &$team, array $player): void
    {
        $team['players'][] = $player;
        $team['total_rating'] += (float) $player['rating'];
        if ((int) $player['is_goalkeeper'] === 1) {
            $team['goalkeeper_count']++;
        }
    }
}
