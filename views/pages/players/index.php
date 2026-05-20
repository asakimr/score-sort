<?php
$formMode = $editingPlayer ? 'edit' : 'create';
$formAction = $editingPlayer ? '/jogadores/' . (int) $editingPlayer['id'] : '/jogadores';
$formTitle = $editingPlayer ? 'Editar jogador' : 'Novo jogador';
$formDescription = $editingPlayer ? 'Atualize nota, status e preferência para goleiro.' : 'Cadastre os jogadores da pelada com nota de 0 a 5 em passos de 0,5.';

$source = $old !== [] ? $old : ($editingPlayer ?? []);
$nameValue = (string) ($source['name'] ?? '');
$ratingValue = (string) ($source['rating'] ?? '0');
$ratingNumber = (float) str_replace(',', '.', $ratingValue);
$isGoalkeeper = (int) ($source['is_goalkeeper'] ?? 0) === 1;
$isActive = array_key_exists('is_active', $source) ? (int) $source['is_active'] === 1 : true;
?>

<section class="space-y-6" x-data="playerForm({ formOpen: <?= $editingPlayer ? 'true' : 'false' ?>, initialRating: <?= json_encode($ratingNumber, JSON_UNESCAPED_UNICODE) ?> })">
    <?php if ($flash): ?>
        <div class="rounded-3xl border px-4 py-3 text-sm font-medium <?= ($flash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-4 xl:flex xl:items-stretch xl:gap-4">
        <div class="rounded-[28px] bg-gradient-to-br from-brand-600 to-brand-900 p-6 text-white shadow-soft sm:p-8 xl:flex-1 xl:p-9">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm text-blue-100">Cadastro mobile-first e desktop-ready</p>
                    <h2 class="mt-2 text-2xl font-bold">Gerencie jogadores, notas e goleiros.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-blue-50">O cadastro já permite criar, editar e ativar/inativar jogadores usando SQLite, com seleção visual refinada para meia estrela e estrela cheia.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-slate-100"
                    @click="formOpen = !formOpen; if (formOpen) { $nextTick(() => document.getElementById('player-name')?.focus()) }"
                >
                    <span x-text="formOpen ? 'Fechar formulário' : 'Adicionar jogador'"></span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:w-[420px] xl:grid-cols-2">
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Total</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= count($players) ?></strong>
                <p class="mt-2 text-xs text-slate-500">cadastros</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Ativos</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= count(array_filter($players, static fn ($player) => (int) $player['is_active'] === 1)) ?></strong>
                <p class="mt-2 text-xs text-slate-500">disponíveis</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Goleiros</p>
                <strong class="mt-4 block text-3xl font-bold text-slate-900"><?= count(array_filter($players, static fn ($player) => (int) $player['is_goalkeeper'] === 1)) ?></strong>
                <p class="mt-2 text-xs text-slate-500">preferenciais</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Ação</p>
                <a href="/peladas/nova" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Marcar presença</a>
            </div>
        </div>
    </div>

    <div class="space-y-6 xl:flex xl:items-start xl:gap-6">
        <section x-show="formOpen" x-transition class="rounded-[28px] bg-white p-5 shadow-soft ring-1 ring-slate-200 sm:p-6 xl:sticky xl:top-24 xl:w-[420px] xl:shrink-0 xl:self-start xl:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900"><?= $formTitle ?></h3>
                    <p class="text-sm text-slate-500"><?= $formDescription ?></p>
                </div>
                <?php if ($editingPlayer): ?>
                    <a href="/jogadores" class="rounded-2xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Cancelar</a>
                <?php endif; ?>
            </div>

            <form action="<?= $formAction ?>" method="post" class="mt-5 space-y-4">
                <div>
                    <label for="player-name" class="mb-2 block text-sm font-medium text-slate-700">Nome do jogador</label>
                    <input
                        id="player-name"
                        name="name"
                        type="text"
                        maxlength="80"
                        value="<?= htmlspecialchars($nameValue) ?>"
                        placeholder="Ex.: João Silva"
                        class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100 <?= isset($errors['name']) ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' ?>"
                    >
                    <?php if (isset($errors['name'])): ?>
                        <p class="mt-2 text-sm text-rose-600"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="player-rating" class="block text-sm font-medium text-slate-700">Nota / estrelas</label>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700" x-text="ratingLabel"></span>
                    </div>

                    <input type="hidden" name="rating" :value="formattedRating">

                    <div class="rounded-3xl border p-4 <?= isset($errors['rating']) ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-slate-50' ?>">
                        <div class="flex items-center justify-center gap-2 sm:gap-3">
                            <template x-for="star in stars" :key="star.index">
                                <button
                                    type="button"
                                    class="group relative flex h-12 w-12 items-center justify-center rounded-2xl border transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:h-14 sm:w-14"
                                    :class="starButtonClass(star.index)"
                                    :aria-label="starAriaLabel(star.index)"
                                >
                                    <span class="absolute inset-y-0 left-0 z-10 w-1/2" @click.stop="setRating(star.index + 0.5)"></span>
                                    <span class="absolute inset-y-0 right-0 z-10 w-1/2" @click.stop="setRating(star.index + 1)"></span>

                                    <svg viewBox="0 0 24 24" class="h-8 w-8 sm:h-9 sm:w-9" aria-hidden="true">
                                        <defs>
                                            <linearGradient :id="`star-gradient-${star.index}`" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" :stop-color="starFill(star.index + 1) > 0 ? '#f59e0b' : '#cbd5e1'"></stop>
                                                <stop :offset="`${starFill(star.index + 1)}%`" :stop-color="starFill(star.index + 1) > 0 ? '#f59e0b' : '#cbd5e1'"></stop>
                                                <stop :offset="`${starFill(star.index + 1)}%`" stop-color="#e2e8f0"></stop>
                                                <stop offset="100%" stop-color="#e2e8f0"></stop>
                                            </linearGradient>
                                        </defs>
                                        <path
                                            :fill="`url(#star-gradient-${star.index})`"
                                            stroke="#cbd5e1"
                                            stroke-width="1.2"
                                            d="M12 2.75l2.9 5.88 6.49.94-4.69 4.57 1.11 6.46L12 17.55 6.19 20.6l1.11-6.46L2.61 9.57l6.49-.94L12 2.75z"
                                        ></path>
                                    </svg>
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 text-xs text-slate-500">
                            <span>0 = vazio · 0,5 = meia · 1 = cheia</span>
                            <button type="button" class="rounded-full border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 transition hover:bg-slate-100" @click="setRating(0)">Limpar</button>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Toque no lado esquerdo para meia estrela ou no lado direito para estrela inteira. Vai de 0 a 5.</p>
                    <?php if (isset($errors['rating'])): ?>
                        <p class="mt-2 text-sm text-rose-600"><?= htmlspecialchars($errors['rating']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4">
                        <div>
                            <span class="block text-sm font-medium text-slate-700">Pode jogar no gol</span>
                            <span class="block text-xs text-slate-500">Usado para priorizar goleiros no sorteio.</span>
                        </div>
                        <input name="is_goalkeeper" type="checkbox" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" <?= $isGoalkeeper ? 'checked' : '' ?>>
                    </label>

                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4">
                        <div>
                            <span class="block text-sm font-medium text-slate-700">Jogador ativo</span>
                            <span class="block text-xs text-slate-500">Ativos aparecem por padrão na presença.</span>
                        </div>
                        <input name="is_active" type="checkbox" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" <?= $isActive ? 'checked' : '' ?>>
                    </label>
                </div>

                <button type="submit" class="w-full rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700">
                    <?= $editingPlayer ? 'Salvar alterações' : 'Cadastrar jogador' ?>
                </button>
            </form>
        </section>

        <section class="min-w-0 flex-1 space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Lista de jogadores</h3>
                    <p class="text-sm text-slate-500">Ativos aparecem primeiro e já ficam prontos para presença.</p>
                </div>
                <?php if (!$editingPlayer): ?>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 xl:hidden"
                        @click="formOpen = true; $nextTick(() => document.getElementById('player-name')?.focus())"
                    >
                        Abrir formulário
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($players !== []): ?>
                <div class="space-y-3 xl:space-y-2">
                    <?php foreach ($players as $player): ?>
                        <article class="rounded-[28px] bg-white p-4 shadow-soft ring-1 ring-slate-200 xl:px-5 xl:py-4">
                            <div class="space-y-4 xl:flex xl:items-center xl:justify-between xl:gap-4 xl:space-y-0">
                                <div class="min-w-0 xl:flex xl:min-w-0 xl:flex-1 xl:items-center xl:gap-4">
                                    <div class="min-w-0">
                                        <h4 class="truncate text-base font-semibold text-slate-900"><?= htmlspecialchars($player['name']) ?></h4>
                                        <div class="mt-2 xl:mt-1">
                                            <?php $rating = (float) $player['rating']; $sizeClass = 'text-lg'; $valueClass = 'text-sm text-slate-500'; require dirname(__DIR__, 2) . '/components/rating-stars.php'; ?>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 xl:mt-0 xl:ml-auto xl:justify-end">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $player['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                            <?= (int) $player['is_active'] === 1 ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                            <?= (int) $player['is_goalkeeper'] === 1 ? 'Pode jogar no gol' : 'Jogador de linha' ?>
                                        </span>
                                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700">
                                            ID #<?= (int) $player['id'] ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 xl:w-[220px] xl:shrink-0">
                                    <a href="/jogadores/<?= (int) $player['id'] ?>/editar" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-slate-800">Editar</a>
                                    <form action="/jogadores/<?= (int) $player['id'] ?>/toggle-active" method="post">
                                        <button type="submit" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100">
                                            <?= (int) $player['is_active'] === 1 ? 'Inativar' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php $title = 'Sem jogadores cadastrados'; $description = 'Use o botão “Adicionar jogador” para criar o primeiro nome da pelada.'; require dirname(__DIR__, 2) . '/components/empty-state.php'; ?>
            <?php endif; ?>
        </section>
    </div>
</section>

<script>
    function playerForm(config) {
        return {
            formOpen: Boolean(config.formOpen),
            rating: Number(config.initialRating ?? 0),
            stars: Array.from({ length: 5 }, (_, index) => ({ index })),
            setRating(value) {
                const normalized = Math.max(0, Math.min(5, Math.round(Number(value) * 2) / 2));
                this.rating = normalized;
            },
            starFill(position) {
                const diff = this.rating - (position - 1);
                if (diff >= 1) {
                    return 100;
                }
                if (diff >= 0.5) {
                    return 50;
                }
                return 0;
            },
            starButtonClass(index) {
                const fill = this.starFill(index + 1);
                if (fill > 0) {
                    return 'border-amber-200 bg-amber-50 hover:bg-amber-100';
                }
                return 'border-slate-200 bg-white hover:bg-slate-100';
            },
            starAriaLabel(index) {
                return `Definir ${index + 1} estrela${index === 0 ? '' : 's'}`;
            },
            get formattedRating() {
                return this.rating.toFixed(1);
            },
            get ratingLabel() {
                return `${this.formattedRating.replace('.', ',')} / 5`;
            },
        };
    }
</script>
