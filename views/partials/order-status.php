<?php
/** @var string $status */

$labels = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'completed' => 'Завершён',
    'cancelled' => 'Отменён',
];
$label = $labels[$status] ?? $status;
?>
<span class="status-pill status-pill--<?= e($status) ?>"><?= e($label) ?></span>
