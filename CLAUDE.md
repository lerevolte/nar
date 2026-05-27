# Project: narepite — Laravel 12

## Что за продукт
narepite.com — сайт для покупки и генерации песен через Suno API (через kie.ai как провайдера).
- Авторизация через Telegram (cookie tg_session)
- Оплата через ЮKassa
- Telegram Mini Apps (контроллеры MaxAppController, MiniAppController)
- QR-коды (для шеринга/оплаты)
- Промокоды
- Кастомные голоса (VoiceService через Kie AI)

## Stack
- Laravel Framework 12.48
- PHP 8.2.30
- БД: одна (обычный single-tenant Laravel)
- Очереди: database driver
- Тесты: PHPUnit 11
- Vite: laravel-vite-plugin для сборки фронт-ассетов
- Сервер: Apache (файлы под пользователем apache:apache)
- Сервер: root@94.241.173.252, путь /var/www/narepite-web/

⚠️ ВАЖНО: это Laravel 12, НЕ 9/10/11.
- НОВАЯ структура: bootstrap/app.php — точка инициализации
- НЕТ app/Http/Kernel.php и app/Console/Kernel.php
- Middleware регистрируется в bootstrap/app.php через ->withMiddleware(...)
- Маршруты, exception handling — тоже в bootstrap/app.php
- НЕ применять синтаксис из Laravel 9-10
- Анонимные миграции — стандарт

## Структура проекта

### app/
- app/Http/Controllers/ — плоская папка контроллеров (без Api/)
- app/Models/ — модели Eloquent
- app/Services/ — сервисный слой (используется)
- app/Jobs/ — очереди
- app/Providers/ — провайдеры

### routes/
- routes/web.php — основные маршруты (лендинг, страницы, ЛК)
- routes/api.php — API-эндпоинты
- routes/console.php — artisan-команды

### Контроллеры (карта продукта)
- LandingController, StaticPageController, PageController, ArticleController — публичные страницы
- AuthController — авторизация (Telegram-cookie)
- DashboardController, ProfileController, FavoriteController — ЛК
- GenerateController, PublicGenerateController — генерация песен
- PersonaController — персонажи/стили
- PaymentController — оплата (ЮKassa)
- PromoCodeController — промокоды
- MaxAppController, MiniAppController — Telegram Mini Apps
- QrCodeController — QR-коды
- BroadcastController — рассылки
- SitemapController, ChartController — SEO и графики

### Сервисы
- VoiceService — работа с Kie AI (генерация голосов)

## Деплой и работа с сервером

⚠️ КРИТИЧНО: на сервере работает git. Раньше файлы правились вручную — БОЛЬШЕ НЕ ДЕЛАЕМ.

Правила:
- Все правки — ТОЛЬКО локально через git
- На сервере НЕ редактировать файлы напрямую
- После любой git-операции на сервере под root обязательно:
  `chown -R apache:apache /var/www/narepite-web`
  (иначе Apache не сможет писать в storage/ — сайт упадёт)
- После изменения config/* или .env — `php artisan config:clear` на сервере

### Storage на сервере
Папка /storage/ целиком в .gitignore.
При разворачивании на новой машине:
mkdir -p storage/{app,framework/{cache,sessions,testing,views},logs}
chmod -R 775 storage
chown -R apache:apache storage
### Public/music, /uploads, /covers
Пользовательский контент. Целиком в .gitignore. НЕ КОММИТИМ.

## Команды
- Тесты: `php artisan test`
- Линт: `./vendor/bin/pint`
- Миграции: `php artisan migrate`
- Локально: `php artisan serve`
- Очереди: `php artisan queue:work`
- Vite dev: `npm run dev`
- Vite билд: `npm run build`
- Очистка кешей: `php artisan optimize:clear`
- Очистка конфига: `php artisan config:clear` (после правок config/* или .env)
- Список маршрутов: `php artisan route:list`

## Конвенции

1. **Бизнес-логика — в app/Services/**, не в контроллерах
2. **Контроллеры тонкие**: принять Request → вызвать Service → вернуть response/view
3. **Валидация — через FormRequest** (app/Http/Requests/) для нетривиальной валидации
4. **API-ответы — через response()->json([...])** (Resources в проекте не используются)
5. **API-ключи и секреты — ТОЛЬКО через config()**, никогда не хардкодить в коде
   - .env → config/services.php (или другой config-файл) → config('services.xxx.api_key')
   - В config файлы — через env(), в коде — через config(), НЕ env()
6. **При работе с существующим файлом** — следовать его стилю,
   НЕ рефакторить попутно. Рефакторинг — отдельной задачей.

## ⚠️ Безопасность

- НИКОГДА не хардкодить API-ключи, пароли, токены в .php/.js файлах
- Все секреты — в .env, читать через config()
- Проверять .env.example — там только пустые шаблоны, не реальные значения
- Перед коммитом проверять `git diff` глазами на наличие случайных секретов

## Что НЕ делать

- НЕ применять синтаксис Laravel 9-10 (старые Kernel.php, старая структура)
- НЕ редактировать файлы в public/music/, /uploads/, /covers/ — пользовательский контент
- НЕ коммитить .env, storage/*
- НЕ запускать migrate:fresh, db:wipe на проде
- НЕ менять файлы напрямую на сервере, минуя git
- НЕ рефакторить существующий код "попутно"
- НЕ хардкодить секреты — только через .env и config()

## Перед коммитом

1. `./vendor/bin/pint`
2. `php artisan test`
3. Проверить `git diff` глазами на секреты
4. Conventional commits: feat/fix/refactor/chore/docs

## Интеграции с секретами в .env

- KIE_API_KEY — kie.ai (Suno API провайдер) → config('services.kie.api_key')
- ЮKassa secret_key → config('yookassa.secret_key')
- Telegram bot token → config()
- Любые другие — также через config()
