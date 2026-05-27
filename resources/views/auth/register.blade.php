<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — На Репите</title>
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

        .register-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            max-width: 400px;
            width: 100%;
        }

        .logo-icon { font-size: 48px; margin-bottom: 8px; }
        .register-container h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px; }
        .subtitle { color: var(--text-secondary); margin-bottom: 24px; font-size: 14px; }

        .register-form { text-align: left; margin-bottom: 20px; }

        .register-btn {
            width: 100%; padding: 14px;
            background: var(--accent); color: white; border: none;
            border-radius: var(--radius-md); font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all var(--duration) var(--ease);
        }
        .register-btn:hover { background: var(--accent-hover); box-shadow: var(--shadow-glow); }

        .login-link {
            margin-top: 16px; font-size: 14px; color: var(--text-secondary);
        }
        .login-link a { color: var(--accent); font-weight: 600; }

        .bonus-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--success-soft); color: var(--success);
            padding: 8px 16px; border-radius: var(--radius-full);
            font-size: 13px; font-weight: 600; margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo-icon">🎵</div>
        <h1>Регистрация</h1>
        <p class="subtitle">Создавай уникальные песни с ИИ</p>

        <!-- <div class="bonus-badge">🎁 1 бесплатная песня при регистрации</div> -->

        @if($errors->any())
            <div class="error-message">
                @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
            </div>
        @endif

        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form class="register-form" method="POST" action="{{ route('register.submit') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Имя (необязательно)</label>
                <input type="text" name="first_name" class="form-input" placeholder="Как тебя зовут?" value="{{ old('first_name') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="your@email.com" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-input" placeholder="Минимум 6 символов" required>
            </div>
            <div class="form-group">
                <label class="form-label">Подтверждение пароля</label>
                <input type="password" name="password_confirmation" class="form-input" placeholder="Повторите пароль" required>
            </div>
            <button type="submit" class="register-btn">Создать аккаунт</button>
        </form>

        <div class="login-link">
            Уже есть аккаунт? <a href="/login">Войти</a>
        </div>
    </div>
</body>
</html>