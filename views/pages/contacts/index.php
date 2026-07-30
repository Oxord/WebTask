<?php
/** @var array $errors */

use App\Core\Csrf;

$mapSrc = 'https://www.openstreetmap.org/export/embed.html?bbox=37.5500%2C55.7200%2C37.6800%2C55.7900&layer=mapnik&marker=55.7558%2C37.6173';
?>
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">Контакты</span>
</nav>

<section class="section">
    <div class="container">
        <span class="eyebrow">Свяжитесь с нами</span>
        <h1>Контакты</h1>

        <div class="contact-layout">
            <div class="contact-info card">
                <h2>Как нас найти</h2>
                <dl>
                    <dt>Адрес</dt>
                    <dd>г. Москва, ул. Тверская, д. 1</dd>
                    <dt>Телефон</dt>
                    <dd><a href="tel:+74951234567">+7 (495) 123-45-67</a></dd>
                    <dt>Email</dt>
                    <dd><a href="mailto:hello@decor-home.ru">hello@decor-home.ru</a></dd>
                    <dt>Часы работы</dt>
                    <dd>Ежедневно, с 9:00 до 21:00</dd>
                </dl>
                <div class="map-frame">
                    <iframe src="<?= e($mapSrc) ?>" title="Карта проезда до магазина" loading="lazy"></iframe>
                </div>
            </div>

            <div class="card">
                <h2>Написать нам</h2>
                <p class="text-muted">Заполните форму — ответим на почту или перезвоним в течение рабочего дня.</p>
                <form action="<?= e(url('/contacts')) ?>" method="post" class="stack">
                    <?= Csrf::field() ?>

                    <div class="field<?= isset($errors['name']) ? ' field--error' : '' ?>">
                        <label for="name">Имя</label>
                        <input type="text" id="name" name="name" required value="<?= e((string) old('name')) ?>">
                        <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>
                    </div>

                    <div class="field-row field-row--2">
                        <div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required value="<?= e((string) old('email')) ?>">
                            <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>
                        </div>
                        <div class="field<?= isset($errors['phone']) ? ' field--error' : '' ?>">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" value="<?= e((string) old('phone')) ?>" placeholder="+7 900 000-00-00">
                            <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone'][0]) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="field<?= isset($errors['message']) ? ' field--error' : '' ?>">
                        <label for="message">Сообщение</label>
                        <textarea id="message" name="message" required minlength="10" maxlength="2000"><?= e((string) old('message')) ?></textarea>
                        <?php if (isset($errors['message'])): ?><p class="field-error"><?= e($errors['message'][0]) ?></p><?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--block">Отправить сообщение</button>
                </form>
            </div>
        </div>
    </div>
</section>
