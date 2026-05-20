<?php
$old = $old ?? [];
$errors = $errors ?? [];
$selectedPlayerIds = array_map('intval', $old['present_player_ids'] ?? []);
$sessionDate = (string) ($old['session_date'] ?? date('Y-m-d'));
$maxPlayersPerMatch = (int) ($old['max_players_per_match'] ?? 10);
$drawMode = (string) ($old['draw_mode'] ?? 'balanced');
$prioritizeGoalkeepers = (int) ($old['prioritize_goalkeepers'] ?? 1) === 1;
?>

<section class="space-y-6" x-data="sessionForm()">
    <?php if ($flash): ?>
        <div class="rounded-3xl border px-4 py-3 text-sm font-medium <?= ($flash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-4 xl:flex xl:items-stretch xl:gap-4">
        <div class="rounded-[28px] bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-soft sm:p-8 xl:flex-1 xl:p-9">
            <p class="text-sm text-blue-100">Passo 1 do fluxo da rodada</p>
            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Monte a sessão do dia e marque quem realmente chegou para jogar.</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-50">Defina a data, o limite por partida, o modo do sorteio e a lista de presença. O resumo já mostra quantos ficam na quadra e quantos sobram para a fila.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:w-[420px] xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Jogadores ativos</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900" x-text="players.length"></strong>
                <p class="mt-2 text-xs text-slate-500">prontos para presença</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Selecionados</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900" x-text="selectedCount"></strong>
                <p class="mt-2 text-xs text-slate-500">confirmados</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Por time</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900" x-text="playersPerTeam"></strong>
                <p class="mt-2 text-xs text-slate-500">jogadores</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Na espera</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900" x-text="waitingCount"></strong>
                <p class="mt-2 text-xs text-slate-500">fora da ativa</p>
            </div>
        </div>
    </div>

    <form action="/peladas" method="post" class="space-y-6 xl:flex xl:items-start xl:gap-6 xl:space-y-0">
        <section class="space-y-4 xl:sticky xl:top-24 xl:w-[420px] xl:shrink-0 xl:self-start">
            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Configuração da sessão</h3>
                        <p class="text-sm text-slate-500">Essas opções serão usadas no sorteio da rodada.</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">MVP</span>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="session-date" class="mb-2 block text-sm font-medium text-slate-700">Data da pelada</label>
                        <input id="session-date" name="session_date" type="date" value="<?= htmlspecialchars($sessionDate) ?>" class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 <?= isset($errors['session_date']) ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' ?>">
                        <?php if (isset($errors['session_date'])): ?>
                            <p class="mt-2 text-sm text-rose-600\"><?= htmlspecialchars($errors['session_date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="max-players-per-match" class="mb-2 block text-sm font-medium text-slate-700">Máximo por partida</label>
                        <input id="max-players-per-match" name="max_players_per_match" x-model.number="maxPlayersPerMatch" type="number" min="2" step="2" value="<?= $maxPlayersPerMatch ?>" class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 <?= isset($errors['max_players_per_match']) ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' ?>">
                        <p class="mt-2 text-xs text-slate-500">Ex.: 10 gera dois times de 5.</p>
                        <?php if (isset($errors['max_players_per_match'])): ?>
                            <p class="mt-2 text-sm text-rose-600"><?= htmlspecialchars($errors['max_players_per_match']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
                <h3 class="text-lg font-semibold text-slate-900">Modo do sorteio</h3>
                <p class="mt-1 text-sm text-slate-500">Escolha o comportamento desejado antes de montar os times.</p>

                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <label class="cursor-pointer rounded-2xl border p-4 transition" :class="drawMode === 'balanced' ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-slate-200 bg-white text-slate-700'">
                        <input type="radio" name="draw_mode" value="balanced" class="sr-only" x-model="drawMode" <?= $drawMode === 'balanced' ? 'checked' : '' ?>>
                        <strong class="block text-sm">Balanceado</strong>
                        <span class="mt-1 block text-xs leading-5 text-inherit/80">Equilibra as estrelas entre os times com variação controlada.</span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border p-4 transition" :class="drawMode === 'random' ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-slate-200 bg-white text-slate-700'">
                        <input type="radio" name="draw_mode" value="random" class="sr-only" x-model="drawMode" <?= $drawMode === 'random' ? 'checked' : '' ?>>
                        <strong class="block text-sm">Aleatório</strong>
                        <span class="mt-1 block text-xs leading-5 text-inherit/80">Sorteio livre entre os presentes.</span>
                    </label>
                </div>

                <label class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Priorizar goleiros</p>
                        <p class="text-xs text-slate-500">Tenta reservar um goleiro para cada time da partida ativa.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold" :class="prioritizeGoalkeepers ? 'text-brand-700' : 'text-slate-500'" x-text="prioritizeGoalkeepers ? 'Ligado' : 'Desligado'"></span>
                        <input type="checkbox" name="prioritize_goalkeepers" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" x-model="prioritizeGoalkeepers" <?= $prioritizeGoalkeepers ? 'checked' : '' ?>>
                    </div>
                </label>
            </div>

            <div class="rounded-[28px] bg-slate-900 p-5 text-white shadow-soft sm:p-6 xl:p-6">
                <h3 class="text-lg font-semibold">Resumo rápido</h3>
                <dl class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                        <dt>Jogadores por time</dt>
                        <dd class="font-semibold text-white" x-text="playersPerTeam"></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                        <dt>Na partida ativa</dt>
                        <dd class="font-semibold text-white" x-text="activeMatchCount"></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                        <dt>Fora / espera</dt>
                        <dd class="font-semibold text-white" x-text="waitingCount"></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                        <dt>Faltam para fechar o próximo time</dt>
                        <dd class="font-semibold text-white" x-text="missingForNextTeam"></dd>
                    </div>
                </dl>

                <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">Salvar sessão e presença</button>
            </div>
        </section>

        <section class="min-w-0 flex-1 rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Lista de presença</h3>
                    <p class="text-sm text-slate-500">Selecione quem está no local para jogar hoje.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="selectAll()" class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100">Selecionar todos</button>
                    <button type="button" @click="clearAll()" class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100">Limpar</button>
                </div>
            </div>

            <?php if (isset($errors['player_ids'])): ?>
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    <?= htmlspecialchars($errors['player_ids']) ?>
                </div>
            <?php endif; ?>

            <?php if ($players !== []): ?>
                <div class="mt-5 space-y-3 xl:space-y-2">
                    <?php foreach ($players as $player): ?>
                        <?php $playerId = (int) $player['id']; ?>
                        <label class="group block cursor-pointer rounded-3xl border border-slate-200 p-4 transition hover:border-brand-200 hover:bg-brand-50/40 xl:px-5 xl:py-4">
                            <div class="space-y-3 xl:flex xl:items-center xl:justify-between xl:gap-4 xl:space-y-0">
                                <div class="flex items-start gap-4 min-w-0 flex-1">
                                    <input
                                        type="checkbox"
                                        name="player_ids[]"
                                        value="<?= $playerId ?>"
                                        class="mt-1 h-5 w-5 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                        x-model="selectedPlayers"
                                        <?= in_array($playerId, $selectedPlayerIds, true) ? 'checked' : '' ?>
                                    >
                                    <div class="min-w-0 flex-1">
                                        <h4 class="truncate font-semibold text-slate-900"><?= htmlspecialchars($player['name']) ?></h4>
                                        <div class="mt-2">
                                            <?php $rating = (float) $player['rating']; $sizeClass = 'text-base'; $valueClass = 'text-sm text-slate-500'; require dirname(__DIR__, 2) . '/components/rating-stars.php'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 xl:shrink-0">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $player['is_goalkeeper'] === 1 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= (int) $player['is_goalkeeper'] === 1 ? 'Goleiro' : 'Linha' ?>
                                    </span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-5">
                    <?php $title = 'Nenhum jogador ativo'; $description = 'Cadastre ou reative jogadores antes de marcar presença.'; require dirname(__DIR__, 2) . '/components/empty-state.php'; ?>
                </div>
            <?php endif; ?>
        </section>
    </form>
</section>

<script>
    function sessionForm() {
        return {
            players: <?= json_encode(array_map(static fn (array $player): array => ['id' => (int) $player['id']], $players), JSON_UNESCAPED_UNICODE) ?>,
            selectedPlayers: <?= json_encode($selectedPlayerIds, JSON_UNESCAPED_UNICODE) ?>,
            maxPlayersPerMatch: <?= $maxPlayersPerMatch ?>,
            drawMode: '<?= htmlspecialchars($drawMode, ENT_QUOTES) ?>',
            prioritizeGoalkeepers: <?= $prioritizeGoalkeepers ? 'true' : 'false' ?>,
            selectAll() {
                this.selectedPlayers = this.players.map(player => player.id);
            },
            clearAll() {
                this.selectedPlayers = [];
            },
            get selectedCount() {
                return this.selectedPlayers.length;
            },
            get playersPerTeam() {
                const total = Number(this.maxPlayersPerMatch || 0);
                return total >= 2 ? Math.floor(total / 2) : 0;
            },
            get activeMatchCount() {
                return Math.min(this.selectedCount, Number(this.maxPlayersPerMatch || 0));
            },
            get waitingCount() {
                return Math.max(0, this.selectedCount - this.activeMatchCount);
            },
            get missingForNextTeam() {
                const perTeam = this.playersPerTeam;
                if (perTeam === 0 || this.waitingCount === 0) {
                    return 0;
                }

                const remainder = this.waitingCount % perTeam;
                return remainder === 0 ? 0 : perTeam - remainder;
            }
        }
    }
</script>
