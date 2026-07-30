<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <a href="<?= e(url('/')) ?>">Главная</a> / <span aria-current="page">О нас</span>
</nav>

<section class="section section--tight">
    <div class="container">
        <span class="eyebrow">Компания</span>
        <h1>Decor Home Store — магазин уютных вещей для дома</h1>
        <p style="max-width:64ch">Мы открылись в 2019 году с простой идеей: сделать так, чтобы красивый и продуманный декор для интерьера был доступен без переплат за громкое имя бренда. Сегодня в каталоге — текстиль, керамика, свет, зеркала и ароматы для дома, которые подбирают наши мастера и проверенные производители.</p>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Наша миссия</span>
                <h2>Почему мы этим занимаемся</h2>
                <p>Мы верим, что дом — это не про идеальную картинку из журнала, а про вещи, которые приятно трогать и в которых хочется находиться каждый день. Поэтому отбираем товары так, будто выбираем их для себя.</p>
            </div>
        </div>
        <div class="grid grid--4">
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">🏺</div>
                <h3>Отбор вручную</h3>
                <p>Каждый товар в каталоге проходит через наших закупщиков — никакого случайного ассортимента.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">💶</div>
                <h3>Честная цена</h3>
                <p>Работаем без посредников там, где это возможно, поэтому цены ниже, чем в шоу-румах.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">📦</div>
                <h3>Бережная упаковка</h3>
                <p>Хрупкие товары — керамику, зеркала, светильники — упаковываем в два слоя защиты.</p>
            </div>
            <div class="perk-card">
                <div class="perk-card__icon" aria-hidden="true">🤝</div>
                <h3>Гарантия возврата</h3>
                <p>Если товар не подошёл — вернём деньги или обменяем в течение 14 дней.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-layout">
            <div class="contact-info card">
                <h2>Контакты</h2>
                <dl>
                    <dt>Адрес</dt>
                    <dd>г. Москва, ул. Тверская, д. 1</dd>
                    <dt>Телефон</dt>
                    <dd><a href="tel:+74951234567">+7 (495) 123-45-67</a></dd>
                    <dt>Email</dt>
                    <dd><a href="mailto:hello@decor-home.ru">hello@decor-home.ru</a></dd>
                    <dt>Часы работы</dt>
                    <dd>Ежедневно, 9:00–21:00</dd>
                </dl>
                <a class="btn" href="<?= e(url('/contacts')) ?>">Все способы связи</a>
            </div>
            <div class="card">
                <h2>Как мы работаем</h2>
                <p class="text-muted">Заказ собирается на складе в течение суток и передаётся в службу доставки. По Москве привозим на следующий день, по России — от 2 до 7 дней в зависимости от региона.</p>
                <p class="text-muted">Есть вопросы по товару или подбору декора для конкретной комнаты — напишите нам, поможем определиться.</p>
                <a class="btn btn--outline" href="<?= e(url('/catalog')) ?>">Перейти в каталог</a>
            </div>
        </div>
    </div>
</section>
