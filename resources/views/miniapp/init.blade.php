<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>На Репите</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .loader-container { text-align: center; padding: 20px; }
        .loader {
            width: 50px; height: 50px;
            border: 4px solid var(--tg-theme-hint-color, #ccc);
            border-top-color: var(--tg-theme-button-color, #3390ec);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { font-size: 16px; color: var(--tg-theme-hint-color, #999); }
        .error-container { display: none; text-align: center; padding: 20px; }
        .error-icon { font-size: 48px; margin-bottom: 16px; }
        .error-text { font-size: 16px; margin-bottom: 20px; }
        .retry-btn {
            background: var(--tg-theme-button-color, #3390ec);
            color: var(--tg-theme-button-text-color, #fff);
            border: none; padding: 12px 24px; border-radius: 10px;
            font-size: 16px; cursor: pointer;
        }
        .debug-info {
            margin-top: 20px; padding: 10px; background: rgba(0,0,0,0.1);
            border-radius: 8px; font-size: 11px; text-align: left;
            max-width: 300px; word-break: break-all; white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="loader-container" id="loader">
        <div class="loader"></div>
        <div class="loader-text" id="loader-text">Загрузка...</div>
    </div>

    <div class="error-container" id="error">
        <div class="error-icon">😕</div>
        <div class="error-text" id="error-text">Ошибка авторизации</div>
        <button class="retry-btn" onclick="init()">Попробовать снова</button>
        <div class="debug-info" id="debug-info"></div>
    </div>

    <script>
        const redirect = @json($redirect ?? '/dashboard');
        let tg = null;

        function log(msg) {
            console.log('[MiniApp]', msg);
            const el = document.getElementById('loader-text');
            if (el) el.textContent = msg;
        }

        function showError(message, debug = '') {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('error').style.display = 'block';
            document.getElementById('error-text').textContent = message;
            document.getElementById('debug-info').textContent = debug;
        }

        async function init() {
            document.getElementById('loader').style.display = 'block';
            document.getElementById('error').style.display = 'none';

            log('Инициализация...');

            // Проверяем Telegram WebApp
            if (typeof window.Telegram === 'undefined' || !window.Telegram.WebApp) {
                showError('Telegram WebApp не найден', 'window.Telegram: ' + typeof window.Telegram);
                return;
            }

            tg = window.Telegram.WebApp;
            log('Telegram WebApp загружен');

            // Инициализируем
            tg.ready();
            tg.expand();

            log('Проверка initData...');

            // Проверяем initData
            const initData = tg.initData;
            
            if (!initData || initData === '') {
                showError(
                    'Откройте через Telegram',
                    'initData пустой.\n\nПроверьте:\n1. Открыто через Telegram бот\n2. Bot username в BotFather совпадает с конфигом\n\ntg.initDataUnsafe: ' + JSON.stringify(tg.initDataUnsafe || {})
                );
                return;
            }

            log('initData получен, авторизация...');

            try {
                const response = await fetch('/api/miniapp/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ initData: initData }),
                });

                const data = await response.json();
                
                if (!response.ok) {
                    showError(
                        data.error || 'Ошибка авторизации',
                        'Status: ' + response.status + '\nResponse: ' + JSON.stringify(data)
                    );
                    return;
                }

                log('Авторизация успешна!');

                // Сохраняем токен
                const maxAge = 60 * 60 * 24 * 7;
                document.cookie = `tg_session=${data.token}; path=/; max-age=${maxAge}; SameSite=Lax`;
                document.cookie = `miniapp=1; path=/; max-age=${maxAge}; SameSite=Lax`;

                log('Переход в приложение...');

                // Небольшая задержка для сохранения cookie
                await new Promise(r => setTimeout(r, 100));

                // Редирект
                window.location.href = redirect;

            } catch (error) {
                showError('Ошибка сети', error.message + '\n\n' + error.stack);
            }
        }

        // Запускаем после загрузки страницы
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    </script>
</body>
</html>