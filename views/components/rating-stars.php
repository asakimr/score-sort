<?php
$rating = isset($rating) ? max(0, min(5, (float) $rating)) : 0.0;
$sizeClass = $sizeClass ?? 'text-base';
$showValue = $showValue ?? true;
$valueClass = $valueClass ?? 'text-xs text-slate-500';
$wrapperClass = $wrapperClass ?? 'flex items-center gap-2';
$fillWidth = max(0, min(100, $rating * 20));
?>

<div class="min-w-0 <?= htmlspecialchars($wrapperClass) ?>" aria-label="<?= htmlspecialchars(number_format($rating, 1, ',', '.')) ?> de 5 estrelas">
    <div class="relative inline-block max-w-full leading-none <?= htmlspecialchars($sizeClass) ?>" aria-hidden="true">
        <span class="block select-none overflow-hidden text-slate-300">★★★★★</span>
        <span class="pointer-events-none absolute inset-y-0 left-0 block overflow-hidden whitespace-nowrap text-amber-400" style="width: <?= htmlspecialchars((string) $fillWidth) ?>%">★★★★★</span>
    </div>

    <?php if ($showValue): ?>
        <span class="<?= htmlspecialchars($valueClass) ?>"><?= htmlspecialchars(number_format($rating, 1, ',', '.')) ?>/5</span>
    <?php endif; ?>
</div>
