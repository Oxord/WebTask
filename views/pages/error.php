<?php
/**
 * @var int $status
 * @var string $title
 * @var string $detail
 */
?>
<section class="section error-page">
    <div class="container">
        <div class="error-page__code"><?= (int) $status ?></div>
        <h1><?= e($title) ?></h1>
        <p class="text-muted" style="max-width:52ch;margin:0 auto var(--space-5)"><?= e($detail) ?></p>
        <div class="cluster" style="justify-content:center">
            <a class="btn" href="<?= e(url('/')) ?>">На главную</a>
            <a class="btn btn--outline" href="<?= e(url('/catalog')) ?>">В каталог</a>
        </div>
    </div>
</section>
