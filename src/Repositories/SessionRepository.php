<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use Throwable;

final class SessionRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function latest(): ?array
    {
        $statement = $this->pdo->query(
            'SELECT s.id,
                    s.session_date,
                    s.status,
                    s.max_players_per_match,
                    s.draw_mode,
                    COALESCE(s.prioritize_goalkeepers, 1) AS prioritize_goalkeepers,
                    COUNT(sa.id) AS present_count
             FROM sessions s
             LEFT JOIN session_attendances sa
               ON sa.session_id = s.id
              AND sa.is_present = 1
             GROUP BY s.id
             ORDER BY s.session_date DESC, s.id DESC
             LIMIT 1'
        );
        $session = $statement ? $statement->fetch() : false;

        return $session ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.id,
                    s.session_date,
                    s.status,
                    s.max_players_per_match,
                    s.draw_mode,
                    COALESCE(s.prioritize_goalkeepers, 1) AS prioritize_goalkeepers,
                    COUNT(sa.id) AS present_count
             FROM sessions s
             LEFT JOIN session_attendances sa
               ON sa.session_id = s.id
              AND sa.is_present = 1
             WHERE s.id = :id
             GROUP BY s.id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $session = $statement->fetch();

        return $session ?: null;
    }

    public function create(array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO sessions (session_date, status, max_players_per_match, draw_mode, prioritize_goalkeepers, created_at, updated_at)
                 VALUES (:session_date, :status, :max_players_per_match, :draw_mode, :prioritize_goalkeepers, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );

            $statement->execute([
                'session_date' => $data['session_date'],
                'status' => 'draft',
                'max_players_per_match' => $data['max_players_per_match'],
                'draw_mode' => $data['draw_mode'],
                'prioritize_goalkeepers' => (int) ($data['prioritize_goalkeepers'] ?? 1),
            ]);

            $sessionId = (int) $this->pdo->lastInsertId();

            $attendanceStatement = $this->pdo->prepare(
                'INSERT INTO session_attendances (session_id, player_id, is_present, created_at)
                 VALUES (:session_id, :player_id, 1, CURRENT_TIMESTAMP)'
            );

            foreach ($data['present_player_ids'] as $playerId) {
                $attendanceStatement->execute([
                    'session_id' => $sessionId,
                    'player_id' => $playerId,
                ]);
            }

            $this->pdo->commit();

            return $sessionId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function saveTeams(int $sessionId, array $teams): void
    {
        $this->pdo->beginTransaction();

        try {
            $teamIds = $this->pdo->prepare('SELECT id FROM teams WHERE session_id = :session_id');
            $teamIds->execute(['session_id' => $sessionId]);
            $existingTeamIds = $teamIds->fetchAll(PDO::FETCH_COLUMN) ?: [];

            if ($existingTeamIds !== []) {
                $deleteTeamPlayers = $this->pdo->prepare('DELETE FROM team_players WHERE team_id = :team_id');
                foreach ($existingTeamIds as $teamId) {
                    $deleteTeamPlayers->execute(['team_id' => $teamId]);
                }

                $deleteTeams = $this->pdo->prepare('DELETE FROM teams WHERE session_id = :session_id');
                $deleteTeams->execute(['session_id' => $sessionId]);
            }

            $insertTeam = $this->pdo->prepare(
                'INSERT INTO teams (session_id, name, team_order, status, created_at)
                 VALUES (:session_id, :name, :team_order, :status, CURRENT_TIMESTAMP)'
            );

            $insertTeamPlayer = $this->pdo->prepare(
                'INSERT INTO team_players (team_id, player_id, created_at)
                 VALUES (:team_id, :player_id, CURRENT_TIMESTAMP)'
            );

            foreach ($teams as $team) {
                $insertTeam->execute([
                    'session_id' => $sessionId,
                    'name' => $team['name'],
                    'team_order' => $team['team_order'],
                    'status' => $team['status'],
                ]);

                $teamId = (int) $this->pdo->lastInsertId();

                foreach ($team['players'] as $player) {
                    $insertTeamPlayer->execute([
                        'team_id' => $teamId,
                        'player_id' => (int) $player['id'],
                    ]);
                }
            }

            $updateSession = $this->pdo->prepare(
                'UPDATE sessions
                 SET status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $updateSession->execute([
                'id' => $sessionId,
                'status' => 'drawn',
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function initializeMatches(int $sessionId): void
    {
        $this->pdo->beginTransaction();

        try {
            $deleteMatches = $this->pdo->prepare('DELETE FROM matches WHERE session_id = :session_id');
            $deleteMatches->execute(['session_id' => $sessionId]);

            $teams = $this->teamsBySession($sessionId);
            $activeTeams = array_values(array_filter(
                $teams,
                static fn (array $team): bool => ($team['status'] ?? 'waiting') === 'active'
            ));

            if (count($activeTeams) >= 2) {
                $this->createMatchRecord($sessionId, (int) $activeTeams[0]['id'], (int) $activeTeams[1]['id'], 1);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function attendanceBySession(int $sessionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id,
                    p.name,
                    p.rating,
                    p.is_goalkeeper
             FROM session_attendances sa
             INNER JOIN players p ON p.id = sa.player_id
             WHERE sa.session_id = :session_id
               AND sa.is_present = 1
             ORDER BY p.is_goalkeeper DESC, p.rating DESC, p.name ASC'
        );
        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll() ?: [];
    }

    public function teamsBySession(int $sessionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id,
                    t.name,
                    t.team_order,
                    t.status,
                    p.id AS player_id,
                    p.name AS player_name,
                    p.rating AS player_rating,
                    p.is_goalkeeper AS player_is_goalkeeper
             FROM teams t
             LEFT JOIN team_players tp ON tp.team_id = t.id
             LEFT JOIN players p ON p.id = tp.player_id
             WHERE t.session_id = :session_id
             ORDER BY t.team_order ASC,
                      p.is_goalkeeper DESC,
                      p.rating DESC,
                      p.name ASC'
        );
        $statement->execute(['session_id' => $sessionId]);
        $rows = $statement->fetchAll() ?: [];

        if ($rows === []) {
            return [];
        }

        $teams = [];

        foreach ($rows as $row) {
            $teamId = (int) $row['id'];

            if (!isset($teams[$teamId])) {
                $teams[$teamId] = [
                    'id' => $teamId,
                    'name' => (string) $row['name'],
                    'team_order' => (int) $row['team_order'],
                    'status' => (string) $row['status'],
                    'players' => [],
                    'total_rating' => 0.0,
                    'goalkeeper_count' => 0,
                ];
            }

            if ($row['player_id'] === null) {
                continue;
            }

            $player = [
                'id' => (int) $row['player_id'],
                'name' => (string) $row['player_name'],
                'rating' => (float) $row['player_rating'],
                'is_goalkeeper' => (int) $row['player_is_goalkeeper'],
            ];

            $teams[$teamId]['players'][] = $player;
            $teams[$teamId]['total_rating'] += (float) $player['rating'];
            if ($player['is_goalkeeper'] === 1) {
                $teams[$teamId]['goalkeeper_count']++;
            }
        }

        return array_values($teams);
    }

    public function currentMatchBySession(int $sessionId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.id,
                    m.session_id,
                    m.match_order,
                    m.score_team_a,
                    m.score_team_b,
                    m.transfer_player_id,
                    m.transfer_to_team_id,
                    m.transfer_mode,
                    m.winner_team_id,
                    m.loser_team_id,
                    teamA.id AS team_a_id,
                    teamA.name AS team_a_name,
                    teamB.id AS team_b_id,
                    teamB.name AS team_b_name
             FROM matches m
             INNER JOIN teams teamA ON teamA.id = m.team_a_id
             INNER JOIN teams teamB ON teamB.id = m.team_b_id
             WHERE m.session_id = :session_id
               AND m.winner_team_id IS NULL
             ORDER BY m.match_order DESC, m.id DESC
             LIMIT 1'
        );
        $statement->execute(['session_id' => $sessionId]);
        $match = $statement->fetch();

        return $match ?: null;
    }

    public function findMatchById(int $matchId, int $sessionId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.id,
                    m.session_id,
                    m.match_order,
                    m.score_team_a,
                    m.score_team_b,
                    m.winner_team_id,
                    m.loser_team_id,
                    teamA.id AS team_a_id,
                    teamA.name AS team_a_name,
                    teamB.id AS team_b_id,
                    teamB.name AS team_b_name
             FROM matches m
             INNER JOIN teams teamA ON teamA.id = m.team_a_id
             INNER JOIN teams teamB ON teamB.id = m.team_b_id
             WHERE m.id = :id
               AND m.session_id = :session_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $matchId,
            'session_id' => $sessionId,
        ]);
        $match = $statement->fetch();

        return $match ?: null;
    }

    public function matchesBySession(int $sessionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.id,
                    m.match_order,
                    m.created_at,
                    m.updated_at,
                    m.score_team_a,
                    m.score_team_b,
                    m.transfer_player_id,
                    m.transfer_to_team_id,
                    m.transfer_mode,
                    m.winner_team_id,
                    m.loser_team_id,
                    teamA.id AS team_a_id,
                    teamA.name AS team_a_name,
                    teamB.id AS team_b_id,
                    teamB.name AS team_b_name,
                    winner.name AS winner_team_name,
                    loser.name AS loser_team_name,
                    transferPlayer.name AS transfer_player_name,
                    transferTarget.name AS transfer_to_team_name
             FROM matches m
             INNER JOIN teams teamA ON teamA.id = m.team_a_id
             INNER JOIN teams teamB ON teamB.id = m.team_b_id
             LEFT JOIN teams winner ON winner.id = m.winner_team_id
             LEFT JOIN teams loser ON loser.id = m.loser_team_id
             LEFT JOIN players transferPlayer ON transferPlayer.id = m.transfer_player_id
             LEFT JOIN teams transferTarget ON transferTarget.id = m.transfer_to_team_id
             WHERE m.session_id = :session_id
             ORDER BY m.match_order ASC, m.id ASC'
        );
        $statement->execute(['session_id' => $sessionId]);
        $matches = $statement->fetchAll() ?: [];

        if ($matches === []) {
            return [];
        }

        $eventsByMatch = $this->matchEventsBySession($sessionId);

        foreach ($matches as &$match) {
            $match['events'] = $eventsByMatch[(int) $match['id']] ?? [];
        }
        unset($match);

        return $matches;
    }

    public function finishMatch(
        int $sessionId,
        int $matchId,
        int $winnerTeamId,
        int $scoreTeamA,
        int $scoreTeamB,
        array $goalEvents,
        string $transferMode,
        ?int $transferPlayerId = null
    ): array {
        $match = $this->findMatchById($matchId, $sessionId);

        if ($match === null) {
            throw new RuntimeException('Partida não encontrada.');
        }

        if ($match['winner_team_id'] !== null) {
            throw new RuntimeException('Essa partida já foi finalizada.');
        }

        $teamAId = (int) $match['team_a_id'];
        $teamBId = (int) $match['team_b_id'];

        if (!in_array($winnerTeamId, [$teamAId, $teamBId], true)) {
            throw new RuntimeException('O vencedor selecionado não pertence à partida atual.');
        }

        $loserTeamId = $winnerTeamId === $teamAId ? $teamBId : $teamAId;
        $session = $this->findById($sessionId);
        $playersPerTeam = max(1, (int) floor(((int) ($session['max_players_per_match'] ?? 10)) / 2));

        $this->pdo->beginTransaction();

        try {
            $transferPlayer = null;
            $nextTeam = $this->nextWaitingTeam($sessionId, [$winnerTeamId, $loserTeamId]);

            if ($nextTeam !== null) {
                $targetTeamId = (int) $nextTeam['id'];
                $targetPlayers = $this->playersByTeamId($targetTeamId);

                if (count($targetPlayers) < $playersPerTeam) {
                    $loserPlayers = $this->playersByTeamId($loserTeamId);
                    $transferPlayer = $this->resolveTransferPlayer($loserPlayers, $transferMode, $transferPlayerId);

                    $movePlayer = $this->pdo->prepare(
                        'UPDATE team_players
                         SET team_id = :to_team_id
                         WHERE team_id = :from_team_id
                           AND player_id = :player_id'
                    );
                    $movePlayer->execute([
                        'to_team_id' => $targetTeamId,
                        'from_team_id' => $loserTeamId,
                        'player_id' => (int) $transferPlayer['id'],
                    ]);

                    $insertTransfer = $this->pdo->prepare(
                        'INSERT INTO match_transfers (match_id, player_id, from_team_id, to_team_id, transfer_mode, created_at)
                         VALUES (:match_id, :player_id, :from_team_id, :to_team_id, :transfer_mode, CURRENT_TIMESTAMP)'
                    );
                    $insertTransfer->execute([
                        'match_id' => $matchId,
                        'player_id' => (int) $transferPlayer['id'],
                        'from_team_id' => $loserTeamId,
                        'to_team_id' => $targetTeamId,
                        'transfer_mode' => $transferMode,
                    ]);
                }
            }

            $updateMatch = $this->pdo->prepare(
                'UPDATE matches
                 SET winner_team_id = :winner_team_id,
                     loser_team_id = :loser_team_id,
                     score_team_a = :score_team_a,
                     score_team_b = :score_team_b,
                     transfer_player_id = :transfer_player_id,
                     transfer_to_team_id = :transfer_to_team_id,
                     transfer_mode = :transfer_mode,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $updateMatch->execute([
                'id' => $matchId,
                'winner_team_id' => $winnerTeamId,
                'loser_team_id' => $loserTeamId,
                'score_team_a' => $scoreTeamA,
                'score_team_b' => $scoreTeamB,
                'transfer_player_id' => $transferPlayer['id'] ?? null,
                'transfer_to_team_id' => $nextTeam['id'] ?? null,
                'transfer_mode' => $transferPlayer !== null ? $transferMode : null,
            ]);

            $deleteEvents = $this->pdo->prepare('DELETE FROM match_events WHERE match_id = :match_id');
            $deleteEvents->execute(['match_id' => $matchId]);

            if ($goalEvents !== []) {
                $insertEvent = $this->pdo->prepare(
                    'INSERT INTO match_events (match_id, team_id, player_id, event_type, related_player_id, event_order, created_at)
                     VALUES (:match_id, :team_id, :player_id, :event_type, :related_player_id, :event_order, CURRENT_TIMESTAMP)'
                );

                foreach (array_values($goalEvents) as $index => $event) {
                    $insertEvent->execute([
                        'match_id' => $matchId,
                        'team_id' => (int) $event['team_id'],
                        'player_id' => (int) $event['player_id'],
                        'event_type' => 'goal',
                        'related_player_id' => $event['assist_player_id'] ?: null,
                        'event_order' => $index + 1,
                    ]);
                }
            }

            if ($nextTeam !== null) {
                $this->setTeamStatus($winnerTeamId, 'active');
                $this->setTeamStatus((int) $nextTeam['id'], 'active');
                $this->setTeamStatus($loserTeamId, 'waiting');
                $this->pushTeamToQueueEnd($sessionId, $loserTeamId);
                $nextMatchTeamA = $winnerTeamId;
                $nextMatchTeamB = (int) $nextTeam['id'];
            } else {
                $this->setTeamStatus($winnerTeamId, 'active');
                $this->setTeamStatus($loserTeamId, 'active');
                $nextMatchTeamA = $winnerTeamId;
                $nextMatchTeamB = $loserTeamId;
            }

            $this->createMatchRecord($sessionId, $nextMatchTeamA, $nextMatchTeamB, ((int) $match['match_order']) + 1);

            $updateSession = $this->pdo->prepare(
                'UPDATE sessions
                 SET status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $updateSession->execute([
                'id' => $sessionId,
                'status' => 'in_progress',
            ]);

            $this->pdo->commit();

            return [
                'winner_team_id' => $winnerTeamId,
                'loser_team_id' => $loserTeamId,
                'transfer_player_name' => $transferPlayer['name'] ?? null,
                'transfer_to_team_name' => $nextTeam['name'] ?? null,
                'transfer_mode' => $transferPlayer !== null ? $transferMode : null,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function countPresentInLatestSession(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*)
             FROM session_attendances sa
             WHERE sa.is_present = 1
               AND sa.session_id = (
                    SELECT id
                    FROM sessions
                    ORDER BY session_date DESC, id DESC
                    LIMIT 1
               )'
        );

        return (int) ($statement?->fetchColumn() ?: 0);
    }

    public function countWaitingTeamsInLatestSession(): int
    {
        $latest = $this->latest();

        if ($latest === null) {
            return 0;
        }

        $presentCount = (int) ($latest['present_count'] ?? 0);
        $maxPlayersPerMatch = max(2, (int) $latest['max_players_per_match']);

        if ($presentCount <= $maxPlayersPerMatch) {
            return 0;
        }

        return (int) ceil(($presentCount - $maxPlayersPerMatch) / $maxPlayersPerMatch);
    }

    private function playersByTeamId(int $teamId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id,
                    p.name,
                    p.rating,
                    p.is_goalkeeper
             FROM team_players tp
             INNER JOIN players p ON p.id = tp.player_id
             WHERE tp.team_id = :team_id
             ORDER BY p.is_goalkeeper DESC, p.rating DESC, p.name ASC'
        );
        $statement->execute(['team_id' => $teamId]);

        return $statement->fetchAll() ?: [];
    }

    private function nextWaitingTeam(int $sessionId, array $excludeTeamIds): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, team_order, status
             FROM teams
             WHERE session_id = :session_id
               AND status = :status
             ORDER BY team_order ASC, id ASC'
        );
        $statement->execute([
            'session_id' => $sessionId,
            'status' => 'waiting',
        ]);

        $teams = $statement->fetchAll() ?: [];

        foreach ($teams as $team) {
            if (!in_array((int) $team['id'], $excludeTeamIds, true)) {
                return $team;
            }
        }

        return null;
    }

    private function resolveTransferPlayer(array $loserPlayers, string $transferMode, ?int $transferPlayerId): array
    {
        if ($loserPlayers === []) {
            throw new RuntimeException('Não há jogador disponível no time perdedor para completar o próximo time.');
        }

        if ($transferMode === 'manual') {
            foreach ($loserPlayers as $player) {
                if ((int) $player['id'] === (int) $transferPlayerId) {
                    return $player;
                }
            }

            throw new RuntimeException('Escolha um jogador válido do time perdedor para completar o próximo time.');
        }

        shuffle($loserPlayers);

        return $loserPlayers[0];
    }

    private function setTeamStatus(int $teamId, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE teams
             SET status = :status
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $teamId,
            'status' => $status,
        ]);
    }

    private function pushTeamToQueueEnd(int $sessionId, int $teamId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(MAX(team_order), 0) FROM teams WHERE session_id = :session_id'
        );
        $statement->execute(['session_id' => $sessionId]);
        $maxOrder = (int) ($statement->fetchColumn() ?: 0);

        $update = $this->pdo->prepare(
            'UPDATE teams
             SET team_order = :team_order
             WHERE id = :id'
        );
        $update->execute([
            'id' => $teamId,
            'team_order' => $maxOrder + 1,
        ]);
    }

    private function createMatchRecord(int $sessionId, int $teamAId, int $teamBId, int $matchOrder): void
    {
        $insertMatch = $this->pdo->prepare(
            'INSERT INTO matches (session_id, team_a_id, team_b_id, match_order, created_at, updated_at)
             VALUES (:session_id, :team_a_id, :team_b_id, :match_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $insertMatch->execute([
            'session_id' => $sessionId,
            'team_a_id' => $teamAId,
            'team_b_id' => $teamBId,
            'match_order' => $matchOrder,
        ]);
    }

    private function matchEventsBySession(int $sessionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT me.match_id,
                    me.team_id,
                    me.player_id,
                    me.related_player_id,
                    me.event_order,
                    me.event_type,
                    scorer.name AS player_name,
                    assister.name AS assist_player_name,
                    team.name AS team_name
             FROM match_events me
             INNER JOIN matches m ON m.id = me.match_id
             INNER JOIN players scorer ON scorer.id = me.player_id
             INNER JOIN teams team ON team.id = me.team_id
             LEFT JOIN players assister ON assister.id = me.related_player_id
             WHERE m.session_id = :session_id
             ORDER BY me.match_id ASC, me.event_order ASC, me.id ASC'
        );
        $statement->execute(['session_id' => $sessionId]);
        $rows = $statement->fetchAll() ?: [];
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['match_id']][] = [
                'team_id' => (int) $row['team_id'],
                'team_name' => (string) $row['team_name'],
                'player_id' => (int) $row['player_id'],
                'player_name' => (string) $row['player_name'],
                'assist_player_id' => $row['related_player_id'] !== null ? (int) $row['related_player_id'] : null,
                'assist_player_name' => $row['assist_player_name'] !== null ? (string) $row['assist_player_name'] : null,
                'event_order' => (int) $row['event_order'],
                'event_type' => (string) $row['event_type'],
            ];
        }

        return $grouped;
    }
}
