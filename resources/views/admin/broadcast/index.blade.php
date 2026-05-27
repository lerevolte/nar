@extends('layouts.app')

@section('title', 'Рассылки — Админ')

@push('styles')
<style>
    .broadcast-form { margin-bottom: 32px; }
    .channel-options { display: flex; gap: 8px; margin-bottom: 16px; }
    .channel-btn {
        flex: 1; padding: 12px; border: 2px solid var(--border); border-radius: var(--radius-md);
        background: var(--bg-card); cursor: pointer; text-align: center; font-size: 13px; font-weight: 600;
        transition: all var(--duration) var(--ease);
    }
    .channel-btn:hover { border-color: var(--accent); }
    .channel-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }
    .segment-select { width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: var(--radius-md); background: var(--bg-card); font-size: 14px; color: var(--text-primary); }
    .segment-count { font-size: 13px; color: var(--text-tertiary); margin-top: 6px; }
    .telegram-fields, .web-fields { margin-top: 16px; padding: 16px; background: var(--bg-input); border-radius: var(--radius-md); }
    .field-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }

    .broadcast-history { margin-top: 32px; }
    .broadcast-item {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md);
        padding: 14px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;
    }
    .broadcast-info { flex: 1; }
    .broadcast-id { font-weight: 700; font-size: 14px; }
    .broadcast-meta { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
    .broadcast-stats { display: flex; gap: 12px; font-size: 12px; }
    .broadcast-badge {
        padding: 3px 10px; border-radius: var(--radius-full); font-size: 11px; font-weight: 700;
    }
    .badge-pending { background: var(--warning-soft); color: var(--warning); }
    .badge-running { background: var(--accent-soft); color: var(--accent); }
    .badge-completed { background: var(--success-soft); color: var(--success); }
    .badge-failed { background: var(--danger-soft); color: var(--danger); }
    .badge-paused { background: var(--surface-glass); color: var(--text-secondary); }
</style>
@endpush

@section('content')
<h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;">📢 Рассылки</h2>

<div class="card broadcast-form">
    <h3 class="card-title">Новая рассылка</h3>

    {{-- Канал --}}
    <div class="form-group">
        <label class="form-label">Канал отправки</label>
        <div class="channel-options">
            <button type="button" class="channel-btn selected" data-channel="telegram" onclick="selectChannel('telegram')">📱 Telegram</button>
            <button type="button" class="channel-btn" data-channel="web" onclick="selectChannel('web')">🌐 Сайт</button>
            <button type="button" class="channel-btn" data-channel="both" onclick="selectChannel('both')">📱+🌐 Оба</button>
        </div>
    </div>

    {{-- Сегмент --}}
    <div class="form-group">
        <label class="form-label">Аудитория</label>
        <select class="segment-select" id="segment-select" onchange="countSegment()">
            <option value="all">🌍 Все пользователи</option>
            <option value="inactive_mix">💤 Спящие + Черновики + Без оплат</option>
            <option value="paid">💰 Оплатившие</option>
            <option value="test">🧪 Тест (только 288559694)</option>
        </select>
        <div class="segment-count" id="segment-count">Загрузка...</div>
    </div>

    {{-- Telegram текст --}}
    <div class="telegram-fields" id="telegram-fields">
        <div class="field-label">📱 Текст для Telegram (HTML)</div>
        <textarea class="form-textarea" id="tg-text" rows="6" placeholder="Привет! 🎵&#10;&#10;<b>Новые функции...</b>"></textarea>
    </div>

    {{-- Web уведомление --}}
    <div class="web-fields" id="web-fields" style="display: none;">
        <div class="field-label">🌐 Уведомление на сайте</div>
        <div class="form-group">
            <input type="text" class="form-input" id="web-title" placeholder="Заголовок уведомления">
        </div>
        <textarea class="form-textarea" id="web-message" rows="4" placeholder="Текст уведомления..."></textarea>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn btn-primary btn-block" id="create-btn" onclick="createBroadcast()">🚀 Создать рассылку</button>
    </div>

    <div id="result-message" style="display:none; margin-top:12px;"></div>
</div>

{{-- История --}}
<div class="broadcast-history">
    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">📋 История рассылок</h3>

    @forelse($broadcasts as $b)
        <div class="broadcast-item">
            <div class="broadcast-info">
                <div class="broadcast-id">
                    #{{ $b->id }}
                    <span class="broadcast-badge badge-{{ $b->status }}">{{ $b->status }}</span>
                </div>
                <div class="broadcast-meta">
                    {{ $b->channel }} · {{ $b->segment }} · {{ $b->created_at->format('d.m.Y H:i') }}
                </div>
            </div>
            <div class="broadcast-stats">
                <span>✅ {{ $b->sent_count }}</span>
                <span>❌ {{ $b->failed_count }}</span>
                <span>🚫 {{ $b->blocked_count }}</span>
                <span>/ {{ $b->total_users }}</span>
            </div>
        </div>
    @empty
        <p style="color: var(--text-tertiary); text-align: center; padding: 20px;">Рассылок пока нет</p>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
    let selectedChannel = 'telegram';

    function selectChannel(ch) {
        selectedChannel = ch;
        document.querySelectorAll('.channel-btn').forEach(b => b.classList.toggle('selected', b.dataset.channel === ch));
        document.getElementById('telegram-fields').style.display = ['telegram','both'].includes(ch) ? 'block' : 'none';
        document.getElementById('web-fields').style.display = ['web','both'].includes(ch) ? 'block' : 'none';
    }

    async function countSegment() {
        const seg = document.getElementById('segment-select').value;
        const el = document.getElementById('segment-count');
        el.textContent = 'Считаю...';
        try {
            const r = await fetch('/api/admin/broadcast/count-segment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({ segment: seg }),
            });
            const d = await r.json();
            el.textContent = `👥 ${d.count} получателей`;
        } catch (e) { el.textContent = 'Ошибка подсчёта'; }
    }

    async function createBroadcast() {
        const btn = document.getElementById('create-btn');
        const resultEl = document.getElementById('result-message');
        btn.disabled = true; btn.textContent = 'Создаю...';
        resultEl.style.display = 'none';

        try {
            const body = {
                segment: document.getElementById('segment-select').value,
                channel: selectedChannel,
                text_content: document.getElementById('tg-text')?.value || null,
                web_title: document.getElementById('web-title')?.value || null,
                web_message: document.getElementById('web-message')?.value || null,
            };

            const r = await fetch('/api/admin/broadcast/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const d = await r.json();

            if (!r.ok) throw new Error(d.error || 'Ошибка');

            resultEl.className = 'alert alert-success';
            resultEl.textContent = `✅ ${d.message}`;
            resultEl.style.display = 'block';

            // Обновляем страницу через 2 сек
            setTimeout(() => window.location.reload(), 2000);

        } catch (e) {
            resultEl.className = 'alert alert-error';
            resultEl.textContent = '❌ ' + e.message;
            resultEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '🚀 Создать рассылку';
        }
    }

    // Подсчёт при загрузке
    countSegment();
</script>
@endpush