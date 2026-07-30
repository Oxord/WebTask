<?php
/**
 * @var array $errors
 * @var string $redirect
 * @var bool $throttled
 */

use App\Core\Csrf;
?>
<section class="section">
    <div class="container">
        <div class="card" style="max-width:440px;margin:0 auto">
            <h1>Вход в аккаунт</h1>

            <?php if ($throttled): ?>
                <div class="flash flash--error">Слишком много неудачных попыток входа. Подождите примерно 15 минут и попробуйте снова.</div>
            <?php endif; ?>

            <form action="<?= e(url('/login')) ?>" method="post" class="stack">
                <?= Csrf::field() ?>
                <?php if ($redirect !== ''): ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                <?php endif; ?>

                <div class="field<?= isset($errors['email']) ? ' field--error' : '' ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?= e((string) old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['password']) ? ' field--error' : '' ?>">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>
                </div>

                <button type="submit" class="btn btn--block" <?= $throttled ? 'disabled aria-disabled="true"' : '' ?>>Войти</button>
            </form>

            <p class="text-muted" style="margin-top:var(--space-4)">
                Ещё нет аккаунта? <a href="<?= e(url('/register')) ?>">Зарегистрироваться</a>
            </p>
            <p class="text-muted" style="font-size:.82rem">
                Демо-доступ: user@decor.local / User123!
            </p>
        </div>
    </div>
</section>
