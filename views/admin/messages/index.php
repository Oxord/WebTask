<?php
/**
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var bool $unreadOnly
 * @var int $unreadCount
 */

use App\Core\Csrf;
use App\Core\View;
?>
<div class="page-head">
    <h1>Сообщения</h1>
    <p class="page-head__subtitle">
        Всего: <?= (int) $total ?> · Непрочитано: <span class="badge badge--warning"><?= (int) $unreadCount ?></span>
    </p>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/messages')) ?>">
    <div class="filters__field filters__field--checkbox">
        <label class="checkbox">
            <input type="checkbox" name="unread" value="1"<?= $unreadOnly ? ' checked' : '' ?> data-autosubmit>
            Только непрочитанные
        </label>
    </div>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">Сообщений не найдено.</p>
<?php else: ?>
    <div class="message-list">
        <?php foreach ($items as $message): ?>
            <article class="message-card<?= (int) $message['is_read'] === 0 ? ' message-card--unread' : '' ?>">
                <header class="message-card__head">
                    <div>
                        <strong><?= e($message['name']) ?></strong>
                        <span class="message-card__contact"><?= e($message['email']) ?><?= $message['phone'] ? ' · ' . e($message['phone']) : '' ?></span>
                    </div>
                    <time datetime="<?= e($message['created_at']) ?>"><?= e(date('d.m.Y H:i', strtotime($message['created_at']))) ?></time>
                </header>

                <p class="message-card__body"><?= nl2br(e($message['message'])) ?></p>

                <footer class="message-card__foot">
                    <?php if ((int) $message['is_read'] === 0): ?>
                        <span class="badge badge--warning">Новое</span>
                        <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/read')) ?>">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--sm btn--secondary">Отметить прочитанным</button>
                        </form>
                    <?php else: ?>
                        <span class="badge badge--muted">Прочитано</span>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/delete')) ?>" data-confirm="Удалить сообщение?">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--sm btn--danger">Удалить</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>

    <?= View::partial('admin/partials/pagination', [
        'page' => $page,
        'pages' => $pages,
        'basePath' => '/admin/messages',
        'query' => array_filter(['unread' => $unreadOnly ? '1' : null]),
    ]) ?>
<?php endif; ?>
