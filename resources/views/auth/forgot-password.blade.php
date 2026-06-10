<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля — На Репите</title>
    <link rel="stylesheet" href="/css/app.css?v={{ time() }}">
    <style>
        body { background: var(--bg-primary); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 40px 32px; text-align: center; box-shadow: var(--shadow-lg); max-width: 400px; width: 100%; }
        .logo-icon { font-size: 48px; margin-bottom: 8px; }
        .login-container h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px; }
        .subtitle { color: var(--text-secondary); margin-bottom: 24px; font-size: 14px; }
        .login-form { text-align: left; margin-bottom: 16px; }
        .login-btn { width: 100%; padding: 14px; background: var(--accent); color: white; border: none; border-radius: var(--radius-md); font-size: 15px; font-weight: 700; cursor: pointer; transition: all var(--duration) var(--ease); }
        .login-btn:hover { background: var(--accent-hover); box-shadow: var(--shadow-glow); }
        .register-link { margin-top: 16px; font-size: 14px; color: var(--text-secondary); }
        .register-link a { color: var(--accent); font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-icon">🔑</div>
        <h1>Восстановление пароля</h1>
        <p class="subtitle">Укажи email, на который зарегистрирован аккаунт — пришлём ссылку для сброса пароля.</p>

        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif

        <form class="login-form" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="email@example.com" value="{{ old('email') }}" required>
            </div>
            <button type="submit" class="login-btn">Отправить ссылку</button>
        </form>

        <div class="register-link">
            Вспомнил пароль? <a href="/login">Войти</a>
        </div>
    </div>
</body>
</html>
