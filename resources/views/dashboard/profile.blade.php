@extends('layouts.app')

@section('title', 'Профиль — На Репите')

@push('styles')
<style>
    .profile-card {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 24px; margin-bottom: 16px;
    }
    .profile-card h3 { font-size: 18px; margin-bottom: 16px; font-weight: 700; }

    .info-row {
        display: flex; justify-content: space-between; padding: 12px 0;
        border-bottom: 1px solid var(--border); font-size: 14px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-secondary); }
    .info-value { font-weight: 600; }

    .save-btn {
        background: var(--accent); color: white; border: none;
        padding: 12px 24px; border-radius: var(--radius-md);
        font-size: 16px; font-weight: 600; cursor: pointer;
        width: 100%; transition: background var(--duration) var(--ease);
    }
    .save-btn:hover { background: var(--accent-hover); }

    .logout-btn {
        background: var(--danger); color: white; border: none;
        padding: 12px 24px; border-radius: var(--radius-md);
        font-size: 16px; font-weight: 600; cursor: pointer;
        width: 100%; margin-top: 8px; transition: opacity var(--duration) var(--ease);
    }
    .logout-btn:hover { opacity: 0.9; }

    .telegram-link-section {
        padding: 20px; border-radius: var(--radius-md);
        text-align: center; margin-top: 12px;
    }
    .telegram-linked {
        background: var(--success-soft); border: 1px solid rgba(22,163,74,0.15);
    }
    .telegram-linked .tg-status { color: var(--success); font-weight: 600; font-size: 14px; }
    .telegram-not-linked {
        background: var(--warning-soft); border: 1px solid rgba(217,119,6,0.15);
    }
    .telegram-not-linked .tg-status { color: var(--warning); font-weight: 600; font-size: 14px; margin-bottom: 12px; }
    .telegram-not-linked .tg-hint { color: var(--text-secondary); font-size: 13px; margin-bottom: 16px; line-height: 1.5; }
</style>
@endpush

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
        </div>
    @endif

    <!-- Профиль -->
    <div class="profile-card">
        <h3>👤 Профиль</h3>
        <div class="info-row">
            <span class="info-label">Имя</span>
            <span class="info-value">{{ $authUser->first_name ?? 'Не указано' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Username</span>
            <span class="info-value">{{ $authUser->username ? '@' . $authUser->username : '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value">{{ $authUser->email ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Баланс</span>
            <span class="info-value">{{ $authUser->balance }} песен</span>
        </div>
        <div class="info-row">
            <span class="info-label">Треков</span>
            <span class="info-value">{{ $authUser->songs()->count() }}</span>
        </div>

        {{-- Telegram привязка --}}
        @php
            // user_id >= 9_000_000_000 = email-регистрация (без Telegram)
            $hasTelegram = $authUser->user_id < 9000000000;
        @endphp

        @if($hasTelegram)
            <div class="telegram-link-section telegram-linked">
                <div class="tg-status">✅ Telegram подключён (ID: {{ $authUser->user_id }})</div>
            </div>
        @else
            <div class="telegram-link-section telegram-not-linked">
                <div class="tg-status">⚠️ Telegram не подключён</div>
                <div class="tg-hint">
                    Подключи Telegram, чтобы получать уведомления о готовых песнях и результатах чартов
                </div>
                <script async src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="{{ config('telegram.bot_username') }}"
                    data-size="large"
                    data-radius="10"
                    data-auth-url="{{ route('telegram.link') }}"
                    data-request-access="write"
                ></script>
            </div>
        @endif
    </div>

    <!-- Данные для входа -->
    <div class="profile-card">
        <h3>🔐 Данные для входа</h3>

        <form method="POST" action="{{ route('profile.update-credentials') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input"
                    value="{{ old('email', $authUser->email) }}"
                    placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="new_password" class="form-input"
                    placeholder="Оставьте пустым, если не меняете">
            </div>
            <div class="form-group">
                <label class="form-label">Подтверждение пароля</label>
                <input type="password" name="new_password_confirmation" class="form-input"
                    placeholder="Повторите пароль">
            </div>
            <button type="submit" class="save-btn">Сохранить</button>
        </form>
    </div>

    <!-- Выход -->
    <div class="profile-card">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">Выйти</button>
        </form>
    </div>
@endsection