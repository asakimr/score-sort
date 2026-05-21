<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1d4ed8">
    <title><?= htmlspecialchars(($title ?? 'Pelada Manager') . ' | ' . ($appName ?? 'Pelada Manager')) ?></title>
    <link rel="manifest" href="/manifest.webmanifest">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef6ff',
                            100: '#d9ebff',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            700: '#1e40af',
                            900: '#172554'
                        }
                    },
                    boxShadow: {
                        soft: '0 12px 32px rgba(15, 23, 42, 0.08)'
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen overflow-x-clip bg-slate-100 text-slate-900 antialiased">
    <?php
    $authUser = $authUser ?? null;
    $isSupervisor = is_array($authUser) && (($authUser['role'] ?? 'viewer') === 'supervisor');
    ?>
    <div class="min-h-screen overflow-x-clip bg-slate-100">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex w-full max-w-[1680px] items-center justify-between gap-4 px-4 py-4 sm:px-6 xl:px-8 2xl:px-10">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Pelada Manager</p>
                    <div class="mt-2 flex items-center gap-3">
                        <h1 class="truncate text-lg font-bold text-slate-900 sm:text-xl"><?= htmlspecialchars($headerTitle ?? ($title ?? 'Dashboard')) ?></h1>
                        <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 md:inline-flex">MVP web</span>
                    </div>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <a href="/" class="rounded-2xl px-4 py-2 text-sm font-semibold transition <?= ($activeNav ?? 'home') === 'home' ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">Início</a>
                    <?php if ($isSupervisor): ?>
                        <a href="/jogadores" class="rounded-2xl px-4 py-2 text-sm font-semibold transition <?= ($activeNav ?? '') === 'players' ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">Jogadores</a>
                        <a href="/peladas/nova" class="rounded-2xl px-4 py-2 text-sm font-semibold transition <?= ($activeNav ?? '') === 'sessions' ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">Presença</a>
                        <a href="/peladas/nova" class="inline-flex items-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">Nova pelada</a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-2">
                    <?php if ($isSupervisor): ?>
                        <div class="hidden rounded-2xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 sm:block">
                            <?= htmlspecialchars((string) ($authUser['username'] ?? 'supervisor')) ?>
                        </div>
                        <form action="/logout" method="post" class="hidden sm:block">
                            <button type="submit" class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Sair</button>
                        </form>
                        <a href="/peladas/nova" class="inline-flex items-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 md:hidden">Nova</a>
                    <?php else: ?>
                        <a href="/login" class="inline-flex items-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">Entrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="mx-auto grid w-full max-w-[1680px] gap-5 px-4 py-5 sm:px-6 lg:grid-cols-[250px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,1fr)] xl:px-8 2xl:px-10 lg:py-7 xl:gap-7">
            <aside class="hidden lg:block">
                <div class="sticky top-24 space-y-3 rounded-3xl bg-white p-4 shadow-soft ring-1 ring-slate-200 xl:p-5">
                    <p class="px-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Navegação</p>
                    <a href="/" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold transition <?= ($activeNav ?? 'home') === 'home' ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                        <span>Dashboard</span>
                        <span>🏠</span>
                    </a>
                    <?php if ($isSupervisor): ?>
                        <a href="/jogadores" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold transition <?= ($activeNav ?? '') === 'players' ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                            <span>Jogadores</span>
                            <span>👥</span>
                        </a>
                        <a href="/peladas/nova" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold transition <?= ($activeNav ?? '') === 'sessions' ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                            <span>Presença e sorteio</span>
                            <span>⚽</span>
                        </a>
                        <form action="/logout" method="post" class="pt-2">
                            <button type="submit" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                <span>Sair</span>
                                <span>↩</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="/login" class="flex items-center justify-between rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                            <span>Entrar como supervisor</span>
                            <span>🔐</span>
                        </a>
                    <?php endif; ?>
                </div>
            </aside>

            <main class="min-w-0 overflow-x-clip pb-24 lg:pb-0">
                <?= $content ?>
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden">
            <div class="mx-auto grid max-w-screen-md <?= $isSupervisor ? 'grid-cols-3' : 'grid-cols-2' ?> gap-2 px-4 py-3 text-xs font-medium text-slate-500">
                <a href="/" class="rounded-2xl px-3 py-2 text-center transition <?= ($activeNav ?? 'home') === 'home' ? 'bg-brand-50 text-brand-700' : 'hover:bg-slate-100 hover:text-slate-900' ?>">Início</a>
                <?php if ($isSupervisor): ?>
                    <a href="/jogadores" class="rounded-2xl px-3 py-2 text-center transition <?= ($activeNav ?? '') === 'players' ? 'bg-brand-50 text-brand-700' : 'hover:bg-slate-100 hover:text-slate-900' ?>">Jogadores</a>
                    <a href="/peladas/nova" class="rounded-2xl px-3 py-2 text-center transition <?= ($activeNav ?? '') === 'sessions' ? 'bg-brand-50 text-brand-700' : 'hover:bg-slate-100 hover:text-slate-900' ?>">Presença</a>
                <?php else: ?>
                    <a href="/login" class="rounded-2xl px-3 py-2 text-center transition <?= ($activeNav ?? '') === 'login' ? 'bg-brand-50 text-brand-700' : 'hover:bg-slate-100 hover:text-slate-900' ?>">Entrar</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
</body>
</html>
