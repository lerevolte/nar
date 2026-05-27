@extends('layouts.app')

@section('title', 'Заявка #' . $ticket->id)

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <a href="{{ route('admin.support.index') }}" style="color:var(--text-tertiary);font-size:14px;">← Назад</a>
        <h2 style="font-size:22px;font-weight:800;flex:1;">Заявка #{{ $ticket->id }}</h2>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="form-section" style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;">
        <div style="margin-bottom:16px;">
            <span style="font-size:12px;color:var(--text-tertiary);">Email</span>
            <div style="font-size:16px;font-weight:600;">{{ $ticket->email }}</div>
        </div>

        @if($ticket->user_id)
        <div style="margin-bottom:16px;">
            <span style="font-size:12px;color:var(--text-tertiary);">User ID</span>
            <div>{{ $ticket->user_id }}</div>
        </div>
        @endif

        <div style="margin-bottom:16px;">
            <span style="font-size:12px;color:var(--text-tertiary);">IP</span>
            <div style="font-size:13px;">{{ $ticket->ip }}</div>
        </div>

        <div style="margin-bottom:16px;">
            <span style="font-size:12px;color:var(--text-tertiary);">Дата</span>
            <div>{{ $ticket->created_at->format('d.m.Y H:i:s') }}</div>
        </div>

        <div style="margin-bottom:20px;">
            <span style="font-size:12px;color:var(--text-tertiary);">Сообщение</span>
            <div style="background:#f8fafc;padding:16px;border-radius:10px;margin-top:6px;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $ticket->message }}</div>
        </div>

        @if($ticket->attached_files)
        <div style="margin-bottom:20px;">
            <span style="font-size:12px;color:var(--text-tertiary);">Прикреплённые файлы</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
                @foreach(explode(',', $ticket->attached_files) as $file)
                    <a href="/uploads/support/{{ $file }}" target="_blank"
                       style="padding:6px 12px;background:var(--accent-soft);color:var(--accent);border-radius:6px;font-size:13px;text-decoration:none;">
                        📎 {{ $file }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.support.update', $ticket->id) }}" style="margin-top:16px;">
        @csrf
        <div class="form-section" style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:14px;">Ответ / Управление</h3>

            <div class="form-group">
                <label class="form-label">Статус</label>
                <select name="status" class="form-input">
                    <option value="new" {{ $ticket->status === 'new' ? 'selected' : '' }}>🟢 Новая</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>🟡 В работе</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>⚫ Закрыта</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Комментарий админа</label>
                <textarea name="admin_reply" class="form-textarea" rows="4">{{ old('admin_reply', $ticket->admin_reply) }}</textarea>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">💾 Сохранить</button>
                <form method="POST" action="{{ route('admin.support.destroy', $ticket->id) }}" style="display:inline;" onsubmit="return confirm('Удалить заявку?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary" style="color:var(--danger);">🗑 Удалить</button>
                </form>
            </div>
        </div>
    </form>
</div>
@endsection