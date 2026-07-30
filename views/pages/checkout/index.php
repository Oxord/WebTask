<?php
/**
 * @var array $items
 * @var array $totals
 * @var array|null $user
 * @var array $errors
 */

use App\Core\Csrf;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> /
    <a href="<?= e(url('/cart')) ?>">Корзина</a> /
    <span aria-current="page">Оформление заказа</span>
</nav>

<section class="section">
    <div class="container">
        <h1>Оформление заказа</h1>

        <div class="checkout-layout">
            <form action="<?= e(url('/checkout')) ?>" method="post" class="card">
                <?= Csrf::field() ?>

                <div class="field<?= isset($errors['customer_name']) ? ' field--error' : '' ?>">
                    <label for="customer_name">ФИО получателя</label>
                    <input type="text" id="customer_name" name="customer_name" required
                           value="<?= e((string) old('customer_name', $user['full_name'] ?? '')) ?>">
                    <?php if (isset($errors['customer_name'])): ?><p class="field-error"><?= e($errors['customer_name'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field-row field-row--2">
                    <div class="field<?= isset($errors['customer_email']) ? ' field--error' : '' ?>">
                        <label for="customer_email">Email</label>
                        <input type="email" id="customer_email" name="customer_email" required
                               value="<?= e((string) old('customer_email', $user['email'] ?? '')) ?>">
                        <?php if (isset($errors['customer_email'])): ?><p class="field-error"><?= e($errors['customer_email'][0]) ?></p><?php endif; ?>
                    </div>
                    <div class="field<?= isset($errors['customer_phone']) ? ' field--error' : '' ?>">
                        <label for="customer_phone">Телефон</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required placeholder="+7 900 000-00-00"
                               value="<?= e((string) old('customer_phone', $user['phone'] ?? '')) ?>">
                        <?php if (isset($errors['customer_phone'])): ?><p class="field-error"><?= e($errors['customer_phone'][0]) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="field<?= isset($errors['shipping_address']) ? ' field--error' : '' ?>">
                    <label for="shipping_address">Адрес доставки</label>
                    <input type="text" id="shipping_address" name="shipping_address" required
                           value="<?= e((string) old('shipping_address', $user['address'] ?? '')) ?>">
                    <?php if (isset($errors['shipping_address'])): ?><p class="field-error"><?= e($errors['shipping_address'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['comment']) ? ' field--error' : '' ?>">
                    <label for="comment">Комментарий к заказу (необязательно)</label>
                    <textarea id="comment" name="comment" maxlength="2000"><?= e((string) old('comment')) ?></textarea>
                    <?php if (isset($errors['comment'])): ?><p class="field-error"><?= e($errors['comment'][0]) ?></p><?php endif; ?>
                </div>

                <button type="submit" class="btn btn--block">Подтвердить заказ</button>
            </form>

            <aside class="cart-summary">
                <h2 class="mt-0">Ваш заказ</h2>
                <div class="order-summary-list">
                    <?php foreach ($items as $item): ?>
                        <div class="order-summary-item">
                            <span>
                                <?= e($item['product']['name']) ?>
                                <small><?= (int) $item['qty'] ?> × <?= price($item['final_price']) ?></small>
                            </span>
                            <span><?= price($item['line_total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary__row">
                    <span>Подытог</span>
                    <span><?= price($totals['subtotal']) ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Скидка</span>
                    <span>−<?= price($totals['discount']) ?></span>
                </div>
                <div class="cart-summary__row cart-summary__row--total">
                    <span>К оплате</span>
                    <span><?= price($totals['total']) ?></span>
                </div>
            </aside>
        </div>
    </div>
</section>
