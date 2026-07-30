<?php
/**
 * @var array $user
 * @var array $recentOrders
 * @var array $errors
 */

use App\Core\Csrf;
use App\Core\View;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Личный кабинет</span>
</nav>

<section class="section">
    <div class="container">
        <h1>Здравствуйте, <?= e($user['full_name']) ?>!</h1>

        <div class="account-layout">
            <?= View::partial('partials/account-nav', ['active' => 'index']) ?>

            <div>
                <div class="card">
                    <h2 class="mt-0">Профиль</h2>
                    <form action="<?= e(url('/account/profile')) ?>" method="post" class="stack">
                        <?= Csrf::field() ?>

                        <div class="field<?= isset($errors['full_name']) ? ' field--error' : '' ?>">
                            <label for="full_name">ФИО</label>
                            <input type="text" id="full_name" name="full_name" required value="<?= e((string) old('full_name', $user['full_name'])) ?>">
                            <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name'][0]) ?></p><?php endif; ?>
                        </div>

                        <div class="field-row field-row--2">
                            <div class="field<?= isset($errors['phone']) ? ' field--error' : '' ?>">
                                <label for="phone">Телефон</label>
                                <input type="tel" id="phone" name="phone" value="<?= e((string) old('phone', (string) $user['phone'])) ?>">
                                <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone'][0]) ?></p><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="email_ro">Email</label>
                                <input type="email" id="email_ro" value="<?= e($user['email']) ?>" disabled>
                                <p class="hint">Email нельзя изменить в этой форме.</p>
                            </div>
                        </div>

                        <div class="field<?= isset($errors['address']) ? ' field--error' : '' ?>">
                            <label for="address">Адрес доставки по умолчанию</label>
                            <input type="text" id="address" name="address" value="<?= e((string) old('address', (string) $user['address'])) ?>">
                            <?php if (isset($errors['address'])): ?><p class="field-error"><?= e($errors['address'][0]) ?></p><?php endif; ?>
                        </div>

                        <button type="submit" class="btn">Сохранить профиль</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="mt-0">Смена пароля</h2>
                    <form action="<?= e(url('/account/password')) ?>" method="post" class="stack">
                        <?= Csrf::field() ?>

                        <div class="field<?= isset($errors['current_password']) ? ' field--error' : '' ?>">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                            <?php if (isset($errors['current_password'])): ?><p class="field-error"><?= e($errors['current_password'][0]) ?></p><?php endif; ?>
                        </div>

                        <div class="field-row field-row--2">
                            <div class="field<?= isset($errors['password']) ? ' field--error' : '' ?>">
                                <label for="new_password">Новый пароль</label>
                                <input type="password" id="new_password" name="password" required minlength="6" autocomplete="new-password">
                                <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>
                            </div>
                            <div class="field<?= isset($errors['password_confirmation']) ? ' field--error' : '' ?>">
                                <label for="password_confirmation">Повторите пароль</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" autocomplete="new-password">
                                <?php if (isset($errors['password_confirmation'])): ?><p class="field-error"><?= e($errors['password_confirmation'][0]) ?></p><?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn">Сменить пароль</button>
                    </form>
                </div>

                <div class="card">
                    <div class="section__head" style="margin-bottom:var(--space-4)">
                        <h2 class="mt-0">Последние заказы</h2>
                        <a class="btn btn--outline btn--sm" href="<?= e(url('/account/orders')) ?>">Вся история</a>
                    </div>
                    <?php if ($recentOrders === []): ?>
                        <p class="text-muted">Вы ещё не оформляли заказы. <a href="<?= e(url('/catalog')) ?>">Перейти в каталог</a></p>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-row">
                                <div class="order-row__meta">
                                    <strong><a href="<?= e(url('/account/orders/' . $order['id'])) ?>">№ <?= e($order['order_number']) ?></a></strong>
                                    <span class="text-muted"><?= date('d.m.Y', strtotime((string) $order['created_at'])) ?> · <?= price((float) $order['total']) ?></span>
                                </div>
                                <?= View::partial('partials/order-status', ['status' => $order['status']]) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
