<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — На Репите</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            font-weight: 500;
        }

        .balance {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-card p {
            color: #666;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎵 На Репите</h1>
        <div class="user-info">
            <span class="user-name">{{ $authUser->first_name ?? $authUser->username ?? 'Пользователь' }}</span>
            <span class="balance">💰 {{ $authUser->balance }} песен</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">Выйти</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="success-message">
            ✅ Авторизация прошла успешно! Добро пожаловать, {{ $authUser->first_name ?? 'друг' }}!
        </div>

        <div class="welcome-card">
            <h2>🎉 Добро пожаловать в личный кабинет!</h2>
            <p>Здесь скоро появятся твои треки и возможность создавать новые песни.</p>
            <br>
            <p><strong>Telegram ID:</strong> {{ $authUser->user_id }}</p>
            <p><strong>Username:</strong> {{ $authUser->username ?? 'не указан' }}</p>
            <p><strong>Баланс:</strong> {{ $authUser->balance }} песен</p>
        </div>
    </div>
</body>
</html>