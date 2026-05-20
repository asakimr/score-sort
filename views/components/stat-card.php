<div class="rounded-3xl bg-white p-4 shadow-soft ring-1 ring-slate-200">
    <p class="text-sm font-medium text-slate-500"><?= htmlspecialchars($label) ?></p>
    <div class="mt-3 flex items-end justify-between">
        <strong class="text-3xl font-bold text-slate-900"><?= htmlspecialchars((string) $value) ?></strong>
        <?php if (!empty($hint)): ?>
            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700"><?= htmlspecialchars($hint) ?></span>
        <?php endif; ?>
    </div>
</div>
