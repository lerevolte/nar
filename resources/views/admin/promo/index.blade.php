@extends('layouts.app')

@section('title', 'Промокоды — Админ')

@push('styles')
<style>
    .promo-form-card { margin-bottom: 24px; }
    .promo-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 500px) { .promo-form-grid { grid-template-columns: 1fr; } }

    .promo-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .promo-table th {
        text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--text-tertiary); border-bottom: 2px solid var(--border);
    }
    .promo-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .promo-table tr:hover td { background: var(--surface-glass); }

    .promo-code-cell { font-family: monospace; font-weight: 700; font-size: 14px; letter-spacing: 0.03em; }
    .promo-active { color: var(--success); }
    .promo-inactive { color: var(--danger); }

    .promo-toggle-btn {
        padding: 4px 10px; border-radius: var(--radius-sm); font-size: 11px; font-weight: 600;
        cursor: pointer; border: none; transition: all var(--duration) var(--ease);
    }
    .promo-toggle-btn.deactivate { background: var(--danger-soft); color: var(--danger); }
    .promo-toggle-btn.deactivate:hover { background: var(--danger); color: white; }
    .promo-toggle-btn.activate { background: var(--success-soft); color: var(--success); }
    .promo-toggle-btn.activate:hover { background: var(--success); color: white; }

    .promo-stats { display: flex; gap: 16px; margin-bottom: 20px; }
    .promo-stat-card {
        flex: 1; background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius-md); padding: 14px; text-align: center; box-shadow: var(--shadow-xs);
    }
    .promo-stat-value { font-size: 24px; font-weight: 800; color: var(--accent); }
    .promo-stat-label { font-size: 11px; color: var(--text-tertiary); font-weight: 600; text-transform: uppercase; }

    .table-scroll { overflow-x: auto; }
    .generate-hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }
</style>
@endpush

@section('content')
<h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;">🎟️ Промокоды</h2>

{{-- Статистика --}}
<div class="promo-stats">
    <div class="promo-stat-card">
        <div class="promo-stat-value">{{ $stats['total'] }}</div>
        <div class="promo-stat-label">Всего</div>
    </div>
    <div class="promo-stat-card">
        <div class="promo-stat-value">{{ $stats['active'] }}</div>
        <div class="promo-stat-label">Активных</div>
    </div>
    <div class="promo-stat-card">
        <div class="promo-stat-value">{{ $stats['total_uses'] }}</div>
        <div class="promo-stat-label">Использований</div>
    </div>
</div>

{{-- Форма создания --}}
<div class="card promo-form-card">
    <h3 class="card-title">Создать промокод</h3>

    <div class="promo-form-grid">
        <div class="form-group">
            <label class="form-label">Код</label>
            <input type="text" class="form-input" id="promo-code" placeholder="BONUS5" maxlength="100" style="text-transform: uppercase;">
            <div class="generate-hint">Оставь пустым — сгенерируется автоматически</div>
        </div>
        <div class="form-group">
            <label class="form-label">Тип</label>
            <select class="form-input" id="promo-type">
                <option value="song">Песни</option>
            </select>
        </div>
    </div>

    <div class="promo-form-grid">
        <div class="form-group">
            <label class="form-label">Кол-во песен</label>
            <input type="number" class="form-input" id="promo-songs" value="3" min="1" max="999">
        </div>
        <div class="form-group">
            <label class="form-label">Цена (₽, 0 = бесплатно)</label>
            <input type="number" class="form-input" id="promo-value" value="0" min="0">
        </div>
    </div>

    <div class="promo-form-grid">
        <div class="form-group">
            <label class="form-label">Макс. использований</label>
            <input type="number" class="form-input" id="promo-max-uses" value="1" min="1" max="100000">
        </div>
        <div class="form-group">
            <label class="form-label">Кол-во кодов (для массовой генерации)</label>
            <input type="number" class="form-input" id="promo-quantity" value="1" min="1" max="500">
        </div>
    </div>

    <button class="btn btn-primary btn-block" id="create-btn" onclick="createPromo()" style="margin-top: 8px;">
        Создать
    </button>

    <div id="create-result" style="display:none; margin-top: 12px;"></div>
</div>

{{-- Таблица промокодов --}}
<div class="card">
    <h3 class="card-title">Все промокоды</h3>
    <div class="table-scroll">
        <table class="promo-table">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Песен</th>
                    <th>Цена</th>
                    <th>Исп-й</th>
                    <th>Статус</th>
                    <th>Создан</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="promo-table-body">
                @foreach($promoCodes as $p)
                <tr id="promo-row-{{ $p->id }}">
                    <td class="promo-code-cell">{{ $p->code }}</td>
                    <td>{{ $p->songs_amount ?: $p->songs_count }}</td>
                    <td>{{ $p->value > 0 ? $p->value . ' ₽' : 'Бесплатно' }}</td>
                    <td>{{ $p->current_uses }} / {{ $p->max_uses }}</td>
                    <td>
                        @if($p->is_active)
                            <span class="promo-active">● Активен</span>
                        @else
                            <span class="promo-inactive">● Выкл</span>
                        @endif
                    </td>
                    <td>{{ $p->created_at ? $p->created_at->format('d.m.Y') : '—' }}</td>
                    <td>
                        @if($p->is_active)
                            <button class="promo-toggle-btn deactivate" onclick="togglePromo({{ $p->id }}, 0)">Выкл</button>
                        @else
                            <button class="promo-toggle-btn activate" onclick="togglePromo({{ $p->id }}, 1)">Вкл</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($promoCodes->hasPages())
        <div class="pagination" style="margin-top: 16px;">
            {{ $promoCodes->links('pagination::bootstrap-4') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function createPromo() {
    const btn = document.getElementById('create-btn');
    const resultEl = document.getElementById('create-result');
    btn.disabled = true; btn.textContent = 'Создаю...';
    resultEl.style.display = 'none';

    const body = {
        code: document.getElementById('promo-code').value.trim() || null,
        type: document.getElementById('promo-type').value,
        songs_amount: parseInt(document.getElementById('promo-songs').value) || 1,
        value: parseInt(document.getElementById('promo-value').value) || 0,
        max_uses: parseInt(document.getElementById('promo-max-uses').value) || 1,
        quantity: parseInt(document.getElementById('promo-quantity').value) || 1,
    };

    try {
        const r = await fetch('/api/admin/promo/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Ошибка');

        resultEl.className = 'alert alert-success';
        if (d.codes && d.codes.length > 1) {
            resultEl.innerHTML = `✅ Создано ${d.codes.length} промокодов:<br><code>${d.codes.join(', ')}</code>`;
        } else {
            resultEl.textContent = `✅ Промокод ${d.codes[0]} создан!`;
        }
        resultEl.style.display = 'block';

        // Перезагрузить через 1.5 сек
        setTimeout(() => window.location.reload(), 1500);

    } catch (e) {
        resultEl.className = 'alert alert-error';
        resultEl.textContent = '❌ ' + e.message;
        resultEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Создать';
    }
}

async function togglePromo(id, active) {
    try {
        const r = await fetch('/api/admin/promo/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: JSON.stringify({ id, is_active: active }),
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Ошибка');
        window.location.reload();
    } catch (e) { alert('Ошибка: ' + e.message); }
}
</script>
@endpush