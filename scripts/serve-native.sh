#!/usr/bin/env bash
#
# Запуск сайта без Docker: встроенный сервер PHP + локальный MySQL.
# Ничего не устанавливает в систему — только проверяет, что нужное есть,
# и подсказывает команды установки.
#
#   ./scripts/serve-native.sh              # порт 8081
#   PORT=8090 ./scripts/serve-native.sh    # другой порт
#
set -euo pipefail

cd "$(dirname "$0")/.."

PORT="${PORT:-8081}"
DB_NAME="${DB_NAME:-decor_home}"
DB_USER="${DB_USER:-decor}"
DB_PASS="${DB_PASS:-decor_secret}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
# Пользователь, под которым создаём базу. У brew-установки MySQL root без пароля.
ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
ADMIN_PASS="${MYSQL_ADMIN_PASS:-}"

bold() { printf '\033[1m%s\033[0m\n' "$1"; }
ok()   { printf '  \033[32m✓\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✗\033[0m %s\n' "$1"; }

missing=0

bold "Проверка окружения"

# --- PHP ---
if ! command -v php >/dev/null 2>&1; then
    fail "PHP не найден"
    echo "      macOS:  brew install php@8.3 && brew link --overwrite --force php@8.3"
    echo "      Ubuntu: sudo apt install php8.3-cli php8.3-mysql php8.3-gd php8.3-mbstring"
    missing=1
else
    php_version=$(php -r 'echo PHP_VERSION;')
    if php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
        ok "PHP ${php_version}"
    else
        fail "PHP ${php_version} — нужен 8.3 или новее"
        missing=1
    fi

    for ext in pdo_mysql gd mbstring; do
        if php -m | grep -qi "^${ext}$"; then
            ok "расширение ${ext}"
        else
            fail "нет расширения PHP: ${ext}"
            missing=1
        fi
    done
fi

# --- Composer ---
if command -v composer >/dev/null 2>&1; then
    ok "Composer"
else
    fail "Composer не найден  (macOS: brew install composer)"
    missing=1
fi

# --- MySQL ---
if ! command -v mysql >/dev/null 2>&1; then
    fail "Клиент mysql не найден"
    echo "      macOS:  brew install mysql@8.0 && brew link --overwrite --force mysql@8.0"
    echo "      Ubuntu: sudo apt install mysql-server"
    missing=1
else
    ok "клиент mysql"
fi

if [ "$missing" -ne 0 ]; then
    echo
    bold "Не хватает зависимостей — установите их и запустите скрипт снова."
    echo "Либо используйте вариант с Docker: docker compose up -d --build"
    exit 1
fi

# --- Сервер MySQL запущен? ---
admin_args=(-h "$DB_HOST" -P "$DB_PORT" -u "$ADMIN_USER")
[ -n "$ADMIN_PASS" ] && admin_args+=("-p${ADMIN_PASS}")

if ! mysql "${admin_args[@]}" -e 'SELECT 1' >/dev/null 2>&1; then
    fail "MySQL не отвечает на ${DB_HOST}:${DB_PORT} под пользователем ${ADMIN_USER}"
    echo "      Запустить:  brew services start mysql@8.0   (Linux: sudo systemctl start mysql)"
    echo "      Если у root есть пароль:  MYSQL_ADMIN_PASS=... ./scripts/serve-native.sh"
    exit 1
fi
ok "сервер MySQL доступен"

echo
bold "Подготовка базы"

mysql "${admin_args[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
ok "база ${DB_NAME} и пользователь ${DB_USER}"

# Схему и демо-данные заливаем только в пустую базу — как это делает Docker-образ MySQL.
table_count=$(mysql "${admin_args[@]}" -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")

if [ "$table_count" -eq 0 ]; then
    mysql "${admin_args[@]}" "$DB_NAME" < db/01_schema.sql
    mysql "${admin_args[@]}" "$DB_NAME" < db/02_seed.sql
    ok "схема и демо-данные загружены"
else
    ok "база уже наполнена (${table_count} таблиц) — импорт пропущен"
    echo "      Пересоздать: mysql -u ${ADMIN_USER} -e 'DROP DATABASE ${DB_NAME};' && $0"
fi

echo
bold "Зависимости PHP"
if [ -d vendor ]; then
    ok "vendor/ на месте"
else
    composer install --no-interaction
    ok "composer install"
fi

echo
bold "Запуск"
echo "  Сайт:    http://localhost:${PORT}"
echo "  Админка: http://localhost:${PORT}/admin"
echo "  Остановить — Ctrl+C"
echo

# Настройки передаём через окружение: Config::get() читает getenv() раньше .env,
# поэтому лежащий рядом .env для Docker остаётся нетронутым.
# PHP_CLI_SERVER_WORKERS даёт параллельную обработку — иначе AJAX блокирует страницу.
DB_HOST="$DB_HOST" \
DB_PORT="$DB_PORT" \
DB_NAME="$DB_NAME" \
DB_USER="$DB_USER" \
DB_PASS="$DB_PASS" \
APP_URL="" \
APP_ENV="${APP_ENV:-dev}" \
APP_DEBUG="${APP_DEBUG:-true}" \
PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}" \
exec php -S "localhost:${PORT}" -t public scripts/router.php
