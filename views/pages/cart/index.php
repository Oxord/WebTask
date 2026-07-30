<?php
/**
 * @var array $items
 * @var array $totals
 */

use App\Core\Csrf;
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Корзина</span>
</nav>

<section class="section">
    <div class="container">
        <h1>Корзина</h1>

        <?php if ($items === []): ?>
            <div class="empty-state">
                <h3>Ваша корзина пуста</h3>
                <p>Загляните в каталог — там много интересного для дома.</p>
                <a class="btn" href="<?= e(url('/catalog')) ?>">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div>
                    <table class="cart-table" id="cartTable">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th><span class="visually-hidden">Действия</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php $product = $item['product']; ?>
                                <tr data-product-id="<?= (int) $product['id'] ?>">
                                    <td data-label="Товар">
                                        <div class="cart-item">
                                            <img src="<?= e(url($product['image_path'] ?? '/assets/img/products/product-01.svg')) ?>" alt="<?= e($product['name']) ?>" width="64" height="64" loading="lazy">
                                            <div>
                                                <a href="<?= e(url('/catalog/' . $product['slug'])) ?>"><?= e($product['name']) ?></a>
                                                <?php if ($item['discount_percent'] > 0): ?>
                                                    <div><span class="badge badge--sale">−<?= (int) $item['discount_percent'] ?>%</span></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Цена">
                                        <?php if ($item['discount_percent'] > 0): ?>
                                            <span class="price-old"><?= price($item['unit_price']) ?></span><br>
                                        <?php endif; ?>
                                        <span class="price-new"><?= price($item['final_price']) ?></span>
                                    </td>
                                    <td data-label="Количество">
                                        <form action="<?= e(url('/cart/update')) ?>" method="post" class="js-update-form">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                            <span class="qty-control">
                                                <button type="button" class="js-qty-minus" aria-label="Уменьшить количество">−</button>
                                                <input type="number" name="qty" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $product['stock'] ?>" class="js-qty-input" inputmode="numeric">
                                                <button type="button" class="js-qty-plus" aria-label="Увеличить количество">+</button>
                                            </span>
                                            <noscript><button type="submit" class="btn btn--sm">Обновить</button></noscript>
                                        </form>
                                    </td>
                                    <td data-label="Сумма" class="js-line-total"><?= price($item['line_total']) ?></td>
                                    <td data-label="">
                                        <form action="<?= e(url('/cart/remove')) ?>" method="post" class="remove-form js-remove-form">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                            <button type="submit" class="btn btn--ghost btn--sm js-remove-btn">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <form action="<?= e(url('/cart/clear')) ?>" method="post" style="margin-top:var(--space-4)" onsubmit="return confirm('Очистить корзину полностью?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--ghost">Очистить корзину</button>
                    </form>
                </div>

                <aside class="cart-summary" id="cartSummary">
                    <h2 class="mt-0">Итого</h2>
                    <div class="cart-summary__row">
                        <span>Подытог</span>
                        <span class="js-summary-subtotal"><?= price($totals['subtotal']) ?></span>
                    </div>
                    <div class="cart-summary__row">
                        <span>Скидка</span>
                        <span class="js-summary-discount">−<?= price($totals['discount']) ?></span>
                    </div>
                    <div class="cart-summary__row cart-summary__row--total">
                        <span>К оплате</span>
                        <span class="js-summary-total"><?= price($totals['total']) ?></span>
                    </div>
                    <a class="btn btn--block" href="<?= e(url('/checkout')) ?>">Оформить заказ</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
