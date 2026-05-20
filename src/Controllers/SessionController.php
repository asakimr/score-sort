<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PlayerRepository;
use App\Repositories\SessionRepository;
use App\Services\TeamDrawService;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class SessionController
{
    public function __construct(
        private readonly View $view,
        private readonly PlayerRepository $players,
        private readonly SessionRepository $sessions,
        private readonly TeamDrawService $teamDrawService
    ) {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $flash = $_SESSION['flash'] ?? null;
        $errors = $_SESSION['session_form_errors'] ?? [];
        $old = $_SESSION['old_session_form'] ?? [];

        unset($_SESSION['flash'], $_SESSION['session_form_errors'], $_SESSION['old_session_form']);

        return $this->view->render($response, 'pages/sessions/create', [
            'title' => 'Nova pelada',
            'headerTitle' => 'Presença e sorteio',
            'activeNav' => 'sessions',
            'players' => $this->players->active(),
            'flash' => $flash,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->normalizeInput((array) $request->getParsedBody());
        $errors = $this->validate($input);

        if ($errors !== []) {
            $_SESSION['old_session_form'] = $input;
            $_SESSION['session_form_errors'] = $errors;

            return $this->redirect($response, '/peladas/nova');
        }

        $sessionId = $this->sessions->create($input);
        $attendees = $this->sessions->attendanceBySession($sessionId);
        $summary = $this->teamDrawService->generate(
            $attendees,
            (int) $input['max_players_per_match'],
            (string) $input['draw_mode'],
            (bool) $input['prioritize_goalkeepers']
        );
        $this->sessions->saveTeams($sessionId, $summary['teams']);
        $this->sessions->initializeMatches($sessionId);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Pelada criada, presença salva e times sorteados com sucesso.',
        ];

        return $this->redirect($response, '/peladas/' . $sessionId);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sessionId = (int) ($args['id'] ?? 0);
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Sessão não encontrada.',
            ];

            return $this->redirect($response, '/peladas/nova');
        }

        $attendees = $this->sessions->attendanceBySession($sessionId);
        $teams = $this->sessions->teamsBySession($sessionId);

        if ($teams === []) {
            $summary = $this->teamDrawService->generate(
                $attendees,
                (int) $session['max_players_per_match'],
                (string) $session['draw_mode'],
                (int) ($session['prioritize_goalkeepers'] ?? 1) === 1
            );
            $this->sessions->saveTeams($sessionId, $summary['teams']);
            $this->sessions->initializeMatches($sessionId);
            $teams = $this->sessions->teamsBySession($sessionId);
        }

        $summary = $this->buildSummaryFromTeams($attendees, $teams, (int) $session['max_players_per_match']);
        $currentMatch = $this->sessions->currentMatchBySession($sessionId);
        $matches = $this->sessions->matchesBySession($sessionId);
        $flash = $_SESSION['flash'] ?? null;
        $matchErrors = $_SESSION['match_form_errors'] ?? [];
        $oldMatch = $_SESSION['old_match_form'] ?? [];

        unset($_SESSION['flash'], $_SESSION['match_form_errors'], $_SESSION['old_match_form']);

        return $this->view->render($response, 'pages/sessions/show', [
            'title' => 'Resumo da sessão',
            'headerTitle' => 'Sorteio da sessão',
            'activeNav' => 'sessions',
            'flash' => $flash,
            'matchErrors' => $matchErrors,
            'oldMatch' => $oldMatch,
            'session' => $session,
            'attendees' => $attendees,
            'teams' => $teams,
            'summary' => $summary,
            'currentMatch' => $currentMatch,
            'matches' => $matches,
            'teamMap' => $this->indexTeamsById($teams),
        ]);
    }

    public function resort(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sessionId = (int) ($args['id'] ?? 0);
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Sessão não encontrada para re-sortear.',
            ];

            return $this->redirect($response, '/peladas/nova');
        }

        $attendees = $this->sessions->attendanceBySession($sessionId);
        $summary = $this->teamDrawService->generate(
            $attendees,
            (int) $session['max_players_per_match'],
            (string) $session['draw_mode'],
            (int) ($session['prioritize_goalkeepers'] ?? 1) === 1
        );

        $this->sessions->saveTeams($sessionId, $summary['teams']);
        $this->sessions->initializeMatches($sessionId);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Times re-sorteados com sucesso.',
        ];

        return $this->redirect($response, '/peladas/' . $sessionId);
    }

    public function finishMatch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sessionId = (int) ($args['id'] ?? 0);
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Sessão não encontrada.',
            ];

            return $this->redirect($response, '/peladas/nova');
        }

        $teams = $this->sessions->teamsBySession($sessionId);
        $teamMap = $this->indexTeamsById($teams);
        $currentMatch = $this->sessions->currentMatchBySession($sessionId);

        if ($currentMatch === null) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Não há partida em aberto para registrar resultado.',
            ];

            return $this->redirect($response, '/peladas/' . $sessionId);
        }

        $input = $this->normalizeMatchInput((array) $request->getParsedBody(), $currentMatch, $teamMap);
        $errors = $this->validateMatchResult($input, $currentMatch, $teamMap);

        if ($errors !== []) {
            $_SESSION['match_form_errors'] = $errors;
            $_SESSION['old_match_form'] = $input;

            return $this->redirect($response, '/peladas/' . $sessionId);
        }

        try {
            $result = $this->sessions->finishMatch(
                $sessionId,
                (int) $currentMatch['id'],
                (int) $input['winner_team_id'],
                (int) $input['score_team_a'],
                (int) $input['score_team_b'],
                $input['goal_events'],
                (string) $input['transfer_mode'],
                $input['transfer_player_id'] !== null ? (int) $input['transfer_player_id'] : null
            );
        } catch (RuntimeException $exception) {
            $_SESSION['match_form_errors'] = ['general' => $exception->getMessage()];
            $_SESSION['old_match_form'] = $input;

            return $this->redirect($response, '/peladas/' . $sessionId);
        }

        $message = 'Resultado da partida salvo com sucesso.';
        if (($result['transfer_player_name'] ?? null) !== null && ($result['transfer_to_team_name'] ?? null) !== null) {
            $modeLabel = ($result['transfer_mode'] ?? 'manual') === 'random' ? 'aleatoriamente' : 'manualmente';
            $message .= ' ' . $result['transfer_player_name'] . ' foi movido ' . $modeLabel . ' para ' . $result['transfer_to_team_name'] . '.';
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $message,
        ];

        return $this->redirect($response, '/peladas/' . $sessionId);
    }

    private function normalizeInput(array $payload): array
    {
        $selectedPlayers = $payload['player_ids'] ?? [];

        if (!is_array($selectedPlayers)) {
            $selectedPlayers = [$selectedPlayers];
        }

        $uniquePlayers = array_values(array_unique(array_map(
            static fn ($value): int => (int) $value,
            array_filter($selectedPlayers, static fn ($value): bool => (int) $value > 0)
        )));

        return [
            'session_date' => trim((string) ($payload['session_date'] ?? date('Y-m-d'))),
            'max_players_per_match' => (int) ($payload['max_players_per_match'] ?? 10),
            'draw_mode' => in_array(($payload['draw_mode'] ?? 'balanced'), ['balanced', 'random'], true)
                ? (string) $payload['draw_mode']
                : 'balanced',
            'prioritize_goalkeepers' => isset($payload['prioritize_goalkeepers']) ? 1 : 0,
            'present_player_ids' => $uniquePlayers,
        ];
    }

    private function validate(array $input): array
    {
        $errors = [];

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $input['session_date']);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        $hasDateErrors = is_array($dateErrors)
            ? (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0)
            : false;

        if ($input['session_date'] === '' || $date === false || $hasDateErrors) {
            $errors['session_date'] = 'Informe uma data válida para a pelada.';
        }

        if ($input['max_players_per_match'] < 2 || $input['max_players_per_match'] % 2 !== 0) {
            $errors['max_players_per_match'] = 'O máximo por partida deve ser um número par a partir de 2.';
        }

        if ($input['present_player_ids'] === []) {
            $errors['player_ids'] = 'Selecione pelo menos um jogador presente.';
        }

        if (count($input['present_player_ids']) < 2) {
            $errors['player_ids'] = 'Selecione pelo menos 2 jogadores para abrir a sessão.';
        }

        return $errors;
    }

    private function normalizeMatchInput(array $payload, array $currentMatch, array $teamMap): array
    {
        $winnerTeamId = (int) ($payload['winner_team_id'] ?? 0);
        $scoreTeamA = max(0, (int) ($payload['score_team_a'] ?? 0));
        $scoreTeamB = max(0, (int) ($payload['score_team_b'] ?? 0));
        $transferMode = ($payload['transfer_mode'] ?? 'random') === 'manual' ? 'manual' : 'random';
        $transferPlayerId = isset($payload['transfer_player_id']) && (int) $payload['transfer_player_id'] > 0
            ? (int) $payload['transfer_player_id']
            : null;

        $goalScorers = $payload['goal_scorer'] ?? [];
        $goalAssists = $payload['goal_assist'] ?? [];

        $goalEvents = [];
        $expectedGoals = $scoreTeamA + $scoreTeamB;
        for ($index = 0; $index < $expectedGoals; $index++) {
            $scorerId = isset($goalScorers[$index]) ? (int) $goalScorers[$index] : 0;
            if ($scorerId <= 0) {
                $goalEvents[] = [
                    'team_id' => 0,
                    'player_id' => 0,
                    'assist_player_id' => null,
                ];
                continue;
            }

            $teamId = $this->findPlayerTeamId($scorerId, $teamMap, [(int) $currentMatch['team_a_id'], (int) $currentMatch['team_b_id']]);
            $assistPlayerId = isset($goalAssists[$index]) && (int) $goalAssists[$index] > 0 ? (int) $goalAssists[$index] : null;
            $goalEvents[] = [
                'team_id' => $teamId,
                'player_id' => $scorerId,
                'assist_player_id' => $assistPlayerId,
            ];
        }

        return [
            'winner_team_id' => $winnerTeamId,
            'score_team_a' => $scoreTeamA,
            'score_team_b' => $scoreTeamB,
            'transfer_mode' => $transferMode,
            'transfer_player_id' => $transferPlayerId,
            'goal_events' => $goalEvents,
        ];
    }

    private function validateMatchResult(array $input, array $currentMatch, array $teamMap): array
    {
        $errors = [];
        $teamAId = (int) $currentMatch['team_a_id'];
        $teamBId = (int) $currentMatch['team_b_id'];

        if (!in_array((int) $input['winner_team_id'], [$teamAId, $teamBId], true)) {
            $errors['winner_team_id'] = 'Escolha o time vencedor da partida.';
        }

        if ((int) $input['score_team_a'] === (int) $input['score_team_b']) {
            $errors['score'] = 'Para seguir o fluxo, informe um vencedor com placar diferente.';
        }

        $expectedGoals = (int) $input['score_team_a'] + (int) $input['score_team_b'];
        if (count($input['goal_events']) !== $expectedGoals) {
            $errors['goal_events'] = 'A lista de gols não bate com o placar informado.';
        }

        $teamGoalQuota = [
            $teamAId => (int) $input['score_team_a'],
            $teamBId => (int) $input['score_team_b'],
        ];
        $teamGoalCount = [
            $teamAId => 0,
            $teamBId => 0,
        ];

        foreach ($input['goal_events'] as $index => $event) {
            if ((int) $event['player_id'] <= 0 || (int) $event['team_id'] <= 0) {
                $errors['goal_events'] = 'Selecione o autor de todos os gols.';
                break;
            }

            if (!isset($teamGoalQuota[(int) $event['team_id']])) {
                $errors['goal_events'] = 'Há um gol atribuído a jogador fora dos times da partida.';
                break;
            }

            $teamGoalCount[(int) $event['team_id']]++;

            $teamPlayers = $teamMap[(int) $event['team_id']]['players'] ?? [];
            $playerIds = array_map(static fn (array $player): int => (int) $player['id'], $teamPlayers);

            if (!in_array((int) $event['player_id'], $playerIds, true)) {
                $errors['goal_events'] = 'Há um autor de gol que não pertence ao time selecionado.';
                break;
            }

            if (($event['assist_player_id'] ?? null) !== null && !in_array((int) $event['assist_player_id'], $playerIds, true)) {
                $errors['goal_events'] = 'A assistência deve ser de um jogador do mesmo time do gol.';
                break;
            }

            if (($event['assist_player_id'] ?? null) !== null && (int) $event['assist_player_id'] === (int) $event['player_id']) {
                $errors['goal_events'] = 'O mesmo jogador não pode marcar gol e assistência no mesmo lance.';
                break;
            }
        }

        foreach ($teamGoalQuota as $teamId => $quota) {
            if ($teamGoalCount[$teamId] !== $quota) {
                $errors['goal_events'] = 'A distribuição dos gols entre os times não bate com o placar informado.';
                break;
            }
        }

        if (($input['transfer_mode'] ?? 'random') === 'manual') {
            $loserTeamId = (int) $input['winner_team_id'] === $teamAId ? $teamBId : $teamAId;
            $loserPlayers = $teamMap[$loserTeamId]['players'] ?? [];
            $loserPlayerIds = array_map(static fn (array $player): int => (int) $player['id'], $loserPlayers);
            if (($input['transfer_player_id'] ?? null) !== null && !in_array((int) $input['transfer_player_id'], $loserPlayerIds, true)) {
                $errors['transfer_player_id'] = 'Escolha um jogador do time perdedor para completar o próximo time.';
            }
        }

        return $errors;
    }

    private function buildSummaryFromTeams(array $attendees, array $teams, int $maxPlayersPerMatch): array
    {
        $playersPerTeam = max(1, (int) floor($maxPlayersPerMatch / 2));
        $activeMatchCount = 0;
        $waitingCount = 0;
        $fullWaitingTeams = 0;
        $missingForNextTeam = 0;

        foreach ($teams as $team) {
            $count = count($team['players']);

            if (($team['status'] ?? 'waiting') === 'active') {
                $activeMatchCount += $count;
                continue;
            }

            $waitingCount += $count;
            if ($count >= $playersPerTeam) {
                $fullWaitingTeams++;
            }
        }

        $lastWaitingTeam = null;
        foreach ($teams as $team) {
            if (($team['status'] ?? 'waiting') === 'waiting') {
                $lastWaitingTeam = $team;
            }
        }

        if ($lastWaitingTeam !== null) {
            $missingForNextTeam = max(0, $playersPerTeam - count($lastWaitingTeam['players']));
            if (count($lastWaitingTeam['players']) >= $playersPerTeam) {
                $missingForNextTeam = 0;
            }
        }

        return [
            'present_count' => count($attendees),
            'players_per_team' => $playersPerTeam,
            'active_match_count' => $activeMatchCount,
            'waiting_count' => $waitingCount,
            'full_waiting_teams' => $fullWaitingTeams,
            'missing_for_next_team' => $missingForNextTeam,
            'goalkeeper_count' => count(array_filter($attendees, static fn (array $player): bool => (int) ($player['is_goalkeeper'] ?? 0) === 1)),
            'team_count' => count($teams),
        ];
    }

    private function indexTeamsById(array $teams): array
    {
        $teamMap = [];
        foreach ($teams as $team) {
            $teamMap[(int) $team['id']] = $team;
        }

        return $teamMap;
    }

    private function findPlayerTeamId(int $playerId, array $teamMap, array $allowedTeamIds): int
    {
        foreach ($allowedTeamIds as $teamId) {
            foreach (($teamMap[$teamId]['players'] ?? []) as $player) {
                if ((int) $player['id'] === $playerId) {
                    return (int) $teamId;
                }
            }
        }

        return 0;
    }

    private function redirect(ResponseInterface $response, string $path): ResponseInterface
    {
        return $response
            ->withHeader('Location', $path)
            ->withStatus(302);
    }
}
