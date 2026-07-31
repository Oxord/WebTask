#Requires -Version 5.1
<#
.SYNOPSIS
    Запуск сайта «Декор для дома» на Windows без Docker.

.DESCRIPTION
    Проверяет PHP, Composer и MySQL, создаёт базу и пользователя, загружает схему
    с демо-данными и поднимает встроенный сервер PHP. В систему ничего не
    устанавливает — при нехватке зависимостей показывает команды и останавливается.

.NOTES
    Файл обязан оставаться в UTF-8 С BOM. Windows PowerShell 5.1 (тот, что идёт
    в комплекте с Windows) читает .ps1 без BOM в ANSI-кодировке — кириллица
    рассыпается, и скрипт падает с «Unexpected token» ещё на этапе разбора.

.EXAMPLE
    .\scripts\serve-native.ps1
    .\scripts\serve-native.ps1 -Port 8090
    .\scripts\serve-native.ps1 -AdminPass "пароль_root"
#>
[CmdletBinding()]
param(
    [int]$Port = 8081,
    [string]$DbName = 'decor_home',
    [string]$DbUser = 'decor',
    [string]$DbPass = 'decor_secret',
    [string]$DbServer = '127.0.0.1',
    [int]$DbPort = 3306,
    [string]$AdminUser = 'root',
    [string]$AdminPass = ''
)

$ErrorActionPreference = 'Stop'

# Кириллица в SQL и в выводе не должна превращаться в «кракозябры».
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch { }

function Write-Bold($text) { Write-Host $text -ForegroundColor White }
function Write-Ok($text)   { Write-Host "  [OK] $text" -ForegroundColor Green }
function Write-Fail($text) { Write-Host "  [!!] $text" -ForegroundColor Red }
function Write-Hint($text) { Write-Host "       $text" -ForegroundColor DarkGray }

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$missing = $false

Write-Bold 'Проверка окружения'

# --- PHP ---
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Fail 'PHP не найден'
    Write-Hint 'Chocolatey:  choco install php'
    Write-Hint 'Либо скачайте ZIP (Thread Safe) с https://windows.php.net/download/'
    Write-Hint 'и добавьте каталог с php.exe в переменную среды PATH.'
    $missing = $true
} else {
    $phpVersion = (& php -r 'echo PHP_VERSION;')
    & php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'
    if ($LASTEXITCODE -eq 0) {
        Write-Ok "PHP $phpVersion"
    } else {
        Write-Fail "PHP $phpVersion — нужен 8.3 или новее"
        $missing = $true
    }

    $modules = (& php -m)
    foreach ($ext in @('pdo_mysql', 'gd', 'mbstring')) {
        if ($modules -contains $ext) {
            Write-Ok "расширение $ext"
        } else {
            Write-Fail "нет расширения PHP: $ext"
            $iniPath = (& php -r 'echo php_ini_loaded_file() ?: "php.ini не подключён";')
            Write-Hint "Откройте $iniPath и раскомментируйте строку extension=$ext"
            Write-Hint 'Если php.ini отсутствует — скопируйте php.ini-development в php.ini'
            $missing = $true
        }
    }
}

# --- Composer ---
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Ok 'Composer'
} else {
    Write-Fail 'Composer не найден'
    Write-Hint 'choco install composer   либо  https://getcomposer.org/Composer-Setup.exe'
    $missing = $true
}

# --- Клиент MySQL ---
if (Get-Command mysql -ErrorAction SilentlyContinue) {
    Write-Ok 'клиент mysql'
} else {
    Write-Fail 'Клиент mysql не найден'
    Write-Hint 'choco install mysql   либо  https://dev.mysql.com/downloads/installer/'
    Write-Hint 'Каталог MySQL\bin должен быть в PATH.'
    $missing = $true
}

if ($missing) {
    Write-Host ''
    Write-Bold 'Не хватает зависимостей — установите их и запустите скрипт снова.'
    Write-Host 'Либо используйте вариант с Docker: docker compose up -d --build'
    exit 1
}

# Пароль передаём через MYSQL_PWD, чтобы он не светился в списке процессов.
if ($AdminPass) { $env:MYSQL_PWD = $AdminPass }
$adminArgs = @('-h', $DbServer, '-P', "$DbPort", '-u', $AdminUser, '--default-character-set=utf8mb4')

& mysql @adminArgs -e 'SELECT 1' *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Fail "MySQL не отвечает на ${DbServer}:${DbPort} под пользователем $AdminUser"
    Write-Hint 'Запустить службу:  net start MySQL80    (имя службы можно увидеть в services.msc)'
    Write-Hint 'Если у root есть пароль:  .\scripts\serve-native.ps1 -AdminPass "пароль"'
    exit 1
}
Write-Ok 'сервер MySQL доступен'

Write-Host ''
Write-Bold 'Подготовка базы'

$setupSql = @"
CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DbUser'@'localhost' IDENTIFIED BY '$DbPass';
CREATE USER IF NOT EXISTS '$DbUser'@'127.0.0.1' IDENTIFIED BY '$DbPass';
GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'localhost';
GRANT ALL PRIVILEGES ON ``$DbName``.* TO '$DbUser'@'127.0.0.1';
FLUSH PRIVILEGES;
"@
$setupSql | & mysql @adminArgs
if ($LASTEXITCODE -ne 0) { Write-Fail 'Не удалось создать базу'; exit 1 }
Write-Ok "база $DbName и пользователь $DbUser"

# Схему и демо-данные заливаем только в пустую базу — так же ведёт себя образ MySQL в Docker.
$tableCount = [int](& mysql @adminArgs -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DbName';")

if ($tableCount -eq 0) {
    # -RedirectStandardInput отдаёт файл процессу байт в байт: PowerShell не трогает
    # кодировку, поэтому кириллица в демо-данных доезжает без искажений.
    foreach ($file in @('db/01_schema.sql', 'db/02_seed.sql')) {
        $full = Join-Path $root $file
        $proc = Start-Process -FilePath 'mysql' -ArgumentList ($adminArgs + @($DbName)) `
            -RedirectStandardInput $full -NoNewWindow -Wait -PassThru
        if ($proc.ExitCode -ne 0) { Write-Fail "Ошибка при импорте $file"; exit 1 }
    }
    Write-Ok 'схема и демо-данные загружены'
} else {
    Write-Ok "база уже наполнена ($tableCount таблиц) — импорт пропущен"
    Write-Hint "Пересоздать: mysql -u $AdminUser -e `"DROP DATABASE $DbName;`" затем запустить скрипт снова"
}

Write-Host ''
Write-Bold 'Зависимости PHP'
if (Test-Path (Join-Path $root 'vendor')) {
    Write-Ok 'vendor\ на месте'
} else {
    & composer install --no-interaction
    if ($LASTEXITCODE -ne 0) { Write-Fail 'composer install завершился с ошибкой'; exit 1 }
    Write-Ok 'composer install'
}

Write-Host ''
Write-Bold 'Запуск'
Write-Host "  Сайт:    http://localhost:$Port"
Write-Host "  Админка: http://localhost:$Port/admin"
Write-Host '  Остановить — Ctrl+C'
Write-Host ''

# Настройки передаём через окружение: Config::get() читает getenv() раньше .env,
# поэтому лежащий рядом .env для Docker остаётся нетронутым.
$env:DB_HOST   = $DbServer
$env:DB_PORT   = "$DbPort"
$env:DB_NAME   = $DbName
$env:DB_USER   = $DbUser
$env:DB_PASS   = $DbPass
$env:APP_URL   = ''
if (-not $env:APP_ENV)   { $env:APP_ENV = 'dev' }
if (-not $env:APP_DEBUG) { $env:APP_DEBUG = 'true' }

& php -S "localhost:$Port" -t public scripts/router.php
