<?php
/** @var array $errors */

use App\Core\Csrf;
?>
<section class="section">
    <div class="container">
        <div class="card" style="max-width:480px;margin:0 auto">
            <h1>Регистрация</h1>

            <form action="<?= e(url('/register')) ?>" method="post" class="stack">
                <?= Csrf::field() ?>

                <div class="field<?= isset($errors['full_name']) ? ' field--error' : '' ?>">
                    <label for="full_name">ФИО</label>
                    <input type="text" id="full_name" name="full_name" required value="<?= e((string) old('full_name')) ?>">
                    <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?= e((string) old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['phone']) ? ' field--error' : '' ?>">
                    <label for="phone">Телефон (необязательно)</label>
                    <input type="tel" id="phone" name="phone" placeholder="+7 900 000-00-00" value="<?= e((string) old('phone')) ?>">
                    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['password']) ? ' field--error' : '' ?>">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                    <p class="hint">Не менее 6 символов.</p>
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>
                </div>

                <button type="submit" class="btn btn--block">Зарегистрироваться</button>
            </form>

            <p class="text-muted" style="margin-top:var(--space-4)">
                Уже есть аккаунт? <a href="<?= e(url('/login')) ?>">Войти</a>
            </p>
        </div>
    </div>
</section>
