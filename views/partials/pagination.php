<?php
/**
 * @var int $page
 * @var int $pages
 * @var string $baseUrl  URL текущей страницы без параметра page (с завершающим ? или &)
 */

if ($pages <= 1) {
    return;
}

$link = static fn (int $p): string => $baseUrl . 'page=' . $p;
$window = 2;
?>
<nav class="pagination" aria-label="Постраничная навигация">
    <a href="<?= e($link(max(1, $page - 1))) ?>" class="<?= $page <= 1 ? 'is-disabled' : '' ?>" <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>‹</a>

    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <?php if ($p === 1 || $p === $pages || abs($p - $page) <= $window): ?>
            <?php if ($p === $page): ?>
                <span class="is-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= e($link($p)) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php elseif (abs($p - $page) === $window + 1): ?>
            <span>…</span>
        <?php endif; ?>
    <?php endfor; ?>

    <a href="<?= e($link(min($pages, $page + 1))) ?>" class="<?= $page >= $pages ? 'is-disabled' : '' ?>" <?= $page >= $pages ? 'aria-disabled="true"' : '' ?>>›</a>
</nav>
