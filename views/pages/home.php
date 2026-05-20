<section class="space-y-6">
    <div class="grid gap-4 2xl:grid-cols-[1.55fr_0.85fr]">
        <div class="rounded-[28px] bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-soft sm:p-8 xl:p-9 2xl:p-10">
            <p class="text-sm font-medium text-blue-100">Organize a rodada sem planilha e sem confusão</p>
            <h2 class="mt-3 max-w-2xl text-2xl font-bold leading-tight sm:text-3xl">Cadastre os jogadores, marque presença e gere os times em uma interface feita para celular e desktop.</h2>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-blue-50 sm:text-base">Agora o fluxo já cobre cadastro, presença da sessão e sorteio automático com times ativos e fila de espera.</p>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="/peladas/nova" class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 transition hover:bg-slate-100">Marcar presença agora</a>
                <a href="/jogadores" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Gerenciar jogadores</a>
            </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-7">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-slate-500">Status rápido</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Última sessão</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <?= $latestSession ? htmlspecialchars((string) $latestSession['status']) : 'sem sessão' ?>
                </span>
            </div>

            <?php if ($latestSession): ?>
                <dl class="mt-5 space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <dt>Data</dt>
                        <dd class="font-semibold text-slate-900"><?= htmlspecialchars((string) $latestSession['session_date']) ?></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <dt>Presentes</dt>
                        <dd class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($latestSession['present_count'] ?? 0)) ?></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <dt>Limite por partida</dt>
                        <dd class="font-semibold text-slate-900"><?= htmlspecialchars((string) $latestSession['max_players_per_match']) ?></dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <dt>Modo</dt>
                        <dd class="font-semibold text-slate-900"><?= htmlspecialchars((string) $latestSession['draw_mode']) ?></dd>
                    </div>
                </dl>

                <a href="/peladas/<?= (int) $latestSession['id'] ?>" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Ver times da sessão</a>
            <?php else: ?>
                <div class="mt-5">
                    <?php $title = 'Nenhuma pelada criada'; $description = 'Crie a primeira sessão para marcar presença e começar a testar o fluxo completo.'; require dirname(__DIR__) . '/components/empty-state.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <?php $label = 'Jogadores ativos'; $value = $stats['activePlayers']; $hint = 'cadastro'; require dirname(__DIR__) . '/components/stat-card.php'; ?>
        <?php $label = 'Presentes na última'; $value = $stats['presenceConfirmed']; $hint = 'sessão'; require dirname(__DIR__) . '/components/stat-card.php'; ?>
        <?php $label = 'Times na espera'; $value = $stats['waitingTeams']; $hint = 'fila'; require dirname(__DIR__) . '/components/stat-card.php'; ?>
    </div>

    <div class="grid gap-6 2xl:grid-cols-[1.25fr_0.95fr]">
        <section class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Como usar na prática</h3>
                    <p class="text-sm text-slate-500">Fluxo curto para organizar a pelada do dia.</p>
                </div>
                <a href="/peladas/nova" class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Criar nova sessão</a>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl bg-slate-50 p-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700">1</span>
                    <h4 class="mt-4 font-semibold text-slate-900">Cadastre os jogadores</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Defina nota de 0 a 5, status ativo e quem pode jogar no gol.</p>
                </article>
                <article class="rounded-3xl bg-slate-50 p-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700">2</span>
                    <h4 class="mt-4 font-semibold text-slate-900">Marque presença</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Selecione só quem chegou para jogar e configure o formato da partida.</p>
                </article>
                <article class="rounded-3xl bg-slate-50 p-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700">3</span>
                    <h4 class="mt-4 font-semibold text-slate-900">Abra os times</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">O sistema mostra Time 1, Time 2, Time 3 e assim por diante com a nota de cada jogador.</p>
                </article>
            </div>
        </section>

        <section class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:p-7">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Jogadores recentes</h3>
                    <p class="text-sm text-slate-500">Prévia dos cadastros disponíveis.</p>
                </div>
                <a href="/jogadores" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Abrir lista</a>
            </div>

            <?php if ($players !== []): ?>
                <div class="mt-5 space-y-3">
                    <?php foreach ($players as $player): ?>
                        <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-4">
                            <div class="min-w-0">
                                <h4 class="truncate font-semibold text-slate-900"><?= htmlspecialchars($player['name']) ?></h4>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <?php $rating = (float) $player['rating']; $sizeClass = 'text-base'; $valueClass = 'text-sm text-slate-500'; require dirname(__DIR__) . '/components/rating-stars.php'; ?>
                                    <span class="text-sm text-slate-400">·</span>
                                    <span class="text-sm text-slate-500"><?= (int) $player['is_goalkeeper'] === 1 ? 'Goleiro' : 'Linha' ?></span>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?= (int) $player['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                <?= (int) $player['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mt-5">
                    <?php $title = 'Nenhum jogador cadastrado'; $description = 'Assim que você adicionar os primeiros nomes, eles aparecem aqui.'; require dirname(__DIR__) . '/components/empty-state.php'; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
