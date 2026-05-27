<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>На Репите</title>
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff; color: #000;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .loader-container { text-align: center; padding: 20px; }
        .loader {
            width: 50px; height: 50px; border: 4px solid #ccc; border-top-color: #3390ec;
            border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text { font-size: 16px; color: #999; }
        .error-container { display: none; text-align: center; padding: 20px; max-width: 350px; }
        .error-text { font-size: 16px; margin-bottom: 20px; }
        .debug-info {
            margin-top: 20px; padding: 10px; background: rgba(0,0,0,0.05);
            border-radius: 8px; font-size: 11px; text-align: left;
            word-break: break-all; white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="loader-container" id="loader">
        <div class="loader"></div>
        <div class="loader-text" id="loader-text">Загрузка...</div>
    </div>

    <div class="error-container" id="error">
        <div class="error-text" id="error-text"></div>
        <div class="debug-info" id="debug-info"></div>
    </div>

    <!-- Скрытая форма для серверного редиректа -->
    <form id="auth-form" method="POST" action="/max/auth-redirect" style="display:none;">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="initData" id="form-initData">
        <input type="hidden" name="redirect" value="{{ $redirect ?? '/dashboard' }}">
    </form>

    <script>
        function showError(msg, debug) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('error').style.display = 'block';
            document.getElementById('error-text').textContent = msg;
            document.getElementById('debug-info').textContent = debug || '';
        }

        function init() {
            const el = document.getElementById('loader-text');

            // Ждём пока MAX Bridge загрузится
            let attempts = 0;
            const maxAttempts = 20;

            function tryInit() {
                attempts++;

                if (typeof window.WebApp === 'undefined' || !window.WebApp) {
                    if (attempts < maxAttempts) {
                        setTimeout(tryInit, 200);
                        return;
                    }
                    showError('WebApp не найден', 'MAX Bridge не загрузился');
                    return;
                }

                const webapp = window.WebApp;
                try { webapp.ready(); } catch(e) {}

                // Ждём initData — может появиться не сразу
                const initData = webapp.initData;
                if (!initData) {
                    if (attempts < maxAttempts) {
                        setTimeout(tryInit, 200);
                        return;
                    }
                    showError('initData пустой', 'Откройте через MAX-бота');
                    return;
                }

                el.textContent = 'Авторизация...';

                // AJAX вместо формы — чтобы MAX WebView не зависал на редиректе
                fetch('/max/auth-ajax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ initData: initData }),
                    credentials: 'same-origin'
                })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (data.success && data.token) {
                        // Загружаем dashboard через iframe с токеном
                        var url = '{{ $redirect ?? "/dashboard" }}' + '?tg_token=' + data.token + '&miniapp=1&max_app=1';
                        document.body.innerHTML = '<iframe src="' + url + '" style="position:fixed;top:0;left:0;width:100%;height:100%;border:none;"></iframe>';
                    } else {
                        showError('Ошибка авторизации', data.error || 'Неизвестная ошибка');
                    }
                })
                .catch(function(err) {
                    showError('Ошибка сети', err.message);
                });
            }
            

            tryInit();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    </script>
</body>
</html>