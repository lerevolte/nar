@extends('layouts.app')

@section('title', 'Тестовые инструменты — Админ')

@push('styles')
<style>
    .tt-wrap { max-width: 720px; margin: 0 auto; padding: 16px; }
    .tt-card {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px;
    }
    .tt-card h2 { font-size: 17px; font-weight: 800; margin: 0 0 6px; }
    .tt-card p.tt-desc { font-size: 13px; color: var(--text-secondary); margin: 0 0 14px; }
    .tt-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .tt-input {
        flex: 1; min-width: 220px; padding: 11px 12px; border: 1px solid var(--border);
        border-radius: var(--radius-md); background: var(--bg-primary); color: var(--text-primary); font-size: 14px;
    }
    .tt-btn {
        padding: 11px 18px; border: none; border-radius: var(--radius-md);
        background: var(--accent); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer;
    }
    .tt-btn:hover { background: var(--accent-hover); }
    .tt-btn:disabled { opacity: .6; cursor: default; }
    .tt-btn-danger { background: #ef4444; }
    .tt-result {
        margin-top: 12px; padding: 12px; border-radius: var(--radius-md);
        font-size: 13px; white-space: pre-wrap; word-break: break-word; display: none;
        background: var(--bg-primary); border: 1px solid var(--border);
    }
    .tt-result.ok { border-color: #10b981; }
    .tt-result.err { border-color: #ef4444; }
    .tt-result a { color: var(--accent); font-weight: 700; }
    .tt-note { font-size: 12px; color: var(--text-tertiary); margin-top: 8px; }
</style>
@endpush

@section('content')
<div class="tt-wrap">
    <h1 style="font-size:22px;font-weight:800;margin:8px 0 4px;">🧪 Тестовые инструменты</h1>
    <p style="font-size:13px;color:var(--text-secondary);margin:0 0 20px;">
        Прогон сценариев без реальной оплаты. Видно только тебе. Email по умолчанию — твой.
    </p>

    {{-- Сценарий 1 --}}
    <div class="tt-card">
        <h2>1. Успешная оплата (без ЮKassa)</h2>
        <p class="tt-desc">Создаёт заказ, начисляет баланс, запускает <b>реальную</b> генерацию (Suno), шлёт письмо с доступами и уведомление админу.</p>
        <div class="tt-row">
            <input type="email" class="tt-input" id="pay-email" placeholder="email для письма" value="{{ $defaultEmail }}">
            <button class="tt-btn" onclick="tt('pay', 'pay-email', this)">Завершить оплату</button>
        </div>
        <div class="tt-result" id="res-pay"></div>
        <div class="tt-note">⚠️ Запустит реальную генерацию в Suno (расход кредитов API).</div>
    </div>

    {{-- Сценарий 2 --}}
    <div class="tt-card">
        <h2>2. Ошибка генерации + возврат</h2>
        <p class="tt-desc">Симулирует провал генерации: возвращает 1 песню на баланс, шлёт письмо об ошибке, на странице успеха появляется кнопка «Повторить».</p>
        <div class="tt-row">
            <input type="email" class="tt-input" id="fail-email" placeholder="email для письма" value="{{ $defaultEmail }}">
            <button class="tt-btn tt-btn-danger" onclick="tt('fail', 'fail-email', this)">Симулировать ошибку</button>
        </div>
        <div class="tt-result" id="res-fail"></div>
    </div>

    {{-- Сценарий 3 --}}
    <div class="tt-card">
        <h2>3. Проверка писем (SMTP)</h2>
        <p class="tt-desc">Отправляет все 4 письма (доступы, готово, ошибка, сброс пароля) на адрес — синхронно, ошибки SMTP видно сразу.</p>
        <div class="tt-row">
            <input type="email" class="tt-input" id="mail-email" placeholder="куда отправить" value="{{ $defaultEmail }}">
            <button class="tt-btn" onclick="tt('mail', 'mail-email', this)">Отправить 4 письма</button>
        </div>
        <div class="tt-result" id="res-mail"></div>
    </div>

    {{-- Сценарий 4 --}}
    <div class="tt-card">
        <h2>4. Сброс пароля</h2>
        <p class="tt-desc">Отправляет настоящую ссылку сброса пароля (работает только для существующего аккаунта с этим email).</p>
        <div class="tt-row">
            <input type="email" class="tt-input" id="reset-email" placeholder="email аккаунта" value="{{ $defaultEmail }}">
            <button class="tt-btn" onclick="tt('reset', 'reset-email', this)">Отправить ссылку сброса</button>
        </div>
        <div class="tt-result" id="res-reset"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const TT_URLS = {
    pay:   @json(route('admin.test-tools.pay')),
    fail:  @json(route('admin.test-tools.fail')),
    mail:  @json(route('admin.test-tools.mail')),
    reset: @json(route('admin.test-tools.reset')),
};
const TT_CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function tt(action, inputId, btn) {
    const email = document.getElementById(inputId).value.trim();
    const box = document.getElementById('res-' + action);
    const orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Выполняю…';
    box.style.display = 'none'; box.className = 'tt-result';

    try {
        const r = await fetch(TT_URLS[action], {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TT_CSRF, 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ email }),
        });
        const d = await r.json();

        let html = '';
        if (d.message) html += d.message + '\n';
        if (d.results) html += Object.entries(d.results).map(([k, v]) => `• ${k}: ${v}`).join('\n') + '\n';
        if (d.status && !d.message) html += 'status: ' + d.status + '\n';
        if (d.error) html += '⚠️ ' + d.error + '\n';
        if (d.success_url) html += `\n➡ <a href="${d.success_url}" target="_blank">Открыть страницу песни</a>`;

        box.innerHTML = html.trim() || (d.ok ? 'Готово' : 'Ошибка');
        box.classList.add(d.ok ? 'ok' : 'err');
        box.style.display = 'block';
    } catch (e) {
        box.textContent = 'Сетевая ошибка: ' + e.message;
        box.classList.add('err');
        box.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = orig;
    }
}
</script>
@endpush
