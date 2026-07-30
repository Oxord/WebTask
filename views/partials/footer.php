<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="32" height="32">
                    <span>Декор для дома</span>
                </div>
                <p>Уютные вещи для интерьера: текстиль, свет, керамика и ароматы — с доставкой по всей России.</p>
                <div class="social-links">
                    <a href="#" aria-label="Мы во ВКонтакте">VK</a>
                    <a href="#" aria-label="Мы в Telegram">TG</a>
                    <a href="#" aria-label="Мы в Дзен">Zen</a>
                </div>
            </div>

            <div>
                <h4>Покупателям</h4>
                <ul>
                    <li><a href="<?= e(url('/catalog')) ?>">Каталог</a></li>
                    <li><a href="<?= e(url('/promotions')) ?>">Акции</a></li>
                    <li><a href="<?= e(url('/cart')) ?>">Корзина</a></li>
                    <li><a href="<?= e(url('/account')) ?>">Личный кабинет</a></li>
                </ul>
            </div>

            <div>
                <h4>Компания</h4>
                <ul>
                    <li><a href="<?= e(url('/about')) ?>">О нас</a></li>
                    <li><a href="<?= e(url('/reviews')) ?>">Отзывы</a></li>
                    <li><a href="<?= e(url('/contacts')) ?>">Контакты</a></li>
                </ul>
            </div>

            <div>
                <h4>Документы</h4>
                <ul>
                    <li><a href="#">Публичная оферта</a></li>
                    <li><a href="#">Политика конфиденциальности</a></li>
                    <li><a href="#">Доставка и оплата</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?= date('Y') ?> ООО «Декор для дома». Все права защищены.</span>
            <div class="footer-legal">
                <span>ИНН 7701234567</span>
                <span>ОГРН 1157746112233</span>
            </div>
        </div>
    </div>
</footer>
