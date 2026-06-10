<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — На Репите</title>
    <link rel="stylesheet" href="/css/app.css?v={{ time() }}">
    <style>
        body {
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 40px 32px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            max-width: 400px;
            width: 100%;
        }

        .logo-icon { font-size: 48px; margin-bottom: 8px; }
        .login-container h1 { font-size: 26px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px; }
        .subtitle { color: var(--text-secondary); margin-bottom: 24px; font-size: 14px; }

        .login-form { text-align: left; margin-bottom: 20px; }

        .login-btn {
            width: 100%; padding: 14px;
            background: var(--accent); color: white; border: none;
            border-radius: var(--radius-md); font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all var(--duration) var(--ease);
        }
        .login-btn:hover { background: var(--accent-hover); box-shadow: var(--shadow-glow); }

        .divider {
            display: flex; align-items: center;
            margin: 20px 0; color: var(--text-tertiary); font-size: 13px;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .divider::before { margin-right: 12px; }
        .divider::after { margin-left: 12px; }

        .telegram-login { display: flex; justify-content: center; margin-bottom: 20px; }

        /* Новые стили для кнопок-ссылок на ботов */
        .bot-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .bot-link-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--text-secondary, #4a5568);
            text-decoration: none;
            border-radius: var(--radius-md, 8px);
            border: 1px solid var(--border, #e2e8f0);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .bot-link-btn:hover {
            background: var(--border, #e2e8f0);
            color: var(--text-primary, #1a202c);
            transform: translateY(-1px);
        }

        .features { margin-top: 24px; text-align: left; }
        .feature { display: flex; align-items: center; margin: 10px 0; color: var(--text-secondary); font-size: 14px; }
        .feature-icon { margin-right: 10px; font-size: 18px; }

        .register-link { margin-top: 16px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-icon">🎵</div>
        <h1>На Репите</h1>
        <p class="subtitle">Создавай уникальные песни с помощью ИИ</p>

        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif

        <form class="login-form" method="POST" action="{{ route('password.login') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email или ID пользователя</label>
                <input type="text" name="login" class="form-input" placeholder="email@example.com или 123456789" required>
            </div>
            <div class="form-group">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-input" placeholder="Ваш пароль" required>
            </div>
            <button type="submit" class="login-btn">Войти</button>
        </form>

        <div class="register-link" style="margin-top: 12px;">
            <a href="{{ route('password.request') }}">Забыли пароль?</a>
        </div>

        <div class="register-link">
            Нет аккаунта? <a href="/register">Зарегистрироваться</a>
        </div>

        <div class="divider">или</div>

        <div class="telegram-login">
            <script async src="https://telegram.org/js/telegram-widget.js?22"
                data-telegram-login="{{ config('telegram.bot_username') }}"
                data-size="large"
                data-radius="10"
                data-auth-url="{{ route('telegram.callback') }}"
                data-request-access="write"
            ></script>
        </div>

        <div class="divider">открыть в мессенджере</div>
        
        <div class="bot-links">
            <a href="https://t.me/na_repitebot" target="_blank" class="bot-link-btn">
                Telegram
            </a>
            
            <a href="https://max.ru/id501216944367_bot" target="_blank" class="bot-link-btn">
                MAX
            </a>
        </div>

        <div class="features">
            <div class="feature"><span class="feature-icon">🎤</span><span>Генерация песен на любой повод</span></div>
            <div class="feature"><span class="feature-icon">📊</span><span>Участвуй в музыкальных чартах</span></div>
            <div class="feature"><span class="feature-icon">🏆</span><span>Голосуй за лучшие треки</span></div>
        </div>
    </div>
</body>
</html>