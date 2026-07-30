<?php
/**
 * @var array $product
 * @var \App\Core\Auth|null $auth
 */

use App\Core\Csrf;

$discount = (int) ($product['discount_percent'] ?? 0);
$finalPrice = (float) ($product['final_price'] ?? $product['price']);
$stock = (int) $product['stock'];
$inStock = $stock > 0;
?>
<article class="product-card">
    <div class="product-card__media">
        <div class="product-card__badges">
            <?php if ($discount > 0): ?>
                <span class="badge badge--sale">−<?= $discount ?>%</span>
            <?php endif; ?>
            <?php if (!$inStock): ?>
                <span class="badge badge--out">Нет в наличии</span>
            <?php endif; ?>
        </div>
        <a href="<?= e(url('/catalog/' . $product['slug'])) ?>">
            <img src="<?= e(url($product['image_path'] ?? '/assets/img/products/product-01.svg')) ?>"
                 alt="<?= e($product['name']) ?>" loading="lazy" width="400" height="300">
        </a>
    </div>
    <div class="product-card__body">
        <h3 class="product-card__title"><a href="<?= e(url('/catalog/' . $product['slug'])) ?>"><?= e($product['name']) ?></a></h3>
        <div class="product-card__price">
            <?php if ($discount > 0): ?>
                <span class="price-old"><?= price((float) $product['price']) ?></span>
                <span class="price-new"><?= price($finalPrice) ?></span>
            <?php else: ?>
                <span class="price-new"><?= price($finalPrice) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-card__foot">
        <?php if ($inStock): ?>
            <form action="<?= e(url('/cart/add')) ?>" method="post" class="js-add-to-cart-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="btn js-add-to-cart">В корзину</button>
            </form>
        <?php else: ?>
            <button type="button" class="btn" disabled aria-disabled="true">Нет в наличии</button>
        <?php endif; ?>
    </div>
</article>
