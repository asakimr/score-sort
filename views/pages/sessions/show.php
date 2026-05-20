<?php
$flash = $flash ?? null;
$matchErrors = $matchErrors ?? [];
$oldMatch = $oldMatch ?? [];
$session = $session ?? null;
$attendees = $attendees ?? [];
$teams = $teams ?? [];
$teamMap = $teamMap ?? [];
$summary = $summary ?? [];
$currentMatch = $currentMatch ?? null;
$matches = $matches ?? [];
$modeLabels = [
    'balanced' => 'Balanceado',
    'random' => 'Aleatório',
];
$statusLabels = [
    'draft' => 'Rascunho',
    'drawn' => 'Sorteado',
    'in_progress' => 'Em andamento',
    'finished' => 'Finalizado',
];
$activeTeams = array_values(array_filter($teams, static fn (array $team): bool => ($team['status'] ?? '') === 'active'));
$waitingTeams = array_values(array_filter($teams, static fn (array $team): bool => ($team['status'] ?? '') === 'waiting'));
$nextWaitingTeam = $waitingTeams[0] ?? null;
$currentTeamA = $currentMatch ? ($teamMap[(int) $currentMatch['team_a_id']] ?? null) : null;
$currentTeamB = $currentMatch ? ($teamMap[(int) $currentMatch['team_b_id']] ?? null) : null;
$defaultWinnerTeamId = (int) ($oldMatch['winner_team_id'] ?? 0);
$defaultScoreA = (int) ($oldMatch['score_team_a'] ?? 0);
$defaultScoreB = (int) ($oldMatch['score_team_b'] ?? 0);
$defaultTransferMode = (string) ($oldMatch['transfer_mode'] ?? 'random');
$defaultTransferPlayerId = $oldMatch['transfer_player_id'] ?? null;
$oldGoalEvents = $oldMatch['goal_events'] ?? [];
$defaultGoalScorers = [];
$defaultGoalAssists = [];
foreach ($oldGoalEvents as $index => $event) {
    $defaultGoalScorers[$index] = (int) ($event['player_id'] ?? 0);
    $defaultGoalAssists[$index] = (int) ($event['assist_player_id'] ?? 0);
}

$pitchPositions = static function (int $count): array {
    return match ($count) {
        1 => ['top-[45%] left-[50%]'],
        2 => ['top-[28%] left-[50%]', 'top-[65%] left-[50%]'],
        3 => ['top-[18%] left-[50%]', 'top-[50%] left-[28%]', 'top-[50%] left-[72%]'],
        4 => ['top-[18%] left-[50%]', 'top-[42%] left-[28%]', 'top-[42%] left-[72%]', 'top-[72%] left-[50%]'],
        default => ['top-[14%] left-[50%]', 'top-[34%] left-[24%]', 'top-[34%] left-[76%]', 'top-[60%] left-[34%]', 'top-[60%] left-[66%]'],
    };
};

$renderPitch = static function (array $team, string $tone = 'brand') use ($pitchPositions): void {
    $players = $team['players'] ?? [];
    $positions = $pitchPositions(count($players));
    $badgeClasses = $tone === 'emerald'
        ? 'border-emerald-200 bg-white/95 text-emerald-900 shadow-[0_10px_25px_rgba(5,150,105,0.18)]'
        : 'border-brand-200 bg-white/95 text-brand-950 shadow-[0_10px_25px_rgba(37,99,235,0.16)]';
    ?>
    <div class="relative overflow-hidden rounded-[28px] border border-emerald-900/30 bg-gradient-to-b from-emerald-500 via-emerald-600 to-emerald-700 p-3 shadow-inner sm:p-4">
        <div class="relative h-[320px] rounded-[24px] border-2 border-white/75">
            <div class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-white/70"></div>
            <div class="absolute left-1/2 top-1/2 h-20 w-20 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white/70"></div>
            <div class="absolute left-1/2 top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/80"></div>
            <div class="absolute left-1/2 top-0 h-12 w-28 -translate-x-1/2 border-x-2 border-b-2 border-white/70"></div>
            <div class="absolute left-1/2 bottom-0 h-12 w-28 -translate-x-1/2 border-x-2 border-t-2 border-white/70"></div>
            <div class="absolute left-1/2 top-0 h-5 w-14 -translate-x-1/2 border-x-2 border-b-2 border-white/70"></div>
            <div class="absolute left-1/2 bottom-0 h-5 w-14 -translate-x-1/2 border-x-2 border-t-2 border-white/70"></div>

            <?php foreach ($players as $index => $player): ?>
                <?php $positionClass = $positions[$index] ?? 'top-[50%] left-[50%]'; ?>
                <div class="absolute <?= $positionClass ?> w-[92px] -translate-x-1/2 -translate-y-1/2 sm:w-[104px]">
                    <div class="rounded-2xl border px-2 py-2 text-center <?= $badgeClasses ?>">
                        <p class="truncate text-[11px] font-bold sm:text-xs"><?= htmlspecialchars((string) $player['name']) ?></p>
                        <div class="mt-1 flex items-center justify-center gap-1 text-[10px] sm:text-[11px]">
                            <span class="rounded-full bg-slate-900/6 px-2 py-0.5 font-semibold"><?= htmlspecialchars(number_format((float) $player['rating'], 1, ',', '.')) ?></span>
                            <?php if ((int) ($player['is_goalkeeper'] ?? 0) === 1): ?>
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">GK</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};
?>

<section class="space-y-6" x-data="sessionPage(<?= htmlspecialchars(json_encode([
    'activeTab' => 'team-1',
    'currentMatch' => $currentMatch ? [
        'id' => (int) $currentMatch['id'],
        'team_a_id' => (int) $currentMatch['team_a_id'],
        'team_b_id' => (int) $currentMatch['team_b_id'],
    ] : null,
    'teams' => array_map(static function (array $team): array {
        return [
            'id' => (int) $team['id'],
            'name' => (string) $team['name'],
            'status' => (string) $team['status'],
            'team_order' => (int) $team['team_order'],
            'players' => array_map(static function (array $player): array {
                return [
                    'id' => (int) $player['id'],
                    'name' => (string) $player['name'],
                    'rating' => (float) $player['rating'],
                    'is_goalkeeper' => (int) $player['is_goalkeeper'],
                ];
            }, $team['players'] ?? []),
        ];
    }, $teams),
    'matchForm' => [
        'winner_team_id' => $defaultWinnerTeamId,
        'score_team_a' => $defaultScoreA,
        'score_team_b' => $defaultScoreB,
        'transfer_mode' => $defaultTransferMode,
        'transfer_player_id' => $defaultTransferPlayerId !== null ? (int) $defaultTransferPlayerId : '',
        'goal_scorers' => $defaultGoalScorers,
        'goal_assists' => $defaultGoalAssists,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)">
    <?php if ($flash): ?>
        <div class="rounded-3xl border px-4 py-3 text-sm font-medium <?= ($flash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-4 xl:flex xl:items-stretch xl:gap-4">
        <div class="rounded-[28px] bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-soft sm:p-8 xl:flex-1 xl:p-9">
            <p class="text-sm text-blue-100">Rodada em andamento</p>
            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Organize quem joga agora, quem venceu e quem fez gol ou assistência.</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-50">Quando a partida termina, o vencedor permanece. Se houver time esperando incompleto, você escolhe manualmente ou aleatoriamente um jogador do perdedor para completar.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <form action="/peladas/<?= (int) ($session['id'] ?? 0) ?>/resortear" method="post" class="w-full sm:w-auto">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 transition hover:bg-slate-100 sm:w-auto">Re-sortear times</button>
                </form>
                <a href="/peladas/nova" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 transition hover:bg-slate-100">Nova sessão</a>
                <a href="/jogadores" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Ajustar jogadores</a>
            </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:w-[420px] xl:shrink-0">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Configuração da rodada</h3>
                    <p class="text-sm text-slate-500">Dados usados no sorteio atual.</p>
                </div>
                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700"><?= (int) ($summary['team_count'] ?? count($teams)) ?> time(s)</span>
            </div>

            <dl class="mt-5 space-y-3 text-sm text-slate-600">
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <dt>Data</dt>
                    <dd class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($session['session_date'] ?? '')) ?></dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <dt>Modo do sorteio</dt>
                    <dd class="font-semibold text-slate-900"><?= htmlspecialchars($modeLabels[(string) ($session['draw_mode'] ?? 'balanced')] ?? (string) ($session['draw_mode'] ?? 'balanced')) ?></dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <dt>Máximo por partida</dt>
                    <dd class="font-semibold text-slate-900"><?= (int) ($session['max_players_per_match'] ?? 0) ?></dd>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <dt>Status</dt>
                    <dd class="font-semibold text-slate-900"><?= htmlspecialchars($statusLabels[(string) ($session['status'] ?? 'drawn')] ?? (string) ($session['status'] ?? 'drawn')) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Presentes</p>
            <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= (int) ($summary['present_count'] ?? 0) ?></strong>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Por time</p>
            <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= (int) ($summary['players_per_team'] ?? 0) ?></strong>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Na partida ativa</p>
            <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= (int) ($summary['active_match_count'] ?? 0) ?></strong>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Na espera</p>
            <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= (int) ($summary['waiting_count'] ?? 0) ?></strong>
        </div>
        <div class="col-span-2 rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200 lg:col-span-1">
            <p class="text-sm font-medium text-slate-500">Faltam pro próximo time</p>
            <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= (int) ($summary['missing_for_next_team'] ?? 0) ?></strong>
        </div>
    </div>

    <div class="space-y-6 xl:flex xl:items-start xl:gap-6 xl:space-y-0">
        <section class="space-y-4 xl:w-[390px] xl:shrink-0">
            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Partidas</h3>
                        <p class="text-sm text-slate-500">Confronto atual e histórico com placar, gols e assistências.</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700"><?= count($matches) ?> registro(s)</span>
                </div>

                <?php if ($currentMatch): ?>
                    <div class="mt-5 rounded-3xl border border-brand-100 bg-brand-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Partida atual</p>
                        <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-center">
                            <div class="min-w-0 rounded-2xl bg-white px-4 py-4 ring-1 ring-brand-100">
                                <p class="truncate text-base font-bold text-slate-900"><?= htmlspecialchars((string) ($currentMatch['team_a_name'] ?? 'Time A')) ?></p>
                            </div>
                            <span class="rounded-full bg-brand-700 px-3 py-1 text-xs font-bold text-white">x</span>
                            <div class="min-w-0 rounded-2xl bg-white px-4 py-4 ring-1 ring-brand-100">
                                <p class="truncate text-base font-bold text-slate-900"><?= htmlspecialchars((string) ($currentMatch['team_b_name'] ?? 'Time B')) ?></p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Partida <?= (int) ($currentMatch['match_order'] ?? 1) ?> pronta para receber resultado.</p>
                        <p class="mt-2 text-sm text-slate-600">
                            Ao salvar, a próxima partida fica automática assim:
                            <strong>o vencedor permanece</strong>
                            <?php if ($waitingTeams !== []): ?>
                                e entra o <strong>primeiro time da fila</strong>.
                            <?php else: ?>
                                e, como não há fila, o confronto segue com os mesmos dois times.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mt-5 rounded-3xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                        Ainda não existe uma partida ativa registrada para esta sessão.
                    </div>
                <?php endif; ?>

                <?php if ($matches !== []): ?>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($matches as $match): ?>
                            <article class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <strong class="text-sm text-slate-900">Partida <?= (int) ($match['match_order'] ?? 0) ?></strong>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= ($match['winner_team_id'] ?? null) === null ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                        <?= ($match['winner_team_id'] ?? null) === null ? 'Em aberto' : 'Finalizada' ?>
                                    </span>
                                </div>
                                <p class="mt-2 text-sm font-semibold text-slate-700">
                                    <?= htmlspecialchars((string) ($match['team_a_name'] ?? 'Time A')) ?>
                                    <span class="mx-2 text-slate-400"><?= (int) ($match['score_team_a'] ?? 0) ?></span>
                                    <span class="text-slate-400">x</span>
                                    <span class="mx-2 text-slate-400"><?= (int) ($match['score_team_b'] ?? 0) ?></span>
                                    <?= htmlspecialchars((string) ($match['team_b_name'] ?? 'Time B')) ?>
                                </p>
                                <?php if (($match['winner_team_name'] ?? null) !== null): ?>
                                    <p class="mt-2 text-sm font-medium text-emerald-700">Vencedor: <?= htmlspecialchars((string) $match['winner_team_name']) ?></p>
                                <?php else: ?>
                                    <p class="mt-2 text-sm text-slate-500">Resultado pendente.</p>
                                <?php endif; ?>

                                <?php if (($match['transfer_player_name'] ?? null) !== null && ($match['transfer_to_team_name'] ?? null) !== null): ?>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Completo com <?= htmlspecialchars((string) $match['transfer_player_name']) ?> → <?= htmlspecialchars((string) $match['transfer_to_team_name']) ?>
                                        (<?= ($match['transfer_mode'] ?? 'manual') === 'random' ? 'aleatório' : 'manual' ?>)
                                    </p>
                                <?php endif; ?>

                                <?php if (($match['events'] ?? []) !== []): ?>
                                    <div class="mt-3 space-y-2 rounded-2xl bg-slate-50 p-3 text-sm text-slate-600">
                                        <?php foreach ($match['events'] as $event): ?>
                                            <p>
                                                ⚽ <strong class="text-slate-800"><?= htmlspecialchars((string) $event['player_name']) ?></strong>
                                                <span class="text-slate-500">(<?= htmlspecialchars((string) $event['team_name']) ?>)</span>
                                                <?php if (($event['assist_player_name'] ?? null) !== null): ?>
                                                    · Assistência: <strong class="text-slate-800"><?= htmlspecialchars((string) $event['assist_player_name']) ?></strong>
                                                <?php endif; ?>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <h3 class="text-lg font-semibold text-slate-900">Leitura rápida do sorteio</h3>
                <div class="mt-5 space-y-3 text-sm text-slate-600">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <strong class="block text-slate-900">Partida ativa</strong>
                        <p class="mt-2 leading-6">Os times <strong>1</strong> e <strong>2</strong> entram primeiro em quadra com até <strong><?= (int) ($summary['players_per_team'] ?? 0) ?></strong> jogadores por lado.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <strong class="block text-slate-900">Fila de espera</strong>
                        <p class="mt-2 leading-6">Há <strong><?= count($waitingTeams) ?></strong> time(s) na sequência aguardando entrar, incluindo equipes incompletas quando faltam jogadores.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <strong class="block text-slate-900">Goleiros</strong>
                        <p class="mt-2 leading-6">Foram distribuídos <strong><?= (int) ($summary['goalkeeper_count'] ?? 0) ?></strong> jogador(es) marcados como goleiro entre os times sorteados.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Presentes confirmados</h3>
                        <p class="text-sm text-slate-500">Lista geral dos atletas usados no sorteio.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"><?= count($attendees) ?> atleta(s)</span>
                </div>

                <?php if ($attendees !== []): ?>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($attendees as $player): ?>
                            <article class="flex items-start justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                                <div class="min-w-0">
                                    <h4 class="truncate font-semibold text-slate-900"><?= htmlspecialchars($player['name']) ?></h4>
                                    <p class="mt-1 text-sm text-slate-500">Avaliação <?= htmlspecialchars(number_format((float) $player['rating'], 1, ',', '.')) ?></p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?= (int) $player['is_goalkeeper'] === 1 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= (int) $player['is_goalkeeper'] === 1 ? 'Goleiro' : 'Linha' ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-5">
                        <?php $title = 'Sem presença registrada'; $description = 'Volte para a tela anterior e selecione os jogadores presentes.'; require dirname(__DIR__, 2) . '/components/empty-state.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="min-w-0 flex-1 space-y-6 overflow-hidden">
            <?php if ($currentMatch && $currentTeamA && $currentTeamB): ?>
                <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Fechar partida atual</h3>
                            <p class="text-sm text-slate-500">Informe o placar, o vencedor, quem marcou e quem deu assistência.</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Partida <?= (int) ($currentMatch['match_order'] ?? 1) ?></span>
                    </div>

                    <?php if ($matchErrors !== []): ?>
                        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                            <ul class="space-y-1">
                                <?php foreach ($matchErrors as $error): ?>
                                    <li>• <?= htmlspecialchars((string) $error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="/peladas/<?= (int) ($session['id'] ?? 0) ?>/partidas/finalizar" method="post" class="mt-5 space-y-5">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Visual da partida</h4>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Campinho</span>
                            </div>
                            <div class="space-y-4 2xl:grid 2xl:grid-cols-[1fr_auto_1fr] 2xl:items-center 2xl:gap-4 2xl:space-y-0">
                                <div>
                                    <div class="mb-3 flex items-center justify-between gap-2">
                                        <h5 class="font-semibold text-slate-900"><?= htmlspecialchars((string) $currentTeamA['name']) ?></h5>
                                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Time A</span>
                                    </div>
                                    <?php $renderPitch($currentTeamA, 'brand'); ?>
                                </div>
                                <div class="flex items-center justify-center">
                                    <span class="rounded-full bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-soft">VS</span>
                                </div>
                                <div>
                                    <div class="mb-3 flex items-center justify-between gap-2">
                                        <h5 class="font-semibold text-slate-900"><?= htmlspecialchars((string) $currentTeamB['name']) ?></h5>
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Time B</span>
                                    </div>
                                    <?php $renderPitch($currentTeamB, 'emerald'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) $currentMatch['team_a_name']) ?> - gols</span>
                                <input x-model.number="matchForm.score_team_a" min="0" step="1" type="number" name="score_team_a" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) $currentMatch['team_b_name']) ?> - gols</span>
                                <input x-model.number="matchForm.score_team_b" min="0" step="1" type="number" name="score_team_b" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-700">Vencedor</span>
                                <select x-model.number="matchForm.winner_team_id" name="winner_team_id" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                    <option value="0">Selecione</option>
                                    <option value="<?= (int) $currentMatch['team_a_id'] ?>"><?= htmlspecialchars((string) $currentMatch['team_a_name']) ?></option>
                                    <option value="<?= (int) $currentMatch['team_b_id'] ?>"><?= htmlspecialchars((string) $currentMatch['team_b_name']) ?></option>
                                </select>
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-semibold text-slate-700">Completar próximo time</span>
                                <select x-model="matchForm.transfer_mode" name="transfer_mode" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                    <option value="random">Aleatório</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </label>
                        </div>

                        <template x-if="matchForm.transfer_mode === 'manual'">
                            <label class="block space-y-2">
                                <span class="text-sm font-semibold text-slate-700">Jogador do time perdedor que vai completar o próximo time</span>
                                <select x-model="matchForm.transfer_player_id" name="transfer_player_id" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                    <option value="">Selecione</option>
                                    <template x-for="player in loserPlayers()" :key="player.id">
                                        <option :value="player.id" x-text="player.name"></option>
                                    </template>
                                </select>
                            </label>
                        </template>

                        <div class="rounded-3xl bg-slate-50 p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Gols e assistências</h4>
                                    <p class="text-xs text-slate-500">Crie um item para cada gol do placar.</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200" x-text="goalRows().length + ' gol(s)'"></span>
                            </div>

                            <div class="mt-4 space-y-3" x-show="goalRows().length > 0">
                                <template x-for="(goal, index) in goalRows()" :key="'goal-' + index">
                                    <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
                                        <div class="flex items-center justify-between gap-3">
                                            <strong class="text-sm text-slate-900" x-text="'Gol ' + (index + 1)"></strong>
                                            <span class="text-xs text-slate-500" x-text="goal.teamName"></span>
                                        </div>
                                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                                            <label class="space-y-2">
                                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Quem fez</span>
                                                <select :name="`goal_scorer[${index}]`" x-model.number="matchForm.goal_scorers[index]" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                    <option value="0">Selecione</option>
                                                    <template x-for="player in goal.players" :key="player.id">
                                                        <option :value="player.id" x-text="player.name"></option>
                                                    </template>
                                                </select>
                                            </label>
                                            <label class="space-y-2">
                                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Assistência</span>
                                                <select :name="`goal_assist[${index}]`" x-model.number="matchForm.goal_assists[index]" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                    <option value="0">Sem assistência</option>
                                                    <template x-for="player in assistOptions(goal.players, matchForm.goal_scorers[index])" :key="player.id">
                                                        <option :value="player.id" x-text="player.name"></option>
                                                    </template>
                                                </select>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="goalRows().length === 0" class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                Informe o placar acima para liberar o detalhamento dos gols.
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Salvar resultado e gerar próxima partida</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Times sorteados</h3>
                        <p class="text-sm text-slate-500">Abra cada sub aba para ver os jogadores daquele time com a nota numérica.</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Sub abas por time</span>
                </div>

                <?php if ($teams !== []): ?>
                    <div class="mt-5 max-w-full overflow-hidden pb-1">
                        <div class="grid grid-cols-2 gap-2 rounded-3xl bg-slate-100 p-2 sm:grid-cols-3 xl:flex xl:flex-wrap">
                            <?php foreach ($teams as $team): ?>
                                <?php $tabId = 'team-' . (int) $team['team_order']; ?>
                                <button
                                    type="button"
                                    class="min-w-0 rounded-2xl px-4 py-3 text-left text-sm font-semibold transition xl:min-w-[128px]"
                                    :class="activeTab === '<?= $tabId ?>' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'"
                                    @click="activeTab = '<?= $tabId ?>'"
                                >
                                    <span class="block">Time <?= (int) $team['team_order'] ?></span>
                                    <span class="mt-1 block text-xs font-medium <?= ($team['status'] ?? '') === 'active' ? 'text-emerald-600' : 'text-slate-500' ?>">
                                        <?= ($team['status'] ?? '') === 'active' ? 'Partida ativa' : 'Espera' ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <?php foreach ($teams as $team): ?>
                            <?php $tabId = 'team-' . (int) $team['team_order']; ?>
                            <article x-show="activeTab === '<?= $tabId ?>'" x-transition.opacity.duration.150ms class="space-y-4">
                                <div class="space-y-4 xl:flex xl:items-start xl:gap-4 xl:space-y-0">
                                    <div class="min-w-0 rounded-3xl border border-slate-200 p-5 xl:flex-1 xl:p-6">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h4 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($team['name']) ?></h4>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    <?= ($team['status'] ?? '') === 'active' ? 'Esse time está na partida ativa.' : 'Esse time está aguardando na fila.' ?>
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= ($team['status'] ?? '') === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' ?>">
                                                <?= ($team['status'] ?? '') === 'active' ? 'Ativo' : 'Espera' ?>
                                            </span>
                                        </div>

                                        <div class="mt-5">
                                            <?php $renderPitch($team, ($team['status'] ?? '') === 'active' ? 'brand' : 'emerald'); ?>
                                        </div>

                                        <div class="mt-5 space-y-3">
                                            <?php foreach (($team['players'] ?? []) as $player): ?>
                                                <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-4">
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-slate-900"><?= htmlspecialchars($player['name']) ?></p>
                                                        <p class="mt-1 text-sm text-slate-500">Avaliação <?= htmlspecialchars(number_format((float) $player['rating'], 1, ',', '.')) ?></p>
                                                    </div>
                                                    <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center">
                                                        <?php if ((int) ($player['is_goalkeeper'] ?? 0) === 1): ?>
                                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Goleiro</span>
                                                        <?php endif; ?>
                                                        <span class="rounded-full bg-white px-3 py-1 text-sm font-bold text-slate-800 ring-1 ring-slate-200">
                                                            <?= htmlspecialchars(number_format((float) $player['rating'], 1, ',', '.')) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                            <?php if (($team['players'] ?? []) === []): ?>
                                                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                                    Esse time não recebeu jogadores.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-4 xl:w-[260px] xl:grid-cols-1 xl:shrink-0">
                                        <div class="rounded-3xl bg-slate-50 p-5 xl:p-6">
                                            <p class="text-sm font-medium text-slate-500">Jogadores no time</p>
                                            <strong class="mt-3 block text-3xl font-bold text-slate-900"><?= count($team['players'] ?? []) ?></strong>
                                        </div>
                                        <div class="rounded-3xl bg-slate-50 p-5 xl:p-6">
                                            <p class="text-sm font-medium text-slate-500">Soma das avaliações</p>
                                            <strong class="mt-3 block text-3xl font-bold text-slate-900"><?= htmlspecialchars(number_format((float) ($team['total_rating'] ?? 0), 1, ',', '.')) ?></strong>
                                        </div>
                                        <div class="rounded-3xl bg-slate-50 p-5 xl:p-6">
                                            <p class="text-sm font-medium text-slate-500">Goleiros no time</p>
                                            <strong class="mt-3 block text-3xl font-bold text-slate-900"><?= (int) ($team['goalkeeper_count'] ?? 0) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-5">
                        <?php $title = 'Nenhum time sorteado'; $description = 'Crie uma sessão com presença confirmada para gerar os times.'; require dirname(__DIR__, 2) . '/components/empty-state.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<script>
    function sessionPage(config) {
        return {
            activeTab: config.activeTab || 'team-1',
            currentMatch: config.currentMatch,
            teams: config.teams || [],
            matchForm: config.matchForm || {
                winner_team_id: 0,
                score_team_a: 0,
                score_team_b: 0,
                transfer_mode: 'random',
                transfer_player_id: '',
                goal_scorers: [],
                goal_assists: []
            },
            getTeam(teamId) {
                return this.teams.find(team => team.id === Number(teamId)) || null;
            },
            winnerTeam() {
                return this.getTeam(this.matchForm.winner_team_id);
            },
            loserTeam() {
                if (!this.currentMatch || !this.matchForm.winner_team_id) return null;
                const loserId = Number(this.matchForm.winner_team_id) === Number(this.currentMatch.team_a_id)
                    ? Number(this.currentMatch.team_b_id)
                    : Number(this.currentMatch.team_a_id);
                return this.getTeam(loserId);
            },
            loserPlayers() {
                return this.loserTeam()?.players || [];
            },
            teamAGoalRows() {
                return Math.max(0, Number(this.matchForm.score_team_a || 0));
            },
            teamBGoalRows() {
                return Math.max(0, Number(this.matchForm.score_team_b || 0));
            },
            goalRows() {
                const rows = [];
                const teamA = this.getTeam(this.currentMatch?.team_a_id);
                const teamB = this.getTeam(this.currentMatch?.team_b_id);
                for (let i = 0; i < this.teamAGoalRows(); i++) {
                    rows.push({ teamId: teamA?.id || 0, teamName: teamA?.name || 'Time A', players: teamA?.players || [] });
                }
                for (let i = 0; i < this.teamBGoalRows(); i++) {
                    rows.push({ teamId: teamB?.id || 0, teamName: teamB?.name || 'Time B', players: teamB?.players || [] });
                }
                this.ensureGoalArrays(rows.length);
                return rows;
            },
            ensureGoalArrays(length) {
                this.matchForm.goal_scorers = Array.from({ length }, (_, index) => Number(this.matchForm.goal_scorers[index] || 0));
                this.matchForm.goal_assists = Array.from({ length }, (_, index) => Number(this.matchForm.goal_assists[index] || 0));
            },
            assistOptions(players, scorerId) {
                return (players || []).filter(player => Number(player.id) !== Number(scorerId || 0));
            }
        }
    }
</script>
