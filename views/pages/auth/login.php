<?php
$flash = $flash ?? null;
$errors = $errors ?? [];
$old = $old ?? [];
?>

<section class="mx-auto w-full max-w-md space-y-6">
    <div class="rounded-[28px] bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-soft sm:p-8">
        <p class="text-sm text-blue-100">Área protegida</p>
        <h2 class="mt-2 text-2xl font-bold">Entrar como supervisor</h2>
        <p class="mt-3 text-sm leading-6 text-blue-50">Somente usuários com perfil de supervisor podem cadastrar jogadores, criar peladas e registrar resultados.</p>
    </div>

    <div class="rounded-[28px] bg-white p-6 shadow-soft ring-1 ring-slate-200 sm:p-7">
        <?php if ($flash): ?>
            <div class="mb-4 rounded-2xl border px-4 py-3 text-sm font-medium <?= ($flash['type'] ?? '') === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
                <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= htmlspecialchars((string) $errors['general']) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="post" class="space-y-5">
            <div>
                <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Usuário</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="<?= htmlspecialchars((string) ($old['username'] ?? '')) ?>"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                    placeholder="ex: supervisor"
                    autocomplete="username"
                >
                <?php if (isset($errors['username'])): ?>
                    <p class="mt-2 text-sm text-rose-600"><?= htmlspecialchars((string) $errors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Senha</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                    placeholder="Sua senha"
                    autocomplete="current-password"
                >
                <?php if (isset($errors['password'])): ?>
                    <p class="mt-2 text-sm text-rose-600"><?= htmlspecialchars((string) $errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Entrar
            </button>
        </form>
    </div>
</section>
