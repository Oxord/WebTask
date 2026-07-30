<?php
/** @var array<string, string[]> $flashes */
$labels = ['success' => 'success', 'error' => 'error', 'info' => 'info'];
?>
<?php if ($flashes !== []): ?>
    <div class="container flashes" aria-live="polite">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash--<?= e($labels[$type] ?? 'info') ?>"><?= e($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
